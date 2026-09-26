<?php

namespace App\Services\Seo;

use App\Models\PricingPlan;
use App\Models\Tool;
use App\Services\Tools\ToolCommercialProfileService;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class ToolSearchIntentAnswerService
{
    public function __construct(private readonly ToolCommercialProfileService $commercial) {}

    /**
     * Build short, evidence-aware answers for a small temporary GSC recovery set.
     * This does not change indexability or metadata and never invents missing facts.
     */
    public function build(Tool $tool, ?Collection $pricingPlans = null): array
    {
        $focusSlugs = collect(config('seo.impression_focus_tool_slugs', []))
            ->filter()
            ->map(fn ($slug) => trim((string) $slug))
            ->unique();

        if (! $focusSlugs->contains($tool->slug)) {
            return [];
        }

        $tool->loadMissing(['company', 'category', 'useCaseTerms', 'featureTerms']);
        $pricingPlans ??= $tool->relationLoaded('pricingPlans')
            ? $tool->pricingPlans
            : $tool->pricingPlans()->get();

        $answers = collect();

        $overview = $this->excerpt($tool->description ?: $tool->short_description, 260);
        $identityParts = collect([
            $tool->company?->name ? $tool->name.' is provided by '.$tool->company->name.'.' : null,
            $tool->category?->name ? 'AI Orbit categorizes it under '.$tool->category->name.'.' : null,
        ])->filter()->join(' ');

        if ($overview !== '' || $identityParts !== '') {
            $answers->push([
                'key' => 'what_is',
                'question' => 'What is '.$tool->name.'?',
                'answer' => trim($overview.' '.$identityParts),
            ]);
        }

        if ($pricingPlans->isNotEmpty()) {
            $labels = collect($this->commercial->expectedLabels($tool));
            $hasFree = $labels->contains('Free');
            $verifiedPlans = $pricingPlans->filter(fn (PricingPlan $plan) => $plan->last_verified_at !== null)->values();
            $evidencePlans = $verifiedPlans->isNotEmpty() ? $verifiedPlans : $pricingPlans;
            $lowestPaid = $evidencePlans
                ->filter(fn (PricingPlan $plan) => $plan->monthly_price !== null && (float) $plan->monthly_price > 0)
                ->min(fn (PricingPlan $plan) => (float) $plan->monthly_price);

            $freeAnswer = $hasFree
                ? 'Yes. AI Orbit currently lists a free option for '.$tool->name.($labels->contains('Paid') ? ' alongside paid plans.' : '.')
                : 'AI Orbit does not currently list a free plan for '.$tool->name.' in its pricing inventory.';

            if ($verifiedPlans->isNotEmpty()) {
                $freeAnswer .= ' The pricing inventory includes plan-level verification dates; provider terms can still change.';
            } else {
                $freeAnswer .= ' Pricing verification is still incomplete, so check the provider before making a purchase decision.';
            }

            $answers->push([
                'key' => 'free',
                'question' => 'Is '.$tool->name.' free?',
                'answer' => $freeAnswer,
            ]);

            $priceParts = [];
            if ($hasFree) {
                $priceParts[] = 'a free option';
            }
            if ($lowestPaid !== null) {
                $priceParts[] = 'paid plans starting at $'.number_format((float) $lowestPaid, 2).'/month';
            }
            if ($labels->contains('Usage-based')) {
                $priceParts[] = 'usage-based pricing';
            }
            if ($labels->contains('Enterprise')) {
                $priceParts[] = 'enterprise or custom pricing';
            }

            $pricingAnswer = 'AI Orbit currently lists '.number_format($pricingPlans->count()).' pricing '.Str::plural('plan', $pricingPlans->count()).' for '.$tool->name.'.';
            if ($priceParts !== []) {
                $pricingAnswer .= ' The current inventory includes '.collect($priceParts)->join(', ', ' and ').'.';
            }
            $pricingAnswer .= $verifiedPlans->isNotEmpty()
                ? ' Exact limits, billing units and verification dates are shown on the dedicated pricing page.'
                : ' Exact rates and limits should be confirmed with the provider because verified plan-level evidence is incomplete.';

            $answers->push([
                'key' => 'pricing',
                'question' => 'How much does '.$tool->name.' cost?',
                'answer' => $pricingAnswer,
            ]);
        }

        $useCases = $tool->useCaseTerms->pluck('name')->filter()->unique()->take(4)->values();
        if ($useCases->isNotEmpty()) {
            $answers->push([
                'key' => 'best_for',
                'question' => 'What is '.$tool->name.' best for?',
                'answer' => $tool->name.' is mapped on AI Orbit to use cases including '.$useCases->join(', ', ' and ').'. These are structured product-fit mappings; individually verified fit notes are shown elsewhere on the profile when available.',
            ]);
        }

        return $answers
            ->filter(fn (array $answer) => trim((string) ($answer['answer'] ?? '')) !== '')
            ->take(4)
            ->values()
            ->all();
    }

    private function excerpt(?string $value, int $limit): string
    {
        $text = trim(strip_tags(html_entity_decode((string) $value, ENT_QUOTES | ENT_HTML5, 'UTF-8')));
        $text = preg_replace('/\s+/u', ' ', $text) ?? $text;

        return $text === '' ? '' : Str::limit($text, $limit, '');
    }
}
