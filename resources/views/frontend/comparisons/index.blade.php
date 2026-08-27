@extends('frontend.layouts.app')

@php
    $comparisonsHasFilters = request()->hasAny([
        'type',
        'search',
        'sort',
    ]);

    $comparisonsSeoTitle = 'AI Comparisons — Compare AI Tools and Models | AI Orbit';

    if (!$comparisonsHasFilters && $comparisons->currentPage() > 1) {
        $comparisonsSeoTitle = 'AI Comparisons — Page '
            . $comparisons->currentPage()
            . ' | AI Orbit';
    }

    $comparisonsSeoDescription = 'Compare leading AI tools and models side by side using pricing, capabilities, benchmark scores, ratings and practical product data.';

    $comparisonsCanonical = route('comparisons.index');

    if (!$comparisonsHasFilters && $comparisons->currentPage() > 1) {
        $comparisonsCanonical = $comparisons->url($comparisons->currentPage());
    }

    $comparisonsCollectionSchema = [
        '@' . 'context' => 'https://schema.org',
        '@' . 'type' => 'CollectionPage',
        'name' => 'AI Comparisons',
        'description' => $comparisonsSeoDescription,
        'url' => $comparisonsCanonical,
    ];

    $comparisonsBreadcrumbSchema = [
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
        ],
    ];
@endphp

@section('title', $comparisonsSeoTitle)
@section('meta_description', $comparisonsSeoDescription)
@section('canonical', $comparisonsCanonical)

@section(
    'robots',
    $comparisonsHasFilters
        ? 'noindex,follow'
        : 'index,follow,max-image-preview:large,max-snippet:-1,max-video-preview:-1'
)

@push('head')
<script type="application/ld+json">{!! json_encode(
    $comparisonsCollectionSchema,
    JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
) !!}</script>
<script type="application/ld+json">{!! json_encode(
    $comparisonsBreadcrumbSchema,
    JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
) !!}</script>
@endpush

@push('styles')
<link rel="stylesheet" href="{{ asset('css/frontend/comparisons.css') }}">
@endpush

@section('content')
<section class="comparison-hero">
    <div class="comparison-hero-inner">
        <span class="comparison-kicker"><i data-lucide="scale"></i> Independent AI comparisons</span>
        <h1>Compare AI products with <span>useful data.</span></h1>
        <p>Side-by-side research for AI tools and models — benchmarks, pricing, capabilities, ratings and the details that actually matter.</p>
        <div class="comparison-hero-actions">
            <a class="primary-compare-btn" href="{{ route('comparisons.builder') }}"><i data-lucide="git-compare-arrows"></i> Build a comparison</a>
            <a class="secondary-compare-btn" href="#comparison-library"><i data-lucide="library"></i> Browse comparisons</a>
        </div>
        <div class="comparison-stats">
            <div><strong>{{ number_format($stats['published']) }}</strong><span>Published comparisons</span></div>
            <div><strong>{{ number_format($stats['tool']) }}</strong><span>Tool matchups</span></div>
            <div><strong>{{ number_format($stats['model']) }}</strong><span>Model matchups</span></div>
            <div><strong>{{ number_format($stats['views']) }}</strong><span>Comparison views</span></div>
        </div>
    </div>
</section>

@if($featured->isNotEmpty())
<section class="compare-section compare-featured-section">
    <div class="compare-container">
        <div class="section-heading-row">
            <div><span class="section-eyebrow">MOST VIEWED</span><h2>Popular comparisons</h2><p>Start with the matchups readers are checking most.</p></div>
            <a href="{{ route('comparisons.builder') }}">Compare your own <i data-lucide="arrow-right"></i></a>
        </div>
        <div class="featured-comparison-grid">
            @foreach($featured as $entry)
                @php($resolved = $entry->getRelation('resolved_items'))
                <a class="featured-comparison-card" href="{{ route('comparisons.show', $entry) }}">
                    <div class="featured-card-top"><span class="type-pill {{ $entry->comparable_type }}">{{ ucfirst($entry->comparable_type) }} comparison</span><span><i data-lucide="eye"></i>{{ number_format($entry->views) }}</span></div>
                    <div class="comparison-logos">
                        @foreach($resolved->take(3) as $idx => $item)
                            @if($idx > 0)<span class="vs-dot">VS</span>@endif
                            <span class="compare-logo-wrap">
                                <img src="{{ $item->logo_url }}" alt="{{ $item->name }} logo">
                            </span>
                        @endforeach
                    </div>
                    <h3>{{ $entry->title }}</h3>
                    <p>{{ $resolved->pluck('name')->join(' vs ') }} — compare key strengths, pricing and performance.</p>
                    <span class="card-link">View comparison <i data-lucide="arrow-up-right"></i></span>
                </a>
            @endforeach
        </div>
    </div>
