<?php

namespace App\Services\TestLab;

use App\Models\AiTestResult;
use Illuminate\Support\Collection;

class TestLabAggregateService
{
    public function sync(AiTestResult $result): AiTestResult
    {
        $result->loadMissing(['test', 'runs']);
        $test = $result->test;
        $completeRuns = $result->runs->where('status', 'complete')->values();
        $requiredRuns = max(1, (int) ($test?->required_runs ?: 1));

        if ($completeRuns->isEmpty()) {
            $capturedRun = $result->runs->first(fn ($run) => filled($run->response_text));
            $result->forceFill([
                'status' => 'pending',
                'run_count' => 0,
                'score_breakdown' => null,
                'overall_score' => 0,
                'score_quality' => null,
                'score_accuracy' => null,
                'score_prompt_adherence' => null,
                'score_creativity' => null,
                'score_speed' => null,
                'verification_level' => 'unverified',
                'is_verified' => false,
                'score_min' => null,
                'score_max' => null,
                'score_stddev' => null,
                'avg_latency_ms' => null,
                'avg_estimated_cost_usd' => null,
                'response_text' => $capturedRun?->response_text,
                'model_version' => $capturedRun?->model_version,
                'latency_ms' => $capturedRun?->latency_ms,
                'input_tokens' => $capturedRun?->input_tokens,
                'output_tokens' => $capturedRun?->output_tokens,
                'estimated_cost_usd' => $capturedRun?->estimated_cost_usd,
                'evaluator_summary' => null,
                'source_label' => $capturedRun?->source_label,
                'source_url' => $capturedRun?->source_url,
                'evidence_path' => $capturedRun?->evidence_path,
                'tested_at' => $capturedRun?->tested_at,
                'verified_at' => null,
            ])->save();
            return $result->fresh(['model', 'runs']);
        }

        $rubric = $test?->evaluationRubric() ?: [];
        $breakdown = [];
        foreach ($rubric as $criterion) {
            $key = $criterion['key'];
            $values = $completeRuns->map(fn ($run) => $run->score_breakdown[$key] ?? null)
                ->filter(fn ($value) => $value !== null && $value !== '')
                ->map(fn ($value) => (float) $value);
            if ($values->isNotEmpty()) {
                $breakdown[$key] = round($values->avg(), 1);
            }
        }

        $overallValues = $completeRuns->pluck('overall_score')->filter(fn ($value) => $value !== null)->map(fn ($value) => (float) $value)->values();
        $firstRun = $completeRuns->first();
        $latestRun = $completeRuns->sortByDesc(fn ($run) => $run->tested_at?->timestamp ?? $run->id)->first();
        $allAtLeastReviewed = $completeRuns->every(fn ($run) => in_array($run->verification_level, ['reviewed', 'verified', 'high_confidence'], true));
        $allVerified = $completeRuns->every(fn ($run) => in_array($run->verification_level, ['verified', 'high_confidence'], true));

        $verificationLevel = 'unverified';
        if ($completeRuns->count() >= $requiredRuns) {
            if ($allVerified && $requiredRuns >= 3) $verificationLevel = 'high_confidence';
            elseif ($allVerified) $verificationLevel = 'verified';
            elseif ($allAtLeastReviewed) $verificationLevel = 'reviewed';
        }

        $avgLatency = $this->nullableAverage($completeRuns->pluck('latency_ms'));
        $avgCost = $this->nullableAverage($completeRuns->pluck('estimated_cost_usd'), 6);
        $stddev = $this->stddev($overallValues);

        $legacyInteger = fn ($value) => $value === null ? null : (int) round((float) $value);
        $legacy = [
            'score_quality' => $legacyInteger($breakdown['quality'] ?? null),
            'score_accuracy' => $legacyInteger($breakdown['correctness'] ?? $breakdown['accuracy'] ?? null),
            'score_prompt_adherence' => $legacyInteger($breakdown['instruction_following'] ?? $breakdown['prompt_adherence'] ?? null),
            'score_creativity' => $legacyInteger($breakdown['creativity'] ?? null),
            'score_speed' => $legacyInteger($breakdown['speed'] ?? null),
        ];

        $result->forceFill([
            ...$legacy,
            'status' => $completeRuns->count() >= $requiredRuns ? 'complete' : 'pending',
            'score_breakdown' => $breakdown ?: null,
            'overall_score' => $overallValues->isNotEmpty() ? round($overallValues->avg(), 1) : 0,
            'run_count' => $completeRuns->count(),
            'score_min' => $overallValues->isNotEmpty() ? round($overallValues->min(), 1) : null,
            'score_max' => $overallValues->isNotEmpty() ? round($overallValues->max(), 1) : null,
            'score_stddev' => $stddev,
            'avg_latency_ms' => $avgLatency === null ? null : (int) round($avgLatency),
            'avg_estimated_cost_usd' => $avgCost,
            'verification_level' => $verificationLevel,
            'is_verified' => in_array($verificationLevel, ['verified', 'high_confidence'], true),
            'response_text' => $firstRun?->response_text,
            'model_version' => $latestRun?->model_version,
            'latency_ms' => $avgLatency === null ? null : (int) round($avgLatency),
            'input_tokens' => ($avgInput = $this->nullableAverage($completeRuns->pluck('input_tokens'))) === null ? null : (int) round($avgInput),
            'output_tokens' => ($avgOutput = $this->nullableAverage($completeRuns->pluck('output_tokens'))) === null ? null : (int) round($avgOutput),
            'estimated_cost_usd' => $avgCost,
            'evaluator_summary' => $completeRuns->count() > 1
                ? 'Aggregate of '.$completeRuns->count().' controlled runs. Average score '.number_format((float) ($overallValues->avg() ?: 0), 1).'/100; range '.number_format((float) ($overallValues->min() ?: 0), 1).'–'.number_format((float) ($overallValues->max() ?: 0), 1).'.'
                : $firstRun?->evaluator_summary,
            'source_label' => $firstRun?->source_label,
            'source_url' => $firstRun?->source_url,
            'evidence_path' => $firstRun?->evidence_path,
            'tested_at' => $completeRuns->pluck('tested_at')->filter()->sortDesc()->first(),
            'verified_at' => in_array($verificationLevel, ['verified', 'high_confidence'], true) ? now() : null,
        ])->save();

        return $result->fresh(['model', 'runs']);
    }

    private function nullableAverage(Collection $values, int $precision = 2): float|int|null
    {
        $filtered = $values->filter(fn ($value) => $value !== null && $value !== '')->map(fn ($value) => (float) $value);
        return $filtered->isEmpty() ? null : round($filtered->avg(), $precision);
    }

    private function stddev(Collection $values): ?float
    {
        if ($values->isEmpty()) return null;
        if ($values->count() === 1) return 0.0;
        $avg = $values->avg();
        $variance = $values->map(fn ($value) => (($value - $avg) ** 2))->avg();
        return round(sqrt($variance), 2);
    }
}
