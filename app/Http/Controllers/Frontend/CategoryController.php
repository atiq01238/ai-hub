<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\AiModel;
use App\Models\Article;
use App\Models\Category;
use App\Models\NewsItem;
use App\Models\Tool;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function index()
    {
        $categories = Category::query()
            ->withCount([
                'tools' => fn ($q) => $q->where('status', 'published'),
                'articles' => fn ($q) => $q->where('status', 'published'),
            ])
            ->orderByDesc('tools_count')
            ->orderBy('name')
            ->get()
            ->map(function (Category $category) {
                $category->models_count = AiModel::query()
                    ->whereIn('status', ['active', 'preview'])
                    ->whereHas('tool', fn ($q) => $q->where('category_id', $category->id))
                    ->count();

                $category->news_count = NewsItem::query()
                    ->where('status', 'published')
                    ->where(function ($q) use ($category) {
                        $q->where('category', 'like', '%' . $category->name . '%')
                            ->orWhere('ai_topic', 'like', '%' . $category->name . '%');
                    })
                    ->count();

                return $category;
            });

        $featuredTools = Tool::query()
            ->with(['company', 'category'])
            ->where('status', 'published')
            ->orderByDesc('rating')
            ->orderByDesc('popularity')
            ->take(8)
            ->get();

        return view('frontend.categories.index', compact('categories', 'featuredTools'));
    }

    public function show(Request $request, Category $category)
    {
        $sort = (string) $request->query('sort', 'top');

        $toolsQuery = Tool::query()
            ->with(['company', 'category'])
            ->where('status', 'published')
            ->where('category_id', $category->id);

        match ($sort) {
            'newest' => $toolsQuery->orderByDesc('published_at'),
            'popular' => $toolsQuery->orderByDesc('popularity'),
            default => $toolsQuery->orderByDesc('rating')->orderByDesc('popularity'),
        };

        $tools = $toolsQuery->paginate(12)->withQueryString();

        $modelBase = AiModel::query()
            ->whereIn('status', ['active', 'preview'])
            ->whereHas('tool', fn ($q) => $q->where('category_id', $category->id));
        $modelsCount = (clone $modelBase)->count();
        $models = $modelBase->with(['company', 'tool'])->orderByDesc('benchmark_score')->take(8)->get();

        $articleBase = Article::query()
            ->where('status', 'published')
            ->where(function ($q) use ($category) {
                $q->where('category_id', $category->id)
                    ->orWhere('category', 'like', '%' . $category->name . '%');
            });
        $articlesCount = (clone $articleBase)->count();
        $articles = $articleBase->with(['author', 'company'])->orderByDesc('published_at')->take(6)->get();

        $newsBase = NewsItem::query()
            ->where('status', 'published')
            ->where(function ($q) {
                $q->whereNull('duplicate_status')->orWhere('duplicate_status', '!=', 'duplicate');
            })
            ->where(function ($q) use ($category) {
                $q->where('category', 'like', '%' . $category->name . '%')
                    ->orWhere('ai_topic', 'like', '%' . $category->name . '%')
                    ->orWhere('headline', 'like', '%' . $category->name . '%');
            });
        $newsCount = (clone $newsBase)->count();
        $news = $newsBase->with('company')->orderByDesc('published_at')->take(6)->get();

        $relatedCategories = Category::query()
            ->withCount(['tools' => fn ($q) => $q->where('status', 'published')])
            ->where('id', '!=', $category->id)
            ->orderByDesc('tools_count')
            ->take(6)
            ->get();

        $stats = [
            'tools' => $tools->total(),
            'models' => $modelsCount,
            'articles' => $articlesCount,
            'news' => $newsCount,
        ];

        return view('frontend.categories.show', compact(
            'category', 'tools', 'models', 'articles', 'news', 'relatedCategories', 'stats', 'sort'
        ));
    }
}
