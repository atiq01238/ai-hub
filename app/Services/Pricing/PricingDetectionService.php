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
                        $stats['unchanged']++;
                        continue;
                    }

                    $change = DetectedPriceChange::query()
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
                    $this->notify($change);
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
                'User-Agent' => 'AI-Hub-Pricing-Monitor/1.0 (+pricing verification)',
                'Accept-Language' => 'en-US,en;q=0.9',
            ])
            ->connectTimeout(8)
            ->timeout(18)
            ->retry(2, 500, throw: false)
            ->get($source->source_url);

        if (! $response->successful()) {
            throw new RuntimeException("Source returned HTTP {$response->status()}.");
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
        $text = html_entity_decode(strip_tags(preg_replace('/<(script|style)[^>]*>.*?<\/\\1>/is', ' ', $html) ?? $html));
        $text = preg_replace('/\s+/', ' ', $text) ?? $text;
        $needle = trim($source->plan->plan_name);
        $position = $needle !== '' ? stripos($text, $needle) : false;

        $window = $position !== false
            ? substr($text, max(0, $position - 250), 900)
            : substr($text, 0, 5000);

        if ($source->metric === 'api_price_label') {
            if (preg_match('/(?:USD\s*)?\$\s*\d+(?:\.\d+)?\s*\/(?:\s*1M|\s*million|\s*1K|\s*1k)?[^,.<]{0,30}/i', $window, $m)) {
                return trim($m[0]);
            }
        }

        if (preg_match('/(?:USD\s*)?\$\s*([0-9][0-9,]*(?:\.\d+)?)/i', $window, $m)) {
            return $this->cleanValue($m[1]);
        }

        if (preg_match('/([0-9][0-9,]*(?:\.\d+)?)\s*(?:USD|US dollars?)/i', $window, $m)) {
            return $this->cleanValue($m[1]);
        }

        throw new RuntimeException('Automatic extractor could not confidently find a price. Use Regex or JSON Path for this source.');
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

        return abs((float) $current - (float) $detected) < 0.00001;
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
