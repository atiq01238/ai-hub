@extends('frontend.layouts.app')
@php
    $isFeature = $kind === 'features';
    $title = $isFeature ? 'AI Features & Capabilities' : 'AI Use Cases';
    $description = $isFeature
        ? 'Explore normalized AI capabilities across tools and models, from reasoning and research to image, audio, coding and agents.'
        : 'Find AI tools and models by the work you want to accomplish, from research and coding to creative production and automation.';
@endphp
@section('title',$title.' — AI Orbit')
@section('meta_description',$description)
@push('styles')<link rel="stylesheet" href="{{ asset('css/frontend/discovery.css') }}">@endpush
@section('content')
<section class="category-directory-hero"><div class="category-directory-inner"><span class="eyebrow"><i data-lucide="{{ $isFeature ? 'sparkles':'target' }}"></i> Taxonomy v2</span><h1>{{ $title }}</h1><p>{{ $description }}</p><div class="category-hero-actions"><a class="primary-action" href="{{ route('tools.index') }}">Browse AI tools <i data-lucide="arrow-right"></i></a><a class="secondary-action" href="{{ $isFeature ? route('use-cases.index') : route('features.index') }}">{{ $isFeature ? 'Explore use cases':'Explore features' }}</a></div></div></section>
<div class="discovery-page category-directory-page">
    <section class="directory-intro"><div><span class="eyebrow"><i data-lucide="network"></i> Structured discovery</span><h2>{{ number_format($items->count()) }} curated {{ $isFeature ? 'capabilities':'use cases' }}</h2><p>Every term is normalized and connected to real tool/model records instead of relying on inconsistent free-text labels.</p></div></section>
    <div class="category-directory-grid taxonomy-v2-directory">
        @foreach($items as $item)
            <a class="category-directory-card" href="{{ $isFeature ? route('features.show',$item) : route('use-cases.show',$item) }}">
                <div class="category-card-top"><span class="category-orb large"><i data-lucide="{{ $item->icon ?: ($isFeature ? 'sparkles':'target') }}"></i></span><span class="category-arrow"><i data-lucide="arrow-up-right"></i></span></div>
                @if($isFeature && $item->group)<small class="taxonomy-group-label">{{ $item->group }}</small>@endif
                <h3>{{ $item->name }}</h3><p>{{ $item->description ?: $item->short_description }}</p>
                <div class="category-metrics"><span><strong>{{ number_format($item->tools_count) }}</strong>Tools</span><span><strong>{{ number_format($item->models_count) }}</strong>Models</span></div>
            </a>
        @endforeach
    </div>
</div>
@endsection
