<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Company;
use App\Models\Feature;
use App\Models\NewsItem;
use App\Models\PricingPlan;
use App\Models\Tool;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use App\Services\Seo\EntitySeoService;

class ToolController extends Controller
{
    public function index(Request $request)
    {
        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'category' => ['nullable', 'string', 'max:100'],
            'pricing' => ['nullable', 'in:free,paid'],
            'rating' => ['nullable', 'in:4,4.5'],
            'company' => ['nullable', 'string', 'max:100'],
            'platform' => ['nullable', 'in:Web,API,Desktop,Mobile'],
            'feature' => ['nullable', 'string', 'max:100'],
            'sort' => ['nullable', 'in:popular,rating,newest,benchmark,name'],
            'view' => ['nullable', 'in:grid,list'],
        ]);

        $query = Tool::query()
            ->with(['company', 'category', 'subcategoryTerm'])
            ->where('status', 'published');

        $this->applyFilters($query, $validated);
        $this->applySort($query, $validated['sort'] ?? 'popular');

        $tools = $query->paginate(12)->withQueryString();

        $categories = Category::query()
            ->withCount(['tools' => fn (Builder $q) => $q->where('status', 'published')])
            ->having('tools_count', '>', 0)
            ->orderByDesc('tools_count')
            ->orderBy('name')
            ->get();

        $companies = Company::query()
            ->withCount(['tools' => fn (Builder $q) => $q->where('status', 'published')])
            ->whereHas('tools', fn (Builder $q) => $q->where('status', 'published'))
            ->orderByDesc('tools_count')
            ->orderBy('name')
            ->take(20)
            ->get();

        $features = Feature::query()
            ->withCount(['tools' => fn (Builder $q) => $q->where('status', 'published')])
            ->having('tools_count', '>', 0)
            ->orderByDesc('tools_count')
            ->orderBy('name')
            ->take(16)
            ->get();

        $stats = [
            'tools' => Tool::where('status', 'published')->count(),
            'categories' => $categories->count(),
            'free' => Tool::where('status', 'published')->whereJsonContains('pricing_models', 'Free')->count(),
            'topRated' => Tool::where('status', 'published')->where('rating', '>=', 4.5)->count(),
        ];

        $featuredTools = Tool::query()
            ->with(['company', 'category'])
            ->where('status', 'published')
            ->orderByDesc('popularity')
            ->orderByDesc('rating')
            ->take(4)
            ->get();

        return view('frontend.tools.index', compact(
            'tools',
            'categories',
            'companies',
            'features',
            'stats',
            'featuredTools',
        ));
    }

    public function show(Tool $tool, EntitySeoService $seoService)
    {
        abort_unless($tool->status === 'published', 404);

        $tool->load([
            'company',
            'category',
            'subcategoryTerm',
            'featureTerms',
            'tagTerms',
            'models' => fn ($query) => $query
                ->whereIn('status', ['active', 'preview'])
                ->orderByDesc('benchmark_score')
                ->orderByDesc('release_date'),
            'reviews' => fn ($query) => $query
                ->published()
                ->with('user')
                ->latest('moderated_at')
                ->latest('created_at'),
            'benchmarkResults' => fn ($query) => $query
                ->with('benchmark')
                ->where('verified', true)
                ->latest('tested_at'),
        ]);

        $pricingPlans = PricingPlan::query()
            ->where('tool_id', $tool->id)
            ->with(['sources' => fn ($query) => $query->latest('last_checked_at')])
            ->orderBy('monthly_price')
            ->orderBy('id')
            ->get();

        $relatedTools = Tool::query()
            ->with(['company', 'category'])
            ->where('status', 'published')
            ->where('id', '!=', $tool->id)
            ->when($tool->category_id, function (Builder $query) use ($tool) {
                $query->where(function (Builder $related) use ($tool) {
                    $related->where('category_id', $tool->category_id);
                    if ($tool->company_id) {
                        $related->orWhere('company_id', $tool->company_id);
                    }
                });
            })
            ->orderByDesc('rating')
            ->orderByDesc('popularity')
            ->take(4)
            ->get();

        if ($relatedTools->count() < 4) {
            $fallback = Tool::query()
                ->with(['company', 'category'])
                ->where('status', 'published')
                ->where('id', '!=', $tool->id)
                ->whereNotIn('id', $relatedTools->pluck('id'))
                ->orderByDesc('rating')
                ->orderByDesc('popularity')
                ->take(4 - $relatedTools->count())
                ->get();

            $relatedTools = $relatedTools->concat($fallback);
        }

        $latestNews = NewsItem::query()
            ->with('company')
            ->where('status', 'published')
            ->whereNull('duplicate_of_id')
            ->where(function (Builder $query) use ($tool) {
                if ($tool->company_id) {
                    $query->where('company_id', $tool->company_id);
                } else {
                    $query->whereRaw('1 = 0');
                }

                $query->orWhere('headline', 'like', '%' . $tool->name . '%')
                    ->orWhere('summary', 'like', '%' . $tool->name . '%');
            })
            ->latest('published_at')
            ->take(4)
            ->get();

        $editorReview = $tool->reviews
            ->first(fn ($review) => $review->review_type === 'editor')
            ?? $tool->reviews->first();

        $ratingBreakdown = collect($tool->rating_breakdown ?? []);
        $capabilities = collect($tool->capabilities ?? [])->filter()->values();
        $platforms = collect($tool->platforms ?? [])->filter()->values();
        $tags = $tool->tagTerms->pluck('name')
            ->merge(collect($tool->tags ?? []))
            ->filter()
            ->unique()
            ->values();

        $seo = $seoService->tool($tool);
        $seoSchemas = $seoService->schemas('tool', $tool, $seo);

        return view('frontend.tools.show', compact(
            'tool',
            'pricingPlans',
            'relatedTools',
            'latestNews',
            'editorReview',
            'ratingBreakdown',
            'capabilities',
            'platforms',
            'tags',
            'seo',
            'seoSchemas',
        ));
    }

    private function applyFilters(Builder $query, array $filters): void
    {
        if (!empty($filters['q'])) {
            $search = trim($filters['q']);
            $query->where(function (Builder $q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('short_description', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhereHas('company', fn (Builder $company) => $company->where('name', 'like', "%{$search}%"))
                    ->orWhereHas('category', fn (Builder $category) => $category->where('name', 'like', "%{$search}%"));
            });
        }

        if (!empty($filters['category'])) {
            $query->whereHas('category', fn (Builder $q) => $q->where('slug', $filters['category']));
        }

        if (($filters['pricing'] ?? null) === 'free') {
            $query->whereJsonContains('pricing_models', 'Free');
        }

        if (($filters['pricing'] ?? null) === 'paid') {
            $query->whereJsonContains('pricing_models', 'Paid');
        }

        if (!empty($filters['rating'])) {
            $query->where('rating', '>=', (float) $filters['rating']);
        }

        if (!empty($filters['company'])) {
            $query->whereHas('company', fn (Builder $q) => $q->where('slug', $filters['company']));
        }

        if (!empty($filters['platform'])) {
            $query->whereJsonContains('platforms', $filters['platform']);
        }

        if (!empty($filters['feature'])) {
            $query->whereHas('featureTerms', fn (Builder $q) => $q->where('slug', $filters['feature']));
        }
    }

    private function applySort(Builder $query, string $sort): void
    {
        match ($sort) {
            'rating' => $query->orderByDesc('rating')->orderByDesc('popularity'),
            'newest' => $query->orderByDesc('published_at')->orderByDesc('id'),
            'benchmark' => $query->orderByDesc('benchmark_score')->orderByDesc('rating'),
            'name' => $query->orderBy('name'),
            default => $query->orderByDesc('popularity')->orderByDesc('rating'),
        };
    }
}
