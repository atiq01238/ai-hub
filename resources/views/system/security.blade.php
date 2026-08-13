@extends('layouts.admin')
@section('title', 'Security Center')

@section('content')

<x-page-header title="Security Center" subtitle="Real login activity and active sessions for your account" :breadcrumb="['System', 'Security']" />

@if (session('status'))
    <div class="alert alert-success" style="margin-bottom:16px;">{{ session('status') }}</div>
@endif

<div class="kpi-grid" style="grid-template-columns:repeat(3,1fr); margin-bottom:20px;">
    <x-kpi-card icon="shield-check" label="2FA Status" value="{{ $user->two_factor_enabled ? 'Enabled' : 'Disabled' }}" />
    <x-kpi-card icon="alert-triangle" label="Failed Logins (24h)" value="{{ $failed24h }}" />
    <x-kpi-card icon="monitor" label="Active Sessions" value="{{ $activeSessions->count() }}" />
</div>

@unless ($user->two_factor_enabled)
<div class="card card-pad" style="margin-bottom:20px; border-color:var(--warn);">
    <div class="flex items-center gap-12">
        <div class="kpi-icon" style="color:var(--warn);"><i data-lucide="shield-alert"></i></div>
        <div style="flex:1;">
            <b>Two-factor authentication is off</b>
            <div class="text-sub" style="font-size:12.5px;">Turn it on for a real extra layer of protection.</div>
        </div>
        <a href="{{ route('admin.system.2fa') }}" class="btn btn-primary btn-sm">Enable 2FA</a>
    </div>
</div>
@endunless

<div class="card" style="margin-bottom:20px;">
    <div class="card-head"><h3>Active Sessions</h3></div>
    @if (! $usingDatabaseSessions)
        <div class="card-pad text-sub" style="font-size:12.5px;">
            Your app's session driver is set to "<b>{{ config('session.driver') }}</b>" — active
            sessions can only be listed when using the "database" driver. Set
            <code>SESSION_DRIVER=database</code> in your <code>.env</code> and run
            <code>php artisan session:table && php artisan migrate</code> to enable this.
        </div>
    @else
        <div class="table-wrap">
        <table class="data-table">
            <thead><tr><th>Device / Browser</th><th>IP Address</th><th>Last Active</th><th></th></tr></thead>
            <tbody>
            @forelse ($activeSessions as $session)
            <tr>
                <td class="text-sub" style="font-size:12px; max-width:320px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">
                    {{ $session->user_agent ?? 'Unknown device' }}
                    @if ($session->id === session()->getId())<span class="badge badge-pos" style="margin-left:6px;">This device</span>@endif
                </td>
                <td class="mono">{{ $session->ip_address }}</td>
                <td class="cell-sub">{{ \Illuminate\Support\Carbon::createFromTimestamp($session->last_activity)->diffForHumans() }}</td>
                <td>
                    @if ($session->id !== session()->getId())
                    <form action="{{ route('admin.system.security.revoke-session', $session->id) }}" method="POST">
                        @csrf
                        <button type="submit" class="btn btn-ghost btn-sm">Revoke</button>
                    </form>
                    @endif
                </td>
            </tr>
            @empty
            <tr><td colspan="4" class="text-sub" style="text-align:center; padding:24px;">No active sessions found.</td></tr>
            @endforelse
            </tbody>
        </table>
        </div>
    @endif
</div>

<div class="card">
    <div class="card-head"><h3>Recent Login Attempts</h3></div>
    <div class="table-wrap">
    <table class="data-table">
        <thead><tr><th>Email</th><th>IP Address</th><th>Result</th><th>When</th></tr></thead>
        <tbody>
        @forelse ($recentAttempts as $attempt)
        <tr>
            <td class="text-sub">{{ $attempt->email }}</td>
            <td class="mono">{{ $attempt->ip_address }}</td>
            <td><span class="badge {{ $attempt->successful ? 'badge-pos' : 'badge-neg' }}">{{ $attempt->successful ? 'Success' : 'Failed' }}</span></td>
            <td class="cell-sub">{{ $attempt->created_at->diffForHumans() }}</td>
        </tr>
        @empty
        <tr><td colspan="4" class="text-sub" style="text-align:center; padding:24px;">No login attempts logged yet.</td></tr>
        @endforelse
        </tbody>
    </table>
    </div>
</div>
@endsection
