@extends('frontend.layouts.app')

@php
    $pricingHasFilters = request()->hasAny([
        'q',
        'type',
        'sort',
    ]);

    $pricingSeoTitle = 'AI Pricing Intelligence — Compare AI Tool Prices | AI Orbit';
    $pricingSeoDescription = 'Track AI tool pricing, compare free and paid plans, API pricing and recent price changes.';
    $pricingCanonical = route('pricing.index');

    $pricingCollectionSchema = [
        '@' . 'context' => 'https://schema.org',
        '@' . 'type' => 'CollectionPage',
        'name' => 'AI Pricing Intelligence',
        'description' => $pricingSeoDescription,
        'url' => $pricingCanonical,
    ];

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
                'item' => $pricingCanonical,
            ],
        ],
    ];
@endphp

@section('title', $pricingSeoTitle)
@section('meta_description', $pricingSeoDescription)
@section('canonical', $pricingCanonical)

@section(
    'robots',
    $pricingHasFilters
        ? 'noindex,follow'
        : 'index,follow,max-image-preview:large,max-snippet:-1,max-video-preview:-1'
)

@push('head')
<script type="application/ld+json">{!! json_encode(
    $pricingCollectionSchema,
    JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
) !!}</script>
<script type="application/ld+json">{!! json_encode(
    $pricingBreadcrumbSchema,
    JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
) !!}</script>
@endpush

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/frontend/pricing-intelligence.css') }}">
@endpush

@section('content')
<section class="pi-hero">
    <div class="pi-wrap">
        <span class="pi-kicker"><i data-lucide="radar"></i> Pricing Intelligence</span>
        <h1>Know what AI really costs.</h1>
        <p>Compare plans, spot free tiers, review API pricing and follow verified pricing changes from one research dashboard.</p>

        <div class="pi-stats">
            <div><b>{{ number_format((int) $stats['tools']) }}</b><span>Tools tracked</span></div>
            <div><b>{{ number_format((int) $stats['plans']) }}</b><span>Plans indexed</span></div>
            <div><b>{{ number_format((int) $stats['free']) }}</b><span>Free plans</span></div>
            <div><b>{{ number_format((int) $stats['changes']) }}</b><span>30-day changes</span></div>
        </div>
    </div>
</section>

