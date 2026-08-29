<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\AiModel;
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
        $query = $modelClass::query()->with('company');
        $rows = $query->whereIn('id', $data['items'])->get()->keyBy('id');
        $items = collect($data['items'])->map(fn ($id) => $rows->get((int) $id))->filter()->values();

        abort_if($items->count() < 2, 404);

        $this->hydrateForDisplay($items, $data['type']);

        $comparison = null;
        $comparisonType = $data['type'];
        $winner = $this->winner($items);
        $title = $items->pluck('name')->join(' vs ');
        $relatedComparisons = collect();
        $isPreview = true;
        $intelligence = $this->safeIntelligence($items, $comparisonType);

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
            'comparison', 'comparisonType', 'items', 'winner', 'title', 'relatedComparisons', 'isPreview', 'intelligence'
        ));
    }

    public function show(Request $request, Comparison $comparison)
    {
        abort_unless($comparison->status === 'published', 404);

        $comparison->increment('views');
        $comparison->refresh();

        $items = $comparison->items();
        abort_if($items->count() < 2, 404);

        $this->hydrateForDisplay($items, $comparison->comparable_type);

        $comparisonType = $comparison->comparable_type;
        $winner = $this->winner($items);
        $title = $comparison->title;
        $isPreview = false;
        $intelligence = $this->safeIntelligence($items, $comparisonType);

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
            'comparison', 'comparisonType', 'items', 'winner', 'title', 'relatedComparisons', 'isPreview', 'intelligence'
        ));
    }

    private function hydrateForDisplay(Collection $items, string $type): void
    {
        foreach ($items as $item) {
            // These are the only relations the Blade template needs directly.
            // Benchmark/pricing relations are queried by the intelligence service
            // and are deliberately not force-loaded here.
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
            // Comparison pages should remain usable even when an optional
            // benchmark/pricing evidence query is temporarily incompatible with
            // a legacy row or schema. The visible page falls back to catalog data.
            report($e);

            return [
                'benchmarkMatrix' => [],
                'benchmarkMeta' => [],
                'wins' => [],
                'pricing' => [],
                'overall' => null,
                'valueWinner' => null,
            ];
        }
    }

    private function winner(Collection $items): mixed
    {
        return $items->sortByDesc(function ($item) {
            $benchmark = (float) ($item->benchmark_score ?? 0);
            $rating = (float) ($item->rating ?? 0) * 10;
            return $benchmark > 0 ? $benchmark : $rating;
        })->first();
    }
}
