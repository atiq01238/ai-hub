<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AiModel;
use App\Models\Benchmark;
use App\Models\BenchmarkResult;
use App\Models\Tool;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class BenchmarkController extends Controller
{
    private array $defaults = [
        ['name'=>'MMLU Pro','category'=>'Knowledge & Reasoning','weight'=>1.20],
        ['name'=>'HumanEval','category'=>'Coding','weight'=>1.15],
        ['name'=>'GPQA Diamond','category'=>'Reasoning','weight'=>1.20],
        ['name'=>'MATH','category'=>'Mathematics','weight'=>1.00],
        ['name'=>'SWE-bench','category'=>'Software Engineering','weight'=>1.25],
    ];

    public function index(Request $request)
    {
        $this->ensureDefaults();
        $type = $request->query('type', 'model') === 'tool' ? 'tool' : 'model';
        $items = $this->itemsFor($type);
        $recordedBenchmarks = $items->flatMap(fn ($item) => array_keys($item->benchmarks ?? []))->unique()->values();
        $benchmarks = $recordedBenchmarks->isNotEmpty()
            ? $recordedBenchmarks->all()
            : Benchmark::where('is_active',true)->orderBy('name')->pluck('name')->all();
        $selected = $request->query('benchmark', $benchmarks[0] ?? 'MMLU Pro');
        $rankings = $items->filter(fn ($item) => isset($item->benchmarks[$selected]))
            ->sortByDesc(fn ($item) => (float) $item->benchmarks[$selected])->values();
        return view('benchmarks.index', compact('benchmarks','selected','rankings','type'));
    }

    public function create()
    {
        $this->ensureDefaults();
        return view('benchmarks.create', [
            'models'=>AiModel::with('company')->orderBy('name')->get(),
            'tools'=>Tool::with('company')->orderBy('name')->get(),
            'benchmarks'=>Benchmark::where('is_active',true)->orderBy('name')->pluck('name')->all(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'type'=>['required','in:model,tool'], 'item_id'=>['required','integer'],
            'benchmark_name'=>['required','string','max:100'], 'score'=>['required','numeric','min:0','max:100'],
            'tested_at'=>['nullable','date'], 'source_name'=>['nullable','string','max:150'],
            'source_url'=>['nullable','url','max:500'], 'notes'=>['nullable','string','max:2000'],
            'verified'=>['nullable','boolean'],
        ]);
        $item = $data['type'] === 'tool' ? Tool::findOrFail($data['item_id']) : AiModel::findOrFail($data['item_id']);
        $benchmark = Benchmark::firstOrCreate(
            ['name'=>$data['benchmark_name']],
            ['slug'=>$this->uniqueBenchmarkSlug($data['benchmark_name']),'category'=>'Custom','weight'=>1,'max_score'=>100,'higher_is_better'=>true,'is_active'=>true]
        );

        BenchmarkResult::create([
            'benchmark_id'=>$benchmark->id,
            'benchmarkable_type'=>$item::class,
            'benchmarkable_id'=>$item->id,
            'score'=>$data['score'],
            'tested_at'=>$data['tested_at'] ?? now()->toDateString(),
            'source_name'=>$data['source_name'] ?? null,
            'source_url'=>$data['source_url'] ?? null,
            'notes'=>$data['notes'] ?? null,
            'verified'=>$request->boolean('verified'),
        ]);

        // Keep legacy JSON in sync so existing model/tool pages continue to work.
        $breakdown = $item->benchmarks ?? [];
        $breakdown[$benchmark->name] = (float) $data['score'];
        $item->benchmarks = $breakdown;
        $item->benchmark_score = $this->weightedComposite($item);
        $item->save();

        return redirect()->route('admin.benchmarks.index', ['benchmark'=>$benchmark->name,'type'=>$data['type']])
            ->with('status','Benchmark result saved with history.');
    }

    public function results(Request $request)
    {
        $query = BenchmarkResult::with(['benchmark','benchmarkable'])->latest('tested_at')->latest();
        if ($request->filled('benchmark')) $query->where('benchmark_id',$request->integer('benchmark'));
        if ($request->query('verified') === '1') $query->where('verified',true);
        if ($request->query('verified') === '0') $query->where('verified',false);
        if ($request->query('type') === 'model') $query->where('benchmarkable_type',AiModel::class);
        if ($request->query('type') === 'tool') $query->where('benchmarkable_type',Tool::class);
        $results = $query->paginate(25)->withQueryString();
        $benchmarks = Benchmark::orderBy('name')->get();
        return view('benchmarks.results', compact('results','benchmarks'));
    }

    public function destroyResult(int $resultId)
    {
        $result = BenchmarkResult::findOrFail($resultId);
        $result->delete();
        return back()->with('status','Benchmark history record deleted.');
    }

    private function itemsFor(string $type): Collection
    {
        return $type === 'tool'
            ? Tool::with('company')->whereNotNull('benchmarks')->get()
            : AiModel::with('company')->whereNotNull('benchmarks')->get();
    }

    private function weightedComposite($item): float
    {
        $latest = BenchmarkResult::with('benchmark')
            ->where('benchmarkable_type',$item::class)->where('benchmarkable_id',$item->id)
            ->orderByDesc('tested_at')->orderByDesc('id')->get()->unique('benchmark_id');
        if ($latest->isEmpty()) return 0;
        $weighted = 0; $weights = 0;
        foreach ($latest as $result) {
            $weight = max((float) ($result->benchmark->weight ?? 1), 0.01);
            $max = max((float) ($result->benchmark->max_score ?? 100), 0.01);
            $normalized = min(100, max(0, ((float) $result->score / $max) * 100));
            $weighted += $normalized * $weight; $weights += $weight;
        }
        return round($weighted / $weights, 1);
    }

    private function ensureDefaults(): void
    {
        foreach ($this->defaults as $row) {
            Benchmark::firstOrCreate(['name'=>$row['name']], [
                'slug'=>Str::slug($row['name']),'category'=>$row['category'],'weight'=>$row['weight'],
                'max_score'=>100,'higher_is_better'=>true,'is_active'=>true,
            ]);
        }
    }

    private function uniqueBenchmarkSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'benchmark'; $slug = $base; $i = 2;
        while (Benchmark::where('slug',$slug)->exists()) $slug = $base.'-'.$i++;
        return $slug;
    }
}
