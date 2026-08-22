<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\AiModel;
use App\Models\Tool;
use App\Models\PricingPlan;
use App\Models\BenchmarkResult;
use App\Services\Imports\CompanySpreadsheetReader;
use App\Services\Imports\SpreadsheetReader;
use App\Services\Taxonomy\TaxonomyNormalizer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Throwable;

class DataImportController extends Controller
{
    public function index()
    {
        $this->purgeExpiredPreviews();

        return view('data-import.index', [
            'companyCount' => Company::count(),
            'modelCount' => AiModel::count(),
            'toolCount' => Tool::count(),
            'pricingCount' => PricingPlan::count(),
            'benchmarkResultCount' => BenchmarkResult::count(),
        ]);
    }

    public function companyTemplate()
    {
        $path = storage_path('app/import-templates/ai-hub-235-companies-import.csv');
        abort_unless(File::exists($path), 404, 'Company import template not found.');

        return response()->download($path, 'ai-hub-235-companies-import.csv');
    }

    public function companyXlsxTemplate()
    {
        $path = storage_path('app/import-templates/ai-hub-235-companies-manual-data.xlsx');
        abort_unless(File::exists($path), 404, 'Company XLSX template not found.');

        return response()->download($path, 'ai-hub-235-companies-manual-data.xlsx');
    }

