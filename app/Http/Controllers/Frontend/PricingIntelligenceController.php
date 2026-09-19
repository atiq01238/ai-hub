<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\PricingHistory;
use App\Models\PricingPlan;
use App\Models\Tool;
use App\Services\Frontend\QuickFeedbackService;
use App\Services\Seo\SeoMetadataService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class PricingIntelligenceController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'type' => ['nullable', 'in:all,free,paid,api'],
            'category' => ['nullable', 'string', 'max:100'],
            'price' => ['nullable', 'in:all,under_10,10_20,20_50,50_plus,custom'],
            'freshness' => ['nullable', 'in:all,fresh,review,stale,unverified'],
            'sort' => ['nullable', 'in:updated,value,price_low,price_high,name'],
            'page' => ['nullable', 'integer', 'min:1'],
        ]);

        $query = Tool::query()
            ->where('status', 'published')
            ->whereHas('pricingPlans')
            ->with([
                'company',
                'category',
                'pricingPlans' => fn ($q) => $q
                    ->with('sources')
                    ->orderByRaw('monthly_price IS NULL')
                    ->orderBy('monthly_price'),
            ])
            ->withCount([
                'pricingPlans',
                'pricingPlans as free_plans_count' => fn ($q) => $q->where('monthly_price', 0),
                'pricingPlans as api_plans_count' => fn ($q) => $q->whereNotNull('api_price_label'),
            ]);

        if ($search = trim((string) ($filters['q'] ?? ''))) {
            $query->where(function (Builder $builder) use ($search) {
                $builder->where('name', 'like', "%{$search}%")
                    ->orWhereHas('company', fn ($company) => $company->where('name', 'like', "%{$search}%"));
            });
        }

        $type = $filters['type'] ?? 'all';
        if ($type === 'free') {
            $query->whereHas('pricingPlans', fn ($plan) => $plan->where('monthly_price', 0));
        } elseif ($type === 'paid') {
            $query->whereHas('pricingPlans', fn ($plan) => $plan->where('monthly_price', '>', 0));
        } elseif ($type === 'api') {
            $query->whereHas('pricingPlans', fn ($plan) => $plan->whereNotNull('api_price_label'));
        }

        $category = trim((string) ($filters['category'] ?? ''));
        if ($category !== '' && $category !== 'all') {
            $query->whereHas('category', fn ($categoryQuery) => $categoryQuery->where('slug', $category));
        }

        $price = $filters['price'] ?? 'all';
        $this->applyPriceFilter($query, $price);

        $freshness = $filters['freshness'] ?? 'all';
        $this->applyFreshnessFilter($query, $freshness);

        $sort = $filters['sort'] ?? 'updated';
        $this->applySort($query, $sort);

        $tools = $query
            ->paginate(24)
            ->withQueryString();

        $tools->getCollection()->transform(function (Tool $tool) {
            $paid = $tool->pricingPlans->filter(
                fn ($plan) => $plan->monthly_price !== null && (float) $plan->monthly_price > 0
            );

            $tool->setAttribute('lowest_monthly', $paid->min(fn ($plan) => (float) $plan->monthly_price));
            $tool->setAttribute('has_free', (int) $tool->free_plans_count > 0);
            $tool->setAttribute('has_api', (int) $tool->api_plans_count > 0);
            $tool->setAttribute('best_value_score', $this->valueScore($tool));

            [$latestEvidenceAt, $pricingFreshness] = $this->pricingEvidence($tool);
            $tool->setAttribute('latest_pricing_evidence_at', $latestEvidenceAt);
            $tool->setAttribute('pricing_freshness', $pricingFreshness);

            return $tool;
        });

        $categories = Category::query()
            ->select(['id', 'name', 'slug'])
            ->product()
            ->active()
            ->whereHas('tools', fn ($toolQuery) => $toolQuery
                ->where('status', 'published')
                ->whereHas('pricingPlans'))
            ->withCount(['tools as pricing_tools_count' => fn ($toolQuery) => $toolQuery
                ->where('status', 'published')
                ->whereHas('pricingPlans')])
            ->orderBy('name')
            ->get();

        $recentChanges = PricingHistory::query()
            ->whereHas('tool', fn ($tool) => $tool->where('status', 'published'))
            ->with(['tool' => fn ($tool) => $tool->select(['id', 'name', 'slug', 'status'])])
            ->latest()
            ->limit(12)
            ->get()
            ->each(function (PricingHistory $change) {
                $old = $change->old_price !== null ? (float) $change->old_price : null;
                $new = $change->new_price !== null ? (float) $change->new_price : null;
                $percent = null;

                if ($old !== null && $new !== null && $old > 0 && $old !== $new) {
                    $percent = round((($new - $old) / $old) * 100, 1);
                }

                $change->setAttribute('change_percent', $percent);
                $change->setAttribute('metric_label', match ($change->metric) {
                    'monthly_price' => 'Monthly price',
                    'yearly_price' => 'Annual price',
                    'api_price_label' => 'API pricing',
                    default => str_replace('_', ' ', ucfirst((string) ($change->metric ?: 'pricing'))),
                });
            });
        $stats = [
            'tools' => Tool::where('status', 'published')->whereHas('pricingPlans')->count(),
            'plans' => PricingPlan::count(),
            'free' => PricingPlan::where('monthly_price', 0)->count(),
            'changes' => PricingHistory::where('created_at', '>=', now()->subDays(30))->count(),
        ];

        return view('frontend.pricing.index', compact(
            'tools',
            'recentChanges',
            'stats',
            'filters',
            'type',
            'category',
            'price',
            'freshness',
            'sort',
            'categories'
        ));
    }

    public function show(Request $request, Tool $tool, QuickFeedbackService $feedback, SeoMetadataService $metadata)
    {
        abort_unless($tool->status === 'published', 404);

        $validated = $request->validate([
            'billing' => ['nullable', 'in:monthly,annual'],
        ]);
        $billingView = $validated['billing'] ?? 'monthly';

        $tool->load([
            'company',
            'category',
            'pricingPlans' => fn ($query) => $query
                ->with(['sources' => fn ($source) => $source
                    ->where('enabled', true)
                    ->orderByDesc('last_checked_at')])
                ->orderByRaw('monthly_price IS NULL')
                ->orderBy('monthly_price')
                ->orderBy('plan_name'),
        ]);

        $history = PricingHistory::where('tool_id', $tool->id)
            ->latest()
            ->limit(20)
            ->get();

        $alternatives = Tool::where('status', 'published')
            ->whereKeyNot($tool->id)
            ->whereHas('pricingPlans')
            ->with('pricingPlans')
            ->orderByDesc('rating')
            ->limit(4)
            ->get();

        $pricingFeedback = $feedback->voteSummary('pricing', $tool->id, auth()->user());

        [$latestPricingEvidenceAt, $pricingFreshness] = $this->pricingEvidence($tool);
        $pricingSources = $tool->pricingPlans
            ->flatMap(fn ($plan) => $plan->sources)
            ->filter(fn ($source) => $source->enabled && filled($source->source_url))
            ->unique('source_url')
            ->values();

        $officialSource = $pricingSources
            ->sortByDesc(function ($source) {
                $official = $source->source_type === 'official'
                    || str_contains(mb_strtolower((string) $source->source_name), 'official');
                $checked = $source->last_checked_at?->timestamp ?? 0;

                return ($official ? 10_000_000_000 : 0) + $checked;
            })
            ->first();

        $tool->pricingPlans->each(function ($plan) {
            $monthly = $plan->monthly_price !== null ? (float) $plan->monthly_price : null;
            $yearly = $plan->yearly_price !== null ? (float) $plan->yearly_price : null;
            $annualBaseline = $monthly !== null && $monthly > 0 ? $monthly * 12 : null;
            $annualSavings = null;

            if ($annualBaseline && $yearly !== null && $yearly > 0 && $yearly < $annualBaseline) {
                $annualSavings = round((1 - ($yearly / $annualBaseline)) * 100, 1);
            }

            $evidenceDates = collect([$plan->last_verified_at])
                ->merge($plan->sources->pluck('last_checked_at'))
                ->filter();
            $latestPlanEvidence = $evidenceDates
                ->sortByDesc(fn ($date) => $date->timestamp)
                ->first();

            $plan->setAttribute('annual_monthly_equivalent', $yearly !== null && $yearly > 0 ? round($yearly / 12, 2) : null);
            $plan->setAttribute('annual_savings_percent', $annualSavings);
            $plan->setAttribute('latest_evidence_at', $latestPlanEvidence);
        });

        $pricingSummary = [
            'latest_evidence_at' => $latestPricingEvidenceAt,
            'freshness' => $pricingFreshness,
            'source_count' => $pricingSources->count(),
            'official_source' => $officialSource,
            'has_annual_pricing' => $tool->pricingPlans->contains(fn ($plan) => $plan->yearly_price !== null),
            'verified_plan_count' => $tool->pricingPlans->filter(fn ($plan) => $plan->freshness !== 'unverified')->count(),
        ];

        $pricingSeo = $metadata->forKey(
            'pricing.show:'.$tool->id,
            $tool->name.' Pricing and Plans | AI Orbit',
            \Illuminate\Support\Str::limit(
                'Compare '.$tool->name.' pricing, plans, limits, API rates and published price history on AI Orbit.',
                158,
                ''
            )
        );

        return view('frontend.pricing.show', compact(
            'tool',
            'history',
            'alternatives',
            'pricingFeedback',
            'pricingSeo',
            'billingView',
            'pricingSummary'
        ));
    }

    private function applyPriceFilter(Builder $query, string $price): void
    {
        $lowestPaidSql = '(SELECT MIN(pp.monthly_price) FROM pricing_plans pp WHERE pp.tool_id = tools.id AND pp.monthly_price > 0)';

        match ($price) {
            'under_10' => $query->whereRaw("{$lowestPaidSql} < ?", [10]),
            '10_20' => $query->whereRaw("{$lowestPaidSql} >= ? AND {$lowestPaidSql} <= ?", [10, 20]),
            '20_50' => $query->whereRaw("{$lowestPaidSql} > ? AND {$lowestPaidSql} <= ?", [20, 50]),
            '50_plus' => $query->whereRaw("{$lowestPaidSql} > ?", [50]),
            'custom' => $query->whereDoesntHave('pricingPlans', fn ($plan) => $plan->whereNotNull('monthly_price')),
            default => null,
        };
    }

    private function applyFreshnessFilter(Builder $query, string $freshness): void
    {
        $freshSince = now()->subDays(14);
        $reviewSince = now()->subDays(45);

        $hasEvidenceSince = static function (Builder $planQuery, $since): void {
            $planQuery->where(function (Builder $evidence) use ($since) {
                $evidence->where('last_verified_at', '>=', $since)
                    ->orWhereHas('sources', fn ($source) => $source->where('last_checked_at', '>=', $since));
            });
        };

        $hasAnyEvidence = static function (Builder $planQuery): void {
            $planQuery->where(function (Builder $evidence) {
                $evidence->whereNotNull('last_verified_at')
                    ->orWhereHas('sources', fn ($source) => $source->whereNotNull('last_checked_at'));
            });
        };

        if ($freshness === 'fresh') {
            $query->whereHas('pricingPlans', fn ($plan) => $hasEvidenceSince($plan, $freshSince));
        } elseif ($freshness === 'review') {
            $query
                ->whereDoesntHave('pricingPlans', fn ($plan) => $hasEvidenceSince($plan, $freshSince))
                ->whereHas('pricingPlans', fn ($plan) => $hasEvidenceSince($plan, $reviewSince));
        } elseif ($freshness === 'stale') {
            $query
                ->whereDoesntHave('pricingPlans', fn ($plan) => $hasEvidenceSince($plan, $reviewSince))
                ->whereHas('pricingPlans', fn ($plan) => $hasAnyEvidence($plan));
        } elseif ($freshness === 'unverified') {
            $query->whereDoesntHave('pricingPlans', fn ($plan) => $hasAnyEvidence($plan));
        }
    }

    private function applySort(Builder $query, string $sort): void
    {
        $lowestPaidSql = '(SELECT MIN(pp.monthly_price) FROM pricing_plans pp WHERE pp.tool_id = tools.id AND pp.monthly_price > 0)';
        $hasFreeSql = 'EXISTS(SELECT 1 FROM pricing_plans ppf WHERE ppf.tool_id = tools.id AND ppf.monthly_price = 0)';
        $effectivePriceSql = "CASE WHEN {$hasFreeSql} THEN 0 ELSE COALESCE({$lowestPaidSql}, 999999) END";
        $effectiveHighPriceSql = "CASE WHEN {$hasFreeSql} THEN 0 ELSE COALESCE({$lowestPaidSql}, -1) END";
        $valueScoreSql = "(COALESCE(tools.rating,0) * 0.55) + ((COALESCE(tools.benchmark_score,0) / 20) * 0.35) + CASE WHEN {$hasFreeSql} THEN 1.2 WHEN {$lowestPaidSql} <= 20 THEN 0.7 ELSE 0.2 END";

        match ($sort) {
            'price_low' => $query->orderByRaw("{$effectivePriceSql} ASC")->orderBy('tools.name'),
            'price_high' => $query->orderByRaw("{$effectiveHighPriceSql} DESC")->orderBy('tools.name'),
            'name' => $query->orderBy('tools.name'),
            'value' => $query->orderByRaw("{$valueScoreSql} DESC")->orderBy('tools.name'),
            default => $query
                ->orderByDesc(
                    PricingPlan::select('last_verified_at')
                        ->whereColumn('pricing_plans.tool_id', 'tools.id')
                        ->whereNotNull('last_verified_at')
                        ->orderByDesc('last_verified_at')
                        ->limit(1)
                )
                ->orderByDesc('tools.updated_at')
                ->orderBy('tools.name'),
        };
    }

    private function pricingEvidence(Tool $tool): array
    {
        $timestamps = collect();

        foreach ($tool->pricingPlans as $plan) {
            if ($plan->last_verified_at) {
                $timestamps->push($plan->last_verified_at);
            }

            foreach ($plan->sources as $source) {
                if ($source->last_checked_at) {
                    $timestamps->push($source->last_checked_at);
                }
            }
        }

        $latest = $timestamps->sortByDesc(fn ($timestamp) => $timestamp->getTimestamp())->first();
        if (! $latest) {
            return [null, 'unverified'];
        }

        if ($latest->gte(now()->subDays(14))) {
            return [$latest, 'fresh'];
        }

        if ($latest->gte(now()->subDays(45))) {
            return [$latest, 'review'];
        }

        return [$latest, 'stale'];
    }

    private function valueScore(Tool $tool): float
    {
        $rating = (float)($tool->rating ?? 0);
        $benchmark = (float)($tool->benchmark_score ?? 0) / 20;
        $priceBonus = $tool->has_free ? 1.2 : (($tool->lowest_monthly ?? 100) <= 20 ? .7 : .2);
        return round(($rating * .55) + ($benchmark * .35) + $priceBonus, 2);
    }
}
