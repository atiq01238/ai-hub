@extends('layouts.admin')
@section('title','System Overview')
@push('styles')<link rel="stylesheet" href="{{ asset('css/pages/final-polish.css') }}">@endpush
@section('content')
@php
$checks=collect($healthData['checks']??[]);
$healthy=$checks->where('status','pos')->count();
$warnings=$checks->where('status','warn')->count();
$critical=$checks->where('status','neg')->count();
$total=$checks->count();
$healthPct=$total?(int)round(($healthy/$total)*100):100;
$latestBackup=collect($backupItems)->first();
$providers=collect($apiData['providers']??[]);
@endphp
<div class="fp-page">
<x-page-header title="System Overview" subtitle="One operational snapshot across infrastructure, security, backups and external API activity." :breadcrumb="['System','Overview']">
<x-slot:actions><a href="{{ route('admin.system.health') }}" class="btn btn-secondary"><i data-lucide="stethoscope"></i>System Health</a><a href="{{ route('admin.system.security') }}" class="btn btn-primary"><i data-lucide="shield-check"></i>Security Center</a></x-slot:actions>
</x-page-header>

<section class="fp-overview-hero">
<div class="fp-health-orb" style="--score:{{ $healthPct }}"><strong>{{ $healthPct }}</strong><span>% healthy</span></div>
<div class="fp-overview-hero__copy"><span class="fp-eyebrow">Operations Command Center</span><h1>{{ $critical ? 'Critical attention required' : ($warnings ? 'Operational with warnings' : 'Systems operating normally') }}</h1><p>Live summary assembled from the existing System Health, Backup, API Monitoring and Security services.</p></div>
<div class="fp-overview-hero__signals"><div><span>Healthy</span><strong>{{ $healthy }}</strong></div><div><span>Warnings</span><strong>{{ $warnings }}</strong></div><div><span>Critical</span><strong>{{ $critical }}</strong></div></div>
</section>

<section class="fp-kpis">
@foreach([
['Failed Logins · 24h',$security['failed_24h']??0,'log-in','red'],
['Admins Without 2FA',$security['admins_without_2fa']??0,'shield-alert','amber'],
['Open Errors',$security['open_errors']??0,'bug','red'],
['Configured APIs',$providers->where('configured',true)->count(),'plug-zap','violet'],
] as [$label,$value,$icon,$tone])
<article class="fp-kpi fp-kpi--{{ $tone }}"><span><i data-lucide="{{ $icon }}"></i></span><div><small>{{ $label }}</small><strong>{{ number_format($value) }}</strong></div></article>
@endforeach
</section>

<div class="fp-overview-grid">
<section class="card fp-panel">
<header class="fp-card-head"><div><span class="fp-eyebrow">Infrastructure</span><h2>Health checks</h2><p>Current readiness of core application dependencies.</p></div><a href="{{ route('admin.system.health') }}" class="btn btn-ghost btn-sm">Open Health</a></header>
<div class="fp-health-list">
@forelse($checks as $check)
<div><span class="fp-health-icon"><i data-lucide="{{ $check['icon']??'activity' }}"></i></span><div><strong>{{ $check['name']??'Check' }}</strong><small>{{ $check['detail']??'' }}</small></div><span class="fp-state {{ ($check['status']??'warn')==='pos'?'is-good':(($check['status']??'warn')==='neg'?'is-bad':'is-warning') }}">{{ $check['label']??'Unknown' }}</span></div>
@empty<div class="fp-empty fp-empty--small"><p>No health checks returned.</p></div>@endforelse
</div>
</section>

<aside class="fp-overview-side">
<section class="card fp-snapshot-card">
<div class="fp-snapshot-card__head"><span class="fp-eyebrow">Backup Protection</span><a href="{{ route('admin.system.backups') }}">Manage</a></div>
@if($latestBackup)<strong>{{ $latestBackup['created_at']->diffForHumans() }}</strong><p>Latest {{ ucfirst($latestBackup['type']) }} snapshot · {{ $latestBackup['size'] }}</p>@else<strong>No backups yet</strong><p>Create the first protected local snapshot.</p>@endif
</section>
<section class="card fp-snapshot-card">
<div class="fp-snapshot-card__head"><span class="fp-eyebrow">API Operations</span><a href="{{ route('admin.system.api-monitoring') }}">Monitor</a></div>
<div class="fp-mini-grid"><div><strong>{{ $providers->where('configured',true)->count() }}</strong><span>Configured</span></div><div><strong>{{ number_format($apiData['todayRequests']??0) }}</strong><span>Requests Today</span></div><div><strong>{{ $apiData['errorRate']??0 }}%</strong><span>Error Rate</span></div></div>
</section>
<section class="card fp-snapshot-card">
<div class="fp-snapshot-card__head"><span class="fp-eyebrow">Quick Access</span></div>
<div class="fp-quick-links"><a href="{{ route('admin.system.errors.index') }}"><i data-lucide="bug"></i>Error Monitoring</a><a href="{{ route('admin.system.automation-monitor') }}"><i data-lucide="workflow"></i>Automation Monitor</a><a href="{{ route('admin.system.activity-logs') }}"><i data-lucide="scroll-text"></i>Activity Logs</a></div>
</section>
</aside>
</div>
</div>
@endsection
