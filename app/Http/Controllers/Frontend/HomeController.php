<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\AiModel;
use App\Models\AiTest;
use App\Models\Article;
use App\Models\BenchmarkResult;
use App\Models\Category;
use App\Models\Company;
use App\Models\Comparison;
use App\Models\NewsItem;
use App\Models\PricingPlan;
use App\Models\Review;
use App\Models\Tool;

class HomeController extends Controller
{
    public function index()
    {
        $categories = Category::query()
            ->product()->active()
            ->withCount(['tools' => fn ($q) => $q->where('status', 'published')])
            ->orderByDesc('tools_count')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->take(8)
            ->get();

        $bestTools = Tool::query()
            ->with(['company', 'category'])
            ->where('status', 'published')
            ->orderByDesc('rating')
            ->orderByDesc('popularity')
            ->take(8)
            ->get();

        $popularTools = Tool::query()
            ->with(['company', 'category'])
            ->where('status', 'published')
            ->orderByDesc('popularity')
            ->orderByDesc('rating')
            ->take(5)
            ->get();

        $trendingTools = Tool::query()
            ->where('status', 'published')
            ->orderByDesc('popularity')
            ->take(6)
            ->get();

        $latestNews = NewsItem::query()
            ->with('company')
            ->where('status', 'published')
            ->where(function ($q) {
                $q->whereNull('duplicate_status')->orWhere('duplicate_status', '!=', 'duplicate');
            })
            ->orderByDesc('published_at')
            ->take(4)
            ->get();

        $comparisons = Comparison::query()
            ->where('status', 'published')
            ->orderByDesc('views')
            ->take(4)
            ->get()
            ->map(function (Comparison $comparison) {
                $comparison->resolved_items = $comparison->items();
                return $comparison;
            });

        $featuredModels = AiModel::query()
            ->with(['company', 'tool'])
            ->where('status', 'active')
            ->orderByDesc('benchmark_score')
            ->take(6)
            ->get();

        $recentModels = AiModel::query()
            ->with(['company', 'tool'])
            ->where('status', 'active')
            ->orderByDesc('release_date')
            ->take(6)
            ->get();

        $recentTools = Tool::query()
            ->with(['company', 'category'])
            ->where('status', 'published')
            ->orderByDesc('published_at')
            ->take(6)
            ->get();

        $pricingPicks = PricingPlan::query()
            ->with('tool.company')
            ->whereHas('tool', fn ($q) => $q->where('status', 'published'))
            ->orderByRaw('CASE WHEN monthly_price = 0 THEN 0 ELSE 1 END')
            ->orderBy('monthly_price')
            ->take(6)
            ->get();

        $latestReviews = Review::query()
            ->with(['tool.company', 'model.company', 'user'])
            ->published()
            ->where(function ($query) {
                $query->whereHas('tool', fn ($tool) => $tool->where('status', 'published'))
                    ->orWhereHas('model', fn ($model) => $model->whereIn('status', ['active', 'preview']));
            })
            ->orderByDesc('rating')
            ->orderByDesc('created_at')
            ->take(4)
            ->get();

        $featuredArticles = Article::query()
            ->with(['author', 'company'])
            ->where('status', 'published')
            ->orderByDesc('published_at')
            ->take(4)
            ->get();

        $topCompanies = Company::query()
            ->withCount([
                'tools' => fn ($q) => $q->where('status', 'published'),
                'models' => fn ($q) => $q->where('status', 'active'),
            ])
            ->where('status', 'active')
            ->orderByDesc('tools_count')
            ->orderByDesc('models_count')
            ->take(8)
            ->get();

        $benchmarkGroups = BenchmarkResult::query()
            ->with(['benchmark', 'benchmarkable'])
            ->where('verified', true)
            ->where('benchmarkable_type', AiModel::class)
            ->orderByDesc('score')
            ->get()
            ->groupBy('benchmark_id')
            ->map(fn ($rows) => $rows->take(3))
            ->take(4);

        $testLab = AiTest::query()->published()->with(['completedResults.model'])->orderByDesc('is_featured')->latest('published_at')->first();

        $newsCategoryCounts = NewsItem::query()
            ->where('status', 'published')
            ->selectRaw('category, COUNT(*) as total')
            ->whereNotNull('category')
            ->groupBy('category')
            ->orderByDesc('total')
            ->take(7)
            ->pluck('total', 'category');

        return view('frontend.home.index', compact(
            'categories',
            'bestTools',
            'popularTools',
            'trendingTools',
            'latestNews',
            'comparisons',
            'featuredModels',
            'recentModels',
            'recentTools',
            'pricingPicks',
            'latestReviews',
            'featuredArticles',
            'topCompanies',
            'benchmarkGroups',
            'testLab',
            'newsCategoryCounts',
        ));
    }
}
