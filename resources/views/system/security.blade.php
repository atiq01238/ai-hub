@extends('layouts.admin')
@section('title', 'Security Center')

@section('content')

<x-page-header title="Security Center" subtitle="Account, session, and login security in one place" :breadcrumb="['System', 'Security Center']" />

<div class="grid-12" style="margin-bottom:20px;">
    <div class="col-4 card card-pad" style="text-align:center;">
        <div class="cell-sub" style="margin-bottom:10px;">Security Score</div>
        <x-score-meter :value="88" :segments="12" />
        <div class="text-sub" style="font-size:12px; margin-top:8px;">Strong — 2 recommendations</div>
    </div>
    <div class="col-8">
        <div class="kpi-grid" style="grid-template-columns:repeat(4,1fr); margin-bottom:0;">
            <x-kpi-card icon="monitor" label="Active Sessions" value="3" />
            <x-kpi-card icon="log-in" label="Recent Logins" value="14" />
            <x-kpi-card icon="shield-x" label="Failed Attempts" value="2" />
            <x-kpi-card icon="key-round" label="Password Status" value="Good" />
        </div>
    </div>
</div>

<div class="grid-12" style="margin-bottom:20px;">
    <div class="col-6 card card-pad">
        <div class="section-title">Account Security</div>
        <div class="flex items-center justify-between" style="padding:10px 0; border-bottom:1px solid var(--border-soft);">
            <div><b style="font-size:13px;">Password</b><div class="cell-sub">Last changed 62 days ago</div></div>
            <button class="btn btn-secondary btn-sm">Change</button>
        </div>
        <div class="flex items-center justify-between" style="padding:10px 0; border-bottom:1px solid var(--border-soft);">
            <div><b style="font-size:13px;">Two-Factor Authentication</b><div class="cell-sub">Enabled via Authenticator App</div></div>
            <a href="{{ url('/system/2fa') }}" class="btn btn-secondary btn-sm">Manage</a>
        </div>
        <div class="flex items-center justify-between" style="padding:10px 0; border-bottom:1px solid var(--border-soft);">
            <div><b style="font-size:13px;">Recovery Methods</b><div class="cell-sub">1 recovery email configured</div></div>
            <button class="btn btn-secondary btn-sm">Edit</button>
        </div>
        <div class="flex items-center justify-between" style="padding:10px 0;">
            <div><b style="font-size:13px;">Security Questions</b><div class="cell-sub">Not configured</div></div>
            <button class="btn btn-secondary btn-sm">Set Up</button>
        </div>
    </div>

    <div class="col-6 card card-pad">
        <div class="section-title">Session Management</div>
        @php
        $sessions = [
            ['dev'=>'MacBook Pro · Chrome','loc'=>'Karachi, PK','active'=>'Current session','current'=>true],
            ['dev'=>'iPhone 16 · Safari','loc'=>'Karachi, PK','active'=>'2 hr ago','current'=>false],
            ['dev'=>'Windows PC · Edge','loc'=>'Lahore, PK','active'=>'1 day ago','current'=>false],
        ];
        @endphp
        @foreach($sessions as $s)
        <div class="flex items-center justify-between" style="padding:10px 0; border-bottom:1px solid var(--border-soft);">
            <div class="flex items-center gap-12">
                <div class="kpi-icon"><i data-lucide="{{ str_contains($s['dev'],'iPhone') ? 'smartphone' : 'laptop' }}"></i></div>
                <div>
                    <b style="font-size:13px;">{{ $s['dev'] }}</b>
                    <div class="cell-sub">{{ $s['loc'] }} · {{ $s['active'] }}</div>
                </div>
            </div>
            @if($s['current'])<span class="badge badge-pos">This device</span>@else<button class="btn btn-ghost btn-sm">Revoke</button>@endif
        </div>
        @endforeach
    </div>
</div>

<div class="grid-12">
    <div class="col-6 card">
        <div class="card-head"><h3>Login Security</h3></div>
        <div class="table-wrap">
        <table class="data-table">
            <thead><tr><th>Type</th><th>Device</th><th>Time</th><th>Status</th></tr></thead>
            <tbody>
                <tr><td class="text-sub">Login</td><td class="text-sub">MacBook Pro</td><td class="cell-sub">Today, 9:02 AM</td><td><x-status-badge status="Success" type="pos"/></td></tr>
                <tr><td class="text-sub">Login</td><td class="text-sub">Unknown Device</td><td class="cell-sub">Yesterday, 11:48 PM</td><td><x-status-badge status="Failed" type="neg"/></td></tr>
                <tr><td class="text-sub">Login</td><td class="text-sub">Unknown Device</td><td class="cell-sub">Yesterday, 11:47 PM</td><td><x-status-badge status="Suspicious" type="warn"/></td></tr>
            </tbody>
        </table>
        </div>
    </div>
    <div class="col-6 card card-pad">
        <div class="section-title">Security Alerts</div>
        @foreach([
            ['New device login detected','neg','6 hr ago'],
            ['Multiple failed login attempts','warn','1 day ago'],
            ['Admin role changed for Imran Khan','info','2 days ago'],
        ] as $alert)
        <div class="flex items-center gap-12" style="padding:9px 0; border-bottom:1px solid var(--border-soft);">
            <span class="dot-indicator" style="background:var(--{{ $alert[1] }});"></span>
            <div style="flex:1; font-size:13px;">{{ $alert[0] }}</div>
            <span class="cell-sub">{{ $alert[2] }}</span>
        </div>
        @endforeach
    </div>
</div>
@endsection
