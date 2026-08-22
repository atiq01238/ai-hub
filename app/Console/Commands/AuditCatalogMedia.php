<?php

namespace App\Console\Commands;

use App\Models\AiModel;
use App\Models\Company;
use App\Models\Tool;
use App\Support\MediaUrl;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class AuditCatalogMedia extends Command
{
    protected $signature = 'catalog:media-audit {--fix : Normalize legacy public-media paths when the target file exists}';

    protected $description = 'Audit AI Hub catalog logo paths and optionally normalize legacy storage/ prefixes.';

    public function handle(): int
    {
        $sets = [
            ['Tools', Tool::class],
            ['Models', AiModel::class],
            ['Companies', Company::class],
        ];

        $totals = ['records' => 0, 'with_path' => 0, 'resolved' => 0, 'normalized' => 0, 'missing' => 0, 'fallback' => 0];
        $missingRows = [];

        foreach ($sets as [$label, $modelClass]) {
            $stats = ['records' => 0, 'with_path' => 0, 'resolved' => 0, 'normalized' => 0, 'missing' => 0, 'fallback' => 0];

            $query = $modelClass::query()->orderBy('id');
            if ($modelClass !== Company::class) {
                $query->with('company');
            }

            /** @var Model $record */
            foreach ($query->cursor() as $record) {
                $stats['records']++;
                $raw = trim((string) $record->logo_path);

                if ($raw === '') {
                    if (($record instanceof Tool || $record instanceof AiModel)
                        && $record->company?->logo_path
                        && MediaUrl::exists($record->company->logo_path)) {
                        $stats['fallback']++;
                    }
                    continue;
                }

                $stats['with_path']++;

                if (Str::startsWith($raw, ['http://', 'https://', '//', 'data:'])) {
                    $stats['resolved']++;
                    continue;
                }

                $relative = MediaUrl::diskPath($raw);
                if ($relative && MediaUrl::exists($relative)) {
                    $stats['resolved']++;

                    if ($this->option('fix') && $raw !== $relative) {
                        $record->forceFill(['logo_path' => $relative])->saveQuietly();
                        $stats['normalized']++;
                    }
                    continue;
                }

                $stats['missing']++;
                if (count($missingRows) < 25) {
                    $missingRows[] = [$label, $record->getKey(), $record->name ?? '—', $raw ?: 'NULL'];
                }
            }

            foreach ($stats as $key => $value) {
                $totals[$key] += $value;
            }

            $this->line(sprintf(
                '%s: %d records · %d paths · %d resolved · %d fallback · %d missing%s',
                $label,
                $stats['records'],
                $stats['with_path'],
                $stats['resolved'],
                $stats['fallback'],
                $stats['missing'],
                $this->option('fix') ? ' · '.$stats['normalized'].' normalized' : ''
            ));
        }

        if ($missingRows) {
            $this->newLine();
            $this->warn('Unresolved logo paths (first 25):');
            $this->table(['Type', 'ID', 'Name', 'Stored path'], $missingRows);
        }

        $this->newLine();
        $this->info(sprintf(
            'Catalog media audit complete: %d/%d stored logo paths resolve. %d records use company fallback. %d are unresolved.',
            $totals['resolved'],
            $totals['with_path'],
            $totals['fallback'],
            $totals['missing']
        ));

        if (! $this->option('fix') && $totals['with_path'] > 0) {
            $this->comment('Run php artisan catalog:media-audit --fix to normalize legacy storage/... paths after reviewing this report.');
        }

        return self::SUCCESS;
    }
}
