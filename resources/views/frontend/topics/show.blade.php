@extends('frontend.layouts.app')

@php
    $topicHasExtraQuery = collect(request()->query())
        ->except('page')
        ->isNotEmpty();

    $topicSeoTitle = $topic->meta_title
        ?: $topic->name . ' — AI Orbit';

    $topicSeoTitle = str_ireplace(
        'AI Hub',
        'AI Orbit',
        $topicSeoTitle
    );

    $topicSeoDescription = $topic->meta_description
        ?: $topic->short_description
        ?: $topic->description
        ?: 'Explore published AI Orbit articles about ' . $topic->name . '.';

    for ($i = 0; $i < 5; $i++) {
        $decodedTitle = html_entity_decode($topicSeoTitle, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $decodedDescription = html_entity_decode($topicSeoDescription, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        if ($decodedTitle === $topicSeoTitle && $decodedDescription === $topicSeoDescription) {
            break;
        }
        $topicSeoTitle = $decodedTitle;
        $topicSeoDescription = $decodedDescription;
    }
    $topicSeoDescription = trim(strip_tags($topicSeoDescription));

    $topicCanonical = route('topics.show', $topic);

    if (!$topicHasExtraQuery && $articles->currentPage() > 1) {
        $topicCanonical = $articles->url($articles->currentPage());
        $topicSeoTitle = $topic->name
            . ' — Page '
            . $articles->currentPage()
            . ' | AI Orbit';
    }

    $topicCollectionSchema = [
        '@' . 'context' => 'https://schema.org',
        '@' . 'type' => 'CollectionPage',
        'name' => $topic->name,
        'description' => $topicSeoDescription,
        'url' => $topicCanonical,
    ];

    $topicBreadcrumbSchema = [
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
                'name' => 'AI Topics',
                'item' => route('topics.index'),
            ],
            [
                '@' . 'type' => 'ListItem',
                'position' => 3,
                'name' => $topic->name,
                'item' => route('topics.show', $topic),
            ],
        ],
    ];
@endphp

@section('title', $topicSeoTitle)
@section('meta_description', $topicSeoDescription)
@section('canonical', $topicCanonical)

@section(
    'robots',
    $topicHasExtraQuery
        ? 'noindex,follow'
        : ($articles->total() > 0
            ? 'index,follow,max-image-preview:large,max-snippet:-1,max-video-preview:-1'
            : 'noindex,follow')
)

@push('head')
<script type="application/ld+json">{!! json_encode(
    $topicCollectionSchema,
    JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
) !!}</script>
<script type="application/ld+json">{!! json_encode(
    $topicBreadcrumbSchema,
    JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
) !!}</script>
@endpush

@push('styles')<link rel="stylesheet" href="{{ asset('css/frontend/discovery.css') }}">@endpush
@section('content')
<section class="category-detail-hero"><div class="category-detail-inner"><div class="category-hero-main"><a class="breadcrumb-link" href="{{ route('topics.index') }}"><i data-lucide="arrow-left"></i> All Topics</a><span class="eyebrow"><i data-lucide="newspaper"></i> Editorial Topic</span><h1>{{ $topic->name }}</h1><p>{{ $topic->description ?: $topic->short_description }}</p></div><div class="category-stat-board"><span><strong>{{ number_format($articles->total()) }}</strong><small>Published articles</small></span></div></div></section>
<div class="discovery-page category-detail-page"><section class="result-section"><div class="section-bar"><div><span class="section-icon gold"><i data-lucide="book-open-text"></i></span><h2>Latest {{ $topic->name }}</h2></div></div>@if($articles->count())<div class="mini-story-list taxonomy-story-grid">@foreach($articles as $article)<a href="{{ route('articles.show',$article) }}"><img src="{{ $article->featured_image_url ?: '/images/frontend/content-placeholder.svg' }}" alt="{{ $article->title }}"><div><small>{{ optional($article->published_at)->format('M j, Y') }} · {{ $article->company?->name ?? 'AI Orbit' }}</small><h3>{{ $article->title }}</h3><p>{{ \Illuminate\Support\Str::limit($article->summary ?: strip_tags($article->content),120) }}</p></div></a>@endforeach</div><div class="category-pagination">{{ $articles->links() }}</div>@else<div class="inline-empty"><i data-lucide="book-open"></i><div><h3>No published articles yet</h3><p>This editorial topic is ready for future content.</p></div></div>@endif</section></div>
@endsection
