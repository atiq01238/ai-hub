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
use App\Services\BenchmarkScoringService;
use App\Services\BenchmarkSemanticsService;

class BenchmarkController extends Controller
{
    private array $defaults = [
        ['name'=>'MMLU Pro','category'=>'Knowledge & Reasoning','weight'=>1.20,'benchmark_class'=>Benchmark::CLASS_TECHNICAL],
        ['name'=>'HumanEval','category'=>'Coding','weight'=>1.15,'benchmark_class'=>Benchmark::CLASS_TECHNICAL],
        ['name'=>'GPQA Diamond','category'=>'Reasoning','weight'=>1.20,'benchmark_class'=>Benchmark::CLASS_TECHNICAL],
        ['name'=>'MATH','category'=>'Mathematics','weight'=>1.00,'benchmark_class'=>Benchmark::CLASS_TECHNICAL],
        ['name'=>'SWE-bench','category'=>'Software Engineering','weight'=>1.25,'benchmark_class'=>Benchmark::CLASS_TECHNICAL],
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
            'benchmarkDefinitions'=>Benchmark::where('is_active',true)->orderBy('name')->get(['name','benchmark_class']),
            'benchmarkClasses'=>collect(Benchmark::CLASSES)->mapWithKeys(fn ($class) => [$class => Benchmark::classLabel($class)])->all(),
        ]);
    }

    public function store(Request $request, BenchmarkScoringService $scoring, BenchmarkSemanticsService $semantics)
    {
        $data = $request->validate([
            'type'=>['required','in:model,tool'], 'item_id'=>['required','integer'],
            'benchmark_name'=>['required','string','max:100'], 'benchmark_class'=>['required','in:'.implode(',',Benchmark::CLASSES)], 'score'=>['required','numeric'],
            'tested_at'=>['nullable','date'], 'source_name'=>['nullable','string','max:150'],
            'source_url'=>['nullable','url','max:500'], 'notes'=>['nullable','string','max:2000'], 'source_type'=>['nullable','in:official,benchmark_org,research_paper,independent,ai_hub,community'], 'model_version'=>['nullable','string','max:150'],
            'verified'=>['nullable','boolean'],
        ]);
        $item = $data['type'] === 'tool' ? Tool::findOrFail($data['item_id']) : AiModel::findOrFail($data['item_id']);
        $benchmark = Benchmark::firstOrCreate(
            ['name'=>$data['benchmark_name']],
            [
                'slug'=>$this->uniqueBenchmarkSlug($data['benchmark_name']),
                'category'=>'Custom',
                'benchmark_class'=>$semantics->normalize($data['benchmark_class']),
                'weight'=>1,
                'max_score'=>100,
                'higher_is_better'=>true,
                'is_active'=>true,
            ]
        );
        $requestedClass = $semantics->normalize($data['benchmark_class']);
        $currentClass = $benchmark->benchmark_class ?: Benchmark::CLASS_UNCLASSIFIED;
        if ($currentClass === Benchmark::CLASS_UNCLASSIFIED) {
            $benchmark->update(['benchmark_class' => $requestedClass]);
        } elseif ($currentClass !== $requestedClass) {
            return back()->withInput()->withErrors([
                'benchmark_class' => 'This benchmark is already classified as '.Benchmark::classLabel($currentClass).'. Reclassify it through the Phase 2 benchmark classification workflow instead of changing semantics while adding a result.',
            ]);
        }

        BenchmarkResult::create([
            'benchmark_id'=>$benchmark->id,
            'benchmarkable_type'=>$item::class,
            'benchmarkable_id'=>$item->id,
            'score'=>$data['score'],
            'tested_at'=>$data['tested_at'] ?? now()->toDateString(),
            'source_name'=>$data['source_name'] ?? null,
            'source_url'=>$data['source_url'] ?? null,
            'notes'=>$data['notes'] ?? null, 'source_type'=>$data['source_type'] ?? 'independent', 'model_version'=>$data['model_version'] ?? null,
            'verified'=>$request->boolean('verified'), 'status'=>$request->boolean('verified') ? 'verified' : 'pending', 'verified_by'=>$request->boolean('verified') ? $request->user()?->id : null, 'verified_at'=>$request->boolean('verified') ? now() : null,
            'fingerprint'=>$scoring->fingerprint($benchmark->id,$item::class,$item->id,$data['tested_at'] ?? now()->toDateString(),$data['source_url'] ?? null,(float)$data['score']),
        ]);

        // Verified benchmark_results are the source of truth; legacy fields are compatibility mirrors.
        $scoring->sync($item);

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
        if ($request->filled('benchmark_class')) $query->whereHas('benchmark', fn ($q) => $q->where('benchmark_class',$request->query('benchmark_class')));
        $results = $query->paginate(25)->withQueryString();
        $benchmarks = Benchmark::orderBy('name')->get();
        $benchmarkClasses = collect(Benchmark::CLASSES)->mapWithKeys(fn ($class) => [$class => Benchmark::classLabel($class)])->all();
        return view('benchmarks.results', compact('results','benchmarks','benchmarkClasses'));
    }

    public function destroyResult(int $resultId, BenchmarkScoringService $scoring)
    {
        $result = BenchmarkResult::with('benchmarkable')->findOrFail($resultId);
        $item = $result->benchmarkable; $result->delete();
        if ($item) $scoring->sync($item);
        return back()->with('status','Benchmark history record deleted.');
    }

    private function itemsFor(string $type): Collection
    {
        return $type === 'tool'
            ? Tool::with('company')->whereNotNull('benchmarks')->get()
            : AiModel::with('company')->whereNotNull('benchmarks')->get();
    }

    private function ensureDefaults(): void
    {
        foreach ($this->defaults as $row) {
            Benchmark::firstOrCreate(['name'=>$row['name']], [
                'slug'=>Str::slug($row['name']),'category'=>$row['category'],'benchmark_class'=>$row['benchmark_class'] ?? Benchmark::CLASS_TECHNICAL,'weight'=>$row['weight'],
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
