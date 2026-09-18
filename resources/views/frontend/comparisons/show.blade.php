@extends('frontend.layouts.app')

@php
    $comparisonSeoTitle = data_get($comparison, 'meta_title')
        ?: data_get($comparison, 'title')
        ?: $title
        ?: 'AI Comparison';

    $comparisonSeoTitle = str_ireplace(
        'AI Hub',
        'AI Orbit',
        $comparisonSeoTitle
    );

    $comparisonSeoTitle = html_entity_decode(
        html_entity_decode(
            $comparisonSeoTitle,
            ENT_QUOTES | ENT_HTML5,
            'UTF-8'
        ),
        ENT_QUOTES | ENT_HTML5,
        'UTF-8'
    );

    if (!str_contains(
        strtolower($comparisonSeoTitle),
        'ai orbit'
    )) {
        $comparisonSeoTitle .= ' | AI Orbit';
    }

    $comparisonItemNames = collect($items ?? [])
        ->pluck('name')
        ->filter()
        ->values();

    if ($comparisonItemNames->count() === 2) {
        $comparisonSeoDescription = 'Compare '
            . $comparisonItemNames[0]
            . ' vs '
            . $comparisonItemNames[1]
            . ' side by side across pricing, benchmarks, capabilities and verified product data on AI Orbit.';
    } else {
        $comparisonSeoDescription = data_get($comparison, 'meta_description')
            ?: data_get($comparison, 'summary')
            ?: data_get($comparison, 'description')
            ?: data_get($comparison, 'notes')
            ?: 'Compare AI tools and models with detailed features, pricing, capabilities and insights on AI Orbit.';
    }

    $comparisonSeoDescription = html_entity_decode(
        html_entity_decode(
            strip_tags($comparisonSeoDescription),
            ENT_QUOTES | ENT_HTML5,
            'UTF-8'
        ),
        ENT_QUOTES | ENT_HTML5,
        'UTF-8'
    );

    $comparisonFaq = collect(data_get($comparison, 'seo_faq', []))
        ->map(function ($faq) {
            return [
                'question' => str_ireplace(
                    'AI Hub',
                    'AI Orbit',
                    $faq['question'] ?? ''
                ),
                'answer' => str_ireplace(
                    'AI Hub',
                    'AI Orbit',
                    $faq['answer'] ?? ''
                ),
            ];
        })
        ->filter(fn ($faq) =>
            $faq['question'] !== '' &&
            $faq['answer'] !== ''
        )
        // Pair-specific stored FAQs can become stale when comparison items are
        // edited. Rebuild that question from the currently resolved entities.
        ->reject(function ($faq) {
            $question = strtolower(trim((string) $faq['question']));
            return str_starts_with($question, 'which is better:')
                || str_starts_with($question, 'which is better ');
        })
        ->values();

    if ($comparisonItemNames->count() === 2) {
        $freshPairFaq = [
            'question' => 'How do ' . $comparisonItemNames[0] . ' and ' . $comparisonItemNames[1] . ' compare?',
            'answer' => 'Compare the side-by-side pricing, benchmark, capability and product data on this page. The better fit depends on your use case and the verified evidence available for each product.',
        ];

        $comparisonFaq->prepend($freshPairFaq);
    }

    $comparisonIsSeoPair = !($isPreview ?? false)
        && $comparison
        && $comparisonItemNames->count() === 2
        && (bool) data_get($comparisonSeoAssessment ?? [], 'indexable', false);

    $comparisonUrl = ($isPreview ?? false)
        ? request()->fullUrl()
        : route('comparisons.show', $comparison);

    $editComparisonUrl = route('comparisons.builder', [
        'type' => $comparisonType,
        'items' => collect($items ?? [])->pluck('id')->filter()->implode(','),
    ]);

    $sharedBenchmarkMatrix = collect($intelligence['sharedBenchmarkMatrix'] ?? []);
    $additionalBenchmarkMatrix = collect($intelligence['additionalBenchmarkMatrix'] ?? []);
    $decisionSignals = collect($intelligence['decisionSignals'] ?? []);
    $pricingIntel = $intelligence['pricing'] ?? [];
    $capabilityCoverage = $intelligence['capabilityCoverage'] ?? ['shared' => [], 'unique' => []];
    $useCaseCoverage = $intelligence['useCaseCoverage'] ?? ['shared' => [], 'unique' => []];
    $itemEvidence = $intelligence['itemEvidence'] ?? [];
    $benchmarkWins = $intelligence['wins'] ?? [];
    $benchmarkTies = $intelligence['ties'] ?? [];
    $benchmarkLeaders = $intelligence['benchmarkLeaders'] ?? [];
    $benchmarkGroups = $intelligence['benchmarkGroups'] ?? [];
    $evidenceAsOf = $intelligence['evidenceAsOf'] ?? null;

    // Visible FAQ content and FAQ schema must describe the same current pair.
    // Add only factual questions that can be answered from structured data on
    // this exact page; missing evidence is never guessed.
    if ($comparisonItemNames->count() === 2) {
        $firstItem = collect($items ?? [])->values()->get(0);
        $secondItem = collect($items ?? [])->values()->get(1);
        $firstPricing = $firstItem ? ($pricingIntel[(int) $firstItem->id] ?? []) : [];
        $secondPricing = $secondItem ? ($pricingIntel[(int) $secondItem->id] ?? []) : [];

        if ($comparisonType === 'model' && $firstItem && $secondItem) {
            $firstInput = $firstPricing['input'] ?? null;
            $firstOutput = $firstPricing['output'] ?? null;
            $secondInput = $secondPricing['input'] ?? null;
            $secondOutput = $secondPricing['output'] ?? null;

            if ($firstInput !== null && $firstOutput !== null && $secondInput !== null && $secondOutput !== null) {
                $pricingAnswer = 'API cost depends on your input/output token mix. Use the cost calculator on this page to estimate the same workload with both models.';
                if ((float) $firstInput <= (float) $secondInput && (float) $firstOutput <= (float) $secondOutput
                    && ((float) $firstInput < (float) $secondInput || (float) $firstOutput < (float) $secondOutput)) {
                    $pricingAnswer = $firstItem->name.' has the lower listed per-million-token rate on both input and output in the current verified pricing data.';
                } elseif ((float) $secondInput <= (float) $firstInput && (float) $secondOutput <= (float) $firstOutput
                    && ((float) $secondInput < (float) $firstInput || (float) $secondOutput < (float) $firstOutput)) {
                    $pricingAnswer = $secondItem->name.' has the lower listed per-million-token rate on both input and output in the current verified pricing data.';
                }

                $comparisonFaq->push([
                    'question' => 'Which is cheaper for API use: '.$firstItem->name.' or '.$secondItem->name.'?',
                    'answer' => $pricingAnswer,
                ]);
            }
        } elseif ($comparisonType === 'tool' && $firstItem && $secondItem) {
            $firstStarting = $firstPricing['starting'] ?? null;
            $secondStarting = $secondPricing['starting'] ?? null;
            if ($firstStarting !== null && $secondStarting !== null) {
                $cheaper = (float) $firstStarting < (float) $secondStarting
                    ? $firstItem->name
                    : ((float) $secondStarting < (float) $firstStarting ? $secondItem->name : null);
                $comparisonFaq->push([
                    'question' => 'Which has the lower starting price: '.$firstItem->name.' or '.$secondItem->name.'?',
                    'answer' => $cheaper
                        ? $cheaper.' has the lower starting monthly price in AI Orbit’s current structured pricing data. Compare included features and billing terms before choosing a plan.'
                        : 'Their current structured starting monthly prices are the same. Compare plan features and billing terms on this page before choosing.',
                ]);
            }
        }

        if (count($sharedBenchmarkMatrix) > 0) {
            $comparisonFaq->push([
                'question' => 'How much shared benchmark evidence is available for '.$comparisonItemNames[0].' vs '.$comparisonItemNames[1].'?',
                'answer' => 'AI Orbit currently shows '.count($sharedBenchmarkMatrix).' shared verified benchmark'.(count($sharedBenchmarkMatrix) === 1 ? '' : 's').' where both products have comparable results. One-sided benchmark evidence is shown separately and is not counted as a head-to-head win.',
            ]);
        }

        $comparisonFaq = $comparisonFaq->unique('question')->take(5)->values();
    }

    $freshnessLabel = fn ($status) => match ($status) {
        'fresh' => 'Fresh',
        'review' => 'Review soon',
        'stale' => 'Stale',
        default => 'Unverified',
    };
