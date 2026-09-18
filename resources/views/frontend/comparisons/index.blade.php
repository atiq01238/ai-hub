@extends('frontend.layouts.app')

@php
    $comparisonsHasFilters = request()->hasAny([
        'type',
        'search',
        'sort',
    ]);

    $comparisonsSeoTitle = 'AI Model Comparison Tool — Compare Models Side by Side | AI Orbit';

    if (!$comparisonsHasFilters && $comparisons->currentPage() > 1) {
        $comparisonsSeoTitle = 'AI Comparisons — Page '
            . $comparisons->currentPage()
            . ' | AI Orbit';
    }

    $comparisonsSeoDescription = "Use AI Orbit's AI model comparison tool to compare AI models and tools side by side across pricing, benchmarks, context, capabilities and product details.";

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
<link rel="stylesheet" href="{{ asset('css/frontend/comparisons.css') }}?v={{ @filemtime(public_path('css/frontend/comparisons.css')) ?: '20260918-compare4' }}">
@endpush
@push('scripts')
<script src="{{ asset('js/frontend/comparisons.js') }}?v={{ @filemtime(public_path('js/frontend/comparisons.js')) ?: '20260918-compare4' }}" defer></script>
@endpush

@section('content')
<section class="comparison-hero">
    <div class="comparison-hero-inner">
        <span class="comparison-kicker"><i data-lucide="scale"></i> Independent AI comparisons</span>
        <h1>Compare AI Models &amp; Tools <span>Side by Side.</span></h1>
        <p>Use AI Orbit's AI model comparison tool to compare models and tools side by side across pricing, benchmarks, capabilities, ratings and verified product details.</p>
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

@php($quickDefaultType = $type === 'tool' ? 'tool' : 'model')
<section class="compare-quick-start" data-quick-compare data-default-type="{{ $quickDefaultType }}">
    <div class="compare-container">
        <div class="quick-compare-shell">
            <div class="quick-compare-copy">
                <span class="section-eyebrow">QUICK COMPARE</span>
                <h2>Start a side-by-side comparison in seconds</h2>
                <p>Choose two AI models or tools here. Use the full builder when you want to compare three or four items.</p>
                <div class="quick-type-tabs" role="tablist" aria-label="Comparison type">
                    <button type="button" class="{{ $quickDefaultType === 'model' ? 'active' : '' }}" data-quick-type="model"><i data-lucide="cpu"></i> AI Models</button>
                    <button type="button" class="{{ $quickDefaultType === 'tool' ? 'active' : '' }}" data-quick-type="tool"><i data-lucide="bot"></i> AI Tools</button>
                </div>
            </div>

            <form class="quick-compare-form" method="get" action="{{ route('comparisons.preview') }}" data-quick-compare-form>
                <input type="hidden" name="type" value="{{ $quickDefaultType }}" data-quick-form-type>

                <div class="quick-compare-panel {{ $quickDefaultType === 'model' ? '' : 'hidden' }}" data-quick-panel="model">
                    <label><span>First model</span><select name="items[]" data-quick-select @disabled($quickDefaultType !== 'model')><option value="">Choose an AI model</option>@foreach($quickModels as $model)<option value="{{ $model->id }}">{{ $model->name }}{{ $model->company ? ' · '.$model->company->name : '' }}</option>@endforeach</select></label>
                    <button type="button" class="quick-swap-btn" data-quick-swap aria-label="Swap selected models"><i data-lucide="arrow-left-right"></i></button>
                    <label><span>Second model</span><select name="items[]" data-quick-select @disabled($quickDefaultType !== 'model')><option value="">Choose another model</option>@foreach($quickModels as $model)<option value="{{ $model->id }}">{{ $model->name }}{{ $model->company ? ' · '.$model->company->name : '' }}</option>@endforeach</select></label>
                </div>

                <div class="quick-compare-panel {{ $quickDefaultType === 'tool' ? '' : 'hidden' }}" data-quick-panel="tool">
                    <label><span>First tool</span><select name="items[]" data-quick-select @disabled($quickDefaultType !== 'tool')><option value="">Choose an AI tool</option>@foreach($quickTools as $tool)<option value="{{ $tool->id }}">{{ $tool->name }}{{ $tool->company ? ' · '.$tool->company->name : '' }}</option>@endforeach</select></label>
                    <button type="button" class="quick-swap-btn" data-quick-swap aria-label="Swap selected tools"><i data-lucide="arrow-left-right"></i></button>
                    <label><span>Second tool</span><select name="items[]" data-quick-select @disabled($quickDefaultType !== 'tool')><option value="">Choose another tool</option>@foreach($quickTools as $tool)<option value="{{ $tool->id }}">{{ $tool->name }}{{ $tool->company ? ' · '.$tool->company->name : '' }}</option>@endforeach</select></label>
                </div>

                <div class="quick-compare-actions">
                    <button type="submit" class="primary-compare-btn" data-quick-submit disabled><i data-lucide="git-compare-arrows"></i> Compare now</button>
                    <a href="{{ route('comparisons.builder', ['type' => $quickDefaultType]) }}" data-quick-builder-link data-builder-base="{{ route('comparisons.builder') }}">Compare 2–4 items <i data-lucide="arrow-right"></i></a>
                </div>
                <p class="quick-compare-status" data-quick-status>Select two different items to continue.</p>
            </form>
        </div>

        @auth
            @if($personalStats)
            <div class="comparison-research-strip">
                <div><span><i data-lucide="bookmark-check"></i></span><div><strong>Your comparison research</strong><small>Pick up where you left off across saved and recently viewed comparisons.</small></div></div>
                <nav>
                    <a href="{{ route('user.comparisons.index') }}"><b>{{ number_format($personalStats['saved']) }}</b> Saved</a>
                    <a href="{{ route('user.comparisons.history') }}"><b>{{ number_format($personalStats['history']) }}</b> History</a>
                </nav>
            </div>
            @endif
        @endauth
    </div>
