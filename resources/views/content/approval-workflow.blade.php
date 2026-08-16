@extends('layouts.admin')
@section('title', 'Approval Workflow')
@section('content')

<x-page-header title="Content Approval Workflow" subtitle="Real Draft → Review → Changes → Approved → Scheduled/Published workflow" :breadcrumb="['Content','Approval Workflow']" />
@if(session('status'))<div class="alert alert-success" style="margin-bottom:16px;">{{ session('status') }}</div>@endif
@if($errors->any())<div class="alert alert-danger" style="margin-bottom:16px;">{{ $errors->first() }}</div>@endif

@php
$columns = [
'draft' => ['Draft','file-edit'],
'in_review' => ['In Review','search-check'],
'needs_changes' => ['Needs Changes','message-square-warning'],
'approved' => ['Approved','badge-check'],
];
@endphp

<div style="display:grid;grid-template-columns:repeat(4,minmax(245px,1fr));gap:14px;overflow-x:auto;align-items:start;">
@foreach($columns as $key => [$label,$icon])
@php $items = $articlesByStage->get($key, collect()); @endphp
<div>
<div class="flex items-center justify-between" style="margin-bottom:10px;"><span style="font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:.04em;color:var(--text-lo);"><i data-lucide="{{ $icon }}" style="width:13px;height:13px;vertical-align:-2px;"></i> {{ $label }}</span><span class="badge badge-neutral">{{ $items->count() }}</span></div>
<div style="display:flex;flex-direction:column;gap:10px;">
@forelse($items as $article)
<div class="card card-pad">
<a href="{{ route('admin.content.articles.show',$article->id) }}" style="font-size:12.5px;font-weight:700;line-height:1.4;display:block;margin-bottom:8px;">{{ $article->title }}</a>
<div class="cell-sub" style="margin-bottom:10px;">{{ $article->author->name ?? '—' }} @if($article->reviewer) · Reviewer: {{ $article->reviewer->name }} @endif</div>
@if($key==='draft')
<form method="POST" action="{{ route('admin.content.approval.submit',$article->id) }}">@csrf<select class="select" name="reviewer_id" style="width:100%;margin-bottom:7px;"><option value="">Assign reviewer (optional)</option>@foreach($reviewers as $r)<option value="{{ $r->id }}">{{ $r->name }}</option>@endforeach</select><button class="btn btn-primary btn-sm" style="width:100%;justify-content:center;">Submit for Review</button></form>
@elseif($key==='in_review')
<form method="POST" action="{{ route('admin.content.approval.approve',$article->id) }}" style="margin-bottom:7px;">@csrf<input class="input" name="comment" placeholder="Approval note (optional)" style="margin-bottom:6px;"><button class="btn btn-primary btn-sm" style="width:100%;justify-content:center;">Approve</button></form>
<form method="POST" action="{{ route('admin.content.approval.request-changes',$article->id) }}">@csrf<textarea class="input" name="comment" rows="2" required placeholder="What needs changing?" style="margin-bottom:6px;"></textarea><button class="btn btn-secondary btn-sm" style="width:100%;justify-content:center;">Request Changes</button></form>
@elseif($key==='needs_changes')
<form method="POST" action="{{ route('admin.content.approval.resubmit',$article->id) }}">@csrf<input class="input" name="comment" placeholder="What was updated?" style="margin-bottom:6px;"><button class="btn btn-primary btn-sm" style="width:100%;justify-content:center;">Resubmit</button></form>
@else
<div class="cell-sub" style="margin-bottom:8px;">Approved {{ $article->approved_at?->diffForHumans() ?? '' }}</div><a href="{{ route('admin.content.articles.editor.edit',$article->id) }}" class="btn btn-secondary btn-sm" style="width:100%;justify-content:center;"><i data-lucide="calendar-clock"></i> Schedule / Publish</a>
@endif
</div>
@empty<div class="card card-pad text-sub" style="text-align:center;">No articles in this stage.</div>@endforelse
</div></div>
@endforeach
</div>

<div class="grid-12" style="margin-top:24px;">
<div class="col-5 card card-pad"><div class="section-title">Scheduled & Published</div>@forelse($publishedArticles as $article)<div class="flex items-center justify-between" style="padding:9px 0;border-bottom:1px solid var(--border-soft);"><div><a href="{{ route('admin.content.articles.show',$article->id) }}" style="font-size:13px;font-weight:650;">{{ $article->title }}</a><div class="cell-sub">{{ $article->published_at?->format('M j, Y g:i A') ?? 'No date' }}</div></div><x-status-badge status="{{ ucfirst($article->status) }}" type="{{ $article->status==='published'?'pos':'info' }}" /></div>@empty<div class="text-sub">No scheduled/published content yet.</div>@endforelse</div>
<div class="col-7 card card-pad"><div class="section-title">Recent Approval History</div>@forelse($history as $event)<div class="flex items-start gap-12" style="padding:9px 0;border-bottom:1px solid var(--border-soft);"><span class="dot-indicator" style="background:var(--brand-1);margin-top:5px;"></span><div style="flex:1;"><div style="font-size:13px;"><b>{{ $event->article->title ?? 'Deleted article' }}</b> — {{ ucwords(str_replace('_',' ',$event->action)) }}</div>@if($event->comment)<div class="cell-sub">{{ $event->comment }}</div>@endif<div class="cell-sub">{{ $event->user->name ?? 'System' }} · {{ $event->created_at->format('M j, g:i A') }}</div></div></div>@empty<div class="text-sub">No workflow history yet.</div>@endforelse</div>
</div>
@endsection
