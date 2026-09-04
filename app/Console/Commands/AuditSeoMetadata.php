<?php

namespace App\Console\Commands;

use App\Models\SeoTarget;
use App\Services\Seo\SeoMetadataService;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

class AuditSeoMetadata extends Command
{
    protected $signature = 'seo:audit-metadata {--details : Show sample warnings and generated metadata}';

    protected $description = 'Audit Phase 2 SEO titles and meta descriptions against the persisted Phase 1 intent map.';

    public function handle(SeoMetadataService $metadata): int
    {
        $this->info('AI Orbit SEO Metadata Alignment Audit — Phase 2');

        if (! Schema::hasTable('seo_targets')) {
            $this->error('seo_targets table is missing. Apply Phase 1 and run php artisan migrate first.');
            return self::FAILURE;
        }

        $targets = SeoTarget::query()
            ->orderBy('page_type')
            ->orderBy('target_key')
            ->get();

        if ($targets->isEmpty()) {
            $this->error('No persisted SEO targets found. Run php artisan seo:audit-intent-map --sync first.');
            return self::FAILURE;
        }

        $rows = collect();

        foreach ($targets as $target) {
            try {
                $resolved = $metadata->forTarget($target);
            } catch (\Throwable $e) {
                report($e);
                $rows->push([
                    'target' => $target,
                    'title' => '',
                    'description' => '',
                    'error' => $e->getMessage(),
                    'aligned' => false,
                ]);
                continue;
            }

            $rows->push([
                'target' => $target,
                'title' => $resolved['title'] ?? '',
                'description' => $resolved['description'] ?? '',
                'error' => null,
                'aligned' => $metadata->titleRepresentsPrimary(
                    (string) ($resolved['title'] ?? ''),
                    (string) $target->primary_keyword,
                ),
            ]);
        }

        $summary = $targets
            ->groupBy('page_type')
            ->map(function (Collection $group, string $pageType) use ($rows) {
                $keys = $group->pluck('target_key');
                $matches = $rows->filter(fn ($row) => $keys->contains($row['target']->target_key));

                return [
                    $this->label($pageType),
                    $group->count(),
                    $matches->where('aligned', true)->count(),
                    $matches->filter(fn ($row) => trim($row['title']) !== '')->count(),
                    $matches->filter(fn ($row) => trim($row['description']) !== '')->count(),
                ];
            })
            ->values()
            ->all();

        $this->table(
            ['Page family', 'Targets', 'Keyword aligned', 'Titles', 'Descriptions'],
            $summary,
        );

        $duplicateTitles = $rows
            ->filter(fn ($row) => trim($row['title']) !== '')
            ->groupBy(fn ($row) => $metadata->normalized($row['title']))
            ->filter(fn (Collection $group) => $group->count() > 1);

        $toolPricingLeaks = $rows->filter(function ($row) use ($metadata) {
            if ($row['target']->page_type !== 'tool_detail') {
                return false;
            }
            $title = $metadata->normalized($row['title']);
            return preg_match('/\b(pricing|price|cost)\b/u', $title) === 1;
        });

        $pricingReviewLeaks = $rows->filter(function ($row) use ($metadata) {
            if ($row['target']->page_type !== 'tool_pricing') {
                return false;
            }
            return preg_match('/\breview\b/u', $metadata->normalized($row['title'])) === 1;
        });

        $quality = [
            ['Persisted Phase 1 targets', $targets->count()],
            ['Titles aligned to primary intent', $rows->where('aligned', true)->count()],
            ['Pages missing title', $rows->filter(fn ($row) => trim($row['title']) === '')->count()],
            ['Pages missing meta description', $rows->filter(fn ($row) => trim($row['description']) === '')->count()],
            ['Exact duplicate generated title groups', $duplicateTitles->count()],
            ['Tool-review titles leaking pricing ownership', $toolPricingLeaks->count()],
            ['Tool-pricing titles leaking review ownership', $pricingReviewLeaks->count()],
            ['Titles over 70 characters', $rows->filter(fn ($row) => mb_strlen($row['title']) > 70)->count()],
            ['Descriptions over 165 characters', $rows->filter(fn ($row) => mb_strlen($row['description']) > 165)->count()],
            ['Metadata generation errors', $rows->whereNotNull('error')->count()],
        ];

        $this->newLine();
        $this->table(['Phase 2 quality check', 'Count'], $quality);

        if ($this->option('details')) {
            $this->newLine();
            $this->line('Metadata samples / warnings (max 25 per section)');

            $errors = $rows->whereNotNull('error')->take(25);
            if ($errors->isNotEmpty()) {
                $this->newLine();
                $this->error('Metadata generation errors');
                $this->table(['Target', 'Error'], $errors->map(fn ($row) => [
                    $row['target']->target_key,
                    $row['error'],
                ])->all());
            }

            $misaligned = $rows->where('aligned', false)->take(25);
            if ($misaligned->isNotEmpty()) {
                $this->newLine();
                $this->warn('Primary-intent title mismatches');
                $this->table(['Target', 'Primary keyword', 'Generated title'], $misaligned->map(fn ($row) => [
                    $row['target']->target_key,
                    $row['target']->primary_keyword,
                    $row['title'] ?: '[missing]',
                ])->all());
            }

            if ($duplicateTitles->isNotEmpty()) {
                $this->newLine();
                $this->warn('Duplicate generated title groups');
                $this->table(['Title', 'Owners'], $duplicateTitles->take(25)->map(fn ($group) => [
                    $group->first()['title'],
                    $group->pluck('target.target_key')->join(', '),
                ])->values()->all());
            }

            $sample = $rows
                ->filter(fn ($row) => in_array($row['target']->page_type, [
                    'tool_detail', 'tool_pricing', 'model_detail', 'company_detail',
                    'comparison_detail', 'benchmark_detail', 'category_detail',
                ], true))
                ->groupBy(fn ($row) => $row['target']->page_type)
                ->map(fn ($group) => $group->first())
                ->take(12)
                ->values();

            if ($sample->isNotEmpty()) {
                $this->newLine();
                $this->info('Representative generated metadata');
                $this->table(['Target', 'Title', 'Meta description'], $sample->map(fn ($row) => [
                    $row['target']->target_key,
                    $row['title'],
                    $row['description'],
                ])->all());
            }
        }

        $hardFailures = $rows->where('aligned', false)->count()
            + $rows->filter(fn ($row) => trim($row['title']) === '')->count()
            + $rows->filter(fn ($row) => trim($row['description']) === '')->count()
            + $duplicateTitles->count()
            + $toolPricingLeaks->count()
            + $pricingReviewLeaks->count()
            + $rows->whereNotNull('error')->count();

        $this->newLine();
        if ($hardFailures === 0) {
            $this->info('Phase 2 metadata audit passed with no hard conflicts.');
            return self::SUCCESS;
        }

        $this->warn('Phase 2 metadata audit found items to review before deployment.');
        return self::SUCCESS;
    }

    private function label(string $pageType): string
    {
        return str_replace('_', ' ', $pageType);
    }
}
