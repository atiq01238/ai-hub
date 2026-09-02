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

class AuditSeoCrawlPriority extends Command
{
    protected $signature = 'seo:audit-crawl-priority {--details : Show sample records for remaining crawl/indexing gaps}';

    protected $description = 'Audit canonical discovery, homepage authority, taxonomy quality gates and crawl-priority risks for AI Orbit.';

    public function handle(): int
    {
        $this->info('AI Orbit Phase 5 SEO crawl-priority & taxonomy-quality audit');

        $toolQuery = Tool::query()->where('status', 'published');
        $modelQuery = AiModel::query()->whereIn('status', ['active', 'preview']);
        $companyQuery = Company::query()->seoIndexable();

        $toolCount = (clone $toolQuery)->count();
        $modelCount = (clone $modelQuery)->count();
        $companyCount = (clone $companyQuery)->count();

        $categoryHubQuery = Category::query()->seoProductIndexable();
        $featureHubQuery = Feature::query()->seoIndexable();
        $useCaseHubQuery = UseCase::query()->seoIndexable();

        $this->table(
            ['Canonical directory', 'Eligible URLs', '12/page directory depth'],
            [
                ['AI Tools', $toolCount, $this->pages($toolCount, 12)],
                ['AI Models', $modelCount, $this->pages($modelCount, 12)],
                ['AI Companies', $companyCount, $this->pages($companyCount, 12)],
                ['Category hubs', (clone $categoryHubQuery)->count(), '1 hub page'],
                ['Feature hubs', (clone $featureHubQuery)->count(), '1 hub page'],
                ['Use-case hubs', (clone $useCaseHubQuery)->count(), '1 hub page'],
            ]
        );

        $toolsWithCanonicalCategory = Tool::query()
            ->where('status', 'published')
            ->whereHas('category', fn ($q) => $q->seoProductIndexable())
            ->count();

        $toolsWithIndexableCompany = Tool::query()
            ->where('status', 'published')
            ->whereHas('company', fn ($q) => $q->seoIndexable())
            ->count();

        $modelsWithIndexableCompany = AiModel::query()
            ->whereIn('status', ['active', 'preview'])
            ->whereHas('company', fn ($q) => $q->seoIndexable())
            ->count();

        $modelsWithCanonicalTaxonomy = AiModel::query()
            ->whereIn('status', ['active', 'preview'])
            ->where(function ($query) {
                $query->whereHas('featureTerms', fn ($q) => $q->seoIndexable())
                    ->orWhereHas('useCaseTerms', fn ($q) => $q->seoIndexable());
            })
            ->count();

        $companiesWithEntityRelation = Company::query()
            ->seoIndexable()
            ->where(function ($query) {
                $query->whereHas('tools', fn ($q) => $q->where('status', 'published'))
                    ->orWhereHas('models', fn ($q) => $q->whereIn('status', ['active', 'preview']));
            })
            ->count();

        $this->newLine();
        $this->table(
            ['Canonical discovery signal', 'Covered', 'Eligible', 'Coverage'],
            [
                ['Tool → indexable category hub', $toolsWithCanonicalCategory, $toolCount, $this->percent($toolsWithCanonicalCategory, $toolCount)],
                ['Tool → indexable company profile', $toolsWithIndexableCompany, $toolCount, $this->percent($toolsWithIndexableCompany, $toolCount)],
                ['Model → indexable company profile', $modelsWithIndexableCompany, $modelCount, $this->percent($modelsWithIndexableCompany, $modelCount)],
                ['Model → indexable feature/use-case hub', $modelsWithCanonicalTaxonomy, $modelCount, $this->percent($modelsWithCanonicalTaxonomy, $modelCount)],
                ['Company → public tool/model', $companiesWithEntityRelation, $companyCount, $this->percent($companiesWithEntityRelation, $companyCount)],
            ]
        );

        $homepage = $this->homepageBaseline();
        $this->newLine();
        $this->table(
            ['Static homepage authority baseline', 'Unique direct links', 'Eligible', 'Coverage'],
            [
                ['Tools (best/popular/recent; trending excluded)', $homepage['tools'], $toolCount, $this->percent($homepage['tools'], $toolCount)],
                ['Models (benchmark/recent)', $homepage['models'], $modelCount, $this->percent($homepage['models'], $modelCount)],
                ['Companies (top public)', $homepage['companies'], $companyCount, $this->percent($homepage['companies'], $companyCount)],
                ['Categories (top indexable)', $homepage['categories'], (clone $categoryHubQuery)->count(), $this->percent($homepage['categories'], (clone $categoryHubQuery)->count())],
                ['Valid comparisons', $homepage['comparisons'], $homepage['valid_comparisons'], $this->percent($homepage['comparisons'], $homepage['valid_comparisons'])],
            ]
        );

        $publicCompanies = Company::query()->public()->count();
        $thinCompanies = max(0, $publicCompanies - $companyCount);

        $riskRows = [
            ['Thin company profiles kept out of canonical discovery', $thinCompanies],
            ['Published tools without indexable category hub', max(0, $toolCount - $toolsWithCanonicalCategory)],
            ['Published tools without indexable company profile', max(0, $toolCount - $toolsWithIndexableCompany)],
            ['Public models without indexable company profile', max(0, $modelCount - $modelsWithIndexableCompany)],
            ['Public models without indexable taxonomy hub', max(0, $modelCount - $modelsWithCanonicalTaxonomy)],
            ['Product-category records withheld by empty-content quality gate', Category::query()->product()->active()->where('is_indexable', true)->whereDoesntHave('tools', fn ($q) => $q->where('status', 'published'))->count()],
            ['Subcategory records withheld by empty-content quality gate', Subcategory::query()->active()->where('is_indexable', true)->whereDoesntHave('tools', fn ($q) => $q->where('status', 'published'))->count()],
            ['Feature/use-case records withheld by empty-content quality gate', $this->emptyTaxonomyCount()],
            ['Published duplicate news excluded from discovery', NewsItem::query()->where('status', 'published')->where(fn ($q) => $q->whereNotNull('duplicate_of_id')->orWhere('duplicate_status', 'duplicate'))->count()],
            ['Published articles not approved for public discovery', Article::query()->where('status', 'published')->where('approval_status', '!=', 'approved')->count()],
            ['Published blank community reviews excluded', Review::query()->published()->where('review_type', 'user')->where(fn ($q) => $q->whereNull('body')->orWhereRaw("TRIM(body) = ''"))->count()],
            ['Published comparisons resolving fewer than 2 public items', $this->invalidComparisonCount()],
            ['Active benchmarks without verified public results', Benchmark::query()->where('is_active', true)->whereDoesntHave('results', fn ($q) => $q->where('verified', true)->where('status', 'verified'))->count()],
        ];

        $this->newLine();
        $this->table(['Crawl/indexing risk or intentional exclusion', 'Count'], $riskRows);

        $sitemapDuplicates = $this->crossSitemapDuplicates();
        $seoCollisions = $this->storedSeoCollisions();

        $this->newLine();
        $this->table(
            ['Canonical/metadata consistency check', 'Count'],
            [
                ['Exact canonical URLs duplicated across sitemap groups', $sitemapDuplicates->count()],
                ['Duplicate published Tool SEO-title override groups', $seoCollisions['tool_titles']->count()],
                ['Duplicate published Tool meta-description override groups', $seoCollisions['tool_descriptions']->count()],
                ['Duplicate approved Article SEO-title override groups', $seoCollisions['article_titles']->count()],
                ['Duplicate approved Article meta-description override groups', $seoCollisions['article_descriptions']->count()],
                ['Duplicate indexable taxonomy meta-title groups', $seoCollisions['taxonomy_titles']->count()],
                ['Duplicate indexable taxonomy meta-description groups', $seoCollisions['taxonomy_descriptions']->count()],
            ]
        );

        if ($this->option('details')) {
            $this->showDetails($sitemapDuplicates, $seoCollisions);
        }

        $this->newLine();
        $this->info('Phase 5 audit complete. Empty taxonomy records may stay in the database, but are withheld from canonical discovery until they have public content.');

        return self::SUCCESS;
    }

