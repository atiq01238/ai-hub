@extends('frontend.layouts.app')

@php
    $categorySeoTitle = $category->meta_title
        ?: $category->name . ' AI Tools & Models | AI Orbit';

    // Remove old branding from saved SEO titles.
    $categorySeoTitle = str_ireplace(
        'AI Hub',
        'AI Orbit',
        $categorySeoTitle
    );

    // Fully normalize previously stored HTML entities.
        for ($i = 0; $i < 5; $i++) {
            $decoded = html_entity_decode(
                $categorySeoTitle,
                ENT_QUOTES | ENT_HTML5,
                'UTF-8'
            );

            if ($decoded === $categorySeoTitle) {
                break;
            }

            $categorySeoTitle = $decoded;
        }

    $categorySeoDescription = $category->meta_description
        ?: $category->short_description
        ?: 'Explore AI tools, models, guides and news in the '
            . $category->name
            . ' category on AI Orbit.';

    for ($i = 0; $i < 5; $i++) {
        $decoded = html_entity_decode(
            $categorySeoDescription,
            ENT_QUOTES | ENT_HTML5,
            'UTF-8'
        );

        if ($decoded === $categorySeoDescription) {
            break;
        }

        $categorySeoDescription = $decoded;
    }

    $categorySeoDescription = trim(
        strip_tags($categorySeoDescription)
    );

    $categoryUrl = route('categories.show', $category);
    $categoryHasFilters = request()->hasAny(['sort']);
    $categoryCanonical = $categoryUrl;

    if (!$categoryHasFilters && $tools->currentPage() > 1) {
        $categoryCanonical = $tools->url($tools->currentPage());
        $categorySeoTitle = $category->name
            . ' AI Tools and Models — Page '
            . $tools->currentPage()
            . ' | AI Orbit';
    }

    $categorySchema = [
        '@' . 'context' => 'https://schema.org',
        '@' . 'type' => 'CollectionPage',
        'name' => $category->name,
        'description' => $categorySeoDescription,
        'url' => $categoryCanonical,
    ];

    $categoryBreadcrumbSchema = [
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
                'name' => 'AI Categories',
                'item' => route('categories.index'),
            ],
            [
                '@' . 'type' => 'ListItem',
                'position' => 3,
                'name' => $category->name,
                'item' => $categoryUrl,
            ],
        ],
    ];
@endphp

@section('title', $categorySeoTitle)
@section('meta_description', $categorySeoDescription)
@section('canonical', $categoryCanonical)

@section(
    'robots',
    (!$category->is_indexable || $categoryHasFilters)
        ? 'noindex,follow'
        : (($stats['tools'] + $stats['models'] + $stats['articles'] + $stats['news']) > 0
            ? 'index,follow,max-image-preview:large,max-snippet:-1,max-video-preview:-1'
            : 'noindex,follow')
)

@push('head')
<script type="application/ld+json">{!! json_encode(
    $categorySchema,
    JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
) !!}</script>

<script type="application/ld+json">{!! json_encode(
    $categoryBreadcrumbSchema,
    JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
) !!}</script>
@endpush

@push('styles')
<link rel="stylesheet" href="{{ asset('css/frontend/discovery.css') }}">
@endpush
@section('content')
<section class="category-detail-hero">
    <div class="category-detail-inner">
        <div class="category-hero-main"><a class="breadcrumb-link" href="{{ route('categories.index') }}"><i data-lucide="arrow-left"></i> All Categories</a><span class="eyebrow"><i data-lucide="sparkles"></i> AI Category</span><h1>{{ $category->name }}</h1><p>{{ $category->description ?: $category->short_description }}</p><div class="category-hero-actions"><a class="primary-action" href="#category-tools">Explore tools <i data-lucide="arrow-down"></i></a><a class="secondary-action" href="{{ route('search.index',['q'=>$category->name]) }}">Search this topic <i data-lucide="search"></i></a></div></div>
        <div class="category-stat-board"><span><strong>{{ number_format($stats['tools']) }}</strong><small>Published tools</small></span><span><strong>{{ number_format($stats['models']) }}</strong><small>Related models</small></span><span><strong>{{ number_format($stats['articles']) }}</strong><small>Guides</small></span><span><strong>{{ number_format($stats['news']) }}</strong><small>News stories</small></span></div>
    </div>
