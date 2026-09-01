<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\AiModel;
use App\Models\Article;
use App\Models\Benchmark;
use App\Models\Comparison;
use App\Models\Category;
use App\Models\Feature;
use App\Models\NewsItem;
use App\Models\Review;
use App\Models\Subcategory;
use App\Models\Tool;
use App\Models\UseCase;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;

class SeoSitemapController extends Controller
{
    public function index(): Response
    {
        $sitemaps = collect([
            route('sitemap.companies'),
            route('sitemap.tools'),
            route('sitemap.models'),
            route('sitemap.news'),
            route('sitemap.articles'),
            route('sitemap.reviews'),
            route('sitemap.pricing'),
            route('sitemap.comparisons'),
            route('sitemap.benchmarks'),
            route('sitemap.taxonomy'),
            route('sitemap.pages'),
        ]);

        $body = view('frontend.sitemaps.index', compact('sitemaps'))->render();
        return $this->xmlResponse($body);
    }

    public function tools(): Response
    {
        $items = Tool::query()
            ->where('status', 'published')
            ->select(['id', 'slug', 'updated_at'])
            ->withMax('pricingPlans', 'updated_at')
            ->withMax(['reviews as public_reviews_updated_at' => fn ($query) => $query->where('status', 'published')], 'updated_at')
            ->withMax(['benchmarkResults as verified_benchmarks_updated_at' => fn ($query) => $query->where('verified', true)->where('status', 'verified')], 'updated_at')
            ->orderBy('id')
            ->get()
            ->each(function (Tool $tool) {
                $tool->updated_at = $this->latestTimestamp([
                    $tool->updated_at,
                    $tool->pricing_plans_max_updated_at,
                    $tool->public_reviews_updated_at,
                    $tool->verified_benchmarks_updated_at,
                ]);
            });

        return $this->xml($items, fn ($item) => route('tools.show', $item));
    }

    public function models(): Response
    {
        $items = AiModel::query()
            ->whereIn('status', ['active', 'preview'])
            ->select(['id', 'slug', 'updated_at'])
            ->withMax(['reviews as public_reviews_updated_at' => fn ($query) => $query->where('status', 'published')], 'updated_at')
            ->withMax(['benchmarkResults as verified_benchmarks_updated_at' => fn ($query) => $query->where('verified', true)->where('status', 'verified')], 'updated_at')
            ->orderBy('id')
            ->get()
            ->each(function (AiModel $model) {
                $model->updated_at = $this->latestTimestamp([
                    $model->updated_at,
                    $model->public_reviews_updated_at,
                    $model->verified_benchmarks_updated_at,
                ]);
            });

        return $this->xml($items, fn ($item) => route('models.show', $item));
    }

    public function news(): Response
    {
        $items = NewsItem::query()
            ->where('status', 'published')
            ->whereNull('duplicate_of_id')
            ->where(function ($query) {
                $query->whereNull('duplicate_status')->orWhere('duplicate_status', '!=', 'duplicate');
            })
            ->select(['slug', 'updated_at'])
            ->orderByDesc('published_at')
            ->get();
        return $this->xml($items, fn($item)=>route('news.show',$item));
    }

    public function articles(): Response
    {
        $items=Article::query()->where('status','published')->where('approval_status','approved')->select(['slug','updated_at'])->orderByDesc('published_at')->get();
        return $this->xml($items, fn($item)=>route('articles.show',$item));
    }


    public function reviews(): Response
    {
        $items = Review::query()
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
            ->select(['id', 'updated_at'])
            ->orderByDesc('updated_at')
            ->get();

        return $this->xml($items, fn ($item) => route('reviews.show', $item));
    }

    public function pricing(): Response
    {
        $items = Tool::query()
            ->where('status', 'published')
            ->whereHas('pricingPlans')
            ->select(['id', 'slug', 'updated_at'])
            ->withMax('pricingPlans', 'updated_at')
            ->orderBy('id')
            ->get()
            ->each(function (Tool $tool) {
                $tool->updated_at = $this->latestTimestamp([
                    $tool->updated_at,
                    $tool->pricing_plans_max_updated_at,
                ]);
            });

        return $this->xml($items, fn ($item) => route('pricing.show', $item));
    }

    public function comparisons(): Response
    {
        $items = Comparison::query()
            ->where('status', 'published')
            ->whereNotNull('slug')
            ->select(['id', 'title', 'slug', 'comparable_type', 'item_ids', 'updated_at', 'last_verified_at'])
            ->orderByDesc('updated_at')
            ->get()
            ->each(function (Comparison $comparison) {
                $comparison->updated_at = $this->latestTimestamp([
                    $comparison->updated_at,
                    $comparison->last_verified_at,
                ]);
            })
            ->filter(function (Comparison $comparison) {
                try {
                    return $comparison->publicItems()->count() >= 2;
                } catch (\Throwable $e) {
                    report($e);
                    return false;
                }
            })
            ->values();

        return $this->xml($items, fn ($item) => route('comparisons.show', $item));
    }

