<?php

namespace App\Services\Tools;

use App\Models\PricingPlan;
use App\Models\Tool;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class ToolCommercialProfileService
{
    public function refresh(Tool $tool): array
    {
        $plans = $tool->pricingPlans()->get();
        $labels = $plans->isNotEmpty()
            ? $this->labelsFromPlans($plans)
            : $this->normalizeLegacyLabels($tool->pricing_models ?? []);

        if ($labels !== array_values((array) ($tool->pricing_models ?? []))) {
            $tool->updateQuietly(['pricing_models' => $labels]);
        }

        return $labels;
    }

    public function summaryLabel(Tool $tool, ?Collection $plans = null): string
    {
        $plans ??= $tool->relationLoaded('pricingPlans') ? $tool->pricingPlans : $tool->pricingPlans()->get();
        $labels = $plans->isNotEmpty() ? $this->labelsFromPlans($plans) : $this->normalizeLegacyLabels($tool->pricing_models ?? []);

        $hasFree = in_array('Free', $labels, true);
        $hasPaid = in_array('Paid', $labels, true);
        $hasUsage = in_array('Usage-based', $labels, true);
        $hasEnterprise = in_array('Enterprise', $labels, true) || in_array('Custom', $labels, true);

        if ($hasFree && $hasPaid) return 'Free + Paid';
        if ($hasFree && $hasUsage && ! $hasEnterprise) return 'Free + Usage-based';
        if ($hasFree && $hasEnterprise && ! $hasUsage) return 'Free + Custom';
        if ($hasFree && ($hasUsage || $hasEnterprise)) return 'Free + Paid';
        if ($hasFree) return 'Free';
        if ($hasPaid) return 'Paid';
        if ($hasUsage) return 'Usage-based';
        if ($hasEnterprise) return 'Custom';
        return $labels[0] ?? 'Pricing varies';
    }

    public function applyFilter(Builder $query, string $filter): Builder
    {
        return match ($filter) {
            'free' => $query->where(function (Builder $outer) {
                $outer->whereHas('pricingPlans', fn (Builder $plans) => $this->applyFreePlanCondition($plans))
                    ->orWhere(function (Builder $legacy) {
                        $legacy->whereDoesntHave('pricingPlans')
                            ->where(function (Builder $q) {
                                $q->whereJsonContains('pricing_models', 'Free')
                                    ->orWhereJsonContains('pricing_models', 'Free Tier')
                                    ->orWhereJsonContains('pricing_models', 'Freemium');
                            });
                    });
            }),
            'paid' => $query->where(function (Builder $outer) {
                $outer->whereHas('pricingPlans', fn (Builder $plans) => $this->applyPaidPlanCondition($plans))
                    ->orWhere(function (Builder $legacy) {
                        $legacy->whereDoesntHave('pricingPlans')
                            ->where(function (Builder $q) {
                                foreach (['Paid', 'Subscription', 'Enterprise', 'Usage-based', 'Custom'] as $label) {
                                    $q->orWhereJsonContains('pricing_models', $label);
                                }
                            });
                    });
            }),
            'usage', 'usage-based' => $query->where(function (Builder $outer) {
                $outer->whereHas('pricingPlans', fn (Builder $plans) => $plans->where('billing_type', 'usage'))
                    ->orWhere(function (Builder $legacy) {
                        $legacy->whereDoesntHave('pricingPlans')
                            ->where(function (Builder $q) {
                                $q->whereJsonContains('pricing_models', 'Usage-based')
                                    ->orWhereJsonContains('pricing_models', 'Usage');
                            });
                    });
            }),
            'enterprise', 'custom' => $query->where(function (Builder $outer) {
                $outer->whereHas('pricingPlans', function (Builder $plans) {
                    $plans->where(function (Builder $q) {
                        $q->where('billing_type', 'custom')
                            ->orWhereRaw('LOWER(plan_name) LIKE ?', ['%enterprise%'])
                            ->orWhereRaw("LOWER(COALESCE(api_price_label, '')) LIKE ?", ['%contact sales%'])
                            ->orWhereRaw("LOWER(COALESCE(api_price_label, '')) LIKE ?", ['%custom%']);
                    });
                })->orWhere(function (Builder $legacy) {
                    $legacy->whereDoesntHave('pricingPlans')
                        ->where(function (Builder $q) {
                            $q->whereJsonContains('pricing_models', 'Enterprise')
                                ->orWhereJsonContains('pricing_models', 'Custom');
                        });
                });
            }),
            default => $query,
        };
    }

    public function expectedLabels(Tool $tool): array
    {
        $plans = $tool->relationLoaded('pricingPlans') ? $tool->pricingPlans : $tool->pricingPlans()->get();
        return $plans->isNotEmpty()
            ? $this->labelsFromPlans($plans)
            : $this->normalizeLegacyLabels($tool->pricing_models ?? []);
    }

    private function labelsFromPlans(Collection $plans): array
    {
        $free = $plans->contains(fn (PricingPlan $plan) => $this->isFreePlan($plan));
        $numericPaid = $plans->contains(fn (PricingPlan $plan) => ($plan->monthly_price !== null && (float) $plan->monthly_price > 0)
            || ($plan->yearly_price !== null && (float) $plan->yearly_price > 0));
        $usage = $plans->contains(fn (PricingPlan $plan) => mb_strtolower((string) $plan->billing_type) === 'usage');
        $custom = $plans->contains(fn (PricingPlan $plan) => mb_strtolower((string) $plan->billing_type) === 'custom'
            || str_contains(mb_strtolower((string) $plan->api_price_label), 'contact sales')
            || str_contains(mb_strtolower((string) $plan->api_price_label), 'custom'));
        $subscription = $plans->contains(fn (PricingPlan $plan) => in_array(mb_strtolower((string) $plan->billing_type), ['subscription', 'per_seat', 'one_time'], true) && ! $this->isFreePlan($plan));
        $enterprise = $plans->contains(fn (PricingPlan $plan) => str_contains(mb_strtolower((string) $plan->plan_name), 'enterprise'));

        $labels = [];
        if ($free) $labels[] = 'Free';
        if ($numericPaid || $subscription) $labels[] = 'Paid';
        if ($usage) $labels[] = 'Usage-based';
        if ($enterprise || $custom) $labels[] = 'Enterprise';

        return $labels;
    }

    private function normalizeLegacyLabels(array $labels): array
    {
        $normalized = [];
        foreach ($labels as $label) {
            $key = mb_strtolower(trim((string) $label));
            if ($key === '') continue;

            if (in_array($key, ['free', 'free tier'], true)) $normalized[] = 'Free';
            elseif ($key === 'freemium') { $normalized[] = 'Free'; $normalized[] = 'Paid'; }
            elseif (in_array($key, ['paid', 'subscription'], true)) $normalized[] = 'Paid';
            elseif (in_array($key, ['usage', 'usage-based', 'pay as you go', 'pay-as-you-go'], true)) $normalized[] = 'Usage-based';
            elseif (in_array($key, ['enterprise', 'custom'], true)) $normalized[] = 'Enterprise';
        }

        return array_values(array_unique($normalized));
    }

    private function isFreePlan(PricingPlan $plan): bool
    {
        $billingType = mb_strtolower((string) $plan->billing_type);
        if (in_array($billingType, ['usage', 'custom'], true)) return false;
        if (str_contains(mb_strtolower((string) $plan->plan_name), 'free')) return true;

        return $billingType === 'subscription'
            && $plan->monthly_price !== null
            && (float) $plan->monthly_price === 0.0
            && ($plan->yearly_price === null || (float) $plan->yearly_price === 0.0)
            && trim((string) $plan->api_price_label) === '';
    }

    private function applyFreePlanCondition(Builder $query): Builder
    {
        return $query->where(function (Builder $q) {
            $q->whereRaw('LOWER(plan_name) LIKE ?', ['%free%'])
                ->orWhere(function (Builder $zero) {
                    $zero->where('billing_type', 'subscription')
                        ->where('monthly_price', 0)
                        ->where(function (Builder $yearly) {
                            $yearly->whereNull('yearly_price')->orWhere('yearly_price', 0);
                        })
                        ->where(function (Builder $api) {
                            $api->whereNull('api_price_label')->orWhere('api_price_label', '');
                        });
                });
        })->whereNotIn('billing_type', ['usage', 'custom']);
    }

    private function applyPaidPlanCondition(Builder $query): Builder
    {
        return $query->where(function (Builder $q) {
            $q->where('monthly_price', '>', 0)
                ->orWhere('yearly_price', '>', 0)
                ->orWhereIn('billing_type', ['usage', 'custom'])
                ->orWhereIn('billing_type', ['per_seat', 'one_time'])
                ->orWhere(function (Builder $subscription) {
                    $subscription->where('billing_type', 'subscription')
                        ->where(function (Builder $notFree) {
                            $notFree->whereNull('monthly_price')->orWhere('monthly_price', '>', 0);
                        });
                });
        });
    }
}