@endphp

@section('title', $comparisonSeoTitle)
@section('meta_description', $comparisonSeoDescription)
@section('canonical', $comparisonUrl)

@section(
    'robots',
    (($isPreview ?? false) || request()->query() || !$comparisonIsSeoPair)
        ? 'noindex,follow'
        : 'index,follow,max-image-preview:large'
)

@push('styles')
<link rel="stylesheet" href="{{ asset('css/frontend/comparisons.css') }}">
@endpush
@if($comparisonIsSeoPair)

@push('head')
@php
    $comparisonUrl = route('comparisons.show', $comparison);

    $comparisonPageSchema = [
        '@' . 'context' => 'https://schema.org',
        '@' . 'type' => 'WebPage',
        'name' => $comparisonSeoTitle,
        'description' => $comparisonSeoDescription,
        'url' => $comparisonUrl,
        'dateModified' => optional($comparison->last_verified_at)->toAtomString(),
    ];

    $comparisonBreadcrumbSchema = [
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
                'name' => 'Comparisons',
                'item' => route('comparisons.index'),
            ],
            [
                '@' . 'type' => 'ListItem',
                'position' => 3,
                'name' => $comparisonSeoTitle,
                'item' => $comparisonUrl,
            ],
        ],
    ];

    $comparisonFaqSchema = $comparisonFaq->isNotEmpty()
    ? [
        '@' . 'context' => 'https://schema.org',
        '@' . 'type' => 'FAQPage',
        'mainEntity' => $comparisonFaq
            ->map(fn ($faq) => [
                '@' . 'type' => 'Question',
                'name' => $faq['question'],
                'acceptedAnswer' => [
                    '@' . 'type' => 'Answer',
                    'text' => $faq['answer'],
                ],
            ])
            ->all(),
    ]
    : null;
@endphp

<script type="application/ld+json">{!! json_encode(
    $comparisonPageSchema,
    JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
) !!}</script>

<script type="application/ld+json">{!! json_encode(
    $comparisonBreadcrumbSchema,
    JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
) !!}</script>

