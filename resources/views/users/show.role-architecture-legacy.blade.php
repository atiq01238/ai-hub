@extends('layouts.admin')
@section('title', $user->name . ' · User Detail')

@section('content')
<x-page-header
    title="{{ $user->name }}"
    subtitle="{{ $user->email }} · Joined {{ $user->created_at->format('M j, Y') }}"
    :breadcrumb="['Users & Community', 'Users', $user->name]">
    <x-slot:actions>
        <a href="{{ route('admin.users.index') }}" class="btn btn-ghost btn-sm"><i data-lucide="arrow-left"></i> Users</a>
        @if ($user->status === 'active' && auth()->id() !== $user->id && auth()->user()->canAccessModule('Users', 'Edit'))
            <button type="button" class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#suspendUserModal">
                <i data-lucide="user-x"></i> Suspend account
            </button>
        @elseif ($user->status === 'suspended' && auth()->user()->canAccessModule('Users', 'Edit'))
            <form action="{{ route('admin.users.activate', $user->id) }}" method="POST">
                @csrf
                <button type="submit" class="btn btn-primary btn-sm"><i data-lucide="user-check"></i> Activate account</button>
            </form>
        @endif
    </x-slot:actions>
</x-page-header>

@if (session('status'))
    <div class="alert alert-success" style="margin-bottom:16px;">{{ session('status') }}</div>
@endif
@if ($errors->any())
    <div class="alert alert-danger" style="margin-bottom:16px;">{{ $errors->first() }}</div>
@endif

@if ($user->status === 'suspended')
    <div class="card card-pad" style="margin-bottom:20px; border-color:rgba(248,113,113,.35); background:var(--neg-bg);">
        <div class="flex gap-12" style="align-items:flex-start;">
            <i data-lucide="shield-alert" style="color:var(--neg); flex-shrink:0;"></i>
            <div>
                <b style="color:var(--neg);">Account suspended</b>
                <div class="text-sub" style="margin-top:5px;">{{ $user->suspension_reason ?: 'No reason recorded.' }}</div>
                <div class="cell-sub" style="margin-top:7px;">
                    {{ $user->suspended_at?->format('M j, Y g:i A') }}
                    @if ($user->suspendedBy) · by {{ $user->suspendedBy->name }} @endif
                    @if ($user->suspended_until) · until {{ $user->suspended_until->format('M j, Y') }} @endif
                </div>
            </div>
        </div>
    </div>
@endif

<div class="kpi-grid">
    <x-kpi-card icon="star" label="Reviews" value="{{ $user->reviews_count }}" />
    <x-kpi-card icon="inbox" label="Submissions" value="{{ $user->submissions_count }}" />
    <x-kpi-card icon="flag" label="Reports Received" value="{{ $user->reports_received_count }}" />
    <x-kpi-card icon="clock-3" label="Last Login" value="{{ $user->last_login_at?->format('M j') ?? 'Never' }}" />
</div>

