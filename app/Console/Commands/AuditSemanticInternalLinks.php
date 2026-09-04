<?php

namespace App\Console\Commands;

use App\Models\AiModel;
use App\Models\Article;
use App\Models\Benchmark;
use App\Models\Comparison;
use App\Models\NewsItem;
use App\Models\Tool;
use App\Services\Seo\InternalLinkingService;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AuditSemanticInternalLinks extends Command
{
    protected $signature = 'seo:audit-semantic-links {--details : Show sample sparse pages and semantic comparison links}';

    protected $description = 'Audit Phase 3 semantic internal-link evidence and public-link safety.';

    public function handle(InternalLinkingService $links): int
    {
        $this->info('AI Orbit Semantic Internal Linking Audit — Phase 3');

        $tools = Tool::query()->where('status', 'published')->get(['id', 'name', 'slug', 'company_id', 'category_id']);
        $models = AiModel::query()->whereIn('status', ['active', 'preview'])->get(['id', 'name', 'slug', 'company_id', 'tool_id']);
        $articles = Article::query()->where('status', 'published')->where('approval_status', 'approved')
            ->with(['relatedToolTerms:id', 'relatedModelTerms:id'])
            ->get();
        $news = $this->publicNews()->with(['relatedToolTerms:id', 'relatedModelTerms:id'])->get();
        $comparisons = $this->validComparisons();

        $articleToolIds = $this->distinctPivotIds('article_tool', 'tool_id', 'article_id', $articles->pluck('id'));
        $articleModelIds = $this->distinctPivotIds('ai_model_article', 'ai_model_id', 'article_id', $articles->pluck('id'));
        $newsToolIds = $this->distinctPivotIds('news_item_tool', 'tool_id', 'news_item_id', $news->pluck('id'));
        $newsModelIds = $this->distinctPivotIds('ai_model_news_item', 'ai_model_id', 'news_item_id', $news->pluck('id'));

        [$toolComparisonIds, $modelComparisonIds] = $this->comparisonEntityIds($comparisons);

        $articleEntityLinked = $articles->filter(function (Article $article) {
            return $article->company_id
                || $article->category_id
                || $article->relatedToolTerms->isNotEmpty()
                || $article->relatedModelTerms->isNotEmpty()
                || collect($article->related_tools ?? [])->filter()->isNotEmpty()
                || collect($article->related_models ?? [])->filter()->isNotEmpty();
        })->count();

        $newsEntityLinked = $news->filter(function (NewsItem $item) {
            return $item->company_id
                || $item->relatedToolTerms->isNotEmpty()
                || $item->relatedModelTerms->isNotEmpty()
                || collect($item->related_tools ?? [])->filter()->isNotEmpty();
        })->count();

        $analysisLinkedNews = Article::query()
            ->where('status', 'published')
            ->where('approval_status', 'approved')
            ->whereNotNull('origin_news_item_id')
            ->whereIn('origin_news_item_id', $news->pluck('id'))
            ->distinct('origin_news_item_id')
            ->count('origin_news_item_id');

        $benchmarkCount = Benchmark::query()
            ->where('is_active', true)
            ->whereHas('results', fn ($query) => $query
                ->where('verified', true)
                ->where('status', 'verified')
                ->whereNotNull('benchmarkable_id')
                ->whereNotNull('benchmarkable_type'))
            ->count();

        $relatedComparisonCoverage = 0;
        $invalidSemanticComparisonTargets = 0;
        $comparisonSamples = collect();
        foreach ($comparisons as $comparison) {
            $related = $links->relatedComparisons($comparison, 4);
            if ($related->isNotEmpty()) {
                $relatedComparisonCoverage++;
            }
            foreach ($related as $candidate) {
                $items = $candidate->getRelation('resolved_items');
                if (! $items instanceof Collection || $items->count() < 2) {
                    $invalidSemanticComparisonTargets++;
                }
            }
            if ($this->option('details') && $related->isNotEmpty() && $comparisonSamples->count() < 12) {
                $comparisonSamples->push([
                    $comparison->title,
                    $related->pluck('title')->take(3)->join(' | '),
                ]);
            }
        }

        $coverageRows = [
            ['Tool → approved article (explicit pivot)', $articleToolIds->intersect($tools->pluck('id'))->count(), $tools->count()],
            ['Tool → public news (explicit pivot)', $newsToolIds->intersect($tools->pluck('id'))->count(), $tools->count()],
            ['Tool → published comparison', $toolComparisonIds->intersect($tools->pluck('id'))->count(), $tools->count()],
            ['Model → approved article (explicit pivot)', $articleModelIds->intersect($models->pluck('id'))->count(), $models->count()],
            ['Model → public news (explicit pivot)', $newsModelIds->intersect($models->pluck('id'))->count(), $models->count()],
            ['Model → published comparison', $modelComparisonIds->intersect($models->pluck('id'))->count(), $models->count()],
            ['Article → entity/taxonomy evidence', $articleEntityLinked, $articles->count()],
            ['News → entity evidence', $newsEntityLinked, $news->count()],
            ['News → published deeper analysis', $analysisLinkedNews, $news->count()],
            ['Comparison → semantic related comparison', $relatedComparisonCoverage, $comparisons->count()],
            ['Benchmark → verified entity result', $benchmarkCount, $benchmarkCount],
        ];

        $this->table(
            ['Semantic discovery path', 'Covered', 'Eligible', 'Coverage'],
            collect($coverageRows)->map(fn ($row) => [$row[0], $row[1], $row[2], $this->percent($row[1], $row[2])])->all()
        );

        $invalidArticleToolLinks = DB::table('article_tool')
            ->join('articles', 'articles.id', '=', 'article_tool.article_id')
            ->join('tools', 'tools.id', '=', 'article_tool.tool_id')
            ->where('articles.status', 'published')->where('articles.approval_status', 'approved')
            ->where('tools.status', '!=', 'published')->count();

        $invalidArticleModelLinks = DB::table('ai_model_article')
            ->join('articles', 'articles.id', '=', 'ai_model_article.article_id')
            ->join('ai_models', 'ai_models.id', '=', 'ai_model_article.ai_model_id')
            ->where('articles.status', 'published')->where('articles.approval_status', 'approved')
            ->whereNotIn('ai_models.status', ['active', 'preview'])->count();

        $invalidNewsToolLinks = DB::table('news_item_tool')
            ->join('news_items', 'news_items.id', '=', 'news_item_tool.news_item_id')
            ->join('tools', 'tools.id', '=', 'news_item_tool.tool_id')
            ->where('news_items.status', 'published')
            ->whereNull('news_items.duplicate_of_id')
            ->where(function ($query) {
                $query->whereNull('news_items.duplicate_status')->orWhere('news_items.duplicate_status', '!=', 'duplicate');
            })
            ->where('tools.status', '!=', 'published')->count();

        $invalidNewsModelLinks = DB::table('ai_model_news_item')
            ->join('news_items', 'news_items.id', '=', 'ai_model_news_item.news_item_id')
            ->join('ai_models', 'ai_models.id', '=', 'ai_model_news_item.ai_model_id')
            ->where('news_items.status', 'published')
            ->whereNull('news_items.duplicate_of_id')
            ->where(function ($query) {
                $query->whereNull('news_items.duplicate_status')->orWhere('news_items.duplicate_status', '!=', 'duplicate');
            })
            ->whereNotIn('ai_models.status', ['active', 'preview'])->count();

        $hardConflicts = [
            ['Semantic comparison outputs resolving < 2 public items', $invalidSemanticComparisonTargets],
        ];

        $this->newLine();
        $this->table(['Phase 3 public-link safety check', 'Count'], $hardConflicts);

        $this->newLine();
        $this->comment('Stored relationship hygiene (filtered from public output when invalid)');
        $this->table(['Stored relationship check', 'Count'], [
            ['Stored approved-article → non-public tool pivots', $invalidArticleToolLinks],
            ['Stored approved-article → non-public model pivots', $invalidArticleModelLinks],
            ['Stored public-news → non-public tool pivots', $invalidNewsToolLinks],
            ['Stored public-news → non-public model pivots', $invalidNewsModelLinks],
        ]);

        $this->newLine();
        $this->line('Phase 3 policy: explicit relationship > shared entity > provider/category/capability > recency/popularity tie-breaker.');
        $this->line('Unrelated latest/popular content is not used to fill empty related-content slots.');

        if ($this->option('details')) {
            $this->showDetails($tools, $models, $articleToolIds, $articleModelIds, $newsToolIds, $newsModelIds, $toolComparisonIds, $modelComparisonIds, $comparisonSamples);
        }

        $this->newLine();
        if ($invalidSemanticComparisonTargets === 0) {
            $this->info('Phase 3 semantic-link audit passed with no unsafe generated links.');
            return self::SUCCESS;
        }

        $this->error('Phase 3 semantic-link audit found unsafe generated links. Review before deployment.');
        return self::FAILURE;
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
        if ($ownerIds->isEmpty()) {
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

    private function percent(int $covered, int $eligible): string
    {
        if ($eligible === 0) {
            return '—';
        }

        return number_format(($covered / $eligible) * 100, 1).'%';
    }

    private function showDetails(
        Collection $tools,
        Collection $models,
        Collection $articleToolIds,
        Collection $articleModelIds,
        Collection $newsToolIds,
        Collection $newsModelIds,
        Collection $toolComparisonIds,
        Collection $modelComparisonIds,
        Collection $comparisonSamples,
    ): void {
        $toolSparse = $tools->filter(fn (Tool $tool) =>
            ! $articleToolIds->contains((int) $tool->id)
            && ! $newsToolIds->contains((int) $tool->id)
            && ! $toolComparisonIds->contains((int) $tool->id)
        )->take(25);

        $modelSparse = $models->filter(fn (AiModel $model) =>
            ! $articleModelIds->contains((int) $model->id)
            && ! $newsModelIds->contains((int) $model->id)
            && ! $modelComparisonIds->contains((int) $model->id)
        )->take(25);

        if ($toolSparse->isNotEmpty()) {
            $this->newLine();
            $this->comment('Published tools with no explicit editorial/comparison edge (contextual company/category links still exist):');
            $this->table(['ID', 'Tool', 'Slug'], $toolSparse->map(fn ($tool) => [$tool->id, $tool->name, $tool->slug])->all());
        }

        if ($modelSparse->isNotEmpty()) {
            $this->newLine();
            $this->comment('Public models with no explicit editorial/comparison edge (provider/taxonomy links still exist):');
            $this->table(['ID', 'Model', 'Slug'], $modelSparse->map(fn ($model) => [$model->id, $model->name, $model->slug])->all());
        }

        if ($comparisonSamples->isNotEmpty()) {
            $this->newLine();
            $this->comment('Semantic related-comparison samples:');
            $this->table(['Comparison', 'Related by shared graph'], $comparisonSamples->all());
        }
    }
}
