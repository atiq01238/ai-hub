@extends('layouts.admin')
@section('title', 'AI Companies')

@section('content')

<x-page-header title="AI Company Management" subtitle="{{ $companies->total() }} companies tracked" :breadcrumb="['AI Management', 'AI Companies']">
    <x-slot:actions>
        <a href="{{ route('admin.companies.create') }}" class="btn btn-primary btn-sm"><i data-lucide="plus"></i> Add Company</a>
    </x-slot:actions>
</x-page-header>

@if (session('status'))
    <div class="alert alert-success" style="margin-bottom:16px;">{{ session('status') }}</div>
@endif

<div class="filter-bar">
    <div class="input-search" style="background:var(--surface); border:1px solid var(--border); border-radius:var(--radius-sm); padding:8px 12px;">
        <i data-lucide="search"></i><input type="text" placeholder="Search companies...">
    </div>
    <select class="select"><option>All Status</option><option>Active</option><option>Acquired</option><option>Inactive</option></select>
</div>

<div class="card">
    <div class="table-wrap">
    <table class="data-table">
        <thead><tr><th>Company</th><th>Website</th><th>Tools</th><th>Latest Update</th><th>Status</th><th></th></tr></thead>
        <tbody>
        @forelse ($companies as $company)
        <tr>
            <td>
                <div class="row-media">
                    @if ($company->logo_path)
                        <img src="{{ \Illuminate\Support\Facades\Storage::url($company->logo_path) }}" alt="" class="thumb" style="object-fit:cover;">
                    @else
                        <div class="thumb">{{ substr($company->name, 0, 2) }}</div>
                    @endif
                    <a href="{{ route('admin.companies.show', $company->id) }}"><b>{{ $company->name }}</b></a>
                </div>
            </td>
            <td class="text-sub">{{ $company->website ?? '—' }}</td>
            <td class="mono">{{ $company->tools_count }}</td>
            <td class="cell-sub">{{ $company->updated_at->diffForHumans() }}</td>
            <td><x-status-badge status="{{ ucfirst($company->status) }}" type="{{ $company->status === 'active' ? 'pos' : ($company->status === 'inactive' ? 'neutral' : 'warn') }}" /></td>
            <td>
                <div class="flex gap-8">
                    <a href="{{ route('admin.companies.edit', $company->id) }}" class="icon-btn" style="width:28px;height:28px;"><i data-lucide="pencil" style="width:14px;height:14px;"></i></a>
                    <form action="{{ route('admin.companies.destroy', $company->id) }}" method="POST" onsubmit="return confirm('Delete this company?')" style="display:inline;">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="icon-btn" style="width:28px;height:28px;"><i data-lucide="trash-2" style="width:14px;height:14px;"></i></button>
                    </form>
                </div>
            </td>
        </tr>
        @empty
        <tr><td colspan="6" class="text-sub" style="text-align:center; padding:32px;">No companies yet — add your first one.</td></tr>
        @endforelse
        </tbody>
    </table>
    </div>
    <div class="pager">
        <span>Showing {{ $companies->firstItem() ?? 0 }}–{{ $companies->lastItem() ?? 0 }} of {{ $companies->total() }} companies</span>
        <div class="pager-btns">{{ $companies->links() }}</div>
    </div>
</div>

@endsection
