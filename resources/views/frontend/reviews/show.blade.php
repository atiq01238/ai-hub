@extends('frontend.layouts.app')
@section('title',($review->verdict ?: $item->name.' Review').' | AI Orbit')
@section('meta_description',\Illuminate\Support\Str::limit($review->body ?: 'Independent AI Orbit review of '.$item->name,155))
@push('styles')<link rel="stylesheet" href="{{ asset('css/frontend/content.css') }}">@endpush

@section('content')
@php
    $logo = $item->logo_url ?? \App\Support\MediaUrl::placeholder();
    $pros = collect($review->pros ?? [])->filter();
    $cons = collect($review->cons ?? [])->filter();
    $breakdown = collect($review->rating_breakdown ?? []);
    $companyName = $item->company?->name ?? 'Independent';
    $itemLabel = $itemType === 'model' ? 'AI Model' : 'AI Tool';
    $itemRoute = $itemType === 'model' ? route('models.show',$item) : route('tools.show',$item);
    $itemSummary = $itemType === 'model'
        ? ($item->capability_notes ?: $item->overview)
        : ($item->description ?: $item->short_description ?: $item->overview);
    $writeReviewRoute = $itemType === 'model'
        ? route('reviews.models.create',$item)
        : route('reviews.create',$item);
@endphp

<section class="review-detail-hero">
    <div class="content-wrap">
        <div class="content-breadcrumb">
            <a href="{{ route('home') }}">Home</a><i data-lucide="chevron-right"></i>
            <a href="{{ route('reviews.index') }}">Reviews</a><i data-lucide="chevron-right"></i>
            <span>{{ $item->name }}</span>
        </div>
        <div class="review-detail-main">
            <div class="review-product-id">
                <img src="{{ $logo }}" alt="{{ $item->name }} logo">
                <div>
                    <span class="review-type type-{{ $review->review_type }}">{{ $review->review_type==='editorial'?'AI Orbit Editorial':'Community Review' }}</span>
                    <h1>{{ $review->verdict ?: $item->name.' Review' }}</h1>
                    <p>{{ $item->name }} · {{ $companyName }} · {{ $itemLabel }} · reviewed {{ $review->created_at->format('M j, Y') }}</p>
                </div>
            </div>
            <div class="review-score"><strong>{{ number_format((float)$review->rating,1) }}</strong><span>★★★★★</span><small>out of 5.0</small></div>
        </div>
    </div>
</section>

<section class="review-detail-body">
    <div class="content-wrap review-reading-grid">
        <main>
            <div class="review-verdict-box">
                <span>VERDICT</span>
                <h2>{{ $review->verdict ?: 'Published user assessment' }}</h2>
                <p>{{ $review->body ?: 'This published review contains a rating without an additional written assessment.' }}</p>
            </div>

            @if($breakdown->isNotEmpty())
                <section class="review-breakdown">
                    <div class="block-heading"><span>SCORECARD</span><h2>Rating breakdown</h2></div>
                    <div class="score-grid">
                        @foreach($breakdown as $label=>$score)
                            <div><span>{{ ucwords(str_replace('_',' ',$label)) }}</span><strong>{{ number_format((float)$score,1) }}</strong><div><i style="width:{{ min(100,max(0,((float)$score/5)*100)) }}%"></i></div></div>
                        @endforeach
                    </div>
                </section>
            @endif

            @if($pros->isNotEmpty() || $cons->isNotEmpty())
                <div class="pros-cons">
                    <section><span class="positive"><i data-lucide="circle-check-big"></i> Strengths</span>@forelse($pros as $pro)<p><i data-lucide="check"></i>{{ $pro }}</p>@empty<p>No structured strengths were supplied.</p>@endforelse</section>
                    <section><span class="negative"><i data-lucide="circle-minus"></i> Trade-offs</span>@forelse($cons as $con)<p><i data-lucide="minus"></i>{{ $con }}</p>@empty<p>No structured trade-offs were supplied.</p>@endforelse</section>
                </div>
            @endif

            <section class="review-tool-cta">
                <img src="{{ $logo }}" alt="{{ $item->name }} logo">
                <div><small>EXPLORE THE {{ strtoupper($itemLabel) }}</small><h3>{{ $item->name }}</h3><p>{{ \Illuminate\Support\Str::limit($itemSummary,120) }}</p></div>
                <a href="{{ $itemRoute }}">Full {{ strtolower($itemLabel) }} profile <i data-lucide="arrow-right"></i></a>
            </section>

            @if($relatedReviews->isNotEmpty())
                <section>
                    <div class="block-heading"><span>MORE REVIEWS</span><h2>Related assessments</h2></div>
                    <div class="related-review-grid">
                        @foreach($relatedReviews as $related)
                            @php($relatedItem = $related->model ?: $related->tool)
                            <a href="{{ route('reviews.show',$related) }}">
                                <span>★ {{ number_format((float)$related->rating,1) }}</span>
                                <h3>{{ $related->verdict ?: $relatedItem?->name.' review' }}</h3>
                                <p>{{ \Illuminate\Support\Str::limit($related->body,95) }}</p>
                                <small>{{ $relatedItem?->name }} · {{ $related->created_at->diffForHumans() }}</small>
                            </a>
                        @endforeach
                    </div>
                </section>
            @endif
        </main>

        <aside class="article-aside">
            <div class="side-card">
                <span class="side-label">REVIEW SNAPSHOT</span>
                <div class="review-snapshot">
                    <div><span>This review</span><strong>{{ number_format((float)$review->rating,1) }}/5</strong></div>
                    <div><span>{{ $item->name }} average</span><strong>{{ number_format((float)($itemReviewStats->average ?? 0),1) }}/5</strong></div>
                    <div><span>Published reviews</span><strong>{{ (int)($itemReviewStats->total ?? 0) }}</strong></div>
                </div>
            </div>
            <div class="side-card">
                <span class="side-label">REVIEWER</span>
                <h3>{{ $review->user?->name ?? 'AI Orbit Editorial' }}</h3>
                <p>{{ $review->review_type==='editorial' ? 'Editorial review produced through AI Orbit content workflow.' : 'Moderated community review published through AI Orbit.' }}</p>
            </div>
            @auth
                <a class="write-review-cta" href="{{ $writeReviewRoute }}"><i data-lucide="pen-line"></i><span><strong>Used {{ $item->name }}?</strong><small>Write or update your review</small></span><i data-lucide="arrow-right"></i></a>
            @endauth
        </aside>
    </div>
</section>
@endsection
