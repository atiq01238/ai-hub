<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Company;
use App\Models\Tool;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ToolController extends Controller
{
    public function index(Request $request)
    {
        $tools = Tool::with(['company', 'category'])
            ->latest()
            ->paginate(20);

        return view('tools.index', compact('tools'));
    }

    public function create()
    {
        $companies = Company::orderBy('name')->get();
        $categories = Category::orderBy('name')->get();

        return view('tools.form', compact('companies', 'categories'));
    }

    public function store(Request $request)
    {
        Tool::create($this->fromRequest($request));

        return redirect()
            ->route('admin.tools.index')
            ->with('status', 'Tool created.');
    }

    public function show(int $id)
    {
        $tool = Tool::with(['company', 'category'])->findOrFail($id);

        return view('tools.show', compact('tool'));
    }

    public function edit(int $id)
    {
        $tool = Tool::findOrFail($id);
        $companies = Company::orderBy('name')->get();
        $categories = Category::orderBy('name')->get();

        return view('tools.form', compact('tool', 'companies', 'categories'));
    }

    public function update(Request $request, int $id)
    {
        $tool = Tool::findOrFail($id);
        $tool->update($this->fromRequest($request, $tool));

        return redirect()
            ->route('admin.tools.show', $tool->id)
            ->with('status', 'Tool updated.');
    }

    public function destroy(int $id)
    {
        $tool = Tool::findOrFail($id);

        // Clean up any uploaded files so they don't pile up unused on disk.
        foreach (['logo_path', 'cover_image_path', 'og_image_path'] as $column) {
            if ($tool->{$column}) {
                Storage::disk('public')->delete($tool->{$column});
            }
        }

        $tool->delete();

        return redirect()
            ->route('admin.tools.index')
            ->with('status', 'Tool deleted.');
    }

    private function fromRequest(Request $request, ?Tool $tool = null): array
    {
        $data = $request->validate([
            'name'               => ['required', 'string', 'max:255'],
            'company_id'         => ['nullable', 'exists:companies,id'],
            'category_id'        => ['nullable', 'exists:categories,id'],
            'subcategory'        => ['nullable', 'string', 'max:255'],
            'website'            => ['nullable', 'url', 'max:255'],
            'launch_date'        => ['nullable', 'date'],
            'short_description'  => ['nullable', 'string', 'max:255'],
            'description'        => ['nullable', 'string'],
            'status'             => ['required', 'in:draft,published,archived'],
            'tags_input'         => ['nullable', 'string'],
            'capabilities'       => ['nullable', 'array'],
            'platforms'          => ['nullable', 'array'],
            'pricing_models'     => ['nullable', 'array'],
            'seo_title'          => ['nullable', 'string', 'max:255'],
            'meta_description'   => ['nullable', 'string', 'max:255'],
            'slug'               => ['nullable', 'string', 'max:255'],
            'logo'               => ['nullable', 'image', 'max:2048'],   // 2MB
            'cover_image'        => ['nullable', 'image', 'max:4096'],   // 4MB
            'og_image'           => ['nullable', 'image', 'max:2048'],
        ]);

        $data['tags'] = collect(explode(',', $data['tags_input'] ?? ''))
            ->map(fn ($tag) => trim($tag))
            ->filter()
            ->values()
            ->all();
        unset($data['tags_input']);

        $data['slug'] = $data['slug'] ?: Str::slug($data['name']);

        if ($data['status'] === 'published' && ! ($tool?->published_at)) {
            $data['published_at'] = now();
        }

        // For each image field: only touch it if a NEW file was uploaded.
        // Otherwise leave the existing path alone (don't overwrite with null).
        foreach ([
            'logo'        => 'logo_path',
            'cover_image' => 'cover_image_path',
            'og_image'    => 'og_image_path',
        ] as $inputName => $column) {
            if ($request->hasFile($inputName)) {
                if ($tool?->{$column}) {
                    Storage::disk('public')->delete($tool->{$column});
                }
                $data[$column] = $request->file($inputName)->store('tools', 'public');
            }
            unset($data[$inputName]);
        }

        return $data;
    }
}
