@extends('frontend.layouts.app')
@section('title','My Comments — My AI Orbit')
@push('styles')<link rel="stylesheet" href="{{ asset('css/frontend/account.css') }}">@endpush

@section('content')
<section class="account-page"><div class="account-shell">
@include('frontend.account._sidebar')

<div class="account-main">
<header class="account-subhead">
    <div>
        <span class="account-kicker"><i data-lucide="message-square"></i> COMMUNITY DISCUSSION</span>
        <h1>My comments & replies</h1>
        <p>Track discussions you joined across News, Articles, Comparisons, Benchmarks and Test Lab.</p>
    </div>
</header>

<div class="account-review-list">
@forelse($comments as $comment)
<article class="account-review-card">
    <div class="review-logo"><i data-lucide="{{ $comment->parent_id ? 'reply' : 'message-square' }}"></i></div>
    <div class="review-main">
        <div class="review-top">
            <div>
                <span>{{ ucfirst($comment->commentable_type) }} {{ $comment->parent_id ? 'reply' : 'comment' }}</span>
                <h2>{{ \Illuminate\Support\Str::limit($comment->body,90) }}</h2>
            </div>
            <b class="status {{ $comment->status }}">{{ ucfirst($comment->status) }}</b>
        </div>
        <p>{{ $comment->body }}</p>
        <div class="review-rating">
            <span>Updated {{ $comment->updated_at->diffForHumans() }}</span>
            <span>{{ $comment->reply_count }} published replies</span>
            <span>{{ $comment->reports_count }} reports</span>
        </div>
        @if($comment->moderation_note)
        <div class="moderation-note"><i data-lucide="message-square-warning"></i><span><b>Moderator note</b>{{ $comment->moderation_note }}</span></div>
        @endif
    </div>
</article>
@empty
<div class="account-empty big">
    <i data-lucide="messages-square"></i>
    <strong>You have not joined a discussion yet.</strong>
    <span>Comment on AI News, Articles, Comparisons, Benchmarks or Test Lab experiments.</span>
    <a href="{{ route('news.index') }}">Explore AI News</a>
</div>
@endforelse
</div>

<div class="account-pagination">{{ $comments->links() }}</div>
</div>
</div></section>
@endsection
