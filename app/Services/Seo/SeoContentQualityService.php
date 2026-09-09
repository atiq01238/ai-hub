<?php

namespace App\Services\Seo;

use App\Models\AiModel;
use App\Models\Article;
use App\Models\NewsItem;
use App\Models\Tool;
use App\Services\ModelConfidenceService;
use App\Services\Articles\ArticleEditorialQualityService;
use App\Services\Tools\ToolDataConfidenceService;
use Illuminate\Support\Collection;

class SeoContentQualityService
{
    public function __construct(
        private readonly ToolDataConfidenceService $toolConfidence,
        private readonly ModelConfidenceService $modelConfidence,
        private readonly ArticleEditorialQualityService $articleQuality,
    ) {
    }

    /**
     * Score a tool profile for enrichment while keeping every published tool
     * indexable during the current cleanup cycle. The quality score is still
     * retained for diagnostics so incomplete profiles can be enriched later.
     *
     * Set seo_content_quality.tool.index_published_by_default to false to
     * restore strict quality-gated indexing after the catalog audit is done.
     */
    public function tool(Tool $tool, ?array $confidence = null): array
    {
        $confidence ??= $this->toolConfidence->score($tool);

        $description = $this->plainText($tool->description ?: $tool->short_description);
        $descriptionChars = mb_strlen($description);
        $profileCompleteness = (int) ($confidence['profile_completeness'] ?? $confidence['score'] ?? 0);

        $featureCount = $this->relationCount($tool, 'featureTerms');
        $useCaseCount = $this->relationCount($tool, 'useCaseTerms');
        $platformCount = $this->relationCount($tool, 'platformTerms');
        $pricingCount = $this->relationCount($tool, 'pricingPlans');
        $benchmarkCount = $this->relationCount($tool, 'benchmarkResults');

        $legacyCapabilities = collect($tool->capabilities ?? [])->filter()->count();
        $legacyPlatforms = collect($tool->platforms ?? [])->filter()->count();

        $hasFeatureSignal = $featureCount > 0 || $legacyCapabilities > 0;
        $hasUseCaseSignal = $useCaseCount > 0;
        $hasPlatformSignal = $platformCount > 0 || $legacyPlatforms > 0;
        $hasTaxonomySignal = $hasFeatureSignal || $hasUseCaseSignal;
        $hasIdentityAnchor = filled($tool->website) || (int) ($confidence['total_sources'] ?? 0) > 0;

        $score = 0;
        $score += $descriptionChars >= 180 ? 20 : ($descriptionChars >= 100 ? 12 : 0);
        $score += filled($tool->website) ? 5 : 0;
        $score += filled($tool->company_id) ? 5 : 0;
        $score += filled($tool->category_id) ? 5 : 0;
        $score += (int) ($confidence['total_sources'] ?? 0) > 0 ? 5 : 0;
        $score += $hasFeatureSignal ? 8 : 0;
        $score += $hasUseCaseSignal ? 7 : 0;
        $score += $hasPlatformSignal ? 5 : 0;
        $score += (int) round(min(100, max(0, $profileCompleteness)) * 0.25);
        $score += (int) ($confidence['verified_sources'] ?? 0) > 0 ? 5 : 0;
        $score += (int) ($confidence['verified_claims'] ?? 0) >= 2 ? 5 : 0;
        $score += $pricingCount > 0 ? 3 : 0;
        $score += $benchmarkCount > 0 ? 2 : 0;
        $score = min(100, $score);

        // These are enrichment diagnostics, not blockers while the temporary
        // published-tools indexing policy is enabled.
        $qualityWarnings = [];
        if ($descriptionChars < (int) config('seo_content_quality.tool.min_description_chars', 100)) {
            $qualityWarnings[] = 'Overview is too thin.';
        }
        if (! $hasIdentityAnchor) {
            $qualityWarnings[] = 'No website or source evidence is attached.';
        }
        if (! $hasTaxonomySignal) {
            $qualityWarnings[] = 'No capability/feature or use-case signal is available.';
        }
        if ($profileCompleteness < (int) config('seo_content_quality.tool.min_profile_completeness', 30)) {
            $qualityWarnings[] = 'Profile completeness is below the enrichment floor.';
        }
        if ($score < (int) config('seo_content_quality.tool.index_threshold', 55)) {
            $qualityWarnings[] = 'Overall content-quality score is below the preferred threshold.';
        }

        $blockingReasons = [];
        if ($tool->status !== 'published') {
            $blockingReasons[] = 'Tool is not published.';
        } elseif (! (bool) config('seo_content_quality.tool.index_published_by_default', true)) {
            // Strict mode can be re-enabled later without rewriting controllers,
            // sitemaps, intent maps or robots handling.
            $blockingReasons = $qualityWarnings;
        }

        $result = $this->result('tool', $score, $blockingReasons, [
            'description_chars' => $descriptionChars,
            'profile_completeness' => $profileCompleteness,
            'verified_sources' => (int) ($confidence['verified_sources'] ?? 0),
            'verified_claims' => (int) ($confidence['verified_claims'] ?? 0),
            'feature_signals' => $featureCount + $legacyCapabilities,
            'use_case_signals' => $useCaseCount,
            'platform_signals' => $platformCount + $legacyPlatforms,
            'pricing_plans' => $pricingCount,
            'benchmark_results' => $benchmarkCount,
        ]);

        $result['quality_warnings'] = array_values(array_unique($qualityWarnings));
        $result['needs_enrichment'] = $result['quality_warnings'] !== [];

        if ($result['indexable'] && $result['needs_enrichment']) {
            $result['label'] = 'Indexable / enrichment recommended';
        }

        return $result;
    }

