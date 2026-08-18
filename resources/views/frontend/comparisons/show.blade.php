@extends('frontend.layouts.app')

@section('title', $title . ' — AI Comparison | AI Hub')
@section('meta_description', 'Compare '.$items->pluck('name')->join(', ').' side by side across benchmarks, pricing, capabilities and product details.')

@push('styles')<link rel="stylesheet" href="{{ asset('css/frontend/comparisons.css') }}">@endpush

@section('content')
<section class="comparison-detail-hero">
    <div class="compare-container">
        <div class="detail-breadcrumbs"><a href="{{ route('comparisons.index') }}">Comparisons</a><i data-lucide="chevron-right"></i><span>{{ $comparisonType === 'tool' ? 'AI Tools' : 'AI Models' }}</span></div>
        <div class="detail-title-row">
            <div>
                <span class="comparison-kicker"><i data-lucide="scale"></i> {{ $isPreview ? 'Live comparison' : 'AI Hub comparison' }}</span>
                <h1>{{ $title }}</h1>
                <p>A practical side-by-side look at performance, pricing, capabilities and product fit.</p>
            </div>
            <div class="detail-actions">
                <a href="{{ route('comparisons.builder', ['type'=>$comparisonType]) }}"><i data-lucide="refresh-cw"></i> New comparison</a>
                @if(!$isPreview)<button type="button" onclick="navigator.clipboard?.writeText(window.location.href)"><i data-lucide="share-2"></i> Share</button>@endif
            </div>
        </div>

        <div class="detail-product-strip cols-{{ min($items->count(),4) }}">
            @foreach($items as $item)
                <div class="detail-product-head {{ $winner && $winner->id === $item->id ? 'winner' : '' }}">
                    @if($winner && $winner->id === $item->id)<span class="winner-badge"><i data-lucide="trophy"></i> Highest overall score</span>@endif
                    <div class="detail-product-logo">@if($item->logo_path)<img src="{{ asset($item->logo_path) }}" alt="{{ $item->name }} logo">@else{{ strtoupper(substr($item->name,0,2)) }}@endif</div>
                    <small>{{ $item->company->name ?? 'Independent' }}</small>
                    <h2>{{ $item->name }}</h2>
                    <p>{{ $comparisonType === 'tool' ? ($item->short_description ?: 'AI product') : ('Version '.($item->version ?: '—')) }}</p>
                    <a href="{{ $comparisonType === 'tool' ? route('tools.show',$item) : route('models.show',$item) }}">View profile <i data-lucide="arrow-up-right"></i></a>
                </div>
            @endforeach
        </div>
    </div>
</section>

