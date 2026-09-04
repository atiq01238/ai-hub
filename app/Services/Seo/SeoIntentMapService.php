<?php

namespace App\Services\Seo;

use App\Models\AiModel;
use App\Models\Article;
use App\Models\Benchmark;
use App\Models\Category;
use App\Models\Company;
use App\Models\Comparison;
use App\Models\Feature;
use App\Models\NewsItem;
use App\Models\Review;
use App\Models\SeoTarget;
use App\Models\Subcategory;
use App\Models\Tool;
use App\Models\UseCase;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class SeoIntentMapService
{
    /**
     * Build the current SEO target inventory from the same public/indexable
     * catalog rules used by AI Orbit's sitemap and crawl-quality gates.
     */
    public function inventory(): Collection
    {
        return collect()
            ->concat($this->staticPages())
            ->concat($this->tools())
            ->concat($this->models())
            ->concat($this->companies())
            ->concat($this->pricingPages())
            ->concat($this->comparisons())
            ->concat($this->articles())
            ->concat($this->news())
            ->concat($this->reviews())
            ->concat($this->benchmarks())
            ->concat($this->productCategories())
            ->concat($this->subcategories())
            ->concat($this->features())
            ->concat($this->useCases())
            ->concat($this->topics())
            ->filter(fn (array $target) => filled($target['primary_keyword'] ?? null))
            ->unique('target_key')
            ->values();
    }

    /**
     * Merge persisted overrides/locks onto a freshly generated inventory.
     * Auto targets continue to work even before --sync is run.
     */
    public function resolvedInventory(?Collection $inventory = null): Collection
    {
        $inventory ??= $this->inventory();

        if (! Schema::hasTable('seo_targets')) {
            return $inventory;
        }

        $stored = SeoTarget::query()
            ->whereIn('target_key', $inventory->pluck('target_key'))
            ->get()
            ->keyBy('target_key');

        return $inventory->map(function (array $generated) use ($stored) {
            $override = $stored->get($generated['target_key']);
            if (! $override) {
                return $generated;
            }

            return array_merge($generated, [
                'primary_keyword' => $override->primary_keyword ?: $generated['primary_keyword'],
                'secondary_keywords' => collect($override->secondary_keywords ?? $generated['secondary_keywords'] ?? [])->filter()->values()->all(),
                'search_intent' => $override->search_intent ?: $generated['search_intent'],
                'topic_cluster' => $override->topic_cluster ?: $generated['topic_cluster'],
                'source' => $override->source,
                'is_locked' => (bool) $override->is_locked,
                'persisted' => true,
            ]);
        })->values();
    }

    /**
     * Persist generated targets without overwriting manually locked research.
     */
    public function sync(Collection $inventory): array
    {
        if (! Schema::hasTable('seo_targets')) {
            throw new \RuntimeException('seo_targets table does not exist. Run php artisan migrate first.');
        }

        $created = 0;
        $updated = 0;
        $locked = 0;

        foreach ($inventory as $target) {
            $row = SeoTarget::query()->where('target_key', $target['target_key'])->first();

            if ($row && ($row->is_locked || $row->source === 'manual')) {
                $locked++;
                continue;
            }

            $payload = [
                'route_name' => $target['route_name'],
                'page_type' => $target['page_type'],
                'targetable_type' => $target['targetable_type'] ?? null,
                'targetable_id' => $target['targetable_id'] ?? null,
                'primary_keyword' => $target['primary_keyword'],
                'secondary_keywords' => $target['secondary_keywords'] ?? [],
                'search_intent' => $target['search_intent'],
                'topic_cluster' => $target['topic_cluster'] ?? null,
                'source' => 'auto',
                'is_locked' => false,
            ];

            if (! $row) {
                SeoTarget::query()->create(array_merge(['target_key' => $target['target_key']], $payload));
                $created++;
                continue;
            }

            $row->fill($payload);
            if ($row->isDirty()) {
                $row->save();
                $updated++;
            }
        }

        return compact('created', 'updated', 'locked');
    }

    public function normalizeKeyword(?string $keyword): string
    {
        $keyword = Str::lower(trim(strip_tags((string) $keyword)));

        // Preserve semantically meaningful plus signs before punctuation is stripped.
        // Example: "Command R+" must not normalize to the same keyword as "Command R".
        $keyword = str_replace('+', ' plus ', $keyword);

        $keyword = preg_replace('/[^\p{L}\p{N}\s]+/u', ' ', $keyword) ?? $keyword;
        $keyword = preg_replace('/\s+/u', ' ', $keyword) ?? $keyword;

        return trim($keyword);
    }

    private function staticPages(): Collection
    {
        $definitions = [
            ['home', 'home', 'AI tools and models', ['best AI tools', 'AI models', 'AI comparisons'], 'commercial_investigation', 'AI Discovery'],
            ['tools.index', 'tools_directory', 'AI tools directory', ['best AI tools', 'AI software', 'AI apps'], 'commercial_investigation', 'AI Tools'],
            ['models.index', 'models_directory', 'AI models', ['LLM models', 'multimodal AI models', 'reasoning models'], 'commercial_investigation', 'AI Models'],
            ['news.index', 'news_directory', 'AI news', ['artificial intelligence news', 'AI product updates', 'AI model releases'], 'fresh_information', 'AI News'],
            ['comparisons.index', 'comparisons_directory', 'AI comparisons', ['AI tool comparisons', 'AI model comparisons', 'compare AI tools'], 'comparison', 'AI Comparisons'],
            ['companies.index', 'companies_directory', 'AI companies', ['AI model companies', 'AI tool companies', 'artificial intelligence companies'], 'commercial_investigation', 'AI Companies'],
            ['articles.index', 'articles_directory', 'AI guides and analysis', ['AI articles', 'AI guides', 'AI analysis'], 'informational', 'AI Editorial'],
            ['reviews.index', 'reviews_directory', 'AI tool and model reviews', ['AI tool reviews', 'AI model reviews'], 'commercial_investigation', 'AI Reviews'],
            ['pricing.index', 'pricing_directory', 'AI pricing', ['AI tool pricing', 'AI API pricing', 'AI software pricing'], 'commercial_investigation', 'AI Pricing'],
            ['categories.index', 'categories_directory', 'AI tool categories', ['AI tools by category', 'AI software categories'], 'commercial_investigation', 'AI Taxonomy'],
            ['features.index', 'features_directory', 'AI tool features', ['AI capabilities', 'AI tools by feature'], 'commercial_investigation', 'AI Taxonomy'],
            ['use-cases.index', 'use_cases_directory', 'AI use cases', ['AI tools by use case', 'AI for work use cases'], 'commercial_investigation', 'AI Taxonomy'],
            ['topics.index', 'topics_directory', 'AI topics', ['AI research topics', 'AI industry topics'], 'informational', 'AI Editorial'],
            ['benchmarks.index', 'benchmarks_directory', 'AI model benchmarks', ['AI benchmark leaderboard', 'AI benchmark scores', 'LLM benchmarks'], 'commercial_investigation', 'AI Benchmarks'],
            ['trending.index', 'trending_directory', 'trending AI tools and models', ['popular AI tools', 'trending AI models', 'popular AI products'], 'fresh_information', 'AI Discovery'],
            ['about', 'about_page', 'AI Orbit', ['about AI Orbit', 'AI Orbit AI directory'], 'navigational', 'AI Orbit'],
            ['methodology', 'methodology_page', 'AI Orbit methodology', ['AI Orbit verification methodology', 'AI Orbit data methodology'], 'informational', 'AI Orbit'],
            ['contact', 'contact_page', 'contact AI Orbit', ['AI Orbit contact'], 'navigational', 'AI Orbit'],
            ['privacy', 'privacy_page', 'AI Orbit privacy policy', [], 'navigational', 'AI Orbit'],
            ['terms', 'terms_page', 'AI Orbit terms of service', [], 'navigational', 'AI Orbit'],
            ['cookies', 'cookies_page', 'AI Orbit cookie policy', [], 'navigational', 'AI Orbit'],
            ['disclosures', 'disclosures_page', 'AI Orbit disclosures', [], 'navigational', 'AI Orbit'],
        ];

        return collect($definitions)->map(fn (array $row) => $this->target(
            'static:'.$row[0],
            $row[0],
            $row[1],
            $row[2],
            $row[3],
            $row[4],
            $row[5],
        ));
    }

    private function tools(): Collection
    {
        return Tool::query()
            ->where('status', 'published')
            ->orderBy('id')
            ->get(['id', 'name'])
            ->map(fn (Tool $tool) => $this->target(
                'tools.show:'.$tool->id,
                'tools.show',
                'tool_detail',
                $tool->name.' review',
                [$tool->name.' features', $tool->name.' alternatives', $tool->name.' use cases', $tool->name.' AI tool'],
                'commercial_investigation',
                'AI Tools',
                $tool,
            ));
    }

    private function models(): Collection
    {
        return AiModel::query()
            ->whereIn('status', ['active', 'preview'])
            ->orderBy('id')
            ->get(['id', 'name'])
            ->map(fn (AiModel $model) => $this->target(
                'models.show:'.$model->id,
                'models.show',
                'model_detail',
                $model->name.' AI model',
                [$model->name.' pricing', $model->name.' benchmarks', $model->name.' context window', $model->name.' capabilities'],
                'commercial_investigation',
                'AI Models',
                $model,
            ));
    }

    private function companies(): Collection
    {
        return Company::query()
            ->seoIndexable()
            ->orderBy('id')
            ->get(['id', 'name'])
            ->map(fn (Company $company) => $this->target(
                'companies.show:'.$company->id,
                'companies.show',
                'company_detail',
                $company->name.' AI company',
                [$company->name.' AI models', $company->name.' AI tools', $company->name.' AI news'],
                'commercial_investigation',
                'AI Companies',
                $company,
            ));
    }

    private function pricingPages(): Collection
    {
        return Tool::query()
            ->where('status', 'published')
            ->whereHas('pricingPlans')
            ->orderBy('id')
            ->get(['id', 'name'])
            ->map(fn (Tool $tool) => $this->target(
                'pricing.show:'.$tool->id,
                'pricing.show',
                'tool_pricing',
                $tool->name.' pricing',
                [$tool->name.' pricing plans', $tool->name.' plans', $tool->name.' price', $tool->name.' cost'],
                'commercial_investigation',
                'AI Pricing',
                $tool,
            ));
    }

    private function comparisons(): Collection
    {
        return Comparison::query()
            ->where('status', 'published')
            ->whereNotNull('slug')
            ->orderBy('id')
            ->get()
            ->filter(function (Comparison $comparison) {
                try {
                    return $comparison->publicItems()->count() >= 2;
                } catch (\Throwable $e) {
                    report($e);
                    return false;
                }
            })
            ->map(function (Comparison $comparison) {
                $items = $comparison->publicItems()->pluck('name')->filter()->take(2)->values();
                $pair = $items->count() === 2
                    ? $items->get(0).' vs '.$items->get(1)
                    : trim((string) $comparison->title);

                $primary = $pair !== '' ? $pair : trim((string) $comparison->title);
                $secondary = $pair !== ''
                    ? [$pair.' pricing', $pair.' features', $pair.' benchmarks']
                    : [];

                return $this->target(
                    'comparisons.show:'.$comparison->id,
                    'comparisons.show',
                    'comparison_detail',
                    $primary,
                    $secondary,
                    'comparison',
                    filled($comparison->primary_intent) ? 'Comparison: '.$comparison->primary_intent : 'AI Comparisons',
                    $comparison,
                );
            })
            ->values();
    }

    private function articles(): Collection
    {
        return Article::query()
            ->where('status', 'published')
            ->where('approval_status', 'approved')
            ->orderBy('id')
            ->get(['id', 'title', 'tags'])
            ->map(fn (Article $article) => $this->target(
                'articles.show:'.$article->id,
                'articles.show',
                'article_detail',
                $this->cleanPhrase($article->title),
                collect($article->tags ?? [])->map(fn ($tag) => $this->cleanPhrase((string) $tag))->filter()->take(5)->values()->all(),
                'informational',
                'AI Editorial',
                $article,
            ));
    }

    private function news(): Collection
    {
        return NewsItem::query()
            ->where('status', 'published')
            ->whereNull('duplicate_of_id')
            ->where(fn ($query) => $query->whereNull('duplicate_status')->orWhere('duplicate_status', '!=', 'duplicate'))
            ->orderBy('id')
            ->get(['id', 'headline', 'tags', 'ai_tags'])
            ->map(function (NewsItem $news) {
                $secondary = collect($news->ai_tags ?? [])
                    ->merge($news->tags ?? [])
                    ->map(fn ($tag) => $this->cleanPhrase((string) $tag))
                    ->filter()
                    ->unique()
                    ->take(5)
                    ->values()
                    ->all();

                return $this->target(
                    'news.show:'.$news->id,
                    'news.show',
                    'news_detail',
                    $this->cleanPhrase($news->headline),
                    $secondary,
                    'fresh_information',
                    'AI News',
                    $news,
                );
            });
    }

    private function reviews(): Collection
    {
        return Review::query()
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
            })
            ->with(['tool:id,name', 'model:id,name'])
            ->orderBy('id')
            ->get(['id', 'tool_id', 'model_id', 'review_type', 'verdict'])
            ->map(function (Review $review) {
                $name = $review->reviewedItem()?->name ?: 'AI product';
                $qualifier = $review->review_type === 'editorial' ? 'editorial review' : 'user review';
                $primary = $name.' '.$qualifier;

                if (filled($review->verdict)) {
                    $primary .= ': '.$this->cleanPhrase(Str::limit((string) $review->verdict, 70, ''));
                }

                return $this->target(
                    'reviews.show:'.$review->id,
                    'reviews.show',
                    'review_detail',
                    $primary,
                    [$name.' reviews', $name.' rating'],
                    'commercial_investigation',
                    'AI Reviews',
                    $review,
                );
            });
    }

    private function benchmarks(): Collection
    {
        return Benchmark::query()
            ->where('is_active', true)
            ->whereHas('results', fn ($query) => $query->where('verified', true)->where('status', 'verified'))
            ->orderBy('id')
            ->get(['id', 'name'])
            ->map(function (Benchmark $benchmark) {
                $name = $this->cleanPhrase($benchmark->name);
                $primary = preg_match('/\bbenchmark\b/i', $name) ? $name : $name.' benchmark';

                return $this->target(
                    'benchmarks.show:'.$benchmark->id,
                    'benchmarks.show',
                    'benchmark_detail',
                    $primary,
                    [$name.' scores', $name.' leaderboard', 'AI model '.$name],
                    'commercial_investigation',
                    'AI Benchmarks',
                    $benchmark,
                );
            });
    }

    private function productCategories(): Collection
    {
        return Category::query()
            ->seoProductIndexable()
            ->orderBy('id')
            ->get(['id', 'name'])
            ->map(fn (Category $category) => $this->target(
                'categories.show:'.$category->id,
                'categories.show',
                'category_detail',
                $this->taxonomyKeyword($category->name),
                ['best '.$this->taxonomyKeyword($category->name), $category->name.' software'],
                'commercial_investigation',
                'AI Taxonomy',
                $category,
            ));
    }

    private function subcategories(): Collection
    {
        return Subcategory::query()
            ->seoIndexable()
            ->orderBy('id')
            ->get(['id', 'name'])
            ->map(fn (Subcategory $subcategory) => $this->target(
                'categories.subcategories.show:'.$subcategory->id,
                'categories.subcategories.show',
                'subcategory_detail',
                $this->taxonomyKeyword($subcategory->name),
                ['best '.$this->taxonomyKeyword($subcategory->name), $subcategory->name.' software'],
                'commercial_investigation',
                'AI Taxonomy',
                $subcategory,
            ));
    }

    private function features(): Collection
    {
        return Feature::query()
            ->seoIndexable()
            ->orderBy('id')
            ->get(['id', 'name'])
            ->map(fn (Feature $feature) => $this->target(
                'features.show:'.$feature->id,
                'features.show',
                'feature_detail',
                'AI tools with '.$feature->name,
                ['AI models with '.$feature->name, $feature->name.' AI tools'],
                'commercial_investigation',
                'AI Taxonomy',
                $feature,
            ));
    }

    private function useCases(): Collection
    {
        return UseCase::query()
            ->seoIndexable()
            ->orderBy('id')
            ->get(['id', 'name'])
            ->map(fn (UseCase $useCase) => $this->target(
                'use-cases.show:'.$useCase->id,
                'use-cases.show',
                'use_case_detail',
                'AI tools for '.$useCase->name,
                ['AI models for '.$useCase->name, 'AI for '.$useCase->name],
                'commercial_investigation',
                'AI Taxonomy',
                $useCase,
            ));
    }

    private function topics(): Collection
    {
        return Category::query()
            ->seoContentIndexable()
            ->orderBy('id')
            ->get(['id', 'name'])
            ->map(function (Category $topic) {
                $primary = preg_match('/\bAI\b/i', $topic->name)
                    ? $topic->name.' news and analysis'
                    : $topic->name.' AI news and analysis';

                return $this->target(
                    'topics.show:'.$topic->id,
                    'topics.show',
                    'topic_detail',
                    $primary,
                    [$topic->name.' AI articles', $topic->name.' AI updates'],
                    'informational',
                    'AI Editorial',
                    $topic,
                );
            });
    }

    private function target(
        string $targetKey,
        string $routeName,
        string $pageType,
        string $primaryKeyword,
        array $secondaryKeywords,
        string $searchIntent,
        ?string $topicCluster = null,
        ?object $targetable = null,
    ): array {
        return [
            'target_key' => $targetKey,
            'route_name' => $routeName,
            'page_type' => $pageType,
            'targetable_type' => $targetable ? $targetable::class : null,
            'targetable_id' => $targetable?->getKey(),
            'primary_keyword' => $this->cleanPhrase($primaryKeyword),
            'secondary_keywords' => collect($secondaryKeywords)
                ->map(fn ($keyword) => $this->cleanPhrase((string) $keyword))
                ->filter()
                ->reject(fn ($keyword) => $this->normalizeKeyword($keyword) === $this->normalizeKeyword($primaryKeyword))
                ->unique(fn ($keyword) => $this->normalizeKeyword($keyword))
                ->take(8)
                ->values()
                ->all(),
            'search_intent' => $searchIntent,
            'topic_cluster' => $topicCluster,
            'source' => 'auto',
            'is_locked' => false,
            'persisted' => false,
        ];
    }

    private function taxonomyKeyword(string $name): string
    {
        $name = $this->cleanPhrase($name);

        return preg_match('/\bAI\b/i', $name)
            ? $name.' tools'
            : $name.' AI tools';
    }

    private function cleanPhrase(?string $value): string
    {
        $value = html_entity_decode(strip_tags((string) $value), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $value = preg_replace('/\s+/u', ' ', trim($value)) ?? trim($value);

        return Str::limit($value, 250, '');
    }
}
