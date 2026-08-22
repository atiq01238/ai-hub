<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Company;
use App\Models\Feature;
use App\Models\Subcategory;
use App\Models\Tag;
use App\Models\Tool;
use App\Models\UseCase;
use App\Support\MediaUrl;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ToolController extends Controller
{
    public function index(Request $request)
    {
        $query = Tool::query()->with(['company', 'category', 'subcategoryTerm'])->withCount('models');

        if ($request->filled('search')) {
            $search = trim((string) $request->input('search'));
            $query->where(fn ($q) => $q->where('name','like',"%{$search}%")
                ->orWhere('short_description','like',"%{$search}%")
                ->orWhere('description','like',"%{$search}%"));
        }
        if ($request->filled('category_id')) $query->where('category_id', $request->integer('category_id'));
        if ($request->filled('company_id')) $query->where('company_id', $request->integer('company_id'));
        if ($request->filled('status') && in_array($request->status, ['draft','published','archived'], true)) $query->where('status', $request->status);
        if ($request->filled('pricing')) $query->whereJsonContains('pricing_models', $request->pricing);
        if ($request->filled('rating')) $query->where('rating', '>=', (float) $request->rating);

        $tools = $query->latest()->paginate(20)->withQueryString();
        $companies = Company::orderBy('name')->get(['id','name']);
        $categories = Category::product()->active()->orderBy('sort_order')->orderBy('name')->get(['id','name']);
        return view('tools.index', compact('tools','companies','categories'));
    }

    public function create()
    {
        return view('tools.form', $this->formOptions());
    }

    public function store(Request $request)
    {
        [$data,$featureIds,$tagIds,$useCaseIds] = $this->fromRequest($request);
        $tool = DB::transaction(function () use ($data,$featureIds,$tagIds,$useCaseIds) {
            $tool = Tool::create($data);
            $tool->featureTerms()->sync($featureIds);
            $tool->tagTerms()->sync($tagIds);
            $tool->useCaseTerms()->sync($useCaseIds);
            return $tool;
        });
        return redirect()->route('admin.tools.show',$tool->id)->with('status','Tool created.');
    }

    public function show(int $id)
    {
        $tool = Tool::with(['company','category','subcategoryTerm','featureTerms','tagTerms','useCaseTerms','models'])
            ->withCount(['models','reviews'])->findOrFail($id);
        return view('tools.show', compact('tool'));
    }

    public function edit(int $id)
    {
        $tool = Tool::with(['featureTerms:id','tagTerms:id','useCaseTerms:id'])->findOrFail($id);
        return view('tools.form', ['tool'=>$tool] + $this->formOptions());
    }

    public function update(Request $request, int $id)
    {
        $tool = Tool::findOrFail($id);
        [$data,$featureIds,$tagIds,$useCaseIds] = $this->fromRequest($request,$tool);
        DB::transaction(function () use ($tool,$data,$featureIds,$tagIds,$useCaseIds) {
            $tool->update($data);
            $tool->featureTerms()->sync($featureIds);
            $tool->tagTerms()->sync($tagIds);
            $tool->useCaseTerms()->sync($useCaseIds);
        });
        return redirect()->route('admin.tools.show',$tool->id)->with('status','Tool updated.');
    }

    public function destroy(int $id)
    {
        $tool = Tool::withCount('models')->findOrFail($id);
        foreach (['logo_path','cover_image_path','og_image_path'] as $column) {
            if ($tool->{$column}) {
                $path = MediaUrl::diskPath($tool->{$column});
                if ($path) Storage::disk('public')->delete($path);
            }
        }
        $tool->delete();
        return redirect()->route('admin.tools.index')->with('status','Tool deleted. Linked models were kept and detached safely.');
    }

    private function formOptions(): array
    {
        return [
            'companies' => Company::orderBy('name')->get(),
            'categories' => Category::product()->active()->orderBy('sort_order')->orderBy('name')->get(),
            'subcategories' => Subcategory::active()->with('category')->orderBy('sort_order')->orderBy('name')->get(),
            'features' => Feature::active()->orderBy('group')->orderBy('sort_order')->orderBy('name')->get(),
            'tags' => Tag::active()->orderBy('sort_order')->orderBy('name')->get(),
            'useCases' => UseCase::active()->orderBy('sort_order')->orderBy('name')->get(),
        ];
    }

    private function fromRequest(Request $request, ?Tool $tool = null): array
    {
        $data = $request->validate([
            'name' => ['required','string','max:255'],
            'company_id' => ['nullable','exists:companies,id'],
            'category_id' => ['nullable', Rule::exists('categories','id')->where(fn($q)=>$q->where('type','product')->where('is_active',true))],
            'subcategory_id' => ['nullable', Rule::exists('subcategories','id')->where(fn($q)=>$q->where('is_active',true))],
            'website' => ['nullable','url','max:255'],
            'launch_date' => ['nullable','date'],
            'short_description' => ['nullable','string','max:255'],
            'description' => ['nullable','string'],
            'status' => ['required', Rule::in(['draft','published','archived'])],
            'feature_ids' => ['nullable','array'],
            'feature_ids.*' => ['integer', Rule::exists('features','id')->where(fn($q)=>$q->where('is_active',true))],
            'tag_ids' => ['nullable','array'],
            'tag_ids.*' => ['integer', Rule::exists('tags','id')->where(fn($q)=>$q->where('is_active',true))],
            'use_case_ids' => ['nullable','array'],
            'use_case_ids.*' => ['integer', Rule::exists('use_cases','id')->where(fn($q)=>$q->where('is_active',true))],
            'platforms' => ['nullable','array'],
            'pricing_models' => ['nullable','array'],
            'seo_title' => ['nullable','string','max:255'],
            'meta_description' => ['nullable','string','max:255'],
            'slug' => ['nullable','string','max:255'],
            'logo' => ['nullable','image','max:2048'],
            'cover_image' => ['nullable','image','max:4096'],
            'og_image' => ['nullable','image','max:2048'],
        ]);

        if (!empty($data['subcategory_id'])) {
            $subcategory = Subcategory::find($data['subcategory_id']);
            if (!$subcategory || empty($data['category_id']) || (int)$subcategory->category_id !== (int)$data['category_id']) {
                throw ValidationException::withMessages(['subcategory_id'=>'Selected subcategory does not belong to the selected product category.']);
            }
        }

        $featureIds = collect($data['feature_ids'] ?? [])->map(fn($id)=>(int)$id)->unique()->values()->all();
        $tagIds = collect($data['tag_ids'] ?? [])->map(fn($id)=>(int)$id)->unique()->values()->all();
        $useCaseIds = collect($data['use_case_ids'] ?? [])->map(fn($id)=>(int)$id)->unique()->values()->all();
        unset($data['feature_ids'],$data['tag_ids'],$data['use_case_ids']);

        $data['capabilities'] = Feature::whereIn('id',$featureIds)->orderBy('name')->pluck('name')->all();
        $data['tags'] = Tag::whereIn('id',$tagIds)->orderBy('name')->pluck('name')->all();
        $data['subcategory'] = !empty($data['subcategory_id']) ? Subcategory::whereKey($data['subcategory_id'])->value('name') : null;

        $baseSlug = Str::slug($data['slug'] ?: $data['name']) ?: 'tool';
        $slug = $baseSlug; $counter = 2;
        while (Tool::where('slug',$slug)->when($tool,fn($q)=>$q->where('id','!=',$tool->id))->exists()) $slug = $baseSlug.'-'.$counter++;
        $data['slug'] = $slug;
        $data['published_at'] = $data['status']==='published' ? ($tool?->published_at ?: now()) : null;

        foreach (['logo'=>'logo_path','cover_image'=>'cover_image_path','og_image'=>'og_image_path'] as $input=>$column) {
            if ($request->hasFile($input)) {
                if ($tool?->{$column}) {
                    $path = MediaUrl::diskPath($tool->{$column});
                    if ($path) Storage::disk('public')->delete($path);
                }
                $data[$column] = $request->file($input)->store('tools','public');
            }
            unset($data[$input]);
        }

        return [$data,$featureIds,$tagIds,$useCaseIds];
    }
}
