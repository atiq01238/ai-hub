@extends('layouts.admin')
@section('title','Security Center')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/pages/system-operations.css') }}">
@endpush

@section('content')
<div class="so-page">
<x-page-header title="Security Center" subtitle="Authentication risk, administrator 2FA compliance and active-session control." :breadcrumb="['System','Security Center']">
<x-slot:actions>
    <a href="{{ route('admin.system.2fa') }}" class="btn btn-secondary"><i data-lucide="shield-check"></i>Two-Factor Authentication</a>
</x-slot:actions>
</x-page-header>

@if(session('status'))<div class="alert alert-success so-flash"><i data-lucide="check-circle-2"></i><span>{{ session('status') }}</span></div>@endif

<section class="card so-security-hero">
    <div class="so-security-score">
        <div class="so-score-ring" style="--score:{{ $securityScore }}"><strong>{{ $securityScore }}</strong><span>/100</span></div>
        <div><span class="so-eyebrow">Security Posture</span><h2>{{ $securityScore >= 85 ? 'Strong security posture' : ($securityScore >= 65 ? 'Attention recommended' : 'Elevated security risk') }}</h2><p>Calculated from failed logins, suspicious IP activity, administrator 2FA adoption and enforcement policy.</p></div>
    </div>
    <div class="so-security-policy">
        <span class="so-eyebrow">Admin 2FA Policy</span>
        <strong>{{ $require2fa ? 'Required' : 'Not enforced' }}</strong>
        <small>{{ $twoFactorCompliance }}% administrator compliance</small>
    </div>
</section>

<section class="so-kpis">
@foreach([
['Failed logins · 24h',$failed24h,'log-in','red'],
['Failed logins · 7d',$failed7d,'calendar-x','amber'],
['Suspicious IPs',$suspiciousIps->count(),'scan-eye','red'],
['Admin 2FA compliance',$twoFactorCompliance.'%','badge-check','green'],
['Admins without 2FA',$adminsWithout2fa,'shield-alert','amber'],
['Active admin sessions',$allAdminSessions,'monitor-smartphone',''],
] as [$label,$value,$icon,$tone])
<article class="so-kpi so-kpi--{{ $tone }}"><span><i data-lucide="{{ $icon }}"></i></span><div><small>{{ $label }}</small><strong>{{ $value }}</strong></div></article>
@endforeach
</section>

<div class="so-grid so-grid--security">
<section class="card so-panel">
<header class="so-card-head"><div><span class="so-eyebrow">Authentication Telemetry</span><h2>Recent login attempts</h2><p>Latest authentication events recorded by the security layer.</p></div><i data-lucide="key-round"></i></header>
@if($recentAttempts->count())
<div class="table-wrap"><table class="data-table so-table"><thead><tr><th>Time</th><th>Identity</th><th>IP Address</th><th>Result</th></tr></thead><tbody>
@foreach($recentAttempts as $attempt)
<tr><td><span class="so-muted">{{ $attempt->created_at->format('M j, H:i') }}</span></td><td>{{ $attempt->email ?? 'Unknown identity' }}</td><td><code>{{ $attempt->ip_address ?: '—' }}</code></td><td><span class="so-status {{ $attempt->successful?'is-good':'is-bad' }}"><i data-lucide="{{ $attempt->successful?'circle-check':'circle-x' }}"></i>{{ $attempt->successful?'Successful':'Failed' }}</span></td></tr>
@endforeach
</tbody></table></div>
@else<div class="so-empty so-empty--small"><span><i data-lucide="key-round"></i></span><h3>No login telemetry</h3><p>The login-attempts table is unavailable or no attempts are recorded yet.</p></div>@endif
</section>

<aside class="so-stack">
<section class="card so-panel">
<header class="so-card-head"><div><span class="so-eyebrow">Threat Signals</span><h2>Suspicious IP activity</h2></div><i data-lucide="radar"></i></header>
<div class="so-list">
@forelse($suspiciousIps as $item)
<div><span class="so-list__icon is-risk"><i data-lucide="shield-alert"></i></span><div><strong>{{ $item->ip_address }}</strong><small>{{ $item->attempts }} failed attempts in the last 24 hours</small></div></div>
@empty<div class="so-empty so-empty--tiny"><p>No IP crossed the suspicious-activity threshold.</p></div>@endforelse
</div>
</section>

<section class="card so-panel">
<header class="so-card-head"><div><span class="so-eyebrow">Session Control</span><h2>Your active sessions</h2></div><i data-lucide="monitor-smartphone"></i></header>
@if(!$usingDatabaseSessions)
<div class="so-callout is-warning"><i data-lucide="triangle-alert"></i><div><strong>Database sessions not enabled</strong><p>Per-session revocation requires the database session driver and sessions table.</p></div></div>
@else
<div class="so-session-list">
@forelse($activeSessions as $session)
<article>
<div><strong>{{ $session->ip_address ?: 'Unknown IP' }}</strong><small>Last activity {{ \Illuminate\Support\Carbon::createFromTimestamp($session->last_activity)->diffForHumans() }} · Session {{ \Illuminate\Support\Str::limit($session->id,14) }}</small></div>
@if(auth()->user()->canAccessModule('Security','Edit'))
<form method="POST" action="{{ route('admin.system.security.revoke-session',$session->id) }}" onsubmit="return confirm('Revoke this session?')">@csrf<button class="icon-btn icon-btn--danger" type="submit"><i data-lucide="log-out"></i></button></form>
@endif
</article>
@empty<div class="so-empty so-empty--tiny"><p>No database-backed sessions found for this account.</p></div>@endforelse
</div>
@endif
</section>
</aside>
</div>
</div>
@endsection
