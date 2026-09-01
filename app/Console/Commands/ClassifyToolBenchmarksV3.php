<?php

namespace App\Console\Commands;

use App\Models\AiModel;
use App\Models\Benchmark;
use App\Models\Tool;
use App\Services\BenchmarkScoringService;
use App\Services\BenchmarkSemanticsService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ClassifyToolBenchmarksV3 extends Command
{
    protected $signature = 'tools:v3-classify-benchmarks
        {--dry-run : Show proposed classifications without writing}
        {--force : Re-evaluate already classified benchmarks}
        {--show-all : Print unchanged classifications too}';

    protected $description = 'Classify benchmark definitions into semantic classes without mixing incompatible score types.';

    public function handle(BenchmarkSemanticsService $semantics, BenchmarkScoringService $scoring): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $force = (bool) $this->option('force');
        $showAll = (bool) $this->option('show-all');

        $benchmarks = Benchmark::query()->with('results')->orderBy('name')->get();
        $changed = 0;
        $unchanged = 0;
        $ambiguous = 0;
        $touched = [];

        foreach ($benchmarks as $benchmark) {
            $current = $benchmark->benchmark_class ?: Benchmark::CLASS_UNCLASSIFIED;
            if (! $force && $current !== Benchmark::CLASS_UNCLASSIFIED) {
                $unchanged++;
                if ($showAll) {
                    $this->line("#{$benchmark->id} {$benchmark->name}: ".Benchmark::classLabel($current).' — already classified');
                }
                continue;
            }

            $inference = $semantics->infer($benchmark);
            if ($inference['class'] === Benchmark::CLASS_UNCLASSIFIED) {
                $ambiguous++;
                $this->warn("#{$benchmark->id} {$benchmark->name}: UNCLASSIFIED — {$inference['reason']}");
                continue;
            }

            if ($current === $inference['class']) {
                $unchanged++;
                if ($showAll) {
                    $this->line("#{$benchmark->id} {$benchmark->name}: {$inference['label']} — unchanged");
                }
                continue;
            }

            $changed++;
            $this->line("#{$benchmark->id} {$benchmark->name}: ".Benchmark::classLabel($current)." -> {$inference['label']} ({$inference['confidence']})");

            if (! $dryRun) {
                DB::transaction(function () use ($benchmark, $inference, &$touched) {
                    $benchmark->update(['benchmark_class' => $inference['class']]);
                    foreach ($benchmark->results as $result) {
                        $key = $result->benchmarkable_type.':'.$result->benchmarkable_id;
                        $touched[$key] = [$result->benchmarkable_type, (int) $result->benchmarkable_id];
                    }
                });
            }
        }

        if (! $dryRun) {
            foreach ($touched as [$type, $id]) {
                $item = match ($type) {
                    Tool::class => Tool::find($id),
                    AiModel::class => AiModel::find($id),
                    default => null,
                };
                if ($item) {
                    $scoring->sync($item);
                }
            }
        }

        $prefix = $dryRun ? 'DRY RUN — ' : '';
        $this->info("{$prefix}Benchmark classification complete: {$benchmarks->count()} definitions checked, {$changed} need/received classification, {$unchanged} unchanged, {$ambiguous} ambiguous/unclassified.");

        return self::SUCCESS;
    }
}
