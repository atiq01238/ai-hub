<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\AiModel;
use App\Models\Article;
use App\Models\Category;
use App\Models\Company;
use App\Models\NewsItem;
use App\Models\Tool;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function index(Request $request)
    {
        $query = trim((string) $request->query('q', ''));
        $type = (string) $request->query('type', 'all');
        $allowedTypes = ['all', 'tools', 'models', 'news', 'companies', 'articles'];

        if (! in_array($type, $allowedTypes, true)) {
            $type = 'all';
        }

        $counts = [
            'tools' => 0,
            'models' => 0,
            'news' => 0,
            'companies' => 0,
            'articles' => 0,
        ];

        $tools = collect();
        $models = collect();
        $news = collect();
        $companies = collect();
        $articles = collect();

        if ($query !== '') {
            $toolQuery = Tool::query()
                ->with(['company', 'category'])
                ->where('status', 'published')
                ->where(function ($builder) use ($query) {
                    $builder->where('name', 'like', "%{$query}%")
                        ->orWhere('short_description', 'like', "%{$query}%")
                        ->orWhere('description', 'like', "%{$query}%")
                        ->orWhereHas('company', fn ($q) => $q->where('name', 'like', "%{$query}%"))
                        ->orWhereHas('category', fn ($q) => $q->where('name', 'like', "%{$query}%"));
                });

            $modelQuery = AiModel::query()
                ->with(['company', 'tool'])
                ->whereIn('status', ['active', 'preview'])
                ->where(function ($builder) use ($query) {
                    $builder->where('name', 'like', "%{$query}%")
                        ->orWhere('version', 'like', "%{$query}%")
                        ->orWhere('capability_notes', 'like', "%{$query}%")
                        ->orWhereHas('company', fn ($q) => $q->where('name', 'like', "%{$query}%"))
                        ->orWhereHas('tool', fn ($q) => $q->where('name', 'like', "%{$query}%"));
                });

            $newsQuery = NewsItem::query()
                ->with('company')
                ->where('status', 'published')
                ->where(function ($q) {
                    $q->whereNull('duplicate_status')->orWhere('duplicate_status', '!=', 'duplicate');
                })
                ->where(function ($builder) use ($query) {
                    $builder->where('headline', 'like', "%{$query}%")
                        ->orWhere('summary', 'like', "%{$query}%")
                        ->orWhere('ai_summary', 'like', "%{$query}%")
                        ->orWhere('source', 'like', "%{$query}%")
                        ->orWhere('category', 'like', "%{$query}%")
                        ->orWhere('ai_topic', 'like', "%{$query}%")
                        ->orWhereHas('company', fn ($q) => $q->where('name', 'like', "%{$query}%"));
                });

            $companyQuery = Company::query()
                ->withCount([
                    'tools' => fn ($q) => $q->where('status', 'published'),
                    'models' => fn ($q) => $q->whereIn('status', ['active', 'preview']),
                ])
                ->where('status', 'active')
                ->where(function ($builder) use ($query) {
                    $builder->where('name', 'like', "%{$query}%")
                        ->orWhere('description', 'like', "%{$query}%");
                });

            $articleQuery = Article::query()
                ->with(['author', 'company', 'categoryTerm'])
                ->where('status', 'published')
                ->where(function ($builder) use ($query) {
                    $builder->where('title', 'like', "%{$query}%")
                        ->orWhere('summary', 'like', "%{$query}%")
                        ->orWhere('content', 'like', "%{$query}%")
                        ->orWhere('category', 'like', "%{$query}%")
                        ->orWhereHas('company', fn ($q) => $q->where('name', 'like', "%{$query}%"))
                        ->orWhereHas('categoryTerm', fn ($q) => $q->where('name', 'like', "%{$query}%"));
                });

            $counts = [
                'tools' => (clone $toolQuery)->count(),
                'models' => (clone $modelQuery)->count(),
                'news' => (clone $newsQuery)->count(),
                'companies' => (clone $companyQuery)->count(),
                'articles' => (clone $articleQuery)->count(),
            ];

            if ($type === 'all' || $type === 'tools') {
                $tools = $toolQuery->orderByDesc('rating')->orderByDesc('popularity')->take($type === 'tools' ? 24 : 8)->get();
            }
            if ($type === 'all' || $type === 'models') {
                $models = $modelQuery->orderByDesc('benchmark_score')->orderByDesc('release_date')->take($type === 'models' ? 24 : 8)->get();
            }
            if ($type === 'all' || $type === 'news') {
                $news = $newsQuery->orderByDesc('published_at')->take($type === 'news' ? 24 : 8)->get();
            }
            if ($type === 'all' || $type === 'companies') {
                $companies = $companyQuery->orderByDesc('tools_count')->orderBy('name')->take($type === 'companies' ? 24 : 8)->get();
            }
            if ($type === 'all' || $type === 'articles') {
                $articles = $articleQuery->orderByDesc('published_at')->take($type === 'articles' ? 24 : 8)->get();
            }
        }

        $popularCategories = Category::query()
            ->withCount(['tools' => fn ($q) => $q->where('status', 'published')])
            ->orderByDesc('tools_count')
            ->take(8)
            ->get();

        $trendingTools = Tool::query()
            ->with('company')
            ->where('status', 'published')
            ->orderByDesc('popularity')
            ->orderByDesc('rating')
            ->take(6)
            ->get();

        $total = array_sum($counts);

        return view('frontend.search.index', compact(
            'query', 'type', 'counts', 'total', 'tools', 'models', 'news', 'companies', 'articles',
            'popularCategories', 'trendingTools'
        ));
    }
}
