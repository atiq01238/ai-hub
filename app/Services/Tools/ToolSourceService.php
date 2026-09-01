<?php

namespace App\Services\Tools;

use App\Models\Tool;
use App\Models\ToolFactEvidence;
use App\Models\ToolSource;
use Carbon\CarbonInterface;
use Illuminate\Support\Str;

class ToolSourceService
{
    public function upsert(
        Tool $tool,
        string $url,
        string $sourceType = 'official_product',
        string $verificationStatus = 'pending',
        ?string $sourceName = null,
        CarbonInterface|string|null $verifiedAt = null,
        ?int $verifiedBy = null,
        ?string $factType = null,
        ?string $factKey = null,
        bool $primary = false,
    ): ?ToolSource {
        $url = trim($url);
        if ($url === '') return null;

        if (! in_array($sourceType, ToolSource::TYPES, true)) {
            $sourceType = $this->inferSourceType($url);
        }
        if (! in_array($verificationStatus, ToolSource::VERIFICATION_STATUSES, true)) {
            $verificationStatus = 'pending';
        }

        if ($primary) {
            $tool->sources()->where('is_primary', true)->update(['is_primary' => false]);
        }

        $source = $tool->sources()->where('source_url', $url)->first() ?: new ToolSource(['tool_id' => $tool->id]);
        $source->source_type = $sourceType;
        $source->source_name = $sourceName ?: $source->source_name ?: $this->defaultSourceName($tool, $sourceType);
        $source->source_url = $url;
        $source->is_primary = $primary || (bool) $source->is_primary;
        $source->enabled = true;

        // Routine imports must never downgrade an already verified source to pending.
        $effectiveStatus = $source->exists && $source->verification_status === 'verified' && $verificationStatus === 'pending'
            ? 'verified'
            : $verificationStatus;
        $source->verification_status = $effectiveStatus;

        if ($effectiveStatus === 'verified') {
            $source->verified_at = $verifiedAt ?: $source->verified_at ?: now();
            $source->last_checked_at = now();
            if ($verifiedBy) $source->verified_by = $verifiedBy;
        }

        $source->save();

        if ($factType) {
            $this->upsertFactEvidence($tool, $source, $factType, $factKey, $effectiveStatus);
        }

        return $source;
    }

    public function bootstrapFromWebsite(Tool $tool): ?ToolSource
    {
        if (! $tool->website) return null;

        return $this->upsert(
            tool: $tool,
            url: $tool->website,
            sourceType: 'official_product',
            verificationStatus: 'pending',
            sourceName: $tool->name . ' official website',
            factType: 'identity',
            factKey: 'website',
            primary: true,
        );
    }

    public function inferSourceType(string $url): string
    {
        $path = mb_strtolower((string) parse_url($url, PHP_URL_PATH));
        if (Str::contains($path, ['pricing', 'plans', 'billing'])) return 'official_pricing';
        if (Str::contains($path, ['api-doc', 'api_docs', '/api/'])) return 'api_docs';
        if (Str::contains($path, ['docs', 'documentation', 'help'])) return 'documentation';
        if (Str::contains($path, ['privacy'])) return 'privacy';
        if (Str::contains($path, ['security', 'trust', 'compliance'])) return Str::contains($path, ['compliance']) ? 'compliance' : 'security';
        if (Str::contains($path, ['license', 'licence'])) return 'license';
        if (Str::contains($path, ['terms', 'legal'])) return 'terms';
        if (Str::contains($path, ['integrations', 'integration', 'connectors'])) return 'integration_docs';
        if (Str::contains($path, ['availability', 'regions', 'countries'])) return 'availability';
        if (Str::contains($path, ['changelog', 'release-notes', 'releases'])) return 'changelog';
        return 'official_product';
    }

    public function syncFactEvidence(Tool $tool, ?ToolSource $source, string $factType, ?string $factKey, ?string $notes = null, bool $allowVerified = true): ToolFactEvidence
    {
        $status = $allowVerified && $source?->verification_status === 'verified' ? 'verified' : 'pending';
        $evidence = ToolFactEvidence::query()
            ->where('tool_id', $tool->id)
            ->where('fact_type', $factType)
            ->where('fact_key', $factKey)
            ->first() ?: new ToolFactEvidence();

        $evidence->fill([
            'tool_id' => $tool->id,
            'tool_source_id' => $source?->id,
            'fact_type' => $factType,
            'fact_key' => $factKey,
            'verification_status' => $status,
            'verified_at' => $status === 'verified' ? ($source?->verified_at ?: now()) : null,
            'notes' => $notes,
        ])->save();

        return $evidence;
    }

    private function upsertFactEvidence(Tool $tool, ToolSource $source, string $factType, ?string $factKey, string $status): void
    {
        $evidence = ToolFactEvidence::query()
            ->where('tool_id', $tool->id)
            ->where('tool_source_id', $source->id)
            ->where('fact_type', $factType)
            ->where('fact_key', $factKey)
            ->first() ?: new ToolFactEvidence();

        $evidence->fill([
            'tool_id' => $tool->id,
            'tool_source_id' => $source->id,
            'fact_type' => $factType,
            'fact_key' => $factKey,
            'verification_status' => $status,
            'verified_at' => $status === 'verified' ? ($source->verified_at ?: now()) : null,
        ])->save();
    }

    private function defaultSourceName(Tool $tool, string $sourceType): string
    {
        return $tool->name . ' ' . match ($sourceType) {
            'official_pricing' => 'official pricing',
            'documentation' => 'documentation',
            'api_docs' => 'API documentation',
            'privacy' => 'privacy source',
            'security' => 'security source',
            'compliance' => 'compliance source',
            'terms' => 'terms source',
            'license' => 'license source',
            'repository' => 'repository',
            'integration_docs' => 'integration documentation',
            'availability' => 'availability source',
            default => 'official source',
        };
    }
}
