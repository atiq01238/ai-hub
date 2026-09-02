<?php

namespace App\Console\Commands;

use App\Models\AiModel;
use App\Models\Company;
use App\Models\Comparison;
use App\Models\Tool;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;

class AuditSeoContentGraph extends Command
{
    protected $signature = 'seo:audit-content-graph';

    protected $description = 'Audit crawlable internal-link and on-page content coverage for public AI Orbit entities.';

    public function handle(): int
    {
        $this->info('AI Orbit Phase 3 SEO content graph audit');

        $models = AiModel::query()
            ->with(['company', 'tool', 'featureTerms', 'useCaseTerms', 'pricingSources', 'benchmarkResults'])
            ->whereIn('status', ['active', 'preview'])
            ->get();

        $tools = Tool::query()
            ->with(['company', 'category', 'models', 'featureTerms', 'useCaseTerms'])
            ->where('status', 'published')
            ->get();

        $companies = Company::query()
            ->seoIndexable()
            ->withCount([
                'tools as public_tools_count' => fn ($query) => $query->where('status', 'published'),
                'models as public_models_count' => fn ($query) => $query->whereIn('status', ['active', 'preview']),
            ])
            ->get();

        $withheldCompanies = Company::query()->public()->count() - $companies->count();

        [$validComparisons, $modelComparisonIds, $toolComparisonIds] = $this->comparisonCoverage();
        $seoCompanyIds = $companies->pluck('id')->map(fn ($id) => (int) $id);

        $coverage = [
            ['Model → indexable company', $models->filter(fn ($model) => $model->company_id && $seoCompanyIds->contains((int) $model->company_id))->count(), $models->count()],
            ['Model → associated public tool', $models->filter(fn ($model) => $model->tool && $model->tool->status === 'published')->count(), $models->count()],
            ['Model → taxonomy feature/use case', $models->filter(fn ($model) => $model->featureTerms->isNotEmpty() || $model->useCaseTerms->isNotEmpty())->count(), $models->count()],
            ['Model → published comparison', $models->filter(fn ($model) => $modelComparisonIds->contains((int) $model->id))->count(), $models->count()],
            ['Tool → indexable company', $tools->filter(fn ($tool) => $tool->company_id && $seoCompanyIds->contains((int) $tool->company_id))->count(), $tools->count()],
            ['Tool → canonical category', $tools->filter(fn ($tool) => $tool->category && $tool->category->is_active)->count(), $tools->count()],
            ['Tool → public model', $tools->filter(fn ($tool) => $tool->models->contains(fn ($model) => in_array($model->status, ['active', 'preview'], true)))->count(), $tools->count()],
            ['Tool → published comparison', $tools->filter(fn ($tool) => $toolComparisonIds->contains((int) $tool->id))->count(), $tools->count()],
            ['Company → public tool/model', $companies->filter(fn ($company) => ((int) $company->public_tools_count + (int) $company->public_models_count) > 0)->count(), $companies->count()],
        ];

        $this->table(
            ['Internal graph signal', 'Covered', 'Eligible', 'Coverage'],
            collect($coverage)->map(fn ($row) => [
                $row[0],
                $row[1],
                $row[2],
                $this->percent($row[1], $row[2]),
            ])->all()
        );

        $sparseModels = $models->filter(function ($model) {
            return blank($model->capability_notes)
                && blank($model->context_window)
                && $model->input_price_per_million === null
                && $model->output_price_per_million === null
                && $model->benchmark_score === null
                && $model->featureTerms->isEmpty()
                && $model->useCaseTerms->isEmpty()
                && collect($model->capabilities ?? [])->filter()->isEmpty();
        });

        $riskRows = [
            ['Public models with very sparse profile signals', $sparseModels->count()],
            ['Public models without an indexable provider page', $models->reject(fn ($model) => $model->company_id && $seoCompanyIds->contains((int) $model->company_id))->count()],
            ['Published tools without an active category hub', $tools->reject(fn ($tool) => $tool->category && $tool->category->is_active)->count()],
            ['Published tools without an indexable provider page', $tools->reject(fn ($tool) => $tool->company_id && $seoCompanyIds->contains((int) $tool->company_id))->count()],
            ['Indexable companies with no public tool/model relation', $companies->filter(fn ($company) => ((int) $company->public_tools_count + (int) $company->public_models_count) === 0)->count()],
            ['Thin/placeholder companies withheld from discovery', $withheldCompanies],
            ['Published comparisons resolving 2+ public items', $validComparisons->count()],
        ];

        $this->newLine();
        $this->table(['Content/indexing check', 'Count'], $riskRows);

        $this->newLine();
        $this->line('Phase 3 audit complete. This command only reads catalog data and does not modify records.');

        return self::SUCCESS;
    }

    private function comparisonCoverage(): array
    {
        $valid = collect();
        $modelIds = collect();
        $toolIds = collect();

        Comparison::query()
            ->where('status', 'published')
            ->get()
            ->each(function (Comparison $comparison) use ($valid, $modelIds, $toolIds) {
                try {
                    $items = $comparison->publicItems();
                } catch (\Throwable $e) {
                    report($e);
                    return;
                }

                if ($items->count() < 2) {
                    return;
                }

                $valid->push($comparison);

                $target = $comparison->comparable_type === 'tool' ? $toolIds : $modelIds;
                $items->each(fn ($item) => $target->push((int) $item->id));
            });

        return [
            $valid->values(),
            $modelIds->unique()->values(),
            $toolIds->unique()->values(),
        ];
    }

    private function percent(int $covered, int $eligible): string
    {
        if ($eligible === 0) {
            return '—';
        }

        return number_format(($covered / $eligible) * 100, 1).'%';
    }
}
