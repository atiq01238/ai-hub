<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Benchmark;
use App\Models\BenchmarkResult;
use App\Models\Category;
use App\Models\Company;
use App\Models\Feature;
use App\Models\NewsItem;
use App\Models\Platform;
use App\Models\PricingPlan;
use App\Models\Tool;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use App\Services\Seo\EntitySeoService;
use App\Services\Seo\InternalLinkingService;
use App\Services\Frontend\QuickFeedbackService;
use App\Services\Tools\ToolCommercialProfileService;
use App\Services\Tools\ToolAlternativeScoringService;
use App\Services\Tools\ToolDataConfidenceService;
use App\Services\BenchmarkScoringService;

class ToolController extends Controller
{
    public function __construct(private readonly ToolCommercialProfileService $commercialProfile) {}

    public function index(Request $request)
    {
        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'category' => ['nullable', 'string', 'max:100'],
            'pricing' => ['nullable', 'in:free,paid'],
            'rating' => ['nullable', 'in:4,4.5'],
            'company' => ['nullable', 'string', 'max:100'],
            'platform' => ['nullable', 'string', 'max:100'],
            'feature' => ['nullable', 'string', 'max:100'],
            'verified_tech' => ['nullable', 'in:api,open-source,self-hosted'],
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
                'platformTerms:id,name,slug,sort_order',
                'benchmarkResults' => fn ($query) => $query
                    ->with('benchmark:id,name,slug,higher_is_better')
                    ->where('verified', true)
                    ->where('status', 'verified')
                    ->latest('tested_at')
                    ->latest('id'),
            ])
            ->where('status', 'published');

        $this->applyFilters($query, $validated);
        $this->applySort($query, $validated['sort'] ?? 'popular');

        $tools = $query->paginate(12)->withQueryString();

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

        $freeQuery = Tool::query()->where('status', 'published');
        $this->commercialProfile->applyFilter($freeQuery, 'free');

        $stats = [
            'tools' => Tool::where('status', 'published')->count(),
            'categories' => $categories->count(),
            'free' => $freeQuery->count(),
            'topRated' => Tool::where('status', 'published')->where('rating', '>=', 4.5)->count(),
        ];

        $platformFilters = Platform::query()
            ->active()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id','name','slug']);

        $featuredTools = Tool::query()
            ->with(['company', 'category'])
            ->where('status', 'published')
            ->orderByDesc('popularity')
            ->orderByDesc('rating')
            ->take(4)
            ->get();

        $categoryHubs = $categories
            ->filter(fn ($category) => (bool) $category->is_indexable)
            ->take(10)
            ->values();

        $featureHubs = $features
            ->filter(fn ($feature) => (bool) $feature->is_indexable)
            ->take(8)
            ->values();

        return view('frontend.tools.index', compact(
            'tools',
            'categories',
            'companies',
            'features',
            'stats',
            'featuredTools',
            'platformFilters',
            'categoryHubs',
            'featureHubs',
        ));
    }

    public function show(Tool $tool, EntitySeoService $seoService, QuickFeedbackService $feedback, BenchmarkScoringService $benchmarkScoring, ToolAlternativeScoringService $alternatives, ToolDataConfidenceService $confidence, InternalLinkingService $internalLinks)
    {
        abort_unless($tool->status === 'published', 404);

        $tool->load([
            'company',
            'category',
            'subcategoryTerm',
            'featureTerms',
            'useCaseTerms',
            'tagTerms',
            'platformTerms' => fn ($query) => $query->where('is_active', true)->orderBy('sort_order'),
            'integrationTerms' => fn ($query) => $query->where('is_active', true)->orderBy('name'),
            'technicalProfile', 'factEvidence',
            'sources' => fn ($query) => $query->where('enabled', true)->orderByDesc('is_primary')->latest('verified_at')->latest('id'),
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

        if ($tool->company) {
            $tool->company->loadCount([
                'tools as published_tools_count' => fn ($query) => $query->where('status', 'published'),
                'models as active_models_count' => fn ($query) => $query->whereIn('status', ['active', 'preview']),
            ]);
        }

        $benchmarkResults = $tool->benchmarkResults
            ->filter(fn ($result) => $result->benchmark)
            ->unique('benchmark_id')
            ->values();

        $benchmarkGroups = $benchmarkResults
            ->groupBy(fn ($result) => $result->benchmark->benchmark_class ?: Benchmark::CLASS_UNCLASSIFIED);
        $benchmarkClassComposites = $benchmarkScoring->classComposites($tool);
        $benchmarkPrimaryClass = $benchmarkScoring->primaryCompositeClass($tool);

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

        $tool->setRelation('pricingPlans', $pricingPlans);
        $priceLabel = $this->commercialProfile->summaryLabel($tool, $pricingPlans);

        $relatedTools = $alternatives->alternatives($tool, 4);
        $relatedComparisons = $internalLinks->comparisonsForTool($tool, 4);

        $latestNews = NewsItem::query()
            ->with('company')
            ->where('status', 'published')
            ->whereNull('duplicate_of_id')
            ->where(fn (Builder $query) => $query->whereNull('duplicate_status')->orWhere('duplicate_status', '!=', 'duplicate'))
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
        $platforms = $tool->platformTerms->pluck('name')->filter()->values();
        if ($platforms->isEmpty()) {
            $platforms = collect($tool->platforms ?? [])->filter()->values();
        }
        $primarySource = $tool->sources->firstWhere('is_primary', true) ?: $tool->sources->first();
        $sourceMap = $tool->sources->keyBy('id');
        $productStatusSource = $tool->product_status_source_id ? $sourceMap->get($tool->product_status_source_id) : null;
        $technicalProfile = $tool->technicalProfile;
        $integrations = $tool->integrationTerms->values();
        $factEvidenceMap = $tool->factEvidence->keyBy(fn ($evidence) => $evidence->fact_type.'.'.$evidence->fact_key);
        $dataConfidence = $confidence->score($tool);
        $verifiedIdentitySource = $tool->sources
            ->first(fn ($source) => in_array($source->source_type, ['official_product','company'], true) && $source->verification_status === 'verified');
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
            'relatedComparisons',
            'latestNews',
            'editorReview',
            'editorialReviews',
            'benchmarkResults',
            'benchmarkGroups',
            'benchmarkClassComposites',
            'benchmarkPrimaryClass',
            'benchmarkContexts',
            'ratingBreakdown',
            'capabilities',
            'platforms',
            'tags',
            'priceLabel',
            'primarySource',
            'sourceMap',
            'productStatusSource',
            'verifiedIdentitySource',
            'technicalProfile',
            'integrations',
            'factEvidenceMap',
            'dataConfidence',
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

        if (in_array(($filters['pricing'] ?? null), ['free', 'paid'], true)) {
            $this->commercialProfile->applyFilter($query, $filters['pricing']);
        }

        if (!empty($filters['rating'])) {
            $query->where('rating', '>=', (float) $filters['rating']);
        }

        if (!empty($filters['company'])) {
            $query->whereHas('company', fn (Builder $q) => $q->where('slug', $filters['company']));
        }

        if (!empty($filters['platform'])) {
            $platform = Platform::query()
                ->active()
                ->where(function (Builder $platformQuery) use ($filters) {
                    $platformQuery->where('slug', $filters['platform'])
                        ->orWhere('name', $filters['platform']);
                })
                ->first();

            if ($platform) {
                $query->where(function (Builder $platformMatch) use ($platform) {
                    $platformMatch->whereHas('platformTerms', fn (Builder $terms) => $terms->where('platforms.id', $platform->id))
                        ->orWhere(function (Builder $legacy) use ($platform) {
                            $legacy->whereDoesntHave('platformTerms')
                                ->whereJsonContains('platforms', $platform->name);
                        });
                });
            } else {
                $query->whereRaw('1 = 0');
            }
        }

        if (!empty($filters['feature'])) {
            $query->whereHas('featureTerms', fn (Builder $q) => $q->where('slug', $filters['feature']));
        }

        if (!empty($filters['verified_tech'])) {
            $factMap = [
                'api' => ['api_status', ['available', 'limited']],
                'open-source' => ['open_source_status', ['open_source', 'source_available', 'mixed']],
                'self-hosted' => ['self_hosting_status', ['supported', 'enterprise_only']],
            ];

            [$factKey, $allowedStatuses] = $factMap[$filters['verified_tech']];

            $query->whereHas('technicalProfile', function (Builder $profile) use ($factKey, $allowedStatuses) {
                $profile->whereIn($factKey, $allowedStatuses);
            })->whereHas('factEvidence', function (Builder $evidence) use ($factKey) {
                $evidence->where('fact_type', 'technical')
                    ->where('fact_key', $factKey)
                    ->where('verification_status', 'verified');
            });
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