@if($comparisonFaqSchema)
<script type="application/ld+json">{!! json_encode(
    $comparisonFaqSchema,
    JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
) !!}</script>
@endif
@endpush
@endif
@section('content')
<section class="comparison-detail-hero">
    <div class="compare-container">
        <div class="detail-breadcrumbs"><a href="{{ route('comparisons.index') }}">Comparisons</a><i data-lucide="chevron-right"></i><span>{{ $comparisonType === 'tool' ? 'AI Tools' : 'AI Models' }}</span></div>
        <div class="detail-title-row">
            <div>
                <span class="comparison-kicker"><i data-lucide="scale"></i> {{ $isPreview ? 'Live comparison' : 'AI Orbit comparison' }}</span>
                <h1>{{ $title }}</h1>
                <p>A practical side-by-side look at performance, pricing, capabilities and product fit.</p>
            </div>
            <div class="detail-actions">
                <a href="{{ $editComparisonUrl }}"><i data-lucide="sliders-horizontal"></i> Edit selection</a>
                <button type="button" data-comparison-share aria-label="Share this comparison"><i data-lucide="share-2"></i><span data-comparison-share-label>Share</span></button>
            </div>
        </div>

        @if(!($isPreview ?? false) && !empty($quickRating))
            @include('frontend.partials.quick-rating', [
                'type' => 'comparison',
                'id' => $comparison->id,
                'summary' => $quickRating,
                'label' => 'Was this comparison useful?',
            ])
        @endif

        <div class="detail-product-strip cols-{{ min($items->count(),4) }}">
            @foreach($items as $item)
                <div class="detail-product-head">
                    <div class="detail-product-logo"><img src="{{ $item->logo_url }}" alt="{{ $item->name }} logo"></div>
                    <small>{{ $item->company->name ?? 'Independent' }}</small>
                    <h2>{{ $item->name }}</h2>
                    <p>{{ $comparisonType === 'tool' ? ($item->short_description ?: Str::limit($item->overview,150)) : Str::limit($item->overview,170) }}</p>
                    <a href="{{ $comparisonType === 'tool' ? route('tools.show',$item) : route('models.show',$item) }}">View profile <i data-lucide="arrow-up-right"></i></a>
                </div>
            @endforeach
        </div>
    </div>
</section>

