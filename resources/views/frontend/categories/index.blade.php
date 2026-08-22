@extends('frontend.layouts.app')
@section('title','AI Categories — AI Hub')
@section('meta_description','Browse AI tools and models across curated product categories with structured subcategories, capabilities, use cases and supporting intelligence.')
@push('head')<link rel="canonical" href="{{ route('categories.index') }}">@endpush
@push('styles')<link rel="stylesheet" href="{{ asset('css/frontend/discovery.css') }}">@endpush
@section('content')
<section class="discovery-hero category-hero">
    <div class="discovery-hero-grid"></div>
    <div class="discovery-hero-copy">
        <span class="eyebrow"><i data-lucide="layout-grid"></i> AI Categories</span>
        <h1>Explore AI by <span>what you want to do.</span></h1>
        <p>Browse focused collections of tools, models, research, news and practical guides across the AI ecosystem.</p>
        <form class="discovery-search" action="{{ route('search.index') }}" method="get"><i data-lucide="search"></i><input name="q" type="search" placeholder="Search across every AI category..."><button type="submit">Search AI Hub <i data-lucide="arrow-right"></i></button></form>
    </div>
</section>
<div class="discovery-page">
    <section class="category-directory-head"><div><span class="eyebrow"><i data-lucide="layers-3"></i> Directory</span><h2>{{ $categories->count() }} AI categories</h2><p>Each category combines the strongest products and intelligence from across AI Hub.</p></div><a href="{{ route('tools.index') }}" class="secondary-action">All AI Tools <i data-lucide="arrow-right"></i></a></section>
    <div class="category-directory-grid">
        @php($icons=['chat-assistants'=>'messages-square','coding-development'=>'code-2','image-design'=>'image','video-animation'=>'clapperboard','writing-content'=>'pen-line','voice-audio'=>'mic-2','music'=>'music-2','search-research'=>'search','agents-automation'=>'workflow','productivity-office'=>'zap','data-analytics'=>'chart-no-axes-combined','marketing-sales'=>'megaphone','customer-support'=>'headphones','education-learning'=>'graduation-cap'])
        @foreach($categories as $category)
            <a class="category-directory-card" href="{{ route('categories.show',$category) }}">
                <div class="category-card-top"><span class="category-orb large"><i data-lucide="{{ $icons[$category->slug] ?? 'sparkles' }}"></i></span><span class="category-arrow"><i data-lucide="arrow-up-right"></i></span></div>
                <h3>{{ $category->name }}</h3>
                <p>{{ $category->short_description ?: 'Discover leading '.strtolower($category->name).' tools, related models and structured AI intelligence.' }}</p>
                <div class="category-metrics"><span><strong>{{ number_format($category->tools_count) }}</strong>Tools</span><span><strong>{{ number_format($category->models_count) }}</strong>Models</span><span><strong>{{ number_format($category->articles_count) }}</strong>Guides</span><span><strong>{{ number_format($category->news_count) }}</strong>News</span></div>
            </a>
        @endforeach
    </div>
    <section class="result-section category-featured">
        <div class="section-bar"><div><span class="section-icon"><i data-lucide="award"></i></span><h2>Top Rated Across Categories</h2><small>Strong products from across the directory</small></div><a href="{{ route('tools.index') }}">Full tools directory <i data-lucide="arrow-right"></i></a></div>
        <div class="entity-grid entity-grid-tools">
            @foreach($featuredTools as $tool)
                <article class="search-entity-card"><a class="entity-top" href="{{ route('tools.show',$tool) }}"><img src="{{ $tool->logo_url }}" alt="{{ $tool->name }} logo"><div><small>{{ $tool->category?->name ?? 'AI Tool' }}</small><h3>{{ $tool->name }}</h3><span>{{ $tool->company?->name ?? 'Independent' }}</span></div><b>{{ number_format((float)$tool->rating,1) }}</b></a><p>{{ \Illuminate\Support\Str::limit($tool->short_description,105) }}</p></article>
            @endforeach
        </div>
    </section>
</div>
@endsection
