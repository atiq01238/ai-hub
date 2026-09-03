<?php

namespace App\Console\Commands;

use App\Models\AiModel;
use App\Models\Company;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class ApplyModelIdentityAudit extends Command
{
    protected $signature = 'models:identity-audit
        {--apply : Persist the identity audit. Without this flag the command is dry-run only.}';

    protected $description = 'Apply the verified AI model identity audit without changing public model names, slugs, or indexed URLs.';

    public function handle(): int
    {
        $verifiedPath = database_path('data/model_identity_verified_2026_08_29.php');
        $reviewPath = database_path('data/model_identity_audit_2026_08_29.php');

        if (! is_file($verifiedPath) || ! is_file($reviewPath)) {
            $this->error('Model identity audit data files are missing.');
            return self::FAILURE;
        }

        $verifiedRows = require $verifiedPath;
        $reviewRows = require $reviewPath;
        if (! is_array($verifiedRows) || ! is_array($reviewRows)) {
            $this->error('Model identity audit data is invalid.');
            return self::FAILURE;
        }

        $rows = [];
        foreach ($verifiedRows as $row) {
            $rows[] = $row + [
                'official_model_id' => null,
                'identity_status' => 'verified',
                'notes' => 'Identity verified from the AI Orbit official-source model audit dated 2026-08-29.',
            ];
        }
        foreach ($reviewRows as $row) {
            $rows[] = $row;
        }

        $apply = (bool) $this->option('apply');
        $verifiedAt = Carbon::parse('2026-08-29 00:00:00');
        $found = 0;
        $missing = 0;
        $changed = 0;
        $verified = 0;
        $needsMapping = 0;
        $invalidIdentity = 0;
        $invalidAlreadyAbsent = 0;
        $report = [];

        $runner = function () use (
            $rows,
            $apply,
            $verifiedAt,
            &$found,
            &$missing,
            &$changed,
            &$verified,
            &$needsMapping,
            &$invalidIdentity,
            &$invalidAlreadyAbsent,
            &$report
        ): void {
            foreach ($rows as $row) {
                $company = Company::query()
                    ->whereRaw('LOWER(name) = ?', [mb_strtolower((string) $row['company'])])
                    ->first();

                /** @var AiModel|null $model */
                $model = null;

                if (! $company) {
                    // The invalid audit row is intentionally provider-neutral. The live
                    // record can still be attached to an old/incorrect provider. Resolve
                    // it by exact model name only when that match is unique; never use
                    // this fallback for verified or needs_mapping identities.
                    if (($row['identity_status'] ?? null) !== 'invalid') {
                        $missing++;
                        $report[] = [$row['company'], $row['current_name'], 'company missing', $row['identity_status']];
                        continue;
                    }

                    $invalidMatches = AiModel::query()
                        ->whereRaw('LOWER(name) = ?', [mb_strtolower((string) $row['current_name'])])
                        ->limit(2)
                        ->get();

                    if ($invalidMatches->isEmpty()) {
                        // Desired state: an invalid catalog record that has already been
                        // removed should not make the audit fail and must never be
                        // recreated just to satisfy the audit manifest.
                        $invalidAlreadyAbsent++;
                        $report[] = [$row['company'], $row['current_name'], 'already absent', $row['identity_status']];
                        continue;
                    }

                    if ($invalidMatches->count() !== 1) {
                        $missing++;
                        $report[] = [$row['company'], $row['current_name'], 'ambiguous invalid match', $row['identity_status']];
                        continue;
                    }

                    $model = $invalidMatches->first();
                } else {
                    $models = AiModel::query()
                        ->where('company_id', $company->id)
                        ->whereRaw('LOWER(name) = ?', [mb_strtolower((string) $row['current_name'])])
                        ->limit(2)
                        ->get();

                    if ($models->count() !== 1) {
                        $missing++;
                        $state = $models->isEmpty() ? 'model missing' : 'ambiguous live match';
                        $report[] = [$row['company'], $row['current_name'], $state, $row['identity_status']];
                        continue;
                    }

                    $model = $models->first();
                }
                $found++;

                match ($row['identity_status']) {
                    'verified' => $verified++,
                    'needs_mapping' => $needsMapping++,
                    'invalid' => $invalidIdentity++,
                    default => null,
                };

                $updates = [
                    'official_name' => $row['official_name'] ?: null,
                    'official_model_id' => $row['official_model_id'] ?: null,
                    'identity_status' => $row['identity_status'],
                    'identity_notes' => $row['notes'] ?: null,
                    'identity_verified_at' => $verifiedAt,
                ];

                // Provenance is additive here. Never replace a source already stored by
                // a newer import or manual verification pass.
                if (blank($model->official_source_url) && ! empty($row['source_url'])) {
                    $updates['official_source_url'] = $row['source_url'];
                }

                $dirty = collect($updates)->contains(function ($value, $key) use ($model) {
                    if ($key === 'identity_verified_at') {
                        return optional($model->identity_verified_at)?->toDateString() !== optional($value)?->toDateString();
                    }

                    return $model->getAttribute($key) != $value;
                });

                if ($dirty) {
                    $changed++;
                }

                if ($apply) {
                    // SEO safety: deliberately does NOT touch public name, slug, status,
                    // release date, pricing, benchmarks, taxonomy, sitemap or canonical data.
                    $model->forceFill($updates)->save();
                }

                if ($row['identity_status'] !== 'verified' || $dirty) {
                    $report[] = [
                        $row['company'],
                        $row['current_name'],
                        $dirty ? ($apply ? 'metadata updated' : 'would update') : 'already current',
                        $row['identity_status'],
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
            $this->table(['Company', 'Current model', 'Result', 'Identity status'], $report);
        }

        $this->newLine();
        $this->info(sprintf(
            '%s: %d matched (%d verified, %d need mapping, %d invalid), %d invalid already absent, %d missing/ambiguous, %d %s.',
            $apply ? 'Identity audit applied' : 'Dry run complete',
            $found,
            $verified,
            $needsMapping,
            $invalidIdentity,
            $invalidAlreadyAbsent,
            $missing,
            $changed,
            $apply ? 'updated' : 'would update'
        ));

        if (! $apply) {
            $this->comment('No database rows were changed. Run again with --apply after reviewing the table.');
        } else {
            $this->comment('Public model names/slugs and all Search Console indexing work were intentionally preserved.');
        }

        return $missing === 0 ? self::SUCCESS : self::INVALID;
    }
}
