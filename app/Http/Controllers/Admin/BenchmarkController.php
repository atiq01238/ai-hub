<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AiModel;
use App\Models\Tool;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class BenchmarkController extends Controller
{
    // Standardized public benchmarks the rankings page understands. An admin
    // can still type a custom name in the "Create Benchmark" form — this list
    // just seeds the filter chips and pre-fills common ones.
    private array $knownBenchmarks = ['MMLU Pro', 'HumanEval', 'GPQA Diamond', 'MATH', 'SWE-bench'];

    public function index(Request $request)
    {
        // "model" (AI Models) or "tool" (AI Tools) — both are ranked the
        // same way, just against a different table.
        $type = $request->query('type', 'model') === 'tool' ? 'tool' : 'model';

        $items = $this->itemsFor($type);

        $recordedBenchmarks = $items
            ->flatMap(fn ($item) => array_keys($item->benchmarks ?? []))
            ->unique()
            ->values();

        $benchmarks = $recordedBenchmarks->isNotEmpty()
            ? $recordedBenchmarks->all()
            : $this->knownBenchmarks;

        $selected = $request->query('benchmark', $benchmarks[0]);

        $rankings = $items
            ->filter(fn ($item) => isset($item->benchmarks[$selected]))
            ->sortByDesc(fn ($item) => $item->benchmarks[$selected])
            ->values();

        return view('benchmarks.index', compact('benchmarks', 'selected', 'rankings', 'type'));
    }

    public function create()
    {
        $models = AiModel::with('company')->orderBy('name')->get();
        $tools = Tool::with('company')->orderBy('name')->get();

        return view('benchmarks.create', [
            'models'     => $models,
            'tools'      => $tools,
            'benchmarks' => $this->knownBenchmarks,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'type'           => ['required', 'in:model,tool'],
            'item_id'        => ['required', 'integer'],
            'benchmark_name' => ['required', 'string', 'max:100'],
            'score'          => ['required', 'numeric', 'min:0', 'max:100'],
        ]);

        $item = $data['type'] === 'tool'
            ? Tool::findOrFail($data['item_id'])
            : AiModel::findOrFail($data['item_id']);

        // Record/overwrite this one benchmark's score, keep the rest as-is.
        $breakdown = $item->benchmarks ?? [];
        $breakdown[$data['benchmark_name']] = (float) $data['score'];
        $item->benchmarks = $breakdown;

        // Composite score = average of every benchmark recorded for it,
        // so it stays honest as more get added.
        $item->benchmark_score = round(array_sum($breakdown) / count($breakdown), 1);
        $item->save();

        return redirect()
            ->route('admin.benchmarks.index', ['benchmark' => $data['benchmark_name'], 'type' => $data['type']])
            ->with('status', 'Benchmark score saved.');
    }

    private function itemsFor(string $type): Collection
    {
        return $type === 'tool'
            ? Tool::with('company')->whereNotNull('benchmarks')->get()
            : AiModel::with('company')->whereNotNull('benchmarks')->get();
    }
}