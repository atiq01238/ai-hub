<?php

namespace App\Services\Pricing;

use App\Models\AppNotification;
use App\Models\DetectedPriceChange;
use App\Models\NotificationRule;
use App\Models\PricingSource;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

class PricingDetectionService
{
    public const METRICS = ['monthly_price', 'yearly_price', 'api_price_label'];

    public function scan(?int $sourceId = null, ?int $planId = null): array
    {
        $query = PricingSource::query()
            ->with(['plan.tool'])
            ->where('enabled', true)
            ->when($sourceId, fn ($q) => $q->whereKey($sourceId))
            ->when($planId, fn ($q) => $q->where('pricing_plan_id', $planId));

        $stats = ['checked' => 0, 'changes' => 0, 'unchanged' => 0, 'failed' => 0];

        $query->orderBy('id')->chunkById(50, function ($sources) use (&$stats) {
            foreach ($sources as $source) {
                $stats['checked']++;

                try {
                    $detected = $this->fetchValue($source);
                    $source->forceFill([
                        'last_checked_at' => now(),
                        'last_check_status' => 'ok',
                        'last_check_message' => null,
                        'last_detected_value' => $detected,
                    ])->save();

                    $current = $this->currentValue($source);

                    if ($this->valuesMatch($source->metric, $current, $detected)) {
                        $this->closeStalePendingChange($source, $current, $detected);
                        $stats['unchanged']++;
                        continue;
                    }

                    // A pending detection belongs to one exact official source. Do not let
                    // a second source for the same plan/metric overwrite another source's review item.
                    $change = DetectedPriceChange::query()
                        ->where('pricing_source_id', $source->id)
                        ->where('pricing_plan_id', $source->pricing_plan_id)
                        ->where('metric', $source->metric)
                        ->where('status', 'pending')
                        ->first();

                    $payload = [
                        'pricing_source_id' => $source->id,
                        'tool_id' => $source->plan->tool_id,
                        'current_value' => $current,
                        'detected_value' => $detected,
                        'currency' => $source->currency,
                        'source_url' => $source->source_url,
                        'detected_at' => now(),
                    ];

                    $shouldNotify = ! $change || ! $this->valuesMatch(
                        $source->metric,
                        (string) $change->detected_value,
                        $detected
                    );

                    if ($change) {
                        $change->update($payload);
                    } else {
                        $change = DetectedPriceChange::create($payload + [
                            'pricing_plan_id' => $source->pricing_plan_id,
                            'metric' => $source->metric,
                            'status' => 'pending',
                        ]);
                    }

                    $stats['changes']++;
                    if ($shouldNotify) {
                        $this->notify($change);
                    }
                } catch (\Throwable $e) {
                    $stats['failed']++;
                    $source->forceFill([
                        'last_checked_at' => now(),
                        'last_check_status' => 'failed',
                        'last_check_message' => Str::limit($e->getMessage(), 1000),
                    ])->save();
                }
            }
        });

        return $stats;
    }

    public function fetchValue(PricingSource $source): string
    {
        $response = Http::withHeaders([
                'User-Agent' => 'AI-Orbit-Pricing-Monitor/1.0 (+pricing verification)',
                'Accept-Language' => 'en-US,en;q=0.9',
            ])
            ->connectTimeout(5)
            ->timeout(12)
            ->get($source->source_url);

        if (! $response->successful()) {
            $status = $response->status();

            $message = match ($status) {
                401, 403 => "Source returned HTTP {$status}; the provider blocks automated monitoring. Verify this source manually or replace it with an accessible official endpoint.",
                429 => 'Source returned HTTP 429 (rate limited). Try again later or reduce monitoring frequency.',
                default => "Source returned HTTP {$status}.",
            };

            throw new RuntimeException($message);
        }

        return match ($source->source_type) {
            'json_path' => $this->extractJsonPath($response->json(), $source->extraction_rule),
            'regex' => $this->extractRegex($response->body(), $source->extraction_rule),
            default => $this->extractAutomatically($response->body(), $source),
        };
    }

    private function extractJsonPath(mixed $json, ?string $path): string
    {
        if (! $path) {
            throw new RuntimeException('JSON path is required for this source.');
        }

        $value = data_get($json, $path);
        if ($value === null || is_array($value) || is_object($value)) {
            throw new RuntimeException("No scalar value found at JSON path: {$path}");
        }

        return $this->cleanValue((string) $value);
    }

