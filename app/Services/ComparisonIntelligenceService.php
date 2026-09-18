<?php

namespace App\Services;

use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class ComparisonIntelligenceService
{
    public function build(Collection $items, string $type): array
    {
        $items = $items->values();
        $itemsById = $items->keyBy(fn ($item) => (int) $item->id);

        $benchmarkMatrix = [];
        $benchmarkMeta = [];
        $latestEvidenceAt = null;
        $benchmarkLatestByItem = [];
        $verifiedBenchmarkItemIds = [];

        foreach ($items as $item) {
            $rows = $item->benchmarkResults()
                ->with('benchmark')
                ->where('verified', true)
                ->where('status', 'verified')
                ->whereNotNull('score')
                ->orderByDesc('tested_at')
                ->orderByDesc('verified_at')
                ->orderByDesc('id')
                ->get()
                ->unique('benchmark_id');

            if ($rows->isNotEmpty()) {
                $verifiedBenchmarkItemIds[] = (int) $item->id;
            }

            foreach ($rows as $result) {
                if (! $result->benchmark) {
                    continue;
                }

                $key = (string) $result->benchmark->slug;
                $benchmarkMeta[$key] = $result->benchmark;
                $benchmarkMatrix[$key][(int) $item->id] = $result;

                $candidateAt = $this->latestDate([
                    $result->verified_at,
                    $result->tested_at,
                    $result->updated_at,
                ]);

                $latestEvidenceAt = $this->maxDate($latestEvidenceAt, $candidateAt);
                $benchmarkLatestByItem[(int) $item->id] = $this->maxDate(
                    $benchmarkLatestByItem[(int) $item->id] ?? null,
                    $candidateAt
                );
            }
        }

        $sharedBenchmarkMatrix = [];
        $additionalBenchmarkMatrix = [];
        foreach ($benchmarkMatrix as $key => $scores) {
            if (count($scores) >= 2) {
                $sharedBenchmarkMatrix[$key] = $scores;
            } else {
                $additionalBenchmarkMatrix[$key] = $scores;
            }
        }

        $wins = [];
        $ties = [];
        $benchmarkLeaders = [];
        foreach ($items as $item) {
            $wins[(int) $item->id] = 0;
            $ties[(int) $item->id] = 0;
        }

        foreach ($sharedBenchmarkMatrix as $key => $scores) {
            $benchmark = $benchmarkMeta[$key];
            $eligible = collect($scores);

            $bestScore = $benchmark->higher_is_better
                ? $eligible->max('score')
                : $eligible->min('score');

            $leaders = $eligible
                ->filter(fn ($result) => (float) $result->score === (float) $bestScore)
                ->values();

            $leaderIds = $leaders
                ->map(fn ($result) => (int) $result->benchmarkable_id)
                ->values()
                ->all();

            $benchmarkLeaders[$key] = [
                'leader_ids' => $leaderIds,
                'best_score' => $bestScore,
                'tie' => count($leaderIds) > 1,
            ];

            if (count($leaderIds) === 1) {
                $wins[$leaderIds[0]] = ($wins[$leaderIds[0]] ?? 0) + 1;
            } else {
                foreach ($leaderIds as $leaderId) {
                    $ties[$leaderId] = ($ties[$leaderId] ?? 0) + 1;
                }
            }
        }

        $benchmarkGroups = collect(array_keys($sharedBenchmarkMatrix))
            ->groupBy(function (string $key) use ($benchmarkMeta) {
                $category = trim((string) ($benchmarkMeta[$key]->category ?? ''));
                return $category !== '' ? Str::headline($category) : 'Other';
            })
            ->map(fn (Collection $keys) => $keys->values()->all())
            ->all();

        $pricing = [];
        $pricingLatestByItem = [];

        foreach ($items as $item) {
            $id = (int) $item->id;

            if ($type === 'model') {
                $verifiedAt = $this->asDate($item->pricing_verified_at ?? null);
                $pricingLatestByItem[$id] = $verifiedAt;
                $latestEvidenceAt = $this->maxDate($latestEvidenceAt, $verifiedAt);

                $pricing[$id] = [
                    'input' => $item->input_price_per_million,
                    'output' => $item->output_price_per_million,
                    'verified' => $item->input_price_per_million !== null || $item->output_price_per_million !== null,
                    'pricing_type' => $item->pricing_type,
                    'pricing_type_label' => $item->pricing_type_label,
                    'pricing_basis' => $item->pricing_basis,
                    'pricing_unit_label' => $item->pricing_unit_label,
                    'pricing_summary' => $item->pricing_summary,
                    'verification_status' => $item->pricing_verification_status,
                    'verification_label' => $item->pricing_verification_label,
                    'verified_at' => $verifiedAt,
                    'freshness' => $this->freshness($verifiedAt),
                ];
                continue;
            }

            $plans = $item->pricingPlans()
                ->with('sources')
                ->orderByRaw('monthly_price is null')
                ->orderBy('monthly_price')
                ->orderBy('plan_name')
                ->get();

            $verifiedAt = $plans
                ->flatMap(function ($plan) {
                    return collect([$plan->last_verified_at])
                        ->merge($plan->sources->pluck('last_checked_at'));
                })
                ->filter()
                ->map(fn ($date) => $this->asDate($date))
                ->filter()
                ->sortByDesc(fn (CarbonInterface $date) => $date->timestamp)
                ->first();

            $pricingLatestByItem[$id] = $verifiedAt;
            $latestEvidenceAt = $this->maxDate($latestEvidenceAt, $verifiedAt);

            $numericMonthly = $plans
                ->whereNotNull('monthly_price')
                ->map(fn ($plan) => (float) $plan->monthly_price);

            $hasFreePlan = $plans->contains(function ($plan) {
                if ($plan->monthly_price !== null && (float) $plan->monthly_price <= 0.0) {
                    return true;
                }

                $haystack = strtolower(trim((string) $plan->plan_name . ' ' . (string) $plan->billing_type));
                return str_contains($haystack, 'free');
            });

            $pricing[$id] = [
                'plans' => $plans,
                'starting' => $numericMonthly->isNotEmpty() ? $numericMonthly->min() : null,
                'free_plan' => $hasFreePlan,
                'verified' => $plans->isNotEmpty(),
                'verified_at' => $verifiedAt,
                'freshness' => $this->freshness($verifiedAt),
            ];
        }

        $pricedItemCount = collect($pricing)
            ->filter(fn ($row) => (bool) ($row['verified'] ?? false))
            ->count();

        $capabilityCoverage = $this->termCoverage($items, fn ($item) => (array) ($item->capabilities ?? []));
        $useCaseCoverage = $this->relationCoverage($items, 'useCaseTerms');

        $decisionSignals = $this->decisionSignals(
            $items,
            $type,
            $pricing,
            $wins,
            count($sharedBenchmarkMatrix)
        );

        $itemEvidence = [];
        foreach ($items as $item) {
            $id = (int) $item->id;
            $profileAt = $type === 'model'
                ? $this->asDate($item->profile_verified_at ?? null)
                : $this->asDate($item->technicalProfile?->last_reviewed_at ?? null);

            $latestItemAt = $this->latestDate([
                $benchmarkLatestByItem[$id] ?? null,
                $pricingLatestByItem[$id] ?? null,
                $profileAt,
            ]);

            $itemEvidence[$id] = [
                'benchmark_count' => isset($verifiedBenchmarkItemIds) && in_array($id, $verifiedBenchmarkItemIds, true)
                    ? collect($benchmarkMatrix)->filter(fn ($scores) => isset($scores[$id]))->count()
                    : 0,
                'benchmark_latest_at' => $benchmarkLatestByItem[$id] ?? null,
                'pricing_latest_at' => $pricingLatestByItem[$id] ?? null,
                'profile_latest_at' => $profileAt,
                'latest_at' => $latestItemAt,
                'freshness' => $this->freshness($latestItemAt),
            ];
        }

        $snapshot = [
            [
                'label' => 'Items compared',
                'value' => (string) $items->count(),
                'detail' => $type === 'model' ? 'Public AI model profiles' : 'Published AI tool profiles',
                'icon' => 'columns-3',
            ],
            [
                'label' => 'Shared benchmarks',
                'value' => (string) count($sharedBenchmarkMatrix),
                'detail' => count($sharedBenchmarkMatrix) > 0 ? 'Exact verified benchmark overlap' : 'No shared verified benchmark yet',
                'icon' => 'gauge',
            ],
            [
                'label' => 'Pricing coverage',
                'value' => $pricedItemCount . ' / ' . $items->count(),
                'detail' => $type === 'model' ? 'Profiles with structured API pricing' : 'Products with structured pricing plans',
                'icon' => 'badge-dollar-sign',
            ],
            [
                'label' => 'Evidence freshness',
                'value' => $latestEvidenceAt ? $latestEvidenceAt->format('M Y') : 'Profile data',
                'detail' => $latestEvidenceAt ? 'Latest verified pricing or benchmark evidence' : 'No dated verified evidence available',
                'icon' => 'calendar-check-2',
            ],
        ];

        return [
            'benchmarkMatrix' => $benchmarkMatrix,
            'sharedBenchmarkMatrix' => $sharedBenchmarkMatrix,
            'additionalBenchmarkMatrix' => $additionalBenchmarkMatrix,
            'benchmarkMeta' => $benchmarkMeta,
            'benchmarkGroups' => $benchmarkGroups,
            'benchmarkLeaders' => $benchmarkLeaders,
            'sharedBenchmarkKeys' => array_keys($sharedBenchmarkMatrix),
            'wins' => $wins,
            'ties' => $ties,
            'verifiedBenchmarkItemIds' => array_values(array_unique($verifiedBenchmarkItemIds)),
            'pricing' => $pricing,
            'sharedBenchmarkCount' => count($sharedBenchmarkMatrix),
            'snapshot' => $snapshot,
            'decisionSignals' => $decisionSignals,
            'capabilityCoverage' => $capabilityCoverage,
            'useCaseCoverage' => $useCaseCoverage,
            'itemEvidence' => $itemEvidence,
            'evidenceAsOf' => $latestEvidenceAt,
            // No universal winner: Phase 2 exposes factual category signals only.
            'overall' => null,
            'overallVerdict' => [
                'winner_id' => null,
                'confidence' => count($sharedBenchmarkMatrix) >= 3 ? 'evidence-rich' : 'limited',
                'confidence_label' => count($sharedBenchmarkMatrix) >= 3 ? 'Multiple shared benchmarks' : 'Limited shared evidence',
                'shared_benchmarks' => count($sharedBenchmarkMatrix),
                'clear_benchmarks' => array_sum($wins),
                'reason' => 'AI Orbit reports category-level evidence instead of declaring a universal winner.',
            ],
            'metricWinners' => [],
            'valueWinner' => null,
            'valueSignalReason' => null,
        ];
    }

    private function decisionSignals(Collection $items, string $type, array $pricing, array $wins, int $sharedBenchmarkCount): array
    {
        $signals = [];
        $itemsById = $items->keyBy(fn ($item) => (int) $item->id);

        if ($sharedBenchmarkCount > 0) {
            $bestWinCount = collect($wins)->max() ?? 0;
            if ($bestWinCount > 0) {
                $leaderIds = collect($wins)
                    ->filter(fn ($count) => (int) $count === (int) $bestWinCount)
                    ->keys()
                    ->map(fn ($id) => (int) $id)
                    ->values()
                    ->all();

                $signals[] = $this->signal(
                    'Shared benchmark wins',
                    $leaderIds,
                    $itemsById,
                    $bestWinCount . ' win' . ($bestWinCount === 1 ? '' : 's'),
                    $sharedBenchmarkCount . ' exact shared benchmark' . ($sharedBenchmarkCount === 1 ? '' : 's') . ' compared',
                    'trophy'
                );
            }
        }

        if ($type === 'model') {
            $input = [];
            $output = [];
            $context = [];
            $release = [];

            foreach ($items as $item) {
                $id = (int) $item->id;
                if ($item->input_price_per_million !== null) $input[$id] = (float) $item->input_price_per_million;
                if ($item->output_price_per_million !== null) $output[$id] = (float) $item->output_price_per_million;

                $contextValue = $this->parseContextWindow((string) ($item->context_window ?? ''));
                if ($contextValue !== null) $context[$id] = $contextValue;

                if ($item->release_date) {
                    $release[$id] = $item->release_date->timestamp;
                }
            }

            if ($input) {
                $min = min($input);
                $leaders = array_keys(array_filter($input, fn ($value) => (float) $value === (float) $min));
                $signals[] = $this->signal('Lower input price', $leaders, $itemsById, '$' . number_format($min, 2) . ' / 1M', 'Lowest verified input-token price', 'arrow-down-circle');
            }

            if ($output) {
                $min = min($output);
                $leaders = array_keys(array_filter($output, fn ($value) => (float) $value === (float) $min));
                $signals[] = $this->signal('Lower output price', $leaders, $itemsById, '$' . number_format($min, 2) . ' / 1M', 'Lowest verified output-token price', 'arrow-down-circle');
            }

            if ($context) {
                $max = max($context);
                $leaders = array_keys(array_filter($context, fn ($value) => (int) $value === (int) $max));
                $raw = $itemsById->get((int) $leaders[0])?->context_window ?: number_format($max);
                $signals[] = $this->signal('Larger context', $leaders, $itemsById, (string) $raw, 'Largest comparable context window', 'maximize-2');
            }

            if ($release) {
                $max = max($release);
                $leaders = array_keys(array_filter($release, fn ($value) => (int) $value === (int) $max));
                $date = $itemsById->get((int) $leaders[0])?->release_date;
                $signals[] = $this->signal('Newer release', $leaders, $itemsById, $date?->format('M Y') ?? '—', 'Most recent listed release date', 'calendar-days');
            }

            return array_slice($signals, 0, 5);
        }

        $startingPrices = [];
        $freePlanIds = [];
        $apiAvailableIds = [];
        $selfHostIds = [];

        foreach ($items as $item) {
            $id = (int) $item->id;
            if (($pricing[$id]['starting'] ?? null) !== null) {
                $startingPrices[$id] = (float) $pricing[$id]['starting'];
            }
            if (($pricing[$id]['free_plan'] ?? false) === true) {
                $freePlanIds[] = $id;
            }

            $profile = $item->technicalProfile;
            if ($profile && in_array($profile->api_status, ['available', 'limited'], true)) {
                $apiAvailableIds[] = $id;
            }
            if ($profile && in_array($profile->self_hosting_status, ['supported', 'enterprise_only'], true)) {
                $selfHostIds[] = $id;
            }
        }

        if ($startingPrices) {
            $min = min($startingPrices);
            $leaders = array_keys(array_filter($startingPrices, fn ($value) => (float) $value === (float) $min));
            $signals[] = $this->signal('Lower starting price', $leaders, $itemsById, '$' . number_format($min, 2) . ' / month', 'Lowest structured monthly starting price', 'badge-dollar-sign');
        }
        if ($freePlanIds) {
            $signals[] = $this->signal('Free plan listed', $freePlanIds, $itemsById, count($freePlanIds) . ' product' . (count($freePlanIds) === 1 ? '' : 's'), 'Structured pricing includes a free option', 'circle-dollar-sign');
        }
        if ($apiAvailableIds) {
            $signals[] = $this->signal('API access', $apiAvailableIds, $itemsById, count($apiAvailableIds) . ' product' . (count($apiAvailableIds) === 1 ? '' : 's'), 'Verified as available or limited API access', 'braces');
        }
        if ($selfHostIds) {
            $signals[] = $this->signal('Self-hosting', $selfHostIds, $itemsById, count($selfHostIds) . ' product' . (count($selfHostIds) === 1 ? '' : 's'), 'Self-hosted or enterprise/private deployment support', 'server');
        }

        return array_slice($signals, 0, 5);
    }

    private function signal(string $label, array $leaderIds, Collection $itemsById, string $value, string $detail, string $icon): array
    {
        $names = collect($leaderIds)
            ->map(fn ($id) => $itemsById->get((int) $id)?->name)
            ->filter()
            ->values();

        return [
            'label' => $label,
            'winner_ids' => array_map('intval', $leaderIds),
            'winner_names' => $names->all(),
            'winner_label' => $names->isEmpty() ? 'No verified leader' : $names->join(', '),
            'value' => $value,
            'detail' => $detail,
            'icon' => $icon,
            'tie' => count($leaderIds) > 1,
        ];
    }

    private function termCoverage(Collection $items, callable $resolver): array
    {
        $map = [];
        foreach ($items as $item) {
            $values = collect($resolver($item))
                ->map(function ($value) {
                    if (is_array($value)) return trim((string) ($value['name'] ?? $value['label'] ?? ''));
                    if (is_object($value)) return trim((string) ($value->name ?? ''));
                    return trim((string) $value);
                })
                ->filter()
                ->unique(fn ($value) => mb_strtolower($value));

            foreach ($values as $value) {
                $key = mb_strtolower($value);
                $map[$key]['label'] = $value;
                $map[$key]['item_ids'][] = (int) $item->id;
            }
        }

        return $this->coverageShape($map, $items);
    }

    private function relationCoverage(Collection $items, string $relation): array
    {
        $map = [];
        foreach ($items as $item) {
            $terms = $item->relationLoaded($relation) ? $item->{$relation} : collect();
            foreach ($terms as $term) {
                $label = trim((string) ($term->name ?? ''));
                if ($label === '') continue;
                $key = mb_strtolower($label);
                $map[$key]['label'] = $label;
                $map[$key]['item_ids'][] = (int) $item->id;
            }
        }

        return $this->coverageShape($map, $items);
    }

    private function coverageShape(array $map, Collection $items): array
    {
        $shared = [];
        $unique = [];
        foreach ($items as $item) {
            $unique[(int) $item->id] = [];
        }

        foreach ($map as $row) {
            $ids = array_values(array_unique(array_map('intval', $row['item_ids'] ?? [])));
            if (count($ids) >= 2) {
                $shared[] = ['label' => $row['label'], 'item_ids' => $ids];
            } elseif (count($ids) === 1) {
                $unique[$ids[0]][] = $row['label'];
            }
        }

        usort($shared, fn ($a, $b) => strcasecmp($a['label'], $b['label']));
        foreach ($unique as &$labels) {
            natcasesort($labels);
            $labels = array_values($labels);
        }

        return [
            'shared' => $shared,
            'unique' => $unique,
        ];
    }

    private function parseContextWindow(string $value): ?int
    {
        $value = strtolower(trim(str_replace([',', 'tokens', 'token'], '', $value)));
        if ($value === '') return null;

        if (! preg_match('/([0-9]+(?:\.[0-9]+)?)\s*([kmb])?/', $value, $matches)) {
            return null;
        }

        $number = (float) $matches[1];
        $multiplier = match ($matches[2] ?? '') {
            'k' => 1_000,
            'm' => 1_000_000,
            'b' => 1_000_000_000,
            default => 1,
        };

        return (int) round($number * $multiplier);
    }

    private function freshness(?CarbonInterface $date): string
    {
        if (! $date) return 'unverified';
        if ($date->gte(now()->subDays(30))) return 'fresh';
        if ($date->gte(now()->subDays(90))) return 'review';
        return 'stale';
    }

    private function asDate(mixed $value): ?CarbonInterface
    {
        if (! $value) return null;
        if ($value instanceof CarbonInterface) return $value;

        try {
            return Carbon::parse($value);
        } catch (\Throwable) {
            return null;
        }
    }

    private function latestDate(array $values): ?CarbonInterface
    {
        $dates = collect($values)
            ->map(fn ($value) => $this->asDate($value))
            ->filter();

        return $dates->sortByDesc(fn (CarbonInterface $date) => $date->timestamp)->first();
    }

    private function maxDate(?CarbonInterface $left, ?CarbonInterface $right): ?CarbonInterface
    {
        if (! $left) return $right;
        if (! $right) return $left;
        return $left->timestamp >= $right->timestamp ? $left : $right;
    }
}
