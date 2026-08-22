@extends('frontend.layouts.app')
@section('title','My Reviews — My AI Hub')
@push('styles')<link rel="stylesheet" href="{{ asset('css/frontend/account.css') }}">@endpush
@section('content')
<section class="account-page"><div class="account-shell">
@include('frontend.account._sidebar')
<div class="account-main">
    <header class="account-subhead"><div><span class="account-kicker"><i data-lucide="star"></i> COMMUNITY CONTRIBUTIONS</span><h1>My reviews & ratings</h1><p>Track published, pending and moderated reviews you have submitted for AI tools and models.</p></div><a href="{{ route('reviews.index') }}"><i data-lucide="star"></i>Browse reviews</a></header>
    <div class="account-mini-stats">
        <div><small>Total</small><strong>{{ $reviews->total() }}</strong></div>
        <div><small>Published</small><strong>{{ (int)($counts['published'] ?? 0) }}</strong></div>
        <div><small>Pending</small><strong>{{ (int)($counts['pending'] ?? 0) }}</strong></div>
        <div><small>Rejected</small><strong>{{ (int)($counts['rejected'] ?? 0) }}</strong></div>
    </div>
    <div class="account-review-list">
        @forelse($reviews as $review)
        <article class="account-review-card">
            <div class="review-logo">{{ strtoupper(substr($review->model?->name ?? $review->tool?->name ?? 'AI',0,2)) }}</div>
            <div class="review-main">
                <div class="review-top"><div><span>{{ $review->model?->company?->name ?? $review->tool?->company?->name ?? 'AI Hub' }}</span><h2>{{ $review->model?->name ?? $review->tool?->name ?? 'AI item' }}</h2></div><b class="status {{ $review->status }}">{{ ucfirst($review->status) }}</b></div>
                <div class="review-rating"><strong>★ {{ number_format((float)$review->rating,1) }}</strong><span>Updated {{ $review->updated_at->diffForHumans() }}</span></div>
                <p>{{ $review->body ?: 'Rating submitted without a written review.' }}</p>
                @if($review->moderation_note)<div class="moderation-note"><i data-lucide="message-square-warning"></i><span><b>Moderator note</b>{{ $review->moderation_note }}</span></div>@endif
                <div class="review-actions">
                    @if($review->model)
                        <a href="{{ route('reviews.models.create',$review->model) }}"><i data-lucide="pencil"></i>Edit review</a>
                    @elseif($review->tool)
                        <a href="{{ route('reviews.create',$review->tool) }}"><i data-lucide="pencil"></i>Edit review</a>
                    @endif
                    @if($review->status === 'published')<a href="{{ route('reviews.show',$review) }}"><i data-lucide="external-link"></i>View published</a>@endif
                </div>
            </div>
        </article>
        @empty
        <div class="account-empty big"><i data-lucide="star"></i><strong>You have not reviewed an AI product yet.</strong><span>Your ratings help other people choose better AI products.</span><a href="{{ route('tools.index') }}">Explore AI Tools</a></div>
        @endforelse
    </div>
    <div class="account-pagination">{{ $reviews->links() }}</div>
</div></div></section>
@endsection
