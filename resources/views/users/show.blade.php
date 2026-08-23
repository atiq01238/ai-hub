@extends('layouts.admin')
@section('title', $user->name . ' · User Detail')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/pages/users-community.css') }}">
@endpush

@section('content')
@php
    $initials = collect(preg_split('/\s+/',trim($user->name)))->filter()->take(2)->map(fn($p)=>mb_strtoupper(mb_substr($p,0,1)))->implode('');
@endphp
<div class="uc-page uc-profile">
<x-page-header
    :title="$user->name"
    :subtitle="$user->email.' · Joined '.$user->created_at->format('M j, Y')"
    :breadcrumb="['Users & Community','Users',$user->name]"
>
<x-slot:actions>
    <a href="{{ route('admin.users.index') }}" class="btn btn-secondary"><i data-lucide="arrow-left"></i>Users</a>
    @if($user->trashed() && auth()->user()->canAccessModule('Users','Delete'))
        <form action="{{ route('admin.users.restore',$user->id) }}" method="POST" onsubmit="return confirm('Restore {{ addslashes($user->name) }}?')">@csrf<button class="btn btn-primary"><i data-lucide="rotate-ccw"></i>Restore Account</button></form>
    @elseif($user->status==='suspended' && auth()->user()->canAccessModule('Users','Edit'))
        <form action="{{ route('admin.users.activate',$user->id) }}" method="POST">@csrf<button class="btn btn-primary"><i data-lucide="user-check"></i>Activate Account</button></form>
    @endif
</x-slot:actions>
</x-page-header>

@if(session('status'))<div class="alert alert-success uc-flash"><i data-lucide="check-circle-2"></i><span>{{ session('status') }}</span></div>@endif
@if($errors->any())<div class="alert alert-danger uc-flash"><i data-lucide="circle-alert"></i><span>{{ $errors->first() }}</span></div>@endif

@if($user->trashed())
<section class="uc-deleted-alert">
    <i data-lucide="trash-2"></i>
    <div>
        <strong>Account deleted</strong>
        <p>{{ $user->deletion_reason ?: 'No deletion reason recorded.' }}</p>
        <small>Deleted {{ $user->deleted_at?->format('M j, Y g:i A') }}@if($user->deletedBy) · by {{ $user->deletedBy->name }}@endif · public/history records are preserved</small>
    </div>
</section>
@elseif($user->status==='suspended')
<section class="uc-suspension-alert">
    <i data-lucide="shield-alert"></i>
    <div><strong>Account suspended</strong><p>{{ $user->suspension_reason ?: 'No reason recorded.' }}</p><small>{{ $user->suspended_at?->format('M j, Y g:i A') }}@if($user->suspendedBy) · by {{ $user->suspendedBy->name }}@endif @if($user->suspended_until) · until {{ $user->suspended_until->format('M j, Y g:i A') }}@endif</small></div>
</section>
@endif

<section class="card uc-profile__hero">
    <div class="uc-profile__identity">
        <div class="uc-profile__avatar">{{ $initials ?: 'U' }}</div>
        <div>
            <div class="uc-profile__badges">
                <span class="uc-access {{ $user->role==='admin'?'is-admin':'' }}"><i data-lucide="{{ $user->role==='admin'?'shield':'user-round' }}"></i>{{ $user->role==='admin'?'Administrator':'Member' }}</span>
                @if($user->trashed())
                    <span class="uc-deleted-badge"><i data-lucide="trash-2"></i>Deleted</span>
                @else
                    <x-status-badge status="{{ ucfirst($user->status) }}" type="{{ $user->status==='active'?'pos':'neg' }}" />
                @endif
                <span class="uc-access {{ $user->community_trust_level==='trusted'?'is-admin':'' }}">
                    <i data-lucide="{{ $user->community_trust_level==='trusted'?'badge-check':($user->community_trust_level==='restricted'?'shield-alert':'user-check') }}"></i>
                    {{ ucfirst($user->community_trust_level ?? 'normal') }} community
                </span>
            </div>
            <h1>{{ $user->name }}</h1>
            <p>{{ $user->email }}</p>
        </div>
    </div>
    <div class="uc-profile__signal"><span class="uc-eyebrow">Community footprint</span><strong>{{ number_format($user->reviews_count + $user->submissions_count) }}</strong><small>reviews + submissions</small></div>
</section>

<section class="uc-profile__metrics">
    @foreach([
        ['Reviews',$user->reviews_count,'star'],
        ['Submissions',$user->submissions_count,'inbox'],
        ['Reports received',$user->reports_received_count,'flag'],
        ['Reports filed',$user->reports_filed_count,'shield-alert'],
        ['Comments',$user->community_comments_count,'messages-square'],
    ] as [$label,$value,$icon])
    <article class="card"><span><i data-lucide="{{ $icon }}"></i></span><div><strong>{{ number_format($value) }}</strong><small>{{ $label }}</small></div></article>
    @endforeach