    private function homepageBaseline(): array
    {
        $toolIds = collect();
        Tool::query()->where('status', 'published')->orderByDesc('rating')->orderByDesc('popularity')->take(8)->pluck('id')->each(fn ($id) => $toolIds->push((int) $id));
        Tool::query()->where('status', 'published')->orderByDesc('popularity')->orderByDesc('rating')->take(5)->pluck('id')->each(fn ($id) => $toolIds->push((int) $id));
        Tool::query()->where('status', 'published')->orderByDesc('published_at')->take(6)->pluck('id')->each(fn ($id) => $toolIds->push((int) $id));

        $modelIds = collect();
        AiModel::query()->where('status', 'active')->orderByDesc('benchmark_score')->take(6)->pluck('id')->each(fn ($id) => $modelIds->push((int) $id));
        AiModel::query()->where('status', 'active')->orderByDesc('release_date')->take(6)->pluck('id')->each(fn ($id) => $modelIds->push((int) $id));

        $companyCount = Company::query()
            ->seoIndexable()
            ->withCount([
                'tools' => fn ($q) => $q->where('status', 'published'),
                'models' => fn ($q) => $q->where('status', 'active'),
            ])
            ->where('status', 'active')
            ->orderByDesc('tools_count')
            ->orderByDesc('models_count')
            ->take(8)
            ->pluck('id')
            ->unique()
            ->count();

        $categoryCount = Category::query()
            ->seoProductIndexable()
            ->withCount(['tools' => fn ($q) => $q->where('status', 'published')])
            ->orderByDesc('tools_count')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->take(8)
            ->pluck('id')
            ->unique()
            ->count();

        $validComparisons = $this->validComparisons();

        return [
            'tools' => $toolIds->unique()->count(),
            'models' => $modelIds->unique()->count(),
            'companies' => $companyCount,
            'categories' => $categoryCount,
            'comparisons' => $validComparisons->take(4)->count(),
            'valid_comparisons' => $validComparisons->count(),
        ];
    }

