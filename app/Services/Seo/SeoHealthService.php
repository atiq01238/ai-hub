<?php

namespace App\Services\Seo;

use App\Models\AiModel;
use App\Models\Article;
use App\Models\Benchmark;
use App\Models\Comparison;
use App\Models\NewsItem;
use App\Models\SeoTarget;
use App\Models\Tool;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SeoHealthService
{
    public function __construct(
        private readonly SeoIntentMapService $intentMap,
        private readonly SeoMetadataService $metadata,
        private readonly InternalLinkingService $links,
    ) {
    }

    /**
     * Phase 4 dashboard/audit snapshot. Coverage numbers are descriptive, not
     * quotas: editorial edges are only counted when a real semantic relation exists.
     */
    public function snapshot(): array
    {
        $inventory = $this->intentMap->resolvedInventory();
        $persisted = Schema::hasTable('seo_targets')
            ? SeoTarget::query()->with('targetable')->get()
            : collect();
        $persistedByKey = $persisted->keyBy('target_key');

        $currentKeys = $inventory->pluck('target_key');
        $currentPersisted = $persisted->filter(fn (SeoTarget $target) => $currentKeys->contains($target->target_key))->values();

        $missingPrimary = $inventory->filter(fn (array $target) => blank($target['primary_keyword'] ?? null))->count();
        $collisionGroups = $inventory
            ->groupBy(fn (array $target) => $this->intentMap->normalizeKeyword($target['primary_keyword'] ?? ''))
            ->filter(fn (Collection $group, string $keyword) => $keyword !== '' && $group->count() > 1);

        $metadataRows = collect();
        foreach ($currentPersisted as $target) {
            try {
                $generated = $this->metadata->forTarget($target);
                $metadataRows->push([
                    'target_key' => $target->target_key,
                    'page_type' => $target->page_type,
                    'primary_keyword' => $target->primary_keyword,
                    'title' => trim((string) ($generated['title'] ?? '')),
                    'description' => trim((string) ($generated['description'] ?? '')),
                    'aligned' => $this->metadata->titleRepresentsPrimary(
                        (string) ($generated['title'] ?? ''),
                        (string) $target->primary_keyword,
                    ),
                    'error' => null,
                ]);
            } catch (\Throwable $e) {
                $metadataRows->push([
                    'target_key' => $target->target_key,
                    'page_type' => $target->page_type,
                    'primary_keyword' => $target->primary_keyword,
                    'title' => '',
                    'description' => '',
                    'aligned' => false,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $metadataMissingTitle = $metadataRows->where('title', '')->count();
        $metadataMissingDescription = $metadataRows->where('description', '')->count();
        $metadataMisaligned = $metadataRows->where('aligned', false)->count();
        $metadataErrors = $metadataRows->filter(fn (array $row) => filled($row['error']))->count();
        $duplicateTitleGroups = $metadataRows
            ->filter(fn (array $row) => filled($row['title']))
            ->groupBy(fn (array $row) => $this->metadata->normalized($row['title']))
            ->filter(fn (Collection $group, string $title) => $title !== '' && $group->count() > 1);

        $semantic = $this->semanticSnapshot();

        $hardConflictCount = $missingPrimary
            + $collisionGroups->count()
            + $metadataMissingTitle
            + $metadataMissingDescription
            + $metadataMisaligned
            + $duplicateTitleGroups->count()
            + $metadataErrors
            + $semantic['unsafe_comparison_links'];

        $intentTotal = $inventory->count();
        $persistedCount = $currentPersisted->count();
        $metadataTotal = $currentPersisted->count();
        $metadataAligned = $metadataRows->where('aligned', true)->count();

        return [
            'summary' => [
                'intent_total' => $intentTotal,
                'persisted_targets' => $persistedCount,
                'intent_coverage' => $this->percent($persistedCount, $intentTotal),
                'metadata_total' => $metadataTotal,
                'metadata_aligned' => $metadataAligned,
                'metadata_coverage' => $this->percent($metadataAligned, $metadataTotal),
                'missing_primary' => $missingPrimary,
                'primary_collisions' => $collisionGroups->count(),
                'missing_titles' => $metadataMissingTitle,
                'missing_descriptions' => $metadataMissingDescription,
                'metadata_misaligned' => $metadataMisaligned,
                'duplicate_titles' => $duplicateTitleGroups->count(),
                'metadata_errors' => $metadataErrors,
                'stale_targets' => $persisted->reject(fn (SeoTarget $target) => $currentKeys->contains($target->target_key))->count(),
                'hard_conflicts' => $hardConflictCount,
                'status' => $hardConflictCount === 0 ? 'healthy' : 'attention',
            ],
            'semantic' => $semantic,
            'metadata_warnings' => $metadataRows
                ->filter(fn (array $row) => ! $row['aligned'] || $row['title'] === '' || $row['description'] === '' || filled($row['error']))
                ->take(20)
                ->values(),
            'collision_groups' => $collisionGroups->take(20)->map(fn (Collection $group) => $group->pluck('target_key')->values())->values(),
        ];
    }

    private function semanticSnapshot(): array
    {
        $tools = Tool::query()->where('status', 'published')->get(['id', 'name', 'slug']);
        $models = AiModel::query()->whereIn('status', ['active', 'preview'])->get(['id', 'name', 'slug']);
        $articles = Article::query()->where('status', 'published')->where('approval_status', 'approved')->get(['id']);
        $news = $this->publicNews()->get(['id']);
        $comparisons = $this->validComparisons();

        $articleToolIds = $this->distinctPivotIds('article_tool', 'tool_id', 'article_id', $articles->pluck('id'));
        $articleModelIds = $this->distinctPivotIds('ai_model_article', 'ai_model_id', 'article_id', $articles->pluck('id'));
        $newsToolIds = $this->distinctPivotIds('news_item_tool', 'tool_id', 'news_item_id', $news->pluck('id'));
        $newsModelIds = $this->distinctPivotIds('ai_model_news_item', 'ai_model_id', 'news_item_id', $news->pluck('id'));
        [$toolComparisonIds, $modelComparisonIds] = $this->comparisonEntityIds($comparisons);

        $toolEditorialIds = $articleToolIds->merge($newsToolIds)->merge($toolComparisonIds)->unique();
        $modelEditorialIds = $articleModelIds->merge($newsModelIds)->merge($modelComparisonIds)->unique();

        $sparseTools = $tools->reject(fn (Tool $tool) => $toolEditorialIds->contains((int) $tool->id))->values();
        $sparseModels = $models->reject(fn (AiModel $model) => $modelEditorialIds->contains((int) $model->id))->values();

        $relatedComparisonCoverage = 0;
        $unsafeComparisonLinks = 0;
        foreach ($comparisons as $comparison) {
            $related = $this->links->relatedComparisons($comparison, 4);
            if ($related->isNotEmpty()) {
                $relatedComparisonCoverage++;
            }
            foreach ($related as $candidate) {
                $items = $candidate->getRelation('resolved_items');
                if (! $items instanceof Collection || $items->count() < 2) {
                    $unsafeComparisonLinks++;
                }
            }
        }

        $analysisLinkedNews = $news->filter(fn (NewsItem $item) => $this->links->analysisForNews($item) !== null)->count();
        $benchmarkCount = Benchmark::query()
            ->where('is_active', true)
            ->whereHas('results', fn ($query) => $query->where('verified', true)->where('status', 'verified'))
            ->count();

        $hygiene = [
            'article_non_public_tools' => $this->invalidPivotCount('article_tool', 'articles', 'tools', 'article_id', 'tool_id', function ($query) {
                $query->where('articles.status', 'published')->where('articles.approval_status', 'approved')->where('tools.status', '!=', 'published');
            }),
            'article_non_public_models' => $this->invalidPivotCount('ai_model_article', 'articles', 'ai_models', 'article_id', 'ai_model_id', function ($query) {
                $query->where('articles.status', 'published')->where('articles.approval_status', 'approved')->whereNotIn('ai_models.status', ['active', 'preview']);
            }),
            'news_non_public_tools' => $this->invalidNewsPivotCount('news_item_tool', 'tools', 'tool_id', fn ($query) => $query->where('tools.status', '!=', 'published')),
            'news_non_public_models' => $this->invalidNewsPivotCount('ai_model_news_item', 'ai_models', 'ai_model_id', fn ($query) => $query->whereNotIn('ai_models.status', ['active', 'preview'])),
        ];

        return [
            'coverage' => [
                ['label' => 'Tool → approved article', 'covered' => $articleToolIds->intersect($tools->pluck('id'))->count(), 'eligible' => $tools->count()],
                ['label' => 'Tool → public news', 'covered' => $newsToolIds->intersect($tools->pluck('id'))->count(), 'eligible' => $tools->count()],
                ['label' => 'Tool → comparison', 'covered' => $toolComparisonIds->intersect($tools->pluck('id'))->count(), 'eligible' => $tools->count()],
                ['label' => 'Model → approved article', 'covered' => $articleModelIds->intersect($models->pluck('id'))->count(), 'eligible' => $models->count()],
                ['label' => 'Model → public news', 'covered' => $newsModelIds->intersect($models->pluck('id'))->count(), 'eligible' => $models->count()],
                ['label' => 'Model → comparison', 'covered' => $modelComparisonIds->intersect($models->pluck('id'))->count(), 'eligible' => $models->count()],
                ['label' => 'News → deeper analysis', 'covered' => $analysisLinkedNews, 'eligible' => $news->count()],
                ['label' => 'Comparison → related comparison', 'covered' => $relatedComparisonCoverage, 'eligible' => $comparisons->count()],
                ['label' => 'Benchmark → verified result', 'covered' => $benchmarkCount, 'eligible' => $benchmarkCount],
            ],
            'sparse_tools_count' => $sparseTools->count(),
            'sparse_models_count' => $sparseModels->count(),
            'sparse_tools' => $sparseTools->take(15),
            'sparse_models' => $sparseModels->take(15),
            'hygiene' => $hygiene,
            'hygiene_total' => array_sum($hygiene),
            'unsafe_comparison_links' => $unsafeComparisonLinks,
            'comparison_count' => $comparisons->count(),
        ];
    }

    private function publicNews()
    {
        return NewsItem::query()
            ->where('status', 'published')
            ->whereNull('duplicate_of_id')
            ->where(fn ($query) => $query->whereNull('duplicate_status')->orWhere('duplicate_status', '!=', 'duplicate'));
    }

    private function validComparisons(): Collection
    {
        return Comparison::query()
            ->where('status', 'published')
            ->get()
            ->filter(function (Comparison $comparison) {
                try {
                    $items = $comparison->publicItems();
                    if ($items->count() < 2) {
                        return false;
                    }
                    $comparison->setRelation('resolved_items', $items);
                    return true;
                } catch (\Throwable $e) {
                    return false;
                }
            })
            ->values();
    }

    private function comparisonEntityIds(Collection $comparisons): array
    {
        $toolIds = collect();
        $modelIds = collect();

        foreach ($comparisons as $comparison) {
            $ids = $comparison->getRelation('resolved_items')->pluck('id')->map(fn ($id) => (int) $id);
            if ($comparison->comparable_type === 'tool') {
                $toolIds = $toolIds->merge($ids);
            } else {
                $modelIds = $modelIds->merge($ids);
            }
        }

        return [$toolIds->unique()->values(), $modelIds->unique()->values()];
    }

    private function distinctPivotIds(string $table, string $targetColumn, string $ownerColumn, Collection $ownerIds): Collection
    {
        if (! Schema::hasTable($table) || $ownerIds->isEmpty()) {
            return collect();
        }

        return DB::table($table)
            ->whereIn($ownerColumn, $ownerIds)
            ->distinct()
            ->pluck($targetColumn)
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();
    }

    private function invalidPivotCount(string $pivot, string $ownerTable, string $targetTable, string $ownerColumn, string $targetColumn, callable $constraints): int
    {
        if (! Schema::hasTable($pivot) || ! Schema::hasTable($ownerTable) || ! Schema::hasTable($targetTable)) {
            return 0;
        }

        $query = DB::table($pivot)
            ->join($ownerTable, $ownerTable.'.id', '=', $pivot.'.'.$ownerColumn)
            ->join($targetTable, $targetTable.'.id', '=', $pivot.'.'.$targetColumn);
        $constraints($query);

        return $query->count();
    }

    private function invalidNewsPivotCount(string $pivot, string $targetTable, string $targetColumn, callable $constraints): int
    {
        if (! Schema::hasTable($pivot) || ! Schema::hasTable('news_items') || ! Schema::hasTable($targetTable)) {
            return 0;
        }

        $query = DB::table($pivot)
            ->join('news_items', 'news_items.id', '=', $pivot.'.news_item_id')
            ->join($targetTable, $targetTable.'.id', '=', $pivot.'.'.$targetColumn)
            ->where('news_items.status', 'published')
            ->whereNull('news_items.duplicate_of_id')
            ->where(function ($q) {
                $q->whereNull('news_items.duplicate_status')->orWhere('news_items.duplicate_status', '!=', 'duplicate');
            });
        $constraints($query);

        return $query->count();
    }

    private function percent(int $covered, int $eligible): float
    {
        return $eligible > 0 ? round(($covered / $eligible) * 100, 1) : 100.0;
    }
}