    private function extractRegex(string $body, ?string $pattern): string
    {
        if (! $pattern) {
            throw new RuntimeException('Regex extraction rule is required for this source.');
        }

        $result = @preg_match($pattern, $body, $matches);
        if ($result !== 1) {
            throw new RuntimeException('Regex did not find a pricing value.');
        }

        $value = $matches['price'] ?? $matches[1] ?? $matches[0] ?? null;
        if ($value === null) {
            throw new RuntimeException('Regex matched but did not return a value.');
        }

        return $this->cleanValue((string) $value);
    }

    private function extractAutomatically(string $html, PricingSource $source): string
    {
        $text = preg_replace('/<(script|style)[^>]*>.*?<\/\1>/is', ' ', $html) ?? $html;
        $text = preg_replace('/<\/?(?:div|section|article|li|p|br|h[1-6]|tr|td|th)[^>]*>/i', ' ', $text) ?? $text;
        $text = html_entity_decode(strip_tags($text));
        $text = preg_replace('/\s+/', ' ', $text) ?? $text;
        $needle = trim((string) $source->plan->plan_name);

        if ($needle === '') {
            throw new RuntimeException('Automatic extraction requires a pricing plan name.');
        }

        $window = $this->planBoundedWindow($text, $source, $needle);

        if ($source->metric === 'api_price_label') {
            if (preg_match('/(?:USD\s*)?\$\s*\d+(?:\.\d+)?\s*\/(?:\s*1M|\s*million|\s*1K|\s*1k)?[^,.<]{0,30}/i', $window, $m)) {
                return trim($m[0]);
            }
        }

        if (in_array($source->metric, ['monthly_price', 'yearly_price'], true)) {
            $candidate = $this->selectRecurringPriceCandidate($window, $source);
            if ($candidate !== null) {
                return $candidate;
            }
        }

        if (in_array($source->metric, ['monthly_price', 'yearly_price'], true)
            && preg_match('/^\s*' . preg_quote($needle, '/') . '\b.{0,120}\bfree\b/i', $window)) {
            return '0';
        }

        throw new RuntimeException(
            'Automatic extractor could not confidently find the requested price inside the selected plan block. Promotional savings, discounts and ambiguous amounts are intentionally ignored. Use Regex or JSON Path for this source.'
        );
    }

    private function selectRecurringPriceCandidate(string $window, PricingSource $source): ?string
    {
        $candidates = $this->currencyCandidates($window);
        if ($candidates === []) {
            return null;
        }

        $usable = [];
        foreach ($candidates as $candidate) {
            if ($this->isPromotionalAmount($window, $candidate)) {
                continue;
            }

            $candidate['monthly_context'] = $this->hasPeriodContext($window, $candidate, 'month');
            $candidate['yearly_context'] = $this->hasPeriodContext($window, $candidate, 'year');
            $candidate['score'] = 0;

            if ($source->metric === 'monthly_price') {
                $candidate['score'] += $candidate['monthly_context'] ? 8 : 0;
                $candidate['score'] -= $candidate['yearly_context'] ? 9 : 0;
            } else {
                $candidate['score'] += $candidate['yearly_context'] ? 8 : 0;
                $candidate['score'] -= $candidate['monthly_context'] ? 9 : 0;
            }

            $unit = mb_strtolower(trim((string) $source->unit));
            if ($source->metric === 'monthly_price' && preg_match('/\bmonth(?:ly)?\b|\/\s*mo\b/i', $unit)) {
                $candidate['score'] += $candidate['monthly_context'] ? 2 : 0;
            }
            if ($source->metric === 'yearly_price' && preg_match('/\byear(?:ly)?\b|\bannual(?:ly)?\b/i', $unit)) {
                $candidate['score'] += $candidate['yearly_context'] ? 2 : 0;
            }

            $current = $this->currentValue($source);
            if ($current !== null && $this->numericValuesMatch($current, $candidate['value'])) {
                // A currently stored value that still appears on the official plan card is useful
                // supporting evidence, but explicit billing-period language normally outranks it.
                $candidate['score'] += 2;
            }

            $usable[] = $candidate;
        }

        if ($usable === []) {
            return null;
        }

        // Common annual-billing card pattern:
        //   "Billed annually. Save $36/year ... $15 $12 /month"
        // The first non-promotional price is the regular month-to-month price; the second
        // is the discounted monthly equivalent when paying annually. For a monthly_price
        // source we monitor the regular monthly rate, not the annual saving or annual equivalent.
        if ($source->metric === 'monthly_price' && $this->hasAnnualDiscountContext($window)) {
            foreach ($usable as $monthlyIndex => $candidate) {
                if (! $candidate['monthly_context'] || $monthlyIndex === 0) {
                    continue;
                }

                $previousIndex = $monthlyIndex - 1;
                $distance = $candidate['position'] - ($usable[$previousIndex]['position'] + $usable[$previousIndex]['length']);
                if ($distance >= 0 && $distance <= 100 && ! $usable[$previousIndex]['yearly_context']) {
                    $usable[$previousIndex]['score'] += 11;
                    $usable[$monthlyIndex]['score'] -= 2;
                }
                break;
            }
        }

        usort($usable, function (array $a, array $b): int {
            if ($a['score'] === $b['score']) {
                return $a['position'] <=> $b['position'];
            }

            return $b['score'] <=> $a['score'];
        });

        $best = $usable[0];
        $second = $usable[1] ?? null;

        if ($best['score'] < 0) {
            return null;
        }

        // Do not manufacture a price change when two different amounts are equally plausible.
        // Failing the source check is safer than sending a false value to the human review queue.
        if ($second
            && ! $this->numericValuesMatch($best['value'], $second['value'])
            && abs($best['score'] - $second['score']) <= 1) {
            throw new RuntimeException(
                'Automatic extractor found multiple plausible prices for this plan and refused to guess. Configure a Regex or JSON Path extraction rule.'
            );
        }

        if (count($usable) > 1 && $best['score'] === 0) {
            throw new RuntimeException(
                'Automatic extractor found multiple unlabeled currency amounts for this plan and refused to guess. Configure a Regex or JSON Path extraction rule.'
            );
        }

        return $best['value'];
    }