<section class="comparison-detail-body">
<div class="compare-container">
    <div class="comparison-snapshot">
        <div class="table-title"><span><i data-lucide="scan-search"></i></span><div><h2>Comparison evidence snapshot</h2><p>Factual coverage of this comparison. AI Orbit does not declare a universal winner because the better choice depends on workload and evidence.</p></div></div>
        <div class="comparison-snapshot-grid">
            @foreach(($intelligence['snapshot'] ?? []) as $signal)
                <div class="comparison-snapshot-card">
                    <span><i data-lucide="{{ $signal['icon'] ?? 'circle-dot' }}"></i></span>
                    <div><small>{{ $signal['label'] ?? 'Signal' }}</small><strong>{{ $signal['value'] ?? '—' }}</strong><p>{{ $signal['detail'] ?? '' }}</p></div>
                </div>
            @endforeach
        </div>
    </div>

    @if($isPreview ?? false)
        <div class="custom-comparison-note"><i data-lucide="link-2"></i><p><strong>Custom comparison:</strong> this 2–4 item view is shareable by URL but intentionally excluded from search indexing. Curated public SEO comparisons remain head-to-head pairs.</p></div>
    @elseif(!$comparisonIsSeoPair && $comparisonItemNames->count() !== 2)
        <div class="custom-comparison-note"><i data-lucide="archive"></i><p><strong>Legacy multi-item comparison:</strong> this page remains available to visitors but is intentionally excluded from search indexing. New published comparison pages use exactly two entities.</p></div>
    @elseif(!$comparisonIsSeoPair)
        <div class="custom-comparison-note"><i data-lucide="shield-check"></i><p><strong>Research page:</strong> this pair is publicly available, but AI Orbit keeps it out of the search comparison library until duplicate ownership and editorial/verification checks are complete.</p></div>
    @endif

    @if($decisionSignals->isNotEmpty())
    <div class="comparison-signal-panel">
        <div class="table-title">
            <span><i data-lucide="radar"></i></span>
            <div>
                <h2>At-a-glance signals</h2>
                <p>Category-level facts from verified pricing, shared benchmarks and structured product data. These signals are not an overall ranking.</p>
            </div>
        </div>
        <div class="comparison-signal-grid">
            @foreach($decisionSignals as $signal)
                <article class="comparison-signal-card">
                    <span class="comparison-signal-icon"><i data-lucide="{{ $signal['icon'] ?? 'circle-dot' }}"></i></span>
                    <div>
                        <small>{{ $signal['label'] ?? 'Signal' }}</small>
                        <h3>{{ $signal['winner_label'] ?? 'No verified leader' }}</h3>
                        <strong>{{ $signal['value'] ?? '—' }}</strong>
                        <p>{{ $signal['detail'] ?? '' }}{{ !empty($signal['tie']) ? ' · tie' : '' }}</p>
                    </div>
                </article>
            @endforeach
        </div>
    </div>
    @endif

    <div class="comparison-control-row">
        <div>
            <strong>Comparison view</strong>
            <small>Hide rows where every selected item has the same displayed value.</small>
        </div>
        <label class="comparison-difference-toggle">
            <input type="checkbox" data-differences-toggle>
            <span></span>
            Show differences only
        </label>
    </div>

    <div class="comparison-table-card" data-difference-scope>
        <div class="table-title"><span><i data-lucide="table-2"></i></span><div><h2>Side-by-side comparison</h2><p>Comparable profile facts. Missing fields are shown explicitly instead of being guessed.</p></div></div>
        <div class="comparison-table-scroll">
            <table class="comparison-table">
                <thead><tr><th>Metric</th>@foreach($items as $item)<th>{{ $item->name }}</th>@endforeach</tr></thead>
                <tbody>
                    <tr data-difference-row><th>Provider</th>@foreach($items as $item)<td>{{ $item->company->name ?? 'Not verified' }}</td>@endforeach</tr>
                    @if($comparisonType === 'tool')
                        <tr data-difference-row><th>Rating</th>@foreach($items as $item)<td><span class="rating-cell"><i data-lucide="star"></i>{{ (float)$item->rating > 0 ? number_format((float)$item->rating,1).'/5' : 'Not rated' }}</span></td>@endforeach</tr>
                        <tr data-difference-row><th>Popularity</th>@foreach($items as $item)<td>{{ (int)$item->popularity > 0 ? number_format((int)$item->popularity) : 'Not available' }}</td>@endforeach</tr>
                        <tr data-difference-row><th>Category</th>@foreach($items as $item)<td>{{ $item->category->name ?? 'Not verified' }}</td>@endforeach</tr>
                        <tr data-difference-row><th>Pricing model</th>@foreach($items as $item)<td>@forelse((array)$item->pricing_models as $price)<span class="data-chip">{{ ucfirst((string)$price) }}</span>@empty<span class="muted">Not verified</span>@endforelse</td>@endforeach</tr>
                        <tr data-difference-row><th>Starting monthly price</th>@foreach($items as $item)@php($priceRow=$pricingIntel[(int)$item->id]??[])<td>{{ array_key_exists('starting',$priceRow) && $priceRow['starting'] !== null ? '$'.number_format((float)$priceRow['starting'],2) : 'Not verified' }}</td>@endforeach</tr>
                        <tr data-difference-row><th>Free plan</th>@foreach($items as $item)@php($priceRow=$pricingIntel[(int)$item->id]??[])<td>{{ !empty($priceRow['free_plan']) ? 'Yes' : (($priceRow['verified']??false) ? 'No free plan listed' : 'Not verified') }}</td>@endforeach</tr>
                        <tr data-difference-row><th>Platforms</th>@foreach($items as $item)<td>@forelse((array)$item->platforms as $platform)<span class="data-chip">{{ $platform }}</span>@empty<span class="muted">Not verified</span>@endforelse</td>@endforeach</tr>
                        <tr data-difference-row><th>API access</th>@foreach($items as $item)@php($profile=$item->technicalProfile)<td>{{ $profile ? (\App\Models\ToolTechnicalProfile::API_STATUSES[$profile->api_status ?: 'unknown'] ?? 'Not yet verified') : 'Not verified' }}</td>@endforeach</tr>
                        <tr data-difference-row><th>Open source</th>@foreach($items as $item)@php($profile=$item->technicalProfile)<td>{{ $profile ? (\App\Models\ToolTechnicalProfile::OPEN_SOURCE_STATUSES[$profile->open_source_status ?: 'unknown'] ?? 'Not yet verified') : 'Not verified' }}</td>@endforeach</tr>
                        <tr data-difference-row><th>Self-hosting</th>@foreach($items as $item)@php($profile=$item->technicalProfile)<td>{{ $profile ? (\App\Models\ToolTechnicalProfile::SELF_HOSTING_STATUSES[$profile->self_hosting_status ?: 'unknown'] ?? 'Not yet verified') : 'Not verified' }}</td>@endforeach</tr>
                        <tr data-difference-row><th>Deployment</th>@foreach($items as $item)@php($modes=(array)($item->technicalProfile?->deployment_modes??[]))<td>@forelse($modes as $mode)<span class="data-chip">{{ $mode }}</span>@empty<span class="muted">Not verified</span>@endforelse</td>@endforeach</tr>
                        <tr data-difference-row><th>Launch date</th>@foreach($items as $item)<td>{{ $item->launch_date?->format('M Y') ?? 'Not verified' }}</td>@endforeach</tr>
                    @else
                        <tr data-difference-row><th>Version</th>@foreach($items as $item)<td>{{ $item->version ?: 'Not verified' }}</td>@endforeach</tr>
                        <tr data-difference-row><th>Context window</th>@foreach($items as $item)<td><strong>{{ $item->context_window ?: 'Not verified' }}</strong></td>@endforeach</tr>
                        <tr data-difference-row><th>Input / 1M tokens</th>@foreach($items as $item)<td>{{ $item->input_price_per_million !== null ? '$'.number_format((float)$item->input_price_per_million,2) : 'Not verified' }}</td>@endforeach</tr>
                        <tr data-difference-row><th>Output / 1M tokens</th>@foreach($items as $item)<td>{{ $item->output_price_per_million !== null ? '$'.number_format((float)$item->output_price_per_million,2) : 'Not verified' }}</td>@endforeach</tr>
                        <tr data-difference-row><th>Pricing verification</th>@foreach($items as $item)@php($priceRow=$pricingIntel[(int)$item->id]??[])<td>{{ $priceRow['verification_label'] ?? 'Not verified' }}@if(!empty($priceRow['verified_at']))<small class="comparison-cell-note">{{ $priceRow['verified_at']->format('M j, Y') }}</small>@endif</td>@endforeach</tr>
                        <tr data-difference-row><th>Status</th>@foreach($items as $item)<td><span class="status-chip {{ $item->status }}">{{ ucfirst($item->status) }}</span></td>@endforeach</tr>
                        <tr data-difference-row><th>Release date</th>@foreach($items as $item)<td>{{ $item->release_date?->format('M Y') ?? 'Not verified' }}</td>@endforeach</tr>
                    @endif
                </tbody>
            </table>
        </div>
        <div class="comparison-empty-differences" data-no-differences hidden>No differing rows are visible in this section.</div>
    </div>

    @if($comparisonType === 'model' && collect($pricingIntel)->contains(fn($row) => ($row['input'] ?? null) !== null || ($row['output'] ?? null) !== null))
    <div class="comparison-cost-card" data-model-cost-calculator>
        <div class="table-title">
            <span><i data-lucide="calculator"></i></span>
            <div><h2>API cost calculator</h2><p>Estimate monthly token cost from the verified per-million prices stored on each model profile.</p></div>
        </div>
        <div class="comparison-cost-inputs">
            <label>Input tokens / month <span>millions</span><input type="number" min="0" step="0.1" value="10" data-cost-input-million></label>
            <label>Output tokens / month <span>millions</span><input type="number" min="0" step="0.1" value="2" data-cost-output-million></label>
            <p>Estimate excludes discounts, caching, batch pricing, regional pricing and provider-specific fees unless already reflected in the stored rate.</p>
        </div>
        <div class="comparison-cost-results cols-{{ min($items->count(),4) }}">
            @foreach($items as $item)
                @php($priceRow=$pricingIntel[(int)$item->id]??[])
                <article class="comparison-cost-result"
                    data-cost-model
                    data-input-price="{{ $priceRow['input'] ?? '' }}"
                    data-output-price="{{ $priceRow['output'] ?? '' }}">
                    <div class="tiny-logo"><img src="{{ $item->logo_url }}" alt="{{ $item->name }} logo"></div>
                    <div><small>{{ $item->name }}</small><strong data-cost-output>—</strong><p>{{ $priceRow['verification_label'] ?? 'Pricing not verified' }}</p></div>
                </article>
            @endforeach
        </div>
    </div>
    @elseif($comparisonType === 'tool')
    <div class="comparison-pricing-panel">
        <div class="table-title">
            <span><i data-lucide="wallet-cards"></i></span>
            <div><h2>Structured pricing plans</h2><p>Current plan records stored in AI Orbit. Verify enterprise/custom terms with the provider.</p></div>
        </div>
        <div class="comparison-pricing-columns cols-{{ min($items->count(),4) }}">
            @foreach($items as $item)
                @php($priceRow=$pricingIntel[(int)$item->id]??[])
                <article class="comparison-pricing-column">
                    <header><div class="tiny-logo"><img src="{{ $item->logo_url }}" alt="{{ $item->name }} logo"></div><div><strong>{{ $item->name }}</strong><small class="freshness-badge {{ $priceRow['freshness'] ?? 'unverified' }}">{{ $freshnessLabel($priceRow['freshness'] ?? 'unverified') }}</small></div></header>
                    @forelse(($priceRow['plans'] ?? collect())->take(5) as $plan)
                        <div class="comparison-plan-row">
                            <div><strong>{{ $plan->plan_name }}</strong><small>{{ $plan->billing_type ?: $plan->billing_unit ?: 'Plan' }}</small></div>
                            <span>
                                @if($plan->monthly_price !== null)
                                    {{ ($plan->currency ?: '$') === 'USD' ? '$' : ($plan->currency ?: '$') }}{{ number_format((float)$plan->monthly_price,2) }}<small>/mo</small>
                                @elseif($plan->api_price_label)
                                    {{ $plan->api_price_label }}
                                @else
                                    Custom
                                @endif
                            </span>
                        </div>
                    @empty
                        <p class="comparison-missing-note">No structured pricing plans verified yet.</p>
                    @endforelse
                    @if(!empty($priceRow['verified_at']))<footer>Latest pricing evidence {{ $priceRow['verified_at']->format('M j, Y') }}</footer>@endif
                </article>
            @endforeach
        </div>
    </div>
    @endif

    <div class="comparison-table-card comparison-benchmarks">
        <div class="table-title">
            <span><i data-lucide="gauge"></i></span>
            <div><h2>Shared benchmark head-to-head</h2><p>Only exact benchmarks with verified results for at least two selected items count toward win totals.</p></div>
        </div>
        <div class="benchmark-scoreboard cols-{{ min($items->count(),4) }}">
            @foreach($items as $item)
                <div><div class="tiny-logo"><img src="{{ $item->logo_url }}" alt="{{ $item->name }} logo"></div><span>{{ $item->name }}</span><strong>{{ (int)($benchmarkWins[(int)$item->id]??0) }} wins</strong><small>{{ (int)($benchmarkTies[(int)$item->id]??0) }} ties</small></div>
            @endforeach
        </div>

        @if($sharedBenchmarkMatrix->isNotEmpty())
            @foreach($benchmarkGroups as $groupLabel => $keys)
                <div class="benchmark-group-label"><span>{{ $groupLabel }}</span><small>{{ count($keys) }} shared benchmark{{ count($keys) === 1 ? '' : 's' }}</small></div>
                <div class="comparison-table-scroll" data-difference-scope>
                    <table class="comparison-table">
                        <thead><tr><th>Benchmark</th>@foreach($items as $item)<th>{{ $item->name }}</th>@endforeach</tr></thead>
                        <tbody>
                        @foreach($keys as $key)
                            @php($scores=$sharedBenchmarkMatrix->get($key,[]))
                            @php($b=$intelligence['benchmarkMeta'][$key]??null)
                            @php($leaders=$benchmarkLeaders[$key]['leader_ids']??[])
                            @if($b)
                            <tr data-difference-row>
                                <th>
                                    <strong>{{ $b->name }}</strong>
                                    @if($b->version)<small>{{ $b->version }}</small>@endif
                                    @php($benchmarkUrl=$b->methodology_url ?: $b->official_url)
                                    @if($benchmarkUrl && \Illuminate\Support\Str::startsWith($benchmarkUrl,['http://','https://']))
                                        <a class="benchmark-source-link" href="{{ $benchmarkUrl }}" target="_blank" rel="nofollow noopener">Methodology <i data-lucide="external-link"></i></a>
                                    @endif
                                </th>
                                @foreach($items as $item)
                                    @php($r=$scores[(int)$item->id]??null)
                                    @php($isLeader=$r && in_array((int)$item->id,array_map('intval',$leaders),true))
                                    <td class="{{ $isLeader ? 'benchmark-leader-cell' : '' }}">
                                        @if($r)
                                            <strong>{{ number_format((float)$r->score,2) }}{{ $b->unit==='%'?'%':($b->unit?' '.$b->unit:'') }}</strong>
                                            @if($isLeader)<span class="benchmark-win-badge">{{ count($leaders)>1?'Tie':'Lead' }}</span>@endif
                                            <small class="comparison-cell-note">Verified{{ $r->tested_at?' · '.$r->tested_at->format('M Y'):'' }}</small>
                                            @if($r->source_url && \Illuminate\Support\Str::startsWith($r->source_url,['http://','https://']))
                                                <a class="benchmark-source-link" href="{{ $r->source_url }}" target="_blank" rel="nofollow noopener">Source <i data-lucide="external-link"></i></a>
                                            @endif
                                        @else
                                            <span class="muted">No shared result</span>
                                        @endif
                                    </td>
                                @endforeach
                            </tr>
                            @endif
                        @endforeach
                        </tbody>
                    </table>
                    <div class="comparison-empty-differences" data-no-differences hidden>No differing benchmark rows are visible.</div>
                </div>
            @endforeach
        @else
            <div class="comparison-no-shared-data"><i data-lucide="circle-dashed"></i><div><strong>No exact shared benchmark yet</strong><p>AI Orbit has not found a verified benchmark/version with results for at least two selected items, so no benchmark winner is declared.</p></div></div>
        @endif

        @if($additionalBenchmarkMatrix->isNotEmpty())
            <details class="additional-evidence">
                <summary>Additional one-sided benchmark evidence <span>{{ $additionalBenchmarkMatrix->count() }}</span></summary>
                <p>These verified results provide context but do not count as head-to-head wins because the other selected items do not have the same benchmark result.</p>
                <div class="additional-evidence-list">
                    @foreach($additionalBenchmarkMatrix as $key => $scores)
                        @php($b=$intelligence['benchmarkMeta'][$key]??null)
                        @if($b)
                            @foreach($scores as $itemId => $r)
                                @php($evidenceItem=$items->firstWhere('id',(int)$itemId))
                                <div><span>{{ $evidenceItem?->name ?? 'Item' }}</span><strong>{{ $b->name }}</strong><small>{{ number_format((float)$r->score,2) }}{{ $b->unit==='%'?'%':($b->unit?' '.$b->unit:'') }}</small></div>
                            @endforeach
                        @endif
                    @endforeach
                </div>
            </details>
        @endif
    </div>

    <div class="capability-comparison">
        <div class="table-title"><span><i data-lucide="sparkles"></i></span><div><h2>Capability differences</h2><p>Shared capabilities are separated from capabilities currently cataloged on only one selected item.</p></div></div>
        @if(!empty($capabilityCoverage['shared']))
            <div class="shared-coverage-row"><strong>Shared capabilities</strong><div>@foreach(array_slice($capabilityCoverage['shared'],0,16) as $cap)<span class="data-chip">{{ $cap['label'] }}</span>@endforeach</div></div>
        @endif
        <div class="capability-columns cols-{{ min($items->count(),4) }}">
            @foreach($items as $item)
                @php($uniqueCaps=$capabilityCoverage['unique'][(int)$item->id]??[])
                <div class="capability-column">
                    <div class="capability-column-head"><div class="tiny-logo"><img src="{{ $item->logo_url }}" alt="{{ $item->name }} logo"></div><strong>{{ $item->name }}</strong></div>
                    <small class="coverage-label">DISTINCTIVE CATALOGED CAPABILITIES</small>
                    <div class="capability-chip-list">@forelse(array_slice($uniqueCaps,0,18) as $cap)<span><i data-lucide="plus"></i>{{ $cap }}</span>@empty<span class="muted">No unique capability difference verified.</span>@endforelse</div>
                </div>
            @endforeach
        </div>
    </div>

    <div class="choose-guide">
        <div class="table-title"><span><i data-lucide="route"></i></span><div><h2>Use-case coverage</h2><p>Structured use-case relationships only. Missing relationships are not interpreted as lack of capability.</p></div></div>
        @if(!empty($useCaseCoverage['shared']))
            <div class="shared-coverage-row"><strong>Shared use cases</strong><div>@foreach(array_slice($useCaseCoverage['shared'],0,14) as $useCase)<span class="data-chip">{{ $useCase['label'] }}</span>@endforeach</div></div>
        @endif
        <div class="decision-grid">
            @foreach($items as $item)
                @php($uniqueUseCases=$useCaseCoverage['unique'][(int)$item->id]??[])
                <div class="decision-card">
                    <div class="tiny-logo"><img src="{{ $item->logo_url }}" alt="{{ $item->name }} logo"></div>
                    <div><small>{{ strtoupper($item->name) }}</small><h3>Distinct use-case matches</h3><p>{{ $uniqueUseCases ? implode(' · ', array_slice($uniqueUseCases,0,8)) : 'No unique structured use-case match is verified for this comparison yet.' }}</p></div>
                </div>
            @endforeach
        </div>
    </div>

    <div class="comparison-evidence-panel">
        <div class="table-title"><span><i data-lucide="shield-check"></i></span><div><h2>Evidence & freshness</h2><p>See how much verified benchmark/pricing evidence supports each profile and when it was last refreshed.</p></div></div>
        <div class="comparison-evidence-grid cols-{{ min($items->count(),4) }}">
            @foreach($items as $item)
                @php($e=$itemEvidence[(int)$item->id]??[])
                <article>
                    <header><div class="tiny-logo"><img src="{{ $item->logo_url }}" alt="{{ $item->name }} logo"></div><div><strong>{{ $item->name }}</strong><small class="freshness-badge {{ $e['freshness']??'unverified' }}">{{ $freshnessLabel($e['freshness']??'unverified') }}</small></div></header>
                    <dl>
                        <div><dt>Verified benchmark results</dt><dd>{{ (int)($e['benchmark_count']??0) }}</dd></div>
                        <div><dt>Benchmark evidence</dt><dd>{{ !empty($e['benchmark_latest_at']) ? $e['benchmark_latest_at']->format('M j, Y') : 'Not dated' }}</dd></div>
                        <div><dt>Pricing evidence</dt><dd>{{ !empty($e['pricing_latest_at']) ? $e['pricing_latest_at']->format('M j, Y') : 'Not dated' }}</dd></div>
                        <div><dt>Profile review</dt><dd>{{ !empty($e['profile_latest_at']) ? $e['profile_latest_at']->format('M j, Y') : 'Not dated' }}</dd></div>
                    </dl>
                </article>
            @endforeach
        </div>
        <p class="comparison-evidence-note"><i data-lucide="info"></i> Evidence dates show the latest dated record available to AI Orbit; they do not imply that every field was re-verified on that date.</p>
    </div>

    @if($comparisonIsSeoPair && $comparisonFaq->isNotEmpty())
    <div class="capability-comparison comparison-faq">
        <div class="table-title">
            <span><i data-lucide="circle-help"></i></span>
            <div><h2>Comparison FAQ</h2><p>Quick answers about this comparison.</p></div>
        </div>
        <div class="decision-grid">
            @foreach($comparisonFaq as $faq)
            <article class="decision-card comparison-faq-card">
                    <div>
                        <h3>{{ $faq['question'] }}</h3>
                        <p>{{ $faq['answer'] }}</p>
                    </div>
                </article>
            @endforeach
        </div>
    </div>
    @endif

    @if(!$isPreview && $relatedArticles->isNotEmpty())
    <div class="comparison-guides-block">
        <div class="section-heading-row"><div><span class="section-eyebrow">GUIDES & ANALYSIS</span><h2>Research behind this comparison</h2></div><a href="{{ route('articles.index') }}">All guides <i data-lucide="arrow-right"></i></a></div>
        <div class="comparison-guide-grid">
            @foreach($relatedArticles as $article)
            <a href="{{ route('articles.show',$article) }}"><span>{{ $article->category ?: 'AI Guide' }}@if($article->published_at)<small>{{ $article->published_at->format('M j, Y') }}</small>@endif</span><h3>{{ $article->title }}</h3><p>{{ Str::limit($article->summary,110) }}</p><b>Read analysis <i data-lucide="arrow-right"></i></b></a>
            @endforeach
        </div>
    </div>
    @endif

    @if(!$isPreview && $relatedComparisons->isNotEmpty())
    <div class="related-comparisons">
        <div class="section-heading-row"><div><span class="section-eyebrow">KEEP COMPARING</span><h2>Related comparisons</h2></div><a href="{{ route('comparisons.index') }}">View all <i data-lucide="arrow-right"></i></a></div>
        <div class="related-comparison-grid">
            @foreach($relatedComparisons as $related)
                <a href="{{ route('comparisons.show',$related) }}"><span>{{ ucfirst($related->comparable_type) }}</span><h3>{{ $related->title }}</h3><small>{{ number_format($related->views) }} views</small><i data-lucide="arrow-up-right"></i></a>
            @endforeach
        </div>
    </div>
    @endif
