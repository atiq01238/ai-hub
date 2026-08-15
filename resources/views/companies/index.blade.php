@extends('layouts.admin')
@section('title', 'AI Companies')
@section('content')
<x-page-header title="AI Companies" subtitle="Manage AI companies and their connected tools and models" :breadcrumb="['AI Management','AI Companies']">
    <x-slot:actions><a href="{{ route('admin.companies.create') }}" class="btn btn-primary btn-sm"><i data-lucide="plus"></i> Add Company</a></x-slot:actions>
</x-page-header>
@if(session('status'))<div class="alert alert-success" style="margin-bottom:16px;">{{ session('status') }}</div>@endif
<form method="GET" class="card card-pad" style="margin-bottom:16px;display:grid;grid-template-columns:1fr 220px auto auto;gap:8px;align-items:center;">
    <input class="input" name="search" value="{{ request('search') }}" placeholder="Search name, website or description...">
    <select class="select" name="status">
        <option value="">All Status</option>
        @foreach(['active'=>'Active','acquired'=>'Acquired','inactive'=>'Inactive'] as $v=>$l)<option value="{{ $v }}" @selected(request('status')===$v)>{{ $l }}</option>@endforeach
    </select>
    <button class="btn btn-secondary btn-sm" type="submit"><i data-lucide="search"></i> Filter</button>
    <a class="btn btn-secondary btn-sm" href="{{ route('admin.companies.index') }}">Reset</a>
</form>
<div class="card"><div class="table-wrap"><table class="data-table">
<thead><tr><th>Company</th><th>Website</th><th>Status</th><th>Tools</th><th>Models</th><th>Founded</th><th>Actions</th></tr></thead>
<tbody>@forelse($companies as $company)<tr>
<td><a href="{{ route('admin.companies.show',$company->id) }}"><b>{{ $company->name }}</b></a><div class="cell-sub">{{ $company->slug }}</div></td>
<td>@if($company->website)<a href="{{ $company->website }}" target="_blank" rel="noopener">{{ \Illuminate\Support\Str::limit($company->website,35) }}</a>@else — @endif</td>
<td><x-status-badge status="{{ ucfirst($company->status) }}" type="{{ $company->status==='active'?'pos':($company->status==='acquired'?'warn':'neutral') }}" /></td>
<td class="mono">{{ number_format($company->tools_count) }}</td><td class="mono">{{ number_format($company->models_count) }}</td><td>{{ $company->founded_year ?: '—' }}</td>
<td><div class="flex gap-8"><a class="icon-btn" href="{{ route('admin.companies.edit',$company->id) }}"><i data-lucide="pencil"></i></a><form method="POST" action="{{ route('admin.companies.destroy',$company->id) }}" onsubmit="return confirm('Delete {{ addslashes($company->name) }}? Related tools/models will be kept but detached.')">@csrf @method('DELETE')<button class="icon-btn" type="submit"><i data-lucide="trash-2"></i></button></form></div></td>
</tr>@empty<tr><td colspan="7" style="text-align:center;padding:32px;">No companies found.</td></tr>@endforelse</tbody>
</table></div><div class="pager"><span>Showing {{ $companies->firstItem() ?? 0 }}–{{ $companies->lastItem() ?? 0 }} of {{ $companies->total() }}</span><div class="pager-btns">{{ $companies->links() }}</div></div></div>
@endsection