    private function currencyCandidates(string $window): array
    {
        $candidates = [];

        if (preg_match_all('/(?:USD\s*)?\$\s*([0-9][0-9,]*(?:\.\d+)?)/i', $window, $matches, PREG_OFFSET_CAPTURE)) {
            foreach ($matches[0] as $index => $full) {
                $candidates[] = [
                    'value' => $this->cleanValue($matches[1][$index][0]),
                    'position' => $full[1],
                    'length' => strlen($full[0]),
                ];
            }
        }

        if (preg_match_all('/([0-9][0-9,]*(?:\.\d+)?)\s*(?:USD|US dollars?)/i', $window, $matches, PREG_OFFSET_CAPTURE)) {
            foreach ($matches[0] as $index => $full) {
                $position = $full[1];
                $duplicate = false;
                foreach ($candidates as $existing) {
                    if (abs($existing['position'] - $position) <= 4) {
                        $duplicate = true;
                        break;
                    }
                }

                if (! $duplicate) {
                    $candidates[] = [
                        'value' => $this->cleanValue($matches[1][$index][0]),
                        'position' => $position,
                        'length' => strlen($full[0]),
                    ];
                }
            }
        }

        usort($candidates, fn (array $a, array $b): int => $a['position'] <=> $b['position']);

        return $candidates;
    }

    private function isPromotionalAmount(string $window, array $candidate): bool
    {
        $before = mb_strtolower(substr($window, max(0, $candidate['position'] - 55), min(55, $candidate['position'])));
        $after = mb_strtolower(substr($window, $candidate['position'] + $candidate['length'], 45));

        if (preg_match('/(?:save|saves|saving|savings|discount|discounted|coupon|promo(?:tion)?|deal|cashback|credit)\s*(?:up\s+to\s*)?[^.!?]{0,18}$/i', $before)) {
            return true;
        }

        if (preg_match('/^\s*(?:off|discount|discounted|saving|savings|cashback)\b/i', $after)) {
            return true;
        }

        // Explicit "Save $X/year" / "Save $X per year" style values are savings,
        // not the plan's yearly price.
        if (preg_match('/(?:save|saves|saving|savings)[^.!?]{0,20}$/i', $before)
            && preg_match('/^\s*(?:\/|per\s+)?year\b/i', $after)) {
            return true;
        }

        return false;
    }

