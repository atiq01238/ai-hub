<?php

namespace App\Console\Commands;

use App\Models\AiModel;
use App\Models\Article;
use App\Models\NewsItem;
use App\Models\Tool;
use App\Services\Seo\SeoContentQualityService;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;

class AuditSeoContentQuality extends Command
{
    protected $signature = 'seo:audit-content-quality
        {--type=all : all, tool, model, article or news}
        {--details : Show withheld sample records and reasons}
        {--gaps : Show indexable tool profiles that still need enrichment}';

    protected $description = 'Audit AI Orbit detail pages against the Phase 3 index-quality gate without changing data.';

    public function handle(SeoContentQualityService $quality): int
    {
        $type = strtolower((string) $this->option('type'));
        if (! in_array($type, ['all', 'tool', 'model', 'article', 'news'], true)) {
            $this->error('Invalid --type. Use all, tool, model, article or news.');
            return self::FAILURE;
        }

        $groups = collect();
        if (in_array($type, ['all', 'tool'], true)) {
            $groups->put('Tools', $this->assess($this->tools(), fn ($item) => $quality->tool($item)));
        }
        if (in_array($type, ['all', 'model'], true)) {
            $groups->put('Models', $this->assess($this->models(), fn ($item) => $quality->model($item)));
        }
        if (in_array($type, ['all', 'article'], true)) {
            $groups->put('Articles', $this->assess($this->articles(), fn ($item) => $quality->article($item)));
        }
        if (in_array($type, ['all', 'news'], true)) {
            $groups->put('News', $this->assess($this->news(), fn ($item) => $quality->news($item)));
        }

        $this->info('AI Orbit Phase 3 SEO content-quality audit');
        $this->table(
            ['Type', 'Public candidates', 'Indexable', 'Noindex', 'Indexable %'],
            $groups->map(function (array $group, string $label) {
                $total = $group['rows']->count();
                $indexable = $group['rows']->where('indexable', true)->count();
                return [
                    $label,
                    $total,
                    $indexable,
                    max(0, $total - $indexable),
                    $total > 0 ? number_format(($indexable / $total) * 100, 1).'%' : '—',
                ];
            })->values()->all()
        );

        if ($this->option('details')) {
            foreach ($groups as $label => $group) {
                $withheld = $group['rows']->where('indexable', false)->take(25)->values();
                if ($withheld->isEmpty()) {
                    continue;
                }

                $this->newLine();
                $this->warn($label.' withheld from search indexing (sample):');
                $this->table(
                    ['ID', 'Name/title', 'Score', 'Reason'],
                    $withheld->map(fn (array $row) => [
                        $row['id'],
                        mb_strimwidth($row['name'], 0, 60, '…'),
                        $row['score'],
                        mb_strimwidth(implode(' ', $row['reasons']), 0, 120, '…'),
                    ])->all()
                );
            }
        }


        if ($this->option('gaps') && $groups->has('Tools')) {
            $toolGaps = $groups->get('Tools')['rows']
                ->where('needs_enrichment', true)
                ->take(50)
                ->values();

            $this->newLine();
            if ($toolGaps->isEmpty()) {
                $this->info('No tool enrichment gaps detected in the current dataset.');
            } else {
                $this->warn('Published tools are indexable, but these profiles still need enrichment (sample):');
                $this->table(
                    ['ID', 'Name', 'Score', 'Enrichment gap'],
                    $toolGaps->map(fn (array $row) => [
                        $row['id'],
                        mb_strimwidth($row['name'], 0, 48, '…'),
                        $row['score'],
                        mb_strimwidth(implode(' ', $row['quality_warnings']), 0, 120, '…'),
                    ])->all()
                );
            }
        }

        $this->newLine();
        $this->line('No records were deleted or unpublished. Published tools stay indexable in Phase 3.1; strict quality gates remain active for models, articles and news.');

        return self::SUCCESS;
    }

    private function assess(Collection $items, callable $callback): array
    {
        return [
            'rows' => $items->map(function ($item) use ($callback) {
                $assessment = $callback($item);

                return [
                    'id' => $item->id,
                    'name' => (string) ($item->name ?? $item->title ?? $item->headline ?? $item->slug ?? $item->id),
                    'indexable' => (bool) $assessment['indexable'],
                    'score' => (int) $assessment['score'],
                    'reasons' => $assessment['reasons'] ?? [],
                    'quality_warnings' => $assessment['quality_warnings'] ?? [],
                    'needs_enrichment' => (bool) ($assessment['needs_enrichment'] ?? false),
                ];
            })->values(),
        ];
    }

    private function tools(): Collection
    {
        return Tool::query()
            ->where('status', 'published')
            ->with([
                'company:id,name', 'category:id,name', 'subcategoryTerm:id,name',
                'featureTerms:id,name', 'useCaseTerms:id,name', 'platformTerms:id,name',
                'integrationTerms:id,name', 'sources', 'factEvidence', 'pricingPlans.sources',
                'technicalProfile',
                'benchmarkResults' => fn ($query) => $query
                    ->with('benchmark')
                    ->where('verified', true)
                    ->where('status', 'verified'),
            ])
            ->orderBy('id')
            ->get();
    }

    private function models(): Collection
    {
        return AiModel::query()
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
            ->get();
    }

    private function articles(): Collection
    {
        return Article::query()
            ->where('status', 'published')
            ->where('approval_status', 'approved')
            ->with(['relatedToolTerms:id', 'relatedModelTerms:id', 'tagTerms:id'])
            ->orderBy('id')
            ->get();
    }

    private function news(): Collection
    {
        return NewsItem::query()
            ->publiclyVisible()
            ->orderBy('id')
            ->get();
    }
}
