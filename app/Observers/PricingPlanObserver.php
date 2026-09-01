<?php

namespace App\Observers;

use App\Models\AppNotification;
use App\Models\NotificationRule;
use App\Models\PricingHistory;
use App\Models\PricingPlan;
use App\Services\Tools\ToolCommercialProfileService;

class PricingPlanObserver
{
    public function __construct(private readonly ToolCommercialProfileService $commercialProfile) {}

    public function created(PricingPlan $plan): void
    {
        PricingHistory::create([
            'tool_id' => $plan->tool_id,
            'plan_name' => $plan->plan_name,
            'metric' => 'monthly_price',
            'old_value' => null,
            'new_value' => $plan->monthly_price,
            'old_price' => null,
            'new_price' => $plan->monthly_price,
            'change_type' => 'new_plan',
        ]);

        $this->refreshCommercialProfile($plan);
    }

    public function updated(PricingPlan $plan): void
    {
        foreach (['monthly_price', 'yearly_price', 'api_price_label', 'billing_type'] as $metric) {
            if (! $plan->wasChanged($metric)) {
                continue;
            }

            $old = $plan->getOriginal($metric);
            $new = $plan->{$metric};
            if ($metric === 'billing_type') {
                continue;
            }

            $numeric = in_array($metric, ['monthly_price', 'yearly_price'], true);

            PricingHistory::create([
                'tool_id' => $plan->tool_id,
                'plan_name' => $plan->plan_name,
                'metric' => $metric,
                'old_value' => $old,
                'new_value' => $new,
                'old_price' => $numeric && $old !== null ? $old : null,
                'new_price' => $numeric && $new !== null ? $new : null,
                'change_type' => $this->changeType($old, $new, $numeric),
            ]);

            $this->notify($plan, $metric, $old, $new);
        }

        if ($plan->wasChanged(['monthly_price', 'yearly_price', 'api_price_label', 'billing_type', 'plan_name'])) {
            $this->refreshCommercialProfile($plan);
        }
    }

    public function deleted(PricingPlan $plan): void
    {
        PricingHistory::create([
            'tool_id' => $plan->tool_id,
            'plan_name' => $plan->plan_name,
            'metric' => 'monthly_price',
            'old_value' => $plan->monthly_price,
            'new_value' => null,
            'old_price' => $plan->monthly_price,
            'new_price' => null,
            'change_type' => 'removed_plan',
        ]);

        $tool = $plan->tool;
        if ($tool) $this->commercialProfile->refresh($tool);
    }

    private function refreshCommercialProfile(PricingPlan $plan): void
    {
        $tool = $plan->tool;
        if ($tool) $this->commercialProfile->refresh($tool);
    }

    private function changeType(mixed $old, mixed $new, bool $numeric): string
    {
        if ($old === null && $new !== null) {
            return 'new_plan';
        }

        if ($old !== null && ($new === null || $new === '')) {
            return 'removed_plan';
        }

        if ($numeric) {
            return (float) $new >= (float) $old ? 'increase' : 'decrease';
        }

        return 'increase';
    }

    private function notify(PricingPlan $plan, string $metric, mixed $old, mixed $new): void
    {
        if (! NotificationRule::isEnabled('price_change')) {
            return;
        }

        $toolName = $plan->tool->name ?? 'A tool';
        $label = str_replace('_', ' ', $metric);

        AppNotification::broadcast(
            'tag',
            'warn',
            'Price updated',
            "{$toolName} {$plan->plan_name} {$label} changed from " . ($old ?? '—') . ' to ' . ($new ?? '—')
        );
    }
}