</section>
<div class="discovery-page category-detail-page">

    @if($subcategories->isNotEmpty())
    <section class="related-category-strip"><div><span class="eyebrow"><i data-lucide="git-branch"></i> Browse deeper</span><h2>{{ $category->name }} subcategories</h2></div><div class="related-category-links">@foreach($subcategories as $subcategory)<a href="{{ route('categories.subcategories.show',[$category,$subcategory]) }}"><span>{{ $subcategory->name }}</span><small>{{ $subcategory->tools_count }} tools</small><i data-lucide="arrow-up-right"></i></a>@endforeach</div></section>
    @endif

    <section id="category-tools" class="result-section">
        <div class="section-bar category-tools-bar"><div><span class="section-icon"><i data-lucide="bot"></i></span><h2>Top {{ $category->name }} Tools</h2><small>{{ number_format($tools->total()) }} published products</small></div><form method="get" class="sort-form"><label for="category-sort">Sort</label><select id="category-sort" name="sort" onchange="this.form.submit()"><option value="top" @selected($sort==='top')>Top rated</option><option value="popular" @selected($sort==='popular')>Most popular</option><option value="newest" @selected($sort==='newest')>Newest</option></select></form></div>
        @if($tools->isEmpty())
            <div class="inline-empty"><i data-lucide="package-open"></i><div><h3>No published tools yet</h3><p>This category is ready for future listings.</p></div></div>
        @else
            <div class="category-tool-grid">
                @foreach($tools as $tool)
                    <article class="category-tool-card"><div class="tool-card-head"><img src="{{ $tool->logo_url }}" alt="{{ $tool->name }} logo"><div><small>{{ $tool->company?->name ?? 'AI Tool' }}</small><h3><a href="{{ route('tools.show',$tool) }}">{{ $tool->name }}</a></h3></div><span class="rating-pill"><i data-lucide="star"></i>{{ number_format((float)$tool->rating,1) }}</span></div><p>{{ \Illuminate\Support\Str::limit($tool->short_description ?: $tool->description, 125) }}</p><div class="tool-card-tags">@foreach(array_slice($tool->capabilities ?? [],0,3) as $capability)<span>{{ $capability }}</span>@endforeach</div><div class="tool-card-foot"><span><i data-lucide="flame"></i>{{ number_format((int)$tool->popularity) }} popularity</span><a href="{{ route('tools.show',$tool) }}">View tool <i data-lucide="arrow-right"></i></a></div></article>
                @endforeach
            </div>
            <div class="category-pagination">
                <nav class="category-pager" aria-label="Category tools pagination">
                    @if($tools->onFirstPage())<span class="pager-disabled"><i data-lucide="chevron-left"></i> Previous</span>@else<a href="{{ $tools->previousPageUrl() }}"><i data-lucide="chevron-left"></i> Previous</a>@endif
                    <div class="pager-pages">
                        @foreach(range(max(1,$tools->currentPage()-2), min($tools->lastPage(),$tools->currentPage()+2)) as $page)
                            @if($page===$tools->currentPage())<span class="active">{{ $page }}</span>@else<a href="{{ $tools->url($page) }}">{{ $page }}</a>@endif
                        @endforeach
                    </div>
                    @if($tools->hasMorePages())<a href="{{ $tools->nextPageUrl() }}">Next <i data-lucide="chevron-right"></i></a>@else<span class="pager-disabled">Next <i data-lucide="chevron-right"></i></span>@endif
                </nav>
                <p class="pager-summary">Showing {{ $tools->firstItem() }}–{{ $tools->lastItem() }} of {{ $tools->total() }} tools</p>
            </div>
        @endif
    </section>

    @if($models->isNotEmpty())
    <section class="result-section"><div class="section-bar"><div><span class="section-icon cyan"><i data-lucide="cpu"></i></span><h2>Related AI Models</h2><small>Models powering products in this category</small></div><a href="{{ route('models.index') }}">Models directory <i data-lucide="arrow-right"></i></a></div><div class="category-model-grid">@foreach($models as $model)<a class="category-model-card" href="{{ route('models.show',$model) }}"><img src="{{ $model->logo_url }}" alt="{{ $model->name }}"><div><small>{{ $model->company?->name }}</small><h3>{{ $model->name }}</h3><span>{{ $model->context_window ?: '—' }} context</span></div><b>{{ $model->benchmark_score !== null ? number_format((float)$model->benchmark_score,1) : '—' }}</b></a>@endforeach</div></section>
    @endif

    <div class="category-content-split">
        @if($articles->isNotEmpty())<section class="result-section"><div class="section-bar"><div><span class="section-icon gold"><i data-lucide="newspaper"></i></span><h2>Guides & Articles</h2></div><a href="{{ route('articles.index',['q'=>$category->name]) }}">All articles <i data-lucide="arrow-right"></i></a></div><div class="mini-story-list">@foreach($articles as $article)<a href="{{ route('articles.show',$article) }}"><img src="{{ $article->featured_image_url ?: '/images/frontend/content-placeholder.svg' }}" alt="{{ $article->title }}"><div><small>{{ optional($article->published_at)->format('M j, Y') }}</small><h3>{{ $article->title }}</h3><p>{{ \Illuminate\Support\Str::limit($article->summary,85) }}</p></div></a>@endforeach</div></section>@endif
        @if($news->isNotEmpty())<section class="result-section"><div class="section-bar"><div><span class="section-icon red"><i data-lucide="radio"></i></span><h2>Latest {{ $category->name }} News</h2></div><a href="{{ route('news.index',['q'=>$category->name]) }}">All news <i data-lucide="arrow-right"></i></a></div><div class="mini-story-list">@foreach($news as $item)<a href="{{ route('news.show',$item) }}"><img src="{{ $item->image_url ?: '/images/frontend/content-placeholder.svg' }}" alt="{{ $item->headline }}"><div><small>{{ $item->source ?? $item->company?->name }} · {{ optional($item->published_at)->diffForHumans() }}</small><h3>{{ $item->headline }}</h3><p>{{ \Illuminate\Support\Str::limit($item->summary ?: $item->ai_summary,85) }}</p></div></a>@endforeach</div></section>@endif
    </div>

    <section class="related-category-strip"><div><span class="eyebrow"><i data-lucide="compass"></i> Keep exploring</span><h2>Related AI categories</h2></div><div class="related-category-links">@foreach($relatedCategories as $related)<a href="{{ route('categories.show',$related) }}"><span>{{ $related->name }}</span><small>{{ $related->tools_count }} tools</small><i data-lucide="arrow-up-right"></i></a>@endforeach</div></section>
</div>
@endsection
