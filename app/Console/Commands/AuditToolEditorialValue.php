<?php

namespace App\Console\Commands;

use App\Models\Tool;
use App\Services\Tools\ToolDataConfidenceService;
use App\Services\Tools\ToolEditorialIntelligenceService;
use Illuminate\Console\Command;

class AuditToolEditorialValue extends Command
{
    protected $signature = 'seo:audit-tool-editorial-value
        {--details : Show a sample of profiles with weak decision-support coverage}';

    protected $description = 'Audit Phase 4 tool decision-intelligence coverage without changing any records.';

    public function handle(ToolEditorialIntelligenceService $editorial, ToolDataConfidenceService $confidence): int
    {
        $tools = Tool::query()
            ->where('status', 'published')
            ->with([
                'company', 'category', 'subcategoryTerm',
                'featureTerms', 'useCaseTerms', 'tagTerms', 'platformTerms', 'integrationTerms',
                'sources', 'factEvidence', 'technicalProfile',
                'pricingPlans.sources',
                'reviews' => fn ($query) => $query->publicContent()->latest('moderated_at')->latest('created_at'),
                'benchmarkResults' => fn ($query) => $query
                    ->with('benchmark')
                    ->where('verified', true)
                    ->where('status', 'verified')
                    ->latest('tested_at')
                    ->latest('id'),
            ])
            ->orderBy('id')
            ->get();

        $rows = $tools->map(function (Tool $tool) use ($editorial, $confidence) {
            $editorReview = $tool->reviews->firstWhere('review_type', 'editorial');
            $dataConfidence = $confidence->score($tool);
            $brief = $editorial->build(
                tool: $tool,
                pricingPlans: $tool->pricingPlans,
                relatedTools: collect(),
                relatedComparisons: collect(),
                benchmarkResults: $tool->benchmarkResults->filter(fn ($result) => $result->benchmark)->unique('benchmark_id')->values(),
                dataConfidence: $dataConfidence,
                editorReview: $editorReview,
            );

            $verifiedSignals = collect($brief['signals'] ?? [])->where('meaningful', true)->count();
            $coverage = collect([
                collect($brief['best_for'] ?? [])->isNotEmpty(),
                collect($brief['strengths'] ?? [])->isNotEmpty(),
                $verifiedSignals >= 2,
                trim((string) ($brief['verdict'] ?? '')) !== '',
            ])->filter()->count();

            return [
                'id' => $tool->id,
                'name' => $tool->name,
                'show' => (bool) ($brief['show'] ?? false),
                'coverage' => $coverage,
                'best_for' => collect($brief['best_for'] ?? [])->count(),
                'strengths' => collect($brief['strengths'] ?? [])->count(),
                'considerations' => collect($brief['considerations'] ?? [])->count(),
                'signals' => $verifiedSignals,
                'profile' => (int) ($dataConfidence['profile_completeness'] ?? $dataConfidence['score'] ?? 0),
            ];
        });

        $this->info('AI Orbit Phase 4 tool editorial-value audit');
        $this->table(
            ['Published tools', 'Decision brief', 'No brief', 'Strong coverage', 'Needs enrichment'],
            [[
                $rows->count(),
                $rows->where('show', true)->count(),
                $rows->where('show', false)->count(),
                $rows->where('coverage', '>=', 3)->count(),
                $rows->where('coverage', '<', 2)->count(),
            ]]
        );

        if ($this->option('details')) {
            $weak = $rows
                ->sortBy('coverage')
                ->sortBy('profile')
                ->take(40)
                ->values();

            $this->newLine();
            $this->warn('Lowest decision-support coverage (sample):');
            $this->table(
                ['ID', 'Tool', 'Brief', 'Coverage', 'Best fit', 'Strengths', 'Signals', 'Profile'],
                $weak->map(fn (array $row) => [
                    $row['id'],
                    mb_strimwidth($row['name'], 0, 44, '…'),
                    $row['show'] ? 'Yes' : 'No',
                    $row['coverage'].'/4',
                    $row['best_for'],
                    $row['strengths'],
                    $row['signals'],
                    $row['profile'].'/100',
                ])->all()
            );
        }

        $this->newLine();
        $this->line('Audit only: no tool, review, pricing or SEO records were modified.');

        return self::SUCCESS;
    }
}
