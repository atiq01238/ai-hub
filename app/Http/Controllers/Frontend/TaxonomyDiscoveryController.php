<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\AiModel;
use App\Models\Article;
use App\Models\Category;
use App\Models\Feature;
use App\Models\Tool;
use App\Models\UseCase;

class TaxonomyDiscoveryController extends Controller
{
    public function features()
    {
        $items = Feature::active()->where('is_indexable', true)
            ->where(function ($q) {
                $q->whereHas('tools', fn ($tools) => $tools->where('status', 'published'))
                    ->orWhereHas('models', fn ($models) => $models->whereIn('status', ['active', 'preview']));
            })
            ->withCount([
                'tools' => fn ($q) => $q->where('status','published'),
                'models' => fn ($q) => $q->whereIn('status',['active','preview']),
            ])->orderBy('group')->orderBy('sort_order')->orderBy('name')->get();
        return view('frontend.taxonomy.index', ['kind'=>'features','items'=>$items]);
    }

    public function feature(Feature $feature)
    {
        abort_unless($feature->is_active && $feature->is_indexable, 404);
        $tools = $feature->tools()->with(['company','category'])->where('status','published')->orderByDesc('rating')->orderByDesc('popularity')->paginate(12);
        $models = $feature->models()->with(['company','tool'])->whereIn('status',['active','preview'])->orderByDesc('benchmark_score')->take(12)->get();
        return view('frontend.taxonomy.show', ['kind'=>'feature','term'=>$feature,'tools'=>$tools,'models'=>$models]);
    }

    public function useCases()
    {
        $items = UseCase::active()->where('is_indexable', true)
            ->where(function ($q) {
                $q->whereHas('tools', fn ($tools) => $tools->where('status', 'published'))
                    ->orWhereHas('models', fn ($models) => $models->whereIn('status', ['active', 'preview']));
            })
            ->withCount([
                'tools' => fn ($q) => $q->where('status','published'),
                'models' => fn ($q) => $q->whereIn('status',['active','preview']),
            ])->orderBy('sort_order')->orderBy('name')->get();
        return view('frontend.taxonomy.index', ['kind'=>'use-cases','items'=>$items]);
    }

    public function useCase(UseCase $useCase)
    {
        abort_unless($useCase->is_active && $useCase->is_indexable, 404);
        $tools = $useCase->tools()->with(['company','category'])->where('status','published')->orderByDesc('rating')->orderByDesc('popularity')->paginate(12);
        $models = $useCase->models()->with(['company','tool'])->whereIn('status',['active','preview'])->orderByDesc('benchmark_score')->take(12)->get();
        return view('frontend.taxonomy.show', ['kind'=>'use-case','term'=>$useCase,'tools'=>$tools,'models'=>$models]);
    }

    public function topics()
    {
        $items = Category::content()->active()->where('is_indexable', true)
            ->withCount(['articles'=>fn($q)=>$q->where('status','published')->where('approval_status','approved')])
            ->orderBy('sort_order')->orderBy('name')->get();
        return view('frontend.topics.index', compact('items'));
    }

    public function topic(Category $category)
    {
        abort_unless($category->type === 'content' && $category->is_active && $category->is_indexable, 404);
        $articles = Article::query()->with(['author','company','tagTerms'])
            ->where('category_id',$category->id)->where('status','published')->where('approval_status','approved')
            ->orderByDesc('published_at')->paginate(15);
        return view('frontend.topics.show', ['topic'=>$category,'articles'=>$articles]);
    }
}