    private function hasPeriodContext(string $window, array $candidate, string $period): bool
    {
        $after = substr($window, $candidate['position'] + $candidate['length'], 45);
        $before = substr($window, max(0, $candidate['position'] - 45), min(45, $candidate['position']));

        if ($period === 'month') {
            return (bool) preg_match('/^\s*(?:\/\s*|per\s+)?(?:month|mo)\b/i', $after)
                || (bool) preg_match('/(?:per\s+month|monthly(?:\s+price)?)\s*[:\-]?\s*$/i', $before);
        }

        return (bool) preg_match('/^\s*(?:\/\s*|per\s+)?(?:year|yr)\b/i', $after)
            || (bool) preg_match('/(?:per\s+year|yearly(?:\s+price)?|annual(?:ly)?(?:\s+price)?)\s*[:\-]?\s*$/i', $before);
    }

    private function hasAnnualDiscountContext(string $window): bool
    {
        $hasAnnualBilling = (bool) preg_match('/\bbilled\s+annually\b|\bannual(?:ly)?\s+billing\b|\byearly\s+billing\b/i', $window);
        $hasSaving = (bool) preg_match('/\b(?:save|saves|saving|savings|discount|off)\b/i', $window);

        return $hasAnnualBilling && $hasSaving;
    }

    private function numericValuesMatch(string $left, string $right): bool
    {
        return abs((float) $this->cleanValue($left) - (float) $this->cleanValue($right)) < 0.00001;
    }

    private function planBoundedWindow(string $text, PricingSource $source, string $needle): string
    {
        $offset = 0;
        $best = null;

        while (($position = stripos($text, $needle, $offset)) !== false) {
            $candidate = substr($text, $position, 520);

            // Prefer an occurrence where a currency-marked price follows the plan name.
            if (preg_match('/(?:USD\s*)?\$\s*[0-9]|[0-9][0-9,.]*\s*(?:USD|US dollars?)/i', $candidate)) {
                $best = $position;
                break;
            }

            $best ??= $position;
            $offset = $position + max(1, strlen($needle));
        }

        if ($best === null) {
            throw new RuntimeException("Plan '{$needle}' was not found on the pricing source page. Use Regex or JSON Path for this source.");
        }

        $end = min(strlen($text), $best + 520);
        $tool = $source->plan->tool;

        if ($tool) {
            $tool->loadMissing('pricingPlans');
            foreach ($tool->pricingPlans as $otherPlan) {
                $otherName = trim((string) $otherPlan->plan_name);
                if ($otherPlan->id === $source->plan->id || $otherName === '') {
                    continue;
                }

                $next = stripos($text, $otherName, $best + strlen($needle));
                if ($next !== false && $next < $end) {
                    $end = $next;
                }
            }
        }

        return trim(substr($text, $best, max(0, $end - $best)));
    }

    private function cleanValue(string $value): string
    {
        return trim(preg_replace('/\s+/', ' ', str_replace(["\xc2\xa0", ','], [' ', ''], $value)) ?? $value);
    }

    private function currentValue(PricingSource $source): ?string
    {
        $value = $source->plan->{$source->metric};
        return $value === null ? null : (string) $value;
    }

    private function valuesMatch(string $metric, ?string $current, string $detected): bool
    {
        if ($metric === 'api_price_label') {
            return Str::squish((string) $current) === Str::squish($detected);
        }

        if ($current === null || $current === '') {
            return false;
        }

        return $this->numericValuesMatch($current, $detected);
    }

    private function closeStalePendingChange(PricingSource $source, ?string $current, string $detected): void
    {
        DetectedPriceChange::query()
            ->where('pricing_source_id', $source->id)
            ->where('pricing_plan_id', $source->pricing_plan_id)
            ->where('metric', $source->metric)
            ->where('status', 'pending')
            ->update([
                'status' => 'rejected',
                'review_note' => 'Automatically invalidated after a later official-source scan matched the current stored value.',
                'reviewed_by' => null,
                'reviewed_at' => now(),
                'current_value' => $current,
                'detected_value' => $detected,
                'detected_at' => now(),
                'updated_at' => now(),
            ]);
    }

    private function notify(DetectedPriceChange $change): void
    {
        if (! NotificationRule::isEnabled('price_change')) {
            return;
        }

        $change->loadMissing('tool', 'plan');
        AppNotification::broadcast(
            'tag',
            'warn',
            'External price change detected',
            ($change->tool->name ?? 'A tool') . ' ' . ($change->plan->plan_name ?? '') .
            " has a pending {$change->metric} change: {$change->current_value} → {$change->detected_value}. Review before publishing."
        );
    }
}
