@extends('layouts.admin')
@section('title', $context === 'community' ? 'Review Moderation' : 'AI Reviews')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/pages/content.css') }}">
@endpush

@section('content')
@php
$communityMode = $context === 'community';
$indexRoute = $communityMode ? 'admin.community.reviews.index' : 'admin.content.reviews.index';
$showRoute = $communityMode ? 'admin.community.reviews.show' : 'admin.content.reviews.show';
@endphp
<div class="content-page">
<x-page-header
    :title="$communityMode ? 'User Review Moderation' : 'AI Reviews'"
    :subtitle="$communityMode ? 'Moderate community ratings before they influence public trust.' : 'Manage editorial and user-submitted review intelligence.'"
    :breadcrumb="$communityMode ? ['Users & Community','Review Moderation'] : ['Content','Reviews']"
>
@unless($communityMode)
<x-slot:actions>
@if(auth()->user()->canAccessModule('Reviews','Add'))<a href="{{ route('admin.content.reviews.editor') }}" class="btn btn-primary"><i data-lucide="plus"></i>Add Editorial Review</a>@endif
</x-slot:actions>
@endunless
</x-page-header>

@if(session('status'))<div class="alert alert-success content-flash"><i data-lucide="check-circle-2"></i><span>{{ session('status') }}</span></div>@endif
@if($errors->any())<div class="alert alert-danger content-flash"><i data-lucide="circle-alert"></i><span>{{ $errors->first() }}</span></div>@endif

<section class="content-kpi-grid">
@foreach([['All',$counts['all'],'messages-square'],['Pending',$counts['pending'],'clock-3'],['Published',$counts['published'],'badge-check'],['Flagged',$counts['flagged'],'flag']] as [$label,$value,$icon])
<article class="content-kpi"><span><i data-lucide="{{ $icon }}"></i></span><div><small>{{ $label }}</small><strong>{{ number_format($value) }}</strong></div></article>
@endforeach
</section>

<nav class="content-tabs">
<a href="{{ route($indexRoute) }}" class="{{ !request('status') ? 'is-active' : '' }}">All</a>
<a href="{{ route($indexRoute,['status'=>'pending']) }}" class="{{ request('status')==='pending'?'is-active':'' }}">Pending</a>
<a href="{{ route($indexRoute,['status'=>'published']) }}" class="{{ request('status')==='published'?'is-active':'' }}">Published</a>
<a href="{{ route($indexRoute,['status'=>'flagged']) }}" class="{{ request('status')==='flagged'?'is-active':'' }}">Flagged</a>
</nav>

<form method="GET" class="card content-filter content-filter--reviews">
<input type="hidden" name="status" value="{{ request('status') }}">
<div class="content-search"><i data-lucide="search"></i><input class="input" name="search" value="{{ request('search') }}" placeholder="Search review, user, tool or model..."></div>
<select class="select" name="tool_id"><option value="">All tools</option>@foreach($tools as $tool)<option value="{{ $tool->id }}" @selected((string)request('tool_id')===(string)$tool->id)>{{ $tool->name }}</option>@endforeach</select>
<select class="select" name="model_id"><option value="">All models</option>@foreach($models as $model)<option value="{{ $model->id }}" @selected((string)request('model_id')===(string)$model->id)>{{ $model->name }}</option>@endforeach</select>
@if(!$communityMode)<select class="select" name="type"><option value="">All types</option><option value="user" @selected(request('type')==='user')>User</option><option value="editorial" @selected(request('type')==='editorial')>Editorial</option></select>@endif
<select class="select" name="rating"><option value="">Any rating</option><option value="5" @selected(request('rating')==='5')>5+</option><option value="4" @selected(request('rating')==='4')>4+</option><option value="3" @selected(request('rating')==='3')>3+</option></select>
<button class="btn btn-secondary"><i data-lucide="filter"></i>Filter</button>
</form>

<section class="card content-table-card">
<div class="content-section-head"><div><span class="content-eyebrow">Review Ledger</span><h2>{{ $communityMode ? 'Community reviews' : 'Review management' }}</h2><p>Moderation state, product context and rating quality at a glance.</p></div><span class="content-count">{{ number_format($reviews->total()) }} records</span></div>
@if($reviews->count())
<div class="table-wrap"><table class="data-table content-table"><thead><tr><th>Review</th><th>Reviewed item</th><th>Type</th><th>Rating</th><th>Status</th><th>Moderator</th><th></th></tr></thead><tbody>
@foreach($reviews as $review)
<tr>
<td><div class="content-record"><span class="content-record__icon"><i data-lucide="message-square-text"></i></span><div><a href="{{ route($showRoute,$review->id) }}">{{ $review->verdict ?: \Illuminate\Support\Str::limit($review->body ?: 'Star rating only',55) }}</a><small>{{ $review->user->name ?? ($review->review_type==='editorial'?'Editorial Team':'Deleted user') }}</small></div></div></td>
<td><span class="content-muted">{{ $review->model?->name ?? $review->tool?->name ?? 'Deleted item' }}</span><small class="content-muted">{{ $review->model_id ? 'AI Model' : 'AI Tool' }}</small></td>
<td><span class="content-type-pill">{{ ucfirst($review->review_type) }}</span></td>
<td><span class="content-rating"><i data-lucide="star"></i>{{ number_format((float)$review->rating,1) }}</span></td>
<td><x-status-badge status="{{ ucfirst($review->status) }}" type="{{ $review->status==='published'?'pos':($review->status==='flagged'?'neg':'warn') }}" /></td>
<td><span class="content-muted">{{ $review->moderator?->name ?? '—' }}</span></td>
<td><a href="{{ route($showRoute,$review->id) }}" class="icon-btn"><i data-lucide="eye"></i></a></td>
</tr>
@endforeach
</tbody></table></div>
<div class="content-pagination"><span>Showing {{ $reviews->firstItem() ?? 0 }}–{{ $reviews->lastItem() ?? 0 }} of {{ $reviews->total() }}</span><div>{{ $reviews->links() }}</div></div>
@else
<div class="content-empty"><span><i data-lucide="messages-square"></i></span><h3>No reviews found</h3><p>Adjust the filters or add an editorial review.</p></div>
@endif
</section>
</div>
@endsection
