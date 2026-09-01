<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Tool;
use App\Models\ToolSource;
use App\Models\ToolTechnicalProfile;
use App\Services\Imports\ImportPreviewStore;
use App\Services\Imports\SpreadsheetReader;
use App\Services\Taxonomy\TaxonomyNormalizer;
use App\Services\Tools\PlatformNormalizer;
use App\Services\Tools\ToolSourceService;
use App\Services\Tools\ToolProfileIntelligenceService;
use App\Services\Tools\ToolAdvancedIntelligenceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Throwable;

class ToolImportController extends Controller
{
    public function template()
    {
        $path = storage_path('app/import-templates/ai-hub-tools-import.csv');
        abort_unless(File::exists($path), 404, 'Tool import template not found.');
        return response()->download($path, 'ai-hub-tools-import.csv');
    }

    public function preview(
        Request $request,
        SpreadsheetReader $reader,
        ImportPreviewStore $store,
        TaxonomyNormalizer $taxonomy,
        PlatformNormalizer $platforms,
    ) {
        $request->validate(['file' => ['required', 'file', 'mimes:csv,txt,xlsx', 'max:10240']]);
        try {
            $rows = $reader->read($request->file('file'));
        } catch (Throwable $e) {
            return back()->withErrors(['file' => $e->getMessage()]);
        }

        if (count($rows) > 3000) {
            return back()->withErrors(['file' => 'A single tool import is limited to 3,000 rows.']);
        }

        $companies = Company::get(['id', 'name'])->keyBy(fn ($c) => mb_strtolower(trim($c->name)));
        $seen = [];
        $preview = [];

        foreach ($rows as $row) {
            $n = $this->normalize($row);
            $errors = [];

            if ($n['name'] === '') $errors[] = 'Tool name is required.';
            if ($n['website'] !== '' && !filter_var($n['website'], FILTER_VALIDATE_URL)) $errors[] = 'Website is not a valid URL.';
            if ($n['source_url'] !== '' && !filter_var($n['source_url'], FILTER_VALIDATE_URL)) $errors[] = 'Source URL is not a valid URL.';
            if ($n['launch_date'] !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $n['launch_date'])) $errors[] = 'Launch date must use YYYY-MM-DD.';
            if ($n['source_verified_at'] !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $n['source_verified_at'])) $errors[] = 'Source verified date must use YYYY-MM-DD.';
            if ($n['lifecycle_verified_at'] !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $n['lifecycle_verified_at'])) $errors[] = 'Lifecycle verified date must use YYYY-MM-DD.';
            if ($n['status'] !== '' && !in_array($n['status'], ['draft', 'published', 'archived'], true)) $errors[] = 'Status must be draft, published or archived.';
            if ($n['product_status'] !== '' && !array_key_exists($n['product_status'], Tool::PRODUCT_STATUSES)) $errors[] = 'Unknown product lifecycle status: '.$n['product_status'];
            if ($n['lifecycle_source_url'] !== '' && !filter_var($n['lifecycle_source_url'], FILTER_VALIDATE_URL)) $errors[] = 'Lifecycle source URL is not a valid URL.';
            if ($n['lifecycle_verification_status'] !== '' && !in_array($n['lifecycle_verification_status'], ['pending','verified'], true)) $errors[] = 'Lifecycle verification status must be pending or verified.';
            if ($n['source_verification_status'] !== '' && !in_array($n['source_verification_status'], ['pending', 'verified'], true)) $errors[] = 'Source verification status must be pending or verified.';
            if ($n['source_type'] !== '' && !in_array($n['source_type'], ToolSource::TYPES, true)) $errors[] = 'Unknown source type: '.$n['source_type'];

            foreach (['api_source_url','repository_url','repository_source_url','deployment_source_url','terms_source_url','availability_source_url','privacy_source_url','security_source_url','integration_source_url'] as $urlField) {
                if ($n[$urlField] !== '' && !filter_var($n[$urlField], FILTER_VALIDATE_URL)) $errors[] = Str::headline($urlField).' is not a valid URL.';
            }
            if ($n['api_docs_url'] !== '' && !filter_var($n['api_docs_url'], FILTER_VALIDATE_URL)) $errors[] = 'API docs URL is not a valid URL.';
            if ($n['api_status'] !== '' && !array_key_exists($n['api_status'], ToolTechnicalProfile::API_STATUSES)) $errors[] = 'Unknown API status: '.$n['api_status'];
            if ($n['open_source_status'] !== '' && !array_key_exists($n['open_source_status'], ToolTechnicalProfile::OPEN_SOURCE_STATUSES)) $errors[] = 'Unknown open-source status: '.$n['open_source_status'];
            if ($n['self_hosting_status'] !== '' && !array_key_exists($n['self_hosting_status'], ToolTechnicalProfile::SELF_HOSTING_STATUSES)) $errors[] = 'Unknown self-hosting status: '.$n['self_hosting_status'];
            if ($n['commercial_use_status'] !== '' && !array_key_exists($n['commercial_use_status'], ToolTechnicalProfile::COMMERCIAL_USE_STATUSES)) $errors[] = 'Unknown commercial-use status: '.$n['commercial_use_status'];
            if ($n['data_training_policy'] !== '' && !array_key_exists($n['data_training_policy'], ToolTechnicalProfile::TRAINING_POLICIES)) $errors[] = 'Unknown data-training policy: '.$n['data_training_policy'];
            if ($n['sso_status'] !== '' && !array_key_exists($n['sso_status'], ToolTechnicalProfile::SSO_STATUSES)) $errors[] = 'Unknown SSO status: '.$n['sso_status'];
            foreach ($n['deployment_modes'] as $mode) if (!in_array($mode, ToolTechnicalProfile::DEPLOYMENT_MODES, true)) $errors[] = 'Unknown deployment mode: '.$mode;
            foreach (['api_source_verification_status','repository_source_verification_status','deployment_source_verification_status','terms_source_verification_status','availability_source_verification_status','privacy_source_verification_status','security_source_verification_status','integration_source_verification_status'] as $statusField) {
                if ($n[$statusField] !== '' && !in_array($n[$statusField], ['pending','verified'], true)) $errors[] = Str::headline($statusField).' must be pending or verified.';
            }

            $company = $n['company'] !== '' ? $companies->get(mb_strtolower($n['company'])) : null;
            if ($n['company'] !== '' && !$company) $errors[] = 'Missing company in database: '.$n['company'];

            $category = $taxonomy->productCategoryByName($n['category']);
            if ($n['category'] !== '' && !$category) $errors[] = 'Unknown product category: '.$n['category'].'. Use a Taxonomy v2 category.';

            $subcategory = $taxonomy->subcategoryByName($n['subcategory'], $category?->id);
            if (!$subcategory && $n['subcategory'] === '' && $category) $subcategory = $taxonomy->defaultSubcategoryForCategory($n['category'], $category->id);
            if (!$category && $subcategory) $category = $subcategory->category;
            if ($n['subcategory'] !== '' && !$subcategory) $errors[] = 'Unknown subcategory for selected category: '.$n['subcategory'];

            $n['capabilities'] = $taxonomy->canonicalFeatureNames($n['capabilities']);
            foreach ($taxonomy->unknownFeatureNames($n['capabilities']) as $unknown) {
                $errors[] = 'Unknown Taxonomy v2 capability: '.$unknown;
            }

            $n['use_cases'] = $taxonomy->canonicalUseCaseNames($n['use_cases']);
            foreach ($taxonomy->unknownUseCaseNames($n['use_cases']) as $unknown) {
                $errors[] = 'Unknown Taxonomy v2 use case: '.$unknown;
            }

            $n['tags'] = $taxonomy->canonicalTagNames($n['tags']);
            foreach ($taxonomy->unknownTagNames($n['tags']) as $unknown) {
                $errors[] = 'Unknown Taxonomy v2 tag: '.$unknown;
            }

            $platformResult = $platforms->normalize($n['platforms']);
            $n['platforms'] = $platformResult['canonical'];
            foreach ($platformResult['unknown'] as $unknown) {
                $errors[] = 'Unknown platform: '.$unknown.'. Add/map it to the canonical platform taxonomy before importing.';
            }

            $key = mb_strtolower(($n['company'] ?: 'independent').'|'.$n['name']);
            if (isset($seen[$key])) $errors[] = 'Duplicate tool inside this file.';
            $seen[$key] = true;

            $existing = Tool::query()
                ->where('name', $n['name'])
                ->when($company, fn ($q) => $q->where('company_id', $company->id))
                ->first();

            $explicitUseCaseIds = $n['provided']['use_cases'] ? $taxonomy->useCaseIds($n['use_cases']) : [];
            $explicitTagIds = $n['provided']['tags'] ? $taxonomy->tagIds($n['tags']) : [];

            $preview[] = $n + [
                'company_id' => $company?->id,
                'company_match' => $company?->name,
                'category_id' => $category?->id,
                'category_match' => $category?->name,
                'subcategory_id' => $subcategory?->id,
                'subcategory_match' => $subcategory?->name,
                'platform_ids' => $platforms->idsForNames($n['platforms']),
                'explicit_use_case_ids' => $explicitUseCaseIds,
                'explicit_tag_ids' => $explicitTagIds,
                'default_tag_ids' => $taxonomy->defaultTagIdsForCategory($n['category']),
                'default_use_case_ids' => $taxonomy->inferredUseCaseIds($n['capabilities'], $n['category']),
                'existing_id' => $existing?->id,
                'errors' => array_values(array_unique($errors)),
                'state' => $errors ? 'invalid' : ($existing ? 'existing' : 'ready'),
            ];
        }

        $token = $store->put('tools', $request->user()->id, $preview);
        $stats = $this->stats($preview);
        return view('data-import.tools-preview', compact('preview', 'stats', 'token'));
    }

    public function commit(
        Request $request,
        ImportPreviewStore $store,
        TaxonomyNormalizer $taxonomy,
        ToolSourceService $sourceService,
        ToolProfileIntelligenceService $profileIntelligence,
        ToolAdvancedIntelligenceService $advancedIntelligence,
    ) {
        $data = $request->validate([
            'token' => ['required', 'string', 'size:40'],
            'existing_action' => ['required', Rule::in(['skip', 'update'])],
        ]);

        $payload = $store->get($data['token'], $request->user()->id, 'tools');
        $created = $updated = $skipped = $invalid = $sourcesSaved = 0;

        DB::transaction(function () use ($payload, $data, $taxonomy, $sourceService, $profileIntelligence, $advancedIntelligence, $request, &$created, &$updated, &$skipped, &$invalid, &$sourcesSaved) {
            foreach ($payload['rows'] ?? [] as $row) {
                if (($row['state'] ?? '') === 'invalid' || !empty($row['errors'])) {
                    $invalid++;
                    continue;
                }

                $existing = Tool::query()
                    ->where('name', $row['name'])
                    ->when($row['company_id'], fn ($q, $id) => $q->where('company_id', $id))
                    ->first();

                if ($existing) {
                    if ($data['existing_action'] === 'skip') {
                        $skipped++;
                        continue;
                    }

                    $values = $this->existingUpdateValues($row, $existing);
                    if ($values) $existing->update($values);
                    $tool = $existing;
                    $updated++;

                    // Blank taxonomy columns preserve existing pivot relations.
                    if (!empty($row['provided']['capabilities'])) {
                        $tool->featureTerms()->sync($taxonomy->featureIds($row['capabilities']));
                    }
                    if (!empty($row['provided']['use_cases'])) {
                        $tool->useCaseTerms()->syncWithoutDetaching($row['explicit_use_case_ids'] ?? $taxonomy->useCaseIds($row['use_cases']));
                    }
                    if (!empty($row['provided']['tags'])) {
                        $tool->tagTerms()->syncWithoutDetaching($row['explicit_tag_ids'] ?? $taxonomy->tagIds($row['tags']));
                        $tool->updateQuietly(['tags' => $tool->tagTerms()->orderBy('name')->pluck('name')->all()]);
                    }
                    if (!empty($row['provided']['platforms'])) {
                        $tool->platformTerms()->sync($row['platform_ids'] ?? []);
                        $tool->updateQuietly(['platforms' => $row['platforms']]);
                    }

                    if ($this->persistSource($tool, $row, $sourceService, $request->user()?->id)) $sourcesSaved++;
                    $this->persistLifecycle($tool, $row, $sourceService, $request->user()?->id);
                    $profileIntelligence->bootstrapEvidenceLinks($tool, true);
                    $this->persistAdvanced($tool, $row, $advancedIntelligence, $request->user()?->id);
                    continue;
                }

                $status = $row['status'] !== '' ? $row['status'] : 'published';
                $values = [
                    'company_id' => $row['company_id'] ?: null,
                    'category_id' => $row['category_id'] ?: null,
                    'subcategory_id' => $row['subcategory_id'] ?: null,
                    'subcategory' => $row['subcategory_match'] ?: null,
                    'name' => $row['name'],
                    'slug' => $this->uniqueToolSlug($row['name']),
                    'website' => $row['website'] ?: null,
                    'launch_date' => $row['launch_date'] ?: null,
                    'short_description' => $row['short_description'] ?: null,
                    'description' => $row['description'] ?: null,
                    'pricing_models' => $this->normalizeLegacyPricing($row['pricing_models']),
                    'platforms' => $row['platforms'],
                    'capabilities' => $row['capabilities'],
                    'status' => $status,
                    'product_status' => $row['product_status'] ?: 'unknown',
                    'product_status_note' => $row['product_status_note'] ?: null,
                    'seo_title' => $row['seo_title'] ?: null,
                    'meta_description' => $row['meta_description'] ?: null,
                    'published_at' => $status === 'published' ? now() : null,
                ];

                $tool = Tool::create($values);
                $created++;

                $tool->featureTerms()->sync($taxonomy->featureIds($row['capabilities']));
                $tool->platformTerms()->sync($row['platform_ids'] ?? []);

                $useCaseIds = !empty($row['provided']['use_cases'])
                    ? ($row['explicit_use_case_ids'] ?? $taxonomy->useCaseIds($row['use_cases']))
                    : ($row['default_use_case_ids'] ?? $taxonomy->inferredUseCaseIds($row['capabilities'], $row['category'] ?? null));
                $tool->useCaseTerms()->sync($useCaseIds);

                $tagIds = !empty($row['provided']['tags'])
                    ? ($row['explicit_tag_ids'] ?? $taxonomy->tagIds($row['tags']))
                    : ($row['default_tag_ids'] ?? []);
                if ($tagIds) {
                    $tool->tagTerms()->sync($tagIds);
                    $tool->updateQuietly(['tags' => $tool->tagTerms()->orderBy('name')->pluck('name')->all()]);
                }

                if ($this->persistSource($tool, $row, $sourceService, $request->user()?->id)) $sourcesSaved++;
                $this->persistLifecycle($tool, $row, $sourceService, $request->user()?->id);
                $profileIntelligence->bootstrapEvidenceLinks($tool, true);
                $this->persistAdvanced($tool, $row, $advancedIntelligence, $request->user()?->id);
            }
        });

        $store->forget($data['token']);
        return redirect()->route('admin.tools.index')->with('status', "Tool import complete: {$created} created, {$updated} updated, {$skipped} existing skipped, {$invalid} invalid skipped, {$sourcesSaved} source rows preserved. Canonical platforms were synchronized and source_url evidence is now persisted.");
    }

    private function normalize(array $r): array
    {
        $split = fn ($v) => array_values(array_unique(array_filter(array_map('trim', preg_split('/[|;,]+/', (string) $v) ?: []))));
        $text = fn ($key) => trim((string) ($r[$key] ?? ''));
        $provided = fn ($key) => array_key_exists($key, $r) && trim((string) ($r[$key] ?? '')) !== '';

        return [
            'row_number' => (int) ($r['row_number'] ?? 0),
            'company' => $text('company'),
            'name' => $text('name'),
            'website' => $text('website'),
            'category' => $text('category'),
            'subcategory' => $text('subcategory'),
            'launch_date' => $text('launch_date'),
            'short_description' => $text('short_description'),
            'description' => $text('description'),
            'pricing_models' => $split($r['pricing_models'] ?? ''),
            'platforms' => $split($r['platforms'] ?? ''),
            'capabilities' => $split($r['capabilities'] ?? ''),
            'use_cases' => $split($r['use_cases'] ?? ''),
            'tags' => $split($r['tags'] ?? ''),
            'status' => strtolower($text('status')),
            'product_status' => strtolower($text('product_status')),
            'product_status_note' => $text('product_status_note'),
            'lifecycle_source_url' => $text('lifecycle_source_url'),
            'lifecycle_verification_status' => strtolower($text('lifecycle_verification_status')),
            'lifecycle_verified_at' => $text('lifecycle_verified_at'),
            'seo_title' => $text('seo_title'),
            'meta_description' => $text('meta_description'),
            'source_url' => $text('source_url'),
            'source_name' => $text('source_name'),
            'source_type' => strtolower($text('source_type')),
            'source_verification_status' => strtolower($text('source_verification_status')),
            'source_verified_at' => $text('source_verified_at'),
            'api_status' => strtolower($text('api_status')),
            'api_docs_url' => $text('api_docs_url'),
            'api_source_url' => $text('api_source_url'),
            'api_source_verification_status' => strtolower($text('api_source_verification_status')),
            'open_source_status' => strtolower($text('open_source_status')),
            'license_name' => $text('license_name'),
            'repository_url' => $text('repository_url'),
            'repository_source_url' => $text('repository_source_url'),
            'repository_source_verification_status' => strtolower($text('repository_source_verification_status')),
            'self_hosting_status' => strtolower($text('self_hosting_status')),
            'deployment_source_url' => $text('deployment_source_url'),
            'deployment_source_verification_status' => strtolower($text('deployment_source_verification_status')),
            'deployment_modes' => $split($r['deployment_modes'] ?? ''),
            'commercial_use_status' => strtolower($text('commercial_use_status')),
            'terms_source_url' => $text('terms_source_url'),
            'terms_source_verification_status' => strtolower($text('terms_source_verification_status')),
            'supported_languages' => $split($r['supported_languages'] ?? ''),
            'region_availability' => $split($r['region_availability'] ?? ''),
            'availability_source_url' => $text('availability_source_url'),
            'availability_source_verification_status' => strtolower($text('availability_source_verification_status')),
            'data_training_policy' => strtolower($text('data_training_policy')),
            'data_retention_note' => $text('data_retention_note'),
            'privacy_summary' => $text('privacy_summary'),
            'privacy_source_url' => $text('privacy_source_url'),
            'privacy_source_verification_status' => strtolower($text('privacy_source_verification_status')),
            'security_summary' => $text('security_summary'),
            'security_certifications' => $split($r['security_certifications'] ?? ''),
            'compliance_certifications' => $split($r['compliance_certifications'] ?? ''),
            'data_residency' => $split($r['data_residency'] ?? ''),
            'sso_status' => strtolower($text('sso_status')),
            'security_source_url' => $text('security_source_url'),
            'security_source_verification_status' => strtolower($text('security_source_verification_status')),
            'integrations' => $split($r['integrations'] ?? ''),
            'integration_source_url' => $text('integration_source_url'),
            'integration_source_verification_status' => strtolower($text('integration_source_verification_status')),
            'provided' => [
                'company' => $provided('company'),
                'website' => $provided('website'),
                'category' => $provided('category'),
                'subcategory' => $provided('subcategory'),
                'launch_date' => $provided('launch_date'),
                'short_description' => $provided('short_description'),
                'description' => $provided('description'),
                'pricing_models' => $provided('pricing_models'),
                'platforms' => $provided('platforms'),
                'capabilities' => $provided('capabilities'),
                'use_cases' => $provided('use_cases'),
                'tags' => $provided('tags'),
                'status' => $provided('status'),
                'product_status' => $provided('product_status'),
                'product_status_note' => $provided('product_status_note'),
                'lifecycle_source_url' => $provided('lifecycle_source_url'),
                'seo_title' => $provided('seo_title'),
                'meta_description' => $provided('meta_description'),
                'source_url' => $provided('source_url'),
                'api_status' => $provided('api_status'),
                'api_docs_url' => $provided('api_docs_url'),
                'api_source_url' => $provided('api_source_url'),
                'open_source_status' => $provided('open_source_status'),
                'license_name' => $provided('license_name'),
                'repository_url' => $provided('repository_url'),
                'repository_source_url' => $provided('repository_source_url'),
                'self_hosting_status' => $provided('self_hosting_status'),
                'deployment_source_url' => $provided('deployment_source_url'),
                'deployment_modes' => $provided('deployment_modes'),
                'commercial_use_status' => $provided('commercial_use_status'),
                'terms_source_url' => $provided('terms_source_url'),
                'supported_languages' => $provided('supported_languages'),
                'region_availability' => $provided('region_availability'),
                'availability_source_url' => $provided('availability_source_url'),
                'data_training_policy' => $provided('data_training_policy'),
                'data_retention_note' => $provided('data_retention_note'),
                'privacy_summary' => $provided('privacy_summary'),
                'privacy_source_url' => $provided('privacy_source_url'),
                'security_summary' => $provided('security_summary'),
                'security_certifications' => $provided('security_certifications'),
                'compliance_certifications' => $provided('compliance_certifications'),
                'data_residency' => $provided('data_residency'),
                'sso_status' => $provided('sso_status'),
                'security_source_url' => $provided('security_source_url'),
                'integrations' => $provided('integrations'),
                'integration_source_url' => $provided('integration_source_url'),
            ],
        ];
    }

    private function existingUpdateValues(array $row, Tool $existing): array
    {
        $p = $row['provided'] ?? [];
        $values = [];

        if (!empty($p['company'])) $values['company_id'] = $row['company_id'] ?: null;
        if (!empty($p['category'])) $values['category_id'] = $row['category_id'] ?: null;
        if (!empty($p['subcategory'])) {
            $values['subcategory_id'] = $row['subcategory_id'] ?: null;
            $values['subcategory'] = $row['subcategory_match'] ?: null;
        }
        if (!empty($p['website'])) $values['website'] = $row['website'];
        if (!empty($p['launch_date'])) $values['launch_date'] = $row['launch_date'];
        if (!empty($p['short_description'])) $values['short_description'] = $row['short_description'];
        if (!empty($p['description'])) $values['description'] = $row['description'];
        if (!empty($p['pricing_models']) && ! $existing->pricingPlans()->exists()) {
            $values['pricing_models'] = $this->normalizeLegacyPricing($row['pricing_models']);
        }
        if (!empty($p['platforms'])) $values['platforms'] = $row['platforms'];
        if (!empty($p['capabilities'])) $values['capabilities'] = $row['capabilities'];
        if (!empty($p['seo_title'])) $values['seo_title'] = $row['seo_title'];
        if (!empty($p['meta_description'])) $values['meta_description'] = $row['meta_description'];

        if (!empty($p['status'])) {
            $values['status'] = $row['status'];
            $values['published_at'] = $row['status'] === 'published' ? ($existing->published_at ?: now()) : null;
        }

        if (!empty($p['product_status'])) $values['product_status'] = $row['product_status'];
        if (!empty($p['product_status_note'])) $values['product_status_note'] = $row['product_status_note'];

        if (!$existing->slug) $values['slug'] = $this->uniqueToolSlug($row['name'], $existing->id);

        return $values;
    }

    private function persistSource(Tool $tool, array $row, ToolSourceService $sourceService, ?int $userId): bool
    {
        $url = trim((string) ($row['source_url'] ?? ''));
        if ($url === '') return false;

        $type = trim((string) ($row['source_type'] ?? '')) ?: $sourceService->inferSourceType($url);
        $status = trim((string) ($row['source_verification_status'] ?? '')) ?: 'pending';
        $verifiedAt = $status === 'verified' ? (($row['source_verified_at'] ?? '') ?: now()) : null;

        $factType = match ($type) {
            'official_pricing' => 'pricing',
            'documentation', 'api_docs' => 'capability_source',
            default => 'identity',
        };

        $sourceService->upsert(
            tool: $tool,
            url: $url,
            sourceType: $type,
            verificationStatus: $status,
            sourceName: ($row['source_name'] ?? '') ?: null,
            verifiedAt: $verifiedAt,
            verifiedBy: $status === 'verified' ? $userId : null,
            factType: $factType,
            factKey: $type,
            primary: $type === 'official_product',
        );

        return true;
    }

    private function persistLifecycle(Tool $tool, array $row, ToolSourceService $sourceService, ?int $userId): void
    {
        if (empty($row['provided']['product_status']) && empty($row['provided']['lifecycle_source_url'])) return;
        if (($tool->product_status ?: 'unknown') === 'unknown') {
            $tool->updateQuietly(['product_status_source_id' => null, 'product_status_verified_at' => null]);
            return;
        }

        $url = trim((string) ($row['lifecycle_source_url'] ?? ''));
        if ($url === '') {
            // A lifecycle label without evidence remains explicitly unverified.
            $tool->updateQuietly(['product_status_source_id' => null, 'product_status_verified_at' => null]);
            return;
        }

        $status = ($row['lifecycle_verification_status'] ?? 'pending') === 'verified' ? 'verified' : 'pending';
        $verifiedAt = $status === 'verified' ? (($row['lifecycle_verified_at'] ?? '') ?: now()) : null;
        $source = $sourceService->upsert(
            tool: $tool,
            url: $url,
            sourceType: $sourceService->inferSourceType($url),
            verificationStatus: $status,
            sourceName: $tool->name.' lifecycle source',
            verifiedAt: $verifiedAt,
            verifiedBy: $status === 'verified' ? $userId : null,
            factType: 'lifecycle',
            factKey: 'product_status',
            primary: false,
        );

        $tool->updateQuietly([
            'product_status_source_id' => $source?->id,
            'product_status_verified_at' => $status === 'verified' && $source ? ($source->verified_at ?: now()) : null,
        ]);
    }

    private function persistAdvanced(Tool $tool, array $row, ToolAdvancedIntelligenceService $advanced, ?int $userId): void
    {
        $provided = $row['provided'] ?? [];
        $fieldMap = [
            'api_status' => 'api_status', 'api_docs_url' => 'api_docs_url',
            'open_source_status' => 'open_source_status', 'license_name' => 'license_name', 'repository_url' => 'repository_url',
            'self_hosting_status' => 'self_hosting_status', 'deployment_modes' => 'deployment_modes', 'commercial_use_status' => 'commercial_use_status',
            'supported_languages' => 'supported_languages', 'region_availability' => 'region_availability',
            'data_training_policy' => 'data_training_policy', 'data_retention_note' => 'data_retention_note', 'privacy_summary' => 'privacy_summary',
            'security_summary' => 'security_summary', 'security_certifications' => 'security_certifications',
            'compliance_certifications' => 'compliance_certifications', 'data_residency' => 'data_residency', 'sso_status' => 'sso_status',
        ];
        $values = [];
        foreach ($fieldMap as $providedKey => $valueKey) {
            if (!empty($provided[$providedKey])) $values[$valueKey] = $row[$valueKey];
        }

        $sources = [];
        foreach (['api','repository','deployment','terms','availability','privacy','security'] as $group) {
            $urlKey = $group.'_source_url';
            if (!empty($provided[$urlKey])) {
                $sources[$group] = [
                    'url' => $row[$urlKey],
                    'status' => $row[$group.'_source_verification_status'] ?: 'pending',
                ];
            }
        }
        if ($values || $sources) $advanced->syncTechnicalProfile($tool, $values, $sources, $userId, true);

        if (!empty($provided['integrations']) || !empty($provided['integration_source_url'])) {
            $names = !empty($provided['integrations']) ? $row['integrations'] : $tool->integrationTerms()->pluck('name')->all();
            $advanced->syncIntegrations(
                $tool,
                $names,
                $row['integration_source_url'] ?: null,
                $row['integration_source_verification_status'] ?: 'pending',
                $userId,
                !empty($provided['integrations']),
            );
        }
    }

    private function normalizeLegacyPricing(array $labels): array
    {
        $out = [];
        foreach ($labels as $label) {
            $key = mb_strtolower(trim((string) $label));
            if (in_array($key, ['free', 'free tier'], true)) $out[] = 'Free';
            elseif ($key === 'freemium') { $out[] = 'Free'; $out[] = 'Paid'; }
            elseif (in_array($key, ['paid', 'subscription'], true)) $out[] = 'Paid';
            elseif (in_array($key, ['usage', 'usage-based', 'pay as you go', 'pay-as-you-go'], true)) $out[] = 'Usage-based';
            elseif (in_array($key, ['enterprise', 'custom'], true)) $out[] = 'Enterprise';
        }
        return array_values(array_unique($out));
    }

    private function stats(array $rows): array
    {
        return [
            'total' => count($rows),
            'ready' => collect($rows)->where('state', 'ready')->count(),
            'existing' => collect($rows)->where('state', 'existing')->count(),
            'invalid' => collect($rows)->where('state', 'invalid')->count(),
        ];
    }

    private function uniqueToolSlug(string $name, ?int $ignore = null): string
    {
        $base = Str::slug($name) ?: 'ai-tool';
        $slug = $base;
        $i = 2;
        while (Tool::where('slug', $slug)->when($ignore, fn ($q) => $q->where('id', '!=', $ignore))->exists()) {
            $slug = $base.'-'.$i++;
        }
        return $slug;
    }
}
