@extends('layouts.admin')
@section('title', 'System Overview')
@section('content')
<x-page-header title="System Operations Center" subtitle="Health, security, backups, APIs and operational risk in one place" :breadcrumb="['System', 'Overview']">
    <x-slot:actions><a href="{{ route('admin.system.health') }}" class="btn btn-secondary btn-sm"><i data-lucide="heart-pulse"></i> Live Health</a></x-slot:actions>
</x-page-header>

<div class="kpi-grid" style="grid-template-columns:repeat(4,1fr);margin-bottom:20px;">
    <x-kpi-card icon="activity" label="Health Score" value="{{ $healthData['overallPercent'] }}%" />
    <x-kpi-card icon="shield-check" label="Failed Logins · 24h" value="{{ $security['failed_24h'] }}" />
    <x-kpi-card icon="octagon-alert" label="Open Errors" value="{{ $security['open_errors'] }}" />
    <x-kpi-card icon="database-backup" label="Backups" value="{{ count($backupItems) }}" />
</div>

<div class="grid-12" style="margin-bottom:20px;">
    <div class="col-8 card card-pad">
        <div class="flex items-center justify-between" style="margin-bottom:16px;"><div><div class="section-title">Platform Health</div><div class="cell-sub">Live checks generated {{ $healthData['generatedAt']->diffForHumans() }}</div></div><span class="badge badge-{{ $healthData['critical'] ? 'neg' : ($healthData['warnings'] ? 'warn' : 'pos') }}">{{ $healthData['critical'] ? 'Action Required' : ($healthData['warnings'] ? 'Needs Attention' : 'Operational') }}</span></div>
        <div class="grid-2" style="gap:10px;">
            @foreach($healthData['checks'] as $check)
                <a href="{{ route('admin.system.health') }}" class="card card-pad" style="text-decoration:none;padding:14px;">
                    <div class="flex items-center justify-between"><div class="flex items-center gap-8"><i data-lucide="{{ $check['icon'] }}" style="width:16px;height:16px;"></i><b style="font-size:13px;">{{ $check['name'] }}</b></div><span class="badge badge-{{ $check['status'] }}">{{ $check['label'] }}</span></div>
                    <div class="cell-sub" style="margin-top:6px;">{{ $check['detail'] }}</div>
                </a>
            @endforeach
        </div>
    </div>
    <div class="col-4 card card-pad">
        <div class="section-title">Security Posture</div>
        <div style="display:grid;gap:14px;margin-top:16px;">
            <div class="flex justify-between"><span class="text-sub">Admins without 2FA</span><b>{{ $security['admins_without_2fa'] }}</b></div>
            <div class="flex justify-between"><span class="text-sub">Failed logins · 24h</span><b>{{ $security['failed_24h'] }}</b></div>
            <div class="flex justify-between"><span class="text-sub">Open application errors</span><b>{{ $security['open_errors'] }}</b></div>
        </div>
        <a href="{{ route('admin.system.security') }}" class="btn btn-secondary btn-sm" style="width:100%;justify-content:center;margin-top:18px;">Open Security Center</a>
    </div>
</div>

<div class="grid-12">
    <div class="col-6 card card-pad">
        <div class="flex items-center justify-between"><div class="section-title">Backup Protection</div><a href="{{ route('admin.system.backups') }}" class="btn btn-ghost btn-sm">Manage</a></div>
        @if($latest = collect($backupItems)->first())
            <div style="font-size:26px;font-weight:700;margin-top:12px;">{{ $latest['created_at']->diffForHumans() }}</div><div class="cell-sub">Latest {{ ucfirst($latest['type']) }} backup · {{ $latest['size'] }}</div>
        @else
            <div class="text-sub" style="padding:24px 0;">No local backups found yet. Create the first protected snapshot from Backup Center.</div>
        @endif
    </div>
    <div class="col-6 card card-pad">
        <div class="flex items-center justify-between"><div class="section-title">API Operations</div><a href="{{ route('admin.system.api-monitoring') }}" class="btn btn-ghost btn-sm">Monitor</a></div>
        <div class="grid-3" style="gap:10px;margin-top:14px;"><div><div class="cell-sub">Configured</div><b style="font-size:20px;">{{ $apiData['providers']->where('configured', true)->count() }}</b></div><div><div class="cell-sub">Requests Today</div><b style="font-size:20px;">{{ number_format($apiData['todayRequests']) }}</b></div><div><div class="cell-sub">Error Rate</div><b style="font-size:20px;">{{ $apiData['errorRate'] }}%</b></div></div>
    </div>
</div>
@endsection
