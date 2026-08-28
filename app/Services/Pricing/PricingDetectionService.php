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
                } catch (UnsafeAutomaticPricingExtraction $e) {
                    $stats['failed']++;
                    $source->forceFill([
                        'last_checked_at' => now(),
                        'last_check_status' => 'failed',
                        'last_check_message' => Str::limit('Safe skip: ' . $e->getMessage(), 1000),
                    ])->save();

                    // If an earlier loose extractor created a pending review item from this
                    // exact source, remove it from the human queue once the safer extractor
                    // determines that the page cannot be interpreted unambiguously.
                    $this->invalidateUnsafePendingChange($source, $e->getMessage());
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

        if (in_array($source->metric, ['monthly_price', 'yearly_price'], true)) {
            // Free plan cards often contain large dollar-denominated trial credits (for example
            // "$100 credit") that are not subscription prices. Strong "Free" plan semantics
            // must win before any currency candidate is considered.
            if ($this->isExplicitlyFreePlan($window, $source, $needle)) {
                return '0';
            }

            $candidate = $this->selectRecurringPriceCandidate($window, $source);
            if ($candidate !== null) {
                return $candidate;
            }

            throw new UnsafeAutomaticPricingExtraction(
                'No unambiguous recurring subscription price was found in this plan block. Credits, usage rates and promotional amounts are intentionally ignored. Configure a Regex/JSON Path rule or verify this source manually.'
            );
        }

        if ($source->metric === 'api_price_label') {
            return $this->extractApiPriceLabel($window, $source);
        }

        throw new RuntimeException('Unsupported pricing metric.');
    }

    private function selectRecurringPriceCandidate(string $window, PricingSource $source): ?string
    {
        $candidates = $this->currencyCandidates($window);
        if ($candidates === []) {
            return null;
        }

        $usable = [];
        foreach ($candidates as $candidate) {
            if ($this->isPromotionalAmount($window, $candidate) || $this->isUsageRateAmount($window, $candidate)) {
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

            $usable[] = $candidate;
        }

        if ($usable === []) {
            return null;
        }

        $distinct = [];
        foreach ($usable as $candidate) {
            $distinct[number_format((float) $candidate['value'], 6, '.', '')] = true;
        }

        // Pricing pages with Monthly/Annual toggles frequently expose both values in the same
        // server-rendered HTML. Once tags are flattened there is no provider-independent way to
        // know which number belongs to the month-to-month plan and which is the annual effective
        // monthly rate (or an old struck-through value). Guessing here caused Runway, Replit and
        // Descript false positives. Fail safely instead and let a source-specific Regex/JSON rule
        // handle these cards when automatic monitoring is required.
        if (count($distinct) > 1 && $this->hasBillingVariantContext($window)) {
            throw new UnsafeAutomaticPricingExtraction(
                'The plan exposes multiple billing variants (monthly/annual or discounted prices). Automatic extraction refused to guess which amount is the regular subscription price.'
            );
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

        if ($second
            && ! $this->numericValuesMatch($best['value'], $second['value'])
            && abs($best['score'] - $second['score']) <= 1) {
            throw new UnsafeAutomaticPricingExtraction(
                'Automatic extraction found multiple plausible subscription prices and refused to guess.'
            );
        }

        if (count($usable) > 1 && $best['score'] === 0) {
            throw new UnsafeAutomaticPricingExtraction(
                'Automatic extraction found multiple unlabeled currency amounts and refused to guess.'
            );
        }

        return $best['value'];
    }

    private function extractApiPriceLabel(string $window, PricingSource $source): string
    {
        $current = trim((string) $this->currentValue($source));

        // "Custom", "Contact sales", "Let's talk" etc. are pricing states, not numeric API
        // rates. Do not let a random feature price elsewhere on the Enterprise card overwrite it.
        if ($current !== '' && $this->isCustomPriceLabel($current) && $this->hasCustomPricingContext($window)) {
            return $current;
        }

        $rates = $this->apiRateCandidates($window);
        if ($rates === []) {
            throw new UnsafeAutomaticPricingExtraction(
                'No clear usage-rate expression was found for this API price label.'
            );
        }

        $currentFingerprint = $this->apiRateFingerprint($current);
        if ($currentFingerprint !== null) {
            foreach ($rates as $rate) {
                $fingerprint = $this->apiRateFingerprint($rate['raw']);
                if ($fingerprint !== null && $fingerprint === $currentFingerprint) {
                    // Keep the curated label text when the official page still contains the same
                    // numeric rate/unit. This prevents harmless wording changes from becoming
                    // price-change review items.
                    return $current;
                }
            }
        }

        $unique = [];
        foreach ($rates as $rate) {
            $fingerprint = $this->apiRateFingerprint($rate['raw']);
            if ($fingerprint !== null) {
                $unique[$fingerprint] = $rate['raw'];
            }
        }

        if (count($unique) !== 1) {
            throw new UnsafeAutomaticPricingExtraction(
                'Multiple different API usage rates were found in this plan block. A source-specific Regex/JSON Path rule is required.'
            );
        }

        return $this->cleanValue((string) array_values($unique)[0]);
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
        $before = mb_strtolower(substr($window, max(0, $candidate['position'] - 70), min(70, $candidate['position'])));
        $after = mb_strtolower(substr($window, $candidate['position'] + $candidate['length'], 70));

        if (preg_match('/(?:save|saves|saving|savings|discount|discounted|coupon|promo(?:tion)?|deal|cashback|credit|credits|includes?|included|worth|value)\s*(?:up\s+to\s*)?[^.!?]{0,24}$/i', $before)) {
            return true;
        }

        if (preg_match('/^\s*(?:off|discount|discounted|saving|savings|cashback|credit|credits|free\b|in\s+credits?\b|of\s+(?:monthly\s+)?credits?\b)/i', $after)) {
            return true;
        }

        if (preg_match('/(?:save|saves|saving|savings)[^.!?]{0,20}$/i', $before)
            && preg_match('/^\s*(?:\/|per\s+)?year\b/i', $after)) {
            return true;
        }

        return false;
    }

    private function isUsageRateAmount(string $window, array $candidate): bool
    {
        $after = mb_strtolower(substr($window, $candidate['position'] + $candidate['length'], 60));

        // These are metered API/usage rates, not monthly/yearly subscription prices.
        return (bool) preg_match(
            '/^\s*(?:\/\s*|per\s+)(?:hr|hour|minute|min|second|sec|1k\s*(?:characters?|tokens?)?|1m\s*(?:tokens?)?|million\s+(?:tokens?|characters?)|thousand\s+(?:tokens?|characters?)|request|image|generation|credit|characters?|tokens?)\b/i',
            $after
        );
    }

    private function isExplicitlyFreePlan(string $window, PricingSource $source, string $needle): bool
    {
        $head = mb_substr(trim($window), 0, 190);
        $current = $this->currentValue($source);
        $currentlyFree = $current !== null && $this->numericValuesMatch($current, '0');
        $planNamedFree = preg_match('/\bfree\b/i', $needle) === 1;

        if ($planNamedFree && preg_match('/\bfree\b/i', $head)) {
            return true;
        }

        if (! $currentlyFree) {
            return false;
        }

        $quoted = preg_quote($needle, '/');
        if (preg_match('/^\s*' . $quoted . '\b.{0,95}\b(?:free|free\s+forever|no\s+cost)\b/i', $head)) {
            return true;
        }

        if (preg_match('/^\s*' . $quoted . '\b.{0,120}\b(?:no\s+credit\s+card|required\s+no\s+card|start\s+for\s+free|try\s+for\s+free)\b/i', $head)) {
            return true;
        }

        return false;
    }

    private function hasBillingVariantContext(string $window): bool
    {
        $annual = (bool) preg_match('/\b(?:annual|annually|yearly|billed\s+annually|billed\s+yearly)\b/i', $window);
        $monthly = (bool) preg_match('/\bmonthly\b|\/\s*(?:month|mo)\b|per\s+(?:person\s+)?month\b/i', $window);
        $discount = (bool) preg_match('/\b(?:save|saving|savings|discount|discounted|off)\b/i', $window);

        return $annual && ($monthly || $discount);
    }

    private function isCustomPriceLabel(string $value): bool
    {
        return (bool) preg_match('/\b(?:custom|contact\s+(?:us|sales)|talk\s+to\s+sales|let[’\']?s\s+talk|get\s+in\s+touch|request\s+(?:a\s+)?quote|quote)\b/i', $value);
    }

    private function hasCustomPricingContext(string $window): bool
    {
        return (bool) preg_match('/\b(?:custom\s+pricing|custom|contact\s+(?:us|sales)|talk\s+to\s+sales|let[’\']?s\s+talk|get\s+in\s+touch|book\s+(?:a\s+)?demo|request\s+(?:a\s+)?quote)\b/i', mb_substr($window, 0, 260));
    }

    private function apiRateCandidates(string $window): array
    {
        $rates = [];

        if (! preg_match_all('/(?:USD\s*)?\$\s*[0-9][0-9,]*(?:\.\d+)?/i', $window, $matches, PREG_OFFSET_CAPTURE)) {
            return [];
        }

        foreach ($matches[0] as $match) {
            $position = $match[1];
            $raw = substr($window, $position, min(70, strlen($window) - $position));
            $fingerprint = $this->apiRateFingerprint($raw);
            if ($fingerprint === null) {
                continue;
            }

            $rates[] = [
                'raw' => trim(preg_split('/[|;,]/', $raw, 2)[0]),
                'position' => $position,
            ];
        }

        return $rates;
    }

    private function apiRateFingerprint(string $value): ?string
    {
        if (! preg_match('/(?:USD\s*)?\$\s*([0-9][0-9,]*(?:\.\d+)?)/i', $value, $amount)) {
            return null;
        }

        $normalized = mb_strtolower($value);
        $unit = match (true) {
            (bool) preg_match('/(?:\/|per\s+)(?:hr|hour)\b/i', $normalized) => 'hour',
            (bool) preg_match('/(?:\/|per\s+)(?:min|minute)\b/i', $normalized) => 'minute',
            (bool) preg_match('/(?:\/|per\s+)(?:sec|second)\b/i', $normalized) => 'second',
            (bool) preg_match('/(?:\/|per\s+)(?:1m|million)\s*tokens?\b/i', $normalized) => '1m_tokens',
            (bool) preg_match('/(?:\/|per\s+)(?:1k|thousand)\s*tokens?\b/i', $normalized) => '1k_tokens',
            (bool) preg_match('/(?:\/|per\s+)(?:1k|thousand)\s*characters?\b/i', $normalized) => '1k_characters',
            (bool) preg_match('/(?:\/|per\s+)characters?\b/i', $normalized) => 'character',
            (bool) preg_match('/(?:\/|per\s+)requests?\b/i', $normalized) => 'request',
            (bool) preg_match('/(?:\/|per\s+)images?\b/i', $normalized) => 'image',
            (bool) preg_match('/(?:\/|per\s+)generations?\b/i', $normalized) => 'generation',
            (bool) preg_match('/(?:\/|per\s+)credits?\b/i', $normalized) => 'credit',
            default => null,
        };

        if ($unit === null) {
            return null;
        }

        $number = number_format((float) str_replace(',', '', $amount[1]), 8, '.', '');
        $number = rtrim(rtrim($number, '0'), '.');

        return $number . '|' . $unit;
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
            if (Str::squish((string) $current) === Str::squish($detected)) {
                return true;
            }

            $leftFingerprint = $this->apiRateFingerprint((string) $current);
            $rightFingerprint = $this->apiRateFingerprint($detected);

            return $leftFingerprint !== null
                && $rightFingerprint !== null
                && $leftFingerprint === $rightFingerprint;
        }

        if ($current === null || $current === '') {
            return false;
        }

        return $this->numericValuesMatch($current, $detected);
    }

    private function invalidateUnsafePendingChange(PricingSource $source, string $reason): void
    {
        DetectedPriceChange::query()
            ->where('pricing_source_id', $source->id)
            ->where('pricing_plan_id', $source->pricing_plan_id)
            ->where('metric', $source->metric)
            ->where('status', 'pending')
            ->update([
                'status' => 'rejected',
                'review_note' => Str::limit(
                    'Automatically invalidated by the safer pricing extractor: ' . $reason,
                    1000
                ),
                'reviewed_by' => null,
                'reviewed_at' => now(),
                'updated_at' => now(),
            ]);
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

class UnsafeAutomaticPricingExtraction extends RuntimeException
{
}
