@extends('layouts.admin')
@section('title', 'AI News Intelligence')

@section('content')

<x-page-header title="AI News Intelligence Center" subtitle="{{ $items->total() }} news items" :breadcrumb="['AI Intelligence', 'News Feed']">
    <x-slot:actions>
        <a href="{{ route('admin.news.duplicates') }}" class="btn btn-secondary btn-sm"><i data-lucide="copy"></i> Duplicates</a>
        <a href="{{ route('admin.news.create') }}" class="btn btn-primary btn-sm"><i data-lucide="plus"></i> Add News</a>
    </x-slot:actions>
</x-page-header>

@if (session('status'))
    <div class="alert alert-success" style="margin-bottom:16px;">{{ session('status') }}</div>
@endif
@isset($notice)
    <div class="alert alert-warning" style="margin-bottom:16px;">{{ $notice }}</div>
@endisset

<form method="GET" class="filter-bar">
    <div class="input-search" style="background:var(--surface); border:1px solid var(--border); border-radius:var(--radius-sm); padding:8px 12px;">
        <i data-lucide="search"></i>
        <input type="text" name="search" value="{{ request('search') }}" placeholder="Search headlines...">
    </div>
    <select class="select" name="category" onchange="this.form.submit()">
        <option value="">All Categories</option>
        @foreach (['Breaking News','New Model','Product Launch','Product Update','New Feature','Pricing Change','AI Review','Benchmark','Research','Funding','Acquisition','Security','Policy','Regulation'] as $cat)
            <option value="{{ $cat }}" @selected(request('category') === $cat)>{{ $cat }}</option>
        @endforeach
    </select>
    <select class="select" name="company_id" onchange="this.form.submit()">
        <option value="">All Companies</option>
        @foreach ($companies as $company)
            <option value="{{ $company->id }}" @selected((string) request('company_id') === (string) $company->id)>{{ $company->name }}</option>
        @endforeach
    </select>
    <button type="submit" class="btn btn-secondary btn-sm">Filter</button>
    @if (request()->anyFilled(['search', 'category', 'company_id']))
        <a href="{{ route('admin.news.index') }}" class="btn btn-ghost btn-sm">Clear</a>
    @endif
</form>

<div style="display:flex; flex-direction:column; gap:14px;">
@forelse ($items as $item)
<div class="card card-pad">
    <div class="flex gap-12" style="align-items:flex-start;">
        <div class="thumb lg">{{ substr($item->source ?? $item->headline, 0, 2) }}</div>
        <div style="flex:1; min-width:0;">
            <div class="flex items-center gap-8" style="margin-bottom:6px; flex-wrap:wrap;">
                @if ($item->category)<span class="badge badge-neutral">{{ $item->category }}</span>@endif
                <span class="badge badge-{{ $item->sentiment === 'positive' ? 'pos' : ($item->sentiment === 'negative' ? 'neg' : 'neutral') }}">{{ ucfirst($item->sentiment) }}</span>
                <span class="badge {{ $item->verification_status === 'verified' ? 'badge-pos' : ($item->verification_status === 'unverified' ? 'badge-neg' : 'badge-warn') }}">{{ str_replace('_', ' ', ucfirst($item->verification_status)) }}</span>
                <span class="cell-sub">{{ $item->source ?? 'Unknown source' }} · {{ $item->published_at?->diffForHumans() ?? ucfirst($item->status) }}</span>
            </div>
            <a href="{{ route('admin.news.show', $item->id) }}" style="text-decoration:none; color:inherit;">
                <div style="font-size:15px; font-weight:650; margin-bottom:6px;">{{ $item->headline }}</div>
            </a>
            <div class="text-sub" style="font-size:13px; margin-bottom:6px;">{{ \Illuminate\Support\Str::limit($item->summary, 160) }}</div>
            @if ($item->why_it_matters)
            <div style="font-size:12.5px; color:var(--brand-3);"><i data-lucide="lightbulb" style="width:12px;height:12px; vertical-align:-2px;"></i> Why it matters: {{ $item->why_it_matters }}</div>
            @endif
            <div class="flex items-center gap-8" style="margin-top:10px; flex-wrap:wrap;">
                @if ($item->company)<span class="badge-neutral badge" style="border:none;">{{ $item->company->name }}</span>@endif
                @foreach ($item->related_tools ?? [] as $tool)<span class="badge-neutral badge" style="border:none;">{{ $tool }}</span>@endforeach
            </div>
        </div>
        <div style="text-align:right; flex-shrink:0;">
            <x-score-meter :value="$item->importance" :segments="6" />
            <div class="cell-sub" style="margin-top:4px;">Importance</div>
        </div>
    </div>
    <div class="divider"></div>
    <div class="flex gap-8" style="flex-wrap:wrap;">
        <a href="{{ route('admin.news.show', $item->id) }}" class="btn btn-ghost btn-sm"><i data-lucide="eye"></i> View</a>
        <a href="{{ route('admin.news.edit', $item->id) }}" class="btn btn-ghost btn-sm"><i data-lucide="pencil"></i> Edit</a>
        @if ($item->source_url)
            <a href="{{ $item->source_url }}" target="_blank" class="btn btn-ghost btn-sm"><i data-lucide="external-link"></i> View Source</a>
        @endif
        <form action="{{ route('admin.news.destroy', $item->id) }}" method="POST" onsubmit="return confirm('Delete this news item?')">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-ghost btn-sm"><i data-lucide="trash-2"></i> Delete</button>
        </form>
    </div>
</div>
@empty
<div class="card card-pad text-sub" style="text-align:center; padding:32px;">No news items yet — add your first one.</div>
@endforelse
</div>

<div class="pager" style="margin-top:16px;">
    <span>Showing {{ $items->firstItem() ?? 0 }}–{{ $items->lastItem() ?? 0 }} of {{ $items->total() }}</span>
    <div class="pager-btns">{{ $items->links() }}</div>
</div>

@endsection
