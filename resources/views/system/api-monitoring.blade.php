@extends('layouts.admin')
@section('title','API Monitoring')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/pages/system-operations.css') }}">
@endpush

@section('content')
<div class="so-page">
<x-page-header title="API Monitoring" subtitle="Provider configuration, connection tests, request volume, failures and latency telemetry." :breadcrumb="['System','API Monitoring']" />

@if(session('status'))<div class="alert alert-success so-flash"><i data-lucide="check-circle-2"></i><span>{{ session('status') }}</span></div>@endif
@if(session('error'))<div class="alert alert-danger so-flash"><i data-lucide="circle-alert"></i><span>{{ session('error') }}</span></div>@endif
@if(!$hasLogs)<div class="so-callout is-warning so-telemetry-warning"><i data-lucide="database"></i><div><strong>API telemetry table is not available</strong><p>Run the project migrations to create <code>api_request_logs</code>. Provider configuration and connection testing can still be inspected.</p></div></div>@endif

<section class="so-kpis">
@foreach([
['Requests today',$todayRequests,'send',''],
['Failures today',$todayFailures,'triangle-alert','red'],
['Error rate',$errorRate.'%','percent','amber'],
['Average latency',$avgLatency.' ms','timer','violet'],
] as [$label,$value,$icon,$tone])
<article class="so-kpi so-kpi--{{ $tone }}"><span><i data-lucide="{{ $icon }}"></i></span><div><small>{{ $label }}</small><strong>{{ $value }}</strong></div></article>
@endforeach
</section>

<section class="so-provider-grid">
@foreach($providers as $provider)
<article class="card so-provider so-provider--{{ $provider['status'] }}">
<div class="so-provider__top"><span class="so-provider__icon"><i data-lucide="{{ $provider['icon'] }}"></i></span><span class="so-status {{ $provider['status']==='connected'?'is-good':($provider['status']==='error'?'is-bad':($provider['status']==='ready'?'is-info':'')) }}">{{ ucfirst($provider['status']) }}</span></div>
<h2>{{ $provider['name'] }}</h2>
<p>{{ $provider['configured'] ? 'Credential configured' : $provider['key_name'].' is missing' }}</p>
<dl><div><dt>Requests today</dt><dd>{{ number_format($provider['requests']) }}</dd></div><div><dt>Failures</dt><dd>{{ number_format($provider['failures']) }}</dd></div><div><dt>Avg latency</dt><dd>{{ $provider['avgLatency'] }} ms</dd></div><div><dt>Last event</dt><dd>{{ $provider['last']?->created_at?->diffForHumans() ?? 'Never' }}</dd></div></dl>
@if(auth()->user()->canAccessModule('API Monitoring','Edit'))
<form method="POST" action="{{ route('admin.system.api-monitoring.test',$provider['key']) }}">@csrf<button class="btn btn-secondary" type="submit" {{ !$provider['configured']?'disabled':'' }}><i data-lucide="plug-zap"></i>Test Connection</button></form>
@endif
</article>
@endforeach
</section>

<div class="so-grid so-grid--api">
<section class="card so-panel">
<header class="so-card-head"><div><span class="so-eyebrow">Traffic Telemetry</span><h2>Requests · Last 7 days</h2><p>Successful and failed API events recorded by the monitoring service.</p></div><span class="so-source"><span></span>Telemetry</span></header>
<div class="so-chart-wrap so-chart-wrap--api"><canvas id="apiChart"></canvas></div>
</section>
<aside class="card so-panel">
<header class="so-card-head"><div><span class="so-eyebrow">Event Stream</span><h2>Recent API events</h2></div><i data-lucide="list-activity"></i></header>
<div class="so-api-events">
@forelse($recent as $log)
<article><span class="so-api-events__icon {{ $log->successful?'is-good':'is-bad' }}"><i data-lucide="{{ $log->successful?'circle-check':'circle-x' }}"></i></span><div><strong>{{ strtoupper($log->provider) }}</strong><small>{{ $log->duration_ms ?? 0 }} ms · {{ $log->created_at->diffForHumans() }}</small></div><span class="so-api-code">{{ $log->status_code ?: 'ERR' }}</span></article>
@empty<div class="so-empty so-empty--small"><span><i data-lucide="activity"></i></span><h3>No API telemetry yet</h3><p>Run a configured provider connection test to create the first event.</p></div>@endforelse
</div>
</aside>
</div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded',()=>{
const t=@json($trend), canvas=document.getElementById('apiChart');
if(canvas&&typeof Chart!=='undefined'){new Chart(canvas,{type:'bar',data:{labels:t.map(x=>x.label),datasets:[{label:'Successful',data:t.map(x=>x.success),backgroundColor:'rgba(52,211,153,.58)',borderRadius:5,stack:'traffic'},{label:'Failed',data:t.map(x=>x.failed),backgroundColor:'rgba(251,113,133,.68)',borderRadius:5,stack:'traffic'}]},options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{labels:{color:'#8d98ad',boxWidth:8,font:{size:10}}}},scales:{x:{stacked:true,grid:{display:false},border:{display:false},ticks:{color:'#69758b'}},y:{stacked:true,beginAtZero:true,border:{display:false},grid:{color:'rgba(255,255,255,.045)'},ticks:{color:'#69758b',precision:0}}}}});}
});
</script>
@endpush
