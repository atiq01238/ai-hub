<?php

namespace App\Console\Commands;

use App\Models\SeoTarget;
use App\Models\Tool;
use App\Services\Seo\SeoIntentMapService;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

class AuditSeoIntentMap extends Command
{
    protected $signature = 'seo:audit-intent-map
        {--sync : Persist/update unlocked auto-generated targets before auditing}
        {--details : Show sample duplicate/conflict/stale target records}';

    protected $description = 'Audit AI Orbit page-level search intent, keyword ownership and cannibalization risks without changing live titles or frontend content.';

    public function handle(SeoIntentMapService $service): int
    {
        $this->info('AI Orbit SEO Intent Map & Cannibalization Audit — Phase 1');

        $inventory = $service->inventory();

        if ($this->option('sync')) {
            if (! Schema::hasTable('seo_targets')) {
                $this->error('seo_targets table is missing. Run: php artisan migrate');
                return self::FAILURE;
            }

            $result = $service->sync($inventory);
            $this->line(sprintf(
                'Intent map sync: %d created, %d updated, %d locked/manual rows preserved.',
                $result['created'],
                $result['updated'],
                $result['locked'],
            ));
            $this->newLine();
        }

        $resolved = $service->resolvedInventory($inventory);
        $stored = Schema::hasTable('seo_targets')
            ? SeoTarget::query()->get()->keyBy('target_key')
            : collect();

        $groupRows = $resolved
            ->groupBy('page_type')
            ->map(function (Collection $rows, string $pageType) use ($stored) {
                $targeted = $rows->filter(fn ($row) => filled($row['primary_keyword'] ?? null))->count();
                $persisted = $rows->filter(fn ($row) => $stored->has($row['target_key']))->count();
                $locked = $rows->filter(fn ($row) => (bool) ($stored->get($row['target_key'])?->is_locked))->count();

                return [
                    str_replace('_', ' ', $pageType),
                    $rows->count(),
                    $targeted,
                    $persisted,
                    $locked,
                    $this->percent($targeted, $rows->count()),
                ];
            })
            ->sortBy(fn ($row) => $row[0])
            ->values();

        $this->table(
            ['Page family', 'Eligible', 'Targeted', 'Persisted', 'Locked', 'Coverage'],
            $groupRows->all()
        );

        $primaryDuplicates = $this->primaryDuplicateGroups($resolved, $service);
        $primarySecondaryConflicts = $this->primarySecondaryConflicts($resolved, $service);
        $toolPricing = $this->toolPricingOwnership($resolved, $service);
        $stale = $this->staleTargets($inventory, $stored);
        $missing = $resolved->filter(fn ($row) => blank($row['primary_keyword'] ?? null));

        $this->newLine();
        $this->table(
            ['Intent-map quality check', 'Count'],
            [
                ['Indexable pages in current intent inventory', $resolved->count()],
                ['Pages missing a primary keyword', $missing->count()],
                ['Exact primary-keyword collision groups', $primaryDuplicates->count()],
                ['Primary keyword reused as another page secondary target', $primarySecondaryConflicts->count()],
                ['Tool review ↔ pricing ownership pairs', $toolPricing['pairs']],
                ['Tool review ↔ pricing ownership conflicts', $toolPricing['conflicts']->count()],
                ['Persisted targets no longer in indexable inventory', $stale->count()],
            ]
        );

        $this->newLine();
        $intentRows = $resolved
            ->countBy('search_intent')
            ->sortDesc()
            ->map(fn ($count, $intent) => [str_replace('_', ' ', $intent), $count])
            ->values();
        $this->table(['Search intent', 'Pages'], $intentRows->all());

        if (! Schema::hasTable('seo_targets')) {
            $this->newLine();
            $this->warn('Persistence is not installed yet. Run php artisan migrate, then seo:audit-intent-map --sync.');
        } elseif (! $this->option('sync') && $stored->isEmpty()) {
            $this->newLine();
            $this->comment('No targets are persisted yet. Run seo:audit-intent-map --sync after reviewing this generated map.');
        }

        $this->newLine();
        $this->comment('Phase 1 intentionally does not change live titles/meta, canonical tags, sitemaps or frontend UI.');
        $this->comment('The current Tool SEO title template still mentions Pricing; Phase 2 will align live metadata with the separate tool-review and pricing intent owners created here.');

        if ($this->option('details')) {
            $this->showDetails($primaryDuplicates, $primarySecondaryConflicts, $toolPricing['conflicts'], $stale);
        }

        if ($missing->isNotEmpty()) {
            $this->error('Intent audit found pages without primary keywords.');
            return self::FAILURE;
        }

        $this->newLine();
        $this->info('Phase 1 intent-map audit complete.');

        return self::SUCCESS;
    }

