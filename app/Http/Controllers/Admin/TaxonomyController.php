<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Feature;
use App\Models\Subcategory;
use App\Models\Tag;
use App\Models\Tool;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class TaxonomyController extends Controller
{
    // One small map so every method below can stay generic instead of
    // repeating the same logic 4 times.
    private array $models = [
        'categories'    => Category::class,
        'subcategories' => Subcategory::class,
        'features'      => Feature::class,
        'tags'          => Tag::class,
    ];

    public function categories()
    {
        return $this->renderTab('categories');
    }

    public function subcategories()
    {
        return $this->renderTab('subcategories');
    }

    public function features()
    {
        return $this->renderTab('features');
    }

    public function tags()
    {
        return $this->renderTab('tags');
    }

    public function store(Request $request, string $type)
    {
        $modelClass = $this->modelFor($type);

        $data = $request->validate(['name' => ['required', 'string', 'max:255']]);
        $data['slug'] = Str::slug($data['name']);

        $modelClass::create($data);

        return redirect()->route("admin.taxonomy.{$type}")->with('status', 'Added.');
    }

    public function update(Request $request, string $type, int $id)
    {
        $modelClass = $this->modelFor($type);
        $term = $modelClass::findOrFail($id);

        $data = $request->validate(['name' => ['required', 'string', 'max:255']]);
        $data['slug'] = Str::slug($data['name']);

        $term->update($data);

        return redirect()->route("admin.taxonomy.{$type}")->with('status', 'Updated.');
    }

    public function destroy(string $type, int $id)
    {
        $modelClass = $this->modelFor($type);
        $modelClass::findOrFail($id)->delete();

        return redirect()->route("admin.taxonomy.{$type}")->with('status', 'Deleted.');
    }

    private function renderTab(string $type)
    {
        $modelClass = $this->modelFor($type);
        $terms = $modelClass::orderBy('name')->get();

        // Attach a `usage_count` onto each term, worked out differently
        // per type since they're stored differently on the tools table.
        $terms->each(function ($term) use ($type) {
            $term->usage_count = match ($type) {
                'categories'    => Tool::where('category_id', $term->id)->count(),
                'subcategories' => Tool::where('subcategory', $term->name)->count(),
                'features'      => Tool::whereJsonContains('capabilities', $term->name)->count(),
                'tags'          => Tool::whereJsonContains('tags', $term->name)->count(),
            };
        });

        return view('taxonomy.index', ['tab' => $type, 'terms' => $terms]);
    }

    private function modelFor(string $type): string
    {
        return $this->models[$type] ?? abort(404);
    }
}
