<?php

namespace App\Console\Commands;

use App\Models\AiModel;
use App\Models\Article;
use App\Models\Benchmark;
use App\Models\Category;
use App\Models\Company;
use App\Models\Comparison;
use App\Models\Feature;
use App\Models\NewsItem;
use App\Models\Review;
use App\Models\Subcategory;
use App\Models\Tool;
use App\Models\UseCase;
use Illuminate\Console\Command;

class AuditSeoIndexing extends Command
{
    protected $signature = 'seo:audit-indexing';

    protected $description = 'Audit public sitemap eligibility and crawl-dead-end risks for AI Orbit.';

    public function handle(): int
    {
        $companyQuery = Company::query()->seoIndexable();

        $newsQuery = NewsItem::query()
            ->where('status', 'published')
            ->whereNull('duplicate_of_id')
            ->where(fn ($query) => $query->whereNull('duplicate_status')->orWhere('duplicate_status', '!=', 'duplicate'));

        $reviewQuery = Review::query()
            ->published()
            ->where(function ($query) {
                $query->where('review_type', 'editorial')
                    ->orWhere(function ($community) {
                        $community->where('review_type', 'user')
                            ->whereNotNull('body')
                            ->whereRaw("TRIM(body) <> ''");
                    });
            })
            ->where(function ($query) {
                $query->whereHas('tool', fn ($tool) => $tool->where('status', 'published'))
                    ->orWhereHas('model', fn ($model) => $model->whereIn('status', ['active', 'preview']));
            });

        $comparisonRows = Comparison::query()
            ->where('status', 'published')
            ->whereNotNull('slug')
            ->get();

        $validComparisons = $comparisonRows->filter(function (Comparison $comparison) {
            try {
                return $comparison->publicItems()->count() >= 2;
            } catch (\Throwable $e) {
                return false;
            }
        });

        $benchmarkQuery = Benchmark::query()
            ->where('is_active', true)
            ->whereHas('results', fn ($query) => $query->where('verified', true)->where('status', 'verified'));

        $taxonomyCount = Category::product()->active()->where('is_indexable', true)
                ->whereHas('tools', fn ($q) => $q->where('status', 'published'))->count()
            + Subcategory::active()->where('is_indexable', true)
                ->whereHas('category', fn ($q) => $q->product()->active())
                ->whereHas('tools', fn ($q) => $q->where('status', 'published'))->count()
            + Feature::active()->where('is_indexable', true)
                ->where(function ($q) {
                    $q->whereHas('tools', fn ($tools) => $tools->where('status', 'published'))
                        ->orWhereHas('models', fn ($models) => $models->whereIn('status', ['active', 'preview']));
                })->count()
            + UseCase::active()->where('is_indexable', true)
                ->where(function ($q) {
                    $q->whereHas('tools', fn ($tools) => $tools->where('status', 'published'))
                        ->orWhereHas('models', fn ($models) => $models->whereIn('status', ['active', 'preview']));
                })->count()
            + Category::content()->active()->where('is_indexable', true)
                ->whereHas('articles', fn ($q) => $q->where('status', 'published')->where('approval_status', 'approved'))->count();

        $rows = [
            ['Companies', (clone $companyQuery)->count()],
            ['Tools', Tool::query()->where('status', 'published')->count()],
            ['Models', AiModel::query()->whereIn('status', ['active', 'preview'])->count()],
            ['News', (clone $newsQuery)->count()],
            ['Articles', Article::query()->where('status', 'published')->where('approval_status', 'approved')->count()],
            ['Reviews', (clone $reviewQuery)->count()],
            ['Pricing', Tool::query()->where('status', 'published')->whereHas('pricingPlans')->count()],
            ['Comparisons', $validComparisons->count()],
            ['Benchmarks', (clone $benchmarkQuery)->count()],
            ['Taxonomy', $taxonomyCount],
            ['Static hubs/pages', 22],
        ];

        $this->info('AI Orbit SEO indexing audit');
        $this->table(['Sitemap group', 'Eligible URLs'], $rows);
        $this->line('Estimated sitemap URL total (before cross-sitemap deduplication): '.collect($rows)->sum(fn ($row) => $row[1]));

        $thinCompanies = Company::query()->public()->count() - (clone $companyQuery)->count();
        $invalidComparisons = $comparisonRows->count() - $validComparisons->count();
        $unverifiedBenchmarks = Benchmark::query()
            ->where('is_active', true)
            ->whereDoesntHave('results', fn ($query) => $query->where('verified', true)->where('status', 'verified'))
            ->count();
        $duplicateNews = NewsItem::query()
            ->where('status', 'published')
            ->where(function ($query) {
                $query->whereNotNull('duplicate_of_id')->orWhere('duplicate_status', 'duplicate');
            })->count();
        $blankCommunityReviews = Review::query()
            ->published()
            ->where('review_type', 'user')
            ->where(function ($query) {
                $query->whereNull('body')->orWhereRaw("TRIM(body) = ''");
            })->count();

        $warnings = collect([
            ['Company profiles withheld as thin/placeholder', $thinCompanies],
            ['Published comparisons resolving fewer than 2 items', $invalidComparisons],
            ['Active benchmarks without verified public results', $unverifiedBenchmarks],
            ['Published duplicate news excluded from crawl paths', $duplicateNews],
            ['Published blank community reviews excluded', $blankCommunityReviews],
        ])->filter(fn ($row) => $row[1] > 0)->values();

        if ($warnings->isEmpty()) {
            $this->info('No sitemap/public-route mismatch warnings detected.');
        } else {
            $this->newLine();
            $this->warn('Items intentionally excluded from indexable sitemap inventory:');
            $this->table(['Check', 'Count'], $warnings->all());
        }

        $this->newLine();
        $this->info('Audit complete. This command does not modify database records.');

        return self::SUCCESS;
    }
}
