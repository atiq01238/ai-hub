@extends('layouts.admin')
@section('title','Approval Workflow')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/pages/content.css') }}">
@endpush

@section('content')
@php
$columns=['draft'=>['Draft','file-pen-line'],'in_review'=>['In Review','search-check'],'needs_changes'=>['Needs Changes','message-square-warning'],'approved'=>['Approved','badge-check']];
@endphp
<div class="content-page content-approval">
<x-page-header title="Content Approval Workflow" subtitle="Governed Draft → Review → Changes → Approved → Scheduled/Published editorial operations." :breadcrumb="['Content','Approval Workflow']">
<x-slot:actions><a href="{{ route('admin.content.articles.index') }}" class="btn btn-secondary"><i data-lucide="library"></i>Articles</a>@if(auth()->user()->canAccessModule('Content','Add'))<a href="{{ route('admin.content.articles.editor.create') }}" class="btn btn-primary"><i data-lucide="plus"></i>Create Article</a>@endif</x-slot:actions>
</x-page-header>
@if(session('status'))<div class="alert alert-success content-flash"><i data-lucide="check-circle-2"></i><span>{{ session('status') }}</span></div>@endif
@if($errors->any())<div class="alert alert-danger content-flash"><i data-lucide="circle-alert"></i><span>{{ $errors->first() }}</span></div>@endif

<section class="content-board">
@foreach($columns as $key=>[$label,$icon])
@php $items=$articlesByStage->get($key,collect()); @endphp
<div class="content-board__column">
<div class="content-board__head"><span><i data-lucide="{{ $icon }}"></i>{{ $label }}</span><b>{{ $items->count() }}</b></div>
<div class="content-board__cards">
@forelse($items as $article)
<article class="card content-work-card">
<a href="{{ route('admin.content.articles.show',$article->id) }}">{{ $article->title }}</a>
<div class="content-work-card__meta">{{ $article->author->name ?? '—' }}@if($article->reviewer) · Reviewer: {{ $article->reviewer->name }}@endif</div>
@if($key==='draft')
<form method="POST" action="{{ route('admin.content.approval.submit',$article->id) }}">@csrf<select class="select" name="reviewer_id"><option value="">Assign reviewer (optional)</option>@foreach($reviewers as $reviewer)<option value="{{ $reviewer->id }}">{{ $reviewer->name }}</option>@endforeach</select><button class="btn btn-primary btn-sm"><i data-lucide="send"></i>Submit for Review</button></form>
@elseif($key==='in_review')
<form method="POST" action="{{ route('admin.content.approval.approve',$article->id) }}">@csrf<input class="input" name="comment" placeholder="Approval note (optional)"><button class="btn btn-primary btn-sm"><i data-lucide="badge-check"></i>Approve</button></form>
<form method="POST" action="{{ route('admin.content.approval.request-changes',$article->id) }}">@csrf<textarea class="textarea" name="comment" rows="2" required placeholder="What needs changing?"></textarea><button class="btn btn-secondary btn-sm"><i data-lucide="message-square-warning"></i>Request Changes</button></form>
@elseif($key==='needs_changes')
<form method="POST" action="{{ route('admin.content.approval.resubmit',$article->id) }}">@csrf<input class="input" name="comment" placeholder="What was updated?"><button class="btn btn-primary btn-sm"><i data-lucide="rotate-ccw"></i>Resubmit</button></form>
@else
<div class="content-work-card__approved"><i data-lucide="badge-check"></i>Approved {{ $article->approved_at?->diffForHumans() ?? '' }}</div><a href="{{ route('admin.content.articles.editor.edit',$article->id) }}" class="btn btn-secondary btn-sm"><i data-lucide="calendar-clock"></i>Schedule / Publish</a>
@endif
</article>
@empty
<div class="card content-board__empty">No articles in this stage.</div>
@endforelse
</div>
</div>
@endforeach
</section>

<div class="content-approval__lower">
<section class="card content-panel">
<div class="content-section-head"><div><span class="content-eyebrow">Distribution</span><h2>Scheduled & Published</h2><p>Approved content that has entered the publication pipeline.</p></div><i data-lucide="calendar-days"></i></div>
<div class="content-list">@forelse($publishedArticles as $article)<a href="{{ route('admin.content.articles.show',$article->id) }}"><div><strong>{{ $article->title }}</strong><small>{{ $article->published_at?->format('M j, Y g:i A') ?? 'No date' }}</small></div><x-status-badge status="{{ ucfirst($article->status) }}" type="{{ $article->status==='published'?'pos':'info' }}" /></a>@empty<div class="content-empty content-empty--small"><p>No scheduled or published content yet.</p></div>@endforelse</div>
</section>

<section class="card content-panel">
<div class="content-section-head"><div><span class="content-eyebrow">Audit</span><h2>Recent approval history</h2><p>Latest editorial workflow transitions.</p></div><i data-lucide="history"></i></div>
<div class="content-list">@forelse($history as $event)<div class="content-history-row"><span><i data-lucide="git-commit-horizontal"></i></span><div><strong>{{ $event->article->title ?? 'Deleted article' }}</strong><small>{{ ucwords(str_replace('_',' ',$event->action)) }} · {{ $event->user->name ?? 'System' }} · {{ $event->created_at->format('M j, g:i A') }}</small>@if($event->comment)<p>{{ $event->comment }}</p>@endif</div></div>@empty<div class="content-empty content-empty--small"><p>No workflow history yet.</p></div>@endforelse</div>
</section>
</div>
</div>
@endsection
