<?php

namespace App\Console\Commands;

use App\Models\AiModel;
use App\Models\Article;
use App\Models\Benchmark;
use App\Models\Category;
use App\Models\Company;
use App\Models\Comparison;
use App\Models\Feature;
use App\Models\NewsItem;
use App\Models\Review;
use App\Models\Subcategory;
use App\Models\Tool;
use App\Models\UseCase;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use App\Services\Seo\SeoContentQualityService;

class AuditSeoIndexing extends Command
{
    protected $signature = 'seo:audit-indexing';

    protected $description = 'Audit public sitemap eligibility and crawl-dead-end risks for AI Orbit.';

    public function handle(SeoContentQualityService $contentQuality): int
    {
        $companyQuery = Company::query()->seoIndexable();

        $toolRows = $this->qualityTools($contentQuality);
        $modelRows = $this->qualityModels($contentQuality);
        $articleRows = $this->qualityArticles($contentQuality);
        $newsRows = $this->qualityNews($contentQuality);
        $newsQuery = NewsItem::query()->publiclyVisible();

        $reviewQuery = Review::query()
            ->published()
            ->where(function ($query) {
                $query->where('review_type', 'editorial')
                    ->orWhere(function ($community) {
                        $community->where('review_type', 'user')
                            ->whereNotNull('body')
                            ->whereRaw("TRIM(body) <> ''");
                    });
            })
            ->where(function ($query) {
                $query->whereHas('tool', fn ($tool) => $tool->where('status', 'published'))
                    ->orWhereHas('model', fn ($model) => $model->whereIn('status', ['active', 'preview']));
            });

        $comparisonRows = Comparison::query()
            ->where('status', 'published')
            ->whereNotNull('slug')
            ->get();

        $validComparisons = $comparisonRows->filter(function (Comparison $comparison) {
            try {
                return $comparison->publicItems()->count() >= 2;
            } catch (\Throwable $e) {
                return false;
            }
        });

        $benchmarkQuery = Benchmark::query()
            ->where('is_active', true)
            ->whereHas('results', fn ($query) => $query->where('verified', true)->where('status', 'verified'));

        $taxonomyCount = Category::query()->seoProductIndexable()->count()
            + Subcategory::query()->seoIndexable()->count()
            + Feature::query()->seoIndexable()->count()
            + UseCase::query()->seoIndexable()->count()
            + Category::query()->seoContentIndexable()->count();

        $publicReviewCount = (clone $reviewQuery)->count();
        // Phase 9: the empty Reviews hub is noindex and omitted from the static
        // sitemap until at least one written/editorial review exists.
        $staticPageCount = 24 + ($publicReviewCount > 0 ? 1 : 0);

        $rows = [
            ['Companies', (clone $companyQuery)->count()],
            ['Tools', $toolRows->count()],
            ['Models', $modelRows->count()],
            ['News', $newsRows->count()],
            ['Articles', $articleRows->count()],
            ['Reviews', $publicReviewCount],
            ['Pricing', Tool::query()->where('status', 'published')->whereHas('pricingPlans')->count()],
            ['Comparisons', $validComparisons->count()],
            ['Benchmarks', (clone $benchmarkQuery)->count()],
            ['Taxonomy', $taxonomyCount],
            ['Static hubs/pages', $staticPageCount],
        ];

        $this->info('AI Orbit SEO indexing audit');
        $this->table(['Sitemap group', 'Eligible URLs'], $rows);
        $this->line('Estimated sitemap URL total (before cross-sitemap deduplication): '.collect($rows)->sum(fn ($row) => $row[1]));

        $thinCompanies = Company::query()->public()->count() - (clone $companyQuery)->count();
        $toolsBlockedByQuality = Tool::query()->where('status', 'published')->count() - $toolRows->count();
        $modelsBlockedByQuality = AiModel::query()->whereIn('status', ['active', 'preview'])->count() - $modelRows->count();
        $articlesBlockedByQuality = Article::query()->where('status', 'published')->where('approval_status', 'approved')->count() - $articleRows->count();
        $newsBlockedByQuality = (clone $newsQuery)->count() - $newsRows->count();
        $invalidComparisons = $comparisonRows->count() - $validComparisons->count();
        $unverifiedBenchmarks = Benchmark::query()
            ->where('is_active', true)
            ->whereDoesntHave('results', fn ($query) => $query->where('verified', true)->where('status', 'verified'))
            ->count();
        $duplicateNews = NewsItem::query()
            ->where('status', 'published')
            ->where(function ($query) {
                $query->whereNotNull('duplicate_of_id')->orWhere('duplicate_status', 'duplicate');
            })->count();
        $newsBlockedByRelevance = NewsItem::query()
            ->where('status', 'published')
            ->whereNotIn('id', NewsItem::query()->publiclyVisible()->select('id'))
            ->count();
        $blankCommunityReviews = Review::query()
            ->published()
            ->where('review_type', 'user')
            ->where(function ($query) {
                $query->whereNull('body')->orWhereRaw("TRIM(body) = ''");
            })->count();

        $emptyProductCategories = Category::query()
            ->product()->active()->where('is_indexable', true)
            ->whereDoesntHave('tools', fn ($q) => $q->where('status', 'published'))
            ->count();

        $emptySubcategories = Subcategory::query()
            ->active()->where('is_indexable', true)
            ->whereDoesntHave('tools', fn ($q) => $q->where('status', 'published'))
            ->count();

        $emptyFeaturesAndUseCases = Feature::query()
            ->active()->where('is_indexable', true)
            ->whereDoesntHave('tools', fn ($q) => $q->where('status', 'published'))
            ->whereDoesntHave('models', fn ($q) => $q->whereIn('status', ['active', 'preview']))
            ->count()
            + UseCase::query()
                ->active()->where('is_indexable', true)
                ->whereDoesntHave('tools', fn ($q) => $q->where('status', 'published'))
                ->whereDoesntHave('models', fn ($q) => $q->whereIn('status', ['active', 'preview']))
                ->count();

        $emptyContentTopics = Category::query()
            ->content()->active()->where('is_indexable', true)
            ->whereDoesntHave('articles', fn ($q) => $q
                ->where('status', 'published')
                ->where('approval_status', 'approved'))
            ->count();

        $warnings = collect([
            ['Company profiles withheld as thin/placeholder', $thinCompanies],
            ['Published tools withheld by Phase 3 content-quality gate', $toolsBlockedByQuality],
            ['Active/preview models withheld by Phase 3 content-quality gate', $modelsBlockedByQuality],
            ['Approved articles withheld by Phase 3 content-quality gate', $articlesBlockedByQuality],
            ['AI-relevant news withheld by Phase 3 content-quality gate', $newsBlockedByQuality],
            ['Published comparisons resolving fewer than 2 items', $invalidComparisons],
            ['Active benchmarks without verified public results', $unverifiedBenchmarks],
            ['Published duplicate news excluded from crawl paths', $duplicateNews],
            ['Published news blocked by AI relevance gate', $newsBlockedByRelevance],
            ['Published blank community reviews excluded', $blankCommunityReviews],
            ['Empty product categories excluded by taxonomy quality gate', $emptyProductCategories],
            ['Empty subcategories excluded by taxonomy quality gate', $emptySubcategories],
            ['Empty features/use cases excluded by taxonomy quality gate', $emptyFeaturesAndUseCases],
            ['Empty editorial topics excluded by taxonomy quality gate', $emptyContentTopics],
        ])->filter(fn ($row) => $row[1] > 0)->values();

        if ($warnings->isEmpty()) {
            $this->info('No sitemap/public-route mismatch warnings detected.');
        } else {
            $this->newLine();
            $this->warn('Items intentionally excluded from indexable sitemap inventory:');
            $this->table(['Check', 'Count'], $warnings->all());
        }

        $this->newLine();
        $this->info('Audit complete. This command does not modify database records.');

        return self::SUCCESS;
    }

