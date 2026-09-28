<?php

namespace App\Console\Commands;

use App\Models\AiModel;
use App\Models\Tool;
use App\Services\BenchmarkScoringService;
use Illuminate\Console\Command;

class SyncBenchmarkScoreMirrors extends Command
{
    protected $signature = 'benchmarks:sync-score-mirrors
                            {--dry-run : Report mismatches without writing changes}
                            {--models-only : Check only AI models}
                            {--tools-only : Check only AI tools}';

    protected $description = 'Repair legacy benchmark_score mirrors from verified class-safe benchmark results.';

    public function handle(BenchmarkScoringService $scoring): int
    {
        if ($this->option('models-only') && $this->option('tools-only')) {
            $this->error('Use either --models-only or --tools-only, not both.');
            return self::INVALID;
        }

        $dryRun = (bool) $this->option('dry-run');
        $checked = 0;
        $withVerifiedComposite = 0;
        $mismatches = 0;
        $updated = 0;
        $details = [];

        if (! $this->option('models-only')) {
            Tool::query()
                ->where('status', 'published')
                ->orderBy('id')
                ->chunkById(100, function ($tools) use ($scoring, $dryRun, &$checked, &$withVerifiedComposite, &$mismatches, &$updated, &$details) {
                    foreach ($tools as $tool) {
                        $this->inspectItem($tool, 'tool', $scoring, $dryRun, $checked, $withVerifiedComposite, $mismatches, $updated, $details);
                    }
                });
        }

        if (! $this->option('tools-only')) {
            AiModel::query()
                ->whereIn('status', ['active', 'preview'])
                ->orderBy('id')
                ->chunkById(100, function ($models) use ($scoring, $dryRun, &$checked, &$withVerifiedComposite, &$mismatches, &$updated, &$details) {
                    foreach ($models as $model) {
                        $this->inspectItem($model, 'model', $scoring, $dryRun, $checked, $withVerifiedComposite, $mismatches, $updated, $details);
                    }
                });
        }

        $this->info('AI Orbit benchmark score mirror sync');
        $this->table(['Check', 'Count'], [
            ['Published/active items checked', $checked],
            ['Items with verified composite evidence', $withVerifiedComposite],
            ['Mismatched benchmark_score mirrors', $mismatches],
            [$dryRun ? 'Would update' : 'Updated', $dryRun ? $mismatches : $updated],
        ]);

        if ($details !== []) {
            $this->newLine();
            $this->table(
                ['Type', 'ID', 'Name', 'Stored', 'Verified composite', 'Class'],
                array_slice($details, 0, 50)
            );

            if (count($details) > 50) {
                $this->comment('Only the first 50 mismatches are shown.');
            }
        }

        if ($dryRun) {
            $this->comment('Dry run only. Re-run without --dry-run to repair the listed mirrors.');
        } elseif ($updated > 0) {
            $this->info('Mirrors repaired from verified benchmark evidence. Run benchmarks:v3-audit to confirm zero mismatches.');
        } else {
            $this->info('No benchmark score mirror repairs were needed.');
        }

        return self::SUCCESS;
    }

    private function inspectItem(
        object $item,
        string $type,
        BenchmarkScoringService $scoring,
        bool $dryRun,
        int &$checked,
        int &$withVerifiedComposite,
        int &$mismatches,
        int &$updated,
        array &$details
    ): void {
        $checked++;

        $class = $scoring->primaryCompositeClass($item);
        if ($class === null) {
            // No verified class-safe benchmark result means there is no trusted
            // composite to mirror. Preserve any legacy/imported score as-is.
            return;
        }

        $withVerifiedComposite++;
        $expected = $scoring->compositeForClass($item, $class);
        if ($this->sameScore($expected, $item->benchmark_score)) {
            return;
        }

        $mismatches++;
        $details[] = [
            $type,
            $item->id,
            $item->name,
            $item->benchmark_score === null ? 'null' : number_format((float) $item->benchmark_score, 1),
            $expected === null ? 'null' : number_format((float) $expected, 1),
            $class,
        ];

        if (! $dryRun) {
            $scoring->sync($item);
            $updated++;
        }
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
