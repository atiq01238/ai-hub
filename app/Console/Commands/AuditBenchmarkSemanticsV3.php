<?php

namespace App\Console\Commands;

use App\Models\AiModel;
use App\Models\Benchmark;
use App\Models\BenchmarkResult;
use App\Models\Tool;
use App\Services\BenchmarkScoringService;
use Illuminate\Console\Command;

class AuditBenchmarkSemanticsV3 extends Command
{
    protected $signature = 'benchmarks:v3-audit';
    protected $description = 'Audit Phase 2 benchmark semantic classification and class-safe composite mirrors.';

    public function handle(BenchmarkScoringService $scoring): int
    {
        $active = Benchmark::where('is_active', true)->count();
        $verified = BenchmarkResult::where('verified', true)->where('status', 'verified')->count();
        $unclassified = Benchmark::where('is_active', true)
            ->where('benchmark_class', Benchmark::CLASS_UNCLASSIFIED)
            ->whereHas('results', fn ($q) => $q->where('verified', true)->where('status', 'verified'))
            ->count();

        $rows = [
            ['Active benchmarks', $active],
            ['Verified results', $verified],
            ['Technical Performance', Benchmark::where('benchmark_class', Benchmark::CLASS_TECHNICAL)->count()],
            ['Product Experience', Benchmark::where('benchmark_class', Benchmark::CLASS_PRODUCT_EXPERIENCE)->count()],
            ['Independent Research', Benchmark::where('benchmark_class', Benchmark::CLASS_INDEPENDENT_RESEARCH)->count()],
            ['AI Orbit Tested', Benchmark::where('benchmark_class', Benchmark::CLASS_AI_ORBIT_TESTED)->count()],
            ['Unclassified with verified results', $unclassified],
        ];

        $mismatches = 0;
        foreach (Tool::where('status', 'published')->get() as $tool) {
            $class = $scoring->primaryCompositeClass($tool);
            $expected = $class ? $scoring->compositeForClass($tool, $class) : null;
            if (! $this->sameScore($expected, $tool->benchmark_score)) {
                $mismatches++;
            }
        }
        foreach (AiModel::whereIn('status', ['active', 'preview'])->get() as $model) {
            $class = $scoring->primaryCompositeClass($model);
            $expected = $class ? $scoring->compositeForClass($model, $class) : null;
            if (! $this->sameScore($expected, $model->benchmark_score)) {
                $mismatches++;
            }
        }

        $rows[] = ['Class-safe composite mirror mismatches', $mismatches];
        $this->table(['Phase 2 check', 'Count'], $rows);

        return ($unclassified === 0 && $mismatches === 0) ? self::SUCCESS : self::SUCCESS;
    }

    private function sameScore($expected, $actual): bool
    {
        if ($expected === null && $actual === null) {
            return true;
        }
        if ($expected === null || $actual === null) {
            return false;
        }
        return abs((float) $expected - (float) $actual) < 0.05;
    }
}
