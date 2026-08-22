<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AiModel;
use App\Models\Article;
use App\Models\Category;
use App\Models\Feature;
use App\Models\Subcategory;
use App\Models\Tag;
use App\Models\Tool;
use App\Models\UseCase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class TaxonomyController extends Controller
{
    public function categories() { return $this->renderTab('categories'); }
    public function subcategories() { return $this->renderTab('subcategories'); }
    public function features() { return $this->renderTab('features'); }
    public function useCases() { return $this->renderTab('use-cases'); }
    public function tags() { return $this->renderTab('tags'); }
    public function contentTopics() { return $this->renderTab('content-topics'); }

    public function store(Request $request, string $type)
    {
        $definition = $this->definition($type);
        $modelClass = $definition['model'];
        $data = $this->validated($request, $type, null);
        $data['slug'] = $this->uniqueSlug($modelClass, $data['name']);
        if ($type === 'categories') $data['type'] = 'product';
        if ($type === 'content-topics') $data['type'] = 'content';
        $modelClass::create($data);

        return redirect()->route("admin.taxonomy.{$definition['route']}")->with('status', 'Taxonomy term added.');
    }

    public function update(Request $request, string $type, int $id)
    {
        $definition = $this->definition($type);
        $modelClass = $definition['model'];
        $term = $modelClass::findOrFail($id);
        $oldName = $term->name;
        $data = $this->validated($request, $type, $term);
        $data['slug'] = $this->uniqueSlug($modelClass, $data['name'], $term->id);
        if ($type === 'categories') $data['type'] = 'product';
        if ($type === 'content-topics') $data['type'] = 'content';

        DB::transaction(function () use ($term, $type, $oldName, $data) {
            $term->update($data);

            if ($type === 'subcategories') {
                Tool::where('subcategory_id', $term->id)->update(['subcategory' => $term->name]);
            } elseif ($type === 'features') {
                $term->tools()->each(fn (Tool $tool) => $this->syncToolFeatureJson($tool));
                $term->models()->each(fn (AiModel $model) => $this->syncModelFeatureJson($model));
            } elseif ($type === 'tags') {
                $term->tools()->each(fn (Tool $tool) => $tool->updateQuietly(['tags' => $tool->tagTerms()->orderBy('name')->pluck('name')->all()]));
            } elseif ($type === 'content-topics') {
                Article::where('category_id', $term->id)->update(['category' => $term->name]);
            }
        });

        return redirect()->route("admin.taxonomy.{$this->definition($type)['route']}")->with('status', "{$oldName} updated.");
    }

    public function destroy(string $type, int $id)
    {
        $definition = $this->definition($type);
        $term = $definition['model']::findOrFail($id);
        $usage = $this->usage($term, $type);

        if (in_array($type, ['categories', 'content-topics', 'subcategories'], true) && $usage['total'] > 0) {
            throw ValidationException::withMessages([
                'taxonomy' => "{$term->name} is still connected to {$usage['total']} record(s). Reassign those records before deleting this taxonomy term.",
            ]);
        }

        DB::transaction(function () use ($term, $type) {
            if ($type === 'features') {
                $tools = $term->tools()->get();
                $models = $term->models()->get();
                $term->tools()->detach();
                $term->models()->detach();
                $term->delete();
                $tools->each(fn (Tool $tool) => $this->syncToolFeatureJson($tool));
                $models->each(fn (AiModel $model) => $this->syncModelFeatureJson($model));
                return;
            }
            if ($type === 'tags') {
                $tools = $term->tools()->get();
                $term->tools()->detach();
                $term->models()->detach();
                $term->articles()->detach();
                $term->delete();
                $tools->each(fn (Tool $tool) => $tool->updateQuietly(['tags' => $tool->tagTerms()->orderBy('name')->pluck('name')->all()]));
                return;
            }
            if ($type === 'use-cases') {
                $term->tools()->detach();
                $term->models()->detach();
                $term->delete();
                return;
            }
            $term->delete();
        });

        return redirect()->route("admin.taxonomy.{$definition['route']}")->with('status', 'Taxonomy term deleted safely.');
    }

    private function renderTab(string $type)
    {
        $definition = $this->definition($type);
        $query = $definition['model']::query();
        if ($type === 'categories') $query->product();
        if ($type === 'content-topics') $query->content();
        if ($type === 'subcategories') $query->with('category');

        $terms = $query->orderBy('sort_order')->orderBy('name')->get();
        $terms->each(function ($term) use ($type) {
            $term->usage = $this->usage($term, $type);
            $term->usage_count = $term->usage['total'];
        });

        return view('taxonomy.index', [
            'tab' => $type,
            'terms' => $terms,
            'productCategories' => Category::product()->active()->orderBy('sort_order')->orderBy('name')->get(['id','name']),
        ]);
    }

    private function validated(Request $request, string $type, $term): array
    {
        $definition = $this->definition($type);
        $table = (new $definition['model'])->getTable();
        $rules = [
            'name' => ['required','string','max:255', Rule::unique($table, 'name')->ignore($term?->id)],
            'short_description' => ['nullable','string','max:500'],
            'description' => ['nullable','string','max:5000'],
            'meta_title' => ['nullable','string','max:80'],
            'meta_description' => ['nullable','string','max:180'],
            'sort_order' => ['nullable','integer','min:0','max:65535'],
            'is_active' => ['nullable','boolean'],
            'is_indexable' => ['nullable','boolean'],
        ];

        if ($type === 'subcategories') {
            $rules['category_id'] = ['required', Rule::exists('categories', 'id')->where(fn ($q) => $q->where('type', 'product')->where('is_active', true))];
        }
        if ($type === 'features') {
            $rules['group'] = ['nullable','string','max:80'];
            $rules['icon'] = ['nullable','string','max:80'];
        }
        if ($type === 'use-cases') $rules['icon'] = ['nullable','string','max:80'];

        $data = $request->validate($rules);
        if (in_array('is_active', (new $definition['model'])->getFillable(), true)) $data['is_active'] = $request->boolean('is_active');
        if (in_array('is_indexable', (new $definition['model'])->getFillable(), true)) $data['is_indexable'] = $request->boolean('is_indexable');
        $data['sort_order'] = (int) ($data['sort_order'] ?? 0);
        return $data;
    }

    private function usage($term, string $type): array
    {
        return match ($type) {
            'categories' => [
                'tools' => Tool::where('category_id', $term->id)->count(),
                'models' => AiModel::whereHas('tool', fn ($q) => $q->where('category_id', $term->id))->count(),
                'subcategories' => Subcategory::where('category_id', $term->id)->count(),
                'total' => Tool::where('category_id', $term->id)->count() + Subcategory::where('category_id', $term->id)->count(),
            ],
            'content-topics' => [
                'articles' => Article::where('category_id', $term->id)->count(),
                'total' => Article::where('category_id', $term->id)->count(),
            ],
            'subcategories' => [
                'tools' => Tool::where('subcategory_id', $term->id)->count(),
                'models' => AiModel::whereHas('tool', fn ($q) => $q->where('subcategory_id', $term->id))->count(),
                'total' => Tool::where('subcategory_id', $term->id)->count(),
            ],
            'features' => [
                'tools' => $term->tools()->count(),
                'models' => $term->models()->count(),
                'total' => $term->tools()->count() + $term->models()->count(),
            ],
            'use-cases' => [
                'tools' => $term->tools()->count(),
                'models' => $term->models()->count(),
                'total' => $term->tools()->count() + $term->models()->count(),
            ],
            'tags' => [
                'tools' => $term->tools()->count(),
                'models' => $term->models()->count(),
                'articles' => $term->articles()->count(),
                'total' => $term->tools()->count() + $term->models()->count() + $term->articles()->count(),
            ],
        };
    }

    private function definition(string $type): array
    {
        $definitions = [
            'categories' => ['model'=>Category::class,'route'=>'categories'],
            'content-topics' => ['model'=>Category::class,'route'=>'content-topics'],
            'subcategories' => ['model'=>Subcategory::class,'route'=>'subcategories'],
            'features' => ['model'=>Feature::class,'route'=>'features'],
            'use-cases' => ['model'=>UseCase::class,'route'=>'use-cases'],
            'tags' => ['model'=>Tag::class,'route'=>'tags'],
        ];
        abort_unless(isset($definitions[$type]), 404);
        return $definitions[$type];
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

    private function syncToolFeatureJson(Tool $tool): void
    {
        $tool->updateQuietly(['capabilities' => $tool->featureTerms()->orderBy('name')->pluck('name')->all()]);
    }

    private function syncModelFeatureJson(AiModel $model): void
    {
        $model->updateQuietly(['capabilities' => $model->featureTerms()->orderBy('name')->pluck('name')->all()]);
    }
}
