@extends('frontend.layouts.app')

@php
    $pricingDetailCanonical = route('pricing.show', $tool);
    $pricingDetailTitle = html_entity_decode($pricingSeo['title'] ?? ($tool->name . ' Pricing and Plans | AI Orbit'), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $pricingDetailDescription = html_entity_decode(
        $pricingSeo['description'] ?? \Illuminate\Support\Str::limit(
            'Compare ' . $tool->name . ' pricing, plans, limits, API rates and published price history on AI Orbit.',
            158,
            ''
        ),
        ENT_QUOTES | ENT_HTML5,
        'UTF-8'
    );
    $pricingSchemaImage = $tool->logo_url;
    if (!\Illuminate\Support\Str::startsWith($pricingSchemaImage, ['http://', 'https://'])) {
        $pricingSchemaImage = url('/' . ltrim($pricingSchemaImage, '/'));
    }

    $pricingPageSchema = [
        '@' . 'context' => 'https://schema.org',
        '@' . 'type' => 'WebPage',
        'name' => $pricingDetailTitle,
        'description' => $pricingDetailDescription,
        'url' => $pricingDetailCanonical,
        'about' => [
            '@' . 'type' => 'SoftwareApplication',
            'name' => $tool->name,
            'url' => route('tools.show', $tool),
            'image' => $pricingSchemaImage,
            'applicationCategory' => $tool->category?->name ?: 'Artificial Intelligence',
        ],
    ];

    $pricingPlansForSeo = $tool->pricingPlans ?? collect();
    $pricingHasFreePlan = $pricingPlansForSeo->contains(
        fn ($plan) => $plan->monthly_price !== null && (float) $plan->monthly_price === 0.0
    );
    $pricingPaidMonthly = $pricingPlansForSeo
        ->filter(fn ($plan) => $plan->monthly_price !== null && (float) $plan->monthly_price > 0)
        ->map(fn ($plan) => (float) $plan->monthly_price);
    $pricingLowestPaidMonthly = $pricingPaidMonthly->isNotEmpty() ? $pricingPaidMonthly->min() : null;
    $pricingPlanNames = $pricingPlansForSeo->pluck('plan_name')->filter()->unique()->values();

    $pricingBreadcrumbSchema = [
        '@' . 'context' => 'https://schema.org',
        '@' . 'type' => 'BreadcrumbList',
        'itemListElement' => [
            [
                '@' . 'type' => 'ListItem',
                'position' => 1,
                'name' => 'Home',
                'item' => route('home'),
            ],
            [
                '@' . 'type' => 'ListItem',
                'position' => 2,
                'name' => 'Pricing Intelligence',
                'item' => route('pricing.index'),
            ],
            [
                '@' . 'type' => 'ListItem',
                'position' => 3,
                'name' => $tool->name . ' Pricing',
                'item' => $pricingDetailCanonical,
            ],
        ],
    ];

    $money = static function ($amount, $currency = 'USD') {
        if ($amount === null) return '—';
        $currency = strtoupper((string) ($currency ?: 'USD'));
        $symbol = match ($currency) {
            'USD' => '$',
            'EUR' => '€',
            'GBP' => '£',
            default => $currency . ' ',
        };
        return $symbol . number_format((float) $amount, 2);
    };

    $freshnessLabel = match ($pricingSummary['freshness'] ?? 'unverified') {
        'fresh' => 'Fresh',
        'review' => 'Needs review',
        'stale' => 'Stale',
        default => 'Unverified',
    };
@endphp

@section('title', $pricingDetailTitle)
@section('meta_description', $pricingDetailDescription)
@section('canonical', $pricingDetailCanonical)
@section('og_image', $pricingSchemaImage)
@section('robots', $billingView === 'monthly'
    ? 'index,follow,max-image-preview:large,max-snippet:-1,max-video-preview:-1'
    : 'noindex,follow,max-image-preview:large,max-snippet:-1,max-video-preview:-1')

@push('head')
<script type="application/ld+json">{!! json_encode(
    $pricingPageSchema,
    JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
) !!}</script>
<script type="application/ld+json">{!! json_encode(
    $pricingBreadcrumbSchema,
    JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
) !!}</script>
@endpush

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/frontend/pricing-intelligence.css') }}?v={{ is_file(public_path('css/frontend/pricing-intelligence.css')) ? filemtime(public_path('css/frontend/pricing-intelligence.css')) : '20260919' }}">
@endpush

@section('content')
<section class="pi-detail-hero">
    <div class="pi-wrap">
        <a class="pi-back" href="{{ route('pricing.index') }}">
            <i data-lucide="arrow-left"></i> Pricing Intelligence
        </a>

        <div class="pi-detail-head">
            <div class="pi-logo pi-logo-lg">
                <img src="{{ $tool->logo_url }}" alt="{{ $tool->name }} logo">
            </div>

            <div class="pi-detail-copy">
                <span class="pi-kicker">{{ $tool->company?->name ?? 'AI Tool' }}</span>
                <h1>{{ $tool->name }} pricing</h1>
                <p>Plans, billing options, API pricing and published price history.</p>

                <div class="pi-verification-strip">
                    <span class="pi-freshness {{ $pricingSummary['freshness'] ?? 'unverified' }}">
                        <i data-lucide="badge-check"></i> {{ $freshnessLabel }}
                    </span>

                    @if($pricingSummary['latest_evidence_at'] ?? null)
                        <span>
                            <i data-lucide="calendar-check-2"></i>
                            Verified {{ $pricingSummary['latest_evidence_at']->format('M j, Y') }}
                        </span>
                    @else
                        <span><i data-lucide="circle-alert"></i> Verification date not published</span>
                    @endif

                    <span>
                        <i data-lucide="database"></i>
                        {{ $pricingSummary['source_count'] ?? 0 }} source{{ ($pricingSummary['source_count'] ?? 0) === 1 ? '' : 's' }}
                    </span>

                    @if($pricingSummary['official_source'] ?? null)
                        <a href="{{ $pricingSummary['official_source']->source_url }}" target="_blank" rel="noopener noreferrer">
                            <i data-lucide="external-link"></i>
                            {{ $pricingSummary['official_source']->source_name ?: 'Official pricing source' }}
                        </a>
                    @endif
                </div>
            </div>

            <a class="pi-primary" href="{{ route('tools.show', $tool) }}">
                View tool profile <i data-lucide="arrow-up-right"></i>
            </a>
        </div>
    </div>
</section>

<section class="pi-wrap pi-detail-body">
    <div class="pi-pricing-controls">
        <div class="pi-heading pi-heading-compact">
            <div>
                <span>PLANS</span>
                <h2>Available pricing</h2>
                <p>{{ $pricingSummary['verified_plan_count'] ?? 0 }} of {{ $tool->pricingPlans->count() }} plans have verification evidence.</p>
            </div>
            <small>{{ $tool->pricingPlans->count() }} plans</small>
        </div>

        @if($pricingSummary['has_annual_pricing'] ?? false)
            <nav class="pi-billing-toggle" aria-label="Billing view">
                <a href="{{ route('pricing.show', $tool) }}" class="{{ $billingView === 'monthly' ? 'active' : '' }}">
                    Monthly
                </a>
                <a href="{{ route('pricing.show', ['tool' => $tool, 'billing' => 'annual']) }}" class="{{ $billingView === 'annual' ? 'active' : '' }}">
                    Annual
                </a>
            </nav>
        @endif
    </div>

    <div class="pi-plan-grid">
        @forelse ($tool->pricingPlans as $plan)
            @php
                $monthlyPrice = $plan->monthly_price !== null ? (float) $plan->monthly_price : null;
                $yearlyPrice = $plan->yearly_price !== null ? (float) $plan->yearly_price : null;
                $planFreshness = $plan->freshness;
                $planFreshnessLabel = match ($planFreshness) {
                    'fresh' => 'Fresh',
                    'review' => 'Needs review',
                    'stale' => 'Stale',
                    default => 'Unverified',
                };
                $primaryPlanSource = $plan->sources->first();
            @endphp

            <article class="pi-plan pi-plan-v2">
                <header class="pi-plan-head">
                    <span class="pi-plan-label">{{ $plan->plan_name }}</span>
                    <span class="pi-freshness {{ $planFreshness }}">{{ $planFreshnessLabel }}</span>
                </header>

                <div class="pi-plan-price">
                    @if($billingView === 'annual')
                        @if($yearlyPrice !== null)
                            @if($yearlyPrice === 0.0)
                                <strong>Free</strong>
                            @else
                                <strong>{{ $money($yearlyPrice, $plan->currency) }}</strong>
                                <small>/year</small>
                            @endif
                        @else
                            <strong class="pi-price-muted">Not listed</strong>
                            <small>annual price</small>
                        @endif
                    @else
                        @if($monthlyPrice !== null)
                            @if($monthlyPrice === 0.0)
                                <strong>Free</strong>
                            @else
                                <strong>{{ $money($monthlyPrice, $plan->currency) }}</strong>
                                <small>/month</small>
                            @endif
                        @else
                            <strong>Custom</strong>
                        @endif
                    @endif
                </div>

                <div class="pi-plan-insights">
                    @if($billingView === 'annual' && $plan->annual_monthly_equivalent)
                        <span><i data-lucide="calculator"></i> {{ $money($plan->annual_monthly_equivalent, $plan->currency) }}/mo equivalent</span>
                    @elseif($billingView === 'annual' && $monthlyPrice !== null)
                        <span><i data-lucide="calendar-days"></i> Monthly: {{ $monthlyPrice === 0.0 ? 'Free' : $money($monthlyPrice, $plan->currency) }}</span>
                    @elseif($billingView === 'monthly' && $yearlyPrice !== null)
                        <span><i data-lucide="calendar-days"></i> Annual: {{ $yearlyPrice === 0.0 ? 'Free' : $money($yearlyPrice, $plan->currency) }}</span>
                    @endif

                    @if($plan->annual_savings_percent)
                        <span class="pi-save"><i data-lucide="badge-percent"></i> Save {{ number_format((float)$plan->annual_savings_percent, 1) }}% annually</span>
                    @endif
                </div>

                @if ($plan->api_price_label)
                    <p><i data-lucide="braces"></i> {{ $plan->api_price_label }}</p>
                @endif

                @if ($plan->credits)
                    <p><i data-lucide="coins"></i> {{ $plan->credits }}</p>
                @endif

                @if ($plan->limits)
                    <p><i data-lucide="gauge"></i> {{ $plan->limits }}</p>
                @endif

                @if($plan->billing_type || $plan->billing_unit)
                    <p>
                        <i data-lucide="receipt-text"></i>
                        {{ \Illuminate\Support\Str::headline((string) $plan->billing_type) }}
                        @if($plan->billing_unit) · {{ $plan->billing_unit }} @endif
                    </p>
                @endif

                <footer class="pi-plan-footer-v2">
                    <span>
                        <i data-lucide="database"></i>
                        {{ $plan->sources->count() }} monitored source{{ $plan->sources->count() === 1 ? '' : 's' }}
                    </span>
                    @if($plan->latest_evidence_at)
                        <span><i data-lucide="clock-3"></i> {{ $plan->latest_evidence_at->diffForHumans() }}</span>
                    @endif
                    @if($primaryPlanSource)
                        <a href="{{ $primaryPlanSource->source_url }}" target="_blank" rel="noopener noreferrer">
                            Source <i data-lucide="external-link"></i>
                        </a>
                    @endif
                </footer>
            </article>
        @empty
            <div class="pi-empty">
                <h3>No plans published</h3>
            </div>
        @endforelse
    </div>

    @if($tool->pricingPlans->isNotEmpty())
        <section class="pi-panel pi-plan-comparison-panel">
            <div class="pi-panel-title pi-panel-title-stack-mobile">
                <span><i data-lucide="table-properties"></i> Plan comparison</span>
                <small>Published values only — blanks mean the provider has not supplied a comparable field.</small>
            </div>

            <div class="pi-plan-table-wrap">
                <table class="pi-plan-table">
                    <thead>
                        <tr>
                            <th>Detail</th>
                            @foreach($tool->pricingPlans as $plan)
                                <th>{{ $plan->plan_name }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <th>Monthly</th>
                            @foreach($tool->pricingPlans as $plan)
                                <td>
                                    @if($plan->monthly_price === null)
                                        —
                                    @elseif((float)$plan->monthly_price === 0.0)
                                        <strong>Free</strong>
                                    @else
                                        {{ $money($plan->monthly_price, $plan->currency) }}
                                    @endif
                                </td>
                            @endforeach
                        </tr>
                        <tr>
                            <th>Annual</th>
                            @foreach($tool->pricingPlans as $plan)
                                <td>
                                    @if($plan->yearly_price === null)
                                        —
                                    @elseif((float)$plan->yearly_price === 0.0)
                                        <strong>Free</strong>
                                    @else
                                        {{ $money($plan->yearly_price, $plan->currency) }}
                                    @endif
                                </td>
                            @endforeach
                        </tr>
                        <tr>
                            <th>Annual monthly equivalent</th>
                            @foreach($tool->pricingPlans as $plan)
                                <td>{{ $plan->annual_monthly_equivalent ? $money($plan->annual_monthly_equivalent, $plan->currency) : '—' }}</td>
                            @endforeach
                        </tr>
                        <tr>
                            <th>Annual saving</th>
                            @foreach($tool->pricingPlans as $plan)
                                <td>{{ $plan->annual_savings_percent ? number_format((float)$plan->annual_savings_percent, 1).'%' : '—' }}</td>
                            @endforeach
                        </tr>
                        <tr>
                            <th>API pricing</th>
                            @foreach($tool->pricingPlans as $plan)
                                <td>{{ $plan->api_price_label ?: '—' }}</td>
                            @endforeach
                        </tr>
                        <tr>
                            <th>Credits</th>
                            @foreach($tool->pricingPlans as $plan)
                                <td>{{ $plan->credits ?: '—' }}</td>
                            @endforeach
                        </tr>
                        <tr>
                            <th>Limits</th>
                            @foreach($tool->pricingPlans as $plan)
                                <td>{{ $plan->limits ?: '—' }}</td>
                            @endforeach
                        </tr>
                        <tr>
                            <th>Billing</th>
                            @foreach($tool->pricingPlans as $plan)
                                <td>
                                    {{ $plan->billing_type ? \Illuminate\Support\Str::headline((string)$plan->billing_type) : '—' }}
                                    @if($plan->billing_unit)<small>{{ $plan->billing_unit }}</small>@endif
                                </td>
                            @endforeach
                        </tr>
                        <tr>
                            <th>Verification</th>
                            @foreach($tool->pricingPlans as $plan)
                                <td>
                                    <span class="pi-freshness {{ $plan->freshness }}">
                                        {{ match($plan->freshness) {
                                            'fresh' => 'Fresh',
                                            'review' => 'Needs review',
                                            'stale' => 'Stale',
                                            default => 'Unverified',
                                        } }}
                                    </span>
                                    @if($plan->latest_evidence_at)<small>{{ $plan->latest_evidence_at->format('M j, Y') }}</small>@endif
                                </td>
                            @endforeach
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>
    @endif

    @include('frontend.partials.quick-vote', [
        'type' => 'pricing',
        'id' => $tool->id,
        'summary' => $pricingFeedback,
        'label' => 'Is this pricing information accurate?',
    ])

    <div class="pi-detail-grid">
        <section class="pi-panel">
            <div class="pi-panel-title">
                <span><i data-lucide="history"></i> Published pricing history</span>
                <small>{{ $history->count() }} recent change{{ $history->count() === 1 ? '' : 's' }}</small>
            </div>

            @forelse ($history as $change)
                @php
                    $numericMetric = in_array($change->metric, ['monthly_price', 'yearly_price'], true);
                    $metricLabel = match($change->metric) {
                        'monthly_price' => 'Monthly price',
                        'yearly_price' => 'Annual price',
                        'api_price_label' => 'API pricing',
                        default => \Illuminate\Support\Str::headline((string)$change->metric),
                    };

                    $oldDisplay = $change->old_value;
                    $newDisplay = $change->new_value;
                    if ($numericMetric && $change->old_price !== null) {
                        $oldDisplay = '$' . number_format((float) $change->old_price, 2);
                    }
                    if ($numericMetric && $change->new_price !== null) {
                        $newDisplay = '$' . number_format((float) $change->new_price, 2);
                    }

                    $percentChange = null;
                    if ($numericMetric && $change->old_price !== null && (float)$change->old_price > 0 && $change->new_price !== null) {
                        $percentChange = round((((float)$change->new_price - (float)$change->old_price) / (float)$change->old_price) * 100, 1);
                    }

                    $historyIcon = match($change->change_type) {
                        'decrease' => 'trending-down',
                        'new_plan' => 'plus',
                        'removed_plan' => 'minus',
                        default => 'trending-up',
                    };
                @endphp

                <div class="pi-history pi-history-v2">
                    <span class="pi-change-icon {{ $change->change_type }}">
                        <i data-lucide="{{ $historyIcon }}"></i>
                    </span>
                    <div>
                        <div class="pi-history-title-row">
                            <b>{{ $change->plan_name }} · {{ $metricLabel }}</b>
                            @if($percentChange !== null)
                                <span class="pi-delta {{ $percentChange < 0 ? 'down' : 'up' }}">
                                    {{ $percentChange > 0 ? '+' : '' }}{{ number_format($percentChange, 1) }}%
                                </span>
                            @endif
                        </div>
                        <p>{{ $oldDisplay ?? '—' }} → {{ $newDisplay ?? '—' }}</p>
                        @if($change->source_url)
                            <a class="pi-history-source" href="{{ $change->source_url }}" target="_blank" rel="noopener noreferrer">
                                View source <i data-lucide="external-link"></i>
                            </a>
                        @endif
                    </div>
                    <small>{{ $change->created_at?->format('M j, Y') }}</small>
                </div>
            @empty
                <div class="pi-panel-empty">No published pricing changes for this tool yet.</div>
            @endforelse
        </section>

        <section class="pi-panel">
            <div class="pi-panel-title">
                <span><i data-lucide="shield-check"></i> Verification</span>
            </div>

            <div class="pi-verification-card">
                <span class="pi-freshness {{ $pricingSummary['freshness'] ?? 'unverified' }}">{{ $freshnessLabel }}</span>
                <h3>{{ $pricingSummary['verified_plan_count'] ?? 0 }} verified plan{{ ($pricingSummary['verified_plan_count'] ?? 0) === 1 ? '' : 's' }}</h3>
                <p>AI Orbit uses published plan evidence and monitored pricing sources. Verification freshness reflects the newest recorded plan or source check.</p>

                @if($pricingSummary['latest_evidence_at'] ?? null)
                    <div><i data-lucide="calendar-check-2"></i> Latest check: {{ $pricingSummary['latest_evidence_at']->format('M j, Y') }}</div>
                @endif

                <div><i data-lucide="database"></i> {{ $pricingSummary['source_count'] ?? 0 }} unique monitored source{{ ($pricingSummary['source_count'] ?? 0) === 1 ? '' : 's' }}</div>

                @if($pricingSummary['official_source'] ?? null)
                    <a href="{{ $pricingSummary['official_source']->source_url }}" target="_blank" rel="noopener noreferrer">
                        Open official pricing source <i data-lucide="external-link"></i>
                    </a>
                @endif
            </div>

            <div class="pi-panel-title pi-alt-title">
                <span><i data-lucide="shuffle"></i> Alternatives</span>
            </div>

            @forelse ($alternatives as $alt)
                <a class="pi-alt" href="{{ route('pricing.show', $alt) }}">
                    <div class="pi-logo">
                        <img src="{{ $alt->logo_url }}" alt="{{ $alt->name }} logo">
                    </div>
                    <div>
                        <b>{{ $alt->name }}</b>
                        <small>
                            {{ $alt->pricingPlans->count() }} plans · {{ (float)($alt->rating ?? 0) > 0 ? number_format((float)$alt->rating, 1).' rating' : 'Not rated' }}
                        </small>
                    </div>
                    <i data-lucide="chevron-right"></i>
                </a>
            @empty
                <div class="pi-panel-empty">No pricing alternatives available yet.</div>
            @endforelse
        </section>
    </div>

    @if($pricingPlansForSeo->isNotEmpty())
    <section class="pi-panel pi-faq-panel">
        <div class="pi-panel-title">
            <span><i data-lucide="circle-help"></i> {{ $tool->name }} pricing FAQ</span>
        </div>

        <div class="pi-history">
            <span class="pi-change-icon"><i data-lucide="badge-dollar-sign"></i></span>
            <div>
                <b>How much does {{ $tool->name }} cost?</b>
                <p>
                    @if($pricingHasFreePlan && $pricingLowestPaidMonthly !== null)
                        AI Orbit currently lists a free plan and paid plans starting at ${{ number_format($pricingLowestPaidMonthly, 2) }} per month. Check the plans above for limits, billing details and API pricing where available.
                    @elseif($pricingHasFreePlan)
                        AI Orbit currently lists a free plan for {{ $tool->name }}. Other pricing may be custom, usage-based or published without a standard monthly amount.
                    @elseif($pricingLowestPaidMonthly !== null)
                        AI Orbit currently lists paid {{ $tool->name }} plans starting at ${{ number_format($pricingLowestPaidMonthly, 2) }} per month. Check the plans above for limits and billing details.
                    @else
                        {{ $tool->name }} pricing is listed as custom, usage-based or without a standard monthly amount in the current AI Orbit dataset.
                    @endif
                </p>
            </div>
        </div>

        <div class="pi-history">
            <span class="pi-change-icon"><i data-lucide="gift"></i></span>
            <div>
                <b>Is {{ $tool->name }} free?</b>
                <p>
                    @if($pricingHasFreePlan)
                        Yes. AI Orbit currently records at least one {{ $tool->name }} plan with a $0 monthly price. Review the plan limits above because free-tier allowances can differ from paid plans.
                    @else
                        AI Orbit does not currently record a $0 monthly {{ $tool->name }} plan. Pricing can change, so verify the latest offer with the provider before purchasing.
                    @endif
                </p>
            </div>
        </div>

        @if($pricingPlanNames->isNotEmpty())
        <div class="pi-history">
            <span class="pi-change-icon"><i data-lucide="layers-3"></i></span>
            <div>
                <b>What {{ $tool->name }} pricing plans are listed?</b>
                <p>AI Orbit currently lists {{ $pricingPlanNames->join(', ', ' and ') }}. The plan cards and comparison table above show the stored monthly or yearly price, limits, credits and API rate details where available.</p>
            </div>
        </div>
        @endif
    </section>
    @endif
</section>
@endsection
