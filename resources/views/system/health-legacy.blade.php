@extends('layouts.admin')
@section('title', 'System Health')

@section('content')

<x-page-header title="System Health" subtitle="Live checks against your actual app — not simulated" :breadcrumb="['System', 'System Health']" />

<div class="card card-pad" style="margin-bottom:20px; text-align:center; background:linear-gradient(135deg, rgba(52,211,153,.08), rgba(34,211,238,.05));">
    <div class="cell-sub" style="margin-bottom:6px;">Checks Passing</div>
    <div class="font-display" style="font-size:44px; font-weight:700; color:{{ $overallPercent === 100 ? 'var(--pos)' : ($overallPercent >= 70 ? 'var(--warn)' : 'var(--neg)') }};">{{ $overallPercent }}%</div>
    <div style="font-size:13px; font-weight:600; margin-top:6px; color:{{ $overallPercent === 100 ? 'var(--pos)' : 'var(--warn)' }};">
        {{ $overallPercent === 100 ? 'All Systems Operational' : 'Some Checks Need Attention' }}
    </div>
</div>

<div class="grid-3">
    @foreach ($checks as $check)
    <div class="card card-pad">
        <div class="flex items-center justify-between" style="margin-bottom:10px;">
            <div class="kpi-icon"><i data-lucide="{{ $check['icon'] }}"></i></div>
            <span class="badge badge-{{ $check['status'] }}">{{ $check['label'] }}</span>
        </div>
        <b style="font-size:14px;">{{ $check['name'] }}</b>
        <div class="cell-sub" style="margin-top:4px;">{{ $check['detail'] }}</div>
    </div>
    @endforeach
</div>

<p class="text-sub" style="font-size:12px; margin-top:16px;">
    Note: no uptime history chart here — a real 30-day trend needs something checking and logging
    these results on a schedule over time. This page always shows a live snapshot of right now.
    If you want the historical chart, that's a separate small feature (a scheduled task that
    saves these results daily).
</p>
@endsection
@extends('layouts.admin')
@section('title', 'System Health')

@section('content')

<x-page-header title="System Health" subtitle="Live checks against your actual app — not simulated" :breadcrumb="['System', 'System Health']" />

<div class="card card-pad" style="margin-bottom:20px; text-align:center; background:linear-gradient(135deg, rgba(52,211,153,.08), rgba(34,211,238,.05));">
    <div class="cell-sub" style="margin-bottom:6px;">Checks Passing</div>
    <div class="font-display" style="font-size:44px; font-weight:700; color:{{ $overallPercent === 100 ? 'var(--pos)' : ($overallPercent >= 70 ? 'var(--warn)' : 'var(--neg)') }};">{{ $overallPercent }}%</div>
    <div style="font-size:13px; font-weight:600; margin-top:6px; color:{{ $overallPercent === 100 ? 'var(--pos)' : 'var(--warn)' }};">
        {{ $overallPercent === 100 ? 'All Systems Operational' : 'Some Checks Need Attention' }}
    </div>
</div>

<div class="grid-3">
    @foreach ($checks as $check)
    <div class="card card-pad">
        <div class="flex items-center justify-between" style="margin-bottom:10px;">
            <div class="kpi-icon"><i data-lucide="{{ $check['icon'] }}"></i></div>
            <span class="badge badge-{{ $check['status'] }}">{{ $check['label'] }}</span>
        </div>
        <b style="font-size:14px;">{{ $check['name'] }}</b>
        <div class="cell-sub" style="margin-top:4px;">{{ $check['detail'] }}</div>
    </div>
    @endforeach
</div>

<p class="text-sub" style="font-size:12px; margin-top:16px;">
    Note: no uptime history chart here — a real 30-day trend needs something checking and logging
    these results on a schedule over time. This page always shows a live snapshot of right now.
    If you want the historical chart, that's a separate small feature (a scheduled task that
    saves these results daily).
</p>
@endsection
