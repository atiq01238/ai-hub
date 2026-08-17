@extends('layouts.admin')
@section('title', 'Report Case #' . $report->id)

@section('content')
@php
    $subject = $report->reportable;
    $subjectUrl = match (true) {
        $subject instanceof \App\Models\User => route('admin.users.show', $subject->id),
        $subject instanceof \App\Models\Review => route($subject->review_type === 'user' ? 'admin.community.reviews.show' : 'admin.content.reviews.show', $subject->id),
        $subject instanceof \App\Models\Submission => route('admin.submissions.show', $subject->id),
        default => null,
    };
@endphp

<x-page-header
    title="Report Case #{{ $report->id }}"
    subtitle="{{ ucfirst($report->reason) }} · {{ class_basename($report->reportable_type) }} report"
    :breadcrumb="['Users & Community', 'Reports & Abuse', '#' . $report->id]">
    <x-slot:actions>
        <a href="{{ route('admin.community.reports.index') }}" class="btn btn-ghost btn-sm"><i data-lucide="arrow-left"></i> Reports</a>
        @if($subjectUrl)<a href="{{ $subjectUrl }}" class="btn btn-secondary btn-sm"><i data-lucide="external-link"></i> Open reported item</a>@endif
    </x-slot:actions>
</x-page-header>

@if (session('status'))
    <div class="alert alert-success" style="margin-bottom:16px;">{{ session('status') }}</div>
@endif
@if ($errors->any())
    <div class="alert alert-danger" style="margin-bottom:16px;">{{ $errors->first() }}</div>
@endif

<div class="grid-12">
    <div class="col-8">
        <div class="card card-pad" style="margin-bottom:16px;">
            <div class="flex items-center justify-between" style="margin-bottom:20px;">
                <div class="row-media">
                    <div class="thumb lg"><i data-lucide="flag"></i></div>
                    <div><b style="font-size:16px;">{{ $report->subject_label }}</b><div class="cell-sub">{{ class_basename($report->reportable_type) }} #{{ $report->reportable_id }}</div></div>
                </div>
                <div class="flex gap-8">
                    <span class="badge badge-{{ in_array($report->priority, ['critical', 'high'], true) ? 'neg' : 'warn' }}">{{ ucfirst($report->priority) }} priority</span>
                    <x-status-badge status="{{ ucfirst($report->status) }}" type="{{ in_array($report->status, ['resolved', 'dismissed'], true) ? 'pos' : 'warn' }}" />
                </div>
            </div>

            <div class="section-title">Reason: {{ ucfirst($report->reason) }}</div>
            <p class="text-sub" style="line-height:1.8; white-space:pre-line;">{{ $report->description ?: 'The reporter did not provide additional details.' }}</p>

            <div class="divider"></div>
            <div class="grid-3">
                <div><div class="cell-sub">Reported</div><span>{{ $report->created_at->format('M j, Y g:i A') }}</span></div>
                <div><div class="cell-sub">Assigned to</div><span>{{ $report->assignee?->name ?? 'Unassigned' }}</span></div>
                <div><div class="cell-sub">Case age</div><span>{{ $report->created_at->diffForHumans() }}</span></div>
            </div>
        </div>

        @if ($report->resolution_note)
            <div class="card card-pad">
                <div class="section-title">Resolution Record</div>
                <p class="text-sub" style="line-height:1.8; white-space:pre-line;">{{ $report->resolution_note }}</p>
                <div class="cell-sub">{{ $report->resolver?->name ?? 'Administrator' }} @if($report->resolved_at) · {{ $report->resolved_at->format('M j, Y g:i A') }} @endif</div>
            </div>
        @endif
    </div>

    <div class="col-4">
        <div class="card card-pad" style="margin-bottom:16px;">
            <div class="section-title">Reporter</div>
            @if ($report->reporter)
                <div class="flex items-center gap-12">
                    <div class="thumb">{{ mb_strtoupper(mb_substr($report->reporter->name, 0, 2)) }}</div>
                    <div><b>{{ $report->reporter->name }}</b><div class="cell-sub">{{ $report->reporter->email }}</div></div>
                </div>
                <a href="{{ route('admin.users.show', $report->reporter->id) }}" class="btn btn-ghost btn-sm" style="width:100%; justify-content:center; margin-top:14px;">View reporter profile</a>
            @else
                <p class="text-sub">The reporting account has been deleted.</p>
            @endif
        </div>

        @if (auth()->user()->canAccessModule('Reports', 'Edit'))
        <div class="card card-pad">
            <div class="section-title">Case Workflow</div>
            <form method="POST" action="{{ route('admin.community.reports.status', $report->id) }}">
                @csrf
                @method('PATCH')
                <div class="form-field" style="margin-bottom:12px;">
                    <label>Decision</label>
                    <select class="select" name="status" id="reportStatus" style="width:100%;" required>
                        <option value="reviewing" @selected($report->status === 'reviewing')>Mark as reviewing</option>
                        <option value="resolved" @selected($report->status === 'resolved')>Resolve with action</option>
                        <option value="dismissed" @selected($report->status === 'dismissed')>Dismiss report</option>
                    </select>
                </div>
                <div class="form-field" style="margin-bottom:12px;">
                    <label>Case priority</label>
                    <select class="select" name="priority" style="width:100%;" required>
                        @foreach(['low', 'medium', 'high', 'critical'] as $priority)
                            <option value="{{ $priority }}" @selected($report->priority === $priority)>{{ ucfirst($priority) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-field" style="margin-bottom:12px;">
                    <label>Investigation / resolution note</label>
                    <textarea class="input" name="resolution_note" id="resolutionNote" rows="5" placeholder="Record evidence checked, decision and any action taken...">{{ old('resolution_note', $report->resolution_note) }}</textarea>
                    <span class="hint">Required when resolving or dismissing a case.</span>
                </div>
                <button class="btn btn-primary btn-sm" style="width:100%; justify-content:center;"><i data-lucide="save"></i> Save case decision</button>
            </form>
        </div>
        @else
            <div class="card card-pad"><div class="section-title">Read-only Access</div><p class="text-sub">You can inspect this case but cannot change its workflow status.</p></div>
        @endif
    </div>
</div>
@endsection

@push('scripts')
<script>
document.querySelector('form[action*="/status"]')?.addEventListener('submit', function (event) {
    const status = document.getElementById('reportStatus').value;
    const note = document.getElementById('resolutionNote').value.trim();
    if ((status === 'resolved' || status === 'dismissed') && !note) {
        event.preventDefault();
        document.getElementById('resolutionNote').focus();
        alert('A resolution note is required for this decision.');
    }
});
</script>
@endpush
