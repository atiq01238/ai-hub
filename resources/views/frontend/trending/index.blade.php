@extends('frontend.layouts.app')

@php
    $trendingHasFilters = request()->hasAny(['tab']);
    $trendingSeoTitle = 'Trending AI — Tools, Models, News and Comparisons | AI Orbit';
    $trendingSeoDescription = 'Explore high-interest AI tools, leading models, important AI news, companies and popular comparisons across AI Orbit.';
    $trendingCanonical = route('trending.index');

    $trendingCollectionSchema = [
        '@' . 'context' => 'https://schema.org',
        '@' . 'type' => 'CollectionPage',
        'name' => 'Trending AI',
        'description' => $trendingSeoDescription,
        'url' => $trendingCanonical,
    ];

    $trendingBreadcrumbSchema = [
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
                'name' => 'Trending AI',
                'item' => $trendingCanonical,
            ],
        ],
    ];
@endphp

@section('title', $trendingSeoTitle)
@section('meta_description', $trendingSeoDescription)
@section('canonical', $trendingCanonical)

@section(
    'robots',
    $trendingHasFilters
        ? 'noindex,follow'
        : 'index,follow,max-image-preview:large,max-snippet:-1,max-video-preview:-1'
)

@push('head')
<script type="application/ld+json">{!! json_encode(
    $trendingCollectionSchema,
    JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
) !!}</script>
<script type="application/ld+json">{!! json_encode(
    $trendingBreadcrumbSchema,
    JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
) !!}</script>
@endpush

@push('styles')
<link rel="stylesheet" href="{{ asset('css/frontend/intelligence.css') }}">
@endpush

@section('content')
<section class="intel-hero trending-hero">
    <div class="intel-grid-bg"></div>
    <div class="intel-hero-inner">
        <span class="intel-eyebrow"><i data-lucide="flame"></i> AI discovery pulse</span>
        <h1>What’s <span>Trending in AI</span></h1>
        <p>A fast view of the products, models, stories, companies and comparisons attracting the most attention across the AI Orbit dataset.</p>
        <div class="intel-stat-row"><div><strong>{{ number_format($stats['tools']) }}</strong><span>Published tools</span></div><div><strong>{{ number_format($stats['models']) }}</strong><span>Active models</span></div><div><strong>{{ number_format($stats['news_today']) }}</strong><span>News / 24h</span></div><div><strong>{{ number_format($stats['comparisons']) }}</strong><span>Comparisons</span></div></div>
    </div>
</section>

