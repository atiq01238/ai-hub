<?php

namespace App\Console\Commands;

use App\Models\Comparison;
use App\Models\SeoTarget;
use App\Models\Tool;
use App\Services\Seo\SeoIntentMapService;
use App\Services\Seo\SeoMetadataService;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

class AuditCommercialSeoReadiness extends Command
{
    protected $signature = 'seo:audit-commercial-readiness
        {--details : Show parity, metadata, canonical and persistence issue samples}';

    protected $description = 'Final live-safe readiness audit for Pricing Intelligence and canonical Comparison SEO pages.';

    public function handle(SeoIntentMapService $intentMap, SeoMetadataService $metadata): int
    {
        $this->info('AI Orbit Pricing + Comparison SEO — Final Readiness Audit');

        $inventory = $intentMap->inventory();
        $pricingHub = $inventory->where('target_key', 'static:pricing.index')->values();
        $comparisonHub = $inventory->where('target_key', 'static:comparisons.index')->values();
        $pricing = $inventory->where('page_type', 'tool_pricing')->values();
        $comparisons = $inventory->where('page_type', 'comparison_detail')->values();
        $commercial = $pricingHub->concat($comparisonHub)->concat($pricing)->concat($comparisons)->values();

        $resolvedInventory = $intentMap->resolvedInventory($inventory);
        $resolvedCommercial = $resolvedInventory
            ->whereIn('target_key', $commercial->pluck('target_key'))
            ->values();

        $pricingSitemapIds = Tool::query()
            ->where('status', 'published')
            ->whereHas('pricingPlans')
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->sort()
            ->values();

        $comparisonSitemapIds = Comparison::query()
            ->where('status', 'published')
            ->whereNotNull('slug')
            ->get()
            ->filter(fn (Comparison $comparison) => $comparison->isSeoIndexable())
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->sort()
            ->values();

        $pricingIntentIds = $pricing->pluck('targetable_id')->filter()->map(fn ($id) => (int) $id)->unique()->sort()->values();
        $comparisonIntentIds = $comparisons->pluck('targetable_id')->filter()->map(fn ($id) => (int) $id)->unique()->sort()->values();

        $parityIssues = collect();
        $this->addParityIssues($parityIssues, 'pricing', $pricingIntentIds, $pricingSitemapIds);
        $this->addParityIssues($parityIssues, 'comparison', $comparisonIntentIds, $comparisonSitemapIds);

        $persistence = $this->persistenceIssues($commercial, $intentMap);
        $metadataRows = $this->metadataRows($resolvedCommercial, $metadata);
        $metadataIssues = $this->metadataIssues($metadataRows, $metadata);
        $canonicalRows = $this->canonicalRows($pricingIntentIds, $comparisonIntentIds);
        $canonicalIssues = $this->canonicalIssues($canonicalRows);

        $this->table(['Live database inventory', 'Count'], [
            ['Pricing hub owners', $pricingHub->count()],
            ['Comparison hub owners', $comparisonHub->count()],
            ['Pricing detail owners', $pricing->count()],
            ['Pricing sitemap candidates', $pricingSitemapIds->count()],
            ['SEO-ready comparison owners', $comparisons->count()],
            ['Comparison sitemap candidates', $comparisonSitemapIds->count()],
        ]);

        $this->newLine();
        $this->table(['Final readiness check', 'Count'], [
            ['Intent ↔ sitemap parity issues', $parityIssues->count()],
            ['Missing/outdated commercial persisted targets', $persistence['issues']->count()],
            ['Manual/locked commercial targets preserved', $persistence['locked']],
            ['Metadata generation issues', $metadataIssues->count()],
            ['Duplicate generated title groups', $metadataRows->groupBy('normalized_title')->filter(fn (Collection $group, string $key) => $key !== '' && $group->count() > 1)->count()],
            ['Duplicate generated description groups', $metadataRows->groupBy('normalized_description')->filter(fn (Collection $group, string $key) => $key !== '' && $group->count() > 1)->count()],
            ['Canonical URL issues', $canonicalIssues->count()],
        ]);

        if ($this->option('details')) {
            $this->showDetails($parityIssues, $persistence['issues'], $metadataIssues, $canonicalIssues, $metadataRows);
        }

        $this->newLine();
        $this->comment('This command is read-only. It does not create routes, pages or SEO URLs and it does not modify database rows.');
        $this->comment('Counts come from the database where the command is executed, so production is the source of truth for live Pricing/Comparison inventory.');

        $hardIssues = $parityIssues->count()
            + $persistence['issues']->count()
            + $metadataIssues->count()
            + $canonicalIssues->count();

        if ($hardIssues > 0) {
            $this->error('Pricing + Comparison SEO is not ready to lock. Review the issue samples with --details.');
            return self::FAILURE;
        }

        $this->info('Pricing + Comparison SEO is ready: ownership, persistence, metadata, sitemap parity and canonical URLs are clean.');
        return self::SUCCESS;
    }