</section>

<div class="uc-profile__layout">
<main class="uc-profile__main">
    <section class="card uc-panel">
        <div class="uc-section-head"><div><span class="uc-eyebrow">Activity</span><h2>Recent reviews</h2><p>Latest reviews created by this user.</p></div><i data-lucide="star"></i></div>
        <div class="uc-list">
        @forelse($recentReviews as $review)
            <a href="{{ route($review->review_type==='user'?'admin.community.reviews.show':'admin.content.reviews.show',$review->id) }}"><span class="uc-list__icon"><i data-lucide="message-square-text"></i></span><div><strong>{{ $review->tool->name ?? 'Deleted tool' }}</strong><small>{{ number_format((float)$review->rating,1) }}/5 · {{ ucfirst($review->status) }} · {{ $review->created_at->diffForHumans() }}</small></div><i data-lucide="arrow-up-right"></i></a>
        @empty<div class="uc-empty uc-empty--small"><p>No reviews yet.</p></div>@endforelse
        </div>
    </section>

    <section class="card uc-panel">
        <div class="uc-section-head"><div><span class="uc-eyebrow">Contributions</span><h2>Recent submissions</h2><p>Latest product or correction suggestions.</p></div><i data-lucide="inbox"></i></div>
        <div class="uc-list">
        @forelse($recentSubmissions as $submission)
            <a href="{{ route('admin.submissions.show',$submission->id) }}"><span class="uc-list__icon"><i data-lucide="send"></i></span><div><strong>{{ $submission->tool_name }}</strong><small>{{ ucfirst($submission->submission_type) }} · {{ ucwords(str_replace('_',' ',$submission->status)) }} · {{ $submission->created_at->diffForHumans() }}</small></div><i data-lucide="arrow-up-right"></i></a>
        @empty<div class="uc-empty uc-empty--small"><p>No submissions yet.</p></div>@endforelse
        </div>
    </section>

    <section class="card uc-panel">
        <div class="uc-section-head"><div><span class="uc-eyebrow">Trust & Safety</span><h2>Reports against this user</h2><p>Latest abuse or policy reports received by this account.</p></div><i data-lucide="flag"></i></div>
        <div class="uc-list">
        @forelse($recentReports as $report)
            <a href="{{ route('admin.community.reports.show',$report->id) }}"><span class="uc-list__icon is-risk"><i data-lucide="flag"></i></span><div><strong>{{ ucfirst($report->reason) }}</strong><small>{{ ucfirst($report->priority) }} priority · {{ ucfirst($report->status) }} · reported by {{ $report->reporter?->name ?? 'Deleted user' }}</small></div><i data-lucide="arrow-up-right"></i></a>
        @empty<div class="uc-empty uc-empty--small"><p>No reports against this user.</p></div>@endforelse
        </div>
    </section>
</main>

