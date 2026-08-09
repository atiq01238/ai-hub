@extends('layouts.admin')
@section('title', 'News Articles')

@section('content')

<x-page-header title="Content Management System" subtitle="{{ $articles->total() }} articles" :breadcrumb="['Content', 'News Articles']">
    <x-slot:actions><a href="{{ route('admin.content.articles.editor.create') }}" class="btn btn-primary btn-sm"><i data-lucide="plus"></i> Create Article</a></x-slot:actions>
</x-page-header>

@if (session('status'))
    <div class="alert alert-success" style="margin-bottom:16px;">{{ session('status') }}</div>
@endif

<div class="tabs">
    <a href="{{ route('admin.content.articles.index') }}" class="tab {{ !request('status') ? 'is-active' : '' }}">All</a>
    <a href="{{ route('admin.content.articles.index', ['status' => 'draft']) }}" class="tab {{ request('status')==='draft' ? 'is-active' : '' }}">Drafts</a>
    <a href="{{ route('admin.content.articles.index', ['status' => 'published']) }}" class="tab {{ request('status')==='published' ? 'is-active' : '' }}">Published</a>
    <a href="{{ route('admin.content.articles.index', ['status' => 'scheduled']) }}" class="tab {{ request('status')==='scheduled' ? 'is-active' : '' }}">Scheduled</a>
</div>

<form method="GET" class="filter-bar">
    <input type="hidden" name="status" value="{{ request('status') }}">
    <div class="input-search" style="background:var(--surface); border:1px solid var(--border); border-radius:var(--radius-sm); padding:8px 12px;">
        <i data-lucide="search"></i>
        <input type="text" name="search" value="{{ request('search') }}" placeholder="Search articles...">
    </div>
    <button type="submit" class="btn btn-secondary btn-sm">Search</button>
</form>

<div class="card">
    <div class="table-wrap">
    <table class="data-table">
        <thead><tr><th>Title</th><th>Category</th><th>Author</th><th>Status</th><th>Published</th><th></th></tr></thead>
        <tbody>
        @forelse ($articles as $article)
        <tr>
            <td><a href="{{ route('admin.content.articles.show', $article->id) }}"><b>{{ $article->title }}</b></a></td>
            <td class="text-sub">{{ $article->category ?? '—' }}</td>
            <td class="text-sub">{{ $article->author->name ?? '—' }}</td>
            <td>
                <x-status-badge
                    status="{{ ucfirst($article->status) }}"
                    type="{{ $article->status === 'published' ? 'pos' : ($article->status === 'scheduled' ? 'info' : 'neutral') }}" />
            </td>
            <td class="cell-sub">{{ $article->published_at?->format('M j, g:i A') ?? '—' }}</td>
            <td>
                <div class="flex gap-8">
                    <a href="{{ route('admin.content.articles.editor.edit', $article->id) }}" class="icon-btn" style="width:28px;height:28px;"><i data-lucide="pencil" style="width:14px;height:14px;"></i></a>
                    <form action="{{ route('admin.content.articles.destroy', $article->id) }}" method="POST" onsubmit="return confirm('Delete this article?')" style="display:inline;">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="icon-btn" style="width:28px;height:28px;"><i data-lucide="trash-2" style="width:14px;height:14px;"></i></button>
                    </form>
                </div>
            </td>
        </tr>
        @empty
        <tr><td colspan="6" class="text-sub" style="text-align:center; padding:32px;">No articles yet — create your first one.</td></tr>
        @endforelse
        </tbody>
    </table>
    </div>
    <div class="pager">
        <span>Showing {{ $articles->firstItem() ?? 0 }}–{{ $articles->lastItem() ?? 0 }} of {{ $articles->total() }}</span>
        <div class="pager-btns">{{ $articles->links() }}</div>
    </div>
</div>
@endsection
