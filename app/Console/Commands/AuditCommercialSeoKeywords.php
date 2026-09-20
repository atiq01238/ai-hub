<?php

namespace App\Console\Commands;

use App\Models\Comparison;
use App\Models\SeoTarget;
use App\Models\Tool;
use App\Services\Seo\SeoIntentMapService;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

class AuditCommercialSeoKeywords extends Command
{
    protected $signature = 'seo:audit-commercial-keywords
        {--sync : Persist only current Pricing + Comparison SEO targets; never prune unrelated targets}
        {--details : Show pricing/comparison keyword owners and any rule issues}';

    protected $description = 'Audit Pricing Intelligence and Comparison keyword ownership without creating new URLs.';

    public function handle(SeoIntentMapService $service): int
    {
        $this->info('AI Orbit Pricing + Comparison Keyword Audit');

        $inventory = $service->inventory();
        $pricing = $inventory->where('page_type', 'tool_pricing')->values();
        $comparisons = $inventory->where('page_type', 'comparison_detail')->values();
        $hubs = $inventory->whereIn('target_key', ['static:pricing.index', 'static:comparisons.index'])->values();
        $commercial = $hubs->concat($pricing)->concat($comparisons)->values();

        $toolIds = $pricing->pluck('targetable_id')->filter()->map(fn ($id) => (int) $id)->unique()->values();
        $tools = Tool::query()
            ->with(['pricingPlans:id,tool_id,plan_name,billing_type,billing_unit,monthly_price,yearly_price,api_price_label'])
            ->whereIn('id', $toolIds)
            ->get()
            ->keyBy('id');

        $comparisonIds = $comparisons->pluck('targetable_id')->filter()->map(fn ($id) => (int) $id)->unique()->values();
        $comparisonModels = Comparison::query()
            ->whereIn('id', $comparisonIds)
            ->get()
            ->keyBy('id');

        $pricingIssues = $pricing->flatMap(function (array $target) use ($tools, $service) {
            /** @var Tool|null $tool */
            $tool = $tools->get((int) ($target['targetable_id'] ?? 0));
            if (! $tool) {
                return [[
                    'target' => $target['target_key'],
                    'issue' => 'tool_not_found',
                    'detail' => 'Pricing owner points to a missing tool record.',
                ]];
            }

            $issues = collect();
            $expectedPrimary = $service->normalizeKeyword($tool->name.' pricing');
            if ($service->normalizeKeyword($target['primary_keyword'] ?? '') !== $expectedPrimary) {
                $issues->push([
                    'target' => $target['target_key'],
                    'issue' => 'pricing_primary_owner',
                    'detail' => 'Expected primary: '.$tool->name.' pricing',
                ]);
            }

            $actual = collect($target['secondary_keywords'] ?? [])
                ->map(fn ($keyword) => $service->normalizeKeyword($keyword))
                ->filter();

            foreach ($service->pricingKeywordCluster($tool) as $keyword) {
                if (! $actual->contains($service->normalizeKeyword($keyword))) {
                    $issues->push([
                        'target' => $target['target_key'],
                        'issue' => 'pricing_cluster_missing',
                        'detail' => 'Missing secondary: '.$keyword,
                    ]);
                }
            }

            return $issues;
        })->values();

        $comparisonIssues = $comparisons->flatMap(function (array $target) use ($comparisonModels, $service) {
            /** @var Comparison|null $comparison */
            $comparison = $comparisonModels->get((int) ($target['targetable_id'] ?? 0));
            if (! $comparison) {
                return [[
                    'target' => $target['target_key'],
                    'issue' => 'comparison_not_found',
                    'detail' => 'Comparison keyword owner points to a missing comparison record.',
                ]];
            }

            try {
                $names = $comparison->publicItems()->pluck('name')->filter()->take(2)->values();
            } catch (\Throwable $e) {
                report($e);
                $names = collect();
            }

            if ($names->count() !== 2) {
                return [[
                    'target' => $target['target_key'],
                    'issue' => 'comparison_pair_unresolved',
                    'detail' => 'Could not resolve exactly two public comparison items.',
                ]];
            }

            $issues = collect();
            $expectedPrimary = $service->normalizeKeyword($names[0].' vs '.$names[1]);
            if ($service->normalizeKeyword($target['primary_keyword'] ?? '') !== $expectedPrimary) {
                $issues->push([
                    'target' => $target['target_key'],
                    'issue' => 'comparison_primary_owner',
                    'detail' => 'Expected primary: '.$names[0].' vs '.$names[1],
                ]);
            }

            $actual = collect($target['secondary_keywords'] ?? [])
                ->map(fn ($keyword) => $service->normalizeKeyword($keyword))
                ->filter();

            foreach ($service->comparisonKeywordCluster($comparison, $names) as $keyword) {
                if (! $actual->contains($service->normalizeKeyword($keyword))) {
                    $issues->push([
                        'target' => $target['target_key'],
                        'issue' => 'comparison_cluster_missing',
                        'detail' => 'Missing secondary: '.$keyword,
                    ]);
                }
            }

            return $issues;
        })->values();

        $collisions = $this->secondaryPrimaryCollisions($commercial, $inventory, $service);

        if ($this->option('sync')) {
            if ($pricingIssues->isNotEmpty() || $comparisonIssues->isNotEmpty() || $collisions->isNotEmpty()) {
                $this->error('Commercial-only sync was blocked because keyword ownership issues were found. Re-run with --details.');
                return self::FAILURE;
            }

            try {
                $result = $service->sync($commercial);
            } catch (\Throwable $e) {
                $this->error('Commercial-only sync failed: '.$e->getMessage());
                return self::FAILURE;
            }

            $this->info(sprintf(
                'Commercial-only sync: %d created, %d updated, %d manual/locked targets preserved.',
                $result['created'],
                $result['updated'],
                $result['locked'],
            ));
            $this->comment('Safety: this sync never prunes targets and never writes non-Pricing/Comparison SEO owners.');
            $this->newLine();
        }

        $storedStatus = $this->storedStatus($commercial, $service);

        $this->table(['Commercial keyword inventory', 'Count'], [
            ['Pricing hub owners', $hubs->where('target_key', 'static:pricing.index')->count()],
            ['Comparison hub owners', $hubs->where('target_key', 'static:comparisons.index')->count()],
            ['Pricing detail owners', $pricing->count()],
            ['Comparison detail owners', $comparisons->count()],
            ['Pricing secondary phrases', $pricing->sum(fn ($row) => count($row['secondary_keywords'] ?? []))],
            ['Comparison secondary phrases', $comparisons->sum(fn ($row) => count($row['secondary_keywords'] ?? []))],
        ]);

        $this->newLine();
        $this->table(['Keyword ownership quality', 'Count'], [
            ['Pricing rule issues', $pricingIssues->count()],
            ['Comparison rule issues', $comparisonIssues->count()],
            ['Commercial secondary → other-page primary collisions', $collisions->count()],
            ['Unlocked auto targets needing sync', $storedStatus['outdated']->count()],
            ['Manual/locked commercial targets preserved', $storedStatus['locked']->count()],
            ['Commercial targets not yet persisted', $storedStatus['missing']->count()],
        ]);

        if ($this->option('details')) {
            $this->showDetails($pricing, $comparisons, $pricingIssues, $comparisonIssues, $collisions, $storedStatus, $service);
        }

        $this->newLine();
        $this->comment('No routes or new SEO URLs are generated by this phase. Each existing pricing/comparison URL owns a broader query cluster.');

        if ($pricingIssues->isNotEmpty() || $comparisonIssues->isNotEmpty() || $collisions->isNotEmpty()) {
            $this->error('Commercial keyword audit found ownership problems. Review --details before syncing targets.');
            return self::FAILURE;
        }

        if ($storedStatus['outdated']->isNotEmpty() || $storedStatus['missing']->isNotEmpty()) {
            $this->warn('Generated commercial keyword rules are valid, but persisted commercial targets are not fully current. Run: php artisan seo:audit-commercial-keywords --sync');
        }

        $this->info('Pricing + Comparison keyword ownership is valid.');

        return self::SUCCESS;
    }