    public function previewCompanies(Request $request, CompanySpreadsheetReader $reader)
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt,xlsx', 'max:10240'],
        ]);

        try {
            $rows = $reader->read($request->file('file'));
        } catch (Throwable $e) {
            return back()->withErrors(['file' => $e->getMessage()]);
        }

        if (count($rows) > 2000) {
            return back()->withErrors(['file' => 'A single import is limited to 2,000 rows.']);
        }

        $seen = [];
        $preview = [];
        foreach ($rows as $row) {
            $normalized = $this->normalizeCompanyRow($row);
            $errors = $this->validateCompanyRow($normalized);
            $key = mb_strtolower($normalized['name']);

            if (isset($seen[$key])) {
                $errors[] = 'Duplicate company name inside this file.';
            }
            $seen[$key] = true;

            $existing = Company::query()
                ->where('name', $normalized['name'])
                ->when($normalized['website'], fn ($q, $website) => $q->orWhere('website', $website))
                ->first();

            $preview[] = $normalized + [
                'errors' => array_values(array_unique($errors)),
                'existing_id' => $existing?->id,
                'existing_name' => $existing?->name,
                'state' => $errors ? 'invalid' : ($existing ? 'existing' : 'ready'),
            ];
        }

        $token = Str::random(40);
        $directory = storage_path('app/import-previews');
        File::ensureDirectoryExists($directory);
        File::put($directory.'/'.$token.'.json', json_encode([
            'user_id' => $request->user()->id,
            'created_at' => now()->toIso8601String(),
            'rows' => $preview,
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

        $stats = [
            'total' => count($preview),
            'ready' => collect($preview)->where('state', 'ready')->count(),
            'existing' => collect($preview)->where('state', 'existing')->count(),
            'invalid' => collect($preview)->where('state', 'invalid')->count(),
        ];

        return view('data-import.companies-preview', compact('preview', 'stats', 'token'));
    }

    public function importCompanies(Request $request)
    {
        $data = $request->validate([
            'token' => ['required', 'string', 'size:40'],
            'existing_action' => ['required', Rule::in(['skip', 'update'])],
        ]);

        $path = storage_path('app/import-previews/'.$data['token'].'.json');
        abort_unless(File::exists($path), 419, 'Import preview expired. Upload the file again.');

        $payload = json_decode(File::get($path), true);
        abort_unless(($payload['user_id'] ?? null) === $request->user()->id, 403);

        $created = 0;
        $updated = 0;
        $skipped = 0;
        $invalid = 0;

        DB::transaction(function () use ($payload, $data, &$created, &$updated, &$skipped, &$invalid) {
            foreach ($payload['rows'] ?? [] as $row) {
                if (($row['state'] ?? '') === 'invalid' || ! empty($row['errors'])) {
                    $invalid++;
                    continue;
                }

                $companyData = [
                    'name' => $row['name'],
                    'website' => $row['website'] ?: null,
                    'founded_year' => $row['founded_year'] ?: null,
                    'status' => $row['status'],
                    'description' => $row['description'] ?: null,
                ];

                $existing = Company::query()
                    ->where('name', $row['name'])
                    ->when($row['website'], fn ($q, $website) => $q->orWhere('website', $website))
                    ->first();

                if ($existing) {
                    if ($data['existing_action'] === 'skip') {
                        $skipped++;
                        continue;
                    }

                    $companyData['slug'] = $existing->slug ?: $this->uniqueSlug($row['name'], $existing->id);
                    $existing->update($companyData);
                    $updated++;
                    continue;
                }

                $companyData['slug'] = $this->uniqueSlug($row['name']);
                Company::create($companyData);
                $created++;
            }
        });

        File::delete($path);

        return redirect()->route('admin.companies.index')->with(
            'status',
            "Bulk import complete: {$created} created, {$updated} updated, {$skipped} existing skipped, {$invalid} invalid skipped."
        );
    }

    public function modelTemplate()
    {
        $path = storage_path('app/import-templates/ai-hub-150-models-import.csv');
        abort_unless(File::exists($path), 404, 'Model import template not found.');
        return response()->download($path, 'ai-hub-150-models-import.csv');
    }

    public function previewModels(Request $request, SpreadsheetReader $reader, TaxonomyNormalizer $taxonomy)
    {
        $request->validate(['file' => ['required', 'file', 'mimes:csv,txt,xlsx', 'max:10240']]);

        try {
            $rows = $reader->read($request->file('file'));
        } catch (Throwable $e) {
            return back()->withErrors(['file' => $e->getMessage()]);
        }

        if (count($rows) > 2000) return back()->withErrors(['file' => 'A single import is limited to 2,000 rows.']);

        $companies = Company::query()->get(['id','name'])->keyBy(fn ($c) => mb_strtolower(trim($c->name)));
        $seen = [];
        $preview = [];

        foreach ($rows as $row) {
            $normalized = $this->normalizeModelRow($row);
            $normalized['capabilities'] = $taxonomy->canonicalFeatureNames($normalized['capabilities']);
            $errors = $this->validateModelRow($normalized);
            foreach ($taxonomy->unknownFeatureNames($normalized['capabilities']) as $unknown) {
                $errors[] = 'Unknown Taxonomy v2 capability: '.$unknown;
            }
            $company = $companies->get(mb_strtolower($normalized['company']));
            if (! $company) $errors[] = 'Missing company in database: '.$normalized['company'];

            $key = mb_strtolower($normalized['company'].'|'.$normalized['name'].'|'.$normalized['version']);
            if (isset($seen[$key])) $errors[] = 'Duplicate model inside this file.';
            $seen[$key] = true;

            $existing = $company ? AiModel::query()
                ->where('company_id', $company->id)
                ->where('name', $normalized['name'])
                ->when($normalized['version'], fn ($q, $version) => $q->where('version', $version))
                ->first() : null;

            $preview[] = $normalized + [
                'company_id' => $company?->id,
                'company_match' => $company?->name,
                'errors' => array_values(array_unique($errors)),
                'existing_id' => $existing?->id,
                'state' => $errors ? 'invalid' : ($existing ? 'existing' : 'ready'),
            ];
        }

        $token = Str::random(40);
        $directory = storage_path('app/import-previews');
        File::ensureDirectoryExists($directory);
        File::put($directory.'/'.$token.'.json', json_encode([
            'type' => 'models', 'user_id' => $request->user()->id,
            'created_at' => now()->toIso8601String(), 'rows' => $preview,
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

        $stats = [
            'total' => count($preview),
            'ready' => collect($preview)->where('state','ready')->count(),
            'existing' => collect($preview)->where('state','existing')->count(),
            'invalid' => collect($preview)->where('state','invalid')->count(),
        ];

        return view('data-import.models-preview', compact('preview','stats','token'));
    }

    public function importModels(Request $request, TaxonomyNormalizer $taxonomy)
    {
        $data = $request->validate([
            'token' => ['required','string','size:40'],
            'existing_action' => ['required', Rule::in(['skip','update'])],
        ]);

        $path = storage_path('app/import-previews/'.$data['token'].'.json');
        abort_unless(File::exists($path), 419, 'Import preview expired. Upload the file again.');
        $payload = json_decode(File::get($path), true);
        abort_unless(($payload['user_id'] ?? null) === $request->user()->id && ($payload['type'] ?? null) === 'models', 403);

        $created=$updated=$skipped=$invalid=0;

        DB::transaction(function () use ($payload,$data,$taxonomy,&$created,&$updated,&$skipped,&$invalid) {
            foreach ($payload['rows'] ?? [] as $row) {
                if (($row['state'] ?? '') === 'invalid' || !empty($row['errors']) || empty($row['company_id'])) { $invalid++; continue; }

                $modelData = [
                    'company_id' => $row['company_id'],
                    'name' => $row['name'],
                    'version' => $row['version'] ?: null,
                    'release_date' => $row['release_date'] ?: null,
                    'context_window' => $row['context_window'] ?: null,
                    'input_price_per_million' => $row['input_price_per_million'] === null ? null : $row['input_price_per_million'],
                    'output_price_per_million' => $row['output_price_per_million'] === null ? null : $row['output_price_per_million'],
                    'capabilities' => $row['capabilities'],
                    'capability_notes' => $row['capability_notes'] ?: null,
                    'benchmark_score' => $row['benchmark_score'],
                    'status' => $row['status'],
                ];

                $existing = AiModel::query()->where('company_id',$row['company_id'])->where('name',$row['name'])
                    ->when($row['version'], fn ($q,$v) => $q->where('version',$v))->first();

                if ($existing) {
                    if ($data['existing_action']==='skip') { $skipped++; continue; }
                    $modelData['slug'] = $existing->slug ?: $this->uniqueModelSlug($row['name'],$row['version'],$existing->id);
                    $existing->update($modelData);
                    $model = $existing;
                    $updated++;
                } else {
                    $modelData['slug'] = $this->uniqueModelSlug($row['name'],$row['version']);
                    $model = AiModel::create($modelData);
                    $created++;
                }

                $model->featureTerms()->sync($taxonomy->featureIds($row['capabilities']));
                $model->useCaseTerms()->sync($taxonomy->inferredUseCaseIds($row['capabilities']));
            }
        });

        File::delete($path);
        return redirect()->route('admin.models.index')->with('status',
            "Model import complete: {$created} created, {$updated} updated, {$skipped} existing skipped, {$invalid} invalid skipped.");
    }

    private function normalizeModelRow(array $row): array
    {
        $capabilities = preg_split('/[|,;]+/', (string)($row['capabilities'] ?? ''), -1, PREG_SPLIT_NO_EMPTY);
        $capabilities = array_values(array_unique(array_map('trim', $capabilities ?: [])));
        $number = fn ($value) => trim((string)$value) === '' ? null : (float)$value;

        return [
            'row_number' => (int)($row['row_number'] ?? 0),
            'company' => trim((string)($row['company'] ?? '')),
            'name' => trim((string)($row['name'] ?? '')),
            'version' => trim((string)($row['version'] ?? '')),
            'release_date' => trim((string)($row['release_date'] ?? '')),
            'context_window' => trim((string)($row['context_window'] ?? '')),
            'input_price_per_million' => $number($row['input_price_per_million'] ?? ''),
            'output_price_per_million' => $number($row['output_price_per_million'] ?? ''),
            'capabilities' => $capabilities,
            'capability_notes' => trim((string)($row['capability_notes'] ?? '')),
            'benchmark_score' => $number($row['benchmark_score'] ?? ''),
            'status' => strtolower(trim((string)($row['status'] ?? 'active'))) ?: 'active',
            'source_url' => trim((string)($row['source_url'] ?? '')),
        ];
    }

    private function validateModelRow(array $row): array
    {
        $errors=[];
        if ($row['company']==='') $errors[]='Company is required.';
        if ($row['name']==='') $errors[]='Model name is required.';
        elseif (mb_strlen($row['name'])>255) $errors[]='Model name is too long.';
        if ($row['version']!=='' && mb_strlen($row['version'])>50) $errors[]='Version is too long.';
        if ($row['release_date']!=='' && !preg_match('/^\d{4}-\d{2}-\d{2}$/',$row['release_date'])) $errors[]='Release date must use YYYY-MM-DD.';
        if ($row['context_window']!=='' && mb_strlen($row['context_window'])>50) $errors[]='Context window is too long.';
        foreach (['input_price_per_million','output_price_per_million'] as $field) if ($row[$field]!==null && $row[$field]<0) $errors[]='Pricing cannot be negative.';
        if ($row['benchmark_score'] !== null && ($row['benchmark_score'] < 0 || $row['benchmark_score'] > 100)) $errors[]='Benchmark score must be between 0 and 100.';
        if (!in_array($row['status'],['active','deprecated','preview'],true)) $errors[]='Status must be active, deprecated or preview.';
        return $errors;
    }

    private function uniqueModelSlug(string $name, ?string $version=null, ?int $ignoreId=null): string
    {
        $base=Str::slug(trim($name.' '.($version ?? ''))) ?: 'ai-model';
        $slug=$base; $counter=2;
        while (AiModel::where('slug',$slug)->when($ignoreId,fn($q)=>$q->where('id','!=',$ignoreId))->exists()) $slug=$base.'-'.$counter++;
        return $slug;
    }

    private function normalizeCompanyRow(array $row): array
    {
        $status = strtolower(trim((string) ($row['status'] ?? 'active')));
        $status = match ($status) {
            'operating', 'live', 'enabled' => 'active',
            'closed', 'defunct' => 'inactive',
            default => $status,
        };

        $year = trim((string) ($row['founded_year'] ?? ''));

        return [
            'row_number' => (int) ($row['row_number'] ?? 0),
            'name' => trim((string) ($row['name'] ?? '')),
            'website' => trim((string) ($row['website'] ?? '')),
            'founded_year' => $year === '' ? null : (int) $year,
            'status' => $status ?: 'active',
            'description' => trim((string) ($row['description'] ?? '')),
            'source_url' => trim((string) ($row['source_url'] ?? '')),
            'category' => trim((string) ($row['category'] ?? '')),
        ];
    }

    private function validateCompanyRow(array $row): array
    {
        $errors = [];
        if ($row['name'] === '') {
            $errors[] = 'Company name is required.';
        } elseif (mb_strlen($row['name']) > 255) {
            $errors[] = 'Company name is too long.';
        }

        if ($row['website'] !== '' && ! filter_var($row['website'], FILTER_VALIDATE_URL)) {
            $errors[] = 'Website is not a valid URL.';
        }

        if ($row['founded_year'] !== null && ($row['founded_year'] < 1800 || $row['founded_year'] > ((int) date('Y') + 1))) {
            $errors[] = 'Founded year is outside the allowed range.';
        }

        if (! in_array($row['status'], ['active', 'acquired', 'inactive'], true)) {
            $errors[] = 'Status must be active, acquired or inactive.';
        }

        return $errors;
    }

    private function uniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $base = Str::slug($name) ?: 'company';
        $slug = $base;
        $counter = 2;

        while (Company::where('slug', $slug)->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))->exists()) {
            $slug = $base.'-'.$counter++;
        }

        return $slug;
    }
    private function purgeExpiredPreviews(): void
    {
        $directory = storage_path('app/import-previews');
        if (! File::isDirectory($directory)) {
            return;
        }

        foreach (File::files($directory) as $file) {
            if ($file->getMTime() < now()->subHours(2)->timestamp) {
                File::delete($file->getPathname());
            }
        }
    }

}
