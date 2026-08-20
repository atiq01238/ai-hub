@extends('frontend.layouts.app')
@section('title',$benchmark->name.' Discussion — AI Benchmarks | AI Hub')
@section('meta_description','Community discussion around the '.$benchmark->name.' benchmark, methodology and results.')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/frontend/benchmarks.css') }}">
@endpush

@section('content')
<section class="intel-hero benchmark-intel-hero">
    <div class="intel-hero-grid"></div>
    <div class="intel-wrap">
        <a href="{{ route('benchmarks.index') }}" class="intel-back"><i data-lucide="arrow-left"></i> Benchmarks</a>
        <span class="intel-eyebrow"><i data-lucide="messages-square"></i> BENCHMARK DISCUSSION</span>
        <h1>{{ $benchmark->name }}</h1>
        <p>{{ $benchmark->description ?: 'Discuss methodology, interpretation and real-world implications of this benchmark.' }}</p>
        <div class="intel-hero-stats">
            <span><b>{{ $benchmark->category ?: 'General' }}</b><small>Category</small></span>
            <span><b>{{ number_format((float)$benchmark->max_score,0) }}</b><small>Maximum score</small></span>
            <span><b>{{ $benchmark->higher_is_better ? 'Higher' : 'Lower' }}</b><small>Better direction</small></span>
        </div>
    </div>
</section>

<section class="intel-directory">
    <div class="intel-wrap">
        <div data-community-static data-community-type="benchmark" data-community-id="{{ $benchmark->id }}"></div>
    </div>
</section>
@endsection
