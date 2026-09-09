<?php

namespace App\Console\Commands;

use App\Models\Article;
use App\Services\Articles\ArticleEditorialQualityService;
use Illuminate\Console\Command;

class AuditArticleEditorialQuality extends Command
{
    protected $signature = 'articles:audit-editorial-quality
        {--details : Show per-article results}
        {--only-published : Limit the audit to published + approved articles}
        {--gaps : Show only articles that still need enrichment}';

    protected $description = 'Audit AI Orbit article depth, structure and search-index readiness without modifying content.';

    public function handle(ArticleEditorialQualityService $quality): int
    {
        $query = Article::query()->with(['relatedToolTerms:id', 'relatedModelTerms:id', 'tagTerms:id']);

        if ($this->option('only-published')) {
            $query->where('status', 'published')->where('approval_status', 'approved');
        }

        $articles = $query->orderBy('id')->get();
        if ($articles->isEmpty()) {
            $this->warn('No articles matched the audit filters.');
            return self::SUCCESS;
        }

        $rows = $articles->map(function (Article $article) use ($quality): array {
            return [
                'article' => $article,
                'assessment' => $quality->assess($article),
            ];
        });

        $indexable = $rows->filter(fn (array $row) => (bool) ($row['assessment']['indexable'] ?? false))->count();
        $needsEnrichment = $rows->filter(fn (array $row) => (bool) ($row['assessment']['needs_enrichment'] ?? false))->count();
        $underPreferredDepth = $rows->filter(fn (array $row) => (int) data_get($row, 'assessment.metrics.word_count', 0) < (int) config('seo_content_quality.article.preferred_words', 700))->count();

        $this->info('AI Orbit Phase 5 article editorial-quality audit');
        $this->table(['Metric', 'Count'], [
            ['Articles checked', $rows->count()],
            ['Indexable', $indexable],
            ['Noindex', $rows->count() - $indexable],
            ['Enrichment recommended', $needsEnrichment],
            ['Below preferred depth', $underPreferredDepth],
        ]);

        if ($this->option('details') || $this->option('gaps')) {
            $detailRows = $rows;
            if ($this->option('gaps')) {
                $detailRows = $detailRows->filter(fn (array $row) => (bool) ($row['assessment']['needs_enrichment'] ?? false) || ! (bool) ($row['assessment']['indexable'] ?? false));
            }

            $this->newLine();
            $this->table(
                ['ID', 'Title', 'Words', 'H2', 'FAQ', 'Score', 'Index', 'Primary gap'],
                $detailRows->map(function (array $row): array {
                    /** @var Article $article */
                    $article = $row['article'];
                    $assessment = $row['assessment'];
                    $gap = collect($assessment['reasons'] ?? [])
                        ->merge($assessment['quality_warnings'] ?? [])
                        ->first() ?: '—';

                    return [
                        $article->id,
                        mb_strimwidth($article->title, 0, 48, '…'),
                        (int) data_get($assessment, 'metrics.word_count', 0),
                        (int) data_get($assessment, 'metrics.h2_count', 0),
                        (int) data_get($assessment, 'metrics.faq_count', 0),
                        (int) ($assessment['score'] ?? 0),
                        ($assessment['indexable'] ?? false) ? 'YES' : 'NO',
                        mb_strimwidth((string) $gap, 0, 72, '…'),
                    ];
                })->values()->all()
            );
        }

        $this->newLine();
        $this->line('This audit does not rewrite, publish, unpublish or delete any article.');
        $this->line('Preferred depth is guidance, not a forced word-count target. Expand only where examples, evidence or explanation add real value.');

        return self::SUCCESS;
    }
}
