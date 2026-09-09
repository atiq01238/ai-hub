<?php

namespace App\Console\Commands;

use App\Models\AiModel;
use App\Models\Article;
use App\Models\BenchmarkResult;
use App\Models\Comparison;
use App\Models\NewsItem;
use App\Models\PricingPlan;
use App\Models\Tool;
use App\Services\Seo\SeoContentQualityService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Route;

class AuditHomepageReviewerExperience extends Command
{
    protected $signature = 'seo:audit-homepage-quality {--details : Show reviewer-facing notes and trust-route checks}';

    protected $description = 'Audit the compact Phase 8 homepage and reviewer-facing trust signals without modifying records.';

    public function handle(SeoContentQualityService $quality): int
    {
        $publishedTools = Tool::query()->where('status', 'published')->count();
        $activeModels = AiModel::query()->whereIn('status', ['active', 'preview'])->count();
        $approvedArticles = Article::query()
            ->where('status', 'published')
            ->where('approval_status', 'approved')
            ->count();
        $pricingPlans = PricingPlan::query()
            ->whereHas('tool', fn ($query) => $query->where('status', 'published'))
            ->count();
        $verifiedBenchmarks = BenchmarkResult::query()->where('verified', true)->count();

        $qualityNews = NewsItem::query()
            ->publiclyVisible()
            ->latest('published_at')
            ->get()
            ->filter(fn (NewsItem $news) => $quality->news($news)['indexable'])
            ->count();

        $validComparisons = Comparison::query()
            ->where('status', 'published')
            ->get()
            ->filter(function (Comparison $comparison) {
                try {
                    return $comparison->publicItems()->count() >= 2;
                } catch (\Throwable $e) {
                    report($e);
                    return false;
                }
            })
            ->count();

        $this->info('AI Orbit Phase 8 compact homepage & reviewer-experience audit');
        $this->table(
            ['Homepage signal', 'Count', 'Compact homepage use'],
            [
                ['Published tools', $publishedTools, 'Trust metric + Best AI Tools'],
                ['Active/preview models', $activeModels, 'Trust metric only'],
                ['Published approved articles', $approvedArticles, $approvedArticles > 0 ? 'Latest Insights eligible' : 'Guide rows hidden'],
                ['Quality-gated AI news', $qualityNews, $qualityNews > 0 ? 'Latest Insights eligible' : 'News rows hidden'],
                ['Valid comparisons', $validComparisons, $validComparisons > 0 ? 'Popular Comparisons eligible' : 'Section hidden'],
                ['Published-tool pricing plans', $pricingPlans, 'Trust metric only'],
                ['Verified benchmark results', $verifiedBenchmarks, 'Trust metric only'],
            ]
        );

        $trustRoutes = [
            'methodology' => 'Methodology',
            'editorial-guidelines' => 'Editorial Guidelines',
            'sourcing-verification' => 'Sourcing & Verification',
            'corrections-policy' => 'Corrections Policy',
            'disclosures' => 'Disclosures & Independence',
            'privacy' => 'Privacy',
            'terms' => 'Terms',
            'contact' => 'Contact',
        ];

        $routeRows = collect($trustRoutes)->map(function (string $label, string $name) {
            return [$label, Route::has($name) ? 'PASS' : 'MISSING'];
        })->values()->all();

        $this->newLine();
        $this->line('Reviewer-facing trust routes');
        $this->table(['Route', 'Result'], $routeRows);

        if ($this->option('details')) {
            $this->newLine();
            $this->line('Compact homepage behavior:');
            $this->line(' - Homepage is intentionally limited to Hero, Trust Proof, Trending, Best AI Tools, Popular Comparisons and Latest Insights.');
            $this->line(' - Models, pricing, benchmarks, companies, reviews and release feeds remain available on dedicated pages instead of long homepage modules.');
            $this->line(' - Homepage news uses the stricter SEO content-quality gate, not every public feed item.');
            $this->line(' - Desktop Best AI Tools fills the available width with category-populated cards; mobile uses one card per row and surfaces 5 tools.');
            $this->line(' - Desktop comparisons are capped at 3; mobile presentation surfaces the first 2.');
            $this->line(' - Methodology, sourcing verification and corrections remain visible directly below the hero.');
            $this->line(' - No database records are changed by this audit.');
        }

        $missingRoutes = collect($routeRows)->contains(fn (array $row) => $row[1] !== 'PASS');

        if ($missingRoutes) {
            $this->warn('One or more trust routes are missing. Resolve them before the final AdSense pre-review audit.');
        }

        return self::SUCCESS;
    }
}
