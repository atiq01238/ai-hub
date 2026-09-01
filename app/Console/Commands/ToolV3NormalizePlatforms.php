<?php

namespace App\Console\Commands;

use App\Models\Tool;
use App\Services\Tools\PlatformNormalizer;
use Illuminate\Console\Command;

class ToolV3NormalizePlatforms extends Command
{
    protected $signature = 'tools:v3-normalize-platforms {--dry-run : Report mappings without writing them} {--published : Only published tools}';
    protected $description = 'Normalize legacy tool platform JSON into canonical platform terms and pivot relations.';

    public function handle(PlatformNormalizer $normalizer): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $query = Tool::query()->orderBy('id');
        if ($this->option('published')) $query->where('status', 'published');

        $checked = $changed = $unknownTools = 0;
        $query->chunkById(200, function ($tools) use ($normalizer, $dryRun, &$checked, &$changed, &$unknownTools) {
            foreach ($tools as $tool) {
                $checked++;
                $before = array_values((array) ($tool->platforms ?? []));
                $result = $normalizer->normalize($before);

                if ($result['unknown'] !== []) {
                    $unknownTools++;
                    $this->warn("#{$tool->id} {$tool->name}: unknown platform(s): ".implode(', ', $result['unknown']).' - skipped to avoid data loss.');
                    continue;
                }

                $missingTerms = $normalizer->missingCanonicalNames($result['canonical']);
                if ($missingTerms !== []) {
                    $unknownTools++;
                    $this->warn("#{$tool->id} {$tool->name}: canonical platform term(s) missing from DB: ".implode(', ', $missingTerms).' - run the platform taxonomy migration before backfill.');
                    continue;
                }

                $currentPivot = $tool->platformTerms()->orderBy('platforms.sort_order')->pluck('platforms.name')->all();
                if ($before === $result['canonical'] && $currentPivot === $result['canonical']) continue;

                $changed++;
                $this->line("#{$tool->id} {$tool->name}: [".implode(', ', $before)."] -> [".implode(', ', $result['canonical']).']');
                if (! $dryRun) $normalizer->syncTool($tool, $result['canonical']);
            }
        });

        $this->info(($dryRun ? 'DRY RUN - ' : '')."Platform normalization complete: {$checked} checked, {$changed} need/received updates, {$unknownTools} tools have unknown values.");
        return $unknownTools > 0 ? self::SUCCESS : self::SUCCESS;
    }
}
