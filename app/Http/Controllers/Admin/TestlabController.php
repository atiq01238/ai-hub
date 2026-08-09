<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AiModel;
use App\Models\AiTest;
use App\Models\AiTestResult;
use Illuminate\Http\Request;

class TestlabController extends Controller
{
    public function index(Request $request)
    {
        $query = AiTest::withCount('results');

        if ($category = $request->query('category')) {
            $query->where('category', $category);
        }

        $tests = $query->latest()->paginate(10)->withQueryString();
        $models = AiModel::orderBy('name')->get();

        return view('testlab.index', compact('tests', 'models'));
    }

    // "Results" tab = same list, just a different entry point per the original nav.
    public function results(Request $request)
    {
        return $this->index($request);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'             => ['required', 'string', 'max:255'],
            'prompt'           => ['required', 'string'],
            'category'         => ['nullable', 'string', 'max:50'],
            'criteria'         => ['nullable', 'string', 'max:255'],
            'expected_output'  => ['nullable', 'string'],
            'model_ids'        => ['required', 'array', 'min:2', 'max:4'],
            'model_ids.*'      => ['exists:ai_models,id'],
        ]);

        $test = AiTest::create($data);

        // Create an empty result row per selected model — gives you a
        // "slot" to fill in each model's response/scores from the show page.
        foreach ($data['model_ids'] as $modelId) {
            AiTestResult::create(['ai_test_id' => $test->id, 'ai_model_id' => $modelId]);
        }

        return redirect()->route('admin.testlab.show', $test->id)->with('status', 'Test created — add each model\'s result below.');
    }

    public function show(int $id)
    {
        $test = AiTest::with(['results.model'])->findOrFail($id);

        return view('testlab.show', compact('test'));
    }

    public function updateResult(Request $request, int $resultId)
    {
        $data = $request->validate([
            'response_text'          => ['nullable', 'string'],
            'score_quality'           => ['nullable', 'integer', 'min:0', 'max:100'],
            'score_accuracy'          => ['nullable', 'integer', 'min:0', 'max:100'],
            'score_prompt_adherence'  => ['nullable', 'integer', 'min:0', 'max:100'],
            'score_creativity'        => ['nullable', 'integer', 'min:0', 'max:100'],
            'score_speed'             => ['nullable', 'integer', 'min:0', 'max:100'],
        ]);

        $result = AiTestResult::findOrFail($resultId);
        $result->update($data);

        return redirect()->route('admin.testlab.show', $result->ai_test_id)->with('status', 'Result saved.');
    }

    public function destroy(int $id)
    {
        AiTest::findOrFail($id)->delete();

        return redirect()->route('admin.testlab.index')->with('status', 'Test deleted.');
    }
}