    private function validComparisons(): Collection
    {
        return Comparison::query()
            ->where('status', 'published')
            ->orderByDesc('views')
            ->get()
            ->filter(function (Comparison $comparison) {
                try {
                    return $comparison->publicItems()->count() >= 2;
                } catch (\Throwable $e) {
                    return false;
                }
            })
            ->values();
    }

    private function invalidComparisonCount(): int
    {
        $published = Comparison::query()->where('status', 'published')->count();
        return max(0, $published - $this->validComparisons()->count());
    }

    private function emptyTaxonomyCount(): int
    {
        $emptyFeatures = Feature::query()
            ->active()->where('is_indexable', true)
            ->whereDoesntHave('tools', fn ($q) => $q->where('status', 'published'))
            ->whereDoesntHave('models', fn ($q) => $q->whereIn('status', ['active', 'preview']))
            ->count();

        $emptyUseCases = UseCase::query()
            ->active()->where('is_indexable', true)
            ->whereDoesntHave('tools', fn ($q) => $q->where('status', 'published'))
            ->whereDoesntHave('models', fn ($q) => $q->whereIn('status', ['active', 'preview']))
            ->count();

        return $emptyFeatures + $emptyUseCases;
    }

    private function showDetails(Collection $sitemapDuplicates, array $seoCollisions): void
    {
        $this->newLine();
        $this->comment('Sample remaining gaps (max 25 each)');

        $modelGaps = AiModel::query()
            ->whereIn('status', ['active', 'preview'])
            ->whereDoesntHave('featureTerms', fn ($q) => $q->seoIndexable())
            ->whereDoesntHave('useCaseTerms', fn ($q) => $q->seoIndexable())
            ->orderBy('name')
            ->take(25)
            ->get(['id', 'name', 'slug']);

        if ($modelGaps->isNotEmpty()) {
            $this->table(['Model ID', 'Model', 'Slug'], $modelGaps->map(fn ($model) => [$model->id, $model->name, $model->slug])->all());
        }

        $thinCompanies = Company::query()
            ->public()
            ->whereNotIn('id', Company::query()->seoIndexable()->select('id'))
            ->orderBy('name')
            ->take(25)
            ->get(['id', 'name', 'slug']);

        if ($thinCompanies->isNotEmpty()) {
            $this->table(['Company ID', 'Withheld company', 'Slug'], $thinCompanies->map(fn ($company) => [$company->id, $company->name, $company->slug])->all());
        }

        if ($sitemapDuplicates->isNotEmpty()) {
            $this->newLine();
            $this->comment('Cross-sitemap duplicate canonical URL samples');
            $this->table(
                ['URL', 'Sitemap groups'],
                $sitemapDuplicates->take(25)->map(fn ($item) => [$item['url'], implode(', ', $item['groups'])])->all()
            );
        }

        $metadataRows = collect($seoCollisions)
            ->flatMap(function (Collection $groups, string $type) {
                return $groups->take(8)->map(fn ($item) => [
                    str_replace('_', ' ', $type),
                    $item['count'],
                    mb_strimwidth($item['value'], 0, 90, '…'),
                ]);
            })
            ->values();

        if ($metadataRows->isNotEmpty()) {
            $this->newLine();
            $this->comment('Duplicate stored SEO override samples');
            $this->table(['Field group', 'Occurrences', 'Value'], $metadataRows->all());
        }
    }

