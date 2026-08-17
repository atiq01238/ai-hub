@extends('layouts.admin')
@section('title', 'System Health')
@section('content')
<x-page-header title="System Health" subtitle="Live operational checks against this Laravel installation" :breadcrumb="['System', 'System Health']">
    <x-slot:actions><a href="{{ route('admin.system.health') }}" class="btn btn-secondary btn-sm"><i data-lucide="refresh-cw"></i> Refresh Checks</a></x-slot:actions>
</x-page-header>
<div class="grid-12" style="margin-bottom:20px;">
    <div class="col-4 card card-pad" style="text-align:center;background:linear-gradient(135deg,rgba(52,211,153,.08),rgba(34,211,238,.05));"><div class="cell-sub">Operational Health Score</div><div class="font-display" style="font-size:52px;font-weight:700;margin:8px 0;color:{{ $critical ? 'var(--neg)' : ($warnings ? 'var(--warn)' : 'var(--pos)') }}">{{ $overallPercent }}%</div><span class="badge badge-{{ $critical ? 'neg' : ($warnings ? 'warn' : 'pos') }}">{{ $critical ? 'Critical Attention' : ($warnings ? 'Degraded' : 'All Systems Operational') }}</span></div>
    <div class="col-8 card card-pad"><div class="section-title">Check Summary</div><div class="grid-3" style="margin-top:18px;"><div><div class="cell-sub">Healthy</div><div style="font-size:28px;font-weight:700;color:var(--pos);">{{ $healthy }}</div></div><div><div class="cell-sub">Warnings</div><div style="font-size:28px;font-weight:700;color:var(--warn);">{{ $warnings }}</div></div><div><div class="cell-sub">Critical</div><div style="font-size:28px;font-weight:700;color:var(--neg);">{{ $critical }}</div></div></div><div class="cell-sub" style="margin-top:20px;">Snapshot generated {{ $generatedAt->format('M j, Y · g:i:s A') }}. Historical uptime requires scheduled snapshots; this page intentionally reports live truth only.</div></div>
</div>
<div class="grid-4">
@foreach($checks as $check)
<div class="card card-pad"><div class="flex items-center justify-between" style="margin-bottom:12px;"><div class="kpi-icon"><i data-lucide="{{ $check['icon'] }}"></i></div><span class="badge badge-{{ $check['status'] }}">{{ $check['label'] }}</span></div><b>{{ $check['name'] }}</b><div class="cell-sub" style="margin-top:5px;min-height:34px;">{{ $check['detail'] }}</div></div>
@endforeach
</div>
@endsection
