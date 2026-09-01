<?php

namespace App\Services\Tools;

use App\Models\Benchmark;
use App\Models\Tool;
use Illuminate\Support\Carbon;

class ToolDataConfidenceService
{
    public function score(Tool $tool): array
    {
        $tool->loadMissing([
            'company:id,name', 'category:id,name', 'subcategoryTerm:id,name',
            'featureTerms:id,name', 'useCaseTerms:id,name', 'platformTerms:id,name',
            'sources', 'factEvidence', 'pricingPlans.sources', 'technicalProfile', 'integrationTerms:id,name',
        ]);

        $sections = [];
        $sections['Identity'] = $this->identity($tool);
        $sections['Taxonomy'] = $this->taxonomy($tool);
        $sections['Commercial'] = $this->commercial($tool);
        $sections['Lifecycle'] = $this->lifecycle($tool);
        $sections['Technical'] = $this->technical($tool);
        $sections['Privacy & security'] = $this->trust($tool);
        $sections['Benchmarks'] = $this->benchmarks($tool);
        $sections['Integrations'] = $this->integrations($tool);

        // This is completeness/coverage, not evidence confidence.
        $profileCompleteness = (int) min(100, max(0, round(collect($sections)->sum('score'), 0)));

        $enabledSources = $tool->sources->where('enabled', true);
        $verifiedSources = $enabledSources->where('verification_status', 'verified');
        $latestVerified = $verifiedSources
            ->map(fn ($source) => $source->last_checked_at ?: $source->verified_at)
            ->filter()
            ->sortByDesc(fn ($date) => $date instanceof \Carbon\CarbonInterface ? $date->getTimestamp() : strtotime((string) $date))
            ->first();

        $knownClaims = $tool->factEvidence->count();
        $verifiedClaims = $tool->factEvidence->where('verification_status', 'verified')->count();
        $freshness = $this->freshness($latestVerified);

        // Do not expose a numeric "confidence" until there is a minimum evidence base.
        $canShowConfidence = $verifiedSources->count() >= 1 && $verifiedClaims >= 2;
        $confidenceScore = $canShowConfidence
            ? $this->evidenceConfidence(
                verifiedClaims: $verifiedClaims,
                knownClaims: $knownClaims,
                verifiedSources: $verifiedSources->count(),
                totalSources: $enabledSources->count(),
                freshness: $freshness,
            )
            : null;

        return [
            // Backward-compatible alias used by audits; semantically this is completeness.
            'score' => $profileCompleteness,
            'label' => $this->completenessLabel($profileCompleteness),
            'profile_completeness' => $profileCompleteness,
            'profile_completeness_label' => $this->completenessLabel($profileCompleteness),
            'can_show_confidence' => $canShowConfidence,
            'confidence_score' => $confidenceScore,
            'confidence_label' => $confidenceScore !== null ? $this->confidenceLabel($confidenceScore) : null,
            'verification_status' => $canShowConfidence ? 'evidence_backed' : 'pending',
            'freshness' => $freshness,
            'last_verified_at' => $latestVerified,
            'verified_sources' => $verifiedSources->count(),
            'total_sources' => $enabledSources->count(),
            'verified_claims' => $verifiedClaims,
            'known_claims' => $knownClaims,
            'minimum_verified_sources' => 1,
            'minimum_verified_claims' => 2,
            'sections' => $sections,
        ];
    }

    private function evidenceConfidence(int $verifiedClaims, int $knownClaims, int $verifiedSources, int $totalSources, string $freshness): int
    {
        $claimCoverage = $knownClaims > 0 ? min(1, $verifiedClaims / $knownClaims) : 0;
        $sourceCoverage = $totalSources > 0 ? min(1, $verifiedSources / $totalSources) : 0;
        $freshnessWeight = match ($freshness) {
            'fresh' => 20,
            'review' => 12,
            'stale' => 5,
            default => 0,
        };

        return (int) round(($claimCoverage * 50) + ($sourceCoverage * 30) + $freshnessWeight);
    }

    private function identity(Tool $tool): array
    {
        $score = 0;
        $score += $tool->website ? 2 : 0;
        $score += $tool->company_id ? 2 : 0;
        $score += $tool->category_id ? 2 : 0;
        $score += $tool->subcategory_id ? 2 : 0;
        $score += (trim((string) $tool->short_description) !== '' && trim((string) $tool->description) !== '') ? 2 : 0;
        $primary = $tool->sources->firstWhere('is_primary', true) ?: $tool->sources->where('enabled', true)->first();
        $score += $primary ? 5 : 0;
        $score += $primary?->verification_status === 'verified' ? 5 : 0;
        return $this->section($score, 20);
    }

