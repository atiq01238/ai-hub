<?php

namespace App\Console\Commands;

use App\Models\AiModel;
use App\Models\ModelEvidenceSource;
use App\Services\ModelConfidenceService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

class AuditModelEvidence extends Command
{
    protected $signature = 'models:evidence-audit';

    protected $description = 'Audit verified model pricing/evidence coverage and confidence without changing data.';

    public function handle(ModelConfidenceService $confidenceService): int
    {
        if (!Schema::hasTable('model_evidence_sources') || !Schema::hasColumn('ai_models', 'pricing_type')) {
            $this->error('Phase 5/6 migration has not been run.');
            return self::FAILURE;
        }

        $models = AiModel::query()
            ->with(['company', 'featureTerms', 'pricingSources', 'evidenceSources', 'benchmarkResults' => fn ($query) => $query
                ->where('verified', true)
                ->where('status', 'verified')])
            ->get();

        $total = max(1, $models->count());
        $verifiedIdentity = $models->where('identity_status', 'verified')->count();
        $pricingProfiles = $models->whereNotNull('pricing_type')->count();
        $pricingVerified = $models->filter(fn ($model) => filled($model->pricing_verification_status))->count();
        $evidenceModels = ModelEvidenceSource::query()->distinct()->count('ai_model_id');
        $pricingEvidenceModels = ModelEvidenceSource::query()->where('evidence_type', 'pricing')->distinct()->count('ai_model_id');
        $benchmarkModels = $models->filter(fn ($model) => $model->benchmarkResults->isNotEmpty() || $model->benchmark_score !== null)->count();

        $types = $models->whereNotNull('pricing_type')->countBy('pricing_type')->sortDesc();
        $confidence = $models->map(fn ($model) => $confidenceService->build($model));
        $confidenceCounts = $confidence->countBy('class');

        $metrics = [
            ['Total model rows', $models->count(), '100%'],
            ['Identity verified', $verifiedIdentity, $this->pct($verifiedIdentity, $total)],
            ['Pricing profile classified', $pricingProfiles, $this->pct($pricingProfiles, $total)],
            ['Pricing verification status', $pricingVerified, $this->pct($pricingVerified, $total)],
            ['Models with official evidence', $evidenceModels, $this->pct($evidenceModels, $total)],
            ['Models with pricing evidence', $pricingEvidenceModels, $this->pct($pricingEvidenceModels, $total)],
            ['Models with benchmark evidence/composite', $benchmarkModels, $this->pct($benchmarkModels, $total)],
            ['High confidence', (int) ($confidenceCounts['high'] ?? 0), $this->pct((int) ($confidenceCounts['high'] ?? 0), $total)],
            ['Good confidence', (int) ($confidenceCounts['good'] ?? 0), $this->pct((int) ($confidenceCounts['good'] ?? 0), $total)],
            ['Partial / limited confidence', (int) (($confidenceCounts['partial'] ?? 0) + ($confidenceCounts['limited'] ?? 0)), $this->pct((int) (($confidenceCounts['partial'] ?? 0) + ($confidenceCounts['limited'] ?? 0)), $total)],
        ];

        $this->table(['Metric', 'Count', 'Coverage'], $metrics);
        $this->newLine();

        $this->line('Pricing model classification:');
        $this->table(
            ['Pricing type', 'Models'],
            $types->map(fn ($count, $type) => [$type, $count])->values()->all()
        );

        $unresolved = $models->filter(fn ($model) => $model->identity_status !== 'verified' || !$model->pricing_type);
        if ($unresolved->isNotEmpty()) {
            $this->newLine();
            $this->warn('Models intentionally left outside verified Phase 5 enrichment:');
            $this->table(
                ['Company', 'Model', 'Identity status', 'Pricing profile'],
                $unresolved->map(fn ($model) => [
                    $model->company?->name ?? 'Independent',
                    $model->name,
                    $model->identity_status ?: 'unknown',
                    $model->pricing_type ?: 'not classified',
                ])->values()->all()
            );
        }

        return self::SUCCESS;
    }

    private function pct(int $value, int $total): string
    {
        return number_format(($value / max(1, $total)) * 100, 1).'%';
    }
}
