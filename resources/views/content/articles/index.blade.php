@extends('layouts.admin')
@section('title', $pageTitle ?? 'News Articles')
@section('content')

<x-page-header title="{{ $pageTitle ?? 'Content Management System' }}" subtitle="{{ $articles->total() }} articles" :breadcrumb="['Content', $pageTitle ?? 'News Articles']">
<x-slot:actions><a href="{{ route('admin.content.articles.editor.create') }}" class="btn btn-primary btn-sm"><i data-lucide="plus"></i> Create Article</a></x-slot:actions>
</x-page-header>
@if(session('status'))<div class="alert alert-success" style="margin-bottom:16px;">{{ session('status') }}</div>@endif

<div class="tabs">
<a href="{{ route('admin.content.articles.index') }}" class="tab {{ !request('status')&&!request('approval_status')?'is-active':'' }}">All</a>
<a href="{{ route('admin.content.articles.index',['status'=>'draft']) }}" class="tab {{ request('status')==='draft'?'is-active':'' }}">Drafts</a>
<a href="{{ route('admin.content.articles.index',['approval_status'=>'in_review']) }}" class="tab {{ request('approval_status')==='in_review'?'is-active':'' }}">In Review</a>
<a href="{{ route('admin.content.articles.index',['approval_status'=>'needs_changes']) }}" class="tab {{ request('approval_status')==='needs_changes'?'is-active':'' }}">Needs Changes</a>
<a href="{{ route('admin.content.articles.index',['status'=>'scheduled']) }}" class="tab {{ request('status')==='scheduled'?'is-active':'' }}">Scheduled</a>
<a href="{{ route('admin.content.articles.index',['status'=>'published']) }}" class="tab {{ request('status')==='published'?'is-active':'' }}">Published</a>
</div>

<form method="GET" class="filter-bar">
<input type="hidden" name="status" value="{{ request('status') }}"><input type="hidden" name="approval_status" value="{{ request('approval_status') }}">
<div class="input-search" style="background:var(--surface);border:1px solid var(--border);border-radius:var(--radius-sm);padding:8px 12px;"><i data-lucide="search"></i><input type="text" name="search" value="{{ request('search') }}" placeholder="Search title or summary..."></div>
<select class="select" name="category_id" style="max-width:200px;"><option value="">All categories</option>@foreach($categories as $category)<option value="{{ $category->id }}" @selected(request('category_id')==$category->id)>{{ $category->name }}</option>@endforeach</select>
<button class="btn btn-secondary btn-sm">Filter</button>
</form>

<div class="card"><div class="table-wrap"><table class="data-table"><thead><tr><th>Title</th><th>Category</th><th>Author</th><th>Approval</th><th>Publication</th><th>Publish Time</th><th></th></tr></thead><tbody>
@forelse($articles as $article)
<tr>
<td><a href="{{ route('admin.content.articles.show',$article->id) }}"><b>{{ $article->title }}</b></a></td>
<td class="text-sub">{{ $article->categoryTerm->name ?? $article->category ?? '—' }}</td>
<td class="text-sub">{{ $article->author->name ?? '—' }}</td>
<td><span class="badge badge-neutral">{{ ucwords(str_replace('_',' ',$article->approval_status ?? 'draft')) }}</span></td>
<td><x-status-badge status="{{ ucfirst($article->status) }}" type="{{ $article->status==='published'?'pos':($article->status==='scheduled'?'info':'neutral') }}" /></td>
<td class="cell-sub">{{ $article->published_at?->format('M j, g:i A') ?? '—' }}</td>
<td><div class="flex gap-8"><a href="{{ route('admin.content.articles.editor.edit',$article->id) }}" class="icon-btn" style="width:28px;height:28px;"><i data-lucide="pencil" style="width:14px;height:14px;"></i></a></div></td>
</tr>
@empty<tr><td colspan="7" class="text-sub" style="text-align:center;padding:32px;">No articles match these filters.</td></tr>@endforelse
</tbody></table></div><div class="pager"><span>Showing {{ $articles->firstItem() ?? 0 }}–{{ $articles->lastItem() ?? 0 }} of {{ $articles->total() }}</span><div class="pager-btns">{{ $articles->links() }}</div></div></div>
@endsection
