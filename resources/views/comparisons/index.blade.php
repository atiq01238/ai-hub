@extends('layouts.admin')
@section('title', 'Comparisons')

@section('content')

<x-page-header title="Comparisons" subtitle="{{ $comparisons->total() }} comparisons" :breadcrumb="['Comparison & Benchmarks', 'Comparisons']">
    <x-slot:actions><a href="{{ route('admin.comparisons.builder') }}" class="btn btn-primary btn-sm"><i data-lucide="plus"></i> New Comparison</a></x-slot:actions>
</x-page-header>

@if (session('status'))
    <div class="alert alert-success" style="margin-bottom:16px;">{{ session('status') }}</div>
@endif

<form method="GET" class="filter-bar">
    <div class="input-search" style="background:var(--surface); border:1px solid var(--border); border-radius:var(--radius-sm); padding:8px 12px;">
        <i data-lucide="search"></i>
        <input type="text" name="search" value="{{ request('search') }}" placeholder="Search comparisons...">
    </div>
    <select class="select" name="type" onchange="this.form.submit()">
        <option value="">All Types</option>
        <option value="tool" @selected(request('type') === 'tool')>Tool vs Tool</option>
        <option value="model" @selected(request('type') === 'model')>Model vs Model</option>
    </select>
    <select class="select" name="status" onchange="this.form.submit()">
        <option value="">All Status</option>
        <option value="published" @selected(request('status') === 'published')>Published</option>
        <option value="draft" @selected(request('status') === 'draft')>Draft</option>
    </select>
    <button type="submit" class="btn btn-secondary btn-sm">Filter</button>
</form>

<div class="card">
    <div class="table-wrap">
    <table class="data-table">
        <thead><tr><th>Comparison</th><th>Type</th><th>Views</th><th>Last Updated</th><th>Status</th><th></th></tr></thead>
        <tbody>
        @forelse ($comparisons as $comparison)
        <tr>
            <td><a href="{{ route('admin.comparisons.show', $comparison->id) }}"><b>{{ $comparison->title }}</b></a></td>
            <td class="text-sub">{{ $comparison->comparable_type === 'tool' ? 'Tool vs Tool' : 'Model vs Model' }}</td>
            <td class="mono">{{ number_format($comparison->views) }}</td>
            <td class="cell-sub">{{ $comparison->updated_at->format('M j') }}</td>
            <td><x-status-badge status="{{ ucfirst($comparison->status) }}" type="{{ $comparison->status === 'published' ? 'pos' : 'neutral' }}" /></td>
            <td>
                <div class="flex gap-8">
                    <a href="{{ route('admin.comparisons.edit', $comparison->id) }}" class="icon-btn" style="width:28px;height:28px;"><i data-lucide="pencil" style="width:14px;height:14px;"></i></a>
                    <form action="{{ route('admin.comparisons.destroy', $comparison->id) }}" method="POST" onsubmit="return confirm('Delete this comparison?')" style="display:inline;">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="icon-btn" style="width:28px;height:28px;"><i data-lucide="trash-2" style="width:14px;height:14px;"></i></button>
                    </form>
                </div>
            </td>
        </tr>
        @empty
        <tr><td colspan="6" class="text-sub" style="text-align:center; padding:32px;">No comparisons yet — create your first one.</td></tr>
        @endforelse
        </tbody>
    </table>
    </div>
    <div class="pager">
        <span>Showing {{ $comparisons->firstItem() ?? 0 }}–{{ $comparisons->lastItem() ?? 0 }} of {{ $comparisons->total() }}</span>
        <div class="pager-btns">{{ $comparisons->links() }}</div>
    </div>
</div>
@endsection