    private function secondaryPrimaryCollisions(Collection $commercial, Collection $inventory, SeoIntentMapService $service): Collection
    {
        $primaryOwners = $inventory
            ->mapWithKeys(fn ($row) => [$service->normalizeKeyword($row['primary_keyword'] ?? '') => $row['target_key']])
            ->filter(fn ($owner, $keyword) => $keyword !== '');

        return $commercial->flatMap(function (array $row) use ($primaryOwners, $service) {
            return collect($row['secondary_keywords'] ?? [])->map(function ($secondary) use ($row, $primaryOwners, $service) {
                $normalized = $service->normalizeKeyword($secondary);
                $owner = $primaryOwners->get($normalized);

                if (! $owner || $owner === $row['target_key']) {
                    return null;
                }

                return [
                    'keyword' => $secondary,
                    'primary_owner' => $owner,
                    'secondary_owner' => $row['target_key'],
                ];
            })->filter();
        })->unique(fn ($row) => $service->normalizeKeyword($row['keyword']).'|'.$row['primary_owner'].'|'.$row['secondary_owner'])->values();
    }

    private function storedStatus(Collection $commercial, SeoIntentMapService $service): array
    {
        if (! Schema::hasTable('seo_targets')) {
            return [
                'outdated' => collect(),
                'locked' => collect(),
                'missing' => $commercial,
            ];
        }

        $stored = SeoTarget::query()
            ->whereIn('target_key', $commercial->pluck('target_key'))
            ->get()
            ->keyBy('target_key');

        $missing = $commercial->reject(fn ($row) => $stored->has($row['target_key']))->values();
        $locked = collect();
        $outdated = collect();

        foreach ($commercial as $generated) {
            /** @var SeoTarget|null $row */
            $row = $stored->get($generated['target_key']);
            if (! $row) {
                continue;
            }

            if ($row->is_locked || $row->source === 'manual') {
                $locked->push($row);
                continue;
            }

            $generatedSecondary = collect($generated['secondary_keywords'] ?? [])
                ->map(fn ($keyword) => $service->normalizeKeyword($keyword))
                ->filter()
                ->sort()
                ->values()
                ->all();
            $storedSecondary = collect($row->secondary_keywords ?? [])
                ->map(fn ($keyword) => $service->normalizeKeyword($keyword))
                ->filter()
                ->sort()
                ->values()
                ->all();

            $changed = $service->normalizeKeyword($row->primary_keyword) !== $service->normalizeKeyword($generated['primary_keyword'])
                || $generatedSecondary !== $storedSecondary
                || $row->search_intent !== $generated['search_intent']
                || $row->topic_cluster !== $generated['topic_cluster'];

            if ($changed) {
                $outdated->push([
                    'target_key' => $generated['target_key'],
                    'stored_primary' => $row->primary_keyword,
                    'generated_primary' => $generated['primary_keyword'],
                    'stored_secondary' => $row->secondary_keywords ?? [],
                    'generated_secondary' => $generated['secondary_keywords'] ?? [],
                ]);
            }
        }

        return compact('outdated', 'locked', 'missing');
    }

