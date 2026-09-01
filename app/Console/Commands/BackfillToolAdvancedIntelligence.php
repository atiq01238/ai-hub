<?php

namespace App\Console\Commands;

use App\Models\Tool;
use App\Services\Tools\ToolAdvancedIntelligenceService;
use Illuminate\Console\Command;

class BackfillToolAdvancedIntelligence extends Command
{
    protected $signature = 'tools:v3-advanced-intelligence {--dry-run : Report only} {--published : Only published tools}';
    protected $description = 'Create advanced technical profiles and conservatively backfill only facts already implied by existing structured data.';

    public function handle(ToolAdvancedIntelligenceService $advanced): int
    {
        $query = Tool::query()->with(['technicalProfile','platformTerms','tagTerms','sources'])->orderBy('id');
        if ($this->option('published')) $query->where('status', 'published');

        $checked = $needsProfile = $needsUpdate = 0;
        $inferred = [];
        $write = ! $this->option('dry-run');

        $query->chunkById(100, function ($tools) use ($advanced, $write, &$checked, &$needsProfile, &$needsUpdate, &$inferred) {
            foreach ($tools as $tool) {
                $checked++;
                $result = $advanced->backfill($tool, $write);
                if ($result['needs_profile']) $needsProfile++;
                if ($result['needs_update']) $needsUpdate++;
                foreach ($result['inferred_fields'] as $field) $inferred[$field] = ($inferred[$field] ?? 0) + 1;
            }
        });

        $prefix = $write ? 'APPLIED' : 'DRY RUN';
        $this->info("{$prefix} — Advanced intelligence: {$checked} tools checked, {$needsProfile} need/received profile rows, {$needsUpdate} need/received safe backfill updates.");
        if ($inferred) {
            $this->table(['Conservative inference', 'Tools'], collect($inferred)->sortKeys()->map(fn ($count, $field) => [$field, $count])->values()->all());
        }
        $this->line('No integrations, privacy, security, licensing, commercial-use or lifecycle claims are invented by this backfill. Unknown remains Unknown until evidence is supplied.');
        return self::SUCCESS;
    }
}