    public function benchmarks(): Response
    {
        $items = Benchmark::query()
            ->where('is_active', true)
            ->whereHas('results', fn ($query) => $query->where('verified', true)->where('status', 'verified'))
            ->select(['id', 'slug', 'updated_at'])
            ->withMax(['results as verified_results_updated_at' => fn ($query) => $query->where('verified', true)->where('status', 'verified')], 'updated_at')
            ->orderBy('name')
            ->get()
            ->each(function (Benchmark $benchmark) {
                $benchmark->updated_at = $this->latestTimestamp([
                    $benchmark->updated_at,
                    $benchmark->verified_results_updated_at,
                ]);
            });

        return $this->xml($items, fn ($item) => route('benchmarks.show', $item));
    }

    public function pages(): Response
    {
        $routes = [
            'home', 'tools.index', 'models.index', 'news.index', 'comparisons.index',
            'companies.index', 'articles.index', 'reviews.index', 'pricing.index',
            'categories.index', 'features.index',
            'use-cases.index', 'topics.index', 'benchmarks.index', 'trending.index',
            'about', 'methodology', 'contact', 'privacy', 'terms', 'cookies', 'disclosures',
        ];

        $items = collect($routes)->map(fn (string $name) => (object) [
            'url' => route($name),
            'updated_at' => null,
        ]);

        $body = view('frontend.sitemaps.urls', compact('items'))->render();
        return $this->xmlResponse($body);
    }

    public function testLab(): Response
    {
        // Kept as a named endpoint for backward compatibility while Test Lab is private.
        abort_unless((bool) config('brand.features.public_test_lab', false), 404);
        return response('', 404);
    }

    public function taxonomy(): Response
    {
        $items = collect();

        Category::product()->active()->where('is_indexable', true)
            ->whereHas('tools', fn ($q) => $q->where('status', 'published'))
            ->get()->each(fn (Category $category) => $items->push((object) [
                'url' => route('categories.show', $category), 'updated_at' => $category->updated_at,
            ]));

        Subcategory::active()->where('is_indexable', true)
            ->whereHas('category', fn ($q) => $q->product()->active())
            ->whereHas('tools', fn ($q) => $q->where('status', 'published'))
            ->with('category')->get()->each(fn (Subcategory $subcategory) => $items->push((object) [
                'url' => route('categories.subcategories.show', [$subcategory->category, $subcategory]), 'updated_at' => $subcategory->updated_at,
            ]));

        Feature::active()->where('is_indexable', true)
            ->where(function ($q) {
                $q->whereHas('tools', fn ($tools) => $tools->where('status', 'published'))
                    ->orWhereHas('models', fn ($models) => $models->whereIn('status', ['active', 'preview']));
            })->get()->each(fn (Feature $feature) => $items->push((object) [
                'url' => route('features.show', $feature), 'updated_at' => $feature->updated_at,
            ]));

        UseCase::active()->where('is_indexable', true)
            ->where(function ($q) {
                $q->whereHas('tools', fn ($tools) => $tools->where('status', 'published'))
                    ->orWhereHas('models', fn ($models) => $models->whereIn('status', ['active', 'preview']));
            })->get()->each(fn (UseCase $useCase) => $items->push((object) [
                'url' => route('use-cases.show', $useCase), 'updated_at' => $useCase->updated_at,
            ]));

        Category::content()->active()->where('is_indexable', true)
            ->whereHas('articles', fn ($q) => $q->where('status', 'published')->where('approval_status', 'approved'))
            ->get()->each(fn (Category $topic) => $items->push((object) [
                'url' => route('topics.show', $topic), 'updated_at' => $topic->updated_at,
            ]));

        $body = view('frontend.sitemaps.urls', compact('items'))->render();
        return $this->xmlResponse($body);
    }

    private function xml($items, callable $url): Response
    {
        $body = view('frontend.sitemaps.entities', compact('items', 'url'))->render();

        return $this->xmlResponse($body);
    }

    private function latestTimestamp(array $values): ?Carbon
    {
        return collect($values)
            ->filter()
            ->map(fn ($value) => $value instanceof Carbon ? $value : Carbon::parse($value))
            ->sortDesc()
            ->first();
    }

    private function xmlResponse(string $body): Response
    {
        return response($body, 200)
            ->header('Content-Type', 'application/xml; charset=UTF-8')
            // Short public caching lowers repeat sitemap rendering/database load
            // without allowing the file to become stale for long.
            ->header('Cache-Control', 'public, max-age=900, s-maxage=900');
    }
}
