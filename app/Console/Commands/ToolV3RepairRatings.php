<?php

namespace App\Console\Commands;

use App\Models\Tool;
use Illuminate\Console\Command;

class ToolV3RepairRatings extends Command
{
    protected $signature = 'tools:v3-repair-ratings {--dry-run : Report changes without writing them} {--published : Only published tools}';
    protected $description = 'Repair tool community ratings so only published user reviews contribute.';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $query = Tool::query()->orderBy('id');
        if ($this->option('published')) $query->where('status', 'published');

        $checked = $changed = 0;
        $query->chunkById(200, function ($tools) use ($dryRun, &$checked, &$changed) {
            foreach ($tools as $tool) {
                $checked++;
                $average = $tool->reviews()->published()->where('review_type', 'user')->avg('rating');
                $expected = $average !== null ? round((float) $average, 1) : 0.0;
                if (abs((float) $tool->rating - $expected) < 0.001) continue;

                $changed++;
                $this->line("#{$tool->id} {$tool->name}: {$tool->rating} -> {$expected}");
                if (! $dryRun) $tool->updateQuietly(['rating' => $expected]);
            }
        });

        $this->info(($dryRun ? 'DRY RUN — ' : '')."Rating repair complete: {$checked} checked, {$changed} need/received updates.");
        return self::SUCCESS;
    }
}
