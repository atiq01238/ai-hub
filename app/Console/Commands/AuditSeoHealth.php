<?php

namespace App\Console\Commands;

use App\Services\Seo\SeoHealthService;
use Illuminate\Console\Command;

class AuditSeoHealth extends Command
{
    protected $signature = 'seo:audit-health {--details : Show sparse entity and stored-pivot detail}';

    protected $description = 'Audit Phase 1-4 SEO health: intent, metadata, semantic-link safety and relationship hygiene.';

    public function handle(SeoHealthService $health): int
    {
        $data = $health->snapshot();
        $summary = $data['summary'];
        $semantic = $data['semantic'];

        $this->info('AI Orbit SEO Health Audit — Phase 4');
        $this->table(['Foundation check', 'Value'], [
            ['Current intent inventory', $summary['intent_total']],
            ['Persisted keyword targets', $summary['persisted_targets'].' ('.$summary['intent_coverage'].'%)'],
            ['Metadata titles aligned', $summary['metadata_aligned'].' / '.$summary['metadata_total'].' ('.$summary['metadata_coverage'].'%)'],
            ['Missing primary keywords', $summary['missing_primary']],
            ['Primary-keyword collision groups', $summary['primary_collisions']],
            ['Missing titles', $summary['missing_titles']],
            ['Missing descriptions', $summary['missing_descriptions']],
            ['Metadata alignment warnings', $summary['metadata_misaligned']],
            ['Duplicate generated title groups', $summary['duplicate_titles']],
            ['Metadata generation errors', $summary['metadata_errors']],
            ['Stale persisted targets', $summary['stale_targets']],
            ['Unsafe generated comparison links', $semantic['unsafe_comparison_links']],
        ]);

        $this->newLine();
        $this->comment('Semantic coverage is descriptive, not a quota. Empty slots are preferred over unrelated filler.');
        $this->table(
            ['Semantic path', 'Covered', 'Eligible', 'Coverage'],
            collect($semantic['coverage'])->map(fn (array $row) => [
                $row['label'],
                $row['covered'],
                $row['eligible'],
                $row['eligible'] > 0 ? number_format(($row['covered'] / $row['eligible']) * 100, 1).'%' : '—',
            ])->all()
        );

        $this->newLine();
        $this->table(['Relationship hygiene warning', 'Count'], [
            ['Approved article → non-public tool pivots', $semantic['hygiene']['article_non_public_tools']],
            ['Approved article → non-public model pivots', $semantic['hygiene']['article_non_public_models']],
            ['Public news → non-public tool pivots', $semantic['hygiene']['news_non_public_tools']],
            ['Public news → non-public model pivots', $semantic['hygiene']['news_non_public_models']],
            ['Published tools with no explicit editorial/comparison edge', $semantic['sparse_tools_count']],
            ['Public models with no explicit editorial/comparison edge', $semantic['sparse_models_count']],
        ]);

        if ($this->option('details')) {
            if ($semantic['sparse_tools']->isNotEmpty()) {
                $this->newLine();
                $this->comment('Sample tools with no explicit editorial/comparison edge:');
                $this->table(['ID', 'Tool', 'Slug'], $semantic['sparse_tools']->map(fn ($tool) => [$tool->id, $tool->name, $tool->slug])->all());
            }

            if ($semantic['sparse_models']->isNotEmpty()) {
                $this->newLine();
                $this->comment('Sample models with no explicit editorial/comparison edge:');
                $this->table(['ID', 'Model', 'Slug'], $semantic['sparse_models']->map(fn ($model) => [$model->id, $model->name, $model->slug])->all());
            }

            if ($data['metadata_warnings']->isNotEmpty()) {
                $this->newLine();
                $this->comment('Metadata warnings:');
                $this->table(['Target', 'Primary keyword', 'Generated title', 'Error'], $data['metadata_warnings']->map(fn ($row) => [
                    $row['target_key'],
                    $row['primary_keyword'],
                    $row['title'] ?: '[missing]',
                    $row['error'] ?: '—',
                ])->all());
            }
        }

        $this->newLine();
        if ($summary['hard_conflicts'] === 0) {
            $this->info('Phase 4 SEO health audit passed with no hard conflicts. Stored pivot/sparse-edge counts remain editorial cleanup signals only.');
            return self::SUCCESS;
        }

        $this->error('Phase 4 SEO health audit found hard conflicts. Review before deployment.');
        return self::FAILURE;
    }
}
