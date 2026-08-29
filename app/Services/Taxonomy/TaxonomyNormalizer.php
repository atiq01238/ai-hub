<?php

namespace App\Services\Taxonomy;

use App\Models\Category;
use App\Models\Feature;
use App\Models\Subcategory;
use App\Models\Tag;
use App\Models\UseCase;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class TaxonomyNormalizer
{
    public function canonicalFeatureNames(array $names): array
    {
        $map = $this->featureAliasMap();
        return collect($names)
            ->map(fn ($name) => trim((string) $name))
            ->filter()
            ->map(fn ($name) => $map[Str::lower($name)] ?? $name)
            ->unique(fn ($name) => Str::lower($name))
            ->values()
            ->all();
    }

    public function unknownFeatureNames(array $names): array
    {
        $canonical = $this->canonicalFeatureNames($names);
        $known = Feature::active()->whereIn('name', $canonical)->pluck('name')->map(fn ($n) => Str::lower($n))->all();
        return collect($canonical)->reject(fn ($name) => in_array(Str::lower($name), $known, true))->values()->all();
    }

    public function featureIds(array $names): array
    {
        return Feature::active()->whereIn('name', $this->canonicalFeatureNames($names))->pluck('id')->all();
    }

    public function canonicalUseCaseNames(array $names): array
    {
        return $this->canonicalTermNames($names, UseCase::active()->pluck('name')->all());
    }

    public function unknownUseCaseNames(array $names): array
    {
        $canonical = $this->canonicalUseCaseNames($names);
        $known = UseCase::active()->whereIn('name', $canonical)->pluck('name')->map(fn ($n) => Str::lower($n))->all();
        return collect($canonical)->reject(fn ($name) => in_array(Str::lower($name), $known, true))->values()->all();
    }

    public function useCaseIds(array $names): array
    {
        return UseCase::active()->whereIn('name', $this->canonicalUseCaseNames($names))->pluck('id')->all();
    }

    public function canonicalTagNames(array $names): array
    {
        return $this->canonicalTermNames($names, Tag::active()->pluck('name')->all());
    }

    public function unknownTagNames(array $names): array
    {
        $canonical = $this->canonicalTagNames($names);
        $known = Tag::active()->whereIn('name', $canonical)->pluck('name')->map(fn ($n) => Str::lower($n))->all();
        return collect($canonical)->reject(fn ($name) => in_array(Str::lower($name), $known, true))->values()->all();
    }

    public function tagIds(array $names): array
    {
        return Tag::active()->whereIn('name', $this->canonicalTagNames($names))->pluck('id')->all();
    }

    public function inferredUseCaseIds(array $featureNames, ?string $categoryName = null): array
    {
        $rules = config('taxonomy_v2.use_case_inference', []);
        $names = collect($this->canonicalFeatureNames($featureNames))
            ->flatMap(fn ($feature) => $rules[$feature] ?? [])
            ->merge($this->categoryDefaults($categoryName)['use_cases'] ?? [])
            ->unique()
            ->take(12)
            ->all();

        return UseCase::active()->whereIn('name', $names)->pluck('id')->all();
    }

    public function defaultSubcategoryForCategory(?string $categoryName, ?int $categoryId = null): ?Subcategory
    {
        $name = $this->categoryDefaults($categoryName)['subcategory'] ?? null;
        return $name ? $this->subcategoryByName($name, $categoryId) : null;
    }

    public function defaultTagIdsForCategory(?string $categoryName): array
    {
        $names = $this->categoryDefaults($categoryName)['tags'] ?? [];
        return Tag::active()->whereIn('name', $names)->pluck('id')->all();
    }

    public function categoryDefaults(?string $categoryName): array
    {
        $needle = Str::lower(trim((string) $categoryName));
        if ($needle === '') return [];

        foreach (config('taxonomy_v2.legacy_category_defaults', []) as $name => $defaults) {
            if (Str::lower($name) === $needle) return $defaults;
        }

        return [];
    }

    public function productCategoryByName(?string $name): ?Category
    {
        $needle = trim((string) $name);
        if ($needle === '') return null;

        $alias = collect(config('taxonomy_v2.product_categories', []))->first(function ($definition) use ($needle) {
            $names = array_merge([$definition['name'], $definition['slug']], $definition['legacy'] ?? []);
            return collect($names)->contains(fn ($name) => Str::lower($name) === Str::lower($needle));
        });

        $canonical = $alias['name'] ?? $needle;
        $slug = $alias['slug'] ?? Str::slug($needle);
        return Category::product()->active()->where(function ($q) use ($canonical, $slug) {
            $q->whereRaw('LOWER(name) = ?', [Str::lower($canonical)])->orWhere('slug', $slug);
        })->first();
    }

    public function subcategoryByName(?string $name, ?int $categoryId = null): ?Subcategory
    {
        $needle = trim((string) $name);
        if ($needle === '') return null;

        $legacy = collect(config('taxonomy_v2.legacy_subcategory_map', []))
            ->first(fn ($definition, $legacyName) => Str::lower($legacyName) === Str::lower($needle));
        $canonicalName = $legacy['subcategory'] ?? $needle;

        return Subcategory::active()
            ->where(fn ($q) => $q->whereRaw('LOWER(name) = ?', [Str::lower($canonicalName)])->orWhere('slug', Str::slug($canonicalName)))
            ->when($categoryId, fn ($q) => $q->where('category_id', $categoryId))
            ->first();
    }

    public function activeFeatureGroups(): Collection
    {
        return Feature::active()->orderBy('group')->orderBy('sort_order')->orderBy('name')->get()->groupBy(fn ($feature) => $feature->group ?: 'Other');
    }

    private function featureAliasMap(): array
    {
        $map = [];
        foreach (config('taxonomy_v2.features', []) as $definition) {
            foreach (array_merge([$definition['name']], $definition['aliases'] ?? []) as $name) {
                $map[Str::lower(trim($name))] = $definition['name'];
            }
        }
        return $map;
    }

    private function canonicalTermNames(array $names, array $knownNames): array
    {
        $map = collect($knownNames)->mapWithKeys(fn ($name) => [Str::lower(trim((string) $name)) => (string) $name]);

        return collect($names)
            ->map(fn ($name) => trim((string) $name))
            ->filter()
            ->map(fn ($name) => $map->get(Str::lower($name), $name))
            ->unique(fn ($name) => Str::lower($name))
            ->values()
            ->all();
    }
}
