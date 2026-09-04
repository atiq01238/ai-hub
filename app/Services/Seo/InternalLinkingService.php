<?php

namespace App\Services\Seo;

use App\Models\AiModel;
use App\Models\Article;
use App\Models\Company;
use App\Models\Comparison;
use App\Models\NewsItem;
use App\Models\Tool;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class InternalLinkingService
{
    public function comparisonsForModel(AiModel $model, int $limit = 4): Collection
    {
        $candidates = $this->publishedComparisons('model')
            ->filter(fn (Comparison $comparison) => $this->storedIds($comparison)->contains((int) $model->id));

        return $this->validateCandidates($candidates, $limit);
    }

    public function comparisonsForTool(Tool $tool, int $limit = 4): Collection
    {
        $candidates = $this->publishedComparisons('tool')
            ->filter(fn (Comparison $comparison) => $this->storedIds($comparison)->contains((int) $tool->id));

        return $this->validateCandidates($candidates, $limit);
    }

    public function comparisonsForCompany(Company $company, int $limit = 6): Collection
    {
        $toolIds = $company->tools()
            ->where('status', 'published')
            ->pluck('id')
            ->map(fn ($id) => (int) $id);

        $modelIds = $company->models()
            ->whereIn('status', ['active', 'preview'])
            ->pluck('id')
            ->map(fn ($id) => (int) $id);

        $candidates = $this->publishedComparisons()
            ->filter(function (Comparison $comparison) use ($toolIds, $modelIds) {
                $ids = $this->storedIds($comparison);
                $companyIds = $comparison->comparable_type === 'tool' ? $toolIds : $modelIds;

                return $ids->intersect($companyIds)->isNotEmpty();
            });

        return $this->validateCandidates($candidates, $limit);
    }

    /**
     * Related comparison ranking is semantic-first:
     * shared compared entity > shared provider/category/capability > popularity.
     * Unrelated popular comparisons are intentionally not used as filler.
     */
    public function relatedComparisons(Comparison $comparison, int $limit = 4): Collection
    {
        $baseItems = $this->safePublicItems($comparison);
        if ($baseItems->count() < 2) {
            return collect();
        }

        $baseIds = $baseItems->pluck('id')->map(fn ($id) => (int) $id);
        $baseCompanyIds = $baseItems->pluck('company_id')->filter()->map(fn ($id) => (int) $id)->unique();
        $baseCategoryIds = $comparison->comparable_type === 'tool'
            ? $baseItems->pluck('category_id')->filter()->map(fn ($id) => (int) $id)->unique()
            : collect();

        $baseFeatureIds = collect();
        $baseUseCaseIds = collect();
        if ($comparison->comparable_type === 'model') {
            foreach ($baseItems as $item) {
                $item->loadMissing(['featureTerms:id', 'useCaseTerms:id']);
                $baseFeatureIds = $baseFeatureIds->merge($item->featureTerms->pluck('id'));
                $baseUseCaseIds = $baseUseCaseIds->merge($item->useCaseTerms->pluck('id'));
            }
            $baseFeatureIds = $baseFeatureIds->filter()->unique();
            $baseUseCaseIds = $baseUseCaseIds->filter()->unique();
        }

        return $this->publishedComparisons($comparison->comparable_type)
            ->where('id', '!=', $comparison->id)
            ->map(function (Comparison $candidate) use ($baseIds, $baseCompanyIds, $baseCategoryIds, $baseFeatureIds, $baseUseCaseIds, $comparison) {
                $items = $this->safePublicItems($candidate);
                if ($items->count() < 2) {
                    return null;
                }

                $sharedEntities = $items->pluck('id')->map(fn ($id) => (int) $id)->intersect($baseIds)->count();
                $sharedCompanies = $items->pluck('company_id')->filter()->map(fn ($id) => (int) $id)->intersect($baseCompanyIds)->count();
                $score = ($sharedEntities * 100) + ($sharedCompanies * 30);

                if ($comparison->comparable_type === 'tool') {
                    $sharedCategories = $items->pluck('category_id')->filter()->map(fn ($id) => (int) $id)->intersect($baseCategoryIds)->count();
                    $score += $sharedCategories * 25;
                } else {
                    $candidateFeatureIds = collect();
                    $candidateUseCaseIds = collect();
                    foreach ($items as $item) {
                        $item->loadMissing(['featureTerms:id', 'useCaseTerms:id']);
                        $candidateFeatureIds = $candidateFeatureIds->merge($item->featureTerms->pluck('id'));
                        $candidateUseCaseIds = $candidateUseCaseIds->merge($item->useCaseTerms->pluck('id'));
                    }
                    $score += $candidateFeatureIds->filter()->unique()->intersect($baseFeatureIds)->count() * 12;
                    $score += $candidateUseCaseIds->filter()->unique()->intersect($baseUseCaseIds)->count() * 14;
                }

                if ($score <= 0) {
                    return null;
                }

                $candidate->setRelation('resolved_items', $items);
                $candidate->setAttribute('semantic_link_score', $score);
                return $candidate;
            })
            ->filter()
            ->sortByDesc(fn (Comparison $candidate) =>
                ((int) $candidate->semantic_link_score * 1000000000000)
                + (min((int) $candidate->views, 999999999) * 1000)
                + (int) $candidate->id
            )
            ->take($limit)
            ->values();
    }

    public function modelsForModel(AiModel $model, int $limit = 4): Collection
    {
        $model->loadMissing(['featureTerms:id', 'useCaseTerms:id']);
        $featureIds = $model->featureTerms->pluck('id')->filter();
        $useCaseIds = $model->useCaseTerms->pluck('id')->filter();

        $candidates = AiModel::query()
            ->with(['company', 'tool', 'featureTerms:id', 'useCaseTerms:id'])
            ->whereIn('status', ['active', 'preview'])
            ->whereKeyNot($model->id)
            ->where(function (Builder $query) use ($model, $featureIds, $useCaseIds) {
                if ($model->tool_id) {
                    $query->orWhere('tool_id', $model->tool_id);
                }
                if ($model->company_id) {
                    $query->orWhere('company_id', $model->company_id);
                }
                if ($featureIds->isNotEmpty()) {
                    $query->orWhereHas('featureTerms', fn (Builder $q) => $q->whereIn('features.id', $featureIds));
                }
                if ($useCaseIds->isNotEmpty()) {
                    $query->orWhereHas('useCaseTerms', fn (Builder $q) => $q->whereIn('use_cases.id', $useCaseIds));
                }
            })
            ->limit(80)
            ->get();

        return $candidates
            ->map(function (AiModel $candidate) use ($model, $featureIds, $useCaseIds) {
                $score = 0;
                if ($model->tool_id && (int) $candidate->tool_id === (int) $model->tool_id) {
                    $score += 90;
                }
                if ($model->company_id && (int) $candidate->company_id === (int) $model->company_id) {
                    $score += 60;
                }
                $score += $candidate->featureTerms->pluck('id')->intersect($featureIds)->count() * 12;
                $score += $candidate->useCaseTerms->pluck('id')->intersect($useCaseIds)->count() * 14;
                $candidate->setAttribute('semantic_link_score', $score);
                return $candidate;
            })
            ->filter(fn (AiModel $candidate) => (int) $candidate->semantic_link_score > 0)
            ->sortByDesc(fn (AiModel $candidate) =>
                ((int) $candidate->semantic_link_score * 1000000000000)
                + ((int) round(((float) ($candidate->benchmark_score ?? -1) + 1) * 1000000000))
                + ($candidate->release_date?->timestamp ?? 0)
            )
            ->take($limit)
            ->values();
    }

    public function articlesForTool(Tool $tool, int $limit = 4): Collection
    {
        $tool->loadMissing('category');

        return $this->publishedArticles()
            ->with(['company', 'author', 'relatedToolTerms:id'])
            ->where(function (Builder $query) use ($tool) {
                $query->whereHas('relatedToolTerms', fn (Builder $q) => $q->where('tools.id', $tool->id));
                if ($tool->company_id) {
                    $query->orWhere('company_id', $tool->company_id);
                }
                if ($tool->category_id) {
                    $query->orWhere('category_id', $tool->category_id);
                }
            })
            ->latest('published_at')
            ->limit(40)
            ->get()
            ->map(function (Article $article) use ($tool) {
                $score = $article->relatedToolTerms->contains('id', $tool->id) ? 100 : 0;
                if ($tool->company_id && (int) $article->company_id === (int) $tool->company_id) {
                    $score += 40;
                }
                if ($tool->category_id && (int) $article->category_id === (int) $tool->category_id) {
                    $score += 25;
                }
                $article->setAttribute('semantic_link_score', $score);
                return $article;
            })
            ->filter(fn (Article $article) => (int) $article->semantic_link_score > 0)
            ->sortByDesc(fn (Article $article) => ((int) $article->semantic_link_score * 1000000000000) + ($article->published_at?->timestamp ?? 0))
            ->take($limit)
            ->values();
    }

    public function articlesForModel(AiModel $model, int $limit = 4): Collection
    {
        $categoryId = $model->tool?->category_id;

        return $this->publishedArticles()
            ->with(['company', 'author', 'relatedModelTerms:id'])
            ->where(function (Builder $query) use ($model, $categoryId) {
                $query->whereHas('relatedModelTerms', fn (Builder $q) => $q->where('ai_models.id', $model->id));
                if ($model->company_id) {
                    $query->orWhere('company_id', $model->company_id);
                }
                if ($categoryId) {
                    $query->orWhere('category_id', $categoryId);
                }
            })
            ->latest('published_at')
            ->limit(40)
            ->get()
            ->map(function (Article $article) use ($model, $categoryId) {
                $score = $article->relatedModelTerms->contains('id', $model->id) ? 100 : 0;
                if ($model->company_id && (int) $article->company_id === (int) $model->company_id) {
                    $score += 40;
                }
                if ($categoryId && (int) $article->category_id === (int) $categoryId) {
                    $score += 25;
                }
                $article->setAttribute('semantic_link_score', $score);
                return $article;
            })
            ->filter(fn (Article $article) => (int) $article->semantic_link_score > 0)
            ->sortByDesc(fn (Article $article) => ((int) $article->semantic_link_score * 1000000000000) + ($article->published_at?->timestamp ?? 0))
            ->take($limit)
            ->values();
    }

    public function newsForTool(Tool $tool, int $limit = 4): Collection
    {
        return $this->publicNews()
            ->with(['company', 'newsSource', 'relatedToolTerms:id'])
            ->where(function (Builder $query) use ($tool) {
                $query->whereHas('relatedToolTerms', fn (Builder $q) => $q->where('tools.id', $tool->id));
                if ($tool->company_id) {
                    $query->orWhere('company_id', $tool->company_id);
                }
                $query->orWhere('headline', 'like', '%'.$tool->name.'%')
                    ->orWhere('summary', 'like', '%'.$tool->name.'%');
            })
            ->latest('published_at')
            ->limit(40)
            ->get()
            ->map(function (NewsItem $news) use ($tool) {
                $score = $news->relatedToolTerms->contains('id', $tool->id) ? 100 : 0;
                $text = mb_strtolower(trim($news->headline.' '.$news->summary));
                if ($tool->name && str_contains($text, mb_strtolower($tool->name))) {
                    $score += 70;
                }
                if ($tool->company_id && (int) $news->company_id === (int) $tool->company_id) {
                    $score += 40;
                }
                $news->setAttribute('semantic_link_score', $score);
                return $news;
            })
            ->filter(fn (NewsItem $news) => (int) $news->semantic_link_score > 0)
            ->sortByDesc(fn (NewsItem $news) => ((int) $news->semantic_link_score * 1000000000000) + ((int) $news->importance * 1000000000) + ($news->published_at?->timestamp ?? 0))
            ->take($limit)
            ->values();
    }

    public function newsForModel(AiModel $model, int $limit = 4): Collection
    {
        return $this->publicNews()
            ->with(['company', 'newsSource', 'relatedModelTerms:id'])
            ->where(function (Builder $query) use ($model) {
                $query->whereHas('relatedModelTerms', fn (Builder $q) => $q->where('ai_models.id', $model->id));
                if ($model->company_id) {
                    $query->orWhere('company_id', $model->company_id);
                }
                $query->orWhere('headline', 'like', '%'.$model->name.'%')
                    ->orWhere('summary', 'like', '%'.$model->name.'%');
            })
            ->latest('published_at')
            ->limit(40)
            ->get()
            ->map(function (NewsItem $news) use ($model) {
                $score = $news->relatedModelTerms->contains('id', $model->id) ? 100 : 0;
                $text = mb_strtolower(trim($news->headline.' '.$news->summary));
                if ($model->name && str_contains($text, mb_strtolower($model->name))) {
                    $score += 70;
                }
                if ($model->company_id && (int) $news->company_id === (int) $model->company_id) {
                    $score += 40;
                }
                $news->setAttribute('semantic_link_score', $score);
                return $news;
            })
            ->filter(fn (NewsItem $news) => (int) $news->semantic_link_score > 0)
            ->sortByDesc(fn (NewsItem $news) => ((int) $news->semantic_link_score * 1000000000000) + ((int) $news->importance * 1000000000) + ($news->published_at?->timestamp ?? 0))
            ->take($limit)
            ->values();
    }


    public function articlesForComparison(Comparison $comparison, int $limit = 3): Collection
    {
        $items = $this->safePublicItems($comparison);
        if ($items->count() < 2) {
            return collect();
        }

        $itemIds = $items->pluck('id')->map(fn ($id) => (int) $id)->unique();
        $companyIds = $items->pluck('company_id')->filter()->map(fn ($id) => (int) $id)->unique();

        $query = $this->publishedArticles()->with(['company', 'author', 'relatedToolTerms:id', 'relatedModelTerms:id']);

        if ($comparison->comparable_type === 'tool') {
            $categoryIds = $items->pluck('category_id')->filter()->map(fn ($id) => (int) $id)->unique();

            $query->where(function (Builder $builder) use ($itemIds, $companyIds, $categoryIds) {
                $builder->whereHas('relatedToolTerms', fn (Builder $q) => $q->whereIn('tools.id', $itemIds));
                if ($companyIds->isNotEmpty()) {
                    $builder->orWhereIn('company_id', $companyIds);
                }
                if ($categoryIds->isNotEmpty()) {
                    $builder->orWhereIn('category_id', $categoryIds);
                }
            });

            return $query->latest('published_at')->limit(60)->get()
                ->map(function (Article $article) use ($itemIds, $companyIds, $categoryIds) {
                    $score = $article->relatedToolTerms->pluck('id')->map(fn ($id) => (int) $id)->intersect($itemIds)->count() * 80;
                    if ($article->company_id && $companyIds->contains((int) $article->company_id)) {
                        $score += 35;
                    }
                    if ($article->category_id && $categoryIds->contains((int) $article->category_id)) {
                        $score += 20;
                    }
                    $article->setAttribute('semantic_link_score', $score);
                    return $article;
                })
                ->filter(fn (Article $article) => (int) $article->semantic_link_score > 0)
                ->sortByDesc(fn (Article $article) => ((int) $article->semantic_link_score * 1000000000000) + ($article->published_at?->timestamp ?? 0))
                ->take($limit)
                ->values();
        }

        $query->where(function (Builder $builder) use ($itemIds, $companyIds) {
            $builder->whereHas('relatedModelTerms', fn (Builder $q) => $q->whereIn('ai_models.id', $itemIds));
            if ($companyIds->isNotEmpty()) {
                $builder->orWhereIn('company_id', $companyIds);
            }
        });

        return $query->latest('published_at')->limit(60)->get()
            ->map(function (Article $article) use ($itemIds, $companyIds) {
                $score = $article->relatedModelTerms->pluck('id')->map(fn ($id) => (int) $id)->intersect($itemIds)->count() * 90;
                if ($article->company_id && $companyIds->contains((int) $article->company_id)) {
                    $score += 35;
                }
                $article->setAttribute('semantic_link_score', $score);
                return $article;
            })
            ->filter(fn (Article $article) => (int) $article->semantic_link_score > 0)
            ->sortByDesc(fn (Article $article) => ((int) $article->semantic_link_score * 1000000000000) + ($article->published_at?->timestamp ?? 0))
            ->take($limit)
            ->values();
    }

    public function toolsForArticle(Article $article, int $limit = 6): Collection
    {
        $article->loadMissing(['relatedToolTerms.company']);
        $legacyIds = collect($article->related_tools ?? [])->filter(fn ($id) => is_numeric($id))->map(fn ($id) => (int) $id);
        $explicitIds = $article->relatedToolTerms->pluck('id')->merge($legacyIds)->filter()->unique();

        $explicit = Tool::query()->with('company')->where('status', 'published')->whereIn('id', $explicitIds)->get();
        if ($explicit->count() >= $limit || (! $article->company_id && ! $article->category_id)) {
            return $explicit->take($limit)->values();
        }

        $fallback = Tool::query()
            ->with('company')
            ->where('status', 'published')
            ->whereNotIn('id', $explicit->pluck('id'))
            ->where(function (Builder $query) use ($article) {
                if ($article->company_id) {
                    $query->orWhere('company_id', $article->company_id);
                }
                if ($article->category_id) {
                    $query->orWhere('category_id', $article->category_id);
                }
            })
            ->orderByDesc('popularity')
            ->limit($limit - $explicit->count())
            ->get();

        return $explicit->concat($fallback)->unique('id')->take($limit)->values();
    }

    public function modelsForArticle(Article $article, int $limit = 6): Collection
    {
        $article->loadMissing(['relatedModelTerms.company']);
        $legacyIds = collect($article->related_models ?? [])->filter(fn ($id) => is_numeric($id))->map(fn ($id) => (int) $id);
        $explicitIds = $article->relatedModelTerms->pluck('id')->merge($legacyIds)->filter()->unique();

        $explicit = AiModel::query()->with('company')->whereIn('status', ['active', 'preview'])->whereIn('id', $explicitIds)->get();
        if ($explicit->count() >= $limit) {
            return $explicit->take($limit)->values();
        }

        $fallback = AiModel::query()
            ->with('company')
            ->whereIn('status', ['active', 'preview'])
            ->whereNotIn('id', $explicit->pluck('id'))
            ->when($article->company_id, fn (Builder $query) => $query->where('company_id', $article->company_id), fn (Builder $query) => $query->whereRaw('1 = 0'))
            ->orderByDesc('benchmark_score')
            ->limit($limit - $explicit->count())
            ->get();

        return $explicit->concat($fallback)->unique('id')->take($limit)->values();
    }

    public function relatedArticles(Article $article, int $limit = 4): Collection
    {
        $article->loadMissing(['relatedToolTerms:id', 'relatedModelTerms:id', 'tagTerms:id']);
        $toolIds = $article->relatedToolTerms->pluck('id');
        $modelIds = $article->relatedModelTerms->pluck('id');
        $tagIds = $article->tagTerms->pluck('id');

        return $this->publishedArticles()
            ->with(['company', 'author', 'relatedToolTerms:id', 'relatedModelTerms:id', 'tagTerms:id'])
            ->whereKeyNot($article->id)
            ->limit(80)
            ->get()
            ->map(function (Article $candidate) use ($article, $toolIds, $modelIds, $tagIds) {
                $score = 0;
                $score += $candidate->relatedToolTerms->pluck('id')->intersect($toolIds)->count() * 60;
                $score += $candidate->relatedModelTerms->pluck('id')->intersect($modelIds)->count() * 70;
                $score += $candidate->tagTerms->pluck('id')->intersect($tagIds)->count() * 10;
                if ($article->company_id && (int) $candidate->company_id === (int) $article->company_id) {
                    $score += 40;
                }
                if ($article->category_id && (int) $candidate->category_id === (int) $article->category_id) {
                    $score += 30;
                } elseif ($article->category && $candidate->category === $article->category) {
                    $score += 20;
                }
                $candidate->setAttribute('semantic_link_score', $score);
                return $candidate;
            })
            ->filter(fn (Article $candidate) => (int) $candidate->semantic_link_score > 0)
            ->sortByDesc(fn (Article $candidate) => ((int) $candidate->semantic_link_score * 1000000000000) + ($candidate->published_at?->timestamp ?? 0))
            ->take($limit)
            ->values();
    }

    public function toolsForNews(NewsItem $news, int $limit = 6): Collection
    {
        $news->loadMissing(['relatedToolTerms.company']);
        $explicit = $news->relatedToolTerms->filter(fn (Tool $tool) => $tool->status === 'published')->values();

        $legacyNames = collect($news->related_tools ?? [])->filter(fn ($name) => is_string($name) && trim($name) !== '')->values();
        if ($legacyNames->isNotEmpty()) {
            $legacy = Tool::query()->with('company')->where('status', 'published')
                ->where(function (Builder $query) use ($legacyNames) {
                    foreach ($legacyNames as $name) {
                        $query->orWhere('name', $name);
                    }
                })->get();
            $explicit = $explicit->concat($legacy)->unique('id')->values();
        }

        if ($explicit->count() >= $limit || ! $news->company_id) {
            return $explicit->take($limit)->values();
        }

        $companyTools = Tool::query()->with('company')->where('status', 'published')
            ->where('company_id', $news->company_id)
            ->whereNotIn('id', $explicit->pluck('id'))
            ->orderByDesc('popularity')->take($limit - $explicit->count())->get();

        return $explicit->concat($companyTools)->unique('id')->take($limit)->values();
    }

    public function modelsForNews(NewsItem $news, int $limit = 4): Collection
    {
        $news->loadMissing(['relatedModelTerms.company']);
        $explicit = $news->relatedModelTerms->filter(fn (AiModel $model) => in_array($model->status, ['active', 'preview'], true))->values();

        if ($explicit->count() >= $limit || ! $news->company_id) {
            return $explicit->take($limit)->values();
        }

        $companyModels = AiModel::query()->with('company')->whereIn('status', ['active', 'preview'])
            ->where('company_id', $news->company_id)
            ->whereNotIn('id', $explicit->pluck('id'))
            ->orderByDesc('benchmark_score')->take($limit - $explicit->count())->get();

        return $explicit->concat($companyModels)->unique('id')->take($limit)->values();
    }

    public function relatedNews(NewsItem $news, int $limit = 4): Collection
    {
        $news->loadMissing(['relatedToolTerms:id', 'relatedModelTerms:id']);
        $toolIds = $news->relatedToolTerms->pluck('id');
        $modelIds = $news->relatedModelTerms->pluck('id');
        $topic = trim((string) $news->ai_topic);

        return $this->publicNews()
            ->with(['company', 'newsSource', 'relatedToolTerms:id', 'relatedModelTerms:id'])
            ->whereKeyNot($news->id)
            ->limit(100)
            ->get()
            ->map(function (NewsItem $candidate) use ($news, $toolIds, $modelIds, $topic) {
                $score = 0;
                $score += $candidate->relatedToolTerms->pluck('id')->intersect($toolIds)->count() * 65;
                $score += $candidate->relatedModelTerms->pluck('id')->intersect($modelIds)->count() * 75;
                if ($news->company_id && (int) $candidate->company_id === (int) $news->company_id) {
                    $score += 45;
                }
                if ($news->category && $candidate->category === $news->category) {
                    $score += 20;
                }
                if ($topic !== '' && strcasecmp($topic, (string) $candidate->ai_topic) === 0) {
                    $score += 25;
                }
                $candidate->setAttribute('semantic_link_score', $score);
                return $candidate;
            })
            ->filter(fn (NewsItem $candidate) => (int) $candidate->semantic_link_score > 0)
            ->sortByDesc(fn (NewsItem $candidate) =>
                ((int) $candidate->semantic_link_score * 1000000000000)
                + ((int) $candidate->importance * 1000000000)
                + ($candidate->published_at?->timestamp ?? 0)
            )
            ->take($limit)
            ->values();
    }

    public function analysisForNews(NewsItem $news): ?Article
    {
        return $this->publishedArticles()
            ->where('origin_news_item_id', $news->id)
            ->latest('published_at')
            ->first();
    }

    private function publishedComparisons(?string $type = null): Collection
    {
        return Comparison::query()
            ->where('status', 'published')
            ->when($type, fn ($query) => $query->where('comparable_type', $type))
            ->orderByDesc('last_verified_at')
            ->orderByDesc('views')
            ->latest('id')
            ->get();
    }

    private function publishedArticles(): Builder
    {
        return Article::query()
            ->where('status', 'published')
            ->where('approval_status', 'approved');
    }

    private function publicNews(): Builder
    {
        return NewsItem::query()
            ->where('status', 'published')
            ->whereNull('duplicate_of_id')
            ->where(fn (Builder $query) => $query->whereNull('duplicate_status')->orWhere('duplicate_status', '!=', 'duplicate'));
    }

    private function validateCandidates(Collection $candidates, int $limit): Collection
    {
        return $candidates
            ->filter(function (Comparison $comparison) {
                $items = $this->safePublicItems($comparison);
                if ($items->count() < 2) {
                    return false;
                }
                $comparison->setRelation('resolved_items', $items);
                return true;
            })
            ->take($limit)
            ->values();
    }

    private function safePublicItems(Comparison $comparison): Collection
    {
        try {
            return $comparison->publicItems();
        } catch (\Throwable $e) {
            report($e);
            return collect();
        }
    }

    private function storedIds(Comparison $comparison): Collection
    {
        return collect($comparison->item_ids ?? [])
            ->filter(fn ($id) => is_numeric($id))
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();
    }
}
