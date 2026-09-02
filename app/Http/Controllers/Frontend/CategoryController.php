<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\AiModel;
use App\Models\Article;
use App\Models\Category;
use App\Models\NewsItem;
use App\Models\Subcategory;
use App\Models\Tool;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function index()
    {
        $categories = Category::query()
            ->product()->active()
            ->where('is_indexable', true)
            ->whereHas('tools', fn ($q) => $q->where('status', 'published'))
            ->withCount(['tools' => fn ($q) => $q->where('status', 'published')])
            ->with(['subcategories' => fn ($q) => $q->active()->orderBy('sort_order')])
            ->orderBy('sort_order')->orderBy('name')
            ->get()
            ->map(function (Category $category) {
                $category->models_count = AiModel::query()
                    ->whereIn('status', ['active', 'preview'])
                    ->whereHas('tool', fn ($q) => $q->where('category_id', $category->id))
                    ->count();
                $category->articles_count = Article::query()
                    ->where('status', 'published')->where('approval_status', 'approved')
                    ->whereHas('relatedToolTerms', fn ($q) => $q->where('category_id', $category->id))
                    ->count();
                $category->news_count = $this->newsForCategory($category)->count();
                return $category;
            });

        $featuredTools = Tool::query()
            ->with(['company', 'category'])
            ->where('status', 'published')
            ->orderByDesc('rating')->orderByDesc('popularity')
            ->take(8)->get();

        return view('frontend.categories.index', compact('categories', 'featuredTools'));
    }


    public function legacy(string $legacySlug)
    {
        $definition = collect(config('taxonomy_v2.product_categories', []))->first(function ($definition) use ($legacySlug) {
            return collect($definition['legacy'] ?? [])->contains(fn ($name) => \Illuminate\Support\Str::slug($name) === $legacySlug);
        });
        abort_unless($definition, 404);

        $category = Category::product()->active()->where('slug', $definition['slug'])->firstOrFail();
        return redirect()->route('categories.show', $category, 301);
    }

    public function show(Request $request, Category $category)
    {
        abort_unless($category->type === 'product' && $category->is_active, 404);
        $sort = (string) $request->query('sort', 'top');

        $toolsQuery = Tool::query()
            ->with(['company', 'category', 'subcategoryTerm'])
            ->where('status', 'published')->where('category_id', $category->id);
        $this->sortTools($toolsQuery, $sort);
        $tools = $toolsQuery->paginate(12)->withQueryString();

        $modelBase = AiModel::query()
            ->whereIn('status', ['active', 'preview'])
            ->whereHas('tool', fn ($q) => $q->where('category_id', $category->id));
        $modelsCount = (clone $modelBase)->count();
        $models = $modelBase->with(['company', 'tool'])->orderByDesc('benchmark_score')->take(8)->get();

        $articleBase = Article::query()
            ->where('status', 'published')->where('approval_status', 'approved')
            ->whereHas('relatedToolTerms', fn ($q) => $q->where('category_id', $category->id));
        $articlesCount = (clone $articleBase)->count();
        $articles = $articleBase->with(['author', 'company', 'categoryTerm'])->orderByDesc('published_at')->take(6)->get();

        $newsBase = $this->newsForCategory($category);
        $newsCount = (clone $newsBase)->count();
        $news = $newsBase->with('company')->orderByDesc('published_at')->take(6)->get();

        $relatedCategories = Category::query()->product()->active()
            ->where('is_indexable', true)
            ->whereHas('tools', fn ($q) => $q->where('status', 'published'))
            ->withCount(['tools' => fn ($q) => $q->where('status', 'published')])
            ->where('id', '!=', $category->id)->orderByDesc('tools_count')->take(6)->get();

        $subcategories = $category->subcategories()->active()
            ->where('is_indexable', true)
            ->whereHas('tools', fn ($q) => $q->where('status', 'published'))
            ->withCount(['tools' => fn ($q) => $q->where('status', 'published')])
            ->orderBy('sort_order')->orderBy('name')->get();

        $stats = ['tools'=>$tools->total(),'models'=>$modelsCount,'articles'=>$articlesCount,'news'=>$newsCount];
        return view('frontend.categories.show', compact('category','tools','models','articles','news','relatedCategories','subcategories','stats','sort'));
    }

    public function subcategory(Request $request, Category $category, Subcategory $subcategory)
    {
        abort_unless(
            $category->type === 'product' && $category->is_active && $subcategory->is_active && (int) $subcategory->category_id === (int) $category->id,
            404
        );

        $sort = (string) $request->query('sort', 'top');
        $query = Tool::query()->with(['company','category','subcategoryTerm'])
            ->where('status','published')->where('category_id',$category->id)->where('subcategory_id',$subcategory->id);
        $this->sortTools($query, $sort);
        $tools = $query->paginate(15)->withQueryString();

        $models = AiModel::query()->with(['company','tool'])
            ->whereIn('status',['active','preview'])
            ->whereHas('tool', fn ($q) => $q->where('subcategory_id',$subcategory->id))
            ->orderByDesc('benchmark_score')->take(10)->get();

        $relatedSubcategories = $category->subcategories()->active()
            ->where('is_indexable', true)
            ->whereHas('tools', fn ($q) => $q->where('status', 'published'))
            ->where('id','!=',$subcategory->id)
            ->withCount(['tools'=>fn($q)=>$q->where('status','published')])->orderByDesc('tools_count')->take(8)->get();

        return view('frontend.categories.subcategory', compact('category','subcategory','tools','models','relatedSubcategories','sort'));
    }

    private function sortTools($query, string $sort): void
    {
        match ($sort) {
            'newest' => $query->orderByDesc('published_at'),
            'popular' => $query->orderByDesc('popularity'),
            default => $query->orderByDesc('rating')->orderByDesc('popularity'),
        };
    }

    private function newsForCategory(Category $category)
    {
        $keywords = collect([$category->name])->merge($category->subcategories()->active()->pluck('name'))->take(8)->all();
        return NewsItem::query()->where('status','published')
            ->whereNull('duplicate_of_id')
            ->where(fn($q)=>$q->whereNull('duplicate_status')->orWhere('duplicate_status','!=','duplicate'))
            ->where(function ($q) use ($keywords) {
                foreach ($keywords as $keyword) {
                    $q->orWhere('category','like','%'.$keyword.'%')->orWhere('ai_topic','like','%'.$keyword.'%')->orWhere('headline','like','%'.$keyword.'%');
                }
            });
    }
}
