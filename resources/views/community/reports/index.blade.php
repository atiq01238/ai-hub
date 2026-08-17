@extends('layouts.admin')
@section('title','Reports & Abuse')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/pages/users-community.css') }}">
@endpush

@section('content')
<div class="uc-page">
<x-page-header title="Reports & Abuse" subtitle="Investigate community safety reports through a traceable case-management workflow." :breadcrumb="['Users & Community','Reports & Abuse']" />

@if(session('status'))<div class="alert alert-success uc-flash"><i data-lucide="check-circle-2"></i><span>{{ session('status') }}</span></div>@endif
@if($errors->any())<div class="alert alert-danger uc-flash"><i data-lucide="circle-alert"></i><span>{{ $errors->first() }}</span></div>@endif

<section class="uc-kpi-grid">
@foreach([
['All Reports',$counts['all'],'flag',''],['Pending',$counts['pending'],'clock-3','amber'],['Under Review',$counts['reviewing'],'scan-search','cyan'],['Open Critical',$counts['critical'],'siren','red']
] as [$label,$value,$icon,$tone])
<article class="uc-kpi uc-kpi--{{ $tone }}"><span><i data-lucide="{{ $icon }}"></i></span><div><small>{{ $label }}</small><strong>{{ number_format($value) }}</strong></div></article>
@endforeach
</section>

@php $base=array_filter(['search'=>request('search'),'type'=>request('type'),'priority'=>request('priority')]); @endphp
<nav class="uc-tabs">
<a href="{{ route('admin.community.reports.index',$base) }}" class="{{ !request('status')?'is-active':'' }}">All {{ $counts['all'] }}</a>
<a href="{{ route('admin.community.reports.index',$base+['status'=>'pending']) }}" class="{{ request('status')==='pending'?'is-active':'' }}">Pending {{ $counts['pending'] }}</a>
<a href="{{ route('admin.community.reports.index',$base+['status'=>'reviewing']) }}" class="{{ request('status')==='reviewing'?'is-active':'' }}">Reviewing {{ $counts['reviewing'] }}</a>
<a href="{{ route('admin.community.reports.index',$base+['status'=>'resolved']) }}" class="{{ request('status')==='resolved'?'is-active':'' }}">Resolved {{ $counts['resolved'] }}</a>
<a href="{{ route('admin.community.reports.index',$base+['status'=>'dismissed']) }}" class="{{ request('status')==='dismissed'?'is-active':'' }}">Dismissed</a>
</nav>

<form method="GET" class="card uc-filterbar uc-filterbar--reports">
<input type="hidden" name="status" value="{{ request('status') }}">
<div class="uc-search"><i data-lucide="search"></i><input class="input" name="search" value="{{ request('search') }}" placeholder="Search reason, detail or reporter..."></div>
<select class="select" name="type"><option value="">All reported items</option><option value="user" @selected(request('type')==='user')>Users</option><option value="review" @selected(request('type')==='review')>Reviews</option><option value="submission" @selected(request('type')==='submission')>Submissions</option></select>
<select class="select" name="priority"><option value="">All priorities</option>@foreach(['critical','high','medium','low'] as $p)<option value="{{ $p }}" @selected(request('priority')===$p)>{{ ucfirst($p) }}</option>@endforeach</select>
<button class="btn btn-secondary"><i data-lucide="filter"></i>Apply</button>
</form>

<section class="card uc-table-card">
<div class="uc-section-head"><div><span class="uc-eyebrow">Safety Case Queue</span><h2>Community reports</h2><p>Priority-ordered abuse and trust investigations.</p></div><span class="uc-count">{{ number_format($reports->total()) }} cases</span></div>
@if($reports->count())
<div class="table-wrap"><table class="data-table uc-table"><thead><tr><th>Reported Item</th><th>Reason</th><th>Reporter</th><th>Priority</th><th>Age</th><th>Status</th><th></th></tr></thead><tbody>
@foreach($reports as $report)
<tr>
<td><div class="uc-record"><span class="{{ in_array($report->priority,['critical','high'],true)?'is-risk':'' }}"><i data-lucide="flag"></i></span><div><a href="{{ route('admin.community.reports.show',$report->id) }}"><strong>{{ $report->subject_label }}</strong></a><small>{{ class_basename($report->reportable_type) }} #{{ $report->reportable_id }} · Case #{{ $report->id }}</small></div></div></td>
<td><div class="uc-contributor"><strong>{{ ucfirst($report->reason) }}</strong><small>{{ \Illuminate\Support\Str::limit($report->description,68) ?: 'No additional detail' }}</small></div></td>
<td>@if($report->reporter)<div class="uc-contributor"><a href="{{ route('admin.users.show',$report->reporter->id) }}"><strong>{{ $report->reporter->name }}</strong></a><small>{{ $report->reporter->email }}</small></div>@else<span class="uc-muted">Deleted user</span>@endif</td>
<td><span class="uc-priority uc-priority--{{ $report->priority }}">{{ ucfirst($report->priority) }}</span></td>
<td><span class="uc-muted">{{ $report->created_at->diffForHumans() }}<small>{{ $report->created_at->format('M j, Y') }}</small></span></td>
<td><x-status-badge status="{{ ucfirst($report->status) }}" type="{{ in_array($report->status,['resolved','dismissed'],true)?'pos':($report->priority==='critical'?'neg':'warn') }}" />@if($report->assignee)<small class="uc-assignee">{{ $report->assignee->name }}</small>@endif</td>
<td><a href="{{ route('admin.community.reports.show',$report->id) }}" class="btn btn-secondary btn-sm"><i data-lucide="scan-search"></i>Investigate</a></td>
</tr>
@endforeach
</tbody></table></div>
<div class="uc-pagination"><span>Showing {{ $reports->firstItem() ?? 0 }}–{{ $reports->lastItem() ?? 0 }} of {{ $reports->total() }}</span><div>{{ $reports->onEachSide(1)->links() }}</div></div>
@else<div class="uc-empty"><span><i data-lucide="shield-check"></i></span><h3>No reports found</h3><p>The current safety queue has no matching cases.</p></div>@endif
</section>
</div>
@endsection
