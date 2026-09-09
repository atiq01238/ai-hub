<?php

namespace App\Services\Tools;

use App\Models\Review;
use App\Models\Tool;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class ToolEditorialIntelligenceService
{
    /**
     * Build a compact decision brief from information already present on the
     * tool profile. This service deliberately avoids inventing product claims:
     * source-backed taxonomy, published editorial review content and recorded
     * profile gaps are the only inputs used for strengths/fit/considerations.
     */
    public function build(
        Tool $tool,
        Collection $pricingPlans,
        Collection $relatedTools,
        Collection $relatedComparisons,
        Collection $benchmarkResults,
        array $dataConfidence,
        ?Review $editorReview = null,
    ): array {
        $enabledSources = $tool->sources
            ->filter(fn ($source) => (bool) $source->enabled)
            ->values();

        $verifiedSources = $enabledSources
            ->filter(fn ($source) => $source->verification_status === 'verified')
            ->values();

        $verifiedSourceIds = $verifiedSources
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $bestFor = $this->bestFor($tool, $verifiedSourceIds);
        $strengths = $this->strengths($tool, $verifiedSourceIds, $editorReview);
        $considerations = $this->considerations(
            $tool,
            $pricingPlans,
            $benchmarkResults,
            $dataConfidence,
            $editorReview,
            $verifiedSources,
        );

        $signals = $this->signals(
            $tool,
            $pricingPlans,
            $benchmarkResults,
            $relatedComparisons,
            $verifiedSourceIds,
            $verifiedSources,
        );

        $alternatives = $relatedTools
            ->take(3)
            ->map(fn (Tool $alternative) => [
                'name' => $alternative->name,
                'slug' => $alternative->slug,
                'score' => (float) ($alternative->alternative_match_score ?? 0),
                'reasons' => collect($alternative->alternative_match_reasons ?? [])->filter()->values()->all(),
            ])
            ->values();

        // Do not add an empty boilerplate block to thin profiles. The brief is
        // shown only when there is actual decision-support material to surface.
        $show = $bestFor->isNotEmpty()
            || $strengths->isNotEmpty()
            || $editorReview !== null
            || $signals->where('meaningful', true)->count() >= 2;

        return [
            'show' => $show,
            'best_for' => $bestFor,
            'strengths' => $strengths,
            'considerations' => $considerations,
            'signals' => $signals,
            'alternatives' => $alternatives,
            'verdict' => trim((string) ($editorReview?->verdict ?? '')),
            'method_note' => 'Built from AI Orbit\'s recorded profile data. Unknown product facts are not inferred.',
        ];
    }

    private function bestFor(Tool $tool, array $verifiedSourceIds): Collection
    {
        $sourceMap = $tool->sources->keyBy('id');

        $items = $tool->useCaseTerms
            ->map(function ($useCase) use ($verifiedSourceIds, $sourceMap) {
                $sourceId = (int) ($useCase->pivot?->tool_source_id ?? 0);
                $verified = ($useCase->pivot?->verification_status ?? 'pending') === 'verified'
                    && $sourceId > 0
                    && in_array($sourceId, $verifiedSourceIds, true);

                return [
                    'name' => $useCase->name,
                    'slug' => $useCase->slug,
                    'note' => trim((string) ($useCase->pivot?->fit_note ?? '')),
                    'verified' => $verified,
                    'source_id' => $sourceId ?: null,
                    'source_url' => $sourceId ? $sourceMap->get($sourceId)?->source_url : null,
                ];
            })
            ->sortByDesc(fn (array $item) => $item['verified'] ? 1 : 0)
            ->values();

        // A verified mapping is preferred. If none exists, canonical taxonomy
        // mappings are still useful discovery context but remain clearly marked.
        $verified = $items->where('verified', true)->take(4)->values();

        return $verified->isNotEmpty()
            ? $verified
            : $items->take(4)->values();
    }

    private function strengths(Tool $tool, array $verifiedSourceIds, ?Review $editorReview): Collection
    {
        $strengths = collect();

        foreach (collect($editorReview?->pros ?? [])->filter()->take(3) as $pro) {
            $strengths->push([
                'title' => trim((string) $pro),
                'detail' => 'Published AI Orbit editorial review finding.',
                'verified' => false,
                'kind' => 'editorial',
                'source_url' => null,
            ]);
        }

        $sourceMap = $tool->sources->keyBy('id');

        foreach ($tool->featureTerms as $feature) {
            if ($strengths->count() >= 4) {
                break;
            }

            $sourceId = (int) ($feature->pivot?->tool_source_id ?? 0);
            $verified = ($feature->pivot?->verification_status ?? 'pending') === 'verified'
                && $sourceId > 0
                && in_array($sourceId, $verifiedSourceIds, true);

            if (! $verified) {
                continue;
            }

            $detail = trim((string) ($feature->pivot?->description ?? ''));
            if ($detail === '') {
                $detail = trim((string) ($feature->short_description ?? $feature->description ?? ''));
            }

            $source = $sourceMap->get($sourceId);

            $strengths->push([
                'title' => $feature->name,
                'detail' => $detail !== '' ? Str::limit($detail, 180) : 'Source-backed capability recorded for this tool.',
                'verified' => true,
                'kind' => 'capability',
                'source_url' => $source?->source_url,
            ]);
        }

        // Verified technical facts can provide decision value even when feature
        // taxonomy mappings have not yet been individually source-linked. Only
        // clearly positive/available states are promoted as strengths.
        $profile = $tool->technicalProfile;
        if ($profile && $strengths->count() < 4) {
            $technicalStrengths = [
                ['technical', 'api_status', $profile->api_status === 'available', 'API access', 'Verified profile evidence records API access for this tool.', $profile->api_source_id],
                ['technical', 'self_hosting_status', $profile->self_hosting_status === 'supported', 'Self-hosting support', 'Verified deployment evidence records self-hosting support.', $profile->deployment_source_id],
                ['technical', 'commercial_use_status', $profile->commercial_use_status === 'allowed', 'Commercial-use support', 'Verified terms evidence records commercial use as allowed.', $profile->terms_source_id],
                ['security', 'sso_status', in_array($profile->sso_status, ['available', 'enterprise_only'], true), 'Enterprise access controls', 'Verified security evidence records SSO / SAML availability.', $profile->security_source_id],
            ];

            foreach ($technicalStrengths as [$type, $key, $positive, $title, $detail, $sourceId]) {
                if ($strengths->count() >= 4 || ! $positive || ! $this->verifiedFact($tool, $type, $key, $sourceId, $verifiedSourceIds)) {
                    continue;
                }

                $strengths->push([
                    'title' => $title,
                    'detail' => $detail,
                    'verified' => true,
                    'kind' => 'technical',
                    'source_url' => $sourceId ? $sourceMap->get((int) $sourceId)?->source_url : null,
                ]);
            }
        }

        if ($profile && $strengths->count() < 4 && $this->verifiedFact($tool, 'security', 'security_certifications', $profile->security_source_id, $verifiedSourceIds)) {
            $certifications = collect($profile->security_certifications ?? [])->filter()->take(4);
            if ($certifications->isNotEmpty()) {
                $strengths->push([
                    'title' => 'Recorded security certifications',
                    'detail' => 'Verified security evidence records '. $certifications->join(', ', ' and ') .'.',
                    'verified' => true,
                    'kind' => 'security',
                    'source_url' => $profile->security_source_id ? $sourceMap->get((int) $profile->security_source_id)?->source_url : null,
                ]);
            }
        }

        return $strengths
            ->unique(fn (array $item) => mb_strtolower(trim($item['title'])))
            ->take(4)
            ->values();
    }

    private function considerations(
        Tool $tool,
        Collection $pricingPlans,
        Collection $benchmarkResults,
        array $dataConfidence,
        ?Review $editorReview,
        Collection $verifiedSources,
    ): Collection {
        $items = collect();

        foreach (collect($editorReview?->cons ?? [])->filter()->take(3) as $con) {
            $items->push([
                'title' => trim((string) $con),
                'detail' => 'Published AI Orbit editorial review consideration.',
                'kind' => 'editorial',
            ]);
        }

        $pushGap = function (string $title, string $detail) use ($items): void {
            if ($items->count() >= 3) {
                return;
            }

            $items->push([
                'title' => $title,
                'detail' => $detail,
                'kind' => 'profile_gap',
            ]);
        };

        if ($verifiedSources->isEmpty()) {
            $pushGap(
                'Source verification is incomplete',
                'No enabled source on this profile is currently marked verified; check the provider website for important purchase or deployment decisions.'
            );
        }

        if ($pricingPlans->isEmpty()) {
            $pushGap(
                'Plan-level pricing is not recorded',
                'AI Orbit does not yet have detailed pricing plans for this tool, so current rates should be confirmed directly with the provider.'
            );
        }

        if ($benchmarkResults->isEmpty()) {
            $pushGap(
                'No verified benchmark is published',
                'Relative performance should not be inferred from popularity, star ratings or marketing claims when comparable benchmark evidence is unavailable.'
            );
        }

        if (($tool->product_status ?: 'unknown') === 'unknown') {
            $pushGap(
                'Lifecycle status is not verified',
                'The current product lifecycle state has not yet been source-verified in AI Orbit.'
            );
        }

        if (($dataConfidence['profile_completeness'] ?? $dataConfidence['score'] ?? 0) < 50) {
            $pushGap(
                'Profile coverage is still partial',
                'Some structured fields are not yet populated; use the linked official sources for details not represented on this profile.'
            );
        }

        return $items->take(3)->values();
    }

    private function verifiedFact(Tool $tool, string $type, string $key, mixed $sourceId, array $verifiedSourceIds): bool
    {
        $sourceId = (int) ($sourceId ?? 0);
        if ($sourceId <= 0 || ! in_array($sourceId, $verifiedSourceIds, true)) {
            return false;
        }

        return $tool->factEvidence->contains(function ($evidence) use ($type, $key, $sourceId) {
            return $evidence->fact_type === $type
                && $evidence->fact_key === $key
                && (int) $evidence->tool_source_id === $sourceId
                && $evidence->verification_status === 'verified';
        });
    }

    private function signals(
        Tool $tool,
        Collection $pricingPlans,
        Collection $benchmarkResults,
        Collection $relatedComparisons,
        array $verifiedSourceIds,
        Collection $verifiedSources,
    ): Collection {
        $featureTotal = $tool->featureTerms->count();
        $featureVerified = $tool->featureTerms->filter(function ($feature) use ($verifiedSourceIds) {
            $sourceId = (int) ($feature->pivot?->tool_source_id ?? 0);

            return ($feature->pivot?->verification_status ?? 'pending') === 'verified'
                && $sourceId > 0
                && in_array($sourceId, $verifiedSourceIds, true);
        })->count();

        $useCaseTotal = $tool->useCaseTerms->count();
        $useCaseVerified = $tool->useCaseTerms->filter(function ($useCase) use ($verifiedSourceIds) {
            $sourceId = (int) ($useCase->pivot?->tool_source_id ?? 0);

            return ($useCase->pivot?->verification_status ?? 'pending') === 'verified'
                && $sourceId > 0
                && in_array($sourceId, $verifiedSourceIds, true);
        })->count();

        $verifiedPricing = $pricingPlans->filter(function ($plan) {
            if ($plan->last_verified_at) {
                return true;
            }

            return $plan->relationLoaded('sources')
                && $plan->sources->contains(fn ($source) => (bool) $source->last_checked_at);
        })->count();

        return collect([
            [
                'label' => 'Capabilities',
                'value' => $featureTotal > 0 ? $featureVerified.'/'.$featureTotal.' verified' : 'Not mapped',
                'icon' => 'badge-check',
                'meaningful' => $featureTotal > 0,
            ],
            [
                'label' => 'Use-case fit',
                'value' => $useCaseTotal > 0 ? $useCaseVerified.'/'.$useCaseTotal.' verified' : 'Not mapped',
                'icon' => 'target',
                'meaningful' => $useCaseTotal > 0,
            ],
            [
                'label' => 'Pricing evidence',
                'value' => $pricingPlans->isNotEmpty()
                    ? $verifiedPricing.'/'.$pricingPlans->count().' plans checked'
                    : 'No plan data',
                'icon' => 'badge-dollar-sign',
                'meaningful' => $pricingPlans->isNotEmpty(),
            ],
            [
                'label' => 'Verified sources',
                'value' => (string) $verifiedSources->count(),
                'icon' => 'link-2',
                'meaningful' => $verifiedSources->isNotEmpty(),
            ],
            [
                'label' => 'Benchmarks',
                'value' => $benchmarkResults->isNotEmpty() ? $benchmarkResults->count().' verified' : 'None published',
                'icon' => 'gauge',
                'meaningful' => $benchmarkResults->isNotEmpty(),
            ],
            [
                'label' => 'Comparisons',
                'value' => $relatedComparisons->isNotEmpty() ? $relatedComparisons->count().' published' : 'None linked',
                'icon' => 'scale',
                'meaningful' => $relatedComparisons->isNotEmpty(),
            ],
        ]);
    }
}