<div class="intel-page trending-page">
    <nav class="trend-tabs" aria-label="Trending sections">
        @foreach(['all'=>'All','tools'=>'AI Tools','models'=>'AI Models','news'=>'AI News','companies'=>'Companies','comparisons'=>'Comparisons'] as $value=>$label)
            <a class="{{ $tab===$value?'active':'' }}" href="{{ route('trending.index',['tab'=>$value]) }}">{{ $label }}</a>
        @endforeach
    </nav>

    @if(in_array($tab,['all','tools']))
    <section class="intel-section trend-tools-section">
        <div class="intel-section-head"><div><span class="intel-kicker"><i data-lucide="flame"></i> High-interest products</span><h2>Trending AI Tools</h2><p>Ranked by the popularity signal already tracked in the AI Orbit tool dataset, with rating as a secondary signal.</p></div><a href="{{ route('tools.index',['sort'=>'popular']) }}">Explore tools <i data-lucide="arrow-right"></i></a></div>
        <div class="trend-tool-grid">
            @foreach($tools->take($tab==='tools'?12:8) as $rank=>$tool)
                <a class="trend-tool-card" href="{{ route('tools.show',$tool) }}"><span class="trend-rank">#{{ $rank+1 }}</span><img src="{{ $tool->logo_url }}" alt="{{ $tool->name }} logo"><div><small>{{ $tool->category?->name ?? $tool->company?->name ?? 'AI Tool' }}</small><h3>{{ $tool->name }}</h3><p>{{ \Illuminate\Support\Str::limit($tool->short_description ?: $tool->description,76) }}</p><span class="trend-meta"><i data-lucide="star"></i>{{ number_format((float)$tool->rating,1) }} <b>•</b> Popularity {{ number_format((int)$tool->popularity) }}</span></div><i data-lucide="arrow-up-right" class="card-arrow"></i></a>
            @endforeach
        </div>
    </section>
    @endif

    @if(in_array($tab,['all','models']))
    <section class="intel-section">
        <div class="intel-section-head"><div><span class="intel-kicker cyan"><i data-lucide="cpu"></i> Leading model performance</span><h2>Models to Watch</h2><p>Active and preview models surfaced by benchmark score and release recency.</p></div><a href="{{ route('benchmarks.index',['type'=>'models']) }}">Model benchmarks <i data-lucide="bar-chart-3"></i></a></div>
        <div class="trend-model-grid">
            @foreach($models->take($tab==='models'?12:6) as $rank=>$model)
                <a class="trend-model-card" href="{{ route('models.show',$model) }}"><div class="trend-model-top"><span>#{{ $rank+1 }}</span><img src="{{ $model->logo_url }}" alt="{{ $model->name }} logo"><div><small>{{ $model->company?->name ?? 'AI Model' }}</small><h3>{{ $model->name }}</h3><p>{{ $model->version ?: ucfirst($model->status) }}</p></div></div><div class="trend-model-stats"><span>Benchmark<strong>{{ $model->benchmark_score !== null ? number_format((float)$model->benchmark_score,1) : '—' }}</strong></span><span>Context<strong>{{ $model->context_window ?: '—' }}</strong></span><span>Input / 1M<strong>{{ $model->input_price_per_million !== null ? '$'.number_format((float)$model->input_price_per_million,2) : 'Not verified' }}</strong></span></div></a>
            @endforeach
        </div>
    </section>
    @endif

    @if(in_array($tab,['all','news']))
    <section class="intel-section">
        <div class="intel-section-head"><div><span class="intel-kicker red"><i data-lucide="radio"></i> Important now</span><h2>Trending AI News</h2><p>Published, non-duplicate stories ranked by editorial importance and recency.</p></div><a href="{{ route('news.index',['sort'=>'importance']) }}">News intelligence <i data-lucide="arrow-right"></i></a></div>
        <div class="trend-news-grid">
            @foreach($news->take($tab==='news'?10:6) as $rank=>$item)
                <article class="trend-news-card"><a class="trend-news-image" href="{{ route('news.show',$item) }}"><img src="{{ $item->image_url ?: '/images/frontend/content-placeholder.svg' }}" alt="{{ $item->headline }}" onerror="this.style.display='none'"><span>{{ $item->category ?: 'AI News' }}</span></a><div><small>#{{ $rank+1 }} · {{ $item->company?->name ?? $item->source ?? 'AI Orbit' }} · {{ optional($item->published_at)->diffForHumans() }}</small><h3><a href="{{ route('news.show',$item) }}">{{ $item->headline }}</a></h3><p>{{ \Illuminate\Support\Str::limit($item->summary ?: $item->ai_summary,125) }}</p><footer><span><i data-lucide="signal-high"></i> Importance {{ (int)$item->importance }}/100</span>@if($item->verification_status==='verified')<span class="verified-pill"><i data-lucide="badge-check"></i>Verified</span>@endif</footer></div></article>
            @endforeach
        </div>
    </section>
    @endif

    @if(in_array($tab,['all','companies']))
    <section class="intel-section">
        <div class="intel-section-head"><div><span class="intel-kicker green"><i data-lucide="building-2"></i> Ecosystem momentum</span><h2>AI Companies to Watch</h2><p>Companies ranked from their active tools, models and published news footprint inside AI Orbit.</p></div><a href="{{ route('companies.index') }}">Company directory <i data-lucide="arrow-right"></i></a></div>
        <div class="trend-company-grid">
            @foreach($companies->take($tab==='companies'?10:6) as $rank=>$company)
                <a class="trend-company-card" href="{{ route('companies.show',$company) }}"><b>#{{ $rank+1 }}</b><img src="{{ $company->logo_url }}" alt="{{ $company->name }} logo"><div><h3>{{ $company->name }}</h3><p>{{ \Illuminate\Support\Str::limit($company->description,90) }}</p><span>{{ $company->tools_count }} tools · {{ $company->models_count }} models · {{ $company->news_items_count }} stories</span></div><i data-lucide="arrow-up-right"></i></a>
            @endforeach
        </div>
    </section>
    @endif

    @if(in_array($tab,['all','comparisons']))
    <section class="intel-section">
        <div class="intel-section-head"><div><span class="intel-kicker gold"><i data-lucide="scale"></i> Popular head-to-heads</span><h2>Trending Comparisons</h2><p>Published comparisons ranked by recorded AI Orbit views.</p></div><a href="{{ route('comparisons.builder') }}">Build a comparison <i data-lucide="plus"></i></a></div>
        <div class="trend-comparison-grid">
            @foreach($comparisons as $comparison)
                <a class="trend-comparison-card" href="{{ route('comparisons.show',$comparison) }}"><div class="comparison-stack">@foreach($comparison->resolved_items->take(3) as $item)<img src="{{ $item->logo_url }}" alt="{{ $item->name }} logo">@endforeach</div><div><small>{{ ucfirst($comparison->comparable_type) }} comparison</small><h3>{{ $comparison->title }}</h3><p>{{ $comparison->resolved_items->pluck('name')->join(' vs ') }}</p><span><i data-lucide="eye"></i> {{ number_format((int)$comparison->views) }} views</span></div><i data-lucide="arrow-up-right"></i></a>
            @endforeach
        </div>
    </section>
    @endif

    <section class="trend-note"><i data-lucide="info"></i><div><strong>What “trending” means here</strong><p>This page uses the signals currently available in your database — tool popularity, benchmark performance, editorial news importance, ecosystem activity and comparison views. It does not invent percentage growth or external traffic that AI Orbit has not measured.</p></div></section>
</div>
@endsection
