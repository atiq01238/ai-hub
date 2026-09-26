<?php

namespace App\Console\Commands;

use App\Models\Tool;
use App\Services\Seo\SeoContentQualityService;
use App\Services\Seo\ToolSearchIntentAnswerService;
use App\Services\Tools\ToolDataConfidenceService;
use Illuminate\Console\Command;

class AuditRankingRecovery extends Command
{
    protected $signature = 'seo:audit-ranking-recovery {--details : Show each configured recovery profile}';

    protected $description = 'Audit the temporary Search Console ranking-recovery focus without changing records.';

    public function handle(
        SeoContentQualityService $quality,
        ToolDataConfidenceService $confidence,
        ToolSearchIntentAnswerService $answers,
    ): int {
        $toolSlugs = collect(config('seo.impression_focus_tool_slugs', []))->filter()->unique()->values();
        $pricingSlugs = collect(config('seo.impression_focus_pricing_tool_slugs', []))->filter()->unique()->values();
        $allSlugs = $toolSlugs->merge($pricingSlugs)->unique()->values();

        $tools = Tool::query()
            ->whereIn('slug', $allSlugs)
            ->with([
                'company', 'category', 'useCaseTerms', 'featureTerms', 'platformTerms',
                'pricingPlans.sources', 'sources', 'factEvidence', 'technicalProfile', 'integrationTerms',
                'benchmarkResults' => fn ($query) => $query->where('verified', true)->where('status', 'verified'),
            ])
            ->get()
            ->keyBy('slug');

        $toolRows = $toolSlugs->map(function (string $slug) use ($tools, $quality, $confidence, $answers) {
            $tool = $tools->get($slug);
            if (! $tool) {
                return [$slug, 'missing', '—', '—', '—'];
            }

            $dataConfidence = $confidence->score($tool);
            $assessment = $quality->tool($tool, $dataConfidence);
            $quickAnswers = $answers->build($tool, $tool->pricingPlans);

            return [
                $slug,
                $tool->status,
                ($assessment['indexable'] ?? false) ? 'yes' : 'no',
                (string) ($assessment['score'] ?? 0),
                count($quickAnswers),
            ];
        });

        $pricingRows = $pricingSlugs->map(function (string $slug) use ($tools) {
            $tool = $tools->get($slug);
            if (! $tool) {
                return [$slug, 'missing', 0, 'no'];
            }

            return [
                $slug,
                $tool->status,
                $tool->pricingPlans->count(),
                $tool->pricingPlans->isNotEmpty() ? 'yes' : 'no',
            ];
        });

        $this->info('AI Orbit Ranking Recovery Phase 2 audit');
        $this->table(
            ['Signal', 'Count'],
            [
                ['Configured tool-profile focus', $toolSlugs->count()],
                ['Tool focus found in database', $toolRows->where(1, '!=', 'missing')->count()],
                ['Tool focus indexable', $toolRows->where(2, 'yes')->count()],
                ['Configured pricing focus', $pricingSlugs->count()],
                ['Pricing focus with plans', $pricingRows->where(3, 'yes')->count()],
            ]
        );

        if ($this->option('details')) {
            $this->newLine();
            $this->comment('Tool profile recovery focus');
            $this->table(['Slug', 'Status', 'Indexable', 'Quality', 'Quick answers'], $toolRows->all());

            $this->newLine();
            $this->comment('Pricing-intent recovery focus');
            $this->table(['Slug', 'Status', 'Plans', 'Pricing URL available'], $pricingRows->all());
        }

        $missing = $toolRows->where(1, 'missing')->count() + $pricingRows->where(1, 'missing')->count();
        if ($missing > 0) {
            $this->newLine();
            $this->warn('Some configured focus slugs are not present in this database. This is expected when local and production inventories differ; use the live audit as the source of truth.');
        }

        $this->newLine();
        $this->line('Audit only: titles, meta descriptions, robots, canonicals, sitemaps and database records are unchanged.');

        return self::SUCCESS;
    }
}
