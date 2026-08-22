@extends('frontend.layouts.app')
@section('title','AI Topics, Guides & Analysis — AI Hub')
@section('meta_description','Explore AI Hub editorial topics including guides, model releases, research, benchmarks, pricing analysis and industry intelligence.')
@push('head')<link rel="canonical" href="{{ route('topics.index') }}">@endpush
@push('styles')<link rel="stylesheet" href="{{ asset('css/frontend/discovery.css') }}">@endpush
@section('content')
<section class="category-directory-hero"><div class="category-directory-inner"><span class="eyebrow"><i data-lucide="newspaper"></i> Editorial taxonomy</span><h1>AI Topics & Analysis</h1><p>Browse AI Hub articles by a dedicated content taxonomy, separate from product categories.</p><div class="category-hero-actions"><a class="primary-action" href="{{ route('articles.index') }}">All articles <i data-lucide="arrow-right"></i></a><a class="secondary-action" href="{{ route('categories.index') }}">Product categories</a></div></div></section>
<div class="discovery-page category-directory-page"><div class="category-directory-grid">@foreach($items as $topic)<a class="category-directory-card" href="{{ route('topics.show',$topic) }}"><div class="category-card-top"><span class="category-orb large"><i data-lucide="book-open-text"></i></span><span class="category-arrow"><i data-lucide="arrow-up-right"></i></span></div><h3>{{ $topic->name }}</h3><p>{{ $topic->description ?: $topic->short_description }}</p><div class="category-metrics"><span><strong>{{ number_format($topic->articles_count) }}</strong>Articles</span></div></a>@endforeach</div></div>
@endsection