    private function taxonomy(Tool $tool): array
    {
        $score = 0;
        if ($tool->featureTerms->isNotEmpty()) {
            $score += 3;
            $score += round(2 * ($tool->featureTerms->filter(fn ($feature) => ($feature->pivot?->verification_status ?? 'pending') === 'verified')->count() / max(1, $tool->featureTerms->count())), 2);
        }
        if ($tool->useCaseTerms->isNotEmpty()) {
            $score += 3;
            $score += round(2 * ($tool->useCaseTerms->filter(fn ($useCase) => ($useCase->pivot?->verification_status ?? 'pending') === 'verified')->count() / max(1, $tool->useCaseTerms->count())), 2);
        }
        $score += $tool->platformTerms->isNotEmpty() ? 5 : 0;
        return $this->section($score, 15);
    }

    private function commercial(Tool $tool): array
    {
        if ($tool->pricingPlans->isEmpty()) return $this->section(0, 15);
        $score = 7;
        $verified = $tool->pricingPlans->filter(fn ($plan) => $plan->last_verified_at || $plan->sources->whereNotNull('last_checked_at')->isNotEmpty());
        $score += $verified->isNotEmpty() ? 4 : 0;
        $fresh = $tool->pricingPlans->filter(fn ($plan) => in_array($plan->freshness, ['fresh','review'], true));
        $score += $fresh->isNotEmpty() ? 4 : 0;
        return $this->section($score, 15);
    }

    private function lifecycle(Tool $tool): array
    {
        if (($tool->product_status ?: 'unknown') === 'unknown') return $this->section(0, 10);
        $score = 5;
        $score += $tool->product_status_source_id ? 2 : 0;
        $score += $tool->product_status_verified_at ? 3 : 0;
        return $this->section($score, 10);
    }

    private function technical(Tool $tool): array
    {
        $p = $tool->technicalProfile;
        if (! $p) return $this->section(0, 15);
        $known = 0;
        foreach (['api_status','open_source_status','self_hosting_status','commercial_use_status'] as $key) {
            if (($p->{$key} ?? 'unknown') !== 'unknown') $known++;
        }
        if (! empty($p->deployment_modes)) $known++;
        if (! empty($p->supported_languages) || ! empty($p->region_availability)) $known++;
        $score = min(10, round(($known / 6) * 10, 2));
        $facts = $tool->factEvidence->where('fact_type', 'technical');
        if ($facts->isNotEmpty()) $score += round(5 * ($facts->where('verification_status', 'verified')->count() / $facts->count()), 2);
        return $this->section($score, 15);
    }

    private function trust(Tool $tool): array
    {
        $p = $tool->technicalProfile;
        if (! $p) return $this->section(0, 15);
        $score = 0;
        $score += ($p->data_training_policy !== 'unknown' || $p->privacy_summary || $p->data_retention_note) ? 4 : 0;
        $score += ($p->security_summary || $p->sso_status !== 'unknown') ? 4 : 0;
        $score += (! empty($p->security_certifications) || ! empty($p->compliance_certifications) || ! empty($p->data_residency)) ? 2 : 0;
        $score += $p->privacySource?->verification_status === 'verified' ? 2.5 : 0;
        $score += $p->securitySource?->verification_status === 'verified' ? 2.5 : 0;
        return $this->section($score, 15);
    }

    private function benchmarks(Tool $tool): array
    {
        $exists = $tool->benchmarkResults()
            ->where('verified', true)
            ->where('status', 'verified')
            ->whereHas('benchmark', fn ($q) => $q->where('is_active', true)->where('benchmark_class', '!=', Benchmark::CLASS_UNCLASSIFIED))
            ->exists();
        return $this->section($exists ? 5 : 0, 5);
    }

    private function integrations(Tool $tool): array
    {
        if ($tool->integrationTerms->isEmpty()) return $this->section(0, 5);
        $score = 3;
        $score += $tool->integrationTerms->contains(fn ($integration) => ($integration->pivot->verification_status ?? 'pending') === 'verified') ? 2 : 0;
        return $this->section($score, 5);
    }

    private function section(float $score, int $max): array
    {
        $score = round(min($max, max(0, $score)), 2);
        return ['score' => $score, 'max' => $max, 'percent' => (int) round(($score / $max) * 100)];
    }

    private function completenessLabel(float $score): string
    {
        return match (true) {
            $score >= 85 => 'Excellent',
            $score >= 70 => 'Strong',
            $score >= 50 => 'Moderate',
            $score >= 30 => 'Partial',
            default => 'Limited',
        };
    }

    private function confidenceLabel(float $score): string
    {
        return match (true) {
            $score >= 90 => 'Very high',
            $score >= 75 => 'High',
            $score >= 55 => 'Moderate',
            default => 'Limited',
        };
    }

    private function freshness(mixed $value): string
    {
        if (! $value) return 'unverified';
        $date = $value instanceof \Carbon\CarbonInterface ? $value : Carbon::parse($value);
        if ($date->gte(now()->subDays(30))) return 'fresh';
        if ($date->gte(now()->subDays(90))) return 'review';
        return 'stale';
    }
}
