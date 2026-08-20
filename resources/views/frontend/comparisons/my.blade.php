@extends('frontend.layouts.app')
@section('title','My Saved Comparisons | AI Hub')
@push('styles')<link rel="stylesheet" href="{{ asset('css/frontend/comparisons.css') }}">@endpush
@section('content')
<section class="builder-hero"><div class="compare-container builder-hero-inner">
    <span class="comparison-kicker"><i data-lucide="bookmark-check"></i> YOUR LIBRARY</span>
    <h1>Saved comparisons.</h1>
    <p>Comparisons you chose to keep for later research.</p>
</div></section>
<section class="comparison-detail-body"><div class="compare-container">
    <div class="section-heading-row"><div><span class="section-eyebrow">PERSONAL RESEARCH</span><h2>{{ number_format($comparisons->total()) }} saved comparisons</h2></div><a href="{{ route('comparisons.builder') }}">Build comparison <i data-lucide="plus"></i></a></div>
    <div class="related-comparison-grid">
        @forelse($comparisons as $item)
            @php($params = http_build_query(['type'=>$item->comparable_type,'items'=>$item->item_ids]))
            <a href="{{ $item->comparison ? route('comparisons.show',$item->comparison) : route('comparisons.preview').'?'.$params }}">
                <span>{{ strtoupper($item->comparable_type) }}</span>
                <h3>{{ $item->title }}</h3>
                <small>Saved {{ $item->updated_at->diffForHumans() }}</small>
                <i data-lucide="arrow-up-right"></i>
            </a>
        @empty
            <div class="inline-empty">You have not saved a comparison yet.</div>
        @endforelse
    </div>
    <div style="margin-top:20px">{{ $comparisons->links() }}</div>
</div></section>
@endsection