    /**
     * Model profiles require both evidence confidence and enough model-specific
     * capability/specification content to avoid indexing placeholder profiles.
     */
    public function model(AiModel $model, ?array $confidence = null): array
    {
        $confidence ??= $this->modelConfidence->build($model);

        $notesChars = mb_strlen($this->plainText($model->capability_notes));
        $legacyCapabilities = collect($model->capabilities ?? [])->filter()->count();
        $featureCount = $this->relationCount($model, 'featureTerms');
        $useCaseCount = $this->relationCount($model, 'useCaseTerms');
        $capabilitySignals = $legacyCapabilities + $featureCount;

        $evidence = $this->relationCollection($model, 'evidenceSources');
        $hasOfficialEvidence = filled($model->official_source_url)
            || $evidence->contains(fn ($source) => ($source->source_type ?? null) === 'official' && filled($source->source_url));

        $verifiedBenchmarks = $this->relationCount($model, 'benchmarkResults');
        $confidenceScore = (int) ($confidence['score'] ?? 0);
        $pricingVerified = in_array($model->pricing_verification_status, [
            'verified', 'verified_structure', 'verified_specialized', 'verified_unit_only',
            'provider_dependent', 'historical_unpriced', 'regional', 'not_applicable',
        ], true);

        $hasModelSpecificContent = $notesChars >= (int) config('seo_content_quality.model.min_notes_chars', 80)
            || $capabilitySignals >= (int) config('seo_content_quality.model.min_capability_signals', 2);

        $score = 0;
        $score += (int) round(min(100, max(0, $confidenceScore)) * 0.40);
        $score += $notesChars >= 120 ? 12 : ($notesChars >= 60 ? 8 : 0);
        $score += $capabilitySignals >= 2 ? 8 : ($capabilitySignals === 1 ? 4 : 0);
        $score += $useCaseCount > 0 ? 5 : 0;
        $score += $hasOfficialEvidence ? 10 : 0;
        $score += filled($model->company_id) ? 5 : 0;
        $score += $model->identity_status === 'verified' ? 5 : 0;
        $score += filled($model->release_date) ? 5 : 0;
        $score += (filled($model->context_window) || filled($model->pricing_type)) ? 5 : 0;
        $score += ($verifiedBenchmarks > 0 || $pricingVerified) ? 5 : 0;
        $score = min(100, $score);

        $reasons = [];
        if (! in_array($model->status, ['active', 'preview'], true)) {
            $reasons[] = 'Model is not an active/preview index candidate.';
        }
        if ($confidenceScore < (int) config('seo_content_quality.model.min_confidence', 55)) {
            $reasons[] = 'Evidence confidence is below the indexing floor.';
        }
        if (! $hasOfficialEvidence) {
            $reasons[] = 'No official model evidence/source is linked.';
        }
        if (! $hasModelSpecificContent) {
            $reasons[] = 'Model-specific capability/profile content is too thin.';
        }
        if ($score < (int) config('seo_content_quality.model.index_threshold', 60)) {
            $reasons[] = 'Overall content-quality score is below the indexing threshold.';
        }

        return $this->result('model', $score, $reasons, [
            'confidence_score' => $confidenceScore,
            'capability_notes_chars' => $notesChars,
            'capability_signals' => $capabilitySignals,
            'use_case_signals' => $useCaseCount,
            'official_evidence' => $hasOfficialEvidence,
            'verified_benchmark_results' => $verifiedBenchmarks,
            'pricing_verified' => $pricingVerified,
        ]);
    }

    /**
     * Phase 5: article indexability is based on total editorial value rather
     * than a single arbitrary word-count cutoff. Shorter practical guides can
     * qualify when they have strong structure, review and useful context.
     */
    public function article(Article $article): array
    {
        return $this->articleQuality->assess($article);
    }