<section class="pi-wrap pi-body">
    <form class="pi-toolbar" method="get" action="{{ route('pricing.index') }}">
        <label class="pi-search">
            <i data-lucide="search"></i>
            <input name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Search tool or company...">
        </label>

        <div class="pi-tabs">
            @foreach (['all' => 'All', 'free' => 'Free', 'paid' => 'Paid', 'api' => 'API'] as $key => $label)
                <button type="submit" name="type" value="{{ $key }}" class="{{ $type === $key ? 'active' : '' }}">
                    {{ $label }}
                </button>
            @endforeach
        </div>

        <select name="sort" onchange="this.form.submit()">
            <option value="value" @selected($sort === 'value')>Best value</option>
            <option value="price_low" @selected($sort === 'price_low')>Price: low to high</option>
            <option value="price_high" @selected($sort === 'price_high')>Price: high to low</option>
            <option value="name" @selected($sort === 'name')>Name</option>
            <option value="updated" @selected($sort === 'updated')>Recently updated</option>
        </select>
    </form>

    <div class="pi-layout">
        <main>
            <div class="pi-heading">
                <div>
                    <span>LIVE DIRECTORY</span>
                    <h2>AI pricing comparison</h2>
                </div>
                <small>{{ $tools->count() }} products</small>
            </div>

            <div class="pi-grid">
                @forelse ($tools as $tool)
                    <article class="pi-card">
                        <div class="pi-card-head">
                            <div class="pi-tool">
                                <div class="pi-logo">
                                    <img src="{{ $tool->logo_url }}" alt="{{ $tool->name }} logo">
                                </div>
                                <div>
                                    <h3>{{ $tool->name }}</h3>
                                    <p>{{ $tool->company?->name ?? 'Independent' }}</p>
                                </div>
                            </div>
                            <span class="pi-score">
                                {{ number_format((float) $tool->best_value_score, 1) }}
                                <small>value</small>
                            </span>
                        </div>

                        <div class="pi-price-row">
                            <div>
                                <small>STARTING AT</small>
                                <strong>
                                    @if ($tool->has_free)
                                        Free
                                    @elseif ($tool->lowest_monthly !== null)
                                        ${{ number_format((float) $tool->lowest_monthly, 2) }}<em>/mo</em>
                                    @else
                                        Custom
                                    @endif
                                </strong>
                            </div>

                            <div class="pi-badges">
                                @if ($tool->has_free)
                                    <span>Free tier</span>
                                @endif
                                @if ($tool->has_api)
                                    <span>API</span>
                                @endif
                            </div>
                        </div>

                        <div class="pi-plans">
                            @foreach ($tool->pricingPlans->take(3) as $plan)
                                <div>
                                    <b>{{ $plan->plan_name }}</b>
                                    <span>
                                        @if ($plan->monthly_price !== null)
                                            @if ((float) $plan->monthly_price === 0.0)
                                                Free
                                            @else
                                                ${{ number_format((float) $plan->monthly_price, 2) }}/mo
                                            @endif
                                        @elseif ($plan->api_price_label)
                                            {{ $plan->api_price_label }}
                                        @else
                                            Contact
                                        @endif
                                    </span>
                                </div>
                            @endforeach
                        </div>

                        <div class="pi-card-foot">
                            <span>
                                <i data-lucide="star"></i>
                                {{ number_format((float) ($tool->rating ?? 0), 1) }} rating
                            </span>
                            <a href="{{ route('pricing.show', $tool) }}">
                                Pricing details <i data-lucide="arrow-up-right"></i>
                            </a>
                        </div>
                    </article>
                @empty
                    <div class="pi-empty">
                        <i data-lucide="search-x"></i>
                        <h3>No pricing matches</h3>
                        <p>Try changing the search or pricing filter.</p>
                    </div>
                @endforelse
            </div>
        </main>

        <aside>
            <section class="pi-panel">
                <div class="pi-panel-title">
                    <span><i data-lucide="activity"></i> Recent price intelligence</span>
                    <small>Latest</small>
                </div>

                @forelse ($recentChanges as $change)
                    @php
                        $changeIcon = $change->change_type === 'decrease'
                            ? 'trending-down'
                            : ($change->change_type === 'new_plan' ? 'plus' : 'trending-up');
                    @endphp

                    <div class="pi-change">
                        <div class="pi-change-icon {{ $change->change_type }}">
                            <i data-lucide="{{ $changeIcon }}"></i>
                        </div>
                        <div>
                            <b>{{ $change->tool?->name ?? 'AI Tool' }} · {{ $change->plan_name }}</b>
                            <p>
                                {{ str_replace('_', ' ', ucfirst($change->change_type)) }}
                                @if ($change->old_price !== null && $change->new_price !== null)
                                    · ${{ number_format((float) $change->old_price, 2) }} → ${{ number_format((float) $change->new_price, 2) }}
                                @endif
                            </p>
                            <small>{{ $change->created_at?->diffForHumans() }}</small>
                        </div>
                    </div>
                @empty
                    <div class="pi-panel-empty">No published price changes yet.</div>
                @endforelse
            </section>

            <section class="pi-panel pi-method">
                <span class="pi-panel-title">
                    <span><i data-lucide="shield-check"></i> How pricing works</span>
                </span>
                <p>AI Orbit separates live plan data from detected changes. Automatic detections can be reviewed before they become published pricing history.</p>

                <div>
                    <i data-lucide="scan-search"></i>
                    <span><b>Source monitoring</b><small>Official pricing sources can be tracked.</small></span>
                </div>
                <div>
                    <i data-lucide="badge-check"></i>
                    <span><b>Review workflow</b><small>Detected changes stay separate until approved.</small></span>
                </div>
                <div>
                    <i data-lucide="history"></i>
                    <span><b>Price history</b><small>Published changes build a transparent timeline.</small></span>
                </div>
            </section>
        </aside>
    </div>
</section>
@endsection