<aside class="uc-profile__aside">
    <section class="card uc-facts">
        <span class="uc-eyebrow">Account Facts</span>
        <dl>
            <div><dt>Access</dt><dd>{{ $user->role==='admin'?'Administrator':'Member' }}</dd></div>
            <div><dt>Permission role</dt><dd>{{ $user->role==='admin' ? ($user->roleModel?->name ?? 'Legacy admin') : 'Not applicable' }}</dd></div>
            <div><dt>Status</dt><dd>{{ $user->trashed() ? 'Deleted' : ucfirst($user->status) }}</dd></div>
            @if($user->trashed())
                <div><dt>Deleted</dt><dd>{{ $user->deleted_at?->format('M j, Y') }}</dd></div>
                <div><dt>Deleted by</dt><dd>{{ $user->deletedBy?->name ?? 'Unknown admin' }}</dd></div>
            @endif
            <div><dt>Joined</dt><dd>{{ $user->created_at->format('M j, Y') }}</dd></div>
            <div><dt>Community trust</dt><dd>{{ ucfirst($user->community_trust_level ?? 'normal') }}</dd></div>
            <div><dt>Published comments</dt><dd>{{ $communityStats['published'] ?? 0 }}</dd></div>
            <div><dt>Pending comments</dt><dd>{{ $communityStats['pending'] ?? 0 }}</dd></div>
            <div><dt>Spam outcomes</dt><dd>{{ $communityStats['spam'] ?? 0 }}</dd></div>
        </dl>
    </section>

    @if(! $user->trashed() && auth()->id() !== $user->id && auth()->user()->canAccessModule('Users','Edit') && auth()->user()->canAccessModule('Roles & Permissions','Edit'))
    <section class="card uc-action-card">
        <span class="uc-eyebrow">Access Control</span>
        <h3>Update account access</h3>
        <form action="{{ route('admin.users.access',$user->id) }}" method="POST" onsubmit="return confirm('Update access and revoke active sessions?')">
            @csrf @method('PATCH')
            <label><span>Access level</span><select class="select" name="access_level"><option value="user" @selected($user->role==='user')>Member</option><option value="admin" @selected($user->role==='admin')>Administrator</option></select></label>
            <label><span>Permission role</span><select class="select" name="role_id"><option value="" disabled {{ $user->role!=='admin'?'selected':'' }}>Choose role</option>@foreach($roles as $role)@continue($role->isSystemRole() && !auth()->user()->isSuperAdmin())<option value="{{ $role->id }}" @selected($user->role_id==$role->id)>{{ $role->name }}</option>@endforeach</select></label>
            <button class="btn btn-secondary" type="submit"><i data-lucide="shield-cog"></i>Update Access</button>
        </form>
    </section>
    @endif


    @if(! $user->trashed() && auth()->id() !== $user->id && auth()->user()->canAccessModule('Users','Edit'))
    <section class="card uc-action-card">
        <span class="uc-eyebrow">Community Trust</span>
        <h3>Moderation level</h3>
        <p>
            Normal users are manually reviewed until they earn trust.
            Trusted users publish clean comments instantly.
            Restricted users always require approval.
        </p>
        <form action="{{ route('admin.users.community-trust',$user->id) }}" method="POST">
            @csrf @method('PATCH')
            <label>
                <span>Trust level</span>
                <select class="select" name="community_trust_level">
                    <option value="normal" @selected(($user->community_trust_level ?? 'normal')==='normal')>Normal</option>
                    <option value="trusted" @selected($user->community_trust_level==='trusted')>Trusted</option>
                    <option value="restricted" @selected($user->community_trust_level==='restricted')>Restricted</option>
                </select>
            </label>
            <label>
                <span>Restriction reason</span>
                <textarea class="textarea" name="community_restriction_reason" rows="3" placeholder="Required only when Restricted">{{ $user->community_restriction_reason }}</textarea>
            </label>
            <button class="btn btn-secondary" type="submit">
                <i data-lucide="shield-check"></i>Update Community Trust
            </button>
        </form>
    </section>
    @endif

    @if(! $user->trashed() && $user->status==='active' && auth()->id() !== $user->id && auth()->user()->canAccessModule('Users','Edit'))
    <section class="card uc-action-card uc-action-card--danger">
        <span class="uc-eyebrow">Account Safety</span>
        <h3>Suspend account</h3>
        <p>Suspension revokes database-backed active sessions immediately.</p>
        <form action="{{ route('admin.users.suspend',$user->id) }}" method="POST" onsubmit="return confirm('Suspend {{ addslashes($user->name) }}?')">
            @csrf
            <label><span>Reason <b>*</b></span><textarea class="textarea" name="suspension_reason" rows="4" required placeholder="Policy or safety reason..."></textarea></label>
            <label><span>Suspended until</span><input class="input" type="datetime-local" name="suspended_until"></label>
            <button class="btn btn-danger" type="submit"><i data-lucide="user-x"></i>Suspend Account</button>
        </form>
    </section>
    @endif

    @if(! $user->trashed() && auth()->id() !== $user->id && auth()->user()->canAccessModule('Users','Delete'))
    <section class="card uc-action-card uc-action-card--danger uc-delete-zone">
        <span class="uc-eyebrow">Danger Zone</span>
        <h3>Delete account</h3>
        <p>Soft deletion blocks sign-in, revokes sessions and preserves public/history records. A deleted account can be restored by an authorized admin.</p>
        <form action="{{ route('admin.users.destroy',$user->id) }}" method="POST" onsubmit="return confirm('Delete {{ addslashes($user->name) }}? This account will be signed out immediately.')">
            @csrf @method('DELETE')
            <label><span>Deletion reason <b>*</b></span><textarea class="textarea" name="deletion_reason" rows="4" required maxlength="1000" placeholder="Why is this account being deleted?"></textarea></label>
            <label><span>Type DELETE to confirm <b>*</b></span><input class="input" type="text" name="delete_confirmation" required autocomplete="off" pattern="DELETE" placeholder="DELETE"></label>
            <button class="btn btn-danger" type="submit"><i data-lucide="trash-2"></i>Delete Account</button>
        </form>
    </section>
    @endif

    @if($user->trashed() && auth()->user()->canAccessModule('Users','Delete'))
    <section class="card uc-action-card uc-action-card--restore">
        <span class="uc-eyebrow">Recovery</span>
        <h3>Restore account</h3>
        <p>Restore this user with their previous content, role, preferences and account status intact.</p>
        <form action="{{ route('admin.users.restore',$user->id) }}" method="POST" onsubmit="return confirm('Restore {{ addslashes($user->name) }}?')">
            @csrf
            <button class="btn btn-primary" type="submit"><i data-lucide="rotate-ccw"></i>Restore Account</button>
        </form>
    </section>
    @endif
</aside>
</div>
</div>
@endsection
