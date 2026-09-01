<?php

namespace App\Console\Commands;

use App\Models\Tool;
use App\Services\Tools\PlatformNormalizer;
use App\Services\Tools\ToolCommercialProfileService;
use Illuminate\Console\Command;

class ToolV3Audit extends Command
{
    protected $signature = 'tools:v3-audit {--published : Only published tools}';
    protected $description = 'Audit Phase 1 tool-data correctness and backfill coverage.';

    public function handle(ToolCommercialProfileService $commercial, PlatformNormalizer $platforms): int
    {
        $query = Tool::query()->with(['sources', 'platformTerms', 'useCaseTerms'])->orderBy('id');
        if ($this->option('published')) $query->where('status', 'published');
        $tools = $query->get();

        $missingSources = 0;
        $pricingMismatch = 0;
        $unknownPlatforms = [];
        $missingPlatformPivot = 0;
        $ratingMismatch = 0;
        $missingUseCases = 0;

        foreach ($tools as $tool) {
            if ($tool->sources->isEmpty()) $missingSources++;
            if (array_values((array) ($tool->pricing_models ?? [])) !== $commercial->expectedLabels($tool)) $pricingMismatch++;

            $normalized = $platforms->normalize($tool->platforms ?? []);
            if ($normalized['unknown'] !== []) $unknownPlatforms[$tool->name] = $normalized['unknown'];
            if ($normalized['canonical'] !== [] && $tool->platformTerms->isEmpty()) $missingPlatformPivot++;

            $avg = $tool->reviews()->published()->where('review_type', 'user')->avg('rating');
            $expected = $avg !== null ? round((float) $avg, 1) : 0.0;
            if (abs((float) $tool->rating - $expected) >= 0.001) $ratingMismatch++;
            if ($tool->useCaseTerms->isEmpty()) $missingUseCases++;
        }

        $this->table(['Phase 1 check', 'Count'], [
            ['Tools audited', $tools->count()],
            ['Missing tool_sources', $missingSources],
            ['Pricing cache mismatches', $pricingMismatch],
            ['Canonical platforms but missing pivot', $missingPlatformPivot],
            ['Tools with unknown platform aliases', count($unknownPlatforms)],
            ['Community rating mismatches', $ratingMismatch],
            ['Missing Best-for use cases', $missingUseCases],
        ]);

        foreach ($unknownPlatforms as $tool => $unknown) {
            $this->warn($tool.': '.implode(', ', $unknown));
        }

        return self::SUCCESS;
    }
}
