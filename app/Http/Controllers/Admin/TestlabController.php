<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AiModel;
use App\Models\AiTest;
use App\Models\AiTestResult;
use App\Models\Feature;
use App\Models\UseCase;
use App\Services\TestLab\AutoScoringService;
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
                'completedResults as verified_results_count' => fn ($q) => $q->where('is_verified', true),
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
        $weights = config('test_lab.default_weights', []);

        $stats = [
            'total' => AiTest::count(),
            'published' => AiTest::where('status', 'published')->count(),
            'drafts' => AiTest::where('status', 'draft')->count(),
            'results' => AiTestResult::complete()->count(),
            'verified' => AiTestResult::verified()->count(),
        ];

        return view('testlab.index', compact(
            'tests', 'models', 'features', 'useCases', 'categories', 'difficulties', 'weights', 'stats'
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
            'difficulty' => ['required', Rule::in(array_keys(config('test_lab.difficulties', [])))],
            'feature_id' => ['nullable', 'integer', 'exists:features,id'],
            'use_case_id' => ['nullable', 'integer', 'exists:use_cases,id'],
            'criteria' => ['nullable', 'string', 'max:1000'],
            'expected_output' => ['nullable', 'string', 'max:10000'],
            'methodology' => ['nullable', 'string', 'max:10000'],
            'weights' => ['required', 'array'],
            'weights.*' => ['required', 'integer', 'min:0', 'max:100'],
            'model_ids' => ['required', 'array', 'min:2', 'max:'.config('test_lab.model_limit', 6)],
            'model_ids.*' => ['integer', 'distinct', 'exists:ai_models,id'],
        ]);

        $this->validateWeights($data['weights']);

        $test = AiTest::create([
            ...collect($data)->except(['weights', 'model_ids'])->all(),
            'scoring_weights' => $this->normalizedWeights($data['weights']),
            'status' => 'draft',
        ]);

        foreach ($data['model_ids'] as $modelId) {
            AiTestResult::create(['ai_test_id' => $test->id, 'ai_model_id' => $modelId]);
        }

        return redirect()->route('admin.testlab.show', $test->id)
            ->with('status', 'Draft created. Run each selected model with the exact same prompt, then score and publish the results.');
    }

    public function show(int $id)
    {
        $test = AiTest::with([
            'feature:id,name', 'useCase:id,name',
            'results' => fn ($q) => $q->with(['model.company'])->orderBy('id'),
        ])->findOrFail($id);

        $models = AiModel::query()->with('company:id,name')
            ->whereIn('status', ['active', 'preview'])
            ->whereNotIn('id', $test->results->pluck('ai_model_id'))
            ->orderBy('name')->get(['id', 'company_id', 'name', 'slug', 'logo_path', 'status']);
        $features = Feature::active()->orderBy('sort_order')->orderBy('name')->get(['id', 'name']);
        $useCases = UseCase::active()->orderBy('sort_order')->orderBy('name')->get(['id', 'name']);
        $categories = config('test_lab.categories', []);
        $difficulties = config('test_lab.difficulties', []);
        $criteria = config('test_lab.criteria', []);
        $weights = $test->scoreWeights();
        $autoScorer = app(AutoScoringService::class);
        $autoScores = $test->results->mapWithKeys(function (AiTestResult $result) use ($test, $autoScorer) {
            if (blank($result->response_text)) return [$result->id => null];
            return [$result->id => $autoScorer->score($test, $result)];
        })->all();

        return view('testlab.show', compact(
            'test', 'models', 'features', 'useCases', 'categories', 'difficulties', 'criteria', 'weights', 'autoScores'
        ));
    }

    public function update(Request $request, int $id)
    {
        $test = AiTest::findOrFail($id);
        $section = (string) $request->input('section', 'all');

        if ($section === 'setup') {
            $data = $request->validate([
                'name' => ['required', 'string', 'max:255'],
                'short_description' => ['nullable', 'string', 'max:500'],
                'category' => ['required', Rule::in(array_keys(config('test_lab.categories', [])))],
                'difficulty' => ['required', Rule::in(array_keys(config('test_lab.difficulties', [])))],
                'feature_id' => ['nullable', 'integer', 'exists:features,id'],
                'use_case_id' => ['nullable', 'integer', 'exists:use_cases,id'],
            ]);

            $test->update($data);

            return redirect()->route('admin.testlab.show', ['id' => $test->id, 'step' => 2])
                ->with('status', 'Setup saved. Now lock the exact prompt and answer key.');
        }

        if ($section === 'prompt') {
            $data = $request->validate([
                'prompt' => ['required', 'string', 'max:50000'],
                'criteria' => ['nullable', 'string', 'max:1000'],
                'expected_output' => ['nullable', 'string', 'max:10000'],
                'methodology' => ['nullable', 'string', 'max:10000'],
                'weights' => ['required', 'array'],
                'weights.*' => ['required', 'integer', 'min:0', 'max:100'],
            ]);

            $this->validateWeights($data['weights']);
            $test->fill([
                ...collect($data)->except('weights')->all(),
                'scoring_weights' => $this->normalizedWeights($data['weights']),
            ])->save();

            $test->results()->get()->each(fn (AiTestResult $result) => $result->save());

            return redirect()->route('admin.testlab.show', ['id' => $test->id, 'step' => 3])
                ->with('status', 'Prompt locked. Run each model with this exact prompt and paste the responses.');
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
                throw ValidationException::withMessages([
                    'status' => 'A public Test Lab experiment needs at least two complete model results.',
                ]);
            }

            $isVerified = $request->boolean('is_verified');
            $isFeatured = $request->boolean('is_featured');
            $wasPublished = $test->status === 'published';

            $test->fill([
                ...collect($data)->except(['is_featured', 'is_verified'])->all(),
                'is_featured' => $isFeatured,
                'is_verified' => $isVerified,
                'published_at' => $data['status'] === 'published'
                    ? ($test->published_at ?: now())
                    : null,
                'last_verified_at' => $isVerified
                    ? ($test->last_verified_at ?: now())
                    : null,
            ])->save();

            $message = ! $wasPublished && $test->status === 'published'
                ? 'Experiment published successfully.'
                : ($test->status === 'draft' ? 'Experiment saved as a private draft.' : 'Publication settings updated.');

            return redirect()->route('admin.testlab.show', ['id' => $test->id, 'step' => 4])->with('status', $message);
        }

        // Backward-compatible full update path for older forms/bookmarks.
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'short_description' => ['nullable', 'string', 'max:500'],
            'prompt' => ['required', 'string', 'max:50000'],
            'category' => ['required', Rule::in(array_keys(config('test_lab.categories', [])))],
            'difficulty' => ['required', Rule::in(array_keys(config('test_lab.difficulties', [])))],
            'feature_id' => ['nullable', 'integer', 'exists:features,id'],
            'use_case_id' => ['nullable', 'integer', 'exists:use_cases,id'],
            'criteria' => ['nullable', 'string', 'max:1000'],
            'expected_output' => ['nullable', 'string', 'max:10000'],
            'methodology' => ['nullable', 'string', 'max:10000'],
            'weights' => ['required', 'array'],
            'weights.*' => ['required', 'integer', 'min:0', 'max:100'],
            'status' => ['required', Rule::in(['draft', 'published'])],
            'is_featured' => ['nullable', 'boolean'],
            'is_verified' => ['nullable', 'boolean'],
            'source_note' => ['nullable', 'string', 'max:500'],
            'seo_title' => ['nullable', 'string', 'max:80'],
            'meta_description' => ['nullable', 'string', 'max:180'],
        ]);

        $this->validateWeights($data['weights']);

        if ($data['status'] === 'published' && $test->completedResults()->count() < 2) {
            throw ValidationException::withMessages([
                'status' => 'A public Test Lab experiment needs at least two complete model results.',
            ]);
        }

        $isVerified = $request->boolean('is_verified');
        $isFeatured = $request->boolean('is_featured');
        $wasPublished = $test->status === 'published';

        $test->fill([
            ...collect($data)->except(['weights', 'is_featured', 'is_verified'])->all(),
            'scoring_weights' => $this->normalizedWeights($data['weights']),
            'is_featured' => $isFeatured,
            'is_verified' => $isVerified,
            'published_at' => $data['status'] === 'published'
                ? ($test->published_at ?: now())
                : null,
            'last_verified_at' => $isVerified
                ? ($test->last_verified_at ?: now())
                : null,
        ])->save();

        $test->results()->get()->each(fn (AiTestResult $result) => $result->save());

        $message = ! $wasPublished && $test->status === 'published'
            ? 'Experiment published successfully.'
            : 'Experiment settings updated.';

        return redirect()->route('admin.testlab.show', $test->id)->with('status', $message);
    }

    public function addModels(Request $request, int $id)
    {
        $test = AiTest::withCount('results')->findOrFail($id);
        $data = $request->validate([
            'model_ids' => ['required', 'array', 'min:1'],
            'model_ids.*' => ['integer', 'distinct', 'exists:ai_models,id'],
        ]);

        $existing = $test->results()->pluck('ai_model_id');
        $newIds = collect($data['model_ids'])->map(fn ($id) => (int) $id)->diff($existing)->values();
        $limit = (int) config('test_lab.model_limit', 6);

        if (($test->results_count + $newIds->count()) > $limit) {
            throw ValidationException::withMessages([
                'model_ids' => "A Test Lab experiment can contain up to {$limit} model results.",
            ]);
        }

        $newIds->each(fn ($modelId) => AiTestResult::create([
            'ai_test_id' => $test->id,
            'ai_model_id' => $modelId,
        ]));

        return back()->with('status', $newIds->count().' model result slot(s) added.');
    }

    public function updateResult(Request $request, int $resultId)
    {
        $section = (string) $request->input('section', 'full');
        $result = AiTestResult::with(['test', 'model'])->findOrFail($resultId);

        if ($section === 'capture') {
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

            if ($request->boolean('remove_evidence') && $result->evidence_path) {
                Storage::disk('public')->delete($result->evidence_path);
                $result->evidence_path = null;
            }

            if ($request->hasFile('evidence_image')) {
                if ($result->evidence_path) Storage::disk('public')->delete($result->evidence_path);
                $result->evidence_path = $request->file('evidence_image')->store('testlab/evidence', 'public');
            }

            $result->fill(collect($data)->except(['evidence_image', 'remove_evidence', 'wizard_action'])->all());
            if (blank($result->tested_at)) $result->tested_at = now();
            $result->save();

            $ids = $result->test->results()->orderBy('id')->pluck('id')->values();
            $position = $ids->search($result->id);
            $nextId = $position !== false && $position < ($ids->count() - 1) ? $ids[$position + 1] : null;

            $params = ['id' => $result->ai_test_id, 'step' => $nextId ? 3 : 4];
            if ($nextId) $params['result'] = $nextId;

            return redirect()->route('admin.testlab.show', $params)
                ->with('status', $nextId ? 'Response saved. Run the next model with the same prompt.' : 'All model runs are captured. Automatic score suggestions are ready for review.');
        }

        if ($section === 'score') {
            $data = $request->validate([
                'score_quality' => ['required', 'integer', 'min:0', 'max:100'],
                'score_accuracy' => ['required', 'integer', 'min:0', 'max:100'],
                'score_prompt_adherence' => ['required', 'integer', 'min:0', 'max:100'],
                'score_creativity' => ['required', 'integer', 'min:0', 'max:100'],
                'score_speed' => ['required', 'integer', 'min:0', 'max:100'],
                'evaluator_summary' => ['nullable', 'string', 'max:10000'],
                'is_verified' => ['nullable', 'boolean'],
            ]);

            if (blank($result->response_text)) {
                throw ValidationException::withMessages([
                    'response_text' => 'Capture this model response before scoring it.',
                ]);
            }

            $result->fill($data);
            $result->status = 'complete';
            $result->is_verified = $request->boolean('is_verified');
            $result->save();

            return redirect()->route('admin.testlab.show', ['id' => $result->ai_test_id, 'step' => 4])
                ->with('status', ($result->model?->name ?: 'Model').' score saved. The leaderboard was recalculated automatically.');
        }

        $data = $request->validate([
            'response_text' => ['nullable', 'string', 'max:100000'],
            'status' => ['required', Rule::in(array_keys(config('test_lab.result_statuses', [])))],
            'model_version' => ['nullable', 'string', 'max:120'],
            'latency_ms' => ['nullable', 'integer', 'min:0', 'max:3600000'],
            'input_tokens' => ['nullable', 'integer', 'min:0'],
            'output_tokens' => ['nullable', 'integer', 'min:0'],
            'estimated_cost_usd' => ['nullable', 'numeric', 'min:0', 'max:999999'],
            'score_quality' => ['nullable', 'integer', 'min:0', 'max:100'],
            'score_accuracy' => ['nullable', 'integer', 'min:0', 'max:100'],
            'score_prompt_adherence' => ['nullable', 'integer', 'min:0', 'max:100'],
            'score_creativity' => ['nullable', 'integer', 'min:0', 'max:100'],
            'score_speed' => ['nullable', 'integer', 'min:0', 'max:100'],
            'evaluator_summary' => ['nullable', 'string', 'max:10000'],
            'source_label' => ['nullable', 'string', 'max:160'],
            'source_url' => ['nullable', 'url', 'max:2000'],
            'evidence_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'remove_evidence' => ['nullable', 'boolean'],
            'is_verified' => ['nullable', 'boolean'],
            'tested_at' => ['nullable', 'date'],
            'exclude_reason' => ['nullable', 'string', 'max:5000'],
        ]);

        if ($data['status'] === 'complete') {
            $hasScore = collect(config('test_lab.criteria', []))
                ->contains(fn ($definition) => ($data[$definition['field']] ?? null) !== null);
            if (! $hasScore) {
                throw ValidationException::withMessages([
                    'status' => 'A complete result needs at least one scored criterion.',
                ]);
            }
        }

        if ($data['status'] === 'excluded' && blank($data['exclude_reason'] ?? null)) {
            throw ValidationException::withMessages([
                'exclude_reason' => 'Explain why this result is excluded from public scoring.',
            ]);
        }

        if ($request->boolean('remove_evidence') && $result->evidence_path) {
            Storage::disk('public')->delete($result->evidence_path);
            $result->evidence_path = null;
        }

        if ($request->hasFile('evidence_image')) {
            if ($result->evidence_path) Storage::disk('public')->delete($result->evidence_path);
            $result->evidence_path = $request->file('evidence_image')->store('testlab/evidence', 'public');
        }

        $result->fill(collect($data)->except(['evidence_image', 'remove_evidence', 'is_verified'])->all());
        $result->is_verified = $data['status'] === 'complete' && $request->boolean('is_verified');
        if ($data['status'] !== 'complete') $result->is_verified = false;
        $result->save();

        $test = $result->test()->first();
        $message = 'Model result saved.';
        if ($test && $test->status === 'published' && $test->completedResults()->count() < 2) {
            $test->update(['status' => 'draft', 'published_at' => null]);
            $message = 'Model result saved. The experiment returned to Draft because fewer than two complete results remain.';
        }

        return redirect()->route('admin.testlab.show', $result->ai_test_id)->with('status', $message);
    }

    public function destroyResult(int $resultId)
    {
        $result = AiTestResult::with('test')->findOrFail($resultId);
        $test = $result->test;

        if ($test->results()->count() <= 2) {
            throw ValidationException::withMessages([
                'result' => 'Keep at least two model slots in every Test Lab experiment.',
            ]);
        }

        if ($result->evidence_path) Storage::disk('public')->delete($result->evidence_path);
        $result->delete();

        if ($test->status === 'published' && $test->completedResults()->count() < 2) {
            $test->update(['status' => 'draft', 'published_at' => null]);
        }

        return redirect()->route('admin.testlab.show', $test->id)->with('status', 'Model result slot removed.');
    }

    public function export(int $id)
    {
        $test = AiTest::with(['results.model.company'])->findOrFail($id);
        $filename = ($test->slug ?: 'ai-test-'.$test->id).'-results.csv';

        return response()->streamDownload(function () use ($test) {
            $out = fopen('php://output', 'w');
            fputcsv($out, [
                'Test', 'Category', 'Difficulty', 'Model', 'Provider', 'Result status',
                'Quality', 'Accuracy', 'Prompt adherence', 'Creativity', 'Speed', 'Overall',
                'Latency ms', 'Input tokens', 'Output tokens', 'Estimated cost USD',
                'Model version', 'Verified', 'Tested at', 'Source label', 'Source URL',
            ]);

            foreach ($test->results as $result) {
                fputcsv($out, [
                    $test->name, $test->category, $test->difficulty, $result->model?->name,
                    $result->model?->company?->name, $result->status, $result->score_quality,
                    $result->score_accuracy, $result->score_prompt_adherence, $result->score_creativity,
                    $result->score_speed, $result->overall_score, $result->latency_ms,
                    $result->input_tokens, $result->output_tokens, $result->estimated_cost_usd,
                    $result->model_version, $result->is_verified ? 'Yes' : 'No',
                    $result->tested_at?->toIso8601String(), $result->source_label, $result->source_url,
                ]);
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function destroy(int $id)
    {
        $test = AiTest::with('results')->findOrFail($id);
        $test->results->pluck('evidence_path')->filter()->each(fn ($path) => Storage::disk('public')->delete($path));
        $test->delete();

        return redirect()->route('admin.testlab.index')->with('status', 'Test deleted.');
    }

    private function validateWeights(array $weights): void
    {
        $total = collect($this->normalizedWeights($weights))->sum();
        if ($total !== 100) {
            throw ValidationException::withMessages([
                'weights' => "Scoring weights must total exactly 100%. Current total: {$total}%.",
            ]);
        }
    }

    private function normalizedWeights(array $weights): array
    {
        return collect(config('test_lab.criteria', []))->mapWithKeys(function ($definition, $key) use ($weights) {
            return [$key => max(0, min(100, (int) ($weights[$key] ?? 0)))];
        })->all();
    }
}
