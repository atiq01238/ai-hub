@extends('layouts.admin')
@section('title', 'Activity Logs')

@section('content')

<x-page-header title="Activity Logs" subtitle="{{ $logs->total() }} logged actions" :breadcrumb="['System', 'Activity Logs']" />

<form method="GET" class="filter-bar">
    <select class="select" name="user_id" onchange="this.form.submit()">
        <option value="">All Users</option>
        @foreach ($users as $user)
            <option value="{{ $user->id }}" @selected((string) request('user_id') === (string) $user->id)>{{ $user->name }}</option>
        @endforeach
    </select>
    <select class="select" name="action" onchange="this.form.submit()">
        <option value="">All Actions</option>
        <option value="created" @selected(request('action') === 'created')>Created</option>
        <option value="updated" @selected(request('action') === 'updated')>Updated</option>
        <option value="deleted" @selected(request('action') === 'deleted')>Deleted</option>
    </select>
    <select class="select" name="subject_type" onchange="this.form.submit()">
        <option value="">All Types</option>
        @foreach ($subjectTypes as $type)
            @php $short = class_basename($type); @endphp
            <option value="{{ $short }}" @selected(request('subject_type') === $short)>{{ $short }}</option>
        @endforeach
    </select>
</form>

<div class="card">
    <div class="table-wrap">
    <table class="data-table">
        <thead><tr><th>User</th><th>Action</th><th>Type</th><th>Description</th><th>When</th></tr></thead>
        <tbody>
        @forelse ($logs as $log)
        <tr>
            <td class="text-sub">{{ $log->user->name ?? 'System' }}</td>
            <td>
                <span class="badge {{ $log->action === 'created' ? 'badge-pos' : ($log->action === 'deleted' ? 'badge-neg' : 'badge-warn') }}">{{ ucfirst($log->action) }}</span>
            </td>
            <td class="text-sub">{{ $log->subject_name }}</td>
            <td>{{ $log->description }}</td>
            <td class="cell-sub">{{ $log->created_at->diffForHumans() }}</td>
        </tr>
        @empty
        <tr><td colspan="5" class="text-sub" style="text-align:center; padding:32px;">No activity logged yet — try creating or editing a Tool/Company/Model/Article/News item.</td></tr>
        @endforelse
        </tbody>
    </table>
    </div>
    <div class="pager">
        <span>Showing {{ $logs->firstItem() ?? 0 }}–{{ $logs->lastItem() ?? 0 }} of {{ $logs->total() }}</span>
        <div class="pager-btns">{{ $logs->links() }}</div>
    </div>
</div>
@endsection
