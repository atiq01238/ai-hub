@extends('frontend.layouts.app')
@php
    $isFeature = $kind === 'feature';
    $routeName = $isFeature ? 'features.show' : 'use-cases.show';
    $indexRoute = $isFeature ? 'features.index' : 'use-cases.index';
    $label = $isFeature ? 'AI Capability' : 'AI Use Case';
@endphp
@section('title',$term->meta_title ?: $term->name.' AI Tools & Models — AI Hub')
@section('meta_description',$term->meta_description ?: $term->short_description)
@push('head')
<link rel="canonical" href="{{ route($routeName,$term) }}">
<meta name="robots" content="{{ ($tools->total() + $models->count()) > 0 ? 'index,follow,max-image-preview:large' : 'noindex,follow' }}">
<script type="application/ld+json">{!! json_encode(['@context'=>'https://schema.org','@type'=>'CollectionPage','name'=>$term->name,'description'=>$term->description ?: $term->short_description,'url'=>route($routeName,$term)], JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE) !!}</script>
@endpush
@push('styles')<link rel="stylesheet" href="{{ asset('css/frontend/discovery.css') }}">@endpush
@section('content')
<section class="category-detail-hero"><div class="category-detail-inner"><div class="category-hero-main"><a class="breadcrumb-link" href="{{ route($indexRoute) }}"><i data-lucide="arrow-left"></i> {{ $isFeature ? 'All Features':'All Use Cases' }}</a><span class="eyebrow"><i data-lucide="{{ $term->icon ?: ($isFeature?'sparkles':'target') }}"></i> {{ $label }}</span><h1>{{ $term->name }}</h1><p>{{ $term->description ?: $term->short_description }}</p><div class="category-hero-actions"><a class="primary-action" href="#matching-tools">Explore tools <i data-lucide="arrow-down"></i></a><a class="secondary-action" href="{{ route('search.index',['q'=>$term->name]) }}">Search AI Hub <i data-lucide="search"></i></a></div></div><div class="category-stat-board"><span><strong>{{ number_format($tools->total()) }}</strong><small>Published tools</small></span><span><strong>{{ number_format($models->count()) }}</strong><small>Related models</small></span></div></div></section>
<div class="discovery-page category-detail-page">
    <section id="matching-tools" class="result-section"><div class="section-bar"><div><span class="section-icon"><i data-lucide="bot"></i></span><h2>AI tools for {{ $term->name }}</h2><small>Structured taxonomy matches</small></div></div>
        @if($tools->count())<div class="category-tool-grid">@foreach($tools as $tool)<article class="category-tool-card"><div class="tool-card-head"><img src="{{ $tool->logo_url }}" alt="{{ $tool->name }} logo"><div><small>{{ $tool->category?->name ?? 'AI Tool' }}</small><h3><a href="{{ route('tools.show',$tool) }}">{{ $tool->name }}</a></h3></div><span class="rating-pill"><i data-lucide="star"></i>{{ number_format((float)$tool->rating,1) }}</span></div><p>{{ \Illuminate\Support\Str::limit($tool->short_description ?: $tool->description,125) }}</p><div class="tool-card-foot"><span>{{ $tool->company?->name ?? 'Independent' }}</span><a href="{{ route('tools.show',$tool) }}">View tool <i data-lucide="arrow-right"></i></a></div></article>@endforeach</div><div class="category-pagination">{{ $tools->links() }}</div>@else<div class="inline-empty"><i data-lucide="package-open"></i><div><h3>No published tools yet</h3><p>This taxonomy page is ready, but does not yet have enough connected products.</p></div></div>@endif
    </section>
    @if($models->isNotEmpty())<section class="result-section"><div class="section-bar"><div><span class="section-icon cyan"><i data-lucide="cpu"></i></span><h2>Related AI Models</h2><small>Models explicitly mapped to this {{ strtolower($label) }}</small></div></div><div class="category-model-grid">@foreach($models as $model)<a class="category-model-card" href="{{ route('models.show',$model) }}"><img src="{{ $model->logo_url }}" alt="{{ $model->name }}"><div><small>{{ $model->company?->name }}</small><h3>{{ $model->name }}</h3><span>{{ $model->context_window ?: 'Context not listed' }}</span></div><b>{{ $model->benchmark_score !== null ? number_format((float)$model->benchmark_score,1) : '—' }}</b></a>@endforeach</div></section>@endif
</div>
@endsection