</section>
@endif

<section class="compare-section" id="comparison-library">
    <div class="compare-container">
        <div class="section-heading-row library-heading">
            <div><span class="section-eyebrow">COMPARISON LIBRARY</span><h2>Explore every matchup</h2><p>{{ number_format($comparisons->total()) }} comparisons match your current filters.</p></div>
        </div>

        <form class="compare-filterbar" method="get" action="{{ route('comparisons.index') }}">
            <label class="compare-search"><i data-lucide="search"></i><input name="search" value="{{ $search }}" placeholder="Search ChatGPT vs Claude, Gemini, Midjourney..."></label>
            <div class="compare-filter-tabs">
                <a class="{{ !$type ? 'active' : '' }}" href="{{ route('comparisons.index', array_filter(['search'=>$search,'sort'=>$sort])) }}">All</a>
                <a class="{{ $type === 'tool' ? 'active' : '' }}" href="{{ route('comparisons.index', array_filter(['type'=>'tool','search'=>$search,'sort'=>$sort])) }}">AI Tools</a>
                <a class="{{ $type === 'model' ? 'active' : '' }}" href="{{ route('comparisons.index', array_filter(['type'=>'model','search'=>$search,'sort'=>$sort])) }}">AI Models</a>
            </div>
            <select name="sort" onchange="this.form.submit()">
                <option value="popular" @selected($sort==='popular')>Most popular</option>
                <option value="newest" @selected($sort==='newest')>Newest</option>
                <option value="az" @selected($sort==='az')>A–Z</option>
            </select>
            <button type="submit">Search</button>
        </form>

        @if($comparisons->isNotEmpty())
            <div class="comparison-library-grid">
                @foreach($comparisons as $entry)
                    @php($resolved = $entry->getRelation('resolved_items'))
                    <article class="library-comparison-card">
                        <div class="library-card-head"><span class="type-pill {{ $entry->comparable_type }}">{{ ucfirst($entry->comparable_type) }}</span><span><i data-lucide="eye"></i>{{ number_format($entry->views) }}</span></div>
                        <div class="mini-product-row">
                            @foreach($resolved->take(4) as $item)
                                <div class="mini-product" title="{{ $item->name }}">
                                    <img src="{{ $item->logo_url }}" alt="{{ $item->name }} logo">
                                </div>
                            @endforeach
                        </div>
                        <h3><a href="{{ route('comparisons.show', $entry) }}">{{ $entry->title }}</a></h3>
                        <p>{{ $resolved->pluck('name')->join(' vs ') }}</p>
                        <div class="library-card-bottom"><span>{{ $resolved->count() }} products</span><a href="{{ route('comparisons.show', $entry) }}">Compare now <i data-lucide="arrow-right"></i></a></div>
                    </article>
                @endforeach
            </div>

            @if($comparisons->hasPages())
                <nav class="compare-pagination" aria-label="Comparison pagination">
                    @if($comparisons->onFirstPage())<span class="disabled">Previous</span>@else<a href="{{ $comparisons->previousPageUrl() }}">Previous</a>@endif
                    <div class="page-numbers">
                        @foreach(range(1, $comparisons->lastPage()) as $page)
                            @if($page === $comparisons->currentPage())<span class="active">{{ $page }}</span>@else<a href="{{ $comparisons->url($page) }}">{{ $page }}</a>@endif
                        @endforeach
                    </div>
                    @if($comparisons->hasMorePages())<a href="{{ $comparisons->nextPageUrl() }}">Next</a>@else<span class="disabled">Next</span>@endif
                </nav>
            @endif
        @else
            <div class="compare-empty"><i data-lucide="search-x"></i><h3>No comparisons found</h3><p>Try a broader search or build a new side-by-side comparison.</p><a href="{{ route('comparisons.builder') }}">Build comparison</a></div>
        @endif
    </div>
</section>
@endsection
