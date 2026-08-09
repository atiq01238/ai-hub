@extends('layouts.admin')
@section('title', 'Tool Suggestions')

@section('content')

<x-page-header title="Tool Suggestions &amp; Submissions" subtitle="{{ $pendingCount }} pending review" :breadcrumb="['Users & Community', 'Submissions']" />

@if (session('status'))
    <div class="alert alert-success" style="margin-bottom:16px;">{{ session('status') }}</div>
@endif

<div class="tabs">
    <a href="{{ route('admin.submissions.index') }}" class="tab {{ !request('status') ? 'is-active' : '' }}">All</a>
    <a href="{{ route('admin.submissions.index', ['status' => 'pending']) }}" class="tab {{ request('status')==='pending' ? 'is-active' : '' }}">Pending</a>
    <a href="{{ route('admin.submissions.index', ['status' => 'approved']) }}" class="tab {{ request('status')==='approved' ? 'is-active' : '' }}">Approved</a>
    <a href="{{ route('admin.submissions.index', ['status' => 'rejected']) }}" class="tab {{ request('status')==='rejected' ? 'is-active' : '' }}">Rejected</a>
    <a href="{{ route('admin.submissions.index', ['status' => 'needs_info']) }}" class="tab {{ request('status')==='needs_info' ? 'is-active' : '' }}">Needs Info</a>
</div>

<div class="card">
    <div class="table-wrap">
    <table class="data-table">
        <thead><tr><th>Tool Name</th><th>Submitted By</th><th>Website</th><th>Category</th><th>Date</th><th>Status</th><th></th></tr></thead>
        <tbody>
        @forelse ($submissions as $submission)
        <tr>
            <td><b>{{ $submission->tool_name }}</b></td>
            <td class="text-sub">{{ $submission->submitted_by_email }}</td>
            <td class="text-sub">{{ $submission->website ?? '—' }}</td>
            <td class="text-sub">{{ $submission->category ?? '—' }}</td>
            <td class="cell-sub">{{ $submission->created_at->format('M j') }}</td>
            <td>
                <x-status-badge
                    status="{{ ucfirst(str_replace('_', ' ', $submission->status)) }}"
                    type="{{ $submission->status === 'approved' ? 'pos' : ($submission->status === 'rejected' ? 'neg' : 'warn') }}" />
                @if ($submission->admin_notes)
                    <div class="cell-sub" style="margin-top:4px; max-width:220px;">{{ $submission->admin_notes }}</div>
                @endif
            </td>
            <td>
                <div class="flex gap-8" style="flex-wrap:wrap;">
                    @if ($submission->status === 'pending' || $submission->status === 'needs_info')
                    <form action="{{ route('admin.submissions.approve', $submission->id) }}" method="POST">
                        @csrf
                        <button type="submit" class="btn btn-secondary btn-sm"><i data-lucide="check"></i> Approve</button>
                    </form>

                    <form action="{{ route('admin.submissions.reject', $submission->id) }}" method="POST" onsubmit="return fillNotes(this)">
                        @csrf
                        <input type="hidden" name="admin_notes">
                        <button type="submit" class="btn btn-ghost btn-sm"><i data-lucide="x"></i> Reject</button>
                    </form>

                    <form action="{{ route('admin.submissions.request-info', $submission->id) }}" method="POST" onsubmit="return fillNotes(this, true)">
                        @csrf
                        <input type="hidden" name="admin_notes">
                        <button type="submit" class="btn btn-ghost btn-sm"><i data-lucide="message-circle-question"></i> Request Info</button>
                    </form>
                    @else
                        <span class="text-sub">No actions</span>
                    @endif
                </div>
            </td>
        </tr>
        @empty
        <tr><td colspan="7" class="text-sub" style="text-align:center; padding:32px;">No submissions yet.</td></tr>
        @endforelse
        </tbody>
    </table>
    </div>
    <div class="pager">
        <span>Showing {{ $submissions->firstItem() ?? 0 }}–{{ $submissions->lastItem() ?? 0 }} of {{ $submissions->total() }}</span>
        <div class="pager-btns">{{ $submissions->links() }}</div>
    </div>
</div>

<script>
// Reject/Request Info need a short note — plain prompt() keeps this dependency-free.
function fillNotes(form, required = false) {
    const note = prompt(required ? 'What info do you need from them?' : 'Reason (optional):');
    if (required && !note) return false; // block submit if required and empty
    form.querySelector('input[name="admin_notes"]').value = note ?? '';
    return true;
}
</script>
@endsection
