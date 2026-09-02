<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\AiModel;
use App\Models\Article;
use App\Models\BenchmarkResult;
use App\Models\Category;
use App\Models\Company;
use App\Models\Comparison;
use App\Models\NewsItem;
use App\Models\PricingPlan;
use App\Models\Review;
use App\Models\Tool;
use App\Services\Analytics\ToolTrendingService;

class HomeController extends Controller
{
    public function index(ToolTrendingService $toolTrending)
    {
        $categories = Category::query()
            ->product()->active()
            ->where('is_indexable', true)
            ->withCount(['tools' => fn ($q) => $q->where('status', 'published')])
            ->having('tools_count', '>', 0)
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

        $trendingTools = $toolTrending->homepage(6);

        $latestNews = NewsItem::query()
            ->with('company')
            ->where('status', 'published')
            ->whereNull('duplicate_of_id')
            ->where(function ($q) {
                $q->whereNull('duplicate_status')->orWhere('duplicate_status', '!=', 'duplicate');
            })
            ->orderByDesc('published_at')
            ->take(4)
            ->get();

        $comparisons = Comparison::query()
            ->where('status', 'published')
            ->orderByDesc('views')
            ->get()
            ->filter(function (Comparison $comparison) {
                try {
                    $comparison->resolved_items = $comparison->publicItems();
                    return $comparison->resolved_items->count() >= 2;
                } catch (\Throwable $e) {
                    report($e);
                    return false;
                }
            })
            ->take(4)
            ->values();

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
            ->orderByDesc('rating')
            ->orderByDesc('created_at')
            ->take(4)
            ->get();

        $featuredArticles = Article::query()
            ->with(['author', 'company'])
            ->where('status', 'published')
            ->where('approval_status', 'approved')
            ->orderByDesc('published_at')
            ->take(4)
            ->get();

        $topCompanies = Company::query()
            ->seoIndexable()
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

        $newsCategoryCounts = NewsItem::query()
            ->where('status', 'published')
            ->whereNull('duplicate_of_id')
            ->where(fn ($query) => $query->whereNull('duplicate_status')->orWhere('duplicate_status', '!=', 'duplicate'))
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
            'newsCategoryCounts',
        ));
    }
}
