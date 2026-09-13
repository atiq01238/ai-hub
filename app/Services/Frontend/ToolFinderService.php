<?php

namespace App\Services\Frontend;

use App\Models\Tool;
use App\Services\Tools\ToolCommercialProfileService;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class ToolFinderService
{
    public function __construct(private readonly ToolCommercialProfileService $commercialProfile)
    {
    }

    public function find(array $criteria, int $limit = 10): array
    {
        $criteria = $this->normalizeCriteria($criteria);
        $intent = $this->buildIntentProfile($criteria['task'], $criteria['shortcut']);

        $tools = Tool::query()
            ->with([
                'company',
                'category',
                'subcategoryTerm',
                'featureTerms',
                'useCaseTerms',
                'tagTerms',
                'platformTerms',
                'pricingPlans',
                'technicalProfile',
            ])
            ->withCount('verifiedSources')
            ->where('status', 'published')
            ->where(function ($query) {
                $query->whereNull('product_status')
                    ->orWhereNotIn('product_status', ['discontinued', 'sunset']);
            })
            ->get();

        $minimumTaskScore = (int) config('tool_finder.matching.minimum_task_score', 24);
        $maxMatchPercent = (int) config('tool_finder.matching.max_match_percent', 98);
        $evidenceTiebreakMax = (int) config('tool_finder.matching.evidence_tiebreak_max', 40);

        $ranked = $tools->map(function (Tool $tool) use ($criteria, $intent, $minimumTaskScore, $maxMatchPercent, $evidenceTiebreakMax) {
            $task = $this->taskFit($tool, $intent);
            if ($task['score'] < $minimumTaskScore) {
                return null;
            }

            $pricing = $this->pricingProfile($tool);
            $budget = $this->budgetFit($criteria['budget'], $pricing);
            if (! $budget['eligible']) {
                return null;
            }

            $filter = $this->advancedFilterFit($tool, $criteria['filters'], $pricing);
            if (! $filter['eligible']) {
                return null;
            }

            $experience = $this->experienceFit($tool, $criteria['experience']);
            $priority = $this->priorityFit($tool, $criteria['priority'], $pricing, $budget['score']);
            $implicit = $this->implicitPreferenceFit($tool, $intent['preferences'], $pricing);
            $evidence = $this->evidenceProfile($tool, $pricing);

            $components = [
                ['label' => 'Task fit', 'score' => $task['score'], 'weight' => 68],
            ];

            if ($criteria['budget'] !== 'any') {
                $components[] = ['label' => 'Budget fit', 'score' => $budget['score'], 'weight' => 12];
            }

            if ($criteria['experience'] !== 'any') {
                $components[] = ['label' => 'Experience fit', 'score' => $experience['score'], 'weight' => 8];
            }

            if ($criteria['priority'] !== 'any') {
                $components[] = ['label' => 'Priority fit', 'score' => $priority['score'], 'weight' => 12];
            }

            if ($criteria['filters'] !== []) {
                $components[] = ['label' => 'Extra filters', 'score' => 100, 'weight' => min(18, count($criteria['filters']) * 4)];
            }

            if ($intent['preferences'] !== []) {
                $components[] = ['label' => 'Task preferences', 'score' => $implicit['score'], 'weight' => min(10, 4 + (count($intent['preferences']) * 2))];
            }

            $weightTotal = collect($components)->sum('weight');
            $weightedScore = collect($components)->sum(fn (array $component) => $component['score'] * $component['weight']) / max(1, $weightTotal);

            // Broad shortcut-only requests should not look artificially certain.
            $specificityCap = $this->specificityCap($intent);
            $match = (int) round(min($maxMatchPercent, $specificityCap, max(1, $weightedScore)));

            $reasons = collect([
                ...$task['reasons'],
                $budget['reason'],
                $experience['reason'],
                $priority['reason'],
                ...$implicit['reasons'],
                ...$filter['reasons'],
            ])->filter()->unique()->take(4)->values()->all();

            $rating = (float) ($tool->rating ?? 0);
            $evidenceBoost = (int) round(($evidence['score'] / 100) * $evidenceTiebreakMax);
            $rankScore = ($match * 1000)
                + ($task['score'] * 12)
                + ($rating * 5)
                + $evidenceBoost;

            return [
                'tool' => $tool,
                'match' => $match,
                'match_band' => $this->matchBand($match),
                'rank_score' => $rankScore,
                'reasons' => $reasons,
                'breakdown' => collect($components)->map(fn (array $component) => [
                    'label' => $component['label'],
                    'score' => (int) round($component['score']),
                ])->values()->all(),
                'pricing_label' => $pricing['display'],
                'pricing_verified_at' => $pricing['verified_at'],
                'has_free' => $pricing['has_free'],
                'budget_score' => $budget['score'],
                'evidence_score' => $evidence['score'],
                'evidence_label' => $evidence['label'],
                'verified_sources' => (int) ($tool->verified_sources_count ?? 0),
            ];
        })->filter()->sortByDesc('rank_score')->take($limit)->values();

        $ranked = $this->applyResultLabels($ranked, $criteria);

        return [
            'results' => $ranked,
            'criteria' => $criteria,
            'intent' => [
                'categories' => array_values(array_keys($intent['categories'])),
                'features' => array_values(array_keys($intent['features'])),
                'use_cases' => array_values(array_keys($intent['use_cases'])),
                'preferences' => array_values(array_keys($intent['preferences'])),
            ],
            'candidate_count' => $tools->count(),
            'adjustment_tips' => $ranked->isEmpty() ? $this->adjustmentTips($criteria) : [],
        ];
    }

    private function normalizeCriteria(array $criteria): array
    {
        return [
            'task' => trim((string) ($criteria['task'] ?? '')),
            'shortcut' => (string) ($criteria['shortcut'] ?? ''),
            'budget' => (string) ($criteria['budget'] ?? 'any'),
            'experience' => (string) ($criteria['experience'] ?? 'any'),
            'priority' => (string) ($criteria['priority'] ?? 'any'),
            'filters' => array_values(array_unique(array_filter((array) ($criteria['filters'] ?? [])))),
        ];
    }

    private function buildIntentProfile(string $task, string $shortcut): array
    {
        $normalized = $this->normalizeText($task);
        $tokens = $this->tokens($normalized);
        $categories = [];
        $features = [];
        $useCases = [];
        $preferences = [];

        $shortcutConfig = config('tool_finder.shortcuts.'.$shortcut);
        $shortcutCategory = is_array($shortcutConfig) ? ($shortcutConfig['category'] ?? null) : null;

        foreach ((array) config('tool_finder.category_signals', []) as $category => $signals) {
            $hits = $this->signalHits($normalized, (array) $signals);
            if ($hits > 0) {
                $categories[(string) $category] = max($categories[(string) $category] ?? 0, min(3, $hits));
            }
        }

        // Natural-language intent wins when it clearly conflicts with an older
        // shortcut selection. The shortcut remains useful for empty or broad text.
        $effectiveShortcutCategory = $shortcutCategory;
        if ($shortcutCategory) {
            if ($categories === [] || array_key_exists((string) $shortcutCategory, $categories)) {
                $categories[(string) $shortcutCategory] = max($categories[(string) $shortcutCategory] ?? 0, 2);
            } elseif ($normalized !== '') {
                $effectiveShortcutCategory = null;
            }
        }

        foreach ((array) config('tool_finder.feature_signals', []) as $feature => $signals) {
            $hits = $this->signalHits($normalized, (array) $signals);
            if ($hits > 0) {
                $features[(string) $feature] = min(3, $hits);
            }
        }

        foreach ((array) config('tool_finder.use_case_signals', []) as $useCase => $signals) {
            $hits = $this->signalHits($normalized, (array) $signals);
            if ($hits > 0) {
                $useCases[(string) $useCase] = min(3, $hits);
            }
        }

        foreach ((array) config('tool_finder.preference_signals', []) as $preference => $signals) {
            $hits = $this->signalHits($normalized, (array) $signals);
            if ($hits > 0) {
                $preferences[(string) $preference] = min(3, $hits);
            }
        }

        return [
            'text' => $normalized,
            'tokens' => $tokens,
            'shortcut' => $shortcut,
            'shortcut_category' => $effectiveShortcutCategory,
            'categories' => $categories,
            'features' => $features,
            'use_cases' => $useCases,
            'preferences' => $preferences,
        ];
    }

    private function taskFit(Tool $tool, array $intent): array
    {
        $score = 0.0;
        $reasons = [];
        $queryTokens = $intent['tokens'];
        $categorySlug = (string) ($tool->category?->slug ?? '');

        if ($intent['shortcut_category'] && $categorySlug === $intent['shortcut_category']) {
            $score += 48;
            $reasons[] = 'Strong fit for '.($tool->category?->name ?? 'your selected task');
        } elseif (array_key_exists($categorySlug, $intent['categories'])) {
            $score += 30 + (min(2, (int) $intent['categories'][$categorySlug]) * 5);
            $reasons[] = 'Matches your '.strtolower((string) ($tool->category?->name ?? 'task')).' need';
        }

        $categoryText = $this->normalizeText(implode(' ', array_filter([
            $tool->category?->name,
            $tool->category?->short_description,
            $tool->category?->description,
        ])));
        $score += min(8, $this->overlapCount($queryTokens, $categoryText) * 3);

        $subcategoryText = $this->normalizeText(implode(' ', array_filter([
            $tool->subcategoryTerm?->name,
            $tool->subcategoryTerm?->short_description,
            $tool->subcategoryTerm?->description,
            $tool->subcategory,
        ])));
        $subcategoryOverlap = $this->overlapCount($queryTokens, $subcategoryText);
        $score += min(14, $subcategoryOverlap * 5);
        if ($subcategoryOverlap > 0 && $tool->subcategoryTerm?->name) {
            $reasons[] = 'Relevant specialty: '.$tool->subcategoryTerm->name;
        }

        $featureScore = 0;
        $matchedFeatures = [];
        foreach ($tool->featureTerms as $feature) {
            $featureName = (string) $feature->name;
            if (array_key_exists($featureName, $intent['features'])) {
                $verified = ($feature->pivot?->verification_status ?? null) === 'verified';
                $featureScore += 24 + ($verified ? 5 : 0) + (min(2, (int) $intent['features'][$featureName]) * 3);
                $matchedFeatures[] = $featureName;
            }

            $featureText = $this->normalizeText(implode(' ', array_filter([
                $feature->name,
                $feature->short_description,
                $feature->description,
                $feature->pivot?->description,
            ])));
            $featureScore += min(6, $this->overlapCount($queryTokens, $featureText) * 3);
        }
        $score += min(38, $featureScore);
        if ($matchedFeatures !== []) {
            $reasons[] = 'Includes '.implode(' + ', array_slice(array_values(array_unique($matchedFeatures)), 0, 2));
        }

        $useCaseScore = 0;
        $matchedUseCases = [];
        foreach ($tool->useCaseTerms as $useCase) {
            $useCaseName = (string) $useCase->name;
            if (array_key_exists($useCaseName, $intent['use_cases'])) {
                $verified = ($useCase->pivot?->verification_status ?? null) === 'verified';
                $useCaseScore += 20 + ($verified ? 5 : 0) + (min(2, (int) $intent['use_cases'][$useCaseName]) * 3);
                $matchedUseCases[] = $useCaseName;
            }

            $useCaseText = $this->normalizeText(implode(' ', array_filter([
                $useCase->name,
                $useCase->short_description,
                $useCase->description,
                $useCase->pivot?->fit_note,
            ])));
            $overlap = $this->overlapCount($queryTokens, $useCaseText);
            if ($overlap > 0) {
                $useCaseScore += min(7, $overlap * 3);
                $matchedUseCases[] = $useCaseName;
            }
        }
        $score += min(30, $useCaseScore);
        if ($matchedUseCases !== []) {
            $reasons[] = 'Use-case fit: '.implode(' + ', array_slice(array_values(array_unique($matchedUseCases)), 0, 2));
        }

        $tagText = $this->normalizeText($tool->tagTerms->pluck('name')->implode(' '));
        $score += min(6, $this->overlapCount($queryTokens, $tagText) * 2);

        $capabilities = is_array($tool->capabilities) ? implode(' ', $tool->capabilities) : (string) $tool->capabilities;
        $toolText = $this->normalizeText(implode(' ', array_filter([
            $tool->name,
            $tool->short_description,
            $capabilities,
        ])));
        $score += min(10, $this->overlapCount($queryTokens, $toolText) * 2.5);

        return [
            'score' => (int) round(min(100, $score)),
            'reasons' => collect($reasons)->filter()->unique()->take(3)->values()->all(),
        ];
    }

    private function pricingProfile(Tool $tool): array
    {
        $labels = $this->commercialProfile->expectedLabels($tool);
        $plans = $tool->pricingPlans;
        $hasFree = in_array('Free', $labels, true);
        $hasUsage = in_array('Usage-based', $labels, true);
        $hasCustom = in_array('Enterprise', $labels, true) || in_array('Custom', $labels, true);

        $effectiveMonthly = $plans->map(function ($plan) {
            if ($plan->monthly_price !== null && (float) $plan->monthly_price > 0) {
                return (float) $plan->monthly_price;
            }
            if ($plan->yearly_price !== null && (float) $plan->yearly_price > 0) {
                return (float) $plan->yearly_price / 12;
            }
            return null;
        })->filter(fn ($price) => $price !== null && $price > 0);

        $minMonthly = $effectiveMonthly->isNotEmpty() ? (float) $effectiveMonthly->min() : null;
        $verifiedAt = $plans->pluck('last_verified_at')->filter()->sortDesc()->first();

        if ($hasFree && $minMonthly !== null) {
            $display = 'Free + from $'.$this->formatPrice($minMonthly).'/mo';
        } elseif ($hasFree) {
            $display = 'Free plan available';
        } elseif ($minMonthly !== null) {
            $display = 'From $'.$this->formatPrice($minMonthly).'/mo';
        } elseif ($hasUsage) {
            $display = 'Usage-based pricing';
        } elseif ($hasCustom) {
            $display = 'Custom pricing';
        } else {
            $display = $this->commercialProfile->summaryLabel($tool, $plans);
        }

        return [
            'labels' => $labels,
            'has_free' => $hasFree,
            'has_usage' => $hasUsage,
            'has_custom' => $hasCustom,
            'min_monthly' => $minMonthly,
            'verified_at' => $verifiedAt,
            'display' => $display,
        ];
    }

    private function budgetFit(string $budget, array $pricing): array
    {
        if ($budget === 'any') {
            return ['eligible' => true, 'score' => 100, 'reason' => null];
        }

        if ($budget === 'free') {
            return $pricing['has_free']
                ? ['eligible' => true, 'score' => 100, 'reason' => 'Free plan matches your budget']
                : ['eligible' => false, 'score' => 0, 'reason' => null];
        }

        if ($budget === '50plus') {
            $known = $pricing['has_free'] || $pricing['min_monthly'] !== null || $pricing['has_usage'] || $pricing['has_custom'];
            return [
                'eligible' => true,
                'score' => $known ? 100 : 65,
                'reason' => $known ? 'Fits a flexible $50+ budget' : 'Pricing varies by plan',
            ];
        }

        $cap = (float) $budget;
        if ($pricing['has_free']) {
            return ['eligible' => true, 'score' => 100, 'reason' => 'Free option is within your budget'];
        }

        if ($pricing['min_monthly'] !== null) {
            if ($pricing['min_monthly'] <= $cap) {
                return ['eligible' => true, 'score' => 100, 'reason' => 'Starts within your $'.number_format($cap, 0).' budget'];
            }

            return ['eligible' => false, 'score' => 0, 'reason' => null];
        }

        if ($pricing['has_usage']) {
            return ['eligible' => true, 'score' => 65, 'reason' => 'Usage-based pricing may fit your budget'];
        }

        return ['eligible' => false, 'score' => 0, 'reason' => null];
    }

    private function experienceFit(Tool $tool, string $experience): array
    {
        if ($experience === 'any') {
            return ['score' => 100, 'reason' => null];
        }

        $tags = $this->tagNames($tool);

        if ($experience === 'beginner') {
            if ($this->hasAny($tags, ['Beginner Friendly', 'No-Code'])) {
                return ['score' => 100, 'reason' => 'Designed for a beginner-friendly workflow'];
            }
            if ($this->hasAny($tags, ['Low-Code'])) {
                return ['score' => 82, 'reason' => 'Low-code workflow suits your experience level'];
            }
            if ($this->hasAny($tags, ['Developer Focused', 'API First'])) {
                return ['score' => 38, 'reason' => null];
            }
            return ['score' => 62, 'reason' => null];
        }

        if ($experience === 'advanced') {
            if ($this->hasAny($tags, ['Developer Focused', 'API First', 'Professional', 'Open Source', 'Self Hosted'])) {
                return ['score' => 96, 'reason' => 'Supports advanced or professional workflows'];
            }
            return ['score' => 62, 'reason' => null];
        }

        if ($this->hasAny($tags, ['Low-Code', 'Professional', 'Collaboration'])) {
            return ['score' => 90, 'reason' => 'Good fit for an intermediate workflow'];
        }

        return ['score' => 72, 'reason' => null];
    }

    private function priorityFit(Tool $tool, string $priority, array $pricing, float $budgetScore): array
    {
        if ($priority === 'any') {
            return ['score' => 100, 'reason' => null];
        }

        $tags = $this->tagNames($tool);
        $technical = $tool->technicalProfile;

        if ($priority === 'quality') {
            $rating = (float) ($tool->rating ?? 0);
            if ($rating > 0) {
                $score = min(100, 55 + (($rating / 5) * 45));
                return ['score' => $score, 'reason' => $rating >= 4.5 ? 'Highly rated on AI Orbit' : null];
            }

            $verifiedTaxonomy = $tool->featureTerms->filter(fn ($feature) => ($feature->pivot?->verification_status ?? null) === 'verified')->count()
                + $tool->useCaseTerms->filter(fn ($useCase) => ($useCase->pivot?->verification_status ?? null) === 'verified')->count();
            $evidenceScore = min(32, (((int) ($tool->verified_sources_count ?? 0)) * 6) + min(14, $verifiedTaxonomy * 2));
            return ['score' => min(100, 60 + $evidenceScore), 'reason' => $evidenceScore >= 20 ? 'Backed by stronger verified profile evidence' : null];
        }

        if ($priority === 'value') {
            $ratingScore = (float) ($tool->rating ?? 0) > 0 ? (((float) $tool->rating / 5) * 100) : 65;
            $score = ($budgetScore * 0.7) + ($ratingScore * 0.3) + ($pricing['has_free'] ? 8 : 0);
            return ['score' => min(100, $score), 'reason' => $pricing['has_free'] ? 'Free option improves value' : null];
        }

        if ($priority === 'ease') {
            if ($this->hasAny($tags, ['Beginner Friendly', 'No-Code'])) {
                return ['score' => 100, 'reason' => 'Prioritizes an easy, beginner-friendly workflow'];
            }
            if ($this->hasAny($tags, ['Low-Code'])) {
                return ['score' => 84, 'reason' => 'Low-code workflow keeps setup simpler'];
            }
            if ($this->hasAny($tags, ['Developer Focused', 'API First'])) {
                return ['score' => 42, 'reason' => null];
            }
            return ['score' => 66, 'reason' => null];
        }

        if ($priority === 'privacy') {
            $score = 35;
            if ($this->hasAny($tags, ['Privacy Focused', 'Local AI', 'Self Hosted'])) $score += 40;
            if (in_array($technical?->data_training_policy, ['not_used', 'opt_out'], true)) $score += 25;
            if (in_array($technical?->self_hosting_status, ['supported', 'enterprise_only'], true)) $score += 15;
            if (in_array($technical?->open_source_status, ['open_source', 'source_available'], true)) $score += 10;
            return [
                'score' => min(100, $score),
                'reason' => $score >= 75 ? 'Strong privacy or deployment controls are documented' : null,
            ];
        }

        if ($priority === 'api') {
            if ($technical && in_array($technical->api_status, ['available', 'limited'], true)) {
                return ['score' => $technical->api_status === 'available' ? 100 : 82, 'reason' => 'API access is documented'];
            }
            if ($tool->featureTerms->contains(fn ($feature) => $feature->name === 'API Access') || $this->hasAny($tags, ['API First'])) {
                return ['score' => 82, 'reason' => 'API capability is listed in the tool profile'];
            }
            return ['score' => 35, 'reason' => null];
        }

        return ['score' => 70, 'reason' => null];
    }

    private function advancedFilterFit(Tool $tool, array $filters, array $pricing): array
    {
        if ($filters === []) {
            return ['eligible' => true, 'reasons' => []];
        }

        $tags = $this->tagNames($tool);
        $platforms = $tool->platformTerms->pluck('slug')->filter()->all();
        $technical = $tool->technicalProfile;
        $reasons = [];

        foreach ($filters as $filter) {
            $matched = false;
            $reason = null;

            switch ($filter) {
                case 'free_plan':
                    $matched = $pricing['has_free'];
                    $reason = 'Free plan available';
                    break;
                case 'api':
                    $matched = ($technical && in_array($technical->api_status, ['available', 'limited'], true))
                        || $tool->featureTerms->contains(fn ($feature) => $feature->name === 'API Access')
                        || $this->hasAny($tags, ['API First']);
                    $reason = 'API access available';
                    break;
                case 'open_source':
                    $matched = ($technical && in_array($technical->open_source_status, ['open_source', 'source_available', 'mixed'], true))
                        || $this->hasAny($tags, ['Open Source']);
                    $reason = 'Open-source option matches your filter';
                    break;
                case 'mobile':
                    $matched = count(array_intersect($platforms, ['ios', 'android', 'mobile-app'])) > 0
                        || $this->hasAny($tags, ['Mobile App']);
                    $reason = 'Mobile access matches your filter';
                    break;
                case 'browser_extension':
                    $matched = count(array_intersect($platforms, ['browser-extension', 'chrome-extension', 'firefox-extension'])) > 0
                        || $this->hasAny($tags, ['Browser Extension']);
                    $reason = 'Browser extension available';
                    break;
                case 'team_collaboration':
                    $matched = $this->hasAny($tags, ['Team Workspace', 'Collaboration']);
                    $reason = 'Team collaboration features match your filter';
                    break;
                case 'commercial_use':
                    $matched = $technical && in_array($technical->commercial_use_status, ['allowed', 'plan_dependent'], true);
                    $reason = 'Commercial-use terms are documented';
                    break;
                case 'privacy_focused':
                    $matched = $this->hasAny($tags, ['Privacy Focused', 'Local AI', 'Self Hosted'])
                        || ($technical && in_array($technical->data_training_policy, ['not_used', 'opt_out'], true));
                    $reason = 'Privacy-focused controls match your filter';
                    break;
            }

            if (! $matched) {
                return ['eligible' => false, 'reasons' => []];
            }

            if ($reason) {
                $reasons[] = $reason;
            }
        }

        return ['eligible' => true, 'reasons' => array_values(array_unique($reasons))];
    }

    private function implicitPreferenceFit(Tool $tool, array $preferences, array $pricing): array
    {
        if ($preferences === []) {
            return ['score' => 100, 'matched' => 0, 'reasons' => []];
        }

        $tags = $this->tagNames($tool);
        $platforms = $tool->platformTerms->pluck('slug')->filter()->all();
        $technical = $tool->technicalProfile;
        $matched = 0;
        $reasons = [];

        foreach (array_keys($preferences) as $preference) {
            $ok = match ($preference) {
                'free' => $pricing['has_free'],
                'api' => ($technical && in_array($technical->api_status, ['available', 'limited'], true))
                    || $tool->featureTerms->contains(fn ($feature) => $feature->name === 'API Access')
                    || $this->hasAny($tags, ['API First']),
                'open_source' => ($technical && in_array($technical->open_source_status, ['open_source', 'source_available', 'mixed'], true))
                    || $this->hasAny($tags, ['Open Source', 'Self Hosted', 'Local AI']),
                'privacy' => $this->hasAny($tags, ['Privacy Focused', 'Local AI', 'Self Hosted'])
                    || ($technical && in_array($technical->data_training_policy, ['not_used', 'opt_out'], true)),
                'beginner' => $this->hasAny($tags, ['Beginner Friendly', 'No-Code', 'Low-Code']),
                'team' => $this->hasAny($tags, ['Team Workspace', 'Collaboration'])
                    || $tool->featureTerms->contains(fn ($feature) => $feature->name === 'Team Collaboration'),
                'mobile' => count(array_intersect($platforms, ['ios', 'android', 'mobile-app'])) > 0
                    || $this->hasAny($tags, ['Mobile App']),
                default => false,
            };

            if ($ok) {
                $matched++;
                $reasons[] = match ($preference) {
                    'free' => 'Your request mentions a free option and this tool has one',
                    'api' => 'Your request mentions API access and this tool supports it',
                    'open_source' => 'Matches your open-source or self-hosting preference',
                    'privacy' => 'Matches the privacy controls mentioned in your request',
                    'beginner' => 'Matches the easy or beginner-friendly workflow you asked for',
                    'team' => 'Matches the team or collaboration need in your request',
                    'mobile' => 'Matches the mobile access mentioned in your request',
                    default => null,
                };
            }
        }

        $total = max(1, count($preferences));
        $score = (int) round(($matched / $total) * 100);

        return [
            'score' => $score,
            'matched' => $matched,
            'reasons' => collect($reasons)->filter()->unique()->take(2)->values()->all(),
        ];
    }

    private function evidenceProfile(Tool $tool, array $pricing): array
    {
        $featureTotal = max(1, $tool->featureTerms->count());
        $useCaseTotal = max(1, $tool->useCaseTerms->count());
        $verifiedFeatures = $tool->featureTerms->filter(fn ($feature) => ($feature->pivot?->verification_status ?? null) === 'verified')->count();
        $verifiedUseCases = $tool->useCaseTerms->filter(fn ($useCase) => ($useCase->pivot?->verification_status ?? null) === 'verified')->count();
        $taxonomyCoverage = (($verifiedFeatures / $featureTotal) + ($verifiedUseCases / $useCaseTotal)) / 2;
        $sourceScore = min(1, ((int) ($tool->verified_sources_count ?? 0)) / 3);
        $pricingScore = $pricing['verified_at'] ? 1 : 0;

        $score = (int) round(($taxonomyCoverage * 45) + ($sourceScore * 40) + ($pricingScore * 15));
        $label = match (true) {
            $score >= 80 => 'Strong evidence',
            $score >= 55 => 'Good evidence',
            $score >= 30 => 'Partial evidence',
            default => 'Profile data',
        };

        return ['score' => $score, 'label' => $label];
    }

    private function specificityCap(array $intent): int
    {
        $specificSignals = count($intent['features']) + count($intent['use_cases']) + count($intent['preferences']);
        $tokenCount = count($intent['tokens']);

        if ($intent['text'] === '' && $intent['shortcut_category']) {
            return 86;
        }

        if ($specificSignals === 0 && $tokenCount <= 2) {
            return 90;
        }

        if ($specificSignals <= 1 && $tokenCount <= 4) {
            return 95;
        }

        return 98;
    }

    private function matchBand(int $match): string
    {
        return match (true) {
            $match >= 85 => 'Excellent fit',
            $match >= 70 => 'Strong fit',
            $match >= 55 => 'Good fit',
            default => 'Broad fit',
        };
    }

    private function adjustmentTips(array $criteria): array
    {
        $tips = [];

        if ($criteria['filters'] !== []) {
            $tips[] = 'Remove one advanced filter to widen the shortlist.';
        }
        if (! in_array($criteria['budget'], ['any', '50plus'], true)) {
            $tips[] = 'Try a wider budget or “Any” if pricing is not a hard limit.';
        }
        if ($criteria['priority'] !== 'any') {
            $tips[] = 'Set “What matters most?” to No preference for a broader match.';
        }
        if ($criteria['shortcut'] === '') {
            $tips[] = 'Choose a popular task shortcut to give the Finder a stronger category signal.';
        }

        $tips[] = 'Describe the outcome you want, such as “summarize research PDFs with citations” instead of only “research”.';

        return array_slice(array_values(array_unique($tips)), 0, 4);
    }

    private function applyResultLabels(Collection $results, array $criteria): Collection
    {
        return $results->values()->map(function (array $result, int $index) use ($criteria) {
            if ($index === 0) {
                $result['label'] = 'Best Match';
                $result['label_icon'] = 'sparkles';
                return $result;
            }

            if ($index === 1) {
                $result['label'] = match ($criteria['priority']) {
                    'value' => 'Best Value',
                    'ease' => 'Easy Alternative',
                    'privacy' => 'Privacy Fit',
                    'api' => 'API Fit',
                    default => 'Strong Alternative',
                };
                $result['label_icon'] = 'badge-check';
                return $result;
            }

            if ($index === 2) {
                $isValueFit = $criteria['priority'] === 'value'
                    || $result['has_free']
                    || ($criteria['budget'] !== 'any' && $result['budget_score'] >= 95);
                $result['label'] = $isValueFit ? 'Best Value' : 'Another Strong Fit';
                $result['label_icon'] = $result['has_free'] ? 'badge-dollar-sign' : 'circle-check-big';
                return $result;
            }

            $result['label'] = 'More Match';
            $result['label_icon'] = 'circle-check';
            return $result;
        });
    }

    private function tagNames(Tool $tool): array
    {
        return $tool->tagTerms->pluck('name')->filter()->values()->all();
    }

    private function hasAny(array $haystack, array $needles): bool
    {
        return count(array_intersect($haystack, $needles)) > 0;
    }

    private function signalHits(string $normalizedText, array $signals): int
    {
        if ($normalizedText === '') {
            return 0;
        }

        $hits = 0;
        foreach ($signals as $signal) {
            $needle = $this->normalizeText((string) $signal);
            if ($needle !== '' && str_contains(' '.$normalizedText.' ', ' '.$needle.' ')) {
                $hits++;
                continue;
            }

            $signalTokens = $this->tokens($needle);
            if ($signalTokens !== [] && count(array_intersect($signalTokens, $this->tokens($normalizedText))) === count($signalTokens)) {
                $hits++;
            }
        }

        return $hits;
    }

    private function overlapCount(array $queryTokens, string $text): int
    {
        if ($queryTokens === [] || $text === '') {
            return 0;
        }

        return count(array_intersect($queryTokens, $this->tokens($text)));
    }

    private function normalizeText(?string $value): string
    {
        $text = Str::ascii(mb_strtolower(trim((string) $value)));
        $text = preg_replace('/[^a-z0-9]+/u', ' ', $text) ?: '';
        $tokens = preg_split('/\s+/', trim($text)) ?: [];
        $aliases = (array) config('tool_finder.token_aliases', []);
        $tokens = array_map(fn ($token) => $aliases[$token] ?? $token, $tokens);

        return trim(implode(' ', $tokens));
    }

    private function tokens(string $normalizedText): array
    {
        if ($normalizedText === '') {
            return [];
        }

        $stopWords = [
            'a', 'an', 'and', 'are', 'as', 'at', 'be', 'best', 'by', 'can', 'do', 'for', 'from',
            'help', 'i', 'in', 'is', 'it', 'me', 'my', 'of', 'on', 'or', 'please', 'that', 'the',
            'this', 'to', 'tool', 'tools', 'use', 'using', 'want', 'with', 'ai', 'need', 'make', 'create',
        ];

        $tokens = preg_split('/\s+/', trim($normalizedText)) ?: [];
        $tokens = array_values(array_unique(array_filter($tokens, fn ($token) => strlen($token) >= 2 && ! in_array($token, $stopWords, true))));

        return $tokens;
    }

    private function formatPrice(float $price): string
    {
        return fmod($price, 1.0) === 0.0
            ? number_format($price, 0)
            : rtrim(rtrim(number_format($price, 2), '0'), '.');
    }
}