<section class="comparison-detail-body">
<div class="compare-container">
    <div class="comparison-verdict">
        <div class="verdict-icon"><i data-lucide="trophy"></i></div>
        <div><span>OVERALL LEADER</span><h2>{{ $winner?->name ?? 'No clear leader yet' }}</h2><p>Based primarily on the benchmark score available in your AI Hub dataset{{ $comparisonType === 'tool' ? ', with rating used when benchmark data is unavailable' : '' }}.</p></div>
        @if($winner)<strong>{{ number_format((float)($winner->benchmark_score ?: (($winner->rating ?? 0)*10)),1) }}<small>/100</small></strong>@endif
    </div>

    <div class="comparison-table-card">
        <div class="table-title"><span><i data-lucide="table-2"></i></span><div><h2>Side-by-side comparison</h2><p>Key product data in one view.</p></div></div>
        <div class="comparison-table-scroll">
            <table class="comparison-table">
                <thead><tr><th>Metric</th>@foreach($items as $item)<th>{{ $item->name }}</th>@endforeach</tr></thead>
                <tbody>
                    <tr><th>Provider</th>@foreach($items as $item)<td>{{ $item->company->name ?? '—' }}</td>@endforeach</tr>
                    <tr><th>Benchmark score</th>@foreach($items as $item)<td><strong class="score-value">{{ number_format((float)($item->benchmark_score ?? 0),1) }}</strong><span class="mini-score-bar"><i style="width:{{ min(100,max(0,(float)($item->benchmark_score ?? 0))) }}%"></i></span></td>@endforeach</tr>
                    @if($comparisonType === 'tool')
                        <tr><th>Rating</th>@foreach($items as $item)<td><span class="rating-cell"><i data-lucide="star"></i>{{ number_format((float)$item->rating,1) }}/10</span></td>@endforeach</tr>
                        <tr><th>Popularity</th>@foreach($items as $item)<td>{{ number_format((int)$item->popularity) }}</td>@endforeach</tr>
                        <tr><th>Category</th>@foreach($items as $item)<td>{{ $item->category->name ?? '—' }}</td>@endforeach</tr>
                        <tr><th>Pricing</th>@foreach($items as $item)<td>@forelse((array)$item->pricing_models as $price)<span class="data-chip">{{ ucfirst((string)$price) }}</span>@empty—@endforelse</td>@endforeach</tr>
                        <tr><th>Platforms</th>@foreach($items as $item)<td>@forelse((array)$item->platforms as $platform)<span class="data-chip">{{ $platform }}</span>@empty—@endforelse</td>@endforeach</tr>
                        <tr><th>Launch date</th>@foreach($items as $item)<td>{{ $item->launch_date?->format('M Y') ?? '—' }}</td>@endforeach</tr>
                    @else
                        <tr><th>Version</th>@foreach($items as $item)<td>{{ $item->version ?: '—' }}</td>@endforeach</tr>
                        <tr><th>Context window</th>@foreach($items as $item)<td><strong>{{ $item->context_window ?: '—' }}</strong></td>@endforeach</tr>
                        <tr><th>Input / 1M tokens</th>@foreach($items as $item)<td>${{ number_format((float)$item->input_price_per_million,2) }}</td>@endforeach</tr>
                        <tr><th>Output / 1M tokens</th>@foreach($items as $item)<td>${{ number_format((float)$item->output_price_per_million,2) }}</td>@endforeach</tr>
                        <tr><th>Status</th>@foreach($items as $item)<td><span class="status-chip {{ $item->status }}">{{ ucfirst($item->status) }}</span></td>@endforeach</tr>
                        <tr><th>Release date</th>@foreach($items as $item)<td>{{ $item->release_date?->format('M Y') ?? '—' }}</td>@endforeach</tr>
                    @endif
                </tbody>
            </table>
        </div>
    </div>

    <div class="capability-comparison">
        <div class="table-title"><span><i data-lucide="sparkles"></i></span><div><h2>Capabilities</h2><p>What each product is designed to do.</p></div></div>
        <div class="capability-columns cols-{{ min($items->count(),4) }}">
            @foreach($items as $item)
                @php($caps = $comparisonType === 'tool' ? (array)$item->capabilities : (array)$item->capabilities)
                <div class="capability-column"><div class="capability-column-head"><div class="tiny-logo">@if($item->logo_path)<img src="{{ asset($item->logo_path) }}" alt="">@else{{ strtoupper(substr($item->name,0,2)) }}@endif</div><strong>{{ $item->name }}</strong></div><div class="capability-chip-list">@forelse($caps as $cap)<span><i data-lucide="check"></i>{{ is_string($cap) ? $cap : (is_array($cap) ? ($cap['name'] ?? 'Capability') : 'Capability') }}</span>@empty<span class="muted">No capability data yet.</span>@endforelse</div></div>
            @endforeach
        </div>
    </div>

    @if($comparisonType === 'tool')
    <div class="choose-guide">
        <div class="table-title"><span><i data-lucide="lightbulb"></i></span><div><h2>Quick decision guide</h2><p>Use the dataset to narrow your choice.</p></div></div>
        <div class="decision-grid">
            @foreach($items as $item)
                <div class="decision-card"><div class="tiny-logo">@if($item->logo_path)<img src="{{ asset($item->logo_path) }}" alt="">@else{{ strtoupper(substr($item->name,0,2)) }}@endif</div><div><small>CHOOSE {{ strtoupper($item->name) }} IF</small><h3>You value {{ $item->category->name ?? 'its core workflow' }}</h3><p>{{ $item->short_description ?: 'Explore the full profile to see capabilities, pricing and platform support.' }}</p></div></div>
            @endforeach
        </div>
    </div>
    @endif

    @if(!$isPreview && $relatedComparisons->isNotEmpty())
    <div class="related-comparisons">
        <div class="section-heading-row"><div><span class="section-eyebrow">KEEP COMPARING</span><h2>Related comparisons</h2></div><a href="{{ route('comparisons.index') }}">View all <i data-lucide="arrow-right"></i></a></div>
        <div class="related-comparison-grid">
            @foreach($relatedComparisons as $related)
                <a href="{{ route('comparisons.show',$related) }}"><span>{{ ucfirst($related->comparable_type) }}</span><h3>{{ $related->title }}</h3><small>{{ number_format($related->views) }} views</small><i data-lucide="arrow-up-right"></i></a>
            @endforeach
        </div>
    </div>
    @endif
</div>
</section>
@endsection
