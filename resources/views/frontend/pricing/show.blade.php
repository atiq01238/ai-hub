@extends('frontend.layouts.app')

@php
    $pricingDetailCanonical = route('pricing.show', $tool);
    $pricingDetailTitle = $tool->name . ' Pricing and Plans | AI Orbit';
    $pricingDetailDescription = \Illuminate\Support\Str::limit(
        'Compare ' . $tool->name . ' pricing, plans, limits, API rates and published price history on AI Orbit.',
        158,
        ''
    );
    $pricingSchemaImage = $tool->logo_url;
    if (!\Illuminate\Support\Str::startsWith($pricingSchemaImage, ['http://', 'https://'])) {
        $pricingSchemaImage = url('/' . ltrim($pricingSchemaImage, '/'));
    }

    $pricingPageSchema = [
        '@' . 'context' => 'https://schema.org',
        '@' . 'type' => 'WebPage',
        'name' => $tool->name . ' Pricing and Plans',
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
@endphp

@section('title', $pricingDetailTitle)
@section('meta_description', $pricingDetailDescription)
@section('canonical', $pricingDetailCanonical)
@section('og_image', $pricingSchemaImage)
@section('robots', 'index,follow,max-image-preview:large,max-snippet:-1,max-video-preview:-1')

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
    <link rel="stylesheet" href="{{ asset('css/frontend/pricing-intelligence.css') }}">
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

            <div>
                <span class="pi-kicker">{{ $tool->company?->name ?? 'AI Tool' }}</span>
                <h1>{{ $tool->name }} pricing</h1>
                <p>Plans, limits, API pricing and published price history.</p>
            </div>

            <a class="pi-primary" href="{{ route('tools.show', $tool) }}">
                View tool profile <i data-lucide="arrow-up-right"></i>
            </a>
        </div>
    </div>
</section>

<section class="pi-wrap pi-detail-body">
    <div class="pi-heading">
        <div>
            <span>PLANS</span>
            <h2>Available pricing</h2>
        </div>
        <small>{{ $tool->pricingPlans->count() }} plans</small>
    </div>

    <div class="pi-plan-grid">
        @forelse ($tool->pricingPlans as $plan)
            <article class="pi-plan">
                <span class="pi-plan-label">{{ $plan->plan_name }}</span>

                <div class="pi-plan-price">
                    @if ($plan->monthly_price !== null)
                        @if ((float) $plan->monthly_price === 0.0)
                            <strong>Free</strong>
                        @else
                            <strong>${{ number_format((float) $plan->monthly_price, 2) }}</strong>
                            <small>/month</small>
                        @endif
                    @else
                        <strong>Custom</strong>
                    @endif
                </div>

                @if ($plan->yearly_price !== null)
                    <p><i data-lucide="calendar-days"></i> ${{ number_format((float) $plan->yearly_price, 2) }} / year</p>
                @endif

                @if ($plan->api_price_label)
                    <p><i data-lucide="braces"></i> {{ $plan->api_price_label }}</p>
                @endif

                @if ($plan->credits)
                    <p><i data-lucide="coins"></i> {{ $plan->credits }}</p>
                @endif

                @if ($plan->limits)
                    <p><i data-lucide="gauge"></i> {{ $plan->limits }}</p>
                @endif

                <footer>
                    <span>
                        <i data-lucide="database"></i>
                        {{ $plan->sources->where('enabled', true)->count() }} monitored sources
                    </span>
                </footer>
            </article>
        @empty
            <div class="pi-empty">
                <h3>No plans published</h3>
            </div>
        @endforelse
    </div>

    <div class="pi-detail-grid">
        <section class="pi-panel">
            <div class="pi-panel-title">
                <span><i data-lucide="history"></i> Published pricing history</span>
            </div>

            @forelse ($history as $change)
                @php
                    $oldDisplay = $change->old_value;
                    if ($oldDisplay === null && $change->old_price !== null) {
                        $oldDisplay = '$' . number_format((float) $change->old_price, 2);
                    }

                    $newDisplay = $change->new_value;
                    if ($newDisplay === null && $change->new_price !== null) {
                        $newDisplay = '$' . number_format((float) $change->new_price, 2);
                    }
                @endphp

                <div class="pi-history">
                    <span class="pi-change-icon {{ $change->change_type }}">
                        <i data-lucide="{{ $change->change_type === 'decrease' ? 'trending-down' : 'trending-up' }}"></i>
                    </span>
                    <div>
                        <b>{{ $change->plan_name }} · {{ str_replace('_', ' ', ucfirst($change->change_type)) }}</b>
                        <p>{{ $oldDisplay ?? '—' }} → {{ $newDisplay ?? '—' }}</p>
                    </div>
                    <small>{{ $change->created_at?->format('M j, Y') }}</small>
                </div>
            @empty
                <div class="pi-panel-empty">No published pricing changes for this tool yet.</div>
            @endforelse
        </section>

        <section class="pi-panel">
            <div class="pi-panel-title">
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
                            {{ $alt->pricingPlans->count() }} plans · {{ number_format((float) ($alt->rating ?? 0), 1) }} rating
                        </small>
                    </div>
                    <i data-lucide="chevron-right"></i>
                </a>
            @empty
                <div class="pi-panel-empty">No pricing alternatives available yet.</div>
            @endforelse
        </section>
    </div>
</section>
@endsection
