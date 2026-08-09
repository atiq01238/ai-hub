<?php

namespace App\Observers;

use App\Models\AppNotification;
use App\Models\PricingHistory;
use App\Models\PricingPlan;

class PricingPlanObserver
{
    public function created(PricingPlan $plan): void
    {
        PricingHistory::create([
            'tool_id'     => $plan->tool_id,
            'plan_name'   => $plan->plan_name,
            'old_price'   => null,
            'new_price'   => $plan->monthly_price,
            'change_type' => 'new_plan',
        ]);
    }

    public function updated(PricingPlan $plan): void
    {
        if (! $plan->wasChanged('monthly_price')) {
            return;
        }

        $old = $plan->getOriginal('monthly_price');
        $new = $plan->monthly_price;

        PricingHistory::create([
            'tool_id'     => $plan->tool_id,
            'plan_name'   => $plan->plan_name,
            'old_price'   => $old,
            'new_price'   => $new,
            'change_type' => $new > $old ? 'increase' : 'decrease',
        ]);

        $toolName = $plan->tool->name ?? 'A tool';
        AppNotification::broadcast(
            'tag',
            'warn',
            'Price update detected',
            "{$toolName} {$plan->plan_name} changed from \$" . number_format($old, 0) . ' to $' . number_format($new, 0)
        );
    }

    public function deleted(PricingPlan $plan): void
    {
        PricingHistory::create([
            'tool_id'     => $plan->tool_id,
            'plan_name'   => $plan->plan_name,
            'old_price'   => $plan->monthly_price,
            'new_price'   => null,
            'change_type' => 'removed_plan',
        ]);
    }
}
