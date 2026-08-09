<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AiModel;
use App\Models\Comparison;
use App\Models\Tool;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ComparisonController extends Controller
{
    public function index(Request $request)
    {
        $query = Comparison::latest();

        if ($search = $request->query('search')) {
            $query->where('title', 'like', "%{$search}%");
        }

        if ($type = $request->query('type')) {
            $query->where('comparable_type', $type);
        }

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        $comparisons = $query->paginate(20)->withQueryString();

        return view('comparisons.index', compact('comparisons'));
    }

    // "builder" IS the create form now — simplified from the 6-step wizard.
    public function builder()
    {
        $tools = Tool::orderBy('name')->get();
        $models = AiModel::orderBy('name')->get();

        return view('comparisons.builder', compact('tools', 'models'));
    }

    public function store(Request $request)
    {
        Comparison::create($this->fromRequest($request));

        return redirect()
            ->route('admin.comparisons.index')
            ->with('status', 'Comparison created.');
    }

    public function show(int $id)
    {
        $comparison = Comparison::findOrFail($id);
        $comparison->increment('views'); // a real page-view counter, since this IS the detail page

        $items = $comparison->items();

        return view('comparisons.show', compact('comparison', 'items'));
    }

    public function edit(int $id)
    {
        $comparison = Comparison::findOrFail($id);
        $tools = Tool::orderBy('name')->get();
        $models = AiModel::orderBy('name')->get();

        return view('comparisons.builder', compact('comparison', 'tools', 'models'));
    }

    public function update(Request $request, int $id)
    {
        $comparison = Comparison::findOrFail($id);
        $comparison->update($this->fromRequest($request));

        return redirect()
            ->route('admin.comparisons.show', $comparison->id)
            ->with('status', 'Comparison updated.');
    }

    public function destroy(int $id)
    {
        Comparison::findOrFail($id)->delete();

        return redirect()
            ->route('admin.comparisons.index')
            ->with('status', 'Comparison deleted.');
    }

    private function fromRequest(Request $request): array
    {
        $data = $request->validate([
            'title'      => ['required', 'string', 'max:255'],
            'status'     => ['required', 'in:draft,published'],
            'tool_ids'   => ['nullable', 'array'],
            'model_ids'  => ['nullable', 'array'],
        ]);

        $toolIds = array_filter($data['tool_ids'] ?? []);
        $modelIds = array_filter($data['model_ids'] ?? []);

        // Exactly one of the two groups must be used — you can't mix
        // tools and models in a single comparison.
        if (empty($toolIds) && empty($modelIds)) {
            throw ValidationException::withMessages([
                'tool_ids' => 'Select 2–4 tools OR 2–4 models to compare.',
            ]);
        }
        if (! empty($toolIds) && ! empty($modelIds)) {
            throw ValidationException::withMessages([
                'tool_ids' => 'Choose either Tools or Models, not both.',
            ]);
        }

        $chosen = ! empty($toolIds) ? $toolIds : $modelIds;
        if (count($chosen) < 2 || count($chosen) > 4) {
            throw ValidationException::withMessages([
                'tool_ids' => 'Select between 2 and 4 items.',
            ]);
        }

        return [
            'title'           => $data['title'],
            'slug'            => Str::slug($data['title']) . '-' . Str::random(6),
            'comparable_type' => ! empty($toolIds) ? 'tool' : 'model',
            'item_ids'        => array_values(array_map('intval', $chosen)),
            'status'          => $data['status'],
        ];
    }
}
