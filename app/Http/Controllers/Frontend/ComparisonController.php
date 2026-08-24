<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\AiModel;
use App\Models\AiTestResult;
use App\Models\Comparison;
use App\Models\Tool;
use App\Services\Frontend\ComparisonHistoryService;
use App\Services\ComparisonIntelligenceService;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;

class ComparisonController extends Controller
{
    public function __construct(private readonly ComparisonHistoryService $userHistory, private readonly ComparisonIntelligenceService $intelligence)
    {
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
        $comparisons->getCollection()->each(fn (Comparison $comparison) => $comparison->setRelation('resolved_items', $comparison->items()));

        $featured = Comparison::query()
            ->where('status', 'published')
            ->orderByDesc('views')
            ->limit(3)
            ->get();
        $featured->each(fn (Comparison $comparison) => $comparison->setRelation('resolved_items', $comparison->items()));

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
            ->get(['id', 'company_id', 'tool_id', 'name', 'slug', 'logo_path', 'version', 'benchmark_score', 'context_window', 'input_price_per_million', 'output_price_per_million', 'capabilities', 'capability_notes', 'status']);

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
        $query = $modelClass::query()->with('company');
        $rows = $query->whereIn('id', $data['items'])->get()->keyBy('id');
        $items = collect($data['items'])->map(fn ($id) => $rows->get((int) $id))->filter()->values();

        abort_if($items->count() < 2, 404);

        $items->each(function ($item) use ($data) {
            $item->loadMissing(['company','benchmarkResults.benchmark']);
            if ($data['type'] === 'model') {
                $item->loadMissing('tool');
            }
            if ($data['type'] === 'tool') {
                $item->loadMissing(['category', 'featureTerms', 'pricingPlans']);
            }
        });

        $comparison = null;
        $comparisonType = $data['type'];
        $winner = $this->winner($items);
        $title = $items->pluck('name')->join(' vs ');
        $relatedComparisons = collect();
        $isPreview = true;
        $intelligence = $this->intelligence->build($items, $comparisonType);
        $labComparison = ['stats' => collect(), 'shared' => collect(), 'has_data' => false];

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
            'comparison', 'comparisonType', 'items', 'winner', 'title', 'relatedComparisons', 'isPreview', 'intelligence', 'labComparison'
        ));
    }

    public function show(Request $request, Comparison $comparison)
    {
        abort_unless($comparison->status === 'published', 404);

        $comparison->increment('views');
        $comparison->refresh();

        $items = $comparison->items();
        abort_if($items->count() < 2, 404);

        $items->each(function ($item) use ($comparison) {
            $item->loadMissing(['company','benchmarkResults.benchmark']);
            if ($comparison->comparable_type === 'model') {
                $item->loadMissing('tool');
            }
            if ($comparison->comparable_type === 'tool') {
                $item->loadMissing(['category', 'featureTerms', 'pricingPlans']);
            }
        });

        $comparisonType = $comparison->comparable_type;
        $winner = $this->winner($items);
        $title = $comparison->title;
        $isPreview = false;
        $intelligence = $this->intelligence->build($items, $comparisonType);
        $labComparison = ['stats' => collect(), 'shared' => collect(), 'has_data' => false];

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
        $relatedComparisons->each(fn (Comparison $item) => $item->setRelation('resolved_items', $item->items()));

        return view('frontend.comparisons.show', compact(
            'comparison', 'comparisonType', 'items', 'winner', 'title', 'relatedComparisons', 'isPreview', 'intelligence', 'labComparison'
        ));
    }


    private function testLabComparison(Collection $items, string $comparisonType): array
    {
        if ($comparisonType !== 'model' || $items->isEmpty()) {
            return ['stats' => collect(), 'shared' => collect(), 'has_data' => false];
        }

        $results = AiTestResult::query()
            ->with('test')
            ->whereIn('ai_model_id', $items->pluck('id'))
            ->complete()
            ->whereHas('test', fn ($q) => $q->published())
            ->get();

        $stats = $items->mapWithKeys(function ($model) use ($results) {
            $rows = $results->where('ai_model_id', $model->id);
            return [$model->id => [
                'average' => $rows->isNotEmpty() ? round((float) $rows->avg('overall_score'), 1) : null,
                'tests' => $rows->count(),
                'runs' => (int) $rows->sum('run_count'),
                'verified' => $rows->whereIn('verification_level', ['verified', 'high_confidence'])->count(),
            ]];
        });

        $shared = $results->groupBy('ai_test_id')
            ->filter(fn ($rows) => $rows->pluck('ai_model_id')->unique()->count() >= 2)
            ->map(function ($rows) {
                $first = $rows->first();
                return [
                    'test' => $first->test,
                    'scores' => $rows->keyBy('ai_model_id'),
                ];
            })
            ->sortByDesc(fn ($row) => $row['test']?->published_at?->timestamp ?? 0)
            ->take(8)
            ->values();

        return ['stats' => $stats, 'shared' => $shared, 'has_data' => $stats->contains(fn ($row) => $row['tests'] > 0)];
    }

    private function winner(Collection $items): mixed
    {
        $eligible = $items->filter(function ($item) {
            if ($item->benchmark_score !== null && (float) $item->benchmark_score > 0) {
                return true;
            }

            return isset($item->rating) && $item->rating !== null && (float) $item->rating > 0;
        });

        return $eligible->sortByDesc(function ($item) {
            $benchmark = $item->benchmark_score !== null ? (float) $item->benchmark_score : 0.0;
            $rating = isset($item->rating) && $item->rating !== null ? (float) $item->rating * 10 : 0.0;

            return $benchmark > 0 ? $benchmark : $rating;
        })->first();
    }
}
