@extends('layouts.admin')
@section('title', $article->title)
@php use Illuminate\Support\Facades\Storage; @endphp
@section('content')
<x-page-header title="{{ $article->title }}" subtitle="By {{ $article->author->name ?? '—' }} · {{ ucfirst($article->status) }} · Approval: {{ ucwords(str_replace('_',' ',$article->approval_status ?? 'draft')) }}" :breadcrumb="['Content','News Articles',$article->title]">
<x-slot:actions><a href="{{ route('admin.content.articles.editor.edit',$article->id) }}" class="btn btn-primary btn-sm"><i data-lucide="pencil"></i> Edit</a></x-slot:actions>
</x-page-header>
@if(session('status'))<div class="alert alert-success" style="margin-bottom:16px;">{{ session('status') }}</div>@endif

<div class="grid-12">
<div class="col-8">
<div class="card card-pad" style="margin-bottom:16px;">@if($article->featured_image_path)<img src="{{ Storage::url($article->featured_image_path) }}" alt="" style="width:100%;border-radius:10px;margin-bottom:16px;">@endif @if($article->summary)<p class="text-sub" style="font-size:13.5px;font-style:italic;margin-bottom:14px;">{{ $article->summary }}</p>@endif <div style="font-size:14px;line-height:1.8;white-space:pre-line;">{{ $article->content ?: 'No content added yet.' }}</div></div>
<div class="card card-pad"><div class="section-title">Workflow History</div>@forelse($article->workflowEvents as $event)<div class="flex items-start gap-12" style="padding:10px 0;border-bottom:1px solid var(--border-soft);"><span class="dot-indicator" style="background:var(--brand-1);margin-top:5px;"></span><div style="flex:1;"><div style="font-size:13px;font-weight:650;">{{ ucwords(str_replace('_',' ',$event->action)) }} <span class="cell-sub">→ {{ ucwords(str_replace('_',' ',$event->to_status)) }}</span></div>@if($event->comment)<div class="cell-sub" style="margin-top:3px;">{{ $event->comment }}</div>@endif<div class="cell-sub" style="margin-top:3px;">{{ $event->user->name ?? 'System' }} · {{ $event->created_at->format('M j, Y g:i A') }}</div></div></div>@empty<div class="text-sub">No workflow events yet.</div>@endforelse</div>
</div>
<div class="col-4">
<div class="card card-pad" style="margin-bottom:16px;"><div class="section-title">Details</div>
<div class="flex justify-between" style="padding:9px 0;border-bottom:1px solid var(--border-soft);"><span class="cell-sub">Category</span><b>{{ $article->categoryTerm->name ?? $article->category ?? '—' }}</b></div>
<div class="flex justify-between" style="padding:9px 0;border-bottom:1px solid var(--border-soft);"><span class="cell-sub">Reviewer</span><b>{{ $article->reviewer->name ?? 'Unassigned' }}</b></div>
<div class="flex justify-between" style="padding:9px 0;border-bottom:1px solid var(--border-soft);"><span class="cell-sub">Approval</span><span class="badge badge-neutral">{{ ucwords(str_replace('_',' ',$article->approval_status ?? 'draft')) }}</span></div>
<div class="flex justify-between" style="padding:9px 0;"><span class="cell-sub">Publication</span><x-status-badge status="{{ ucfirst($article->status) }}" type="{{ $article->status==='published'?'pos':'neutral' }}" /></div></div>
@if($article->tagTerms->isNotEmpty())<div class="card card-pad" style="margin-bottom:16px;"><div class="cell-sub" style="margin-bottom:8px;">Tags</div><div class="flex gap-8" style="flex-wrap:wrap;">@foreach($article->tagTerms as $tag)<span class="badge badge-neutral">{{ $tag->name }}</span>@endforeach</div></div>@endif
@if($article->relatedToolTerms->isNotEmpty() || $article->relatedModelTerms->isNotEmpty())<div class="card card-pad"><div class="section-title">Related AI</div>@foreach($article->relatedToolTerms as $tool)<span class="badge badge-neutral" style="margin:3px;">{{ $tool->name }}</span>@endforeach @foreach($article->relatedModelTerms as $model)<span class="badge badge-neutral" style="margin:3px;">{{ $model->name }}</span>@endforeach</div>@endif
</div></div>
@endsection
