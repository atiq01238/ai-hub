@extends('frontend.layouts.app')

@php
    $benchmarkCanonical = route('benchmarks.show', $benchmark);

    $benchmarkDatasetSchema = array_filter([
        '@' . 'context' => 'https://schema.org',
        '@' . 'type' => 'Dataset',
        'name' => $benchmark->name . ' benchmark results',
        'description' => $description,
        'url' => $benchmarkCanonical,
        'isAccessibleForFree' => true,
        'measurementTechnique' => $benchmark->name,
        'version' => $benchmark->version ?: null,
        'sameAs' => $benchmark->official_url ?: null,
        'dateModified' => $benchmark->updated_at?->toAtomString(),
    ], fn ($value) => $value !== null && $value !== '');

    $benchmarkBreadcrumbSchema = [
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
                'name' => 'Benchmarks',
                'item' => route('benchmarks.index'),
            ],
            [
                '@' . 'type' => 'ListItem',
                'position' => 3,
                'name' => $benchmark->name,
                'item' => $benchmarkCanonical,
            ],
        ],
    ];
@endphp

@section('title', $title . ' | AI Orbit')
@section('meta_description', $description)
@section('canonical', $benchmarkCanonical)
@section('og_type', 'website')
@section('robots', 'index,follow,max-image-preview:large,max-snippet:-1,max-video-preview:-1')

@push('head')
<script type="application/ld+json">{!! json_encode(
    $benchmarkDatasetSchema,
    JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
) !!}</script>
<script type="application/ld+json">{!! json_encode(
    $benchmarkBreadcrumbSchema,
    JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
) !!}</script>
@endpush
@section('content')
<section class="section"><div class="container"><div class="section-heading"><span class="eyebrow">VERIFIED AI BENCHMARK</span><h1>{{ $benchmark->name }} Leaderboard</h1><p>{{ $benchmark->description ?: $description }}</p></div>
<div class="card" style="padding:18px;margin-bottom:18px"><strong>{{ $benchmark->benchmark_class_label }}</strong> · {{ $benchmark->category }} · {{ $benchmark->unit ?: 'score' }} · {{ $benchmark->higher_is_better ? 'Higher is better' : 'Lower is better' }} @if($benchmark->version) · Version {{ $benchmark->version }} @endif @if($benchmark->variant) · {{ $benchmark->variant }} @endif<br><small>Only verified results from this semantic class are included. This score is not combined with incompatible classes.</small> @if($benchmark->methodology_url || $benchmark->official_url)<p><a rel="nofollow noopener" target="_blank" href="{{ $benchmark->methodology_url ?: $benchmark->official_url }}">Official methodology / benchmark source</a></p>@endif</div>
<div class="card" style="overflow:auto"><table class="table"><thead><tr><th>#</th><th>Model / Tool</th><th>Score</th><th>Tested</th><th>Source</th></tr></thead><tbody>@forelse($results as $result)<tr><td>{{ $loop->iteration }}</td><td><strong>@if($result->benchmarkable instanceof \App\Models\AiModel && in_array($result->benchmarkable->status,['active','preview'],true))<a class="benchmark-entity-link" href="{{ route('models.show',$result->benchmarkable) }}">{{ $result->benchmarkable->name }}<i data-lucide="arrow-up-right"></i></a>@elseif($result->benchmarkable instanceof \App\Models\Tool && $result->benchmarkable->status === 'published')<a class="benchmark-entity-link" href="{{ route('tools.show',$result->benchmarkable) }}">{{ $result->benchmarkable->name }}<i data-lucide="arrow-up-right"></i></a>@else{{ $result->benchmarkable?->name ?? 'Unavailable' }}@endif</strong></td><td>{{ number_format((float)$result->score,2) }} {{ $benchmark->unit }}</td><td>{{ $result->tested_at?->format('M j, Y') ?? '—' }}</td><td>@if($result->source_url)<a rel="nofollow noopener" target="_blank" href="{{ $result->source_url }}">{{ $result->source_name ?: 'Source' }}</a>@else{{ $result->source_name ?: '—' }}@endif</td></tr>@empty<tr><td colspan="5">No verified results published yet.</td></tr>@endforelse</tbody></table></div>
<section style="margin-top:28px"><h2>About {{ $benchmark->name }}</h2><p>This leaderboard uses the latest verified result available for each listed entity. Results retain their source and test date so readers can evaluate freshness and methodology.</p></section></div></section>
@endsection
