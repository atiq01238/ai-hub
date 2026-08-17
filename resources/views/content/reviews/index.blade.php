@extends('layouts.admin')
@section('title', $context === 'community' ? 'Review Moderation' : 'AI Reviews')

@section('content')
@php
    $communityMode = $context === 'community';
    $indexRoute = $communityMode ? 'admin.community.reviews.index' : 'admin.content.reviews.index';
    $showRoute = $communityMode ? 'admin.community.reviews.show' : 'admin.content.reviews.show';
    $baseFilters = array_filter([
        'search' => request('search'),
        'tool_id' => request('tool_id'),
        'rating' => request('rating'),
        'type' => $communityMode ? null : request('type'),
    ]);
@endphp

<x-page-header
    title="{{ $communityMode ? 'User Review Moderation' : 'AI Review Management' }}"
    subtitle="{{ $communityMode ? 'Moderate community ratings before they affect public tool scores' : 'Manage editorial and user-submitted reviews' }}"
    :breadcrumb="$communityMode ? ['Users & Community', 'Review Moderation'] : ['Content', 'Reviews']">
    @unless ($communityMode)
        <x-slot:actions>
            <a href="{{ route('admin.content.reviews.editor') }}" class="btn btn-primary btn-sm"><i data-lucide="plus"></i> Add Editorial Review</a>
        </x-slot:actions>
    @endunless
</x-page-header>

@if (session('status'))
    <div class="alert alert-success" style="margin-bottom:16px;">{{ session('status') }}</div>
@endif
@if ($errors->any())
    <div class="alert alert-danger" style="margin-bottom:16px;">{{ $errors->first() }}</div>
@endif

<div class="kpi-grid">
    <x-kpi-card icon="messages-square" label="All Reviews" value="{{ number_format($counts['all']) }}" />
    <x-kpi-card icon="clock-3" label="Pending" value="{{ number_format($counts['pending']) }}" />
    <x-kpi-card icon="badge-check" label="Published" value="{{ number_format($counts['published']) }}" />
    <x-kpi-card icon="flag" label="Flagged" value="{{ number_format($counts['flagged']) }}" />
</div>

<div class="tabs">
    <a class="tab {{ !request('status') ? 'is-active' : '' }}" href="{{ route($indexRoute, $baseFilters) }}">All {{ $counts['all'] }}</a>
    <a class="tab {{ request('status') === 'pending' ? 'is-active' : '' }}" href="{{ route($indexRoute, $baseFilters + ['status' => 'pending']) }}">Pending {{ $counts['pending'] }}</a>
    <a class="tab {{ request('status') === 'published' ? 'is-active' : '' }}" href="{{ route($indexRoute, $baseFilters + ['status' => 'published']) }}">Published {{ $counts['published'] }}</a>
    <a class="tab {{ request('status') === 'flagged' ? 'is-active' : '' }}" href="{{ route($indexRoute, $baseFilters + ['status' => 'flagged']) }}">Flagged {{ $counts['flagged'] }}</a>
</div>

<form method="GET" class="filter-bar">
    <input type="hidden" name="status" value="{{ request('status') }}">
    <div class="input-search" style="background:var(--surface); border:1px solid var(--border); border-radius:var(--radius-sm); padding:8px 12px; flex:1; max-width:360px;">
        <i data-lucide="search"></i>
        <input name="search" value="{{ request('search') }}" placeholder="Search review, reviewer or tool...">
    </div>
    <select class="select" name="tool_id">
        <option value="">All tools</option>
        @foreach ($tools as $tool)
            <option value="{{ $tool->id }}" @selected((string) request('tool_id') === (string) $tool->id)>{{ $tool->name }}</option>
        @endforeach
    </select>
    @unless ($communityMode)
        <select class="select" name="type">
            <option value="">All review types</option>
            <option value="user" @selected(request('type') === 'user')>User</option>
            <option value="editorial" @selected(request('type') === 'editorial')>Editorial</option>
        </select>
    @endunless
    <select class="select" name="rating">
        <option value="">Any rating</option>
        <option value="5" @selected(request('rating') === '5')>5.0 only</option>
        <option value="4" @selected(request('rating') === '4')>4.0+</option>
        <option value="3" @selected(request('rating') === '3')>3.0+</option>
    </select>
    <button class="btn btn-secondary btn-sm"><i data-lucide="list-filter"></i> Apply</button>
    @if (request('search') || request('tool_id') || request('rating') || request('type'))
        <a href="{{ route($indexRoute, array_filter(['status' => request('status')])) }}" class="btn btn-ghost btn-sm">Reset</a>
    @endif
</form>

<div class="card">
    <div class="table-wrap">
        <table class="data-table">
            <thead><tr><th>Tool & Review</th><th>Reviewer</th><th>Type</th><th>Rating</th><th>Status</th><th>Submitted</th><th></th></tr></thead>
            <tbody>
            @forelse ($reviews as $review)
                <tr>
                    <td>
                        <a href="{{ route($showRoute, $review->id) }}"><b>{{ $review->tool->name ?? 'Deleted tool' }}</b></a>
                        <div class="cell-sub">{{ \Illuminate\Support\Str::limit($review->verdict ?: $review->body, 70) ?: 'Star rating only' }}</div>
                    </td>
                    <td>
                        @if ($review->user)
                            <a href="{{ route('admin.users.show', $review->user->id) }}">{{ $review->user->name }}</a>
                            <div class="cell-sub">{{ $review->user->email }}</div>
                        @else
                            <span class="text-sub">Editorial Team / Deleted user</span>
                        @endif
                    </td>
                    <td><span class="badge {{ $review->review_type === 'editorial' ? 'badge-violet' : 'badge-neutral' }}">{{ ucfirst($review->review_type ?? 'user') }}</span></td>
                    <td><b style="font-size:15px;">{{ number_format((float) $review->rating, 1) }}</b><span class="cell-sub"> / 5</span></td>
                    <td>
                        <x-status-badge status="{{ ucfirst($review->status) }}" type="{{ $review->status === 'published' ? 'pos' : ($review->status === 'flagged' ? 'neg' : 'warn') }}" />
                        @if ($review->moderator)<div class="cell-sub">by {{ $review->moderator->name }}</div>@endif
                    </td>
                    <td><div>{{ $review->created_at->format('M j, Y') }}</div><div class="cell-sub">{{ $review->created_at->diffForHumans() }}</div></td>
                    <td>
                        <div class="flex gap-8">
                            <a href="{{ route($showRoute, $review->id) }}" class="icon-btn" title="Open moderation detail"><i data-lucide="eye"></i></a>
                            @if ($review->status !== 'published' && auth()->user()->canAccessModule('Reviews', 'Publish'))
                                <form method="POST" action="{{ route('admin.content.reviews.approve', $review->id) }}" onsubmit="return confirm('Approve and publish this review?');">
                                    @csrf
                                    <button class="icon-btn" title="Approve"><i data-lucide="check"></i></button>
                                </form>
                            @endif
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="7" class="text-sub" style="text-align:center; padding:40px;">No reviews match these filters.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="pager">
        <span>Showing {{ $reviews->firstItem() ?? 0 }}–{{ $reviews->lastItem() ?? 0 }} of {{ $reviews->total() }}</span>
        <div class="pager-btns">{{ $reviews->onEachSide(1)->links() }}</div>
    </div>
</div>
@endsection
