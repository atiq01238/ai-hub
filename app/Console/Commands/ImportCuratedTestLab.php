<?php

namespace App\Console\Commands;

use App\Models\AiModel;
use App\Models\AiTest;
use App\Models\AiTestResult;
use App\Models\AiTestRun;
use App\Models\Feature;
use App\Models\UseCase;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class ImportCuratedTestLab extends Command
{
    protected $signature = 'testlab:import-curated
        {--dry-run : Validate the dataset and catalog matches without writing}
        {--dataset=v1-2026-08-23 : Curated Test Lab dataset version}
        {--models= : Optional comma-separated exact AI model names overriding the dataset defaults}';

    protected $description = 'Import curated AI Test Lab experiment definitions and create empty model run slots safely.';

    public function handle(): int
    {
        if (! Schema::hasTable('ai_tests') || ! Schema::hasTable('ai_test_results') || ! Schema::hasTable('ai_test_runs')) {
            $this->error('AI Test Lab V3 tables are not ready. Run: php artisan migrate');
            return self::FAILURE;
        }

        $dataset = (string) $this->option('dataset');
        $supported = [
            'v1-2026-08-23' => 'testlab-tests-v1-2026-08-23.json',
        ];

        if (! isset($supported[$dataset])) {
            $this->error('Unsupported Test Lab dataset. Supported: '.implode(', ', array_keys($supported)));
            return self::FAILURE;
        }

        $path = storage_path('app/import-templates/'.$supported[$dataset]);
        if (! is_file($path)) {
            $this->error('Test Lab dataset file is missing: '.$path);
            return self::FAILURE;
        }

        $payload = json_decode((string) file_get_contents($path), true);
        if (! is_array($payload) || ! is_array($payload['tests'] ?? null)) {
            $this->error('Invalid Test Lab dataset JSON.');
            return self::FAILURE;
        }

        $requestedModelNames = $this->requestedModels($payload);
        $modelMap = $this->resolveModels($requestedModelNames);
        $missingModels = array_values(array_filter($requestedModelNames, fn (string $name) => ! isset($modelMap[mb_strtolower($name)])));
        $resolvedModels = collect($requestedModelNames)
            ->map(fn (string $name) => $modelMap[mb_strtolower($name)] ?? null)
            ->filter()
            ->unique('id')
            ->values();

        if ($missingModels) {
            $this->warn('Models not found: '.implode(', ', $missingModels));
        }

        if ($resolvedModels->count() < 2) {
            $this->error('At least 2 requested models must exist in ai_models. Use --models="Exact Model 1,Exact Model 2,Exact Model 3" if needed.');
            return self::FAILURE;
        }

        $validation = $this->validateTests($payload['tests']);
        if ($validation['errors']) {
            foreach ($validation['errors'] as $error) $this->error($error);
            return self::FAILURE;
        }
        foreach ($validation['warnings'] as $warning) $this->warn($warning);

        $quick = collect($payload['tests'])->where('run_mode', 'quick')->count();
        $verified = collect($payload['tests'])->where('run_mode', 'verified')->count();
        $runSlots = collect($payload['tests'])->sum(function (array $test) use ($resolvedModels) {
            $runs = (int) config('test_lab.run_modes.'.($test['run_mode'] ?? 'quick').'.runs', 1);
            return $runs * $resolvedModels->count();
        });

        $this->info("Test Lab dataset {$dataset}: ".count($payload['tests'])." tests ({$quick} quick + {$verified} verified).");
        $this->info('Selected models: '.$resolvedModels->pluck('name')->implode(', '));
        $this->info("Empty run slots represented by this dataset: {$runSlots}.");
        $this->line('No model responses, scores, evidence or verification claims are imported.');

        if ($this->option('dry-run')) {
            $this->info('Dry run complete. No database changes made.');
            return self::SUCCESS;
        }

        $stats = ['created' => 0, 'updated' => 0, 'preserved' => 0, 'results' => 0, 'runs' => 0];

        DB::transaction(function () use ($payload, $resolvedModels, $dataset, &$stats) {
            foreach ($payload['tests'] as $definition) {
                $slug = Str::slug((string) $definition['name']);
                $existing = AiTest::query()->where('slug', $slug)->first();

                $hasCapturedRun = $existing
                    ? AiTestRun::query()
                        ->whereHas('result', fn ($q) => $q->where('ai_test_id', $existing->id))
                        ->whereNotNull('response_text')
                        ->exists()
                    : false;

                $protected = $existing && ($existing->prompt_locked_at || $hasCapturedRun);

                if ($protected) {
                    $test = $existing;
                    $stats['preserved']++;
                } else {
                    $featureId = $this->findFeatureId($definition['feature'] ?? null);
                    $useCaseId = $this->findUseCaseId($definition['use_case'] ?? null);
                    $testType = (string) $definition['test_type'];
                    $runMode = (string) $definition['run_mode'];
                    $rubric = AiTest::rubricForType($testType);
                    $requiredRuns = (int) config('test_lab.run_modes.'.$runMode.'.runs', 1);

                    $data = [
                        'name' => $definition['name'],
                        'short_description' => $definition['short_description'] ?? null,
                        'prompt' => $definition['prompt'],
                        'category' => $definition['category'],
                        'test_type' => $testType,
                        'difficulty' => $definition['difficulty'],
                        'feature_id' => $featureId,
                        'use_case_id' => $useCaseId,
                        'criteria' => $definition['criteria'] ?? null,
                        'evaluation_rubric' => $rubric,
                        'scoring_weights' => collect($rubric)->mapWithKeys(fn ($item) => [$item['key'] => $item['weight']])->all(),
                        'run_mode' => $runMode,
                        'required_runs' => $requiredRuns,
                        'methodology' => $definition['methodology'] ?? null,
                        'expected_output' => $definition['expected_output'] ?? null,
                        'status' => 'draft',
                        'is_featured' => (bool) ($definition['is_featured'] ?? false),
                        'is_verified' => false,
                        'source_note' => $definition['source_note'] ?? "Curated AI Orbit Test Lab dataset {$dataset}.",
                        'seo_title' => $definition['seo_title'] ?? null,
                        'meta_description' => $definition['meta_description'] ?? null,
                        'published_at' => null,
                        'last_verified_at' => null,
                    ];

                    if ($existing) {
                        $existing->fill($data)->save();
                        $test = $existing->fresh();
                        $stats['updated']++;
                    } else {
                        $test = AiTest::create(['slug' => $slug, ...$data]);
                        $stats['created']++;
                    }
                }

                $requiredRuns = max(1, (int) $test->required_runs);
                foreach ($resolvedModels as $model) {
                    $result = AiTestResult::firstOrCreate(
                        ['ai_test_id' => $test->id, 'ai_model_id' => $model->id],
                        ['status' => 'pending', 'verification_level' => 'unverified', 'run_count' => 0]
                    );
                    if ($result->wasRecentlyCreated) $stats['results']++;

                    for ($runNumber = 1; $runNumber <= $requiredRuns; $runNumber++) {
                        $run = AiTestRun::firstOrCreate(
                            ['ai_test_result_id' => $result->id, 'run_number' => $runNumber],
                            ['status' => 'pending', 'verification_level' => 'unverified']
                        );
                        if ($run->wasRecentlyCreated) $stats['runs']++;
                    }
                }
            }
        });

        $this->info("Created {$stats['created']} tests; updated {$stats['updated']} unlocked tests; preserved {$stats['preserved']} locked/captured tests.");
        $this->info("Created {$stats['results']} model result rows and {$stats['runs']} empty run slots.");
        $this->info('Import complete. Open Admin > AI Test Lab and work through the draft experiments when ready.');

        return self::SUCCESS;
    }

    private function requestedModels(array $payload): array
    {
        $override = trim((string) $this->option('models'));
        $names = $override !== '' ? explode(',', $override) : ($payload['default_models'] ?? []);

        return collect($names)
            ->map(fn ($name) => trim((string) $name))
            ->filter()
            ->unique(fn ($name) => mb_strtolower($name))
            ->values()
            ->all();
    }

    private function resolveModels(array $names): array
    {
        if ($names === []) return [];

        $models = AiModel::query()
            ->where(function ($query) use ($names) {
                foreach ($names as $name) {
                    $query->orWhereRaw('LOWER(name) = ?', [mb_strtolower($name)]);
                }
            })
            ->get(['id', 'name', 'status']);

        return $models->keyBy(fn ($model) => mb_strtolower($model->name))->all();
    }

    private function validateTests(array $tests): array
    {
        $errors = [];
        $warnings = [];
        $names = [];
        $categories = array_keys(config('test_lab.categories', []));
        $types = array_keys(config('test_lab.test_types', []));
        $modes = array_keys(config('test_lab.run_modes', []));
        $difficulties = array_keys(config('test_lab.difficulties', []));

        foreach ($tests as $index => $test) {
            $row = $index + 1;
            foreach (['name', 'prompt', 'category', 'test_type', 'run_mode', 'difficulty'] as $field) {
                if (blank($test[$field] ?? null)) $errors[] = "Test {$row}: {$field} is required.";
            }
            if (! in_array($test['category'] ?? null, $categories, true)) $errors[] = "Test {$row}: invalid category.";
            if (! in_array($test['test_type'] ?? null, $types, true)) $errors[] = "Test {$row}: invalid test_type.";
            if (! in_array($test['run_mode'] ?? null, $modes, true)) $errors[] = "Test {$row}: invalid run_mode.";
            if (! in_array($test['difficulty'] ?? null, $difficulties, true)) $errors[] = "Test {$row}: invalid difficulty.";

            $nameKey = mb_strtolower(trim((string) ($test['name'] ?? '')));
            if ($nameKey !== '' && isset($names[$nameKey])) $errors[] = "Test {$row}: duplicate test name in dataset.";
            $names[$nameKey] = true;

            if (! empty($test['feature']) && $this->findFeatureId($test['feature']) === null) {
                $warnings[] = "Test {$row}: feature not found and will be left blank: {$test['feature']}";
            }
            if (! empty($test['use_case']) && $this->findUseCaseId($test['use_case']) === null) {
                $warnings[] = "Test {$row}: use case not found and will be left blank: {$test['use_case']}";
            }
        }

        return ['errors' => array_values(array_unique($errors)), 'warnings' => array_values(array_unique($warnings))];
    }

    private function findFeatureId(?string $name): ?int
    {
        if (blank($name)) return null;
        return Feature::query()->whereRaw('LOWER(name) = ?', [mb_strtolower(trim($name))])->value('id');
    }

    private function findUseCaseId(?string $name): ?int
    {
        if (blank($name)) return null;
        return UseCase::query()->whereRaw('LOWER(name) = ?', [mb_strtolower(trim($name))])->value('id');
    }
}