    private function addParityIssues(Collection $issues, string $family, Collection $intentIds, Collection $sitemapIds): void
    {
        $intentOnly = $intentIds->diff($sitemapIds)->values();
        $sitemapOnly = $sitemapIds->diff($intentIds)->values();

        foreach ($intentOnly as $id) {
            $issues->push(['family' => $family, 'id' => $id, 'issue' => 'intent_only']);
        }
        foreach ($sitemapOnly as $id) {
            $issues->push(['family' => $family, 'id' => $id, 'issue' => 'sitemap_only']);
        }
    }

    private function persistenceIssues(Collection $commercial, SeoIntentMapService $intentMap): array
    {
        if (! Schema::hasTable('seo_targets')) {
            return [
                'issues' => collect([['target' => 'seo_targets', 'issue' => 'table_missing', 'detail' => 'Run migrations before syncing commercial targets.']]),
                'locked' => 0,
            ];
        }

        $stored = SeoTarget::query()
            ->whereIn('target_key', $commercial->pluck('target_key'))
            ->get()
            ->keyBy('target_key');

        $issues = collect();
        $locked = 0;

        foreach ($commercial as $generated) {
            /** @var SeoTarget|null $row */
            $row = $stored->get($generated['target_key']);
            if (! $row) {
                $issues->push([
                    'target' => $generated['target_key'],
                    'issue' => 'missing',
                    'detail' => 'Generated commercial owner is not persisted.',
                ]);
                continue;
            }

            if ($row->is_locked || $row->source === 'manual') {
                $locked++;
                continue;
            }

            $generatedSecondary = collect($generated['secondary_keywords'] ?? [])
                ->map(fn ($keyword) => $intentMap->normalizeKeyword($keyword))
                ->filter()->sort()->values()->all();
            $storedSecondary = collect($row->secondary_keywords ?? [])
                ->map(fn ($keyword) => $intentMap->normalizeKeyword($keyword))
                ->filter()->sort()->values()->all();

            $isOutdated = $intentMap->normalizeKeyword($row->primary_keyword) !== $intentMap->normalizeKeyword($generated['primary_keyword'])
                || $generatedSecondary !== $storedSecondary
                || $row->search_intent !== $generated['search_intent']
                || $row->topic_cluster !== $generated['topic_cluster'];

            if ($isOutdated) {
                $issues->push([
                    'target' => $generated['target_key'],
                    'issue' => 'outdated',
                    'detail' => 'Persisted auto target differs from generated commercial rules.',
                ]);
            }
        }

        return compact('issues', 'locked');
    }

    private function metadataRows(Collection $commercial, SeoMetadataService $metadata): Collection
    {
        if (! Schema::hasTable('seo_targets')) {
            return collect();
        }

        return $commercial->map(function (array $target) use ($metadata) {
            try {
                $seo = $metadata->forKey($target['target_key']);
            } catch (\Throwable $e) {
                report($e);
                return [
                    'target_key' => $target['target_key'],
                    'primary_keyword' => $target['primary_keyword'],
                    'title' => '',
                    'description' => '',
                    'normalized_title' => '',
                    'normalized_description' => '',
                    'error' => $e->getMessage(),
                ];
            }

            return [
                'target_key' => $target['target_key'],
                'primary_keyword' => $target['primary_keyword'],
                'title' => (string) ($seo['title'] ?? ''),
                'description' => (string) ($seo['description'] ?? ''),
                'normalized_title' => $metadata->normalized((string) ($seo['title'] ?? '')),
                'normalized_description' => $metadata->normalized((string) ($seo['description'] ?? '')),
                'error' => null,
            ];
        })->values();
    }

    private function metadataIssues(Collection $rows, SeoMetadataService $metadata): Collection
    {
        $issues = collect();

        foreach ($rows as $row) {
            if (filled($row['error'] ?? null)) {
                $issues->push(['target' => $row['target_key'], 'issue' => 'generation_error', 'detail' => $row['error']]);
                continue;
            }

            if (blank($row['title'])) {
                $issues->push(['target' => $row['target_key'], 'issue' => 'missing_title', 'detail' => 'Generated SEO title is empty.']);
            } elseif (! $metadata->titleRepresentsPrimary($row['title'], $row['primary_keyword'])) {
                $issues->push(['target' => $row['target_key'], 'issue' => 'title_primary_mismatch', 'detail' => $row['title']]);
            }

            if (blank($row['description'])) {
                $issues->push(['target' => $row['target_key'], 'issue' => 'missing_description', 'detail' => 'Generated SEO description is empty.']);
            }
        }

        $titleDuplicates = $rows
            ->filter(fn ($row) => filled($row['normalized_title']))
            ->groupBy('normalized_title')
            ->filter(fn (Collection $group) => $group->count() > 1);

        foreach ($titleDuplicates as $group) {
            $issues->push([
                'target' => $group->pluck('target_key')->join(', '),
                'issue' => 'duplicate_title',
                'detail' => $group->first()['title'],
            ]);
        }

        $descriptionDuplicates = $rows
            ->filter(fn ($row) => filled($row['normalized_description']))
            ->groupBy('normalized_description')
            ->filter(fn (Collection $group) => $group->count() > 1);

        foreach ($descriptionDuplicates as $group) {
            $issues->push([
                'target' => $group->pluck('target_key')->join(', '),
                'issue' => 'duplicate_description',
                'detail' => $group->first()['description'],
            ]);
        }

        return $issues->values();
    }