    private function showDetails(
        Collection $pricing,
        Collection $comparisons,
        Collection $pricingIssues,
        Collection $comparisonIssues,
        Collection $collisions,
        array $storedStatus,
        SeoIntentMapService $service,
    ): void {
        $this->newLine();
        $this->comment('Keyword owner samples (generated V1 rules)');

        if ($pricing->isNotEmpty()) {
            $this->table(
                ['Pricing owner', 'Secondary cluster'],
                $pricing->take(15)->map(fn ($row) => [
                    $row['primary_keyword'],
                    implode(' | ', $row['secondary_keywords'] ?? []),
                ])->all()
            );
        }

        if ($comparisons->isNotEmpty()) {
            $this->newLine();
            $this->table(
                ['Comparison owner', 'Secondary cluster'],
                $comparisons->take(15)->map(fn ($row) => [
                    $row['primary_keyword'],
                    implode(' | ', $row['secondary_keywords'] ?? []),
                ])->all()
            );
        }

        $issues = $pricingIssues->concat($comparisonIssues)->values();
        if ($issues->isNotEmpty()) {
            $this->newLine();
            $this->table(
                ['Target', 'Issue', 'Detail'],
                $issues->take(30)->map(fn ($row) => [$row['target'], $row['issue'], $row['detail']])->all()
            );
        }

        if ($collisions->isNotEmpty()) {
            $this->newLine();
            $this->table(
                ['Keyword', 'Primary owner', 'Also secondary on'],
                $collisions->take(30)->map(fn ($row) => [$row['keyword'], $row['primary_owner'], $row['secondary_owner']])->all()
            );
        }

        if ($storedStatus['outdated']->isNotEmpty()) {
            $this->newLine();
            $this->table(
                ['Target needing sync', 'Generated primary', 'Generated secondary cluster'],
                $storedStatus['outdated']->take(30)->map(fn ($row) => [
                    $row['target_key'],
                    $row['generated_primary'],
                    implode(' | ', $row['generated_secondary']),
                ])->all()
            );
        }

        if ($storedStatus['missing']->isNotEmpty()) {
            $this->newLine();
            $this->table(
                ['Not yet persisted', 'Primary keyword'],
                $storedStatus['missing']->take(30)->map(fn ($row) => [$row['target_key'], $row['primary_keyword']])->all()
            );
        }

        if ($storedStatus['locked']->isNotEmpty()) {
            $this->newLine();
            $this->line('Manual/locked targets are intentionally preserved and are not overwritten by --sync.');
        }
    }
}
