<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\AiModel;
use App\Models\AiTest;
use App\Models\Article;
use App\Models\Benchmark;
use App\Models\Comparison;
use App\Models\Category;
use App\Models\Feature;
use App\Models\NewsItem;
use App\Models\Subcategory;
use App\Models\Tool;
use App\Models\UseCase;
use Illuminate\Http\Response;

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
            route('sitemap.comparisons'),
            route('sitemap.benchmarks'),
            route('sitemap.taxonomy'),
            route('sitemap.testlab'),
            route('sitemap.pages'),
        ]);

        $body = view('frontend.sitemaps.index', compact('sitemaps'))->render();
        return response($body, 200)->header('Content-Type', 'application/xml; charset=UTF-8');
    }

    public function tools(): Response
    {
        $items = Tool::query()->where('status','published')->select(['slug','updated_at'])->orderBy('id')->get();
        return $this->xml($items, fn($item)=>route('tools.show',$item));
    }

    public function models(): Response
    {
        $items = AiModel::query()->whereIn('status',['active','preview'])->select(['slug','updated_at'])->orderBy('id')->get();
        return $this->xml($items, fn($item)=>route('models.show',$item));
    }

    public function news(): Response
    {
        $items=NewsItem::query()->where('status','published')->whereNull('duplicate_of_id')->select(['slug','updated_at'])->orderByDesc('published_at')->get();
        return $this->xml($items, fn($item)=>route('news.show',$item));
    }

    public function articles(): Response
    {
        $items=Article::query()->where('status','published')->where('approval_status','approved')->select(['slug','updated_at'])->orderByDesc('published_at')->get();
        return $this->xml($items, fn($item)=>route('articles.show',$item));
    }


    public function comparisons(): Response
    {
        $items = Comparison::query()
            ->where('status', 'published')
            ->whereNotNull('slug')
            ->select(['slug', 'updated_at'])
            ->orderByDesc('updated_at')
            ->get();

        return $this->xml($items, fn ($item) => route('comparisons.show', $item));
    }

    public function benchmarks(): Response
    {
        $items = Benchmark::query()
            ->where('is_active', true)
            ->whereHas('results')
            ->select(['slug', 'updated_at'])
            ->orderBy('name')
            ->get();

        return $this->xml($items, fn ($item) => route('benchmarks.show', $item));
    }

    public function pages(): Response
    {
        $routes = [
            'home', 'tools.index', 'models.index', 'news.index', 'comparisons.index',
            'companies.index', 'articles.index', 'reviews.index', 'testlab.index',
            'testlab.leaderboard', 'pricing.index', 'categories.index', 'features.index',
            'use-cases.index', 'topics.index', 'benchmarks.index', 'trending.index',
            'about', 'methodology', 'contact', 'privacy', 'terms', 'cookies', 'disclosures',
        ];

        $items = collect($routes)->map(fn (string $name) => (object) [
            'url' => route($name),
            'updated_at' => null,
        ]);

        $body = view('frontend.sitemaps.urls', compact('items'))->render();
        return response($body, 200)->header('Content-Type', 'application/xml; charset=UTF-8');
    }

    public function testLab(): Response
    {
        $items = AiTest::query()->published()
            ->whereHas('completedResults')
            ->select(['slug', 'updated_at'])
            ->orderByDesc('published_at')
            ->get();

        return $this->xml($items, fn ($item) => route('testlab.show', $item));
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
        return response($body, 200)->header('Content-Type', 'application/xml; charset=UTF-8');
    }

    private function xml($items, callable $url): Response
    {
        $body = view('frontend.sitemaps.entities', compact('items','url'))->render();
        return response($body, 200)->header('Content-Type','application/xml; charset=UTF-8');
    }
}
