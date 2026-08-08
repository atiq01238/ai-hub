@extends('layouts.admin')
@section('title', 'AI Tools')

@section('content')

<x-page-header title="AI Tool Management" subtitle="{{ $tools->total() }} tools tracked" :breadcrumb="['AI Management', 'AI Tools']">
    <x-slot:actions>
        <a href="{{ route('admin.tools.create') }}" class="btn btn-primary btn-sm"><i data-lucide="plus"></i> Add AI Tool</a>
    </x-slot:actions>
</x-page-header>

@if (session('status'))
    <div class="alert alert-success" style="margin-bottom:16px;">{{ session('status') }}</div>
@endif

<div class="filter-bar">
    <div class="input-search" style="background:var(--surface); border:1px solid var(--border); border-radius:var(--radius-sm); padding:8px 12px;">
        <i data-lucide="search"></i><input type="text" placeholder="Search tools...">
    </div>
    <select class="select"><option>All Categories</option><option>Chatbot</option><option>Image Gen</option><option>Video Gen</option><option>Coding</option></select>
    <select class="select"><option>All Companies</option><option>OpenAI</option><option>Anthropic</option><option>Google</option></select>
    <select class="select"><option>All Pricing</option><option>Free</option><option>Freemium</option><option>Paid</option></select>
    <select class="select"><option>All Status</option><option>Published</option><option>Draft</option><option>Archived</option></select>
    <select class="select"><option>Rating: Any</option><option>4.5+</option><option>4.0+</option></select>
</div>

<div class="card">
    <div class="table-wrap">
    <table class="data-table">
        <thead>
            <tr>
                <th><input type="checkbox"></th>
                <th>Tool</th><th>Company</th><th>Category</th><th>Pricing</th><th>Rating</th><th>Popularity</th><th>Last Updated</th><th>Status</th><th></th>
            </tr>
        </thead>
        <tbody>
        @forelse ($tools as $tool)
        <tr>
            <td><input type="checkbox"></td>
            <td>
                <div class="row-media">
                    @if ($tool->logo_path)
                        <img src="{{ \Illuminate\Support\Facades\Storage::url($tool->logo_path) }}" alt="" class="thumb" style="object-fit:cover;">
                    @else
                        <div class="thumb">{{ substr($tool->name, 0, 2) }}</div>
                    @endif
                    <a href="{{ route('admin.tools.show', $tool->id) }}"><b>{{ $tool->name }}</b></a>
                </div>
            </td>
            <td class="text-sub">{{ $tool->company->name ?? '—' }}</td>
            <td class="text-sub">{{ $tool->category->name ?? '—' }}</td>
            <td><span class="badge badge-neutral">{{ implode(', ', $tool->pricing_models ?? []) ?: '—' }}</span></td>
            <td class="mono"><i data-lucide="star" style="width:12px;height:12px;color:var(--warn);vertical-align:-2px;"></i> {{ number_format($tool->rating, 1) }}</td>
            <td>
                <div class="progress" style="width:70px;"><span style="width:{{ $tool->popularity }}%;"></span></div>
            </td>
            <td class="cell-sub">{{ $tool->updated_at->format('M j') }}</td>
            <td><x-status-badge :status="ucfirst($tool->status)" :type="$tool->status==='published' ? 'pos' : ($tool->status==='draft' ? 'neutral' : 'warn')" /></td>
            <td>
                <div class="flex gap-8">
                    <a href="{{ route('admin.tools.show', $tool->id) }}" class="icon-btn" style="width:28px;height:28px;"><i data-lucide="eye" style="width:14px;height:14px;"></i></a>
                    <a href="{{ route('admin.tools.edit', $tool->id) }}" class="icon-btn" style="width:28px;height:28px;"><i data-lucide="pencil" style="width:14px;height:14px;"></i></a>
                    <form action="{{ route('admin.tools.destroy', $tool->id) }}" method="POST" onsubmit="return confirm('Delete this tool?')" style="display:inline;">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="icon-btn" style="width:28px;height:28px;"><i data-lucide="trash-2" style="width:14px;height:14px;"></i></button>
                    </form>
                </div>
            </td>
        </tr>
        @empty
        <tr>
            <td colspan="9" class="text-sub" style="text-align:center; padding:32px;">No tools yet — add your first one.</td>
        </tr>
        @endforelse
        </tbody>
    </table>
    </div>
    <div class="pager">
        <span>Showing {{ $tools->firstItem() ?? 0 }}–{{ $tools->lastItem() ?? 0 }} of {{ $tools->total() }} tools</span>
        <div class="pager-btns">
            {{ $tools->links() }}
        </div>
    </div>
</div>

@endsection
