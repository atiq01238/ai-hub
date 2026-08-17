@extends('layouts.admin')
@section('title', 'Review Detail')

@section('content')
@php
    $communityMode = ($context ?? 'content') === 'community';
    $indexRoute = $communityMode ? 'admin.community.reviews.index' : 'admin.content.reviews.index';
@endphp

<x-page-header
    title="Review — {{ $review->tool->name ?? 'Deleted tool' }}"
    subtitle="{{ ucfirst($review->review_type ?? 'user') }} review · submitted {{ $review->created_at->format('M j, Y') }}"
    :breadcrumb="$communityMode ? ['Users & Community', 'Review Moderation', '#' . $review->id] : ['Content', 'Reviews', '#' . $review->id]">
    <x-slot:actions>
        <a href="{{ route($indexRoute) }}" class="btn btn-ghost btn-sm"><i data-lucide="arrow-left"></i> Reviews</a>
        @if ($review->status !== 'published' && auth()->user()->canAccessModule('Reviews', 'Publish'))
            <form action="{{ route('admin.content.reviews.approve', $review->id) }}" method="POST">
                @csrf
                <button type="submit" class="btn btn-primary btn-sm"><i data-lucide="badge-check"></i> Approve & publish</button>
            </form>
        @endif
    </x-slot:actions>
</x-page-header>

@if (session('status'))
    <div class="alert alert-success" style="margin-bottom:16px;">{{ session('status') }}</div>
@endif
@if ($errors->any())
    <div class="alert alert-danger" style="margin-bottom:16px;">{{ $errors->first() }}</div>
@endif

<div class="grid-12">
    <div class="col-8">
        <div class="card card-pad" style="margin-bottom:16px;">
            <div class="flex items-center gap-12" style="margin-bottom:18px;">
                <div class="thumb lg">{{ mb_strtoupper(mb_substr($review->tool->name ?? 'AI', 0, 2)) }}</div>
                <div>
                    <b style="font-size:16px;">{{ $review->tool->name ?? 'Deleted tool' }}</b>
                    <div class="cell-sub">
                        Reviewed by
                        @if($review->user)<a href="{{ route('admin.users.show', $review->user->id) }}">{{ $review->user->name }}</a>@else{{ $review->review_type === 'editorial' ? 'Editorial Team' : 'Deleted user' }}@endif
                    </div>
                </div>
                <div style="margin-left:auto; text-align:right;">
                    <div class="font-display" style="font-size:30px; font-weight:700;">{{ number_format((float) $review->rating, 1) }}</div>
                    <div class="cell-sub">out of 5</div>
                </div>
            </div>

            @if ($review->verdict)
                <div class="section-title">{{ $review->verdict }}</div>
            @endif
            <p class="text-sub" style="font-size:13.5px; line-height:1.8; white-space:pre-line;">{{ $review->body ?: 'No written review — star rating only.' }}</p>
        </div>

        @if (!empty($review->pros) || !empty($review->cons))
            <div class="grid-2" style="margin-bottom:16px;">
                <div class="card card-pad">
                    <div class="section-title" style="color:var(--pos);">Pros</div>
                    <ul style="margin:0; padding-left:18px; font-size:13px; color:var(--text-md); line-height:1.9;">
                        @forelse ($review->pros ?? [] as $pro)<li>{{ $pro }}</li>@empty<li>None listed</li>@endforelse
                    </ul>
                </div>
                <div class="card card-pad">
                    <div class="section-title" style="color:var(--neg);">Cons</div>
                    <ul style="margin:0; padding-left:18px; font-size:13px; color:var(--text-md); line-height:1.9;">
                        @forelse ($review->cons ?? [] as $con)<li>{{ $con }}</li>@empty<li>None listed</li>@endforelse
                    </ul>
                </div>
            </div>
        @endif

        @if (!empty($review->rating_breakdown))
            <div class="card card-pad">
                <div class="section-title">Rating Breakdown</div>
                <div class="grid-2">
                    @foreach ($review->rating_breakdown as $label => $value)
                        <div style="margin-bottom:12px;">
                            <div class="flex items-center justify-between" style="margin-bottom:6px;"><span class="text-sub">{{ ucfirst(str_replace('_', ' ', $label)) }}</span><span class="mono">{{ number_format((float) $value, 1) }}</span></div>
                            <div class="progress"><span style="width:{{ min(100, max(0, ((float) $value / 5) * 100)) }}%;"></span></div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </div>

    <div class="col-4">
        <div class="card card-pad" style="margin-bottom:16px;">
            <div class="section-title">Moderation Status</div>
            <div class="flex items-center justify-between" style="padding-bottom:12px; border-bottom:1px solid var(--border-soft);">
                <span class="cell-sub">Current state</span>
                <x-status-badge status="{{ ucfirst($review->status) }}" type="{{ $review->status === 'published' ? 'pos' : ($review->status === 'flagged' ? 'neg' : 'warn') }}" />
            </div>
            <div class="flex items-center justify-between" style="padding:12px 0; border-bottom:1px solid var(--border-soft);"><span class="cell-sub">Type</span><span>{{ ucfirst($review->review_type) }}</span></div>
            <div class="flex items-center justify-between" style="padding:12px 0; border-bottom:1px solid var(--border-soft);"><span class="cell-sub">Community reports</span><span>{{ $review->reports_count }}</span></div>
            <div class="flex items-center justify-between" style="padding:12px 0;"><span class="cell-sub">Moderator</span><span>{{ $review->moderator?->name ?? '—' }}</span></div>
            @if ($review->moderation_note)
                <div class="divider"></div>
                <div class="cell-sub">Moderation note</div>
                <p class="text-sub" style="line-height:1.6; margin:7px 0 0; white-space:pre-line;">{{ $review->moderation_note }}</p>
            @endif
        </div>

        @if ($review->status !== 'flagged' && auth()->user()->canAccessModule('Reviews', 'Edit'))
            <div class="card card-pad" style="margin-bottom:16px;">
                <div class="section-title">Flag Review</div>
                <form method="POST" action="{{ route('admin.content.reviews.flag', $review->id) }}">
                    @csrf
                    <div class="form-field" style="margin-bottom:10px;">
                        <label>Moderation reason</label>
                        <textarea class="input" name="moderation_note" rows="4" required placeholder="Spam, abuse, conflict of interest, unverifiable claim..."></textarea>
                    </div>
                    <button class="btn btn-secondary btn-sm" style="width:100%; justify-content:center;"><i data-lucide="flag"></i> Flag and unpublish</button>
                </form>
            </div>
        @endif

        @if (auth()->user()->canAccessModule('Reviews', 'Delete'))
        <div class="card card-pad" style="border-color:rgba(248,113,113,.25);">
            <div class="section-title">Recovery-Safe Removal</div>
            <p class="cell-sub">The review will be soft-deleted so its record can be recovered from the database.</p>
            <form method="POST" action="{{ route('admin.content.reviews.destroy', $review->id) }}" onsubmit="return confirm('Move this review to the recovery bin?');">
                @csrf
                @method('DELETE')
                <input type="hidden" name="context" value="{{ $communityMode ? 'community' : 'content' }}">
                <button class="btn btn-danger btn-sm" style="width:100%; justify-content:center;"><i data-lucide="trash-2"></i> Remove review</button>
            </form>
        </div>
        @endif
    </div>
</div>
@endsection
