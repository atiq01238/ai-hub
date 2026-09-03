<?php

namespace App\Console\Commands;

use App\Models\AiModel;
use App\Models\Company;
use App\Services\Taxonomy\TaxonomyNormalizer;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ApplyVerifiedModelProfiles extends Command
{
    protected $signature = 'models:profile-enrich
        {--apply : Persist verified profile enrichment. Without this flag the command is dry-run only.}';

    protected $description = 'Backfill verified AI model core data and rich profile notes without changing indexed names, slugs, pricing or benchmarks.';

    public function handle(TaxonomyNormalizer $taxonomy): int
    {
        $path = database_path('data/model_profile_enrichment_verified_2026_08_29.php');

        if (! is_file($path)) {
            $this->error('Verified model profile enrichment data file is missing.');
            return self::FAILURE;
        }

        if (! Schema::hasColumn('ai_models', 'identity_status') ||
            ! Schema::hasColumn('ai_models', 'profile_verification_status') ||
            ! Schema::hasColumn('ai_models', 'profile_verified_at')) {
            $this->error('Required model profile fields are missing. Run php artisan migrate first.');
            return self::FAILURE;
        }

        $rows = require $path;
        if (! is_array($rows)) {
            $this->error('Verified model profile enrichment data is invalid.');
            return self::FAILURE;
        }

        $apply = (bool) $this->option('apply');
        $matched = 0;
        $wouldUpdate = 0;
        $alreadyCurrent = 0;
        $skippedIdentity = 0;
        $missing = 0;
        $taxonomyErrors = 0;
        $report = [];

        $runner = function () use (
            $rows,
            $apply,
            $taxonomy,
            &$matched,
            &$wouldUpdate,
            &$alreadyCurrent,
            &$skippedIdentity,
            &$missing,
            &$taxonomyErrors,
            &$report
        ): void {
            foreach ($rows as $row) {
                $company = Company::query()
                    ->whereRaw('LOWER(name) = ?', [mb_strtolower((string) $row['company'])])
                    ->first();

                if (! $company) {
                    $missing++;
                    $report[] = [$row['company'], $row['current_name'], 'company missing', '—'];
                    continue;
                }

                $models = AiModel::query()
                    ->where('company_id', $company->id)
                    ->whereRaw('LOWER(name) = ?', [mb_strtolower((string) $row['current_name'])])
                    ->limit(2)
                    ->get();

                if ($models->count() !== 1) {
                    $missing++;
                    $report[] = [
                        $row['company'],
                        $row['current_name'],
                        $models->isEmpty() ? 'model missing' : 'ambiguous live match',
                        '—',
                    ];
                    continue;
                }

                /** @var AiModel $model */
                $model = $models->first();
                $matched++;

                // Phase 3/4 is intentionally restricted to identity-verified rows.
                // Version-ambiguous models remain untouched until Phase 2 mapping is resolved.
                if ($model->identity_status !== 'verified') {
                    $skippedIdentity++;
                    $report[] = [$row['company'], $row['current_name'], 'identity not verified', $model->identity_status ?: 'unreviewed'];
                    continue;
                }

                $capabilities = array_values(array_unique(array_filter(array_map(
                    fn ($value) => trim((string) $value),
                    is_array($row['capabilities'] ?? null) ? $row['capabilities'] : []
                ))));

                if ($capabilities !== []) {
                    $capabilities = $taxonomy->canonicalFeatureNames($capabilities);
                    $unknown = $taxonomy->unknownFeatureNames($capabilities);
                    if ($unknown !== []) {
                        $taxonomyErrors++;
                        $report[] = [
                            $row['company'],
                            $row['current_name'],
                            'unknown capability: '.implode(', ', $unknown),
                            'blocked',
                        ];
                        continue;
                    }
                }

                $updates = [
                    'profile_verification_status' => trim((string) ($row['profile_verification_status'] ?? '')) ?: null,
                    'profile_verified_at' => ! empty($row['profile_verified_at'])
                        ? Carbon::parse($row['profile_verified_at'].' 00:00:00')
                        : null,
                ];

                // Additive enrichment: preserve any richer/newer live value already stored.
                // This phase fills verified gaps; it does not downgrade later manual work.
                if (blank($model->capability_notes) && filled($row['capability_notes'] ?? null)) {
                    $updates['capability_notes'] = trim((string) $row['capability_notes']);
                }
                if (blank($model->official_source_url) && filled($row['official_source_url'] ?? null)) {
                    $updates['official_source_url'] = trim((string) $row['official_source_url']);
                }
                if (blank($model->release_date) && filled($row['release_date'] ?? null)) {
                    $updates['release_date'] = $row['release_date'];
                }
                if (blank($model->context_window) && filled($row['context_window'] ?? null)) {
                    $updates['context_window'] = $row['context_window'];
                }

                // Lifecycle changes in the verified snapshot are authoritative. Blank status
                // remains preserve-only; currently this mainly applies explicit deprecations.
                if (filled($row['status'] ?? null)) {
                    $updates['status'] = $row['status'];
                }

                if ($capabilities !== []) {
                    $currentCaps = array_values(array_filter(array_map(
                        fn ($value) => trim((string) $value),
                        is_array($model->capabilities) ? $model->capabilities : []
                    )));
                    $updates['capabilities'] = array_values(array_unique(array_merge($currentCaps, $capabilities)));
                }

                $dirtyFields = [];
                foreach ($updates as $field => $value) {
                    $current = $model->getAttribute($field);

                    if (in_array($field, ['release_date', 'profile_verified_at'], true)) {
                        $currentDate = $current ? Carbon::parse($current)->toDateString() : null;
                        $nextDate = $value ? Carbon::parse($value)->toDateString() : null;
                        if ($currentDate !== $nextDate) $dirtyFields[] = $field;
                        continue;
                    }

                    if ($field === 'capabilities') {
                        $currentCaps = array_values(array_map('strval', is_array($current) ? $current : []));
                        if ($currentCaps !== $value) $dirtyFields[] = $field;
                        continue;
                    }

                    if ((string) ($current ?? '') !== (string) ($value ?? '')) {
                        $dirtyFields[] = $field;
                    }
                }

                $taxonomyDirty = false;
                if ($capabilities !== []) {
                    $featureIds = collect($taxonomy->featureIds($capabilities))->sort()->values();
                    $currentFeatureIds = $model->featureTerms()->pluck('features.id')->sort()->values();
                    $useCaseIds = collect($taxonomy->inferredUseCaseIds($capabilities))->sort()->values();
                    $currentUseCaseIds = $model->useCaseTerms()->pluck('use_cases.id')->sort()->values();

                    $taxonomyDirty = $featureIds->diff($currentFeatureIds)->isNotEmpty()
                        || $useCaseIds->diff($currentUseCaseIds)->isNotEmpty();
                }

                $dirty = $dirtyFields !== [] || $taxonomyDirty;
                if ($dirty) {
                    $wouldUpdate++;
                } else {
                    $alreadyCurrent++;
                }

                if ($apply && $dirty) {
                    // SEO safety: public name, slug, version, pricing, benchmark score,
                    // canonical routes and indexing fields are deliberately untouched.
                    $model->forceFill($updates)->save();

                    if ($capabilities !== []) {
                        $model->featureTerms()->syncWithoutDetaching($taxonomy->featureIds($capabilities));
                        $model->useCaseTerms()->syncWithoutDetaching($taxonomy->inferredUseCaseIds($capabilities));
                    }
                }

                if ($dirty) {
                    $labels = $dirtyFields;
                    if ($taxonomyDirty) $labels[] = 'taxonomy';
                    $report[] = [
                        $row['company'],
                        $row['current_name'],
                        $apply ? 'profile updated' : 'would update',
                        implode(', ', $labels),
                    ];
                }
            }
        };

        if ($apply) {
            DB::transaction($runner);
        } else {
            $runner();
        }

        if ($report !== []) {
            $this->table(['Company', 'Model', 'Result', 'Fields / status'], $report);
        }

        $this->newLine();
        $this->info(sprintf(
            '%s: %d matched, %d %s, %d already current, %d identity-skipped, %d missing/ambiguous, %d taxonomy errors.',
            $apply ? 'Verified profile enrichment applied' : 'Dry run complete',
            $matched,
            $wouldUpdate,
            $apply ? 'updated' : 'would update',
            $alreadyCurrent,
            $skippedIdentity,
            $missing,
            $taxonomyErrors
        ));

        if (! $apply) {
            $this->comment('No database rows were changed. Review the table, then rerun with --apply.');
        } else {
            $this->comment('Search Console work is preserved: no public model name, slug, canonical URL, sitemap rule, pricing or benchmark field was changed.');
        }

        return ($missing === 0 && $taxonomyErrors === 0) ? self::SUCCESS : self::INVALID;
    }
}
