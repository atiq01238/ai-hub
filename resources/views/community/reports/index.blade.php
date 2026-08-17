@extends('layouts.admin')
@section('title', 'Reports & Abuse')

@section('content')
<x-page-header
    title="Reports & Abuse"
    subtitle="Investigate community safety reports with a complete moderation trail"
    :breadcrumb="['Users & Community', 'Reports & Abuse']" />

@if (session('status'))
    <div class="alert alert-success" style="margin-bottom:16px;">{{ session('status') }}</div>
@endif
@if ($errors->any())
    <div class="alert alert-danger" style="margin-bottom:16px;">{{ $errors->first() }}</div>
@endif

<div class="kpi-grid">
    <x-kpi-card icon="flag" label="All Reports" value="{{ number_format($counts['all']) }}" />
    <x-kpi-card icon="clock-3" label="Pending" value="{{ number_format($counts['pending']) }}" />
    <x-kpi-card icon="scan-search" label="Under Review" value="{{ number_format($counts['reviewing']) }}" />
    <x-kpi-card icon="siren" label="Open Critical" value="{{ number_format($counts['critical']) }}" />
</div>

<div class="tabs">
    @php $base = array_filter(['search' => request('search'), 'type' => request('type'), 'priority' => request('priority')]); @endphp
    <a href="{{ route('admin.community.reports.index', $base) }}" class="tab {{ !request('status') ? 'is-active' : '' }}">All {{ $counts['all'] }}</a>
    <a href="{{ route('admin.community.reports.index', $base + ['status' => 'pending']) }}" class="tab {{ request('status') === 'pending' ? 'is-active' : '' }}">Pending {{ $counts['pending'] }}</a>
    <a href="{{ route('admin.community.reports.index', $base + ['status' => 'reviewing']) }}" class="tab {{ request('status') === 'reviewing' ? 'is-active' : '' }}">Reviewing {{ $counts['reviewing'] }}</a>
    <a href="{{ route('admin.community.reports.index', $base + ['status' => 'resolved']) }}" class="tab {{ request('status') === 'resolved' ? 'is-active' : '' }}">Resolved {{ $counts['resolved'] }}</a>
    <a href="{{ route('admin.community.reports.index', $base + ['status' => 'dismissed']) }}" class="tab {{ request('status') === 'dismissed' ? 'is-active' : '' }}">Dismissed</a>
</div>

<form method="GET" class="filter-bar">
    <input type="hidden" name="status" value="{{ request('status') }}">
    <div class="input-search" style="background:var(--surface); border:1px solid var(--border); border-radius:var(--radius-sm); padding:8px 12px; flex:1; max-width:360px;">
        <i data-lucide="search"></i>
        <input name="search" value="{{ request('search') }}" placeholder="Search reason, detail or reporter...">
    </div>
    <select class="select" name="type">
        <option value="">All reported items</option>
        <option value="user" @selected(request('type') === 'user')>Users</option>
        <option value="review" @selected(request('type') === 'review')>Reviews</option>
        <option value="submission" @selected(request('type') === 'submission')>Submissions</option>
    </select>
    <select class="select" name="priority">
        <option value="">All priorities</option>
        <option value="critical" @selected(request('priority') === 'critical')>Critical</option>
        <option value="high" @selected(request('priority') === 'high')>High</option>
        <option value="medium" @selected(request('priority') === 'medium')>Medium</option>
        <option value="low" @selected(request('priority') === 'low')>Low</option>
    </select>
    <button class="btn btn-secondary btn-sm"><i data-lucide="list-filter"></i> Apply</button>
    @if (request('search') || request('type') || request('priority'))
        <a href="{{ route('admin.community.reports.index', array_filter(['status' => request('status')])) }}" class="btn btn-ghost btn-sm">Reset</a>
    @endif
</form>

<div class="card">
    <div class="table-wrap">
        <table class="data-table">
            <thead><tr><th>Reported Item</th><th>Reason</th><th>Reporter</th><th>Priority</th><th>Age</th><th>Status</th><th></th></tr></thead>
            <tbody>
            @forelse ($reports as $report)
                @php
                    $statusType = in_array($report->status, ['resolved', 'dismissed'], true) ? 'pos' : ($report->priority === 'critical' ? 'neg' : 'warn');
                    $priorityType = in_array($report->priority, ['critical', 'high'], true) ? 'neg' : ($report->priority === 'medium' ? 'warn' : 'neutral');
                @endphp
                <tr>
                    <td>
                        <a href="{{ route('admin.community.reports.show', $report->id) }}"><b>{{ $report->subject_label }}</b></a>
                        <div class="cell-sub">{{ class_basename($report->reportable_type) }} #{{ $report->reportable_id }} · Case #{{ $report->id }}</div>
                    </td>
                    <td><b>{{ ucfirst($report->reason) }}</b><div class="cell-sub">{{ \Illuminate\Support\Str::limit($report->description, 65) ?: 'No additional detail' }}</div></td>
                    <td>
                        @if($report->reporter)<a href="{{ route('admin.users.show', $report->reporter->id) }}">{{ $report->reporter->name }}</a><div class="cell-sub">{{ $report->reporter->email }}</div>@else<span class="text-sub">Deleted user</span>@endif
                    </td>
                    <td><span class="badge badge-{{ $priorityType }}">{{ ucfirst($report->priority) }}</span></td>
                    <td><div>{{ $report->created_at->diffForHumans() }}</div><div class="cell-sub">{{ $report->created_at->format('M j, Y') }}</div></td>
                    <td><x-status-badge status="{{ ucfirst($report->status) }}" type="{{ $statusType }}" />@if($report->assignee)<div class="cell-sub">{{ $report->assignee->name }}</div>@endif</td>
                    <td><a href="{{ route('admin.community.reports.show', $report->id) }}" class="btn btn-secondary btn-sm"><i data-lucide="scan-search"></i> Investigate</a></td>
                </tr>
            @empty
                <tr><td colspan="7" class="text-sub" style="text-align:center; padding:40px;">No reports match these filters.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="pager">
        <span>Showing {{ $reports->firstItem() ?? 0 }}–{{ $reports->lastItem() ?? 0 }} of {{ $reports->total() }}</span>
        <div class="pager-btns">{{ $reports->onEachSide(1)->links() }}</div>
    </div>
</div>
@endsection
