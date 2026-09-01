<?php

namespace App\Console\Commands;

use App\Models\Tool;
use App\Services\Tools\ToolSourceService;
use Illuminate\Console\Command;

class ToolV3BootstrapSources extends Command
{
    protected $signature = 'tools:v3-bootstrap-sources {--dry-run : Report additions without writing them} {--published : Only published tools}';
    protected $description = 'Bootstrap pending official-product source rows from existing tool website URLs.';

    public function handle(ToolSourceService $service): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $query = Tool::query()->whereNotNull('website')->where('website', '!=', '')->orderBy('id');
        if ($this->option('published')) $query->where('status', 'published');

        $checked = $created = 0;
        $query->chunkById(200, function ($tools) use ($service, $dryRun, &$checked, &$created) {
            foreach ($tools as $tool) {
                $checked++;
                $exists = $tool->sources()->where('source_url', $tool->website)->exists();
                if ($exists) continue;

                $created++;
                $this->line("#{$tool->id} {$tool->name}: bootstrap pending official source {$tool->website}");
                if (! $dryRun) $service->bootstrapFromWebsite($tool);
            }
        });

        $this->info(($dryRun ? 'DRY RUN — ' : '')."Source bootstrap complete: {$checked} websites checked, {$created} source rows need/were created. Website bootstrap is pending evidence, not an automatic Verified claim.");
        return self::SUCCESS;
    }
}
