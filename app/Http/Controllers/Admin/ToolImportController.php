<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Tool;
use App\Services\Imports\ImportPreviewStore;
use App\Services\Imports\SpreadsheetReader;
use App\Services\Taxonomy\TaxonomyNormalizer;
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

    public function preview(Request $request, SpreadsheetReader $reader, ImportPreviewStore $store, TaxonomyNormalizer $taxonomy)
    {
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
            if ($n['launch_date'] !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $n['launch_date'])) $errors[] = 'Launch date must use YYYY-MM-DD.';
            if ($n['status'] !== '' && !in_array($n['status'], ['draft', 'published', 'archived'], true)) $errors[] = 'Status must be draft, published or archived.';

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

    public function commit(Request $request, ImportPreviewStore $store, TaxonomyNormalizer $taxonomy)
    {
        $data = $request->validate([
            'token' => ['required', 'string', 'size:40'],
            'existing_action' => ['required', Rule::in(['skip', 'update'])],
        ]);

        $payload = $store->get($data['token'], $request->user()->id, 'tools');
        $created = $updated = $skipped = $invalid = 0;

        DB::transaction(function () use ($payload, $data, $taxonomy, &$created, &$updated, &$skipped, &$invalid) {
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
                    'pricing_models' => $row['pricing_models'],
                    'platforms' => $row['platforms'],
                    'capabilities' => $row['capabilities'],
                    'status' => $status,
                    'seo_title' => $row['seo_title'] ?: null,
                    'meta_description' => $row['meta_description'] ?: null,
                    'published_at' => $status === 'published' ? now() : null,
                ];

                $tool = Tool::create($values);
                $created++;

                $tool->featureTerms()->sync($taxonomy->featureIds($row['capabilities']));

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
            }
        });

        $store->forget($data['token']);
        return redirect()->route('admin.tools.index')->with('status', "Tool import complete: {$created} created, {$updated} updated, {$skipped} existing skipped, {$invalid} invalid skipped. Blank cells preserved on updates; explicit use cases/tags merged without deleting existing relations; capabilities synchronized.");
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
            'seo_title' => $text('seo_title'),
            'meta_description' => $text('meta_description'),
            'source_url' => $text('source_url'),
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
                'seo_title' => $provided('seo_title'),
                'meta_description' => $provided('meta_description'),
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
        if (!empty($p['pricing_models'])) $values['pricing_models'] = $row['pricing_models'];
        if (!empty($p['platforms'])) $values['platforms'] = $row['platforms'];
        if (!empty($p['capabilities'])) $values['capabilities'] = $row['capabilities'];
        if (!empty($p['seo_title'])) $values['seo_title'] = $row['seo_title'];
        if (!empty($p['meta_description'])) $values['meta_description'] = $row['meta_description'];

        if (!empty($p['status'])) {
            $values['status'] = $row['status'];
            $values['published_at'] = $row['status'] === 'published' ? ($existing->published_at ?: now()) : null;
        }

        if (!$existing->slug) $values['slug'] = $this->uniqueToolSlug($row['name'], $existing->id);

        return $values;
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