    private function primaryDuplicateGroups(Collection $rows, SeoIntentMapService $service): Collection
    {
        return $rows
            ->groupBy(fn ($row) => $service->normalizeKeyword($row['primary_keyword'] ?? ''))
            ->filter(fn (Collection $group, string $keyword) => $keyword !== '' && $group->count() > 1)
            ->map(fn (Collection $group, string $keyword) => [
                'keyword' => $keyword,
                'targets' => $group->pluck('target_key')->values()->all(),
            ])
            ->values();
    }

    private function primarySecondaryConflicts(Collection $rows, SeoIntentMapService $service): Collection
    {
        $primaryOwners = $rows
            ->mapWithKeys(fn ($row) => [$service->normalizeKeyword($row['primary_keyword'] ?? '') => $row['target_key']])
            ->filter(fn ($owner, $keyword) => $keyword !== '');

        return $rows->flatMap(function ($row) use ($primaryOwners, $service) {
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
        })->unique(fn ($row) => $row['keyword'].'|'.$row['primary_owner'].'|'.$row['secondary_owner'])->values();
    }

    private function toolPricingOwnership(Collection $rows, SeoIntentMapService $service): array
    {
        $byKey = $rows->keyBy('target_key');
        $pricingTools = Tool::query()
            ->where('status', 'published')
            ->whereHas('pricingPlans')
            ->pluck('id')
            ->map(fn ($id) => (int) $id);

        $conflicts = collect();

        foreach ($pricingTools as $toolId) {
            $review = $byKey->get('tools.show:'.$toolId);
            $pricing = $byKey->get('pricing.show:'.$toolId);
            if (! $review || ! $pricing) {
                continue;
            }

            $pricingPrimary = $service->normalizeKeyword($pricing['primary_keyword']);
            $reviewPrimary = $service->normalizeKeyword($review['primary_keyword']);
            $reviewSecondaries = collect($review['secondary_keywords'] ?? [])->map(fn ($keyword) => $service->normalizeKeyword($keyword));

            if ($reviewPrimary === $pricingPrimary || $reviewSecondaries->contains($pricingPrimary)) {
                $conflicts->push([
                    'tool_id' => $toolId,
                    'review' => $review['primary_keyword'],
                    'pricing' => $pricing['primary_keyword'],
                ]);
            }
        }

        return [
            'pairs' => $pricingTools->count(),
            'conflicts' => $conflicts,
        ];
    }

    private function staleTargets(Collection $inventory, Collection $stored): Collection
    {
        if ($stored->isEmpty()) {
            return collect();
        }

        $liveKeys = $inventory->pluck('target_key')->flip();

        return $stored->reject(fn (SeoTarget $target) => $liveKeys->has($target->target_key))->values();
    }

    private function showDetails(Collection $duplicates, Collection $secondaryConflicts, Collection $toolPricingConflicts, Collection $stale): void
    {
        $this->newLine();
        $this->comment('Intent-map detail samples (max 25 per section)');

        if ($duplicates->isNotEmpty()) {
            $this->newLine();
            $this->table(
                ['Duplicate primary keyword', 'Owners'],
                $duplicates->take(25)->map(fn ($row) => [$row['keyword'], implode(', ', $row['targets'])])->all()
            );
        }

        if ($secondaryConflicts->isNotEmpty()) {
            $this->newLine();
            $this->table(
                ['Keyword', 'Primary owner', 'Also secondary on'],
                $secondaryConflicts->take(25)->map(fn ($row) => [$row['keyword'], $row['primary_owner'], $row['secondary_owner']])->all()
            );
        }

        if ($toolPricingConflicts->isNotEmpty()) {
            $this->newLine();
            $this->table(
                ['Tool ID', 'Review target', 'Pricing target'],
                $toolPricingConflicts->take(25)->map(fn ($row) => [$row['tool_id'], $row['review'], $row['pricing']])->all()
            );
        }

        if ($stale->isNotEmpty()) {
            $this->newLine();
            $this->table(
                ['Stale target key', 'Primary keyword', 'Source', 'Locked'],
                $stale->take(25)->map(fn (SeoTarget $target) => [$target->target_key, $target->primary_keyword, $target->source, $target->is_locked ? 'yes' : 'no'])->all()
            );
        }

        if ($duplicates->isEmpty() && $secondaryConflicts->isEmpty() && $toolPricingConflicts->isEmpty() && $stale->isEmpty()) {
            $this->line('No detailed collision/stale samples to show.');
        }
    }

    private function percent(int $value, int $total): string
    {
        return $total > 0 ? number_format(($value / $total) * 100, 1).'%' : '—';
    }
}
