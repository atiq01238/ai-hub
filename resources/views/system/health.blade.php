@extends('layouts.admin')
@section('title','System Health')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/pages/system-operations.css') }}">
@endpush

@section('content')
<div class="so-page">
<x-page-header title="System Health" subtitle="Live infrastructure checks across database, cache, storage, queue, mail, runtime and automation." :breadcrumb="['System','System Health']">
<x-slot:actions><a href="{{ route('admin.system.health') }}" class="btn btn-secondary"><i data-lucide="refresh-cw"></i>Refresh Snapshot</a></x-slot:actions>
</x-page-header>

<section class="card so-health-hero">
<div class="so-health-score"><div class="so-score-ring so-score-ring--health" style="--score:{{ $overallPercent }}"><strong>{{ $overallPercent }}</strong><span>%</span></div><div><span class="so-eyebrow">Overall Readiness</span><h2>{{ $overallPercent >= 90 ? 'Systems operating normally' : ($overallPercent >= 70 ? 'Operational with warnings' : 'Critical attention required') }}</h2><p>Snapshot generated {{ $generatedAt->format('M j, Y g:i:s A') }} from {{ count($checks) }} active infrastructure checks.</p></div></div>
<div class="so-health-counts"><div><strong>{{ $healthy }}</strong><span>Healthy</span></div><div><strong>{{ $warnings }}</strong><span>Warnings</span></div><div><strong>{{ $critical }}</strong><span>Critical</span></div></div>
</section>

<section class="so-health-grid">
@foreach($checks as $check)
<article class="card so-health-card so-health-card--{{ $check['status'] }}">
<div class="so-health-card__top"><span class="so-health-card__icon"><i data-lucide="{{ $check['icon'] }}"></i></span><span class="so-status {{ $check['status']==='pos'?'is-good':($check['status']==='neg'?'is-bad':'is-warning') }}"><i data-lucide="{{ $check['status']==='pos'?'circle-check':($check['status']==='neg'?'circle-x':'triangle-alert') }}"></i>{{ $check['label'] }}</span></div>
<h2>{{ $check['name'] }}</h2>
<p>{{ $check['detail'] }}</p>
@if(!empty($check['meta']))
<div class="so-health-card__meta">@foreach($check['meta'] as $key=>$value)<span><small>{{ ucwords(str_replace('_',' ',$key)) }}</small><strong>{{ $value ?? '—' }}</strong></span>@endforeach</div>
@endif
</article>
@endforeach
</section>

<section class="card so-panel">
<header class="so-card-head"><div><span class="so-eyebrow">Operational Interpretation</span><h2>What this health score means</h2><p>These checks validate application readiness, not external uptime guarantees.</p></div><i data-lucide="stethoscope"></i></header>
<div class="so-guidance-grid">
<div><span class="is-good"><i data-lucide="circle-check"></i></span><strong>Healthy</strong><p>Check passed under the current runtime configuration.</p></div>
<div><span class="is-warning"><i data-lucide="triangle-alert"></i></span><strong>Warning</strong><p>System can operate, but configuration, capacity or telemetry deserves attention.</p></div>
<div><span class="is-bad"><i data-lucide="circle-x"></i></span><strong>Critical</strong><p>A required dependency is unavailable or a production-risk configuration is active.</p></div>
</div>
</section>
</div>
@endsection
