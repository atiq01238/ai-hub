<?php

namespace App\Services\Tools;

use App\Models\Tool;
use App\Models\ToolSource;
use Illuminate\Support\Facades\DB;

class ToolProfileIntelligenceService
{
    public function primarySource(Tool $tool): ?ToolSource
    {
        return $tool->sources()->where('enabled', true)->orderByDesc('is_primary')->orderByDesc('verified_at')->orderByDesc('id')->first();
    }

    public function syncFeatureProfiles(Tool $tool, array $profiles, ?ToolSource $fallbackSource = null): void
    {
        $attached = $tool->featureTerms()->pluck('features.id')->map(fn ($id) => (int) $id)->all();
        $fallbackSource ??= $this->primarySource($tool);

        foreach ($attached as $featureId) {
            $profile = $profiles[$featureId] ?? $profiles[(string) $featureId] ?? [];
            $requestedStatus = ($profile['verification_status'] ?? 'pending') === 'verified' ? 'verified' : 'pending';
            $source = $this->validSource($tool, $profile['tool_source_id'] ?? null) ?: $fallbackSource;
            $sourceId = $source?->id;
            $status = $requestedStatus === 'verified' && $source?->verification_status === 'verified' ? 'verified' : 'pending';
            $description = trim((string) ($profile['description'] ?? '')) ?: null;
            $notes = trim((string) ($profile['notes'] ?? '')) ?: null;

            DB::table('feature_tool')
                ->where('tool_id', $tool->id)
                ->where('feature_id', $featureId)
                ->update([
                    'description' => $description,
                    'verification_status' => $status,
                    'tool_source_id' => $sourceId,
                    'verified_at' => $status === 'verified' ? now() : null,
                    'notes' => $notes,
                    'updated_at' => now(),
                ]);
        }
    }

    public function syncUseCaseProfiles(Tool $tool, array $profiles, ?ToolSource $fallbackSource = null): void
    {
        $attached = $tool->useCaseTerms()->pluck('use_cases.id')->map(fn ($id) => (int) $id)->all();
        $fallbackSource ??= $this->primarySource($tool);

        foreach ($attached as $useCaseId) {
            $profile = $profiles[$useCaseId] ?? $profiles[(string) $useCaseId] ?? [];
            $requestedStatus = ($profile['verification_status'] ?? 'pending') === 'verified' ? 'verified' : 'pending';
            $source = $this->validSource($tool, $profile['tool_source_id'] ?? null) ?: $fallbackSource;
            $sourceId = $source?->id;
            $status = $requestedStatus === 'verified' && $source?->verification_status === 'verified' ? 'verified' : 'pending';
            $fitNote = trim((string) ($profile['fit_note'] ?? '')) ?: null;
            $notes = trim((string) ($profile['notes'] ?? '')) ?: null;

            DB::table('tool_use_case')
                ->where('tool_id', $tool->id)
                ->where('use_case_id', $useCaseId)
                ->update([
                    'fit_note' => $fitNote,
                    'verification_status' => $status,
                    'tool_source_id' => $sourceId,
                    'verified_at' => $status === 'verified' ? now() : null,
                    'notes' => $notes,
                    'updated_at' => now(),
                ]);
        }
    }

    public function bootstrapEvidenceLinks(Tool $tool, bool $write = false): array
    {
        $source = $this->primarySource($tool);
        $featureQuery = DB::table('feature_tool')->where('tool_id', $tool->id)->whereNull('tool_source_id');
        $useCaseQuery = DB::table('tool_use_case')->where('tool_id', $tool->id)->whereNull('tool_source_id');
        $featureCount = $featureQuery->count();
        $useCaseCount = $useCaseQuery->count();

        if ($write && $source) {
            $featureQuery->update(['tool_source_id' => $source->id, 'verification_status' => 'pending', 'verified_at' => null, 'updated_at' => now()]);
            $useCaseQuery->update(['tool_source_id' => $source->id, 'verification_status' => 'pending', 'verified_at' => null, 'updated_at' => now()]);
        }

        return [
            'has_source' => (bool) $source,
            'feature_links' => $source ? $featureCount : 0,
            'use_case_links' => $source ? $useCaseCount : 0,
        ];
    }

    public function evidenceSummary(Tool $tool): array
    {
        $featureTotal = $tool->featureTerms->count();
        $featureVerified = $tool->featureTerms->filter(fn ($feature) => ($feature->pivot->verification_status ?? 'pending') === 'verified')->count();
        $useCaseTotal = $tool->useCaseTerms->count();
        $useCaseVerified = $tool->useCaseTerms->filter(fn ($useCase) => ($useCase->pivot->verification_status ?? 'pending') === 'verified')->count();

        return compact('featureTotal','featureVerified','useCaseTotal','useCaseVerified');
    }

    private function validSource(Tool $tool, mixed $sourceId): ?ToolSource
    {
        $sourceId = (int) $sourceId;
        if ($sourceId <= 0) return null;
        return $tool->sources()->whereKey($sourceId)->where('enabled', true)->first();
    }
}
