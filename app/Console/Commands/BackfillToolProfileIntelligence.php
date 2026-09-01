<?php

namespace App\Console\Commands;

use App\Models\Tool;
use App\Services\Tools\ToolProfileIntelligenceService;
use Illuminate\Console\Command;

class BackfillToolProfileIntelligence extends Command
{
    protected $signature = 'tools:v3-profile-intelligence {--dry-run} {--published}';
    protected $description = 'Link existing feature/use-case mappings to source evidence without inventing verification claims.';

    public function handle(ToolProfileIntelligenceService $profiles): int
    {
        $query = Tool::query()->with(['sources','featureTerms','useCaseTerms'])->orderBy('id');
        if ($this->option('published')) $query->where('status', 'published');

        $checked = $featureLinks = $useCaseLinks = $withoutSource = 0;
        $write = ! $this->option('dry-run');

        $query->chunkById(100, function ($tools) use ($profiles, $write, &$checked, &$featureLinks, &$useCaseLinks, &$withoutSource) {
            foreach ($tools as $tool) {
                $checked++;
                $result = $profiles->bootstrapEvidenceLinks($tool, $write);
                $featureLinks += $result['feature_links'];
                $useCaseLinks += $result['use_case_links'];
                if (! $result['has_source'] && ($tool->featureTerms->isNotEmpty() || $tool->useCaseTerms->isNotEmpty())) $withoutSource++;
            }
        });

        $prefix = $write ? 'APPLIED' : 'DRY RUN';
        $this->info("{$prefix} — Profile intelligence: {$checked} tools checked, {$featureLinks} feature mappings and {$useCaseLinks} use-case mappings need/received source links; {$withoutSource} tools with taxonomy mappings have no source to link.");
        $this->line('Backfill links evidence as Pending only. It never upgrades a capability/use-case to Verified automatically.');
        return self::SUCCESS;
    }
}
