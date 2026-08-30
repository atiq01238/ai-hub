<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\BenchmarkResult;
use App\Models\Category;
use App\Models\Company;
use App\Models\Feature;
use App\Models\NewsItem;
use App\Models\PricingPlan;
use App\Models\Tool;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use App\Services\Seo\EntitySeoService;
use App\Services\Frontend\QuickFeedbackService;
use App\Services\Analytics\ToolTrendingService;

class ToolController extends Controller
{
    public function index(Request $request, ToolTrendingService $toolTrendingService)
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
            ->with([
                'company',
                'category',
                'subcategoryTerm',
                'featureTerms:id,name,slug',
                'useCaseTerms:id,name,slug',
                'benchmarkResults' => fn ($query) => $query
                    ->with('benchmark:id,name,slug,higher_is_better')
                    ->where('verified', true)
                    ->where('status', 'verified')
                    ->latest('tested_at')
                    ->latest('id'),
            ])
            ->where('status', 'published');

        $this->applyFilters($query, $validated);

        // Use the same first-party 30-day activity intelligence that powers
        // the site's Trending AI experience. This keeps the directory's
        // "Most Popular" ordering and card badges aligned instead of
        // displaying the legacy static popularity percentage.
        $publishedToolCount = Tool::where('status', 'published')->count();
        $trendingTools = $toolTrendingService->homepage(max(1, $publishedToolCount));
        $trendById = $trendingTools->keyBy('id');
        $activeTrendIds = $trendingTools
            ->filter(fn (Tool $tool) => (int) ($tool->trend_current_score ?? 0) > 0)
            ->pluck('id')
            ->values();

        if (($validated['sort'] ?? 'popular') === 'popular' && $activeTrendIds->isNotEmpty()) {
            // Rank tools with measured activity first. Any tools without enough
            // first-party activity fall back to the existing popularity/rating
            // order, so pagination remains deterministic.
            $cases = [];
            $bindings = [];
            foreach ($activeTrendIds as $rank => $toolId) {
                $cases[] = 'WHEN ? THEN ?';
                $bindings[] = (int) $toolId;
                $bindings[] = (int) $rank;
            }

            $query->orderByRaw(
                'CASE tools.id '.implode(' ', $cases).' ELSE ? END ASC',
                [...$bindings, $activeTrendIds->count()]
            )->orderByDesc('popularity')->orderByDesc('rating');
        } else {
            $this->applySort($query, $validated['sort'] ?? 'popular');
        }

        $tools = $query->paginate(12)->withQueryString();

        $tools->setCollection($tools->getCollection()->map(function (Tool $tool) use ($trendById) {
            $trend = $trendById->get($tool->id);

            $tool->trend_label = $trend?->trend_label ?? '—';
            $tool->trend_change = $trend?->trend_change;
            $tool->trend_current_score = (int) ($trend?->trend_current_score ?? 0);
            $tool->trend_previous_score = (int) ($trend?->trend_previous_score ?? 0);
            $tool->trend_details = $trend?->trend_details
                ?? 'Not enough first-party activity in the last 30 days yet.';

            return $tool;
        }));

        $categories = Category::query()
            ->product()->active()
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
            ->active()
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

        $featuredTools = $trendingTools->take(4);
        $featuredTools->loadMissing(['company', 'category']);

        return view('frontend.tools.index', compact(
            'tools',
            'categories',
            'companies',
            'features',
            'stats',
            'featuredTools',
        ));
    }

    public function show(Tool $tool, EntitySeoService $seoService, QuickFeedbackService $feedback)
    {
        abort_unless($tool->status === 'published', 404);

        $tool->load([
            'company',
            'category',
            'subcategoryTerm',
            'featureTerms',
            'useCaseTerms',
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
                ->where('status', 'verified')
                ->latest('tested_at')
                ->latest('id'),
        ]);

        $benchmarkResults = $tool->benchmarkResults
            ->filter(fn ($result) => $result->benchmark)
            ->unique('benchmark_id')
            ->values();

        $benchmarkContexts = collect();

        if ($benchmarkResults->isNotEmpty()) {
            $peerResults = BenchmarkResult::query()
                ->with(['benchmark', 'benchmarkable'])
                ->whereIn('benchmark_id', $benchmarkResults->pluck('benchmark_id')->unique())
                ->where('benchmarkable_type', Tool::class)
                ->where('verified', true)
                ->where('status', 'verified')
                ->orderByDesc('tested_at')
                ->orderByDesc('id')
                ->get()
                ->filter(fn ($result) => $result->benchmarkable instanceof Tool && $result->benchmarkable->status === 'published')
                ->groupBy('benchmark_id');

            foreach ($benchmarkResults as $result) {
                $benchmark = $result->benchmark;
                $latestPeers = $peerResults
                    ->get($result->benchmark_id, collect())
                    ->unique('benchmarkable_id')
                    ->values();

                $ranked = $benchmark->higher_is_better
                    ? $latestPeers->sortByDesc('score')->values()
                    : $latestPeers->sortBy('score')->values();

                $rankIndex = $ranked->search(fn ($peer) => (int) $peer->benchmarkable_id === (int) $tool->id);
                $leader = $ranked->first();
                $isLeader = $leader && (int) $leader->benchmarkable_id === (int) $tool->id;
                $gap = null;

                if ($leader && ! $isLeader) {
                    $gap = $benchmark->higher_is_better
                        ? max(0, (float) $leader->score - (float) $result->score)
                        : max(0, (float) $result->score - (float) $leader->score);
                }

                $benchmarkContexts->put($result->id, [
                    'rank' => $rankIndex === false ? null : $rankIndex + 1,
                    'total' => $ranked->count(),
                    'leader_name' => $leader?->benchmarkable?->name,
                    'leader_score' => $leader ? (float) $leader->score : null,
                    'is_leader' => (bool) $isLeader,
                    'gap' => $gap,
                ]);
            }
        }

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

        $editorialReviews = $tool->reviews
            ->where('review_type', 'editorial')
            ->values();
        $editorReview = $editorialReviews->first();

        $ratingBreakdown = collect($tool->rating_breakdown ?? []);
        $capabilities = collect($tool->capabilities ?? [])->filter()->values();
        $platforms = collect($tool->platforms ?? [])->filter()->values();
        $tags = $tool->tagTerms->pluck('name')
            ->merge(collect($tool->tags ?? []))
            ->filter()
            ->unique()
            ->values();

        $quickRating = $feedback->ratingSummary('tool', $tool->id, auth()->user());

        $seo = $seoService->tool($tool);
        $seoSchemas = $seoService->schemas('tool', $tool, $seo);

        return view('frontend.tools.show', compact(
            'tool',
            'pricingPlans',
            'relatedTools',
            'latestNews',
            'editorReview',
            'editorialReviews',
            'benchmarkResults',
            'benchmarkContexts',
            'ratingBreakdown',
            'capabilities',
            'platforms',
            'tags',
            'quickRating',
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
