<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\AiModel;
use App\Models\Article;
use App\Models\BenchmarkResult;
use App\Models\Category;
use App\Models\Comparison;
use App\Models\NewsItem;
use App\Models\PricingPlan;
use App\Models\Tool;
use App\Services\Analytics\ToolTrendingService;
use App\Services\Seo\SeoContentQualityService;

class HomeController extends Controller
{
    public function index(ToolTrendingService $toolTrending, SeoContentQualityService $contentQuality)
    {
        // Keep the homepage intentionally curated. Full catalogs live on their
        // dedicated index pages; the homepage only needs enough data to help a
        // first-time visitor understand AI Orbit and choose a next step.
        $categories = Category::query()
            ->product()->active()
            ->where('is_indexable', true)
            ->withCount(['tools' => fn ($q) => $q->where('status', 'published')])
            ->having('tools_count', '>', 0)
            ->orderByDesc('tools_count')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->take(5)
            ->get();

        // Keep the classic Best AI Tools block populated even when a local
        // database has not imported community ratings yet. Rated tools still
        // rank first; unrated tools fall back to catalog popularity.
        $bestTools = Tool::query()
            ->with(['company', 'category'])
            ->where('status', 'published')
            ->orderByRaw('CASE WHEN COALESCE(rating, 0) > 0 THEN 0 ELSE 1 END')
            ->orderByDesc('rating')
            ->orderByDesc('popularity')
            ->orderBy('name')
            ->take(8)
            ->get();

        // The category tabs need their own ranked candidates. Filtering only
        // the global top eight meant a category could show one card even when
        // many published tools existed in that category. Load up to eight per
        // homepage category, then merge them into one small client-side pool.
        $bestToolsByCategory = collect();
        foreach ($categories as $category) {
            $categoryTools = Tool::query()
                ->with(['company', 'category'])
                ->where('status', 'published')
                ->where('category_id', $category->id)
                ->orderByRaw('CASE WHEN COALESCE(rating, 0) > 0 THEN 0 ELSE 1 END')
                ->orderByDesc('rating')
                ->orderByDesc('popularity')
                ->orderBy('name')
                ->take(8)
                ->get();

            $bestToolsByCategory = $bestToolsByCategory->merge($categoryTools);
        }

        $bestToolsPool = $bestTools
            ->merge($bestToolsByCategory)
            ->unique('id')
            ->values();

        // Mobile gets a deliberately small random discovery sample. This is
        // rendered separately from the desktop Best Tools ranking so the phone
        // layout can stay one-card-per-row without changing desktop ordering.
        $mobileBestTools = Tool::query()
            ->with(['company', 'category'])
            ->where('status', 'published')
            ->inRandomOrder()
            ->take(5)
            ->get();

        $trendingTools = $toolTrending->homepage(6);

        // Only quality-gated AI news appears on the homepage. The public news
        // directory can remain broader while this surface stays editorially tight.
        $latestNews = NewsItem::query()
            ->with('company')
            ->publiclyVisible()
            ->orderByDesc('published_at')
            ->take(40)
            ->get()
            ->filter(fn (NewsItem $news) => $contentQuality->news($news)['indexable'])
            ->take(2)
            ->values();

        $featuredArticles = Article::query()
            ->with(['author', 'company'])
            ->where('status', 'published')
            ->where('approval_status', 'approved')
            ->orderByDesc('published_at')
            ->take(2)
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
            ->take(3)
            ->values();

        $homepageStats = [
            'tools' => Tool::query()->where('status', 'published')->count(),
            'models' => AiModel::query()->whereIn('status', ['active', 'preview'])->count(),
            'pricing_plans' => PricingPlan::query()
                ->whereHas('tool', fn ($query) => $query->where('status', 'published'))
                ->count(),
            'verified_benchmarks' => BenchmarkResult::query()->where('verified', true)->count(),
        ];

        return view('frontend.home.index', compact(
            'categories',
            'bestTools',
            'bestToolsPool',
            'mobileBestTools',
            'trendingTools',
            'latestNews',
            'comparisons',
            'featuredArticles',
            'homepageStats',
        ));
    }
}
