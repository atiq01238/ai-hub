@extends('layouts.admin')
@section('title', 'Submission #' . $submission->id)

@section('content')
<x-page-header
    title="{{ $submission->tool_name }}"
    subtitle="{{ ucfirst($submission->submission_type) }} submission #{{ $submission->id }}"
    :breadcrumb="['Users & Community', 'Community Submissions', '#' . $submission->id]">
    <x-slot:actions>
        <a href="{{ route('admin.submissions.index') }}" class="btn btn-ghost btn-sm"><i data-lucide="arrow-left"></i> Submissions</a>
        @if ($submission->convertedTool)
            <a href="{{ route('admin.tools.show', $submission->convertedTool->id) }}" class="btn btn-primary btn-sm"><i data-lucide="wrench"></i> Open tool draft</a>
        @endif
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
            <div class="flex items-center justify-between" style="margin-bottom:18px;">
                <div class="row-media">
                    <div class="thumb lg"><i data-lucide="{{ $submission->submission_type === 'tool' ? 'wrench' : ($submission->submission_type === 'model' ? 'brain-circuit' : ($submission->submission_type === 'company' ? 'building-2' : 'pencil-line')) }}"></i></div>
                    <div><b style="font-size:16px;">{{ $submission->tool_name }}</b><div class="cell-sub">Submitted {{ $submission->created_at->format('M j, Y g:i A') }}</div></div>
                </div>
                <x-status-badge status="{{ ucfirst(str_replace('_', ' ', $submission->status)) }}" type="{{ $submission->status === 'approved' ? 'pos' : ($submission->status === 'rejected' ? 'neg' : 'warn') }}" />
            </div>

            <div class="section-title">Contributor Description</div>
            <p class="text-sub" style="line-height:1.8; white-space:pre-line;">{{ $submission->description ?: 'No description was supplied.' }}</p>

            <div class="divider"></div>
            <div class="grid-2">
                <div><div class="cell-sub">Website</div>@if($submission->website)<a href="{{ $submission->website }}" target="_blank" rel="noopener noreferrer">{{ $submission->website }}</a>@else<span>—</span>@endif</div>
                <div><div class="cell-sub">Category</div><span>{{ $submission->category ?: '—' }}</span></div>
            </div>
        </div>

        @if ($submission->admin_notes)
            <div class="card card-pad" style="margin-bottom:16px;">
                <div class="section-title">Moderation Note</div>
                <p class="text-sub" style="line-height:1.7; white-space:pre-line;">{{ $submission->admin_notes }}</p>
                <div class="cell-sub">{{ $submission->reviewer?->name ?? 'Administrator' }} @if($submission->reviewed_at) · {{ $submission->reviewed_at->format('M j, Y g:i A') }} @endif</div>
            </div>
        @endif
    </div>

    <div class="col-4">
        <div class="card card-pad" style="margin-bottom:16px;">
            <div class="section-title">Contributor</div>
            <div class="flex items-center gap-12">
                <div class="thumb">{{ mb_strtoupper(mb_substr($submission->user?->name ?? $submission->submitted_by_email, 0, 2)) }}</div>
                <div>
                    <b>{{ $submission->user?->name ?? 'Guest contributor' }}</b>
                    <div class="cell-sub">{{ $submission->submitted_by_email }}</div>
                </div>
            </div>
            @if ($submission->user)
                <a href="{{ route('admin.users.show', $submission->user->id) }}" class="btn btn-ghost btn-sm" style="margin-top:14px; width:100%; justify-content:center;">View user profile</a>
            @endif
        </div>

        @if (in_array($submission->status, ['pending', 'needs_info'], true) && auth()->user()->canAccessModule('Submissions', 'Edit'))
            <div class="card card-pad">
                <div class="section-title">Moderation Decision</div>

                <form method="POST" action="{{ route('admin.submissions.approve', $submission->id) }}" style="margin-bottom:16px;">
                    @csrf
                    <div class="form-field" style="margin-bottom:10px;">
                        <label>Approval note <span class="cell-sub">(optional)</span></label>
                        <textarea class="input" name="admin_notes" rows="3" placeholder="Internal approval context..."></textarea>
                    </div>
                    <button class="btn btn-primary btn-sm" style="width:100%; justify-content:center;">
                        <i data-lucide="badge-check"></i> {{ $submission->submission_type === 'tool' ? 'Approve & create tool draft' : 'Approve submission' }}
                    </button>
                </form>

                <div class="divider"></div>

                <form method="POST" action="{{ route('admin.submissions.request-info', $submission->id) }}" style="margin-bottom:14px;">
                    @csrf
                    <div class="form-field" style="margin-bottom:10px;">
                        <label>Information needed</label>
                        <textarea class="input" name="admin_notes" rows="3" required placeholder="Clearly explain what is missing..."></textarea>
                    </div>
                    <button class="btn btn-secondary btn-sm" style="width:100%; justify-content:center;"><i data-lucide="message-circle-question"></i> Request information</button>
                </form>

                <form method="POST" action="{{ route('admin.submissions.reject', $submission->id) }}" onsubmit="return confirm('Reject this submission?');">
                    @csrf
                    <div class="form-field" style="margin-bottom:10px;">
                        <label>Rejection reason</label>
                        <textarea class="input" name="admin_notes" rows="3" required placeholder="Record the policy or quality reason..."></textarea>
                    </div>
                    <button class="btn btn-danger btn-sm" style="width:100%; justify-content:center;"><i data-lucide="x"></i> Reject submission</button>
                </form>
            </div>
        @elseif (!in_array($submission->status, ['pending', 'needs_info'], true))
            <div class="card card-pad">
                <div class="section-title">Workflow Complete</div>
                <p class="text-sub" style="line-height:1.7;">This submission was reviewed by {{ $submission->reviewer?->name ?? 'an administrator' }}{{ $submission->reviewed_at ? ' on ' . $submission->reviewed_at->format('M j, Y') : '' }}.</p>
            </div>
        @else
            <div class="card card-pad"><div class="section-title">Read-only Access</div><p class="text-sub">You can inspect this submission but cannot change its moderation status.</p></div>
        @endif
    </div>
</div>
@endsection
