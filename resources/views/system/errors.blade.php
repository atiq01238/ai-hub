@extends('layouts.admin')
@section('title', 'Error Monitoring')

@section('content')

<x-page-header title="Error Monitoring" subtitle="{{ $stats['open'] }} unresolved errors" :breadcrumb="['System', 'Error Monitoring']" />

<div class="kpi-grid" style="grid-template-columns:repeat(4,1fr);">
    <x-kpi-card icon="alert-triangle" label="Total Errors" value="{{ $stats['total'] }}" />
    <x-kpi-card icon="octagon-alert" label="Critical (guessed)" value="{{ $stats['critical'] }}" />
    <x-kpi-card icon="circle-dot" label="Unresolved" value="{{ $stats['open'] }}" />
    <x-kpi-card icon="check-circle" label="Resolved" value="{{ $stats['resolved'] }}" />
</div>

<div class="tabs">
    <a href="{{ route('admin.system.errors.index') }}" class="tab {{ !request('status') ? 'is-active' : '' }}">All</a>
    <a href="{{ route('admin.system.errors.index', ['status' => 'open']) }}" class="tab {{ request('status')==='open' ? 'is-active' : '' }}">Open</a>
    <a href="{{ route('admin.system.errors.index', ['status' => 'investigating']) }}" class="tab {{ request('status')==='investigating' ? 'is-active' : '' }}">Investigating</a>
    <a href="{{ route('admin.system.errors.index', ['status' => 'resolved']) }}" class="tab {{ request('status')==='resolved' ? 'is-active' : '' }}">Resolved</a>
</div>

<div class="card">
    <div class="card-head"><h3>Error Log</h3></div>
    <div class="table-wrap">
    <table class="data-table">
        <thead><tr><th>Error</th><th>File</th><th>Severity</th><th>Occurrences</th><th>First Seen</th><th>Last Seen</th><th>Status</th><th></th></tr></thead>
        <tbody>
        @forelse ($errors as $error)
        <tr>
            <td><b class="mono" style="font-size:12.5px;">{{ class_basename($error->exception_class) }}</b></td>
            <td class="text-sub" style="font-size:11.5px;">{{ basename($error->file ?? '') }}:{{ $error->line }}</td>
            <td><span class="badge badge-{{ $error->severity === 'critical' ? 'neg' : ($error->severity === 'medium' ? 'warn' : 'neutral') }}">{{ ucfirst($error->severity) }}</span></td>
            <td class="text-sub">{{ $error->occurrence_count }}x</td>
            <td class="cell-sub">{{ $error->first_seen_at->format('M j') }}</td>
            <td class="cell-sub">{{ $error->last_seen_at->diffForHumans() }}</td>
            <td><x-status-badge status="{{ ucfirst($error->status) }}" type="{{ $error->status === 'resolved' ? 'pos' : ($error->status === 'open' ? 'neg' : 'warn') }}" /></td>
            <td><a href="{{ route('admin.system.errors.show', $error->id) }}" class="btn btn-ghost btn-sm">View</a></td>
        </tr>
        @empty
        <tr><td colspan="8" class="text-sub" style="text-align:center; padding:32px;">No errors logged — either everything's working, or the exception hook isn't wired up yet (see setup notes).</td></tr>
        @endforelse
        </tbody>
    </table>
    </div>
    <div class="pager">{{ $errors->links() }}</div>
</div>
@endsection
