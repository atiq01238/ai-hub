<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Feature;
use App\Models\Subcategory;
use App\Models\Tag;
use App\Models\Tool;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class TaxonomyController extends Controller
{
    private array $models = [
        'categories' => Category::class,
        'subcategories' => Subcategory::class,
        'features' => Feature::class,
        'tags' => Tag::class,
    ];

    public function categories() { return $this->renderTab('categories'); }
    public function subcategories() { return $this->renderTab('subcategories'); }
    public function features() { return $this->renderTab('features'); }
    public function tags() { return $this->renderTab('tags'); }

    public function store(Request $request, string $type)
    {
        $modelClass = $this->modelFor($type);
        $table = (new $modelClass)->getTable();
        $data = $request->validate(['name' => ['required', 'string', 'max:255', Rule::unique($table, 'name')]]);
        $data['slug'] = $this->uniqueSlug($modelClass, $data['name']);
        $modelClass::create($data);
        return redirect()->route("admin.taxonomy.{$type}")->with('status', 'Added.');
    }

    public function update(Request $request, string $type, int $id)
    {
        $modelClass = $this->modelFor($type);
        $term = $modelClass::findOrFail($id);
        $oldName = $term->name;
        $table = $term->getTable();
        $data = $request->validate(['name' => ['required', 'string', 'max:255', Rule::unique($table, 'name')->ignore($term->id)]]);
        $data['slug'] = $this->uniqueSlug($modelClass, $data['name'], $term->id);

        DB::transaction(function () use ($term, $type, $oldName, $data) {
            $term->update($data);
            if ($type === 'subcategories') {
                Tool::where('subcategory_id', $term->id)->update(['subcategory' => $data['name']]);
            } elseif ($type === 'features') {
                $term->tools()->each(function (Tool $tool) {
                    $tool->update(['capabilities' => $tool->featureTerms()->orderBy('name')->pluck('name')->all()]);
                });
            } elseif ($type === 'tags') {
                $term->tools()->each(function (Tool $tool) {
                    $tool->update(['tags' => $tool->tagTerms()->orderBy('name')->pluck('name')->all()]);
                });
            }
        });

        return redirect()->route("admin.taxonomy.{$type}")->with('status', 'Updated.');
    }

    public function destroy(string $type, int $id)
    {
        $modelClass = $this->modelFor($type);
        $term = $modelClass::findOrFail($id);

        DB::transaction(function () use ($term, $type) {
            if ($type === 'features') {
                $tools = $term->tools()->get();
                $term->tools()->detach();
                $term->delete();
                $tools->each(fn (Tool $tool) => $tool->update(['capabilities' => $tool->featureTerms()->orderBy('name')->pluck('name')->all()]));
                return;
            }
            if ($type === 'tags') {
                $tools = $term->tools()->get();
                $term->tools()->detach();
                $term->delete();
                $tools->each(fn (Tool $tool) => $tool->update(['tags' => $tool->tagTerms()->orderBy('name')->pluck('name')->all()]));
                return;
            }
            $term->delete();
        });

        return redirect()->route("admin.taxonomy.{$type}")->with('status', 'Deleted safely.');
    }

    private function renderTab(string $type)
    {
        $modelClass = $this->modelFor($type);
        $terms = $modelClass::orderBy('name')->get();
        $terms->each(function ($term) use ($type) {
            $term->usage_count = match ($type) {
                'categories' => Tool::where('category_id', $term->id)->count(),
                'subcategories' => Tool::where('subcategory_id', $term->id)->count(),
                'features' => $term->tools()->count(),
                'tags' => $term->tools()->count(),
            };
        });
        return view('taxonomy.index', ['tab' => $type, 'terms' => $terms]);
    }

    private function modelFor(string $type): string
    {
        abort_unless(isset($this->models[$type]), 404);
        return $this->models[$type];
    }

    private function uniqueSlug(string $modelClass, string $name, ?int $ignoreId = null): string
    {
        $base = Str::slug($name) ?: 'term';
        $slug = $base;
        $i = 2;
        while ($modelClass::where('slug', $slug)->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))->exists()) {
            $slug = $base . '-' . $i++;
        }
        return $slug;
    }
}
