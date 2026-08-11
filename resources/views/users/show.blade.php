@extends('layouts.admin')
@section('title', $user->name . ' · User Detail')

@section('content')

<x-page-header
    title="{{ $user->name }}"
    subtitle="{{ $user->email }} · {{ ucfirst($user->role) }} · Joined {{ $user->created_at->format('M Y') }}"
    :breadcrumb="['Users & Community', 'Users', $user->name]">
    <x-slot:actions>
        @if ($user->status === 'active')
        <form action="{{ route('admin.users.suspend', $user->id) }}" method="POST">
            @csrf
            <button type="submit" class="btn btn-danger btn-sm"><i data-lucide="user-x"></i> Suspend</button>
        </form>
        @else
        <form action="{{ route('admin.users.activate', $user->id) }}" method="POST">
            @csrf
            <button type="submit" class="btn btn-primary btn-sm"><i data-lucide="user-check"></i> Activate</button>
        </form>
        @endif
    </x-slot:actions>
</x-page-header>

@if (session('status'))
    <div class="alert alert-success" style="margin-bottom:16px;">{{ session('status') }}</div>
@endif

<div class="kpi-grid" style="grid-template-columns:repeat(2,1fr); margin-bottom:20px;">
    <x-kpi-card icon="star" label="Reviews Submitted" value="{{ $user->reviews_count }}" />
    <x-kpi-card icon="calendar" label="Member Since" value="{{ $user->created_at->format('M Y') }}" />
</div>

<div class="grid-12">
    <div class="col-8 card">
        <div class="card-head"><h3>Recent Reviews</h3></div>
        @forelse ($recentReviews as $review)
        <div class="flex items-center gap-12" style="padding:13px 20px; border-bottom:1px solid var(--border-soft);">
            <div class="kpi-icon"><i data-lucide="star"></i></div>
            <div style="flex:1; font-size:13px;">
                Rated <b>{{ $review->tool->name ?? 'a tool' }}</b> {{ number_format($review->rating, 1) }}/5
                @if ($review->body) — {{ \Illuminate\Support\Str::limit($review->body, 60) }} @endif
            </div>
            <span class="cell-sub">{{ $review->created_at->diffForHumans() }}</span>
        </div>
        @empty
        <div class="text-sub" style="padding:24px; text-align:center;">No reviews submitted yet.</div>
        @endforelse
    </div>
    <div class="col-4 card card-pad">
        <div class="section-title">Profile</div>
        <div class="flex items-center justify-between" style="padding:9px 0; border-bottom:1px solid var(--border-soft);"><span class="cell-sub">Access Level</span><span class="badge badge-violet">{{ ucfirst($user->role) }}</span></div>
        <div class="flex items-center justify-between" style="padding:9px 0; border-bottom:1px solid var(--border-soft);"><span class="cell-sub">Email</span><span style="font-size:12.5px;">{{ $user->email }}</span></div>
        <div class="flex items-center justify-between" style="padding:9px 0; border-bottom:1px solid var(--border-soft);"><span class="cell-sub">Joined</span><span style="font-size:12.5px;">{{ $user->created_at->format('M Y') }}</span></div>
        <div class="flex items-center justify-between" style="padding:9px 0;"><span class="cell-sub">Status</span><x-status-badge status="{{ ucfirst($user->status) }}" type="{{ $user->status === 'active' ? 'pos' : 'neg' }}" /></div>

        <div class="divider"></div>
        <div class="section-title">Permission Role</div>
        <p class="cell-sub" style="margin-bottom:8px;">Separate from Access Level above — this ties into the granular Roles &amp; Permissions matrix.</p>
        <form action="{{ route('admin.users.assign-role', $user->id) }}" method="POST" class="flex gap-8">
            @csrf
            <select class="select" name="role_id" style="flex:1;">
                <option value="">No role assigned</option>
                @foreach ($roles as $role)
                    <option value="{{ $role->id }}" @selected($user->role_id == $role->id)>{{ $role->name }}</option>                @endforeach
            </select>
            <button type="submit" class="btn btn-secondary btn-sm">Save</button>
        </form>
    </div>
</div>
@endsection
