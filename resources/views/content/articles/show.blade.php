@extends('layouts.admin')
@section('title', $article->title)

@push('styles')
<link rel="stylesheet" href="{{ asset('css/pages/content.css') }}">
@endpush

@section('content')
<div class="content-page content-article">
<x-page-header
    :title="$article->title"
    :subtitle="'By '.($article->author->name ?? 'Unknown').' · '.ucfirst($article->status).' · '.ucwords(str_replace('_',' ',$article->approval_status ?? 'draft'))"
    :breadcrumb="['Content','Articles',$article->title]"
>
<x-slot:actions>
    <a href="{{ route('admin.content.approval-workflow') }}" class="btn btn-secondary"><i data-lucide="workflow"></i>Workflow</a>
    @if(auth()->user()->canAccessModule('Content','Edit'))
    <a href="{{ route('admin.content.articles.editor.edit',$article->id) }}" class="btn btn-primary"><i data-lucide="pencil"></i>Edit</a>
    @endif
</x-slot:actions>
</x-page-header>

@if(session('status'))<div class="alert alert-success content-flash"><i data-lucide="check-circle-2"></i><span>{{ session('status') }}</span></div>@endif

<section class="card content-article__hero">
    <div>
        <div class="content-article__badges">
            <span class="content-state content-state--{{ $article->approval_status }}">{{ ucwords(str_replace('_',' ',$article->approval_status ?? 'draft')) }}</span>
            <x-status-badge status="{{ ucfirst($article->status) }}" type="{{ $article->status==='published'?'pos':($article->status==='scheduled'?'info':'neutral') }}" />
        </div>
        <h1>{{ $article->title }}</h1>
        <p>{{ $article->summary ?: 'No summary has been added yet.' }}</p>
    </div>
    <div class="content-article__signal"><span class="content-eyebrow">Workflow</span><strong>{{ $article->workflowEvents->count() }}</strong><small>Recorded events</small></div>
</section>

<div class="content-article__layout">
<main class="content-article__main">
    <section class="card content-article__body">
        @if($article->featured_image_path)<img src="{{ $article->featured_image_url }}" alt="{{ $article->title }}">@endif
        <div>{{ $article->content ?: 'No article body added yet.' }}</div>
    </section>

    <section class="card content-workflow-history">
        <div class="content-section-head"><div><span class="content-eyebrow">Audit Trail</span><h2>Workflow history</h2><p>Editorial decisions recorded against this article.</p></div><i data-lucide="history"></i></div>
        <div class="content-workflow-history__body">
        @forelse($article->workflowEvents as $event)
            <article class="content-event">
                <span><i data-lucide="git-commit-horizontal"></i></span>
                <div><strong>{{ ucwords(str_replace('_',' ',$event->action)) }}</strong><small>{{ ucwords(str_replace('_',' ',$event->from_status ?? 'created')) }} → {{ ucwords(str_replace('_',' ',$event->to_status)) }}</small>@if($event->comment)<p>{{ $event->comment }}</p>@endif<div>{{ $event->user->name ?? 'System' }} · {{ $event->created_at->format('M j, Y g:i A') }}</div></div>
            </article>
        @empty
            <div class="content-empty content-empty--small"><p>No workflow events yet.</p></div>
        @endforelse
        </div>
    </section>
</main>

<aside class="content-article__aside">
    <section class="card content-facts">
        <span class="content-eyebrow">Article Facts</span>
        <dl>
            <div><dt>Category</dt><dd>{{ $article->categoryTerm->name ?? $article->category ?? '—' }}</dd></div>
            <div><dt>Author</dt><dd>{{ $article->author->name ?? '—' }}</dd></div>
            <div><dt>Reviewer</dt><dd>{{ $article->reviewer->name ?? 'Unassigned' }}</dd></div>
            <div><dt>Approval</dt><dd>{{ ucwords(str_replace('_',' ',$article->approval_status ?? 'draft')) }}</dd></div>
            <div><dt>Publication</dt><dd>{{ ucfirst($article->status) }}</dd></div>
            <div><dt>Publish time</dt><dd>{{ $article->published_at?->format('M j, Y g:i A') ?? '—' }}</dd></div>
        </dl>
    </section>

    @if($article->tagTerms->isNotEmpty())
    <section class="card content-tags"><span class="content-eyebrow">Tags</span><div>@foreach($article->tagTerms as $tag)<span>{{ $tag->name }}</span>@endforeach</div></section>
    @endif

    @if($article->relatedToolTerms->isNotEmpty() || $article->relatedModelTerms->isNotEmpty())
    <section class="card content-tags"><span class="content-eyebrow">Related AI</span><div>@foreach($article->relatedToolTerms as $tool)<a href="{{ route('admin.tools.show',$tool->id) }}">{{ $tool->name }}</a>@endforeach @foreach($article->relatedModelTerms as $model)<a href="{{ route('admin.models.show',$model->id) }}">{{ $model->name }}</a>@endforeach</div></section>
    @endif
</aside>
</div>
</div>
@endsection