    /**
     * News indexing requires AI relevance (Phase 2) plus AI Orbit's own useful
     * summary/context. A relevant syndicated headline alone is not indexable.
     */
    public function news(NewsItem $news): array
    {
        $summary = $this->plainText($news->ai_summary ?: $news->summary);
        $why = $this->plainText($news->ai_why_it_matters ?: $news->why_it_matters);
        $summaryChars = mb_strlen($summary);
        $whyChars = mb_strlen($why);
        $sourceUrl = $news->canonical_url ?: $news->source_url;

        $isDuplicate = filled($news->duplicate_of_id) || $news->duplicate_status === 'duplicate';
        $isRelevant = $news->isAiRelevant();
        $relevanceScore = (int) ($news->ai_relevance_score ?? 0);
        if (($news->ai_relevance_override ?: 'auto') === 'include' && $relevanceScore <= 0) {
            $relevanceScore = (int) config('news_relevance.accept_threshold', 60);
        }

        $score = min(30, (int) round(max(0, min(100, $relevanceScore)) * 0.30));
        $score += $summaryChars >= 180 ? 25 : ($summaryChars >= 120 ? 18 : 0);
        $score += $whyChars >= 100 ? 20 : ($whyChars >= 60 ? 14 : 0);
        $score += filled($sourceUrl) ? 10 : 0;
        $score += filled($news->source) ? 5 : 0;
        $score += filled($news->ai_processed_at) ? 4 : 0;
        $score += (int) ($news->ai_confidence ?? 0) >= 60 ? 3 : 0;
        $score += (filled($news->category) || collect($news->ai_tags ?? [])->filter()->isNotEmpty()) ? 3 : 0;
        $score = min(100, $score);

        $reasons = [];
        if ($news->status !== 'published') {
            $reasons[] = 'News item is not published.';
        }
        if ($isDuplicate) {
            $reasons[] = 'News item is a duplicate.';
        }
        if (! $isRelevant) {
            $reasons[] = 'News item did not pass the AI relevance gate.';
        }
        if ($summaryChars < (int) config('seo_content_quality.news.min_summary_chars', 120)) {
            $reasons[] = 'AI Orbit summary is too thin.';
        }
        if ($whyChars < (int) config('seo_content_quality.news.min_why_it_matters_chars', 60)) {
            $reasons[] = 'Why-it-matters context is too thin.';
        }
        if (! filled($sourceUrl)) {
            $reasons[] = 'Original/canonical source URL is missing.';
        }
        if ($score < (int) config('seo_content_quality.news.index_threshold', 60)) {
            $reasons[] = 'Overall content-quality score is below the indexing threshold.';
        }

        return $this->result('news', $score, $reasons, [
            'relevance_score' => $relevanceScore,
            'summary_chars' => $summaryChars,
            'why_it_matters_chars' => $whyChars,
            'source_url_present' => filled($sourceUrl),
            'ai_processing_confidence' => (int) ($news->ai_confidence ?? 0),
        ]);
    }

    public function robots(array $assessment): string
    {
        return ($assessment['indexable'] ?? false)
            ? 'index,follow,max-image-preview:large,max-snippet:-1,max-video-preview:-1'
            : 'noindex,follow';
    }

    private function result(string $type, int $score, array $reasons, array $metrics): array
    {
        $reasons = array_values(array_unique(array_filter($reasons)));
        $indexable = $reasons === [];

        return [
            'type' => $type,
            'indexable' => $indexable,
            'score' => min(100, max(0, $score)),
            'label' => match (true) {
                $indexable && $score >= 80 => 'Strong',
                $indexable => 'Indexable',
                $score >= 50 => 'Needs enrichment',
                default => 'Thin / incomplete',
            },
            'robots' => $indexable
                ? 'index,follow,max-image-preview:large,max-snippet:-1,max-video-preview:-1'
                : 'noindex,follow',
            'reasons' => $reasons,
            'metrics' => $metrics,
        ];
    }

    private function plainText(?string $value): string
    {
        $value = html_entity_decode(strip_tags((string) $value), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $value = preg_replace('/^#{1,6}\s+/mu', '', $value) ?? $value;
        $value = preg_replace('/[`*_>#\[\]()]+/u', ' ', $value) ?? $value;
        $value = preg_replace('/\s+/u', ' ', $value) ?? $value;

        return trim($value);
    }

    private function wordCount(?string $value): int
    {
        $text = $this->plainText($value);
        if ($text === '') {
            return 0;
        }

        preg_match_all('/[\p{L}\p{N}]+(?:[’\'\-][\p{L}\p{N}]+)*/u', $text, $matches);

        return count($matches[0] ?? []);
    }

    private function relationCount(object $model, string $relation): int
    {
        if (method_exists($model, 'relationLoaded') && $model->relationLoaded($relation)) {
            $value = $model->getRelation($relation);
            return $value instanceof Collection ? $value->count() : ($value ? 1 : 0);
        }

        return method_exists($model, $relation) ? (int) $model->{$relation}()->count() : 0;
    }

    private function relationCollection(object $model, string $relation): Collection
    {
        if (method_exists($model, 'relationLoaded') && $model->relationLoaded($relation)) {
            $value = $model->getRelation($relation);
            return $value instanceof Collection ? $value : collect($value ? [$value] : []);
        }

        return method_exists($model, $relation) ? $model->{$relation}()->get() : collect();
    }
}
