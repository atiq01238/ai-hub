<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AiModel;
use App\Models\AiTest;
use App\Models\AiTestResult;
use App\Models\AiTestRun;
use App\Models\Feature;
use App\Models\UseCase;
use App\Services\TestLab\AutoScoringService;
use App\Services\TestLab\TestLabAggregateService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class TestlabController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:120'],
            'category' => ['nullable', 'string', 'max:80'],
            'status' => ['nullable', Rule::in(['draft', 'published'])],
            'difficulty' => ['nullable', Rule::in(array_keys(config('test_lab.difficulties', [])))],
        ]);

        $query = AiTest::query()
            ->with(['feature:id,name', 'useCase:id,name'])
            ->withCount([
                'results',
                'completedResults as complete_results_count',
                'completedResults as verified_results_count' => fn ($q) => $q->whereIn('verification_level', ['verified', 'high_confidence']),
            ]);

        if ($q = trim((string) ($filters['q'] ?? ''))) {
            $query->where(fn ($builder) => $builder
                ->where('name', 'like', "%{$q}%")
                ->orWhere('prompt', 'like', "%{$q}%")
                ->orWhere('short_description', 'like', "%{$q}%"));
        }
        if ($category = $filters['category'] ?? null) $query->where('category', $category);
        if ($status = $filters['status'] ?? null) $query->where('status', $status);
        if ($difficulty = $filters['difficulty'] ?? null) $query->where('difficulty', $difficulty);

        $tests = $query->latest('id')->paginate(12)->withQueryString();
        $models = AiModel::query()->with('company:id,name')
            ->whereIn('status', ['active', 'preview'])
            ->orderBy('name')->get(['id', 'company_id', 'name', 'slug', 'logo_path', 'status']);
        $features = Feature::active()->orderBy('sort_order')->orderBy('name')->get(['id', 'name']);
        $useCases = UseCase::active()->orderBy('sort_order')->orderBy('name')->get(['id', 'name']);
        $categories = config('test_lab.categories', []);
        $difficulties = config('test_lab.difficulties', []);
        $testTypes = config('test_lab.test_types', []);
        $runModes = config('test_lab.run_modes', []);

        $stats = [
            'total' => AiTest::count(),
            'published' => AiTest::where('status', 'published')->count(),
            'drafts' => AiTest::where('status', 'draft')->count(),
            'results' => AiTestResult::complete()->count(),
            'verified' => AiTestResult::verified()->count(),
        ];

        return view('testlab.index', compact(
            'tests', 'models', 'features', 'useCases', 'categories', 'difficulties', 'testTypes', 'runModes', 'stats'
        ));
    }

    public function results(Request $request)
    {
        return $this->index($request);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'short_description' => ['nullable', 'string', 'max:500'],
            'prompt' => ['required', 'string', 'max:50000'],
            'category' => ['required', Rule::in(array_keys(config('test_lab.categories', [])))],
            'test_type' => ['required', Rule::in(array_keys(config('test_lab.test_types', [])))],
            'run_mode' => ['required', Rule::in(array_keys(config('test_lab.run_modes', [])))],
            'difficulty' => ['required', Rule::in(array_keys(config('test_lab.difficulties', [])))],
            'feature_id' => ['nullable', 'integer', 'exists:features,id'],
            'use_case_id' => ['nullable', 'integer', 'exists:use_cases,id'],
            'criteria' => ['nullable', 'string', 'max:1000'],
            'expected_output' => ['nullable', 'string', 'max:10000'],
            'methodology' => ['nullable', 'string', 'max:10000'],
            'model_ids' => ['required', 'array', 'min:2', 'max:'.config('test_lab.model_limit', 6)],
            'model_ids.*' => ['integer', 'distinct', 'exists:ai_models,id'],
        ]);

        $requiredRuns = (int) config('test_lab.run_modes.'.$data['run_mode'].'.runs', 1);
        $rubric = AiTest::rubricForType($data['test_type']);

        $test = AiTest::create([
            ...collect($data)->except(['model_ids'])->all(),
            'evaluation_rubric' => $rubric,
            'scoring_weights' => collect($rubric)->mapWithKeys(fn ($item) => [$item['key'] => $item['weight']])->all(),
            'required_runs' => $requiredRuns,
            'status' => 'draft',
        ]);

        foreach ($data['model_ids'] as $modelId) {
            $result = AiTestResult::create(['ai_test_id' => $test->id, 'ai_model_id' => $modelId]);
            $this->createRunSlots($result, $requiredRuns);
        }

        return redirect()->route('admin.testlab.show', ['id' => $test->id, 'step' => 1])
            ->with('status', 'Draft created. Confirm setup, lock the shared prompt and rubric, then capture every controlled run.');
    }

    public function show(int $id)
    {
        $test = AiTest::with([
            'feature:id,name', 'useCase:id,name',
            'results' => fn ($q) => $q->with(['model.company', 'runs'])->orderBy('id'),
        ])->findOrFail($id);

        foreach ($test->results as $result) {
            if ($result->runs->count() < $test->required_runs && $result->runs->whereNotNull('response_text')->isEmpty()) {
                $this->createRunSlots($result, $test->required_runs);
            }
        }
        $test->load(['results' => fn ($q) => $q->with(['model.company', 'runs'])->orderBy('id')]);

        $models = AiModel::query()->with('company:id,name')
            ->whereIn('status', ['active', 'preview'])
            ->whereNotIn('id', $test->results->pluck('ai_model_id'))
            ->orderBy('name')->get(['id', 'company_id', 'name', 'slug', 'logo_path', 'status']);
        $features = Feature::active()->orderBy('sort_order')->orderBy('name')->get(['id', 'name']);
        $useCases = UseCase::active()->orderBy('sort_order')->orderBy('name')->get(['id', 'name']);
        $categories = config('test_lab.categories', []);
        $difficulties = config('test_lab.difficulties', []);
        $testTypes = config('test_lab.test_types', []);
        $runModes = config('test_lab.run_modes', []);
        $rubricLibrary = config('test_lab.rubric_library', []);
        $rubric = $test->evaluationRubric();
        $autoScorer = app(AutoScoringService::class);
        $autoScores = [];
        foreach ($test->results as $result) {
            foreach ($result->runs as $run) {
                $autoScores[$run->id] = filled($run->response_text) ? $autoScorer->score($test, $run) : null;
            }
        }

        return view('testlab.show', compact(
            'test', 'models', 'features', 'useCases', 'categories', 'difficulties', 'testTypes',
            'runModes', 'rubricLibrary', 'rubric', 'autoScores'
        ));
    }

    public function update(Request $request, int $id)
    {
        $test = AiTest::with('results.runs')->findOrFail($id);
        $section = (string) $request->input('section', 'all');

        if ($section === 'setup') {
            $data = $request->validate([
                'name' => ['required', 'string', 'max:255'],
                'short_description' => ['nullable', 'string', 'max:500'],
                'category' => ['required', Rule::in(array_keys(config('test_lab.categories', [])))],
                'test_type' => ['required', Rule::in(array_keys(config('test_lab.test_types', [])))],
                'run_mode' => ['required', Rule::in(array_keys(config('test_lab.run_modes', [])))],
                'difficulty' => ['required', Rule::in(array_keys(config('test_lab.difficulties', [])))],
                'feature_id' => ['nullable', 'integer', 'exists:features,id'],
                'use_case_id' => ['nullable', 'integer', 'exists:use_cases,id'],
            ]);

            $captured = $test->results->flatMap->runs->contains(fn ($run) => filled($run->response_text));
            if ($captured && ($data['test_type'] !== $test->test_type || $data['run_mode'] !== $test->run_mode)) {
                throw ValidationException::withMessages([
                    'run_mode' => 'Test type or run mode cannot change after model responses have been captured. Create a new experiment for a different protocol.',
                ]);
            }

            $typeChanged = $data['test_type'] !== $test->test_type;
            $modeChanged = $data['run_mode'] !== $test->run_mode;
            $requiredRuns = (int) config('test_lab.run_modes.'.$data['run_mode'].'.runs', 1);

            $test->fill([
                ...$data,
                'required_runs' => $requiredRuns,
                'evaluation_rubric' => $typeChanged ? AiTest::rubricForType($data['test_type']) : $test->evaluation_rubric,
            ])->save();

            if ($modeChanged && ! $captured) {
                foreach ($test->results as $result) $this->resizeRunSlots($result, $requiredRuns);
            }

            return redirect()->route('admin.testlab.show', ['id' => $test->id, 'step' => 2])
                ->with('status', 'Setup saved. Now review the test-specific rubric and lock the exact prompt.');
        }

        if ($section === 'prompt') {
            $data = $request->validate([
                'prompt' => ['required', 'string', 'max:50000'],
                'criteria' => ['nullable', 'string', 'max:1000'],
                'expected_output' => ['nullable', 'string', 'max:10000'],
                'methodology' => ['nullable', 'string', 'max:10000'],
                'rubric' => ['required', 'array'],
                'rubric.*.enabled' => ['nullable', 'boolean'],
                'rubric.*.weight' => ['nullable', 'integer', 'min:0', 'max:100'],
            ]);

            $captured = $test->results->flatMap->runs->contains(fn ($run) => filled($run->response_text));
            if ($captured && trim($data['prompt']) !== trim((string) $test->prompt)) {
                throw ValidationException::withMessages([
                    'prompt' => 'The prompt is locked because model runs already exist. Changing it would make the experiment unfair.',
                ]);
            }

            $rubric = $this->normalizedRubric($data['rubric']);
            $this->validateRubric($rubric);
            $methodologyChanged = trim((string) ($data['methodology'] ?? '')) !== trim((string) $test->methodology);

            if ($captured) {
                $sameRubric = json_encode($rubric) === json_encode($test->evaluationRubric());
                $protocolChanged = trim($data['prompt']) !== trim((string) $test->prompt)
                    || trim((string) ($data['expected_output'] ?? '')) !== trim((string) $test->expected_output)
                    || trim((string) ($data['criteria'] ?? '')) !== trim((string) $test->criteria)
                    || ! $sameRubric;
                if ($protocolChanged) {
                    throw ValidationException::withMessages([
                        'rubric' => 'Prompt, answer key and scoring rubric are locked after the first model response is captured. Only explanatory methodology notes may still be updated without changing the protocol.',
                    ]);
                }
            }

            $test->fill([
                ...collect($data)->except('rubric')->all(),
                'evaluation_rubric' => $rubric,
                'scoring_weights' => collect($rubric)->mapWithKeys(fn ($item) => [$item['key'] => $item['weight']])->all(),
                'prompt_locked_at' => $test->prompt_locked_at ?: now(),
            ])->save();

            if ($captured && $methodologyChanged && $test->is_verified) {
                $test->update(['is_verified' => false, 'last_verified_at' => null]);
            }

            foreach ($test->results as $result) {
                foreach ($result->runs as $run) $run->save();
                app(TestLabAggregateService::class)->sync($result);
            }

            return redirect()->route('admin.testlab.show', ['id' => $test->id, 'step' => 3])
                ->with('status', 'Prompt and rubric locked. Run every model with this exact prompt and capture each required run.');
        }

        if ($section === 'publication') {
            $data = $request->validate([
                'status' => ['required', Rule::in(['draft', 'published'])],
                'is_featured' => ['nullable', 'boolean'],
                'is_verified' => ['nullable', 'boolean'],
                'source_note' => ['nullable', 'string', 'max:500'],
                'seo_title' => ['nullable', 'string', 'max:80'],
                'meta_description' => ['nullable', 'string', 'max:180'],
            ]);

            if ($data['status'] === 'published' && $test->completedResults()->count() < 2) {
                throw ValidationException::withMessages(['status' => 'A public Test Lab experiment needs at least two complete model aggregates.']);
            }

            $isVerified = $request->boolean('is_verified');
            if ($isVerified && $test->completedResults()->whereIn('verification_level', ['verified', 'high_confidence'])->count() < 2) {
                throw ValidationException::withMessages([
                    'is_verified' => 'Methodology verification requires at least two verified model results. Review the run evidence first.',
                ]);
            }

            $wasPublished = $test->status === 'published';
            $test->fill([
                ...collect($data)->except(['is_featured', 'is_verified'])->all(),
                'is_featured' => $request->boolean('is_featured'),
                'is_verified' => $isVerified,
                'published_at' => $data['status'] === 'published' ? ($test->published_at ?: now()) : null,
                'last_verified_at' => $isVerified ? now() : null,
            ])->save();

            $message = ! $wasPublished && $test->status === 'published'
                ? 'Experiment published successfully.'
                : ($test->status === 'draft' ? 'Experiment saved as a private draft.' : 'Publication settings updated.');

            return redirect()->route('admin.testlab.show', ['id' => $test->id, 'step' => 4])->with('status', $message);
        }

        return redirect()->route('admin.testlab.show', $test->id)->with('status', 'No Test Lab section was changed.');
    }

    public function addModels(Request $request, int $id)
    {
        $test = AiTest::withCount('results')->findOrFail($id);
        $data = $request->validate([
            'model_ids' => ['required', 'array', 'min:1'],
            'model_ids.*' => ['integer', 'distinct', 'exists:ai_models,id'],
        ]);

        $existing = $test->results()->pluck('ai_model_id');
        $newIds = collect($data['model_ids'])->map(fn ($modelId) => (int) $modelId)->diff($existing)->values();
        $limit = (int) config('test_lab.model_limit', 6);

        if (($test->results_count + $newIds->count()) > $limit) {
            throw ValidationException::withMessages(['model_ids' => "A Test Lab experiment can contain up to {$limit} models."]);
        }

        $newIds->each(function ($modelId) use ($test) {
            $result = AiTestResult::create(['ai_test_id' => $test->id, 'ai_model_id' => $modelId]);
            $this->createRunSlots($result, $test->required_runs);
        });

        return back()->with('status', $newIds->count().' model slot(s) added with '.$test->required_runs.' run(s) each.');
    }

    public function updateRun(Request $request, int $runId)
    {
        $section = (string) $request->input('section', 'capture');
        $run = AiTestRun::with(['result.test.results.runs', 'result.model'])->findOrFail($runId);
        $result = $run->result;
        $test = $result->test;

        if ($section === 'capture') {
            if (blank($test->prompt_locked_at)) {
                throw ValidationException::withMessages([
                    'response_text' => 'Lock the master prompt and scoring rubric in Step 2 before capturing model runs.',
                ]);
            }

            $data = $request->validate([
                'response_text' => ['required', 'string', 'max:100000'],
                'model_version' => ['nullable', 'string', 'max:120'],
                'latency_ms' => ['nullable', 'integer', 'min:0', 'max:3600000'],
                'input_tokens' => ['nullable', 'integer', 'min:0'],
                'output_tokens' => ['nullable', 'integer', 'min:0'],
                'estimated_cost_usd' => ['nullable', 'numeric', 'min:0', 'max:999999'],
                'source_label' => ['nullable', 'string', 'max:160'],
                'source_url' => ['nullable', 'url', 'max:2000'],
                'evidence_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
                'remove_evidence' => ['nullable', 'boolean'],
                'tested_at' => ['nullable', 'date'],
                'wizard_action' => ['nullable', Rule::in(['next'])],
            ]);

            if ($request->boolean('remove_evidence') && $run->evidence_path) {
                Storage::disk('public')->delete($run->evidence_path);
                $run->evidence_path = null;
            }
            if ($request->hasFile('evidence_image')) {
                if ($run->evidence_path) Storage::disk('public')->delete($run->evidence_path);
                $run->evidence_path = $request->file('evidence_image')->store('testlab/evidence', 'public');
            }

            $responseChanged = trim((string) $run->response_text) !== trim((string) $data['response_text']);
            $run->fill(collect($data)->except(['evidence_image', 'remove_evidence', 'wizard_action'])->all());

            // A changed exact response invalidates the old rubric decision and verification.
            if ($responseChanged && $run->status === 'complete') {
                $run->score_breakdown = null;
                $run->overall_score = null;
                $run->evaluator_summary = null;
                $run->verification_level = 'unverified';
                $run->verified_at = null;
                $run->status = 'pending';
            } elseif ($run->status === 'excluded') {
                $run->status = 'pending';
            }

            $run->tested_at ??= now();
            $run->save();
            app(TestLabAggregateService::class)->sync($result);
            $this->maintainPublicationIntegrity($test);

            $ids = $this->orderedRunIds($test);
            $position = $ids->search($run->id);
            $nextId = $position !== false && $position < ($ids->count() - 1) ? $ids[$position + 1] : null;
            $params = ['id' => $test->id, 'step' => $nextId ? 3 : 4];
            if ($nextId) $params['run'] = $nextId;

            return redirect()->route('admin.testlab.show', $params)
                ->with('status', $nextId ? 'Run captured. Continue with the next controlled run using the same locked prompt.' : 'All run slots have been visited. Review the rubric scores next.');
        }

        if ($section === 'score') {
            $data = $request->validate([
                'scores' => ['required', 'array'],
                'scores.*' => ['nullable', 'integer', 'min:0', 'max:100'],
                'na' => ['nullable', 'array'],
                'na.*' => ['nullable', 'boolean'],
                'evaluator_summary' => ['nullable', 'string', 'max:10000'],
                'verification_level' => ['required', Rule::in(['unverified', 'reviewed', 'verified'])],
            ]);

            if (blank($run->response_text)) {
                throw ValidationException::withMessages(['response_text' => 'Capture this run response before scoring it.']);
            }

            if ($data['verification_level'] === 'verified') {
                $verificationErrors = [];
                if (blank($run->model_version)) {
                    $verificationErrors['verification_level'] = 'A verified run needs the tested model/version recorded in Step 3.';
                }
                if (blank($run->evidence_path) && blank($run->source_url)) {
                    $verificationErrors['source_url'] = 'A verified run needs an evidence screenshot or source URL.';
                }
                if ($verificationErrors !== []) {
                    throw ValidationException::withMessages($verificationErrors);
                }
            }

            $scores = [];
            $missing = [];
            foreach ($test->evaluationRubric() as $criterion) {
                $key = $criterion['key'];
                $value = $data['scores'][$key] ?? null;
                $isNa = filter_var($data['na'][$key] ?? false, FILTER_VALIDATE_BOOLEAN);
                if (($value === null || $value === '') && ! $isNa) {
                    $missing[] = $criterion['label'];
                    continue;
                }
                if (! $isNa && $value !== null && $value !== '') {
                    $scores[$key] = max(0, min(100, (int) $value));
                }
            }
            if ($missing) {
                throw ValidationException::withMessages([
                    'scores' => 'Score each applicable rubric criterion or explicitly mark it N/A: '.implode(', ', $missing).'.',
                ]);
            }
            if ($scores === []) {
                throw ValidationException::withMessages(['scores' => 'At least one applicable rubric criterion must have a real score.']);
            }

            $run->fill([
                'score_breakdown' => $scores,
                'evaluator_summary' => $data['evaluator_summary'] ?? null,
                'verification_level' => $data['verification_level'],
                'status' => 'complete',
            ])->save();

            $aggregate = app(TestLabAggregateService::class)->sync($result);
            $this->maintainPublicationIntegrity($test);
            $message = $aggregate->status === 'complete'
                ? ($result->model?->name ?: 'Model').' aggregate updated from '.$aggregate->run_count.' completed run(s).'
                : 'Run score saved. '.$aggregate->run_count.'/'.$test->required_runs.' required run(s) are complete for this model.';

            return redirect()->to(route('admin.testlab.show', ['id' => $test->id, 'step' => 4]).'#run-'.$run->id)
                ->with('status', $message);
        }

        throw ValidationException::withMessages(['section' => 'Unknown Test Lab run update section.']);
    }

    // Backward-compatible endpoint for bookmarks/forms created before Test Lab V3.
    public function updateResult(Request $request, int $resultId)
    {
        $result = AiTestResult::with('runs')->findOrFail($resultId);
        $run = $result->runs->first() ?: AiTestRun::create(['ai_test_result_id' => $result->id, 'run_number' => 1]);
        return $this->updateRun($request, $run->id);
    }

    public function destroyResult(int $resultId)
    {
        $result = AiTestResult::with(['test', 'runs'])->findOrFail($resultId);
        $test = $result->test;

        if ($test->results()->count() <= 2) {
            throw ValidationException::withMessages(['result' => 'Keep at least two model slots in every Test Lab experiment.']);
        }

        $result->runs->pluck('evidence_path')->filter()->unique()->each(fn ($path) => Storage::disk('public')->delete($path));
        $result->delete();

        $this->maintainPublicationIntegrity($test);

        return redirect()->route('admin.testlab.show', $test->id)->with('status', 'Model and all of its Test Lab runs were removed.');
    }

    public function export(int $id)
    {
        $test = AiTest::with(['results.model.company', 'results.runs'])->findOrFail($id);
        $filename = ($test->slug ?: 'ai-test-'.$test->id).'-results-v3.csv';
        $rubric = $test->evaluationRubric();

        return response()->streamDownload(function () use ($test, $rubric) {
            $out = fopen('php://output', 'w');
            $rubricHeaders = collect($rubric)->pluck('label')->all();
            fputcsv($out, array_merge([
                'Test', 'Test type', 'Run mode', 'Model', 'Provider', 'Aggregate status', 'Runs complete',
            ], $rubricHeaders, [
                'Overall average', 'Score min', 'Score max', 'Std dev', 'Avg latency ms', 'Avg cost USD',
                'Verification level', 'Latest model version', 'Tested at',
            ]));

            foreach ($test->results as $result) {
                $scores = $result->scores();
                fputcsv($out, array_merge([
                    $test->name, $test->testTypeLabel(), $test->runModeLabel(), $result->model?->name,
                    $result->model?->company?->name, $result->status, $result->run_count.'/'.$test->required_runs,
                ], collect($rubric)->map(fn ($item) => $scores[$item['key']] ?? null)->all(), [
                    $result->overall_score, $result->score_min, $result->score_max, $result->score_stddev,
                    $result->avg_latency_ms, $result->avg_estimated_cost_usd, $result->verification_level,
                    $result->model_version, $result->tested_at?->toIso8601String(),
                ]));
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function destroy(int $id)
    {
        $test = AiTest::with('results.runs')->findOrFail($id);
        $test->results->flatMap->runs->pluck('evidence_path')->filter()->unique()->each(fn ($path) => Storage::disk('public')->delete($path));
        $test->delete();
        return redirect()->route('admin.testlab.index')->with('status', 'Test and all controlled runs deleted.');
    }

    private function createRunSlots(AiTestResult $result, int $requiredRuns): void
    {
        $existing = $result->runs()->pluck('run_number')->map(fn ($n) => (int) $n);
        for ($number = 1; $number <= $requiredRuns; $number++) {
            if (! $existing->contains($number)) {
                AiTestRun::create(['ai_test_result_id' => $result->id, 'run_number' => $number]);
            }
        }
    }

    private function resizeRunSlots(AiTestResult $result, int $requiredRuns): void
    {
        $result->load('runs');
        $result->runs->filter(fn ($run) => $run->run_number > $requiredRuns && blank($run->response_text))->each->delete();
        $this->createRunSlots($result, $requiredRuns);
        app(TestLabAggregateService::class)->sync($result);
    }

    private function orderedRunIds(AiTest $test)
    {
        $test->loadMissing(['results' => fn ($q) => $q->with('runs')->orderBy('id')]);
        return $test->results->flatMap(fn ($result) => $result->runs->sortBy('run_number')->pluck('id'))->values();
    }

    private function normalizedRubric(array $input): array
    {
        $library = config('test_lab.rubric_library', []);
        $rubric = [];
        foreach ($library as $key => $definition) {
            $row = $input[$key] ?? [];
            if (! filter_var($row['enabled'] ?? false, FILTER_VALIDATE_BOOLEAN)) continue;
            $weight = max(0, min(100, (int) ($row['weight'] ?? 0)));
            if ($weight <= 0) continue;
            $rubric[] = [
                'key' => $key,
                'label' => $definition['label'],
                'description' => $definition['description'],
                'weight' => $weight,
                'auto_strategy' => $definition['auto_strategy'] ?? 'manual',
            ];
        }
        return $rubric;
    }

    private function maintainPublicationIntegrity(AiTest $test): void
    {
        $test->refresh();
        $changes = [];

        if ($test->status === 'published' && $test->completedResults()->count() < 2) {
            $changes['status'] = 'draft';
            $changes['published_at'] = null;
        }

        if ($test->is_verified && $test->completedResults()->whereIn('verification_level', ['verified', 'high_confidence'])->count() < 2) {
            $changes['is_verified'] = false;
            $changes['last_verified_at'] = null;
        }

        if ($changes !== []) {
            $test->update($changes);
        }
    }

    private function validateRubric(array $rubric): void
    {
        if ($rubric === []) {
            throw ValidationException::withMessages(['rubric' => 'Enable at least one scoring criterion.']);
        }
        $total = collect($rubric)->sum('weight');
        if ($total !== 100) {
            throw ValidationException::withMessages(['rubric' => "Enabled rubric weights must total exactly 100%. Current total: {$total}%."]);
        }
    }
}