</section>

@if(!$comparisonsHasFilters && $comparisons->currentPage() === 1)
<section class="compare-section" aria-labelledby="compare-models-guide">
    <div class="compare-container">
        <div class="section-heading-row">
            <div>
                <span class="section-eyebrow">MODEL COMPARISON GUIDE</span>
                <h2 id="compare-models-guide">How to compare AI models side by side</h2>
                <p>Start with the factors that change a real buying or deployment decision: model capability, benchmark evidence, context window, pricing and provider details.</p>
            </div>
        </div>
        <p>Use the comparison library below for direct matchups, then cross-check the <a href="{{ route('models.index') }}">AI models directory</a>, <a href="{{ route('benchmarks.index') }}">AI model benchmarks</a> and <a href="{{ route('pricing.index') }}">AI pricing intelligence</a> when you need deeper evidence before choosing a model or tool.</p>
    </div>
</section>
@endif

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

@if(!$comparisonsHasFilters && $comparisons->currentPage() === 1 && $recent->isNotEmpty())
<section class="compare-section compare-recent-section">
    <div class="compare-container">
        <div class="section-heading-row">
            <div><span class="section-eyebrow">RECENTLY UPDATED</span><h2>Fresh comparison research</h2><p>Curated pair pages with the most recent editorial or verification activity.</p></div>
            <a href="{{ route('comparisons.index', ['sort' => 'newest']) }}">Browse newest <i data-lucide="arrow-right"></i></a>
        </div>
        <div class="recent-comparison-grid">
            @foreach($recent as $entry)
                @php($resolved = $entry->getRelation('resolved_items'))
                <a class="recent-comparison-card" href="{{ route('comparisons.show', $entry) }}">
                    <div class="recent-comparison-logos">
                        @foreach($resolved->take(2) as $idx => $item)
                            @if($idx > 0)<span>VS</span>@endif
                            <img src="{{ $item->logo_url }}" alt="{{ $item->name }} logo">
                        @endforeach
                    </div>
                    <div><span class="type-pill {{ $entry->comparable_type }}">{{ ucfirst($entry->comparable_type) }}</span><h3>{{ $entry->title }}</h3><p>{{ $entry->last_verified_at ? 'Verified '.$entry->last_verified_at->format('M j, Y') : ($entry->updated_at ? 'Updated '.$entry->updated_at->format('M j, Y') : 'Recently updated') }}</p></div>
                    <i data-lucide="arrow-up-right"></i>
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
