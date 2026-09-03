<?php

namespace App\Console\Commands;

use App\Models\AiModel;
use App\Models\ModelEvidenceSource;
use App\Models\ModelPricingSource;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ApplyVerifiedModelEvidence extends Command
{
    protected $signature = 'models:evidence-enrich {--apply : Persist verified pricing/evidence enrichment}';

    protected $description = 'Safely enrich identity-verified AI models with verified pricing structure and official evidence metadata.';

    public function handle(): int
    {
        if (!Schema::hasTable('model_evidence_sources') || !Schema::hasColumn('ai_models', 'pricing_type')) {
            $this->error('Phase 5/6 migration has not been run. Run php artisan migrate first.');
            return self::FAILURE;
        }

        $datasetPath = database_path('data/model_pricing_evidence_verified_2026_08_29.php');
        if (!is_file($datasetPath)) {
            $this->error('Verified model pricing/evidence dataset is missing.');
            return self::FAILURE;
        }

        /** @var array<int,array<string,mixed>> $dataset */
        $dataset = require $datasetPath;
        $apply = (bool) $this->option('apply');

        $stats = [
            'matched' => 0,
            'would_update' => 0,
            'updated' => 0,
            'current' => 0,
            'identity_skipped' => 0,
            'missing' => 0,
            'ambiguous' => 0,
            'price_conflicts' => 0,
            'authorized_price_corrections' => 0,
            'evidence_rows' => 0,
            'pricing_sources' => 0,
        ];

        $table = [];

        foreach ($dataset as $row) {
            $matches = $this->matches((string) $row['company'], (string) $row['current_name']);

            if ($matches->count() === 0) {
                $stats['missing']++;
                $table[] = [$row['company'], $row['current_name'], 'missing', 'No database row'];
                continue;
            }

            if ($matches->count() !== 1) {
                $stats['ambiguous']++;
                $table[] = [$row['company'], $row['current_name'], 'ambiguous', $matches->count().' database rows'];
                continue;
            }

            /** @var AiModel $model */
            $model = $matches->first();
            $stats['matched']++;

            if ($model->identity_status !== 'verified') {
                $stats['identity_skipped']++;
                $table[] = [$row['company'], $row['current_name'], 'identity skipped', (string) $model->identity_status];
                continue;
            }

            $verifiedAt = Carbon::parse((string) $row['pricing_verified_at'])->startOfDay();
            $updates = $this->pricingProfileUpdates($model, $row, $verifiedAt);
            $priceResolution = $this->priceResolution($model, $row);
            $priceConflicts = $priceResolution['conflicts'];
            $authorizedPriceUpdates = $priceResolution['updates'];
            $updates = array_merge($updates, $authorizedPriceUpdates);
            $stats['price_conflicts'] += count($priceConflicts);
            $stats['authorized_price_corrections'] += count($authorizedPriceUpdates);

            $expectedEvidence = $this->expectedEvidenceRows($model, $row, $verifiedAt);
            $missingEvidenceCount = collect($expectedEvidence)->filter(function (array $evidence): bool {
                return !ModelEvidenceSource::where('source_hash', $evidence['source_hash'])->exists();
            })->count();

            $expectedPricingSources = $this->expectedTokenPricingSources($model, $row, $verifiedAt);
            $missingPricingSourceCount = collect($expectedPricingSources)->filter(function (array $source): bool {
                return !ModelPricingSource::where('ai_model_id', $source['ai_model_id'])
                    ->where('metric', $source['metric'])
                    ->where('source_url', $source['source_url'])
                    ->exists();
            })->count();

            $needsWrite = $updates !== [] || $missingEvidenceCount > 0 || $missingPricingSourceCount > 0;

            if (!$apply) {
                if ($needsWrite) {
                    $stats['would_update']++;
                } else {
                    $stats['current']++;
                }

                $detail = collect([
                    $updates !== [] ? implode(', ', array_keys($updates)) : null,
                    $missingEvidenceCount ? $missingEvidenceCount.' evidence row(s)' : null,
                    $missingPricingSourceCount ? $missingPricingSourceCount.' token source(s)' : null,
                    $authorizedPriceUpdates ? 'AUTHORIZED PRICE CORRECTION: '.implode('; ', collect($authorizedPriceUpdates)->map(fn ($value, $field) => $field.' => '.$value)->all()) : null,
                    $priceConflicts ? 'PRICE CONFLICT: '.implode('; ', $priceConflicts) : null,
                ])->filter()->join(' · ');

                $table[] = [
                    $row['company'],
                    $row['current_name'],
                    $needsWrite ? 'would update' : 'current',
                    $detail ?: 'Verified evidence already current',
                ];
                continue;
            }

            DB::transaction(function () use (
                $model,
                $updates,
                $expectedEvidence,
                $expectedPricingSources,
                &$stats
            ): void {
                if ($updates !== []) {
                    $model->forceFill($updates)->saveQuietly();
                }

                foreach ($expectedEvidence as $evidence) {
                    $this->persistEvidence($evidence);
                    $stats['evidence_rows']++;
                }

                foreach ($expectedPricingSources as $source) {
                    $this->persistPricingSource($source);
                    $stats['pricing_sources']++;
                }
            });

            if ($needsWrite) {
                $stats['updated']++;
            } else {
                $stats['current']++;
            }

            $table[] = [
                $row['company'],
                $row['current_name'],
                $needsWrite ? 'evidence updated' : 'current',
                $priceConflicts
                    ? 'Numeric price preserved; conflict: '.implode('; ', $priceConflicts)
                    : ($authorizedPriceUpdates
                        ? 'Authorized verified numeric price correction applied'
                        : $model->pricing_type_label),
            ];
        }

        $this->table(['Company', 'Model', 'Result', 'Pricing / evidence'], $table);
        $this->newLine();

        if (!$apply) {
            $this->info(sprintf(
                'Dry run complete: %d matched, %d would update, %d already current, %d identity-skipped, %d missing, %d ambiguous, %d authorized numeric price correction(s), %d unresolved numeric price conflict(s).',
                $stats['matched'],
                $stats['would_update'],
                $stats['current'],
                $stats['identity_skipped'],
                $stats['missing'],
                $stats['ambiguous'],
                $stats['authorized_price_corrections'],
                $stats['price_conflicts']
            ));
            $this->line('No public model name, slug, canonical URL, sitemap rule, benchmark score or existing numeric price was changed.');
            $this->line('Only dataset rows explicitly allowlisted for numeric correction can change existing numeric prices; all other conflicts remain blocked.');
            $this->line('Review any unresolved numeric price conflicts before rerunning with --apply.');
            return ($stats['missing'] || $stats['ambiguous']) ? self::FAILURE : self::SUCCESS;
        }

        $this->info(sprintf(
            'Verified pricing/evidence enrichment applied: %d matched, %d updated, %d already current, %d identity-skipped, %d missing, %d ambiguous.',
            $stats['matched'],
            $stats['updated'],
            $stats['current'],
            $stats['identity_skipped'],
            $stats['missing'],
            $stats['ambiguous']
        ));
        $this->line(sprintf(
            'Evidence persisted: %d evidence rows processed; %d token pricing source rows processed; %d authorized numeric price correction(s); %d unresolved numeric price conflict(s) preserved without overwrite.',
            $stats['evidence_rows'],
            $stats['pricing_sources'],
            $stats['authorized_price_corrections'],
            $stats['price_conflicts']
        ));
        $this->line('Search Console work is preserved: no public model name, slug, canonical URL, sitemap rule or benchmark field was changed.');

        return ($stats['missing'] || $stats['ambiguous']) ? self::FAILURE : self::SUCCESS;
    }

    private function matches(string $company, string $model): \Illuminate\Support\Collection
    {
        return AiModel::query()
            ->with('company')
            ->whereRaw('LOWER(name) = ?', [mb_strtolower($model)])
            ->whereHas('company', function (Builder $query) use ($company): void {
                $query->whereRaw('LOWER(name) = ?', [mb_strtolower($company)]);
            })
            ->get();
    }

    private function pricingProfileUpdates(AiModel $model, array $row, Carbon $verifiedAt): array
    {
        $target = [
            'pricing_type' => $row['pricing_type'] ?: null,
            'pricing_basis' => $row['pricing_basis'] ?: null,
            'pricing_unit_label' => $row['pricing_unit_label'] ?: null,
            'pricing_summary' => $row['pricing_summary'] ?: null,
            'pricing_verification_status' => $row['pricing_verification_status'] ?: null,
            'pricing_verified_at' => $verifiedAt,
        ];

        $updates = [];
        foreach ($target as $field => $value) {
            $current = $model->{$field};
            $currentComparable = $current instanceof \DateTimeInterface ? $current->format('Y-m-d') : $current;
            $valueComparable = $value instanceof \DateTimeInterface ? $value->format('Y-m-d') : $value;

            if ($currentComparable !== $valueComparable) {
                $updates[$field] = $value;
            }
        }

        return $updates;
    }

    private function priceResolution(AiModel $model, array $row): array
    {
        $conflicts = [];
        $updates = [];
        $allowNumericUpdate = (bool) ($row['allow_numeric_price_update'] ?? false);

        foreach ([
            'input_price_per_million' => $row['input_price_per_million'],
            'output_price_per_million' => $row['output_price_per_million'],
        ] as $field => $verified) {
            if ($verified === null || $model->{$field} === null) {
                continue;
            }

            if (abs((float) $model->{$field} - (float) $verified) <= 0.000001) {
                continue;
            }

            if ($allowNumericUpdate) {
                $updates[$field] = $verified;
                continue;
            }

            $conflicts[] = $field.' DB='.$model->{$field}.' verified='.$verified;
        }

        return [
            'conflicts' => $conflicts,
            'updates' => $updates,
        ];
    }

    private function expectedEvidenceRows(AiModel $model, array $row, Carbon $verifiedAt): array
    {
        $sourceUrl = trim((string) ($row['official_source_url'] ?? ''));
        if ($sourceUrl === '') {
            return [];
        }

        $baseMetadata = [
            'dataset' => 'AI-Orbit verified model audit 2026-08-29',
            'company' => $row['company'],
            'model' => $row['current_name'],
        ];

        $evidence = [
            $this->evidencePayload(
                $model,
                'profile',
                $row['company'].' official model source',
                $sourceUrl,
                (string) ($row['verification_status'] ?: 'verified'),
                $verifiedAt,
                (string) ($row['recommended_action'] ?: 'Official model identity/profile evidence.'),
                $baseMetadata
            ),
            $this->evidencePayload(
                $model,
                'pricing',
                $row['company'].' official pricing/economics source',
                $sourceUrl,
                (string) ($row['pricing_verification_status'] ?: 'verified'),
                $verifiedAt,
                collect([$row['pricing_basis'] ?? null, $row['pricing_summary'] ?? null])->filter()->join(' — '),
                $baseMetadata + [
                    'pricing_type' => $row['pricing_type'],
                    'pricing_unit_label' => $row['pricing_unit_label'],
                    'input_price_per_million' => $row['input_price_per_million'],
                    'output_price_per_million' => $row['output_price_per_million'],
                ]
            ),
        ];

        if (filled($row['status_update']) || $row['pricing_verification_status'] === 'historical_unpriced') {
            $evidence[] = $this->evidencePayload(
                $model,
                'lifecycle',
                $row['company'].' official lifecycle source',
                $sourceUrl,
                filled($row['status_update']) ? (string) $row['status_update'] : 'historical',
                $verifiedAt,
                (string) ($row['recommended_action'] ?: 'Lifecycle status evidence.'),
                $baseMetadata
            );
        }

        return $evidence;
    }

    private function evidencePayload(
        AiModel $model,
        string $type,
        string $sourceName,
        string $sourceUrl,
        string $status,
        Carbon $verifiedAt,
        string $notes,
        array $metadata
    ): array {
        return [
            'ai_model_id' => $model->id,
            'evidence_type' => $type,
            'source_name' => $sourceName,
            'source_url' => $sourceUrl,
            'source_type' => 'official',
            'verification_status' => $status,
            'verified_at' => $verifiedAt,
            'notes' => $notes !== '' ? $notes : null,
            'metadata' => $metadata,
            'source_hash' => hash('sha256', $model->id.'|'.$type.'|'.$sourceUrl),
        ];
    }

    private function expectedTokenPricingSources(AiModel $model, array $row, Carbon $verifiedAt): array
    {
        if (($row['pricing_type'] ?? null) !== 'token' || empty($row['official_source_url'])) {
            return [];
        }

        $sources = [];
        foreach ([
            'input_price_per_million' => $row['input_price_per_million'],
            'output_price_per_million' => $row['output_price_per_million'],
        ] as $metric => $value) {
            if ($value === null) {
                continue;
            }

            $sources[] = [
                'ai_model_id' => $model->id,
                'metric' => $metric,
                'source_name' => $row['company'].' official pricing',
                'source_url' => $row['official_source_url'],
                'source_type' => 'auto',
                'currency' => 'USD',
                'unit' => 'per 1M tokens',
                'enabled' => true,
                'last_checked_at' => $verifiedAt,
                'last_check_status' => 'verified',
                'last_check_message' => 'Verified official-source model pricing snapshot (2026-08-29).',
                'last_detected_value' => (string) $value,
            ];
        }

        return $sources;
    }

    private function persistEvidence(array $payload): void
    {
        $source = ModelEvidenceSource::firstOrNew(['source_hash' => $payload['source_hash']]);
        $incomingVerifiedAt = $payload['verified_at'];
        $newerExisting = $source->exists && $source->verified_at && $source->verified_at->gt($incomingVerifiedAt);

        if ($newerExisting) {
            $source->fill([
                'ai_model_id' => $payload['ai_model_id'],
                'evidence_type' => $payload['evidence_type'],
                'source_name' => $payload['source_name'],
                'source_url' => $payload['source_url'],
                'source_type' => $payload['source_type'],
                'source_hash' => $payload['source_hash'],
            ]);
        } else {
            $source->fill($payload);
        }

        $source->save();
    }

    private function persistPricingSource(array $payload): void
    {
        $source = ModelPricingSource::firstOrNew([
            'ai_model_id' => $payload['ai_model_id'],
            'metric' => $payload['metric'],
            'source_url' => $payload['source_url'],
        ]);

        $incomingCheckedAt = $payload['last_checked_at'];
        $newerExisting = $source->exists && $source->last_checked_at && $source->last_checked_at->gt($incomingCheckedAt);

        if ($newerExisting) {
            $source->fill([
                'source_name' => $payload['source_name'],
                'source_type' => $payload['source_type'],
                'currency' => $payload['currency'],
                'unit' => $payload['unit'],
                'enabled' => true,
            ]);
        } else {
            $source->fill($payload);
        }

        $source->save();
    }
}
