@extends('frontend.layouts.app')

@php
    $topicsSeoTitle = 'AI Topics, Guides and Analysis | AI Orbit';
    $topicsSeoDescription = 'Explore AI Orbit editorial topics including guides, model releases, research, benchmarks, pricing analysis and industry intelligence.';
    $topicsCanonical = route('topics.index');

    $topicsCollectionSchema = [
        '@' . 'context' => 'https://schema.org',
        '@' . 'type' => 'CollectionPage',
        'name' => 'AI Topics, Guides and Analysis',
        'description' => $topicsSeoDescription,
        'url' => $topicsCanonical,
    ];

    $topicsBreadcrumbSchema = [
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
                'item' => $topicsCanonical,
            ],
        ],
    ];
@endphp

@section('title', $topicsSeoTitle)
@section('meta_description', $topicsSeoDescription)
@section('canonical', $topicsCanonical)
@section('robots', 'index,follow,max-image-preview:large,max-snippet:-1,max-video-preview:-1')

@push('head')
<script type="application/ld+json">{!! json_encode(
    $topicsCollectionSchema,
    JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
) !!}</script>
<script type="application/ld+json">{!! json_encode(
    $topicsBreadcrumbSchema,
    JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
) !!}</script>
@endpush

@push('styles')<link rel="stylesheet" href="{{ asset('css/frontend/discovery.css') }}">@endpush
@section('content')
<section class="category-directory-hero"><div class="category-directory-inner"><span class="eyebrow"><i data-lucide="newspaper"></i> Editorial taxonomy</span><h1>AI Topics & Analysis</h1><p>Browse AI Orbit articles by a dedicated content taxonomy, separate from product categories.</p><div class="category-hero-actions"><a class="primary-action" href="{{ route('articles.index') }}">All articles <i data-lucide="arrow-right"></i></a><a class="secondary-action" href="{{ route('categories.index') }}">Product categories</a></div></div></section>
<div class="discovery-page category-directory-page"><div class="category-directory-grid">@foreach($items as $topic)<a class="category-directory-card" href="{{ route('topics.show',$topic) }}"><div class="category-card-top"><span class="category-orb large"><i data-lucide="book-open-text"></i></span><span class="category-arrow"><i data-lucide="arrow-up-right"></i></span></div><h3>{{ $topic->name }}</h3><p>{{ $topic->description ?: $topic->short_description }}</p><div class="category-metrics"><span><strong>{{ number_format($topic->articles_count) }}</strong>Articles</span></div></a>@endforeach</div></div>
@endsection
