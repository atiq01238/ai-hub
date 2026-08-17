@extends('layouts.admin')
@section('title', 'Community Submissions')

@section('content')
<x-page-header
    title="Community Submissions"
    subtitle="Review suggestions, corrections and new AI directory entries"
    :breadcrumb="['Users & Community', 'Community Submissions']">
    <x-slot:actions>
        <a href="{{ route('submissions.create') }}" target="_blank" rel="noopener" class="btn btn-secondary btn-sm">
            <i data-lucide="external-link"></i> Open public form
        </a>
    </x-slot:actions>
</x-page-header>

@if (session('status'))
    <div class="alert alert-success" style="margin-bottom:16px;">{{ session('status') }}</div>
@endif
@if ($errors->any())
    <div class="alert alert-danger" style="margin-bottom:16px;">{{ $errors->first() }}</div>
@endif

<div class="kpi-grid">
    <x-kpi-card icon="inbox" label="All Submissions" value="{{ number_format($counts['all']) }}" />
    <x-kpi-card icon="clock-3" label="Pending" value="{{ number_format($counts['pending']) }}" />
    <x-kpi-card icon="message-circle-question" label="Needs Information" value="{{ number_format($counts['needs_info']) }}" />
    <x-kpi-card icon="badge-check" label="Approved" value="{{ number_format($counts['approved']) }}" />
</div>

<div class="tabs">
    @php $base = ['type' => request('type'), 'search' => request('search')]; @endphp
    <a href="{{ route('admin.submissions.index', array_filter($base)) }}" class="tab {{ !request('status') ? 'is-active' : '' }}">All {{ $counts['all'] }}</a>
    <a href="{{ route('admin.submissions.index', array_filter($base + ['status' => 'pending'])) }}" class="tab {{ request('status') === 'pending' ? 'is-active' : '' }}">Pending {{ $counts['pending'] }}</a>
    <a href="{{ route('admin.submissions.index', array_filter($base + ['status' => 'needs_info'])) }}" class="tab {{ request('status') === 'needs_info' ? 'is-active' : '' }}">Needs Info {{ $counts['needs_info'] }}</a>
    <a href="{{ route('admin.submissions.index', array_filter($base + ['status' => 'approved'])) }}" class="tab {{ request('status') === 'approved' ? 'is-active' : '' }}">Approved {{ $counts['approved'] }}</a>
    <a href="{{ route('admin.submissions.index', array_filter($base + ['status' => 'rejected'])) }}" class="tab {{ request('status') === 'rejected' ? 'is-active' : '' }}">Rejected {{ $counts['rejected'] }}</a>
</div>

<form method="GET" class="filter-bar">
    <input type="hidden" name="status" value="{{ request('status') }}">
    <div class="input-search" style="background:var(--surface); border:1px solid var(--border); border-radius:var(--radius-sm); padding:8px 12px; flex:1; max-width:380px;">
        <i data-lucide="search"></i>
        <input name="search" value="{{ request('search') }}" placeholder="Search subject, email or website...">
    </div>
    <select class="select" name="type">
        <option value="">All submission types</option>
        <option value="tool" @selected(request('type') === 'tool')>AI Tool</option>
        <option value="model" @selected(request('type') === 'model')>AI Model</option>
        <option value="company" @selected(request('type') === 'company')>AI Company</option>
        <option value="correction" @selected(request('type') === 'correction')>Data Correction</option>
    </select>
    <button class="btn btn-secondary btn-sm"><i data-lucide="list-filter"></i> Apply</button>
    @if (request('search') || request('type'))
        <a href="{{ route('admin.submissions.index', array_filter(['status' => request('status')])) }}" class="btn btn-ghost btn-sm">Reset filters</a>
    @endif
</form>

<div class="card">
    <div class="table-wrap">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Submission</th>
                    <th>Type</th>
                    <th>Submitted By</th>
                    <th>Website / Category</th>
                    <th>Submitted</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            @forelse ($submissions as $submission)
                <tr>
                    <td>
                        <a href="{{ route('admin.submissions.show', $submission->id) }}"><b>{{ $submission->tool_name }}</b></a>
                        <div class="cell-sub">#{{ $submission->id }} · {{ \Illuminate\Support\Str::limit($submission->description, 55) ?: 'No description' }}</div>
                    </td>
                    <td><span class="badge badge-neutral">{{ ucfirst($submission->submission_type) }}</span></td>
                    <td>
                        <div>{{ $submission->user?->name ?? 'Guest contributor' }}</div>
                        <div class="cell-sub">{{ $submission->submitted_by_email }}</div>
                    </td>
                    <td>
                        @if ($submission->website)
                            <a href="{{ $submission->website }}" target="_blank" rel="noopener noreferrer" class="text-sub">{{ parse_url($submission->website, PHP_URL_HOST) ?: $submission->website }}</a>
                        @else
                            <span class="text-sub">No website</span>
                        @endif
                        <div class="cell-sub">{{ $submission->category ?: 'Uncategorized' }}</div>
                    </td>
                    <td><div>{{ $submission->created_at->format('M j, Y') }}</div><div class="cell-sub">{{ $submission->created_at->diffForHumans() }}</div></td>
                    <td>
                        <x-status-badge
                            status="{{ ucfirst(str_replace('_', ' ', $submission->status)) }}"
                            type="{{ $submission->status === 'approved' ? 'pos' : ($submission->status === 'rejected' ? 'neg' : 'warn') }}" />
                        @if ($submission->reviewer)
                            <div class="cell-sub">by {{ $submission->reviewer->name }}</div>
                        @endif
                    </td>
                    <td><a href="{{ route('admin.submissions.show', $submission->id) }}" class="btn btn-secondary btn-sm"><i data-lucide="eye"></i> Review</a></td>
                </tr>
            @empty
                <tr><td colspan="7" class="text-sub" style="text-align:center; padding:40px;">No submissions match these filters.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="pager">
        <span>Showing {{ $submissions->firstItem() ?? 0 }}–{{ $submissions->lastItem() ?? 0 }} of {{ $submissions->total() }}</span>
        <div class="pager-btns">{{ $submissions->onEachSide(1)->links() }}</div>
    </div>
</div>
@endsection
