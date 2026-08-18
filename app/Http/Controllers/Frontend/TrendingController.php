<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\AiModel;
use App\Models\Company;
use App\Models\Comparison;
use App\Models\NewsItem;
use App\Models\Tool;
use Illuminate\Http\Request;

class TrendingController extends Controller
{
    public function index(Request $request)
    {
        $tab = $request->string('tab')->toString();
        $tab = in_array($tab, ['all', 'tools', 'models', 'news', 'companies', 'comparisons'], true) ? $tab : 'all';

        $tools = Tool::query()
            ->with(['company', 'category'])
            ->where('status', 'published')
            ->orderByDesc('popularity')
            ->orderByDesc('rating')
            ->limit(12)
            ->get();

        $models = AiModel::query()
            ->with(['company', 'tool'])
            ->whereIn('status', ['active', 'preview'])
            ->orderByDesc('benchmark_score')
            ->orderByDesc('release_date')
            ->limit(12)
            ->get();

        $news = NewsItem::query()
            ->with('company')
            ->where('status', 'published')
            ->where(function ($query) {
                $query->whereNull('duplicate_status')->orWhere('duplicate_status', '!=', 'duplicate');
            })
            ->orderByDesc('importance')
            ->orderByDesc('published_at')
            ->limit(10)
            ->get();

        $companies = Company::query()
            ->withCount([
                'tools' => fn ($query) => $query->where('status', 'published'),
                'models' => fn ($query) => $query->whereIn('status', ['active', 'preview']),
                'newsItems' => fn ($query) => $query->where('status', 'published'),
            ])
            ->where('status', 'active')
            ->get()
            ->sortByDesc(fn ($company) => ($company->tools_count * 3) + ($company->models_count * 4) + min($company->news_items_count, 10))
            ->take(10)
            ->values();

        $comparisons = Comparison::query()
            ->where('status', 'published')
            ->orderByDesc('views')
            ->orderByDesc('updated_at')
            ->limit(8)
            ->get()
            ->map(function (Comparison $comparison) {
                $comparison->resolved_items = $comparison->items();
                return $comparison;
            });

        return view('frontend.trending.index', [
            'tab' => $tab,
            'tools' => $tools,
            'models' => $models,
            'news' => $news,
            'companies' => $companies,
            'comparisons' => $comparisons,
            'stats' => [
                'tools' => Tool::query()->where('status', 'published')->count(),
                'models' => AiModel::query()->whereIn('status', ['active', 'preview'])->count(),
                'news_today' => NewsItem::query()->where('status', 'published')->where('published_at', '>=', now()->subDay())->count(),
                'comparisons' => Comparison::query()->where('status', 'published')->count(),
            ],
        ]);
    }
}
