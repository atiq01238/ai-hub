<?php

namespace App\Console\Commands;

use App\Models\Comparison;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;

class AuditComparisonSeo extends Command
{
    protected $signature = 'seo:audit-comparisons {--details : Show comparison-level reasons and warnings}';

    protected $description = 'Audit comparison SEO quality, duplicate pair ownership, verification freshness and sitemap readiness.';

    public function handle(): int
    {
        $rows = Comparison::query()
            ->where('status', 'published')
            ->orderByDesc('views')
            ->orderBy('id')
            ->get()
            ->map(function (Comparison $comparison) {
                try {
                    $items = $comparison->publicItems();
                    $comparison->setRelation('resolved_items', $items);
                } catch (\Throwable $e) {
                    report($e);
                    $comparison->setRelation('resolved_items', collect());
                }

                return [
                    'comparison' => $comparison,
                    'assessment' => $comparison->seoAssessment(),
                ];
            });

        $ready = $rows->filter(fn (array $row) => (bool) ($row['assessment']['indexable'] ?? false));
        $utilities = $rows->reject(fn (array $row) => (bool) ($row['assessment']['indexable'] ?? false));
        $reasonCounts = $this->countSignals($rows, 'reasons');
        $warningCounts = $this->countSignals($rows, 'warnings');

        $this->info('AI Orbit Comparison SEO Audit — Phase 4');
        $this->table(['Comparison inventory', 'Count'], [
            ['Published persisted comparisons', $rows->count()],
            ['SEO-ready canonical pairs', $ready->count()],
            ['Utility / review pages', $utilities->count()],
            ['Duplicate pair rows', (int) ($reasonCounts['duplicate_pair'] ?? 0)],
            ['Thin editorial rows', (int) ($reasonCounts['thin_editorial_context'] ?? 0)],
            ['Non-pair persisted rows', (int) ($reasonCounts['not_pair'] ?? 0)],
            ['Missing verification date', (int) ($warningCounts['verification_date_missing'] ?? 0)],
            ['Verification older than 12 months', (int) ($warningCounts['verification_older_than_12_months'] ?? 0)],
        ]);

        if ($this->option('details') && $rows->isNotEmpty()) {
            $this->newLine();
            $this->table(
                ['ID', 'Comparison', 'Pair key', 'SEO', 'Reasons', 'Warnings', 'Views'],
                $rows->map(function (array $row) {
                    /** @var Comparison $comparison */
                    $comparison = $row['comparison'];
                    $assessment = $row['assessment'];

                    return [
                        $comparison->id,
                        $comparison->title,
                        $assessment['pair_key'] ?? '—',
                        ($assessment['indexable'] ?? false) ? 'indexable' : 'review',
                        collect($assessment['reasons'] ?? [])->join(', ') ?: '—',
                        collect($assessment['warnings'] ?? [])->join(', ') ?: '—',
                        number_format((int) $comparison->views),
                    ];
                })->all()
            );
        }

        $this->newLine();
        if (($reasonCounts['duplicate_pair'] ?? 0) > 0) {
            $this->comment('Duplicate pair rows are automatically excluded from comparison sitemaps/internal discovery and redirect to the canonical persisted pair when opened.');
        }
        if (($reasonCounts['thin_editorial_context'] ?? 0) > 0) {
            $this->comment('Thin rows remain publicly available but stay noindex until summary, intent, FAQ or verification context is added.');
        }

        $this->info('Comparison SEO audit complete. Only SEO-ready canonical pairs are promoted to search engines.');

        return self::SUCCESS;
    }

    /** @return array<string,int> */
    private function countSignals(Collection $rows, string $key): array
    {
        return $rows
            ->flatMap(fn (array $row) => $row['assessment'][$key] ?? [])
            ->countBy()
            ->map(fn ($count) => (int) $count)
            ->all();
    }
}
