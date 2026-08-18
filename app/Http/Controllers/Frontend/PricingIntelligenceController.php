<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\PricingHistory;
use App\Models\PricingPlan;
use App\Models\Tool;
use Illuminate\Http\Request;

class PricingIntelligenceController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate([
            'q' => ['nullable','string','max:100'],
            'type' => ['nullable','in:all,free,paid,api'],
            'sort' => ['nullable','in:value,price_low,price_high,name,updated'],
        ]);

        $query = Tool::query()->where('status', 'published')
            ->with(['company','pricingPlans' => fn ($q) => $q->orderByRaw('monthly_price IS NULL')->orderBy('monthly_price')])
            ->whereHas('pricingPlans');

        if ($q = trim((string)($filters['q'] ?? ''))) {
            $query->where(fn ($x) => $x->where('name','like',"%{$q}%")
                ->orWhereHas('company', fn ($c) => $c->where('name','like',"%{$q}%")));
        }
        $type = $filters['type'] ?? 'all';
        if ($type === 'free') $query->whereHas('pricingPlans', fn ($p) => $p->where('monthly_price', 0));
        if ($type === 'paid') $query->whereHas('pricingPlans', fn ($p) => $p->where('monthly_price', '>', 0));
        if ($type === 'api') $query->whereHas('pricingPlans', fn ($p) => $p->whereNotNull('api_price_label'));

        $tools = $query->get()->map(function ($tool) {
            $paid = $tool->pricingPlans->filter(fn ($p) => $p->monthly_price !== null && (float)$p->monthly_price > 0);
            $tool->setAttribute('lowest_monthly', $paid->min(fn ($p) => (float)$p->monthly_price));
            $tool->setAttribute('has_free', $tool->pricingPlans->contains(fn ($p) => $p->monthly_price !== null && (float)$p->monthly_price === 0.0));
            $tool->setAttribute('has_api', $tool->pricingPlans->contains(fn ($p) => filled($p->api_price_label)));
            $tool->setAttribute('best_value_score', $this->valueScore($tool));
            return $tool;
        });

        $sort = $filters['sort'] ?? 'value';
        $tools = (match ($sort) {
            'price_low' => $tools->sortBy(fn ($t) => $t->lowest_monthly ?? PHP_FLOAT_MAX),
            'price_high' => $tools->sortByDesc(fn ($t) => $t->lowest_monthly ?? -1),
            'name' => $tools->sortBy('name', SORT_NATURAL|SORT_FLAG_CASE),
            'updated' => $tools->sortByDesc('updated_at'),
            default => $tools->sortByDesc('best_value_score'),
        })->values();

        $recentChanges = PricingHistory::with('tool')->latest()->limit(8)->get();
        $stats = [
            'tools' => Tool::where('status','published')->whereHas('pricingPlans')->count(),
            'plans' => PricingPlan::count(),
            'free' => PricingPlan::where('monthly_price',0)->count(),
            'changes' => PricingHistory::where('created_at','>=',now()->subDays(30))->count(),
        ];

        return view('frontend.pricing.index', compact('tools','recentChanges','stats','filters','type','sort'));
    }

    public function show(Tool $tool)
    {
        abort_unless($tool->status === 'published', 404);
        $tool->load(['company','pricingPlans.sources']);
        $history = PricingHistory::where('tool_id',$tool->id)->latest()->limit(20)->get();
        $alternatives = Tool::where('status','published')->whereKeyNot($tool->id)
            ->whereHas('pricingPlans')->with('pricingPlans')->orderByDesc('rating')->limit(4)->get();
        return view('frontend.pricing.show', compact('tool','history','alternatives'));
    }

    private function valueScore(Tool $tool): float
    {
        $rating = (float)($tool->rating ?? 0);
        $benchmark = (float)($tool->benchmark_score ?? 0) / 20;
        $priceBonus = $tool->has_free ? 1.2 : (($tool->lowest_monthly ?? 100) <= 20 ? .7 : .2);
        return round(($rating * .55) + ($benchmark * .35) + $priceBonus, 2);
    }
}
