<?php

namespace App\Console\Commands;

use App\Models\AiModel;
use App\Models\Benchmark;
use App\Models\Comparison;
use App\Models\NewsItem;
use App\Services\Seo\SeoContentQualityService;
use Illuminate\Console\Command;

class AuditSeoRecovery extends Command
{
    protected $signature = 'seo:audit-recovery {--details : Show benchmark tiers, withheld model reasons and comparison slug warnings}';

    protected $description = 'Audit the live-safe SEO recovery crawl/index gates without modifying data.';

    public function handle(SeoContentQualityService $contentQuality): int
    {
        $benchmarks = Benchmark::query()
            ->where('is_active', true)
            ->with(['results' => fn ($query) => $query
                ->where('verified', true)
                ->where('status', 'verified')])
            ->orderBy('name')
            ->get()
            ->map(fn (Benchmark $benchmark) => [
                'benchmark' => $benchmark,
                'assessment' => $benchmark->seoAssessment(),
            ]);

        $benchmarkTiers = $benchmarks->countBy(fn (array $row) => $row['assessment']['tier'] ?? 'C');

        $news = NewsItem::query()->publiclyVisible()->orderByDesc('published_at')->get();
        $newsIndexable = $news->filter(fn (NewsItem $item) => (bool) ($contentQuality->news($item)['indexable'] ?? false));
        $newsSitemap = $newsIndexable->filter(fn (NewsItem $item) => $contentQuality->newsDiscoveryPriority($item));

        $models = AiModel::query()
            ->whereIn('status', ['active', 'preview'])
            ->with([
                'company:id,name', 'featureTerms:id,name', 'useCaseTerms:id,name',
                'pricingSources', 'evidenceSources',
                'benchmarkResults' => fn ($query) => $query
                    ->with('benchmark')
                    ->where('verified', true)
                    ->where('status', 'verified'),
            ])
            ->orderBy('id')
            ->get()
            ->map(fn (AiModel $model) => [
                'model' => $model,
                'assessment' => $contentQuality->model($model),
            ]);

        $modelIndexable = $models->filter(fn (array $row) => (bool) ($row['assessment']['indexable'] ?? false));
        $modelWithheld = $models->reject(fn (array $row) => (bool) ($row['assessment']['indexable'] ?? false));

        $comparisons = Comparison::query()
            ->where('status', 'published')
            ->get()
            ->map(function (Comparison $comparison) {
                try {
                    $comparison->setRelation('resolved_items', $comparison->publicItems());
                } catch (\Throwable $e) {
                    report($e);
                    $comparison->setRelation('resolved_items', collect());
                }

                return [
                    'comparison' => $comparison,
                    'assessment' => $comparison->seoAssessment(),
                ];
            });

        $comparisonReady = $comparisons->filter(fn (array $row) => (bool) ($row['assessment']['indexable'] ?? false));
        $comparisonNotPair = $comparisons->filter(fn (array $row) => in_array('not_pair', $row['assessment']['reasons'] ?? [], true));
        $slugWarnings = $comparisons->filter(fn (array $row) => in_array('stored_slug_mismatch', $row['assessment']['warnings'] ?? [], true));

        $focusSlugs = collect(config('seo.crawl_focus_model_slugs', []))->filter()->unique()->values();
        $focusModels = $focusSlugs->isEmpty()
            ? collect()
            : $models->filter(fn (array $row) => $focusSlugs->contains($row['model']->slug));

        $this->info('AI Orbit Live SEO Recovery Phase 1 audit');
        $this->table(['Recovery signal', 'Count'], [
            ['Benchmark Tier A — direct sitemap priority', (int) ($benchmarkTiers['A'] ?? 0)],
            ['Benchmark Tier B — indexable, natural discovery', (int) ($benchmarkTiers['B'] ?? 0)],
            ['Benchmark Tier C — public, noindex until enriched', (int) ($benchmarkTiers['C'] ?? 0)],
            ['News passing normal index gate', $newsIndexable->count()],
            ['News receiving stronger sitemap priority', $newsSitemap->count()],
            ['Active/preview models assessed', $models->count()],
            ['Models passing existing quality gate', $modelIndexable->count()],
            ['Models withheld by existing quality gate', $modelWithheld->count()],
            ['SEO-ready comparison pairs', $comparisonReady->count()],
            ['Published comparisons classified as non-pair', $comparisonNotPair->count()],
            ['Comparison stored-slug warnings', $slugWarnings->count()],
            ['Configured crawl-focus models found', $focusModels->count().'/'.$focusSlugs->count()],
        ]);

        if ($this->option('details')) {
            if ($benchmarks->isNotEmpty()) {
                $this->newLine();
                $this->comment('Benchmark tier details');
                $this->table(
                    ['Benchmark', 'Tier', 'Verified entities', 'Description chars', 'Source', 'Robots'],
                    $benchmarks->map(fn (array $row) => [
                        $row['benchmark']->name,
                        $row['assessment']['tier'] ?? 'C',
                        (int) ($row['assessment']['verified_entities'] ?? 0),
                        (int) ($row['assessment']['description_chars'] ?? 0),
                        ($row['assessment']['has_source'] ?? false) ? 'yes' : 'no',
                        $row['assessment']['robots'] ?? 'noindex,follow',
                    ])->all()
                );
            }

            if ($modelWithheld->isNotEmpty()) {
                $this->newLine();
                $this->comment('Models withheld by the existing model quality gate — diagnostic only; this patch does not loosen model rules');
                $this->table(
                    ['Model', 'Slug', 'Score', 'Confidence', 'Official', 'Notes', 'Signals', 'Reasons'],
                    $modelWithheld->map(function (array $row) {
                        $assessment = $row['assessment'];
                        $metrics = $assessment['metrics'] ?? [];
                        return [
                            $row['model']->name,
                            $row['model']->slug,
                            (int) ($assessment['score'] ?? 0),
                            (int) ($metrics['confidence_score'] ?? 0),
                            ($metrics['official_evidence'] ?? false) ? 'yes' : 'no',
                            (int) ($metrics['capability_notes_chars'] ?? 0),
                            (int) ($metrics['capability_signals'] ?? 0),
                            collect($assessment['reasons'] ?? [])->join(' | ') ?: '—',
                        ];
                    })->all()
                );
            }

            if ($slugWarnings->isNotEmpty()) {
                $this->newLine();
                $this->comment('Comparison slug mismatches already consolidated by the existing runtime canonical redirect');
                $this->table(
                    ['ID', 'Title', 'Stored slug', 'Canonical slug'],
                    $slugWarnings->map(fn (array $row) => [
                        $row['comparison']->id,
                        $row['comparison']->title,
                        $row['assessment']['signals']['stored_slug'] ?? $row['comparison']->slug,
                        $row['assessment']['signals']['canonical_slug'] ?? '—',
                    ])->all()
                );
            }

            if ($focusSlugs->isNotEmpty()) {
                $this->newLine();
                $this->comment('Configured crawl-focus model status');
                $this->table(
                    ['Slug', 'Found', 'Indexable'],
                    $focusSlugs->map(function (string $slug) use ($models) {
                        $row = $models->first(fn (array $candidate) => $candidate['model']->slug === $slug);
                        return [
                            $slug,
                            $row ? 'yes' : 'no',
                            $row ? (($row['assessment']['indexable'] ?? false) ? 'yes' : 'no') : '—',
                        ];
                    })->all()
                );
            }
        }

        $this->newLine();
        $this->info('Recovery audit complete. No database records were modified. Tool, pricing and company indexing rules are unchanged by this recovery patch.');

        return self::SUCCESS;
    }
}