<div class="grid-12">
    <div class="col-8">
        <div class="card" style="margin-bottom:16px;">
            <div class="card-head"><h3>Recent Reviews</h3><span class="card-head__sub">Latest 5</span></div>
            @forelse ($recentReviews as $review)
                <div class="flex items-center gap-12" style="padding:13px 20px; border-bottom:1px solid var(--border-soft);">
                    <div class="kpi-icon"><i data-lucide="star"></i></div>
                    <div style="flex:1; font-size:13px;">
                        Rated <b>{{ $review->tool->name ?? 'Deleted tool' }}</b> {{ number_format((float) $review->rating, 1) }}/5
                        <div class="cell-sub">{{ \Illuminate\Support\Str::limit($review->body ?: 'Star rating only', 90) }}</div>
                    </div>
                    <x-status-badge status="{{ ucfirst($review->status) }}" type="{{ $review->status === 'published' ? 'pos' : ($review->status === 'flagged' ? 'neg' : 'warn') }}" />
                </div>
            @empty
                <div class="text-sub" style="padding:28px; text-align:center;">No reviews submitted yet.</div>
            @endforelse
        </div>

        <div class="grid-2">
            <div class="card">
                <div class="card-head"><h3>Recent Submissions</h3></div>
                @forelse ($recentSubmissions as $submission)
                    <a href="{{ route('admin.submissions.show', $submission->id) }}" class="flex items-center justify-between" style="padding:13px 20px; border-bottom:1px solid var(--border-soft); color:inherit; text-decoration:none;">
                        <div><b style="font-size:13px;">{{ $submission->tool_name }}</b><div class="cell-sub">{{ ucfirst($submission->submission_type) }}</div></div>
                        <x-status-badge status="{{ ucfirst(str_replace('_', ' ', $submission->status)) }}" type="{{ $submission->status === 'approved' ? 'pos' : ($submission->status === 'rejected' ? 'neg' : 'warn') }}" />
                    </a>
                @empty
                    <div class="text-sub" style="padding:24px; text-align:center;">No submissions.</div>
                @endforelse
            </div>

            <div class="card">
                <div class="card-head"><h3>Reports Received</h3></div>
                @forelse ($recentReports as $report)
                    <a href="{{ route('admin.community.reports.show', $report->id) }}" class="flex items-center justify-between" style="padding:13px 20px; border-bottom:1px solid var(--border-soft); color:inherit; text-decoration:none;">
                        <div><b style="font-size:13px;">{{ ucfirst($report->reason) }}</b><div class="cell-sub">By {{ $report->reporter?->name ?? 'Deleted user' }}</div></div>
                        <x-status-badge status="{{ ucfirst($report->status) }}" type="{{ in_array($report->status, ['resolved', 'dismissed']) ? 'pos' : 'warn' }}" />
                    </a>
                @empty
                    <div class="text-sub" style="padding:24px; text-align:center;">No reports received.</div>
                @endforelse
            </div>
        </div>
    </div>

    <div class="col-4">
        <div class="card card-pad" style="margin-bottom:16px;">
            <div class="section-title">Account Profile</div>
            @php
                $profileRows = [
                    ['Access level', $user->role === 'admin' ? 'Administrator' : 'Member'],
                    ['Status', ucfirst($user->status)],
                    ['Email verified', $user->email_verified_at ? $user->email_verified_at->format('M j, Y') : 'Not verified'],
                    ['Two-factor auth', $user->two_factor_enabled ? 'Enabled' : 'Disabled'],
                    ['Last login', $user->last_login_at?->format('M j, Y g:i A') ?? 'Never'],
                    ['Last login IP', $user->last_login_ip ?? '—'],
                ];
            @endphp
            @foreach ($profileRows as [$label, $value])
                <div class="flex items-center justify-between" style="padding:9px 0; border-bottom:1px solid var(--border-soft); gap:16px;">
                    <span class="cell-sub">{{ $label }}</span><span style="font-size:12.5px; text-align:right;">{{ $value }}</span>
                </div>
            @endforeach
        </div>

        @if (auth()->id() !== $user->id && auth()->user()->canAccessModule('Users', 'Edit') && auth()->user()->canAccessModule('Roles & Permissions', 'Edit'))
            <div class="card card-pad" style="margin-bottom:16px;">
                <div class="section-title">Account Access</div>
                <p class="cell-sub" style="margin-bottom:12px;">Promotion and demotion revoke the user's sessions immediately.</p>
                <form action="{{ route('admin.users.access', $user->id) }}" method="POST">
                    @csrf
                    @method('PATCH')
                    <div class="form-field" style="margin-bottom:10px;">
                        <label>Access level</label>
                        <select class="select" name="access_level" id="accessLevel" style="width:100%;">
                            <option value="user" @selected($user->role === 'user')>Member</option>
                            <option value="admin" @selected($user->role === 'admin')>Administrator</option>
                        </select>
                    </div>
                    <div class="form-field" style="margin-bottom:10px;">
                        <label>Permission role for administrator</label>
                        <select class="select" name="role_id" style="width:100%;">
                            @if (!auth()->user()->role_id)<option value="">Full legacy access</option>@endif
                            @foreach ($roles as $role)
                                <option value="{{ $role->id }}" @selected($user->role_id == $role->id)>{{ $role->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <button type="submit" class="btn btn-secondary btn-sm" style="width:100%; justify-content:center;" onclick="return confirm('Change this account access and revoke its sessions?');"><i data-lucide="key-round"></i> Update account access</button>
                </form>
            </div>
        @endif

        @if (auth()->id() !== $user->id && auth()->user()->canAccessModule('Roles & Permissions', 'Edit'))
        <div class="card card-pad">
            <div class="section-title">Permission Role</div>
            <p class="cell-sub" style="margin-bottom:12px;">Granular permissions apply to administrators. A member remains outside the admin panel even if a role is prepared for future promotion.</p>
            <form action="{{ route('admin.users.assign-role', $user->id) }}" method="POST">
                @csrf
                <select class="select" name="role_id" style="width:100%; margin-bottom:10px;">
                    <option value="">{{ $user->role === 'admin' ? 'Full legacy access' : 'No admin permission role' }}</option>
                    @foreach ($roles as $role)
                        <option value="{{ $role->id }}" @selected($user->role_id == $role->id)>{{ $role->name }}</option>
                    @endforeach
                </select>
                <button type="submit" class="btn btn-secondary btn-sm" style="width:100%; justify-content:center;"><i data-lucide="shield-check"></i> Save permission role</button>
            </form>
        </div>
        @endif
    </div>
</div>

@if ($user->status === 'active' && auth()->id() !== $user->id && auth()->user()->canAccessModule('Users', 'Edit'))
<div class="modal fade" id="suspendUserModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="background:var(--surface); border:1px solid var(--border); color:var(--text-hi);">
            <form action="{{ route('admin.users.suspend', $user->id) }}" method="POST">
                @csrf
                <div class="modal-header" style="border-color:var(--border-soft);">
                    <h5 class="modal-title">Suspend {{ $user->name }}</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="form-field" style="margin-bottom:14px;">
                        <label>Reason <span style="color:var(--neg);">*</span></label>
                        <textarea class="input" name="suspension_reason" rows="4" required placeholder="Record a clear moderation reason..."></textarea>
                    </div>
                    <div class="form-field">
                        <label>Suspended until <span class="cell-sub">(optional)</span></label>
                        <input class="input" type="datetime-local" name="suspended_until" min="{{ now()->addMinute()->format('Y-m-d\TH:i') }}">
                    </div>
                    <p class="cell-sub" style="margin:12px 0 0;">Active database sessions will be revoked immediately.</p>
                </div>
                <div class="modal-footer" style="border-color:var(--border-soft);">
                    <button type="button" class="btn btn-ghost btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger btn-sm"><i data-lucide="user-x"></i> Confirm suspension</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif
@endsection