    private function canonicalRows(Collection $pricingIds, Collection $comparisonIds): Collection
    {
        $rows = collect([
            ['target_key' => 'static:pricing.index', 'url' => route('pricing.index')],
            ['target_key' => 'static:comparisons.index', 'url' => route('comparisons.index')],
        ]);

        Tool::query()
            ->whereIn('id', $pricingIds)
            ->get(['id', 'slug'])
            ->each(fn (Tool $tool) => $rows->push([
                'target_key' => 'pricing.show:'.$tool->id,
                'url' => route('pricing.show', $tool),
            ]));

        Comparison::query()
            ->whereIn('id', $comparisonIds)
            ->get()
            ->each(fn (Comparison $comparison) => $rows->push([
                'target_key' => 'comparisons.show:'.$comparison->id,
                'url' => route('comparisons.show', $comparison),
            ]));

        return $rows->values();
    }

    private function canonicalIssues(Collection $rows): Collection
    {
        $issues = collect();

        foreach ($rows as $row) {
            $url = trim((string) ($row['url'] ?? ''));
            if ($url === '') {
                $issues->push(['target' => $row['target_key'], 'issue' => 'missing_url', 'detail' => 'Canonical route could not be generated.']);
                continue;
            }

            $parts = parse_url($url);
            if ($parts === false || blank($parts['path'] ?? null)) {
                $issues->push(['target' => $row['target_key'], 'issue' => 'invalid_url', 'detail' => $url]);
            }

            if (! empty($parts['query'] ?? null) || ! empty($parts['fragment'] ?? null)) {
                $issues->push(['target' => $row['target_key'], 'issue' => 'canonical_has_state', 'detail' => $url]);
            }
        }

        $duplicates = $rows
            ->filter(fn ($row) => filled($row['url'] ?? null))
            ->groupBy(fn ($row) => rtrim(mb_strtolower((string) $row['url']), '/'))
            ->filter(fn (Collection $group) => $group->count() > 1);

        foreach ($duplicates as $url => $group) {
            $issues->push([
                'target' => $group->pluck('target_key')->join(', '),
                'issue' => 'duplicate_canonical',
                'detail' => $url,
            ]);
        }

        return $issues->values();
    }

    private function showDetails(
        Collection $parityIssues,
        Collection $persistenceIssues,
        Collection $metadataIssues,
        Collection $canonicalIssues,
        Collection $metadataRows,
    ): void {
        if ($parityIssues->isNotEmpty()) {
            $this->newLine();
            $this->table(['Family', 'ID', 'Parity issue'], $parityIssues->take(40)->map(fn ($row) => [$row['family'], $row['id'], $row['issue']])->all());
        }

        if ($persistenceIssues->isNotEmpty()) {
            $this->newLine();
            $this->table(['Target', 'Persistence issue', 'Detail'], $persistenceIssues->take(40)->map(fn ($row) => [$row['target'], $row['issue'], $row['detail']])->all());
        }

        if ($metadataIssues->isNotEmpty()) {
            $this->newLine();
            $this->table(['Target', 'Metadata issue', 'Detail'], $metadataIssues->take(40)->map(fn ($row) => [$row['target'], $row['issue'], $row['detail']])->all());
        }

        if ($canonicalIssues->isNotEmpty()) {
            $this->newLine();
            $this->table(['Target', 'Canonical issue', 'Detail'], $canonicalIssues->take(40)->map(fn ($row) => [$row['target'], $row['issue'], $row['detail']])->all());
        }

        if ($metadataRows->isNotEmpty()) {
            $this->newLine();
            $this->comment('Generated metadata samples');
            $this->table(
                ['Target', 'Title', 'Description'],
                $metadataRows->take(12)->map(fn ($row) => [$row['target_key'], $row['title'], $row['description']])->all()
            );
        }

        if ($parityIssues->isEmpty() && $persistenceIssues->isEmpty() && $metadataIssues->isEmpty() && $canonicalIssues->isEmpty()) {
            $this->newLine();
            $this->line('No final commercial SEO issue samples to show.');
        }
    }
}
