<?php

namespace App\Console\Commands;

use App\Models\AiModel;
use App\Models\Article;
use App\Models\Category;
use App\Models\Feature;
use App\Models\Subcategory;
use App\Models\Tag;
use App\Models\Tool;
use App\Models\UseCase;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class SyncTaxonomyV2 extends Command
{
    protected $signature = 'taxonomy:v2-sync {--dry-run : Preview changes and roll them back} {--no-infer : Do not infer use cases from capabilities}';
    protected $description = 'Sync the curated Taxonomy v2 dataset and normalize existing tool/model/content taxonomy safely.';

    private array $legacyToolAssignments = [];

    private array $stats = [
        'categories' => 0,
        'content_topics' => 0,
        'subcategories' => 0,
        'features' => 0,
        'use_cases' => 0,
        'tags' => 0,
        'tools_normalized' => 0,
        'models_normalized' => 0,
        'articles_normalized' => 0,
        'legacy_tools_mapped' => 0,
    ];

    public function handle(): int
    {
        foreach (['categories','subcategories','features','tags','tools','ai_models'] as $table) {
            if (!Schema::hasTable($table)) {
                $this->error("Required table missing: {$table}. Run php artisan migrate first.");
                return self::FAILURE;
            }
        }
        if (!Schema::hasTable('use_cases') || !Schema::hasTable('ai_model_feature')) {
            $this->error('Taxonomy v2 schema is not installed. Run php artisan migrate first.');
            return self::FAILURE;
        }

        $dryRun = (bool) $this->option('dry-run');
        DB::beginTransaction();

        try {
            $this->captureLegacyToolAssignments();
            $this->syncProductCategories();
            $this->syncContentTopics();
            $this->syncSubcategories();
            $this->syncFeatures();
            $this->syncUseCases();
            $this->syncTags();
            $this->applyLegacyToolAssignments();
            $this->normalizeTools();
            $this->normalizeModels();
            $this->normalizeArticles();
            $this->finalizeLegacyTerms();

            if ($dryRun) {
                DB::rollBack();
                $this->warn('Dry run complete. No database changes were saved.');
            } else {
                DB::commit();
                $this->info('Taxonomy v2 synchronized successfully.');
            }
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }

        $this->table(['Area','Processed'], collect($this->stats)->map(fn ($value, $key) => [Str::headline($key), $value])->values()->all());
        return self::SUCCESS;
    }


    private function captureLegacyToolAssignments(): void
    {
        foreach (config('taxonomy_v2.legacy_category_defaults', []) as $legacyName => $defaults) {
            $categoryIds = Category::query()
                ->where(fn ($q) => $q->whereRaw('LOWER(name) = ?', [Str::lower($legacyName)])
                    ->orWhere('slug', Str::slug($legacyName)))
                ->pluck('id');

            if ($categoryIds->isEmpty()) continue;

            Tool::whereIn('category_id', $categoryIds)->pluck('id')->each(function ($toolId) use ($legacyName, $defaults) {
                $this->legacyToolAssignments[(int) $toolId] = [
                    'legacy_name' => $legacyName,
                    'defaults' => $defaults,
                ];
            });
        }
    }

    private function applyLegacyToolAssignments(): void
    {
        foreach ($this->legacyToolAssignments as $toolId => $assignment) {
            $tool = Tool::find($toolId);
            if (!$tool) continue;

            $defaults = $assignment['defaults'] ?? [];
            $subcategory = null;
            if (!empty($defaults['subcategory'])) {
                $subcategory = Subcategory::active()
                    ->whereRaw('LOWER(name) = ?', [Str::lower($defaults['subcategory'])])
                    ->first();
            }

            if ($subcategory) {
                $tool->category_id = $subcategory->category_id;
                $tool->subcategory_id = $subcategory->id;
                $tool->subcategory = $subcategory->name;
            }

            $tagIds = Tag::active()->whereIn('name', $defaults['tags'] ?? [])->pluck('id')->all();
            if ($tagIds) $tool->tagTerms()->syncWithoutDetaching($tagIds);

            $useCaseIds = UseCase::active()->whereIn('name', $defaults['use_cases'] ?? [])->pluck('id')->all();
            if ($useCaseIds) $tool->useCaseTerms()->syncWithoutDetaching($useCaseIds);

            $tool->saveQuietly();
            $this->stats['legacy_tools_mapped']++;
        }
    }

    private function syncProductCategories(): void
    {
        foreach (config('taxonomy_v2.product_categories', []) as $order => $definition) {
            $category = $this->canonicalCategory($definition, 'product', $order + 1);
            $this->stats['categories']++;

            foreach ($definition['legacy'] ?? [] as $legacyName) {
                Category::query()
                    ->where('id', '!=', $category->id)
                    ->where(fn ($q) => $q->whereRaw('LOWER(name) = ?', [Str::lower($legacyName)])->orWhere('slug', Str::slug($legacyName)))
                    ->get()
                    ->each(function (Category $legacy) use ($category) {
                        Tool::where('category_id', $legacy->id)->update(['category_id' => $category->id]);
                        Subcategory::where('category_id', $legacy->id)->update(['category_id' => $category->id]);
                        $legacy->update(['is_active' => false, 'is_indexable' => false]);
                    });
            }
        }
    }

    private function syncContentTopics(): void
    {
        foreach (config('taxonomy_v2.content_topics', []) as $order => $definition) {
            $canonical = Category::where('slug', $definition['slug'])->first();

            if (!$canonical) {
                $legacy = Category::query()
                    ->whereIn('name', array_merge([$definition['name']], $definition['legacy'] ?? []))
                    ->whereDoesntHave('tools')
                    ->first();
                $canonical = $legacy ?: new Category();
            }

            $canonical->fill($this->categoryPayload($definition, 'content', $order + 1))->save();
            $this->stats['content_topics']++;

            $legacyNames = array_merge([$definition['name']], $definition['legacy'] ?? []);
            if (Schema::hasTable('articles')) {
                Article::query()
                    ->where(function ($q) use ($legacyNames, $canonical) {
                        $q->whereIn('category', $legacyNames)
                            ->orWhereIn('category_id', Category::whereIn('name', $legacyNames)->pluck('id'))
                            ->orWhere('category_id', $canonical->id);
                    })
                    ->update(['category_id' => $canonical->id, 'category' => $canonical->name]);
            }

            Category::query()
                ->where('id', '!=', $canonical->id)
                ->whereIn('name', $legacyNames)
                ->whereDoesntHave('tools')
                ->update(['is_active' => false, 'is_indexable' => false]);
        }
    }

    private function canonicalCategory(array $definition, string $type, int $order): Category
    {
        $canonical = Category::where('slug', $definition['slug'])->first();
        if (!$canonical) {
            $names = array_merge([$definition['name']], $definition['legacy'] ?? []);
            $canonical = Category::whereIn('name', $names)->first() ?: new Category();
        }
        $canonical->fill($this->categoryPayload($definition, $type, $order))->save();
        return $canonical;
    }

    private function categoryPayload(array $definition, string $type, int $order): array
    {
        $description = $definition['description'] ?? "Discover {$definition['name']} on AI Hub with structured product data, comparisons and independent intelligence.";
        return [
            'name' => $definition['name'],
            'slug' => $definition['slug'],
            'type' => $type,
            'short_description' => Str::limit($description, 260, ''),
            'description' => $description,
            'meta_title' => Str::limit($definition['name'] . ($type === 'content' ? ' Articles & Analysis | AI Hub' : ' AI Tools & Models | AI Hub'), 80, ''),
            'meta_description' => Str::limit($description, 175, ''),
            'is_active' => true,
            'is_indexable' => true,
            'sort_order' => $order,
        ];
    }

    private function syncSubcategories(): void
    {
        $order = 1;
        foreach (config('taxonomy_v2.product_categories', []) as $categoryDefinition) {
            $category = Category::where('slug', $categoryDefinition['slug'])->firstOrFail();
            foreach ($categoryDefinition['subcategories'] as $name) {
                $slug = Str::slug($name);
                $description = "Discover {$name} in {$category->name}, compare leading AI products and explore capabilities, pricing and related intelligence.";
                Subcategory::updateOrCreate(
                    ['slug' => $slug],
                    [
                        'category_id' => $category->id,
                        'name' => $name,
                        'short_description' => Str::limit($description, 260, ''),
                        'description' => $description,
                        'meta_title' => Str::limit($name . ' AI Tools | AI Hub', 80, ''),
                        'meta_description' => Str::limit($description, 175, ''),
                        'is_active' => true,
                        'is_indexable' => true,
                        'sort_order' => $order++,
                    ]
                );
                $this->stats['subcategories']++;
            }
        }

        foreach (config('taxonomy_v2.legacy_subcategory_map', []) as $legacyName => $map) {
            $canonical = Subcategory::where('slug', Str::slug($map['subcategory']))->first();
            if (!$canonical) continue;
            Subcategory::query()->where('id', '!=', $canonical->id)->whereRaw('LOWER(name) = ?', [Str::lower($legacyName)])->get()
                ->each(function (Subcategory $legacy) use ($canonical) {
                    Tool::where('subcategory_id', $legacy->id)->update(['subcategory_id' => $canonical->id, 'subcategory' => $canonical->name]);
                    $legacy->update(['is_active' => false, 'is_indexable' => false]);
                });
            Tool::whereRaw('LOWER(subcategory) = ?', [Str::lower($legacyName)])
                ->update(['subcategory_id' => $canonical->id, 'subcategory' => $canonical->name]);
        }
    }

    private function syncFeatures(): void
    {
        foreach (config('taxonomy_v2.features', []) as $order => $definition) {
            $slug = Str::slug($definition['name']);
            $feature = Feature::where('slug', $slug)->first();
            if (!$feature) {
                $feature = Feature::whereIn('name', array_merge([$definition['name']], $definition['aliases'] ?? []))->first() ?: new Feature();
            }
            $feature->fill([
                'name' => $definition['name'],
                'slug' => $slug,
                'short_description' => Str::limit($definition['description'], 260, ''),
                'description' => $definition['description'],
                'group' => $definition['group'],
                'icon' => $definition['icon'],
                'meta_title' => Str::limit($definition['name'] . ' AI Tools & Models | AI Hub', 80, ''),
                'meta_description' => Str::limit($definition['description'], 175, ''),
                'is_active' => true,
                'is_indexable' => true,
                'sort_order' => $order + 1,
            ])->save();

            foreach ($definition['aliases'] ?? [] as $alias) {
                Feature::query()->where('id', '!=', $feature->id)->whereRaw('LOWER(name) = ?', [Str::lower($alias)])->get()
                    ->each(fn (Feature $legacy) => $this->mergeFeature($legacy, $feature));
            }
            $this->stats['features']++;
        }
    }

    private function mergeFeature(Feature $legacy, Feature $canonical): void
    {
        foreach ($legacy->tools()->pluck('tools.id') as $toolId) {
            DB::table('feature_tool')->updateOrInsert(['tool_id' => $toolId, 'feature_id' => $canonical->id], ['created_at'=>now(),'updated_at'=>now()]);
        }
        if (Schema::hasTable('ai_model_feature')) {
            foreach ($legacy->models()->pluck('ai_models.id') as $modelId) {
                DB::table('ai_model_feature')->updateOrInsert(['ai_model_id' => $modelId, 'feature_id' => $canonical->id], ['created_at'=>now(),'updated_at'=>now()]);
            }
        }
        $legacy->tools()->detach();
        if (Schema::hasTable('ai_model_feature')) $legacy->models()->detach();
        $legacy->update(['is_active' => false, 'is_indexable' => false]);
    }

    private function syncUseCases(): void
    {
        foreach (config('taxonomy_v2.use_cases', []) as $order => $definition) {
            $description = "Find AI tools and models for {$definition['name']}, with structured capabilities, pricing, comparisons and supporting intelligence.";
            UseCase::updateOrCreate(
                ['slug' => Str::slug($definition['name'])],
                [
                    'name' => $definition['name'],
                    'short_description' => Str::limit($description, 260, ''),
                    'description' => $description,
                    'icon' => $definition['icon'] ?? 'sparkles',
                    'meta_title' => Str::limit('Best AI for ' . $definition['name'] . ' | AI Hub', 80, ''),
                    'meta_description' => Str::limit($description, 175, ''),
                    'is_active' => true,
                    'is_indexable' => true,
                    'sort_order' => $order + 1,
                ]
            );
            $this->stats['use_cases']++;
        }
    }

    private function syncTags(): void
    {
        foreach (config('taxonomy_v2.tags', []) as $order => $definition) {
            $description = $definition['description'];
            Tag::updateOrCreate(
                ['slug' => Str::slug($definition['name'])],
                [
                    'name' => $definition['name'],
                    'short_description' => Str::limit($description, 260, ''),
                    'description' => $description,
                    'meta_title' => Str::limit($definition['name'] . ' AI Tools | AI Hub', 80, ''),
                    'meta_description' => Str::limit($description, 175, ''),
                    'is_active' => true,
                    'is_indexable' => false,
                    'sort_order' => $order + 1,
                ]
            );
            $this->stats['tags']++;
        }

        $tagAliases = [
            'Developer' => 'Developer Focused',
            'Coding' => 'Developer Focused',
            'Enterprise' => 'Enterprise Ready',
        ];
        foreach ($tagAliases as $legacyName => $canonicalName) {
            $legacy = Tag::whereRaw('LOWER(name) = ?', [Str::lower($legacyName)])->first();
            $canonical = Tag::where('slug', Str::slug($canonicalName))->first();
            if (!$legacy || !$canonical || $legacy->is($canonical)) continue;
            foreach ($legacy->tools()->pluck('tools.id') as $toolId) DB::table('tag_tool')->updateOrInsert(['tool_id'=>$toolId,'tag_id'=>$canonical->id], ['created_at'=>now(),'updated_at'=>now()]);
            foreach ($legacy->articles()->pluck('articles.id') as $articleId) DB::table('article_tag')->insertOrIgnore(['article_id'=>$articleId,'tag_id'=>$canonical->id]);
            if (Schema::hasTable('ai_model_tag')) {
                foreach ($legacy->models()->pluck('ai_models.id') as $modelId) DB::table('ai_model_tag')->updateOrInsert(['ai_model_id'=>$modelId,'tag_id'=>$canonical->id], ['created_at'=>now(),'updated_at'=>now()]);
            }
            $legacy->tools()->detach();
            $legacy->articles()->detach();
            if (Schema::hasTable('ai_model_tag')) $legacy->models()->detach();
            $legacy->update(['is_active'=>false,'is_indexable'=>false]);
        }

        Tag::whereIn('name', ['Popular','Editor Pick','Free','Freemium','Paid','Chatbot','Image','Video','Voice'])
            ->update(['is_active' => false, 'is_indexable' => false]);
    }

    private function normalizeTools(): void
    {
        $featureMap = $this->featureNameMap();
        Tool::query()->with(['featureTerms','useCaseTerms','tagTerms'])->orderBy('id')->chunkById(100, function ($tools) use ($featureMap) {
            foreach ($tools as $tool) {
                $normalizedNames = $this->normalizedCapabilityNames($tool->capabilities ?? [], $featureMap);
                $featureIds = Feature::whereIn('name', $normalizedNames)->pluck('id')->all();
                $tool->featureTerms()->sync($featureIds);

                if (!$this->option('no-infer')) {
                    $useCases = $this->inferUseCases($normalizedNames);
                    $ids = UseCase::whereIn('name', $useCases)->pluck('id')->all();
                    if ($ids) $tool->useCaseTerms()->syncWithoutDetaching($ids);
                }

                if ($tool->subcategory_id) {
                    $sub = Subcategory::find($tool->subcategory_id);
                    if ($sub) {
                        $tool->subcategory = $sub->name;
                        if (!$tool->category_id && $sub->category_id) $tool->category_id = $sub->category_id;
                    }
                }
                $this->normalizeToolTags($tool);
                $tool->capabilities = $normalizedNames;
                $tool->saveQuietly();
                $this->stats['tools_normalized']++;
            }
        });
    }

    private function normalizeModels(): void
    {
        $featureMap = $this->featureNameMap();
        AiModel::query()->with(['featureTerms','useCaseTerms'])->orderBy('id')->chunkById(100, function ($models) use ($featureMap) {
            foreach ($models as $model) {
                $normalizedNames = $this->normalizedCapabilityNames($model->capabilities ?? [], $featureMap);
                $featureIds = Feature::whereIn('name', $normalizedNames)->pluck('id')->all();
                $model->featureTerms()->sync($featureIds);
                if (!$this->option('no-infer')) {
                    $useCases = $this->inferUseCases($normalizedNames);
                    $ids = UseCase::whereIn('name', $useCases)->pluck('id')->all();
                    if ($ids) $model->useCaseTerms()->syncWithoutDetaching($ids);
                }
                $model->capabilities = $normalizedNames;
                $model->saveQuietly();
                $this->stats['models_normalized']++;
            }
        });
    }

    private function normalizeArticles(): void
    {
        if (!Schema::hasTable('articles')) return;
        Article::query()->with('categoryTerm')->orderBy('id')->chunkById(100, function ($articles) {
            foreach ($articles as $article) {
                if ($article->categoryTerm?->type === 'content') {
                    if ($article->category !== $article->categoryTerm->name) {
                        $article->updateQuietly(['category' => $article->categoryTerm->name]);
                    }
                    $this->stats['articles_normalized']++;
                }
            }
        });
    }

    private function normalizeToolTags(Tool $tool): void
    {
        $aliases = ['developer'=>'Developer Focused','coding'=>'Developer Focused','enterprise'=>'Enterprise Ready'];
        $remove = collect(['Popular','Editor Pick','Free','Freemium','Paid','Chatbot','Image','Video','Voice'])
            ->map(fn ($name) => Str::lower($name))->all();

        $names = $tool->tagTerms->pluck('name')->merge(collect($tool->tags ?? []))
            ->map(fn ($name) => trim((string) $name))->filter()
            ->map(fn ($name) => $aliases[Str::lower($name)] ?? $name)
            ->reject(fn ($name) => in_array(Str::lower($name), $remove, true))
            ->unique(fn ($name) => Str::lower($name))->values();

        $tags = Tag::active()->whereIn('name', $names)->orderBy('name')->get();
        $tool->tagTerms()->sync($tags->pluck('id')->all());
        $tool->tags = $tags->pluck('name')->all();
    }

    private function finalizeLegacyTerms(): void
    {
        $productSlugs = collect(config('taxonomy_v2.product_categories', []))->pluck('slug');
        $contentSlugs = collect(config('taxonomy_v2.content_topics', []))->pluck('slug');
        $canonicalCategoryIds = Category::whereIn('slug', $productSlugs->merge($contentSlugs))->pluck('id');

        Category::whereNotIn('id', $canonicalCategoryIds)->get()->each(function (Category $category) {
            $used = $category->tools()->exists() || $category->articles()->exists() || $category->subcategories()->exists();
            $category->update(['is_active' => $used, 'is_indexable' => false]);
        });

        $canonicalSubSlugs = collect(config('taxonomy_v2.product_categories', []))
            ->flatMap(fn ($category) => collect($category['subcategories'])->map(fn ($name) => Str::slug($name)))->unique();
        Subcategory::whereNotIn('slug', $canonicalSubSlugs)->get()->each(function (Subcategory $subcategory) {
            $tools = $subcategory->tools()->get(['id','category_id']);
            if ($tools->isEmpty()) {
                $subcategory->update(['is_active' => false, 'is_indexable' => false]);
                return;
            }
            if (!$subcategory->category_id) {
                $parent = $tools->pluck('category_id')->filter()->countBy()->sortDesc()->keys()->first();
                if ($parent) $subcategory->category_id = (int) $parent;
            }
            $subcategory->is_active = (bool) $subcategory->category_id;
            $subcategory->is_indexable = false;
            $subcategory->save();
        });

        $featureSlugs = collect(config('taxonomy_v2.features', []))->pluck('name')->map(fn ($name) => Str::slug($name));
        Feature::whereNotIn('slug', $featureSlugs)->get()->each(function (Feature $feature) {
            $used = $feature->tools()->exists() || $feature->models()->exists();
            $feature->update(['is_active' => $used, 'is_indexable' => false]);
        });

        $tagSlugs = collect(config('taxonomy_v2.tags', []))->pluck('name')->map(fn ($name) => Str::slug($name));
        Tag::whereNotIn('slug', $tagSlugs)->get()->each(function (Tag $tag) {
            $used = $tag->tools()->exists() || $tag->models()->exists() || $tag->articles()->exists();
            $tag->update(['is_active' => $used, 'is_indexable' => false]);
        });
    }

    private function featureNameMap(): array
    {
        $map = [];
        foreach (config('taxonomy_v2.features', []) as $definition) {
            foreach (array_merge([$definition['name']], $definition['aliases'] ?? []) as $name) {
                $map[Str::lower(trim($name))] = $definition['name'];
            }
        }
        return $map;
    }

    private function normalizedCapabilityNames(array $names, array $map): array
    {
        return collect($names)
            ->map(fn ($name) => trim((string) $name))
            ->filter()
            ->map(fn ($name) => $map[Str::lower($name)] ?? $name)
            ->unique(fn ($name) => Str::lower($name))
            ->values()
            ->all();
    }

    private function inferUseCases(array $featureNames): array
    {
        $rules = config('taxonomy_v2.use_case_inference', []);
        return collect($featureNames)
            ->flatMap(fn ($feature) => $rules[$feature] ?? [])
            ->unique()
            ->take(12)
            ->values()
            ->all();
    }
}