    private function qualityTools(SeoContentQualityService $quality): Collection
    {
        return Tool::query()
            ->where('status', 'published')
            ->with([
                'company:id,name', 'category:id,name', 'subcategoryTerm:id,name',
                'featureTerms:id,name', 'useCaseTerms:id,name', 'platformTerms:id,name',
                'integrationTerms:id,name', 'sources', 'factEvidence', 'pricingPlans.sources',
                'technicalProfile',
                'benchmarkResults' => fn ($query) => $query
                    ->with('benchmark')
                    ->where('verified', true)
                    ->where('status', 'verified'),
            ])
            ->orderBy('id')
            ->get()
            ->filter(fn (Tool $tool) => $quality->tool($tool)['indexable'])
            ->values();
    }

    private function qualityModels(SeoContentQualityService $quality): Collection
    {
        return AiModel::query()
            ->whereIn('status', ['active', 'preview'])
            ->with([
                'company:id,name', 'featureTerms:id,name', 'useCaseTerms:id,name',
                'pricingSources', 'evidenceSources',
                'benchmarkResults' => fn ($query) => $query
                    ->with('benchmark')
                    ->where('verified', true)
                    ->where('status', 'verified'),
            ])
            ->orderBy('id')
            ->get()
            ->filter(fn (AiModel $model) => $quality->model($model)['indexable'])
            ->values();
    }

    private function qualityArticles(SeoContentQualityService $quality): Collection
    {
        return Article::query()
            ->where('status', 'published')
            ->where('approval_status', 'approved')
            ->with(['relatedToolTerms:id', 'relatedModelTerms:id', 'tagTerms:id'])
            ->orderBy('id')
            ->get()
            ->filter(fn (Article $article) => $quality->article($article)['indexable'])
            ->values();
    }

    private function qualityNews(SeoContentQualityService $quality): Collection
    {
        return NewsItem::query()
            ->publiclyVisible()
            ->orderBy('id')
            ->get()
            ->filter(fn (NewsItem $news) => $quality->news($news)['indexable'])
            ->values();
    }
}
