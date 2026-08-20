@extends('layouts.admin')
@section('title','Community Comments')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/pages/content.css') }}">
@endpush

@section('content')
<div class="content-page">
<x-page-header
    title="Community Comment Moderation"
    subtitle="Review discussions from News, Articles, Comparisons, Benchmarks and Test Lab."
    :breadcrumb="['Users & Community','Comments']"
/>

@if(session('status'))
<div class="alert alert-success content-flash"><i data-lucide="check-circle-2"></i><span>{{ session('status') }}</span></div>
@endif

<section class="content-kpi-grid">
@foreach([
    ['All',$counts['all'],'messages-square'],
    ['Pending',$counts['pending'],'clock-3'],
    ['Published',$counts['published'],'badge-check'],
    ['Hidden',$counts['hidden'],'eye-off'],
    ['Spam',$counts['spam'],'shield-alert'],
] as [$label,$value,$icon])
<article class="content-kpi"><span><i data-lucide="{{ $icon }}"></i></span><div><small>{{ $label }}</small><strong>{{ number_format($value) }}</strong></div></article>
@endforeach
</section>

<form method="GET" class="card content-filter">
<div class="content-search"><i data-lucide="search"></i><input class="input" name="search" value="{{ request('search') }}" placeholder="Search comment or user..."></div>
<select class="select" name="status">
<option value="">All status</option>
@foreach(['pending','published','hidden','spam'] as $status)<option value="{{ $status }}" @selected(request('status')===$status)>{{ ucfirst($status) }}</option>@endforeach
</select>
<select class="select" name="trust">
<option value="">All trust levels</option>
@foreach(['normal','trusted','restricted'] as $trust)<option value="{{ $trust }}" @selected(request('trust')===$trust)>{{ ucfirst($trust) }}</option>@endforeach
</select>
<select class="select" name="type">
<option value="">All discussion types</option>
@foreach(['news','article','comparison','benchmark','test'] as $type)<option value="{{ $type }}" @selected(request('type')===$type)>{{ ucfirst($type) }}</option>@endforeach
</select>
<button class="btn btn-secondary"><i data-lucide="filter"></i>Filter</button>
</form>

<section class="card content-table-card">
<div class="content-section-head"><div><span class="content-eyebrow">Community Discussion</span><h2>Comments</h2><p>Hybrid moderation: new/risky posts queue here, while trusted clean discussion can publish automatically.</p></div><span class="content-count">{{ number_format($comments->total()) }} records</span></div>

@if($comments->count())
<div class="table-wrap">
<table class="data-table content-table">
<thead><tr><th>Comment</th><th>Surface</th><th>User / Trust</th><th>Status</th><th>Why queued</th><th>Risk</th><th>Reports</th><th>Moderation</th></tr></thead>
<tbody>
@foreach($comments as $comment)
<tr>
<td style="max-width:430px">
    <div class="content-record"><span class="content-record__icon"><i data-lucide="message-square"></i></span><div><strong>{{ \Illuminate\Support\Str::limit($comment->body,100) }}</strong><small>{{ $comment->created_at->diffForHumans() }}{{ $comment->parent_id ? ' · Reply' : '' }}</small></div></div>
</td>
<td><span class="content-type-pill">{{ ucfirst($comment->commentable_type) }}</span><small class="content-muted">#{{ $comment->commentable_id }}</small></td>
<td>
    <span class="content-muted">{{ $comment->user?->name ?? 'Deleted user' }}</span>
    @if($comment->user)
        <small class="content-muted">{{ ucfirst($comment->user->community_trust_level ?? 'normal') }} trust</small>
    @endif
</td>
<td><x-status-badge status="{{ ucfirst($comment->status) }}" type="{{ $comment->status==='published'?'pos':($comment->status==='spam'?'neg':'warn') }}" /></td>
<td>
    <span class="content-muted">{{ $comment->auto_published ? 'Auto-published' : ($comment->moderation_reason ?: 'Manual moderation') }}</span>
</td>
<td><span class="risk-pill is-{{ $comment->risk['level'] }}">{{ $comment->risk['score'] }}/100 · {{ ucfirst($comment->risk['level']) }}</span>@if($comment->risk['reasons'])<small class="content-muted">{{ implode(', ',$comment->risk['reasons']) }}</small>@endif</td>
<td>{{ $comment->reports_count }}</td>
<td>
<form method="POST" action="{{ route('admin.community.comments.update',$comment) }}" style="display:flex;gap:6px;align-items:center;flex-wrap:wrap">
@csrf @method('PATCH')
<select class="select" name="status">
@foreach(['pending','published','hidden','spam'] as $status)<option value="{{ $status }}" @selected($comment->status===$status)>{{ ucfirst($status) }}</option>@endforeach
</select>
<input class="input" name="moderation_note" value="{{ $comment->moderation_note }}" placeholder="Optional note" style="min-width:160px">
<button class="btn btn-secondary btn-sm">Save</button>
</form>
</td>
</tr>
@endforeach
</tbody>
</table>
</div>
<div class="content-pagination"><span>Showing {{ $comments->firstItem() ?? 0 }}–{{ $comments->lastItem() ?? 0 }} of {{ $comments->total() }}</span><div>{{ $comments->links() }}</div></div>
@else
<div class="content-empty"><span><i data-lucide="messages-square"></i></span><h3>No comments found</h3><p>Nothing matches the current moderation filters.</p></div>
@endif
</section>
</div>
@endsection
