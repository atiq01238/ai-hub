<?php

namespace App\Console\Commands;

use App\Models\Benchmark;
use App\Models\Tool;
use App\Services\BenchmarkScoringService;
use App\Services\Tools\PlatformNormalizer;
use App\Services\Tools\ToolCommercialProfileService;
use App\Services\Tools\ToolDataConfidenceService;
use Illuminate\Console\Command;

class ToolV3FinalAudit extends Command
{
    protected $signature = 'tools:v3-final-audit {--published : Only published tools}';
    protected $description = 'Run one consolidated integrity + enrichment audit for the complete AI Orbit Tool Data V3 upgrade.';

    public function handle(
        ToolCommercialProfileService $commercial,
        PlatformNormalizer $platforms,
        ToolDataConfidenceService $confidence,
        BenchmarkScoringService $benchmarkScoring,
    ): int {
        $query = Tool::query()->with([
            'sources','platformTerms','useCaseTerms','featureTerms','technicalProfile','integrationTerms',
            'pricingPlans.sources','factEvidence',
        ])->orderBy('id');
        if ($this->option('published')) $query->where('status', 'published');
        $tools = $query->get();

        $counters = [
            'missing_sources' => 0, 'pricing_mismatch' => 0, 'platform_missing_pivot' => 0, 'unknown_platforms' => 0,
            'rating_mismatch' => 0, 'missing_use_cases' => 0, 'lifecycle_unknown' => 0, 'missing_advanced_profile' => 0,
            'api_unknown' => 0, 'open_source_unknown' => 0, 'self_host_unknown' => 0, 'commercial_use_unknown' => 0,
            'privacy_unknown' => 0, 'security_unknown' => 0, 'no_integrations' => 0, 'benchmark_mismatch' => 0,
        ];
        $confidenceBuckets = ['85-100' => 0, '70-84' => 0, '50-69' => 0, '30-49' => 0, '0-29' => 0];

        foreach ($tools as $tool) {
            if ($tool->sources->where('enabled', true)->isEmpty()) $counters['missing_sources']++;
            if (array_values((array) ($tool->pricing_models ?? [])) !== $commercial->expectedLabels($tool)) $counters['pricing_mismatch']++;

            $normalized = $platforms->normalize($tool->platforms ?? []);
            if ($normalized['unknown'] !== []) $counters['unknown_platforms']++;
            if ($normalized['canonical'] !== [] && $tool->platformTerms->isEmpty()) $counters['platform_missing_pivot']++;

            $avg = $tool->reviews()->published()->where('review_type', 'user')->avg('rating');
            $expectedRating = $avg !== null ? round((float) $avg, 1) : 0.0;
            if (abs((float) $tool->rating - $expectedRating) >= 0.001) $counters['rating_mismatch']++;
            if ($tool->useCaseTerms->isEmpty()) $counters['missing_use_cases']++;
            if (($tool->product_status ?: 'unknown') === 'unknown') $counters['lifecycle_unknown']++;

            $profile = $tool->technicalProfile;
            if (! $profile) {
                $counters['missing_advanced_profile']++;
                foreach (['api_unknown','open_source_unknown','self_host_unknown','commercial_use_unknown','privacy_unknown','security_unknown'] as $key) $counters[$key]++;
            } else {
                if ($profile->api_status === 'unknown') $counters['api_unknown']++;
                if ($profile->open_source_status === 'unknown') $counters['open_source_unknown']++;
                if ($profile->self_hosting_status === 'unknown') $counters['self_host_unknown']++;
                if ($profile->commercial_use_status === 'unknown') $counters['commercial_use_unknown']++;
                if ($profile->data_training_policy === 'unknown' && ! $profile->privacy_summary && ! $profile->data_retention_note) $counters['privacy_unknown']++;
                if ($profile->sso_status === 'unknown' && ! $profile->security_summary && empty($profile->security_certifications) && empty($profile->compliance_certifications)) $counters['security_unknown']++;
            }
            if ($tool->integrationTerms->isEmpty()) $counters['no_integrations']++;

            $class = $benchmarkScoring->primaryCompositeClass($tool);
            $expectedBenchmark = $class ? $benchmarkScoring->compositeForClass($tool, $class) : null;
            if (! $this->sameScore($expectedBenchmark, $tool->benchmark_score)) $counters['benchmark_mismatch']++;

            $score = $confidence->score($tool)['profile_completeness'];
            if ($score >= 85) $confidenceBuckets['85-100']++;
            elseif ($score >= 70) $confidenceBuckets['70-84']++;
            elseif ($score >= 50) $confidenceBuckets['50-69']++;
            elseif ($score >= 30) $confidenceBuckets['30-49']++;
            else $confidenceBuckets['0-29']++;
        }

        $unclassifiedBenchmarks = Benchmark::query()->where('is_active', true)
            ->where('benchmark_class', Benchmark::CLASS_UNCLASSIFIED)
            ->whereHas('results', fn ($q) => $q->where('verified', true)->where('status', 'verified'))
            ->count();

        $this->table(['Final Tool Data V3 integrity check', 'Count'], [
            ['Tools audited', $tools->count()],
            ['Missing tool_sources', $counters['missing_sources']],
            ['Pricing cache mismatches', $counters['pricing_mismatch']],
            ['Canonical platforms but missing pivot', $counters['platform_missing_pivot']],
            ['Tools with unknown platform aliases', $counters['unknown_platforms']],
            ['Community rating mismatches', $counters['rating_mismatch']],
            ['Class-safe benchmark mirror mismatches', $counters['benchmark_mismatch']],
            ['Unclassified benchmarks with verified results', $unclassifiedBenchmarks],
        ]);

        $this->table(['Verified enrichment gap (not a data-corruption error)', 'Count'], [
            ['Missing Best-for use cases', $counters['missing_use_cases']],
            ['Lifecycle not yet verified', $counters['lifecycle_unknown']],
            ['Advanced profile row missing', $counters['missing_advanced_profile']],
            ['API status unknown', $counters['api_unknown']],
            ['Open-source/license status unknown', $counters['open_source_unknown']],
            ['Self-hosting status unknown', $counters['self_host_unknown']],
            ['Commercial-use status unknown', $counters['commercial_use_unknown']],
            ['Privacy/training data not yet reviewed', $counters['privacy_unknown']],
            ['Security/compliance data not yet reviewed', $counters['security_unknown']],
            ['No structured integrations recorded', $counters['no_integrations']],
        ]);

        $this->table(['Profile completeness band', 'Tools'], collect($confidenceBuckets)->map(fn ($count, $band) => [$band, $count])->values()->all());
        $this->line('Integrity rows should be zero. Enrichment gaps are intentionally reported rather than fabricated; use verified source-backed imports/admin review to close them.');
        return self::SUCCESS;
    }

    private function sameScore($expected, $actual): bool
    {
        if ($expected === null && $actual === null) return true;
        if ($expected === null || $actual === null) return false;
        return abs((float) $expected - (float) $actual) < 0.05;
    }
}
