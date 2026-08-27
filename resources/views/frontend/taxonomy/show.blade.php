@extends('frontend.layouts.app')

@php
    $isFeature = $kind === 'feature';

    $routeName = $isFeature
        ? 'features.show'
        : 'use-cases.show';

    $indexRoute = $isFeature
        ? 'features.index'
        : 'use-cases.index';

    $label = $isFeature
        ? 'AI Capability'
        : 'AI Use Case';

    $termUrl = route($routeName, $term);
    $termHasExtraQuery = collect(request()->query())
        ->except('page')
        ->isNotEmpty();

    $termCanonical = $termUrl;

    if (!$termHasExtraQuery && $tools->currentPage() > 1) {
        $termCanonical = $tools->url($tools->currentPage());
    }

    /*
    |--------------------------------------------------------------------------
    | SEO Title
    |--------------------------------------------------------------------------
    */

    $termSeoTitle = $term->meta_title
        ?: $term->name . ' AI Tools & Models | AI Orbit';

    // Remove old AI Hub branding from saved meta titles.
    $termSeoTitle = str_ireplace(
        'AI Hub',
        'AI Orbit',
        $termSeoTitle
    );

    // Fully normalize previously encoded HTML entities.
    for ($i = 0; $i < 5; $i++) {
        $decoded = html_entity_decode(
            $termSeoTitle,
            ENT_QUOTES | ENT_HTML5,
            'UTF-8'
        );
        if ($decoded === $termSeoTitle) {
            break;
        }
        $termSeoTitle = $decoded;
    }

    /*
    |--------------------------------------------------------------------------
    | SEO Description
    |--------------------------------------------------------------------------
    */

    $termSeoDescription = $term->meta_description
        ?: $term->short_description
        ?: ($isFeature
            ? 'Discover AI tools and models with ' . $term->name . ' capabilities on AI Orbit.'
            : 'Discover AI tools and models for ' . $term->name . ' workflows on AI Orbit.');

    for ($i = 0; $i < 5; $i++) {
        $decoded = html_entity_decode(
            $termSeoDescription,
            ENT_QUOTES | ENT_HTML5,
            'UTF-8'
        );
        if ($decoded === $termSeoDescription) {
            break;
        }
        $termSeoDescription = $decoded;
    }
    $termSeoDescription = trim(strip_tags($termSeoDescription));

    /*
    |--------------------------------------------------------------------------
    | Collection Schema
    |--------------------------------------------------------------------------
    */

    $termSchema = [
        '@' . 'context' => 'https://schema.org',
        '@' . 'type' => 'CollectionPage',
        'name' => $term->name,
        'description' => $termSeoDescription,
        'url' => $termCanonical,
    ];

    /*
    |--------------------------------------------------------------------------
    | Breadcrumb Schema
    |--------------------------------------------------------------------------
    */

    $termBreadcrumbSchema = [
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
                'name' => $isFeature ? 'AI Features' : 'AI Use Cases',
                'item' => route($indexRoute),
            ],
            [
                '@' . 'type' => 'ListItem',
                'position' => 3,
                'name' => $term->name,
                'item' => $termUrl,
            ],
        ],
    ];
@endphp

@if(!$termHasExtraQuery && $tools->currentPage() > 1)
    @php
        $termSeoTitle = $term->name
            . ' — Page '
            . $tools->currentPage()
            . ' | AI Orbit';
    @endphp
@endif

@section('title', $termSeoTitle)
@section('meta_description', $termSeoDescription)
@section('canonical', $termCanonical)

@section(
    'robots',
    $termHasExtraQuery
        ? 'noindex,follow'
        : (($tools->total() + $models->count()) > 0
            ? 'index,follow,max-image-preview:large,max-snippet:-1,max-video-preview:-1'
            : 'noindex,follow')
)

@push('head')
<script type="application/ld+json">{!! json_encode(
    $termSchema,
    JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
) !!}</script>

<script type="application/ld+json">{!! json_encode(
    $termBreadcrumbSchema,
    JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
) !!}</script>
@endpush

@push('styles')
<link rel="stylesheet" href="{{ asset('css/frontend/discovery.css') }}">
@endpush
@section('content')
<section class="category-detail-hero"><div class="category-detail-inner"><div class="category-hero-main"><a class="breadcrumb-link" href="{{ route($indexRoute) }}"><i data-lucide="arrow-left"></i> {{ $isFeature ? 'All Features':'All Use Cases' }}</a><span class="eyebrow"><i data-lucide="{{ $term->icon ?: ($isFeature?'sparkles':'target') }}"></i> {{ $label }}</span><h1>{{ $term->name }}</h1><p>{{ $term->description ?: $term->short_description }}</p><div class="category-hero-actions"><a class="primary-action" href="#matching-tools">Explore tools <i data-lucide="arrow-down"></i></a><a class="secondary-action" href="{{ route('search.index',['q'=>$term->name]) }}">Search AI Orbit <i data-lucide="search"></i></a></div></div><div class="category-stat-board"><span><strong>{{ number_format($tools->total()) }}</strong><small>Published tools</small></span><span><strong>{{ number_format($models->count()) }}</strong><small>Related models</small></span></div></div></section>
<div class="discovery-page category-detail-page">
    <section id="matching-tools" class="result-section"><div class="section-bar"><div><span class="section-icon"><i data-lucide="bot"></i></span><h2>AI tools for {{ $term->name }}</h2><small>Structured taxonomy matches</small></div></div>
        @if($tools->count())<div class="category-tool-grid">@foreach($tools as $tool)<article class="category-tool-card"><div class="tool-card-head"><img src="{{ $tool->logo_url }}" alt="{{ $tool->name }} logo"><div><small>{{ $tool->category?->name ?? 'AI Tool' }}</small><h3><a href="{{ route('tools.show',$tool) }}">{{ $tool->name }}</a></h3></div><span class="rating-pill"><i data-lucide="star"></i>{{ number_format((float)$tool->rating,1) }}</span></div><p>{{ \Illuminate\Support\Str::limit($tool->short_description ?: $tool->description,125) }}</p><div class="tool-card-foot"><span>{{ $tool->company?->name ?? 'Independent' }}</span><a href="{{ route('tools.show',$tool) }}">View tool <i data-lucide="arrow-right"></i></a></div></article>@endforeach</div><div class="category-pagination">{{ $tools->links() }}</div>@else<div class="inline-empty"><i data-lucide="package-open"></i><div><h3>No published tools yet</h3><p>This taxonomy page is ready, but does not yet have enough connected products.</p></div></div>@endif
    </section>
    @if($models->isNotEmpty())<section class="result-section"><div class="section-bar"><div><span class="section-icon cyan"><i data-lucide="cpu"></i></span><h2>Related AI Models</h2><small>Models explicitly mapped to this {{ strtolower($label) }}</small></div></div><div class="category-model-grid">@foreach($models as $model)<a class="category-model-card" href="{{ route('models.show',$model) }}"><img src="{{ $model->logo_url }}" alt="{{ $model->name }}"><div><small>{{ $model->company?->name }}</small><h3>{{ $model->name }}</h3><span>{{ $model->context_window ?: 'Context not listed' }}</span></div><b>{{ $model->benchmark_score !== null ? number_format((float)$model->benchmark_score,1) : '—' }}</b></a>@endforeach</div></section>@endif
</div>
@endsection
