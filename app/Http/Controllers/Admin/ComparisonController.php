<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AiModel;
use App\Models\Comparison;
use App\Models\Tool;
use App\Services\ComparisonIntelligenceService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ComparisonController extends Controller
{
    public function __construct(private readonly ComparisonIntelligenceService $intelligence)
    {
    }

    public function index(Request $request)
    {
        $query = Comparison::latest();
        if ($search = trim((string) $request->query('search'))) $query->where('title', 'like', "%{$search}%");
        if (in_array($request->query('type'), ['tool','model'], true)) $query->where('comparable_type', $request->query('type'));
        if (in_array($request->query('status'), ['draft','published'], true)) $query->where('status', $request->query('status'));
        $comparisons = $query->paginate(20)->withQueryString();
        return view('comparisons.index', compact('comparisons'));
    }

    public function builder()
    {
        return view('comparisons.builder', ['tools'=>Tool::orderBy('name')->get(), 'models'=>AiModel::orderBy('name')->get()]);
    }

    public function store(Request $request)
    {
        Comparison::create($this->fromRequest($request));
        return redirect()->route('admin.comparisons.index')->with('status', 'Comparison created.');
    }

    public function show(int $id)
    {
        $comparison = Comparison::findOrFail($id);
        $comparison->increment('views');

        // Keep the admin detail page intentionally lightweight and resilient.
        // Public comparison pages can run the richer intelligence pipeline, but
        // an admin CRUD redirect must never fail because benchmark/pricing
        // evidence is incomplete or a legacy relation is unavailable.
        $items = $comparison->items();

        $winner = $items
            ->filter(fn ($item) => $item->benchmark_score !== null && (float) $item->benchmark_score > 0)
            ->sortByDesc(fn ($item) => (float) $item->benchmark_score)
            ->first();

        return view('comparisons.show', compact('comparison', 'items', 'winner'));
    }

    public function edit(int $id)
    {
        return view('comparisons.builder', [
            'comparison'=>Comparison::findOrFail($id),
            'tools'=>Tool::orderBy('name')->get(),
            'models'=>AiModel::orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, int $id)
    {
        $comparison = Comparison::findOrFail($id);
        $comparison->update($this->fromRequest($request, $comparison));
        return redirect()->route('admin.comparisons.show', $comparison->id)->with('status', 'Comparison updated.');
    }

    public function destroy(int $id)
    {
        Comparison::findOrFail($id)->delete();
        return redirect()->route('admin.comparisons.index')->with('status', 'Comparison deleted.');
    }

    public function metrics()
    {
        $total = Comparison::count();
        $published = Comparison::where('status','published')->count();
        $drafts = Comparison::where('status','draft')->count();
        $toolComparisons = Comparison::where('comparable_type','tool')->count();
        $modelComparisons = Comparison::where('comparable_type','model')->count();
        $totalViews = (int) Comparison::sum('views');
        $avgViews = $total ? round($totalViews / $total, 1) : 0;
        $topComparisons = Comparison::orderByDesc('views')->limit(8)->get();
        $recentComparisons = Comparison::latest()->limit(8)->get();
        return view('comparisons.metrics', compact('total','published','drafts','toolComparisons','modelComparisons','totalViews','avgViews','topComparisons','recentComparisons'));
    }

    private function fromRequest(Request $request, ?Comparison $comparison = null): array
    {
        $data = $request->validate([
            'title'=>['required','string','max:255'],
            'status'=>['required', Rule::in(['draft','published'])],
            'tool_ids'=>['nullable','array','max:4'], 'tool_ids.*'=>['integer','distinct','exists:tools,id'],
            'model_ids'=>['nullable','array','max:4'], 'model_ids.*'=>['integer','distinct','exists:ai_models,id'],
        ]);
        $toolIds = array_values(array_filter($data['tool_ids'] ?? []));
        $modelIds = array_values(array_filter($data['model_ids'] ?? []));
        if (!$toolIds && !$modelIds) throw ValidationException::withMessages(['tool_ids'=>'Select 2–4 tools OR 2–4 models to compare.']);
        if ($toolIds && $modelIds) throw ValidationException::withMessages(['tool_ids'=>'Choose either Tools or Models, not both.']);
        $chosen = $toolIds ?: $modelIds;
        if (count($chosen) < 2 || count($chosen) > 4) throw ValidationException::withMessages(['tool_ids'=>'Select between 2 and 4 unique items.']);

        $type = $toolIds ? 'tool' : 'model';
        $chosen = array_map('intval', $chosen);
        $duplicate = $this->semanticDuplicate($type, $chosen, $comparison?->id);
        if ($duplicate) {
            $field = $toolIds ? 'tool_ids' : 'model_ids';
            throw ValidationException::withMessages([
                $field => 'This same comparison set already exists as “' . $duplicate->title . '”. Reverse item order does not create a separate comparison.',
            ]);
        }

        $payload = [
            'title'=>$data['title'], 'comparable_type'=>$type,
            'item_ids'=>$chosen, 'status'=>$data['status'],
        ];
        // Preserve URLs on edit. A slug is generated only once when the comparison is created.
        if (!$comparison) $payload['slug'] = $this->uniqueSlug($data['title']);
        return $payload;
    }

    private function semanticDuplicate(string $type, array $itemIds, ?int $ignoreId = null): ?Comparison
    {
        $signature = $this->itemSignature($itemIds);

        return Comparison::query()
            ->where('comparable_type', $type)
            ->when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))
            ->get(['id', 'title', 'item_ids'])
            ->first(fn (Comparison $candidate) => $this->itemSignature($candidate->item_ids ?? []) === $signature);
    }

    private function itemSignature(array $itemIds): string
    {
        $ids = array_values(array_unique(array_map('intval', $itemIds)));
        sort($ids, SORT_NUMERIC);

        return implode(':', $ids);
    }

    private function uniqueSlug(string $title): string
    {
        $base = Str::slug($title) ?: 'comparison';
        $slug = $base; $i = 2;
        while (Comparison::where('slug',$slug)->exists()) $slug = $base.'-'.$i++;
        return $slug;
    }
}
