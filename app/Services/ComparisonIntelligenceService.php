<?php

namespace App\Services;

use Illuminate\Support\Collection;

class ComparisonIntelligenceService
{
    public function build(Collection $items, string $type): array
    {
        $benchmarkMatrix = [];
        $benchmarkMeta = [];

        foreach ($items as $item) {
            $rows = $item->benchmarkResults()
                ->with('benchmark')
                ->where('verified', true)
                ->where('status', 'verified')
                ->orderByDesc('tested_at')
                ->orderByDesc('id')
                ->get()
                ->unique('benchmark_id');

            foreach ($rows as $result) {
                if (! $result->benchmark) {
                    continue;
                }

                $key = $result->benchmark->slug;
                $benchmarkMeta[$key] = $result->benchmark;
                $benchmarkMatrix[$key][$item->id] = $result;
            }
        }

        $wins = [];
        foreach ($items as $item) {
            $wins[$item->id] = 0;
        }

        foreach ($benchmarkMatrix as $key => $scores) {
            $benchmark = $benchmarkMeta[$key];
            $eligible = collect($scores);

            // A benchmark only creates a comparative win when at least two
            // selected items have results for the same benchmark.
            if ($eligible->count() < 2) {
                continue;
            }

            $best = $benchmark->higher_is_better
                ? $eligible->sortByDesc('score')->first()
                : $eligible->sortBy('score')->first();

            $wins[$best->benchmarkable_id]++;
        }

        $pricing = [];
        foreach ($items as $item) {
            if ($type === 'model') {
                $pricing[$item->id] = [
                    'input' => $item->input_price_per_million,
                    'output' => $item->output_price_per_million,
                    'verified' => $item->input_price_per_million !== null || $item->output_price_per_million !== null,
                ];
                continue;
            }

            $plans = $item->pricingPlans()
                ->orderByRaw('monthly_price is null')
                ->orderBy('monthly_price')
                ->get();

            $pricing[$item->id] = [
                'plans' => $plans,
                'starting' => $plans->whereNotNull('monthly_price')->min('monthly_price'),
                'verified' => $plans->isNotEmpty(),
            ];
        }

        $overallCandidates = $items->filter(function ($item) use ($type, $wins) {
            if ($item->benchmark_score !== null) {
                return true;
            }

            return $type === 'tool' && $item->rating !== null && (float) $item->rating > 0
                || ($wins[$item->id] ?? 0) > 0;
        });

        $overall = $overallCandidates->sortByDesc(function ($item) use ($wins) {
            $benchmark = $item->benchmark_score !== null ? (float) $item->benchmark_score : 0.0;
            $rating = isset($item->rating) ? (float) $item->rating * 10 : 0.0;
            $base = $benchmark > 0 ? $benchmark : $rating;

            return ($base * 0.8) + (($wins[$item->id] ?? 0) * 5);
        })->first();

        $valueCandidates = $items->filter(function ($item) use ($type, $pricing) {
            $score = (float) ($item->benchmark_score ?? 0);
            if ($score <= 0) {
                return false;
            }

            if (! ($pricing[$item->id]['verified'] ?? false)) {
                return false;
            }

            if ($type === 'model') {
                return $pricing[$item->id]['input'] !== null || $pricing[$item->id]['output'] !== null;
            }

            return $pricing[$item->id]['starting'] !== null;
        });

        $valueWinner = $valueCandidates->sortBy(function ($item) use ($type, $pricing) {
            $score = max((float) ($item->benchmark_score ?? 0), 1);

            if ($type === 'model') {
                $input = $pricing[$item->id]['input'];
                $output = $pricing[$item->id]['output'];
                $cost = ($input !== null ? (float) $input : 0.0) + ($output !== null ? (float) $output : 0.0);
            } else {
                $cost = (float) $pricing[$item->id]['starting'];
            }

            return $cost / $score;
        })->first();

        return compact(
            'benchmarkMatrix',
            'benchmarkMeta',
            'wins',
            'pricing',
            'overall',
            'valueWinner',
        );
    }
}