    private function crossSitemapDuplicates(): Collection
    {
        $groups = collect([
            'companies' => Company::query()->seoIndexable()->get()->map(fn (Company $company) => route('companies.show', $company)),
            'tools' => Tool::query()->where('status', 'published')->get()->map(fn (Tool $tool) => route('tools.show', $tool)),
            'models' => AiModel::query()->whereIn('status', ['active', 'preview'])->get()->map(fn (AiModel $model) => route('models.show', $model)),
            'news' => NewsItem::query()->where('status', 'published')->whereNull('duplicate_of_id')->where(fn ($q) => $q->whereNull('duplicate_status')->orWhere('duplicate_status', '!=', 'duplicate'))->get()->map(fn (NewsItem $news) => route('news.show', $news)),
            'articles' => Article::query()->where('status', 'published')->where('approval_status', 'approved')->get()->map(fn (Article $article) => route('articles.show', $article)),
            'reviews' => Review::query()->published()
                ->where(fn ($q) => $q->where('review_type', 'editorial')->orWhere(fn ($user) => $user->where('review_type', 'user')->whereNotNull('body')->whereRaw("TRIM(body) <> ''")))
                ->where(fn ($q) => $q->whereHas('tool', fn ($tool) => $tool->where('status', 'published'))->orWhereHas('model', fn ($model) => $model->whereIn('status', ['active', 'preview'])))
                ->get()->map(fn (Review $review) => route('reviews.show', $review)),
            'pricing' => Tool::query()->where('status', 'published')->whereHas('pricingPlans')->get()->map(fn (Tool $tool) => route('pricing.show', $tool)),
            'comparisons' => $this->validComparisons()->map(fn (Comparison $comparison) => route('comparisons.show', $comparison)),
            'benchmarks' => Benchmark::query()->where('is_active', true)->whereHas('results', fn ($q) => $q->where('verified', true)->where('status', 'verified'))->get()->map(fn (Benchmark $benchmark) => route('benchmarks.show', $benchmark)),
            'taxonomy' => $this->taxonomyUrls(),
            'pages' => collect($this->staticPageRoutes())->map(fn (string $name) => route($name)),
        ]);

        $seen = [];
        foreach ($groups as $group => $urls) {
            foreach ($urls->filter()->unique() as $url) {
                $seen[$url] ??= [];
                $seen[$url][] = $group;
            }
        }

        return collect($seen)
            ->filter(fn (array $groupsForUrl) => count(array_unique($groupsForUrl)) > 1)
            ->map(fn (array $groupsForUrl, string $url) => ['url' => $url, 'groups' => array_values(array_unique($groupsForUrl))])
            ->values();
    }

