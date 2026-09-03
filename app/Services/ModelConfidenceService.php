<?php

namespace App\Services;

use App\Models\AiModel;
use Illuminate\Support\Collection;

class ModelConfidenceService
{
    public function build(AiModel $model): array
    {
        $evidence = $model->relationLoaded('evidenceSources')
            ? $model->evidenceSources
            : $model->evidenceSources()->get();

        $benchmarkResults = $model->relationLoaded('benchmarkResults')
            ? $model->benchmarkResults
            : $model->benchmarkResults()
                ->where('verified', true)
                ->where('status', 'verified')
                ->get();

        $checks = collect();

        $this->addCheck(
            $checks,
            'Identity',
            20,
            true,
            $model->identity_status === 'verified',
            $model->identity_status === 'verified'
                ? 'Exact model identity verified'
                : 'Exact version mapping still required'
        );

        $this->addCheck(
            $checks,
            'Profile',
            20,
            true,
            filled($model->profile_verified_at) && $model->identity_status === 'verified',
            filled($model->profile_verified_at)
                ? 'Profile checked '.optional($model->profile_verified_at)->format('M j, Y')
                : 'Profile verification is incomplete'
        );

        $hasOfficialEvidence = filled($model->official_source_url)
            || $evidence->contains(fn ($source) => $source->source_type === 'official' && filled($source->source_url));

        $this->addCheck(
            $checks,
            'Official evidence',
            15,
            true,
            $hasOfficialEvidence,
            $hasOfficialEvidence ? 'Official-source evidence linked' : 'Official source not linked'
        );

        $hasCapabilities = $model->relationLoaded('featureTerms')
            ? $model->featureTerms->isNotEmpty() || collect($model->capabilities ?? [])->filter()->isNotEmpty()
            : collect($model->capabilities ?? [])->filter()->isNotEmpty();

        $this->addCheck(
            $checks,
            'Capabilities',
            10,
            true,
            $hasCapabilities,
            $hasCapabilities ? 'Capability taxonomy populated' : 'Capability evidence is incomplete'
        );

        $this->addCheck(
            $checks,
            'Release date',
            10,
            true,
            filled($model->release_date),
            filled($model->release_date) ? 'Official release date stored' : 'Exact dated source not pinned'
        );

        $contextApplicable = $this->contextApplicable($model);
        $this->addCheck(
            $checks,
            'Context window',
            10,
            $contextApplicable,
            !$contextApplicable || filled($model->context_window),
            $contextApplicable
                ? (filled($model->context_window) ? 'Context specification stored' : 'Context specification not pinned')
                : 'Not applicable to this model type'
        );

        $pricingApplicable = filled($model->pricing_type)
            || $model->input_price_per_million !== null
            || $model->output_price_per_million !== null
            || ($model->relationLoaded('pricingSources') && $model->pricingSources->isNotEmpty());

        $pricingVerified = in_array($model->pricing_verification_status, [
            'verified',
            'verified_structure',
            'verified_specialized',
            'verified_unit_only',
            'provider_dependent',
            'historical_unpriced',
            'regional',
            'not_applicable',
        ], true);

        $this->addCheck(
            $checks,
            'Pricing model',
            10,
            $pricingApplicable,
            $pricingVerified,
            $pricingVerified
                ? ($model->pricing_verification_label ?: 'Pricing structure verified')
                : 'Commercial terms are not fully pinned'
        );

        $benchmarkApplicable = $model->benchmark_score !== null || $benchmarkResults->isNotEmpty();
        $benchmarkVerified = $benchmarkResults->isNotEmpty() || $model->benchmark_score !== null;

        $this->addCheck(
            $checks,
            'Benchmarks',
            5,
            $benchmarkApplicable,
            $benchmarkVerified,
            $benchmarkApplicable
                ? ($benchmarkVerified ? 'Verified benchmark evidence available' : 'Benchmark evidence not verified')
                : 'No benchmark claim is made for this profile'
        );

        $applicable = $checks->where('applicable', true);
        $possible = max(1, (int) $applicable->sum('weight'));
        $earned = (int) $applicable->where('verified', true)->sum('weight');
        $score = (int) round(($earned / $possible) * 100);

        [$label, $class] = match (true) {
            $score >= 90 => ['High confidence', 'high'],
            $score >= 75 => ['Good confidence', 'good'],
            $score >= 55 => ['Partial confidence', 'partial'],
            default => ['Limited confidence', 'limited'],
        };

        return [
            'score' => $score,
            'label' => $label,
            'class' => $class,
            'earned_weight' => $earned,
            'possible_weight' => $possible,
            'checks' => $checks->values(),
            'verified_checks' => $checks->where('applicable', true)->where('verified', true)->count(),
            'applicable_checks' => $applicable->count(),
            'evidence_count' => $evidence->count(),
        ];
    }

    private function contextApplicable(AiModel $model): bool
    {
        if (in_array($model->pricing_type, [
            'image',
            'video',
            'audio',
            'text_to_speech',
            'speech_to_text',
            'voice',
            'scientific',
        ], true)) {
            return false;
        }

        if (filled($model->context_window)) {
            return true;
        }

        $capabilities = collect($model->capabilities ?? [])->map(fn ($value) => mb_strtolower((string) $value));

        return $capabilities->contains(fn ($value) => str_contains($value, 'text generation')
            || str_contains($value, 'reasoning')
            || str_contains($value, 'code generation'));
    }

    private function addCheck(
        Collection $checks,
        string $label,
        int $weight,
        bool $applicable,
        bool $verified,
        string $detail
    ): void {
        $checks->push([
            'label' => $label,
            'weight' => $weight,
            'applicable' => $applicable,
            'verified' => $verified,
            'detail' => $detail,
        ]);
    }
}
