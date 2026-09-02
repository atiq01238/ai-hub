<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\Category;
use App\Models\Feature;
use App\Models\UseCase;

class TaxonomyDiscoveryController extends Controller
{
    public function features()
    {
        $items = Feature::query()
            ->seoIndexable()
            ->withCount([
                'tools' => fn ($q) => $q->where('status', 'published'),
                'models' => fn ($q) => $q->whereIn('status', ['active', 'preview']),
            ])
            ->orderBy('group')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return view('frontend.taxonomy.index', ['kind' => 'features', 'items' => $items]);
    }

    public function feature(Feature $feature)
    {
        abort_unless($feature->is_active && $feature->is_indexable, 404);

        $seoEligible = Feature::query()
            ->whereKey($feature->getKey())
            ->seoIndexable()
            ->exists();

        $tools = $feature->tools()
            ->with(['company', 'category'])
            ->where('status', 'published')
            ->orderByDesc('rating')
            ->orderByDesc('popularity')
            ->paginate(12);

        $models = $feature->models()
            ->with(['company', 'tool'])
            ->whereIn('status', ['active', 'preview'])
            ->orderByDesc('benchmark_score')
            ->take(12)
            ->get();

        return view('frontend.taxonomy.show', [
            'kind' => 'feature',
            'term' => $feature,
            'tools' => $tools,
            'models' => $models,
            'seoEligible' => $seoEligible,
        ]);
    }

    public function useCases()
    {
        $items = UseCase::query()
            ->seoIndexable()
            ->withCount([
                'tools' => fn ($q) => $q->where('status', 'published'),
                'models' => fn ($q) => $q->whereIn('status', ['active', 'preview']),
            ])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return view('frontend.taxonomy.index', ['kind' => 'use-cases', 'items' => $items]);
    }

    public function useCase(UseCase $useCase)
    {
        abort_unless($useCase->is_active && $useCase->is_indexable, 404);

        $seoEligible = UseCase::query()
            ->whereKey($useCase->getKey())
            ->seoIndexable()
            ->exists();

        $tools = $useCase->tools()
            ->with(['company', 'category'])
            ->where('status', 'published')
            ->orderByDesc('rating')
            ->orderByDesc('popularity')
            ->paginate(12);

        $models = $useCase->models()
            ->with(['company', 'tool'])
            ->whereIn('status', ['active', 'preview'])
            ->orderByDesc('benchmark_score')
            ->take(12)
            ->get();

        return view('frontend.taxonomy.show', [
            'kind' => 'use-case',
            'term' => $useCase,
            'tools' => $tools,
            'models' => $models,
            'seoEligible' => $seoEligible,
        ]);
    }

    public function topics()
    {
        $items = Category::query()
            ->seoContentIndexable()
            ->withCount([
                'articles' => fn ($q) => $q
                    ->where('status', 'published')
                    ->where('approval_status', 'approved'),
            ])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return view('frontend.topics.index', compact('items'));
    }

    public function topic(Category $category)
    {
        abort_unless(
            $category->type === 'content' && $category->is_active && $category->is_indexable,
            404
        );

        $seoEligible = Category::query()
            ->whereKey($category->getKey())
            ->seoContentIndexable()
            ->exists();

        $articles = Article::query()
            ->with(['author', 'company', 'tagTerms'])
            ->where('category_id', $category->id)
            ->where('status', 'published')
            ->where('approval_status', 'approved')
            ->orderByDesc('published_at')
            ->paginate(15);

        return view('frontend.topics.show', [
            'topic' => $category,
            'articles' => $articles,
            'seoEligible' => $seoEligible,
        ]);
    }
}
