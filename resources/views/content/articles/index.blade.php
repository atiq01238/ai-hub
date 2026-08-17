@extends('layouts.admin')
@section('title', $pageTitle ?? 'Articles')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/pages/content.css') }}">
@endpush

@section('content')
@php
    $title = $pageTitle ?? 'Editorial Articles';
    $isGuides = ($pageTitle ?? '') === 'Guides';
@endphp

<div class="content-page">
    <x-page-header
        :title="$title"
        :subtitle="$isGuides ? 'Manage long-form guides using the same governed editorial workflow.' : 'Create, review, schedule and publish governed editorial content.'"
        :breadcrumb="['Content', $title]"
    >
        <x-slot:actions>
            @if(auth()->user()->canAccessModule('Content', 'Add'))
                <a href="{{ route('admin.content.articles.editor.create') }}" class="btn btn-primary">
                    <i data-lucide="plus"></i>
                    Create Article
                </a>
            @endif
        </x-slot:actions>
    </x-page-header>

    @if(session('status'))
        <div class="alert alert-success content-flash"><i data-lucide="check-circle-2"></i><span>{{ session('status') }}</span></div>
    @endif

    <nav class="content-tabs">
        <a href="{{ route('admin.content.articles.index') }}" class="{{ !$isGuides && !request('status') && !request('approval_status') ? 'is-active' : '' }}"><i data-lucide="files"></i>All</a>
        <a href="{{ route('admin.content.articles.drafts') }}" class="{{ request('status') === 'draft' && !$isGuides ? 'is-active' : '' }}"><i data-lucide="file-pen-line"></i>Drafts</a>
        <a href="{{ route('admin.content.articles.index',['approval_status'=>'in_review']) }}" class="{{ request('approval_status') === 'in_review' ? 'is-active' : '' }}"><i data-lucide="search-check"></i>In Review</a>
        <a href="{{ route('admin.content.articles.index',['approval_status'=>'needs_changes']) }}" class="{{ request('approval_status') === 'needs_changes' ? 'is-active' : '' }}"><i data-lucide="message-square-warning"></i>Needs Changes</a>
        <a href="{{ route('admin.content.articles.index',['status'=>'scheduled']) }}" class="{{ request('status') === 'scheduled' ? 'is-active' : '' }}"><i data-lucide="calendar-clock"></i>Scheduled</a>
        <a href="{{ route('admin.content.articles.index',['status'=>'published']) }}" class="{{ request('status') === 'published' ? 'is-active' : '' }}"><i data-lucide="send"></i>Published</a>
        <a href="{{ route('admin.content.guides') }}" class="{{ $isGuides ? 'is-active' : '' }}"><i data-lucide="book-open-text"></i>Guides</a>
        <a href="{{ route('admin.content.approval-workflow') }}"><i data-lucide="workflow"></i>Workflow</a>
    </nav>

    <form method="GET" class="card content-filter">
        @if(request('status'))<input type="hidden" name="status" value="{{ request('status') }}">@endif
        @if(request('approval_status'))<input type="hidden" name="approval_status" value="{{ request('approval_status') }}">@endif
        <div class="content-search">
            <i data-lucide="search"></i>
            <input class="input" type="search" name="search" value="{{ request('search') }}" placeholder="Search title or summary...">
        </div>
        <select class="select" name="category_id">
            <option value="">All categories</option>
            @foreach($categories as $category)
                <option value="{{ $category->id }}" @selected((string)request('category_id') === (string)$category->id)>{{ $category->name }}</option>
            @endforeach
        </select>
        <button class="btn btn-secondary" type="submit"><i data-lucide="filter"></i>Filter</button>
    </form>

    <section class="card content-table-card">
        <div class="content-section-head">
            <div>
                <span class="content-eyebrow">Editorial Library</span>
                <h2>{{ $title }}</h2>
                <p>{{ number_format($articles->total()) }} content records in this view.</p>
            </div>
            <span class="content-count">{{ number_format($articles->total()) }} records</span>
        </div>

        @if($articles->count())
            <div class="table-wrap">
                <table class="data-table content-table">
                    <thead>
                        <tr><th>Article</th><th>Category</th><th>Author</th><th>Approval</th><th>Publication</th><th>Publish Time</th><th></th></tr>
                    </thead>
                    <tbody>
                    @foreach($articles as $article)
                        <tr>
                            <td>
                                <div class="content-record">
                                    <span class="content-record__icon"><i data-lucide="newspaper"></i></span>
                                    <div>
                                        <a href="{{ route('admin.content.articles.show',$article->id) }}">{{ $article->title }}</a>
                                        <small>{{ \Illuminate\Support\Str::limit($article->summary ?: 'No summary added.', 86) }}</small>
                                    </div>
                                </div>
                            </td>
                            <td><span class="content-muted">{{ $article->categoryTerm->name ?? $article->category ?? '—' }}</span></td>
                            <td><span class="content-muted">{{ $article->author->name ?? '—' }}</span></td>
                            <td><span class="content-state content-state--{{ $article->approval_status }}">{{ ucwords(str_replace('_',' ',$article->approval_status ?? 'draft')) }}</span></td>
                            <td><x-status-badge status="{{ ucfirst($article->status) }}" type="{{ $article->status==='published'?'pos':($article->status==='scheduled'?'info':'neutral') }}" /></td>
                            <td><span class="content-muted">{{ $article->published_at?->format('M j, Y g:i A') ?? '—' }}</span></td>
                            <td>
                                @if(auth()->user()->canAccessModule('Content', 'Edit'))
                                    <a href="{{ route('admin.content.articles.editor.edit',$article->id) }}" class="icon-btn" title="Edit"><i data-lucide="pencil"></i></a>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
            <div class="content-pagination"><span>Showing {{ $articles->firstItem() ?? 0 }}–{{ $articles->lastItem() ?? 0 }} of {{ $articles->total() }}</span><div>{{ $articles->links() }}</div></div>
        @else
            <div class="content-empty"><span><i data-lucide="files"></i></span><h3>No content found</h3><p>Adjust the filters or create a new editorial article.</p></div>
        @endif
    </section>
</div>
@endsection
