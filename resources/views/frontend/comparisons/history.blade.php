@extends('frontend.layouts.app')
@section('title','Comparison History | AI Hub')
@push('styles')<link rel="stylesheet" href="{{ asset('css/frontend/comparisons.css') }}">@endpush
@section('content')
<section class="builder-hero"><div class="compare-container builder-hero-inner">
    <span class="comparison-kicker"><i data-lucide="history"></i> ACCOUNT HISTORY</span>
    <h1>Your comparison history.</h1>
    <p>Recently viewed tool and model comparisons while signed in.</p>
</div></section>
<section class="comparison-detail-body"><div class="compare-container">
    <div class="related-comparison-grid">
        @forelse($comparisons as $item)
            @php($params = http_build_query(['type'=>$item->comparable_type,'items'=>$item->item_ids]))
            <a href="{{ $item->comparison ? route('comparisons.show',$item->comparison) : route('comparisons.preview').'?'.$params }}">
                <span>{{ $item->is_saved ? 'SAVED · ' : '' }}{{ strtoupper($item->comparable_type) }}</span>
                <h3>{{ $item->title }}</h3>
                <small>Viewed {{ optional($item->last_viewed_at)->diffForHumans() }}</small>
                <i data-lucide="arrow-up-right"></i>
            </a>
        @empty
            <div class="inline-empty">No signed-in comparison history yet.</div>
        @endforelse
    </div>
    <div style="margin-top:20px">{{ $comparisons->links() }}</div>
</div></section>
@endsection
