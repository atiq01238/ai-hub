<?php

namespace App\Services\Tools;

use App\Models\Integration;
use App\Models\Tool;
use App\Models\ToolSource;
use App\Models\ToolTechnicalProfile;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class ToolAdvancedIntelligenceService
{
    public function __construct(
        private readonly ToolSourceService $sourceService,
        private readonly ToolProfileIntelligenceService $profileIntelligence,
    ) {}

    public function syncTechnicalProfile(
        Tool $tool,
        array $values,
        array $sources = [],
        ?int $userId = null,
        bool $partial = false,
    ): ToolTechnicalProfile {
        $profile = $tool->technicalProfile ?: new ToolTechnicalProfile(['tool_id' => $tool->id]);
        $payload = $this->normalizeProfileValues($values, $partial && $profile->exists);
        $primary = $this->profileIntelligence->primarySource($tool);

        $sourceDefinitions = [
            'api' => ['type' => 'api_docs', 'name' => $tool->name.' API documentation'],
            'repository' => ['type' => 'repository', 'name' => $tool->name.' repository / license source'],
            'deployment' => ['type' => 'documentation', 'name' => $tool->name.' deployment / self-hosting documentation'],
            'terms' => ['type' => 'terms', 'name' => $tool->name.' commercial-use terms'],
            'availability' => ['type' => 'availability', 'name' => $tool->name.' availability source'],
            'privacy' => ['type' => 'privacy', 'name' => $tool->name.' privacy source'],
            'security' => ['type' => 'security', 'name' => $tool->name.' security / compliance source'],
        ];

        $resolvedSources = [];
        foreach ($sourceDefinitions as $key => $definition) {
            $input = (array) ($sources[$key] ?? []);
            $urlProvided = array_key_exists('url', $input);
            $url = trim((string) ($input['url'] ?? ''));
            $source = null;

            if ($url !== '') {
                $status = ($input['status'] ?? 'pending') === 'verified' ? 'verified' : 'pending';
                $source = $this->sourceService->upsert(
                    tool: $tool,
                    url: $url,
                    sourceType: $definition['type'],
                    verificationStatus: $status,
                    sourceName: $definition['name'],
                    verifiedAt: $status === 'verified' ? now() : null,
                    verifiedBy: $status === 'verified' ? $userId : null,
                    primary: false,
                );
            } elseif (! $partial && $urlProvided) {
                $source = null;
            } else {
                $column = $this->sourceColumn($key);
                $source = $profile->exists && $profile->{$column}
                    ? $tool->sources()->whereKey($profile->{$column})->where('enabled', true)->first()
                    : null;
            }

            $resolvedSources[$key] = $source;
            $column = $this->sourceColumn($key);
            if ($source || (! $partial && $urlProvided)) {
                $payload[$column] = $source?->id;
            }
        }

        if (! $partial || $payload !== []) {
            $payload['last_reviewed_at'] = now();
        }

        $profile->fill($payload);
        $profile->tool_id = $tool->id;
        $profile->save();

        $this->syncProfileEvidence($tool, $profile, $resolvedSources, $primary);
        return $profile->fresh();
    }

    public function syncIntegrations(
        Tool $tool,
        array $names,
        ?string $sourceUrl = null,
        string $sourceStatus = 'pending',
        ?int $userId = null,
        bool $replace = true,
    ): array {
        $names = collect($names)
            ->map(fn ($name) => trim((string) $name))
            ->filter()
            ->unique(fn ($name) => mb_strtolower($name))
            ->values();

        $source = null;
        $explicitSource = trim((string) $sourceUrl) !== '';
        if ($explicitSource) {
            $status = $sourceStatus === 'verified' ? 'verified' : 'pending';
            $source = $this->sourceService->upsert(
                tool: $tool,
                url: trim((string) $sourceUrl),
                sourceType: 'integration_docs',
                verificationStatus: $status,
                sourceName: $tool->name.' integration documentation',
                verifiedAt: $status === 'verified' ? now() : null,
                verifiedBy: $status === 'verified' ? $userId : null,
                factType: 'integration',
                factKey: 'catalog',
                primary: false,
            );
        }

        $source ??= $this->profileIntelligence->primarySource($tool);
        $status = $explicitSource && $source?->verification_status === 'verified' ? 'verified' : 'pending';
        $sync = [];

        foreach ($names as $name) {
            $integration = Integration::query()->where('name', $name)->first();
            if (! $integration) {
                $base = Str::slug($name) ?: 'integration';
                $slug = $base;
                $counter = 2;
                while (Integration::where('slug', $slug)->exists()) $slug = $base.'-'.$counter++;
                $integration = Integration::create(['name' => $name, 'slug' => $slug, 'is_active' => true]);
            }
            $sync[$integration->id] = [
                'tool_source_id' => $source?->id,
                'verification_status' => $status,
                'verified_at' => $status === 'verified' ? ($source?->verified_at ?: now()) : null,
                'notes' => null,
            ];
        }

        if ($replace) $tool->integrationTerms()->sync($sync);
        elseif ($sync) $tool->integrationTerms()->syncWithoutDetaching($sync);

        return ['count' => count($sync), 'verified' => $status === 'verified'];
    }

    public function backfill(Tool $tool, bool $write = false): array
    {
        $tool->loadMissing(['platformTerms:id,name', 'tagTerms:id,name', 'sources']);
        $profile = $tool->technicalProfile;
        $platforms = $tool->platformTerms->pluck('name')->merge(collect($tool->platforms ?? []))->filter()->unique()->values();
        $tags = $tool->tagTerms->pluck('name')->merge(collect($tool->tags ?? []))->filter()->unique()->values();

        $inferred = [];
        if (($profile?->api_status ?? 'unknown') === 'unknown' && $platforms->contains('API')) $inferred['api_status'] = 'available';
        if (($profile?->open_source_status ?? 'unknown') === 'unknown' && $tags->contains(fn ($tag) => mb_strtolower((string) $tag) === 'open source')) $inferred['open_source_status'] = 'open_source';
        if (($profile?->self_hosting_status ?? 'unknown') === 'unknown' && $platforms->contains('Self Hosted')) $inferred['self_hosting_status'] = 'supported';

        $deployment = $this->deploymentModesFromPlatforms($platforms->all());
        if (empty($profile?->deployment_modes) && $deployment) $inferred['deployment_modes'] = $deployment;

        $needsProfile = ! $profile;
        if ($write && ($needsProfile || $inferred)) {
            $backfilled = $this->syncTechnicalProfile($tool, $inferred, [], null, true);
            // A mechanical compatibility backfill is not a human/source review.
            // Keep review freshness unset until an admin/import actually reviews evidence.
            $backfilled->updateQuietly(['last_reviewed_at' => null]);
        }

        return [
            'needs_profile' => $needsProfile,
            'inferred_fields' => array_keys($inferred),
            'needs_update' => $needsProfile || $inferred !== [],
        ];
    }

    public function splitList(mixed $value): array
    {
        if (is_array($value)) return array_values(array_unique(array_filter(array_map(fn ($v) => trim((string) $v), $value))));
        return array_values(array_unique(array_filter(array_map('trim', preg_split('/[|;,\r\n]+/', (string) $value) ?: []))));
    }

    private function normalizeProfileValues(array $values, bool $partial): array
    {
        $defaults = [
            'api_status' => 'unknown', 'api_docs_url' => null,
            'open_source_status' => 'unknown', 'license_name' => null, 'repository_url' => null,
            'self_hosting_status' => 'unknown', 'deployment_modes' => [], 'commercial_use_status' => 'unknown',
            'supported_languages' => [], 'region_availability' => [],
            'data_training_policy' => 'unknown', 'data_retention_note' => null, 'privacy_summary' => null,
            'security_summary' => null, 'security_certifications' => [], 'compliance_certifications' => [],
            'data_residency' => [], 'sso_status' => 'unknown',
        ];

        $payload = $partial ? Arr::only($values, array_keys($defaults)) : array_merge($defaults, Arr::only($values, array_keys($defaults)));
        foreach (['deployment_modes','supported_languages','region_availability','security_certifications','compliance_certifications','data_residency'] as $key) {
            if (array_key_exists($key, $payload)) $payload[$key] = $this->splitList($payload[$key]);
        }
        foreach (['api_docs_url','license_name','repository_url','data_retention_note','privacy_summary','security_summary'] as $key) {
            if (array_key_exists($key, $payload)) $payload[$key] = trim((string) ($payload[$key] ?? '')) ?: null;
        }
        return $payload;
    }

    private function syncProfileEvidence(Tool $tool, ToolTechnicalProfile $profile, array $sources, ?ToolSource $primary): void
    {
        $groups = [
            'technical' => [
                'api_status' => [$profile->api_status, $sources['api'] ?? $primary, isset($sources['api'])],
                'api_docs_url' => [$profile->api_docs_url, $sources['api'] ?? $primary, isset($sources['api'])],
                'open_source_status' => [$profile->open_source_status, $sources['repository'] ?? $primary, isset($sources['repository'])],
                'license_name' => [$profile->license_name, $sources['repository'] ?? $primary, isset($sources['repository'])],
                'repository_url' => [$profile->repository_url, $sources['repository'] ?? $primary, isset($sources['repository'])],
                'self_hosting_status' => [$profile->self_hosting_status, $sources['deployment'] ?? $primary, isset($sources['deployment'])],
                'deployment_modes' => [$profile->deployment_modes, $sources['deployment'] ?? $primary, isset($sources['deployment'])],
                'commercial_use_status' => [$profile->commercial_use_status, $sources['terms'] ?? $primary, isset($sources['terms'])],
                'supported_languages' => [$profile->supported_languages, $sources['availability'] ?? $primary, isset($sources['availability'])],
                'region_availability' => [$profile->region_availability, $sources['availability'] ?? $primary, isset($sources['availability'])],
            ],
            'privacy' => [
                'data_training_policy' => [$profile->data_training_policy, $sources['privacy'] ?? $primary, isset($sources['privacy'])],
                'data_retention_note' => [$profile->data_retention_note, $sources['privacy'] ?? $primary, isset($sources['privacy'])],
                'privacy_summary' => [$profile->privacy_summary, $sources['privacy'] ?? $primary, isset($sources['privacy'])],
            ],
            'security' => [
                'security_summary' => [$profile->security_summary, $sources['security'] ?? $primary, isset($sources['security'])],
                'security_certifications' => [$profile->security_certifications, $sources['security'] ?? $primary, isset($sources['security'])],
                'compliance_certifications' => [$profile->compliance_certifications, $sources['security'] ?? $primary, isset($sources['security'])],
                'data_residency' => [$profile->data_residency, $sources['security'] ?? $primary, isset($sources['security'])],
                'sso_status' => [$profile->sso_status, $sources['security'] ?? $primary, isset($sources['security'])],
            ],
        ];

        foreach ($groups as $factType => $facts) {
            foreach ($facts as $key => [$value, $source, $allowVerified]) {
                if (! $this->meaningful($key, $value)) {
                    $tool->factEvidence()->where('fact_type', $factType)->where('fact_key', $key)->delete();
                    continue;
                }
                $this->sourceService->syncFactEvidence($tool, $source, $factType, $key, null, $allowVerified);
            }
        }
    }

    private function meaningful(string $key, mixed $value): bool
    {
        if (is_array($value)) return $value !== [];
        if ($value === null || trim((string) $value) === '') return false;
        if (in_array($key, ['api_status','open_source_status','self_hosting_status','commercial_use_status','data_training_policy','sso_status'], true)) {
            return ! in_array($value, [null, '', 'unknown'], true);
        }
        return true;
    }

    private function sourceColumn(string $key): string
    {
        return match ($key) {
            'api' => 'api_source_id',
            'repository' => 'repository_source_id',
            'deployment' => 'deployment_source_id',
            'terms' => 'terms_source_id',
            'availability' => 'availability_source_id',
            'privacy' => 'privacy_source_id',
            'security' => 'security_source_id',
        };
    }

    private function deploymentModesFromPlatforms(array $platforms): array
    {
        $map = [
            'Cloud' => 'Cloud', 'API' => 'API', 'Desktop' => 'Desktop', 'Windows' => 'Desktop', 'macOS' => 'Desktop', 'Linux' => 'Desktop',
            'Mobile App' => 'Mobile', 'iOS' => 'Mobile', 'Android' => 'Mobile', 'iPadOS' => 'Mobile',
            'Self Hosted' => 'Self-hosted', 'On-Premises' => 'On-Premises', 'Local' => 'Local', 'Embedded' => 'Embedded',
        ];
        return collect($platforms)->map(fn ($name) => $map[$name] ?? null)->filter()->unique()->values()->all();
    }
}