</div>
</section>
@endsection
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const shareButton = document.querySelector('[data-comparison-share]');
    if (shareButton) {
        const label = shareButton.querySelector('[data-comparison-share-label]');
        const originalLabel = label?.textContent || 'Share';
        let resetTimer = null;

        const setFeedback = (text) => {
            if (!label) return;
            label.textContent = text;
            window.clearTimeout(resetTimer);
            resetTimer = window.setTimeout(() => { label.textContent = originalLabel; }, 1800);
        };

        const copyLink = async () => {
            if (navigator.clipboard && window.isSecureContext) {
                await navigator.clipboard.writeText(window.location.href);
                return true;
            }

            const textarea = document.createElement('textarea');
            textarea.value = window.location.href;
            textarea.setAttribute('readonly', '');
            textarea.style.position = 'fixed';
            textarea.style.opacity = '0';
            textarea.style.pointerEvents = 'none';
            document.body.appendChild(textarea);
            textarea.select();
            textarea.setSelectionRange(0, textarea.value.length);
            const copied = document.execCommand('copy');
            textarea.remove();
            return copied;
        };

        shareButton.addEventListener('click', async () => {
            if (shareButton.disabled) return;
            shareButton.disabled = true;
            try {
                const title = document.querySelector('.comparison-detail-hero h1')?.textContent?.trim() || document.title;
                const shareData = { title, text: `Compare ${title} on AI Orbit`, url: window.location.href };

                if (navigator.share) {
                    try {
                        await navigator.share(shareData);
                        setFeedback('Shared');
                        return;
                    } catch (error) {
                        if (error?.name === 'AbortError') return;
                    }
                }

                const copied = await copyLink();
                setFeedback(copied ? 'Link copied' : 'Copy failed');
            } catch (_) {
                setFeedback('Copy failed');
            } finally {
                shareButton.disabled = false;
            }
        });
    }

    const differenceToggle = document.querySelector('[data-differences-toggle]');
    const normalizeCell = (cell) => (cell?.textContent || '')
        .replace(/\s+/g, ' ')
        .trim()
        .toLowerCase();

    const refreshDifferences = () => {
        const onlyDifferences = Boolean(differenceToggle?.checked);
        document.querySelectorAll('[data-difference-scope]').forEach(scope => {
            let visible = 0;
            scope.querySelectorAll('[data-difference-row]').forEach(row => {
                const values = [...row.querySelectorAll('td')].map(normalizeCell);
                const meaningful = values.filter(value => value && value !== 'not verified' && value !== 'not available' && value !== '—');
                const allSame = values.length > 1 && new Set(values).size === 1;
                const noComparableData = meaningful.length === 0;
                const shouldHide = onlyDifferences && (allSame || noComparableData);
                row.hidden = shouldHide;
                if (!shouldHide) visible++;
            });

            const empty = scope.querySelector('[data-no-differences]');
            if (empty) empty.hidden = !onlyDifferences || visible > 0;
        });
    };

    differenceToggle?.addEventListener('change', refreshDifferences);
    refreshDifferences();

    const calculator = document.querySelector('[data-model-cost-calculator]');
    if (calculator) {
        const inputMillions = calculator.querySelector('[data-cost-input-million]');
        const outputMillions = calculator.querySelector('[data-cost-output-million]');
        const cards = [...calculator.querySelectorAll('[data-cost-model]')];

        const formatCurrency = (value) => new Intl.NumberFormat('en-US', {
            style: 'currency',
            currency: 'USD',
            minimumFractionDigits: value < 10 ? 2 : 0,
            maximumFractionDigits: value < 10 ? 2 : 0,
        }).format(value);

        const recalc = () => {
            const input = Math.max(0, Number.parseFloat(inputMillions?.value || '0') || 0);
            const output = Math.max(0, Number.parseFloat(outputMillions?.value || '0') || 0);

            cards.forEach(card => {
                const inputPrice = Number.parseFloat(card.dataset.inputPrice || '');
                const outputPrice = Number.parseFloat(card.dataset.outputPrice || '');
                const target = card.querySelector('[data-cost-output]');
                if (!target) return;

                const inputNeeded = input > 0;
                const outputNeeded = output > 0;
                const inputKnown = Number.isFinite(inputPrice);
                const outputKnown = Number.isFinite(outputPrice);

                if ((inputNeeded && !inputKnown) || (outputNeeded && !outputKnown)) {
                    target.textContent = 'Incomplete pricing';
                    card.classList.add('missing-price');
                    return;
                }

                const total = (inputKnown ? input * inputPrice : 0) + (outputKnown ? output * outputPrice : 0);
                target.textContent = `${formatCurrency(total)} / month`;
                card.classList.remove('missing-price');
            });
        };

        inputMillions?.addEventListener('input', recalc);
        outputMillions?.addEventListener('input', recalc);
        recalc();
    }

    if (window.lucide) window.lucide.createIcons();
});
</script>
@endpush
