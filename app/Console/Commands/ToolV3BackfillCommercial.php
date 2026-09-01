<?php

namespace App\Console\Commands;

use App\Models\Tool;
use App\Services\Tools\ToolCommercialProfileService;
use Illuminate\Console\Command;

class ToolV3BackfillCommercial extends Command
{
    protected $signature = 'tools:v3-backfill-commercial {--dry-run : Report changes without writing them} {--published : Only published tools}';
    protected $description = 'Derive the legacy tools.pricing_models cache from detailed pricing plans.';

    public function handle(ToolCommercialProfileService $service): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $query = Tool::query()->orderBy('id');
        if ($this->option('published')) $query->where('status', 'published');

        $checked = $changed = 0;
        $query->chunkById(200, function ($tools) use ($service, $dryRun, &$checked, &$changed) {
            foreach ($tools as $tool) {
                $checked++;
                $before = array_values((array) ($tool->pricing_models ?? []));
                $expected = $service->expectedLabels($tool);
                if ($before === $expected) continue;

                $changed++;
                $this->line("#{$tool->id} {$tool->name}: [".implode(', ', $before)."] -> [".implode(', ', $expected).']');
                if (! $dryRun) $tool->updateQuietly(['pricing_models' => $expected]);
            }
        });

        $this->info(($dryRun ? 'DRY RUN — ' : '')."Commercial profile audit complete: {$checked} checked, {$changed} need/received updates.");
        return self::SUCCESS;
    }
}
