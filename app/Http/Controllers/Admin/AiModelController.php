<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AiModel;
use App\Models\Company;
use App\Models\Tool;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AiModelController extends Controller
{
    public function index(Request $request)
    {
        $models = AiModel::with(['company', 'tool'])
            ->latest()
            ->paginate(20);

        return view('models.index', compact('models'));
    }

    public function create()
    {
        $companies = Company::orderBy('name')->get();
        $tools = Tool::orderBy('name')->get();

        return view('models.form', compact('companies', 'tools'));
    }

    public function store(Request $request)
    {
        AiModel::create($this->fromRequest($request));

        return redirect()
            ->route('admin.models.index')
            ->with('status', 'Model created.');
    }

    public function show(int $id)
    {
        $model = AiModel::with(['company', 'tool'])->findOrFail($id);

        return view('models.show', ['model' => $model]);
    }

    public function edit(int $id)
    {
        $model = AiModel::findOrFail($id);
        $companies = Company::orderBy('name')->get();
        $tools = Tool::orderBy('name')->get();

        return view('models.form', ['model' => $model] + compact('companies', 'tools'));
    }

    public function update(Request $request, int $id)
    {
        $model = AiModel::findOrFail($id);
        $model->update($this->fromRequest($request));

        return redirect()
            ->route('admin.models.show', $model->id)
            ->with('status', 'Model updated.');
    }

    public function destroy(int $id)
    {
        AiModel::findOrFail($id)->delete();

        return redirect()
            ->route('admin.models.index')
            ->with('status', 'Model deleted.');
    }

    private function fromRequest(Request $request): array
    {
        $data = $request->validate([
            'name'                      => ['required', 'string', 'max:255'],
            'company_id'                => ['nullable', 'exists:companies,id'],
            'tool_id'                   => ['nullable', 'exists:tools,id'],
            'version'                   => ['nullable', 'string', 'max:50'],
            'release_date'              => ['nullable', 'date'],
            'context_window'            => ['nullable', 'string', 'max:50'],
            'input_price_per_million'   => ['nullable', 'numeric', 'min:0'],
            'output_price_per_million'  => ['nullable', 'numeric', 'min:0'],
            'status'                    => ['required', 'in:active,deprecated,preview'],
            'capabilities'              => ['nullable', 'array'],
            'capability_notes'          => ['nullable', 'string'],
            'benchmark_score'           => ['nullable', 'numeric', 'min:0', 'max:100'],
        ]);

        $data['slug'] = Str::slug($data['name'] . '-' . ($data['version'] ?? uniqid()));

        return $data;
    }
}