    private function taxonomyUrls(): Collection
    {
        $urls = collect();

        Category::query()
            ->seoProductIndexable()
            ->get()
            ->each(fn (Category $category) => $urls->push(route('categories.show', $category)));

        Subcategory::query()
            ->seoIndexable()
            ->with('category')
            ->get()
            ->each(fn (Subcategory $subcategory) => $urls->push(route('categories.subcategories.show', [$subcategory->category, $subcategory])));

        Feature::query()
            ->seoIndexable()
            ->get()
            ->each(fn (Feature $feature) => $urls->push(route('features.show', $feature)));

        UseCase::query()
            ->seoIndexable()
            ->get()
            ->each(fn (UseCase $useCase) => $urls->push(route('use-cases.show', $useCase)));

        Category::query()
            ->seoContentIndexable()
            ->get()
            ->each(fn (Category $topic) => $urls->push(route('topics.show', $topic)));

        return $urls->unique()->values();
    }

    private function staticPageRoutes(): array
    {
        return [
            'home', 'tools.index', 'models.index', 'news.index', 'comparisons.index',
            'companies.index', 'articles.index', 'reviews.index', 'pricing.index',
            'categories.index', 'features.index', 'use-cases.index', 'topics.index',
            'benchmarks.index', 'trending.index', 'about', 'methodology', 'contact',
            'privacy', 'terms', 'cookies', 'disclosures',
        ];
    }

    private function storedSeoCollisions(): array
    {
        $toolQuery = Tool::query()->where('status', 'published');
        $articleQuery = Article::query()->where('status', 'published')->where('approval_status', 'approved');

        $taxonomy = collect()
            ->merge(Category::query()->seoProductIndexable()->get(['meta_title', 'meta_description']))
            ->merge(Category::query()->seoContentIndexable()->get(['meta_title', 'meta_description']))
            ->merge(Subcategory::query()->seoIndexable()->get(['meta_title', 'meta_description']))
            ->merge(Feature::query()->seoIndexable()->get(['meta_title', 'meta_description']))
            ->merge(UseCase::query()->seoIndexable()->get(['meta_title', 'meta_description']));

        return [
            'tool_titles' => $this->duplicateValues((clone $toolQuery)->pluck('seo_title')),
            'tool_descriptions' => $this->duplicateValues((clone $toolQuery)->pluck('meta_description')),
            'article_titles' => $this->duplicateValues((clone $articleQuery)->pluck('seo_title')),
            'article_descriptions' => $this->duplicateValues((clone $articleQuery)->pluck('meta_description')),
            'taxonomy_titles' => $this->duplicateValues($taxonomy->pluck('meta_title')),
            'taxonomy_descriptions' => $this->duplicateValues($taxonomy->pluck('meta_description')),
        ];
    }

    private function duplicateValues(Collection $values): Collection
    {
        return $values
            ->filter(fn ($value) => is_string($value) && trim($value) !== '')
            ->map(fn (string $value) => trim(preg_replace('/\s+/u', ' ', $value)))
            ->groupBy(fn (string $value) => mb_strtolower($value))
            ->filter(fn (Collection $group) => $group->count() > 1)
            ->map(fn (Collection $group) => ['value' => $group->first(), 'count' => $group->count()])
            ->sortByDesc('count')
            ->values();
    }

    private function pages(int $count, int $perPage): int
    {
        return max(1, (int) ceil($count / max(1, $perPage)));
    }

    private function percent(int $covered, int $eligible): string
    {
        if ($eligible <= 0) {
            return '—';
        }

        return number_format(($covered / $eligible) * 100, 1).'%';
    }
}
