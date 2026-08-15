<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AiModel;
use App\Models\Company;
use App\Models\Tool;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AiModelController extends Controller
{
    public function index(Request $request)
    {
        $query = AiModel::query()->with(['company', 'tool']);

        if ($request->filled('search')) {
            $search = trim((string) $request->input('search'));
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('version', 'like', "%{$search}%")
                    ->orWhere('context_window', 'like', "%{$search}%");
            });
        }
        if ($request->filled('company_id')) $query->where('company_id', $request->integer('company_id'));
        if ($request->filled('tool_id')) $query->where('tool_id', $request->integer('tool_id'));
        if ($request->filled('status') && in_array($request->status, ['active', 'deprecated', 'preview'], true)) $query->where('status', $request->status);
        if ($request->filled('capability')) $query->whereJsonContains('capabilities', $request->capability);

        $models = $query->latest()->paginate(20)->withQueryString();
        $companies = Company::orderBy('name')->get(['id', 'name']);
        $tools = Tool::orderBy('name')->get(['id', 'name', 'company_id']);

        return view('models.index', compact('models', 'companies', 'tools'));
    }

    public function create()
    {
        return view('models.form', $this->options());
    }

    public function store(Request $request)
    {
        $model = AiModel::create($this->fromRequest($request));
        return redirect()->route('admin.models.show', $model->id)->with('status', 'Model created.');
    }

    public function show(int $id)
    {
        $model = AiModel::with(['company', 'tool'])->findOrFail($id);
        return view('models.show', compact('model'));
    }

    public function edit(int $id)
    {
        $model = AiModel::findOrFail($id);
        return view('models.form', ['model' => $model] + $this->options());
    }

    public function update(Request $request, int $id)
    {
        $model = AiModel::findOrFail($id);
        $model->update($this->fromRequest($request, $model));
        return redirect()->route('admin.models.show', $model->id)->with('status', 'Model updated.');
    }

    public function destroy(int $id)
    {
        AiModel::findOrFail($id)->delete();
        return redirect()->route('admin.models.index')->with('status', 'Model deleted.');
    }

    private function options(): array
    {
        return [
            'companies' => Company::orderBy('name')->get(),
            'tools' => Tool::orderBy('name')->get(),
        ];
    }

    private function fromRequest(Request $request, ?AiModel $model = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'company_id' => ['nullable', 'exists:companies,id'],
            'tool_id' => ['nullable', 'exists:tools,id'],
            'version' => ['nullable', 'string', 'max:50'],
            'release_date' => ['nullable', 'date'],
            'context_window' => ['nullable', 'string', 'max:50'],
            'input_price_per_million' => ['nullable', 'numeric', 'min:0'],
            'output_price_per_million' => ['nullable', 'numeric', 'min:0'],
            'status' => ['required', Rule::in(['active', 'deprecated', 'preview'])],
            'capabilities' => ['nullable', 'array'],
            'capabilities.*' => ['string', 'max:100'],
            'capability_notes' => ['nullable', 'string'],
            'benchmark_score' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ]);

        if (!empty($data['tool_id'])) {
            $tool = Tool::find($data['tool_id']);
            if (!empty($data['company_id']) && $tool?->company_id && (int) $tool->company_id !== (int) $data['company_id']) {
                throw ValidationException::withMessages(['tool_id' => 'Selected tool does not belong to the selected company.']);
            }
            if (empty($data['company_id']) && $tool?->company_id) {
                $data['company_id'] = $tool->company_id;
            }
        }

        $base = Str::slug(trim($data['name'] . ' ' . ($data['version'] ?? ''))) ?: 'ai-model';
        $slug = $base;
        $counter = 2;
        while (AiModel::where('slug', $slug)->when($model, fn ($q) => $q->where('id', '!=', $model->id))->exists()) {
            $slug = $base . '-' . $counter++;
        }
        $data['slug'] = $slug;

        return $data;
    }
}
