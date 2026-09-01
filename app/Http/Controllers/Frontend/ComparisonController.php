<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\AiModel;
use App\Models\Comparison;
use App\Models\Tool;
use App\Services\Frontend\ComparisonHistoryService;
use App\Services\Frontend\QuickFeedbackService;
use App\Services\ComparisonIntelligenceService;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ComparisonController extends Controller
{
    public function __construct(
        private readonly ComparisonHistoryService $userHistory,
        private readonly ComparisonIntelligenceService $intelligence,
        private readonly QuickFeedbackService $feedback,
    ) {
    }

    public function index(Request $request)
    {
        $type = in_array($request->query('type'), ['tool', 'model'], true)
            ? $request->query('type')
            : null;
        $search = trim((string) $request->query('search'));
        $sort = in_array($request->query('sort'), ['popular', 'newest', 'az'], true)
            ? $request->query('sort')
            : 'popular';

        $query = Comparison::query()->where('status', 'published');

        if ($type) {
            $query->where('comparable_type', $type);
        }

        if ($search !== '') {
            $query->where('title', 'like', '%' . $search . '%');
        }

        match ($sort) {
            'newest' => $query->latest(),
            'az' => $query->orderBy('title'),
            default => $query->orderByDesc('views')->latest(),
        };

        $comparisons = $query->paginate(9)->withQueryString();
        $comparisons->getCollection()->each(function (Comparison $comparison) {
            try {
                $comparison->setRelation('resolved_items', $comparison->publicItems());
            } catch (\Throwable $e) {
                report($e);
                $comparison->setRelation('resolved_items', collect());
            }
        });

        // Do not expose internal links to saved comparisons that cannot resolve
        // at least two current catalog entities; their detail route is a 404.
        $comparisons->setCollection(
            $comparisons->getCollection()
                ->filter(fn (Comparison $comparison) => $comparison->getRelation('resolved_items')->count() >= 2)
                ->values()
        );

        $featured = Comparison::query()
            ->where('status', 'published')
            ->orderByDesc('views')
            ->limit(3)
            ->get();
        $featured->each(function (Comparison $comparison) {
            try {
                $comparison->setRelation('resolved_items', $comparison->publicItems());
            } catch (\Throwable $e) {
                report($e);
                $comparison->setRelation('resolved_items', collect());
            }
        });

        $featured = $featured
            ->filter(fn (Comparison $comparison) => $comparison->getRelation('resolved_items')->count() >= 2)
            ->values();

        $stats = [
            'published' => Comparison::where('status', 'published')->count(),
            'tool' => Comparison::where('status', 'published')->where('comparable_type', 'tool')->count(),
            'model' => Comparison::where('status', 'published')->where('comparable_type', 'model')->count(),
            'views' => (int) Comparison::where('status', 'published')->sum('views'),
        ];

        return view('frontend.comparisons.index', compact(
            'comparisons', 'featured', 'stats', 'type', 'search', 'sort'
        ));
    }

    public function builder(Request $request)
    {
        $type = in_array($request->query('type'), ['tool', 'model'], true) ? $request->query('type') : 'tool';

        $tools = Tool::query()
            ->with('company:id,name')
            ->where('status', 'published')
            ->orderByDesc('rating')
            ->orderBy('name')
            ->get(['id', 'company_id', 'name', 'slug', 'logo_path', 'rating', 'benchmark_score', 'pricing_models', 'short_description']);

        $models = AiModel::query()
            ->with('company:id,name')
            ->whereIn('status', ['active', 'preview'])
            ->orderByDesc('benchmark_score')
            ->orderBy('name')
            ->get(['id', 'company_id', 'name', 'slug', 'logo_path', 'version', 'benchmark_score', 'context_window', 'status']);

        return view('frontend.comparisons.builder', compact('tools', 'models', 'type'));
    }

    public function preview(Request $request)
    {
        $data = $request->validate([
            'type' => ['required', Rule::in(['tool', 'model'])],
            'items' => ['required', 'array', 'min:2', 'max:4'],
            'items.*' => ['required', 'integer', 'distinct'],
        ]);

        $modelClass = $data['type'] === 'tool' ? Tool::class : AiModel::class;
        $rows = $modelClass::query()
            ->with('company')
            ->whereIn('id', $data['items'])
            ->get()
            ->keyBy('id');

        $items = collect($data['items'])
            ->map(fn ($id) => $rows->get((int) $id))
            ->filter()
            ->values();

        abort_if($items->count() < 2, 404);

        $this->hydrateForDisplay($items, $data['type']);

        $comparison = null;
        $comparisonType = $data['type'];
        $title = $items->pluck('name')->join(' vs ');
        $relatedComparisons = collect();
        $isPreview = true;
        $intelligence = $this->safeIntelligence($items, $comparisonType);
        $winner = data_get($intelligence, 'overall');
        $labComparison = ['stats' => collect(), 'shared' => collect(), 'has_data' => false];
        // Preview comparisons do not have a persisted comparison ID to rate.
        $quickRating = null;

        if ($request->user()) {
            $this->userHistory->fromPreview(
                $request->user(),
                $comparisonType,
                $items->pluck('id')->all(),
                $title,
                false
            );
        }

        return view('frontend.comparisons.show', compact(
            'comparison', 'comparisonType', 'items', 'winner', 'title',
            'relatedComparisons', 'isPreview', 'intelligence', 'labComparison', 'quickRating'
        ));
    }

    public function show(Request $request, Comparison $comparison)
    {
        abort_unless($comparison->status === 'published', 404);

        // Views are analytics, not editorial content. Updating the model with
        // Eloquent's increment() can advance updated_at, which would make sitemap
        // lastmod look fresh on every visit. Increment directly instead.
        DB::table($comparison->getTable())
            ->where($comparison->getKeyName(), $comparison->getKey())
            ->increment('views');
        $comparison->views = (int) $comparison->views + 1;

        try {
            $items = $comparison->publicItems();
        } catch (\Throwable $e) {
            report($e);
            $items = collect();
        }

        // A saved comparison is only meaningful when at least two catalog items
        // can be recovered. Comparison::items() includes stale-ID recovery.
        abort_if($items->count() < 2, 404);

        $this->hydrateForDisplay($items, $comparison->comparable_type);

        $comparisonType = $comparison->comparable_type;
        $title = $comparison->title;
        $isPreview = false;
        $intelligence = $this->safeIntelligence($items, $comparisonType);
        $winner = data_get($intelligence, 'overall');
        $labComparison = ['stats' => collect(), 'shared' => collect(), 'has_data' => false];

        // Quick feedback is optional UI. A feedback-table/config problem must
        // never take the whole comparison page down.
        try {
            $quickRating = $this->feedback->ratingSummary('comparison', $comparison->id, $request->user());
        } catch (\Throwable $e) {
            report($e);
            $quickRating = null;
        }

        if ($request->user()) {
            $this->userHistory->fromPublished($request->user(), $comparison, false);
        }

        $relatedComparisons = Comparison::query()
            ->where('status', 'published')
            ->where('comparable_type', $comparisonType)
            ->where('id', '!=', $comparison->id)
            ->orderByDesc('views')
            ->limit(4)
            ->get();

        $relatedComparisons->each(function (Comparison $item) {
            try {
                $item->setRelation('resolved_items', $item->publicItems());
            } catch (\Throwable $e) {
                report($e);
                $item->setRelation('resolved_items', collect());
            }
        });

        $relatedComparisons = $relatedComparisons
            ->filter(fn (Comparison $item) => $item->getRelation('resolved_items')->count() >= 2)
            ->values();

        return view('frontend.comparisons.show', compact(
            'comparison', 'comparisonType', 'items', 'winner', 'title',
            'relatedComparisons', 'isPreview', 'intelligence', 'labComparison', 'quickRating'
        ));
    }

    private function hydrateForDisplay(Collection $items, string $type): void
    {
        foreach ($items as $item) {
            $relations = ['company'];
            if ($type === 'tool') {
                $relations[] = 'category';
            }

            try {
                $item->loadMissing($relations);
            } catch (\Throwable $e) {
                report($e);
            }
        }
    }

    private function safeIntelligence(Collection $items, string $type): array
    {
        try {
            return $this->intelligence->build($items, $type);
        } catch (\Throwable $e) {
            report($e);

            // Keep every key used by the comparison Blade available. This is a
            // presentation-safe fallback only; it never fabricates benchmark data.
            return [
                'benchmarkMatrix' => [],
                'benchmarkMeta' => [],
                'sharedBenchmarkKeys' => [],
                'benchmarkLeaders' => [],
                'wins' => [],
                'weightedWins' => [],
                'verifiedBenchmarkItemIds' => [],
                'verifiedComposite' => [],
                'pricing' => [],
                'overall' => null,
                'overallVerdict' => [
                    'winner_id' => null,
                    'confidence' => 'limited',
                    'confidence_label' => 'Limited evidence',
                    'shared_benchmarks' => 0,
                    'clear_benchmarks' => 0,
                    'reason' => 'Verified comparison evidence is temporarily unavailable. AI Orbit is not declaring an evidence-backed winner.',
                ],
                'metricWinners' => [
                    'benchmark' => null,
                    'capabilities' => null,
                    'price' => null,
                    'rating' => null,
                    'popularity' => null,
                    'context' => null,
                ],
                'valueWinner' => null,
                'valueSignalReason' => null,
                'evidenceAsOf' => null,
            ];
        }
    }
}
