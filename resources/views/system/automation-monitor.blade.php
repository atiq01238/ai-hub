@extends('layouts.admin')
@section('title','Automation Monitor')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/pages/data-reliability.css') }}">
@endpush

@section('content')
<div class="dr-page">
<x-page-header title="Automation Monitor" subtitle="Operate the RSS → duplicate detection → local AI processing pipeline." :breadcrumb="['AI Intelligence','Automation Monitor']">
<x-slot:actions>
<form method="POST" action="{{ route('admin.system.automation-monitor.run-now') }}">@csrf<button class="btn btn-primary"><i data-lucide="refresh-cw"></i>Run Pipeline Now</button></form>
</x-slot:actions>
</x-page-header>

@if(session('status'))<div class="alert alert-success dr-flash"><i data-lucide="check-circle-2"></i><span>{{ session('status') }}</span></div>@endif
@if(session('error'))<div class="alert alert-danger dr-flash"><i data-lucide="circle-alert"></i><span>{{ session('error') }}</span></div>@endif
@if($errors->any())<div class="alert alert-danger dr-flash"><i data-lucide="circle-alert"></i><span>{{ $errors->first() }}</span></div>@endif

<section class="dr-kpis dr-kpis--five">
@foreach([
['Active Sources',$activeSources,'radio-tower','green'],
['News Today',$newsToday,'newspaper',''],
['Published Today',$publishedToday,'send','cyan'],
['Source Errors',$failedSources,'triangle-alert','red'],
['Last Fetch',$lastFetch ? \Carbon\Carbon::parse($lastFetch)->diffForHumans() : 'Never','clock-3','violet'],
] as [$label,$value,$icon,$tone])
<article class="dr-kpi dr-kpi--{{ $tone }}"><span><i data-lucide="{{ $icon }}"></i></span><div><small>{{ $label }}</small><strong>{{ $value }}</strong></div></article>
@endforeach
</section>

<section class="card dr-pipeline">
<div class="dr-pipeline__head">
<div><span class="dr-eyebrow">Automatic News Pipeline</span><h2>Collection & AI processing</h2><p>Govern how often the application pulls active sources and runs local news processing.</p></div>
<span class="dr-status {{ $automationEnabled?'is-good':'' }}"><i data-lucide="{{ $automationEnabled?'circle-check':'pause-circle' }}"></i>{{ $automationEnabled?'Automation On':'Automation Paused' }}</span>
</div>
<div class="dr-pipeline__steps">
<div><span><i data-lucide="radio"></i></span><strong>RSS Fetch</strong><small>{{ $activeSources }} active sources</small></div><i data-lucide="arrow-right"></i>
<div><span><i data-lucide="copy-check"></i></span><strong>Duplicate Detection</strong><small>Local comparison stage</small></div><i data-lucide="arrow-right"></i>
<div><span><i data-lucide="sparkles"></i></span><strong>AI Processing</strong><small>{{ $processing['available'] ? ($processing['pending'].' pending') : 'Telemetry unavailable' }}</small></div>
</div>
</section>

<div class="dr-grid dr-grid--automation">
<section class="card dr-panel">
<header class="dr-card-head"><div><span class="dr-eyebrow">Scheduler</span><h2>Automation configuration</h2><p>Changes affect future scheduled runs; manual execution remains available.</p></div><i data-lucide="calendar-clock"></i></header>
<form method="POST" action="{{ route('admin.system.automation-monitor.update') }}" class="dr-form">@csrf @method('PUT')
<div class="dr-form-grid">
<label class="dr-field"><span>Automation Status</span><select class="select" name="automation_enabled"><option value="1" @selected($automationEnabled)>Enabled — fetch automatically</option><option value="0" @selected(!$automationEnabled)>Paused — manual only</option></select></label>
<label class="dr-field"><span>Fetch Frequency</span><select class="select" name="frequency_minutes">@foreach($frequencyOptions as $minutes=>$label)<option value="{{ $minutes }}" @selected($frequencyMinutes===$minutes)>{{ $label }}</option>@endforeach</select></label>
</div>
<div class="dr-schedule-summary"><span><i data-lucide="clock-3"></i></span><div><strong>{{ $automationEnabled && $nextRunAt ? 'Next run '.$nextRunAt->format('M d, Y · h:i A') : 'Automatic runs are paused' }}</strong><small>Current interval: {{ $frequencyOptions[$frequencyMinutes] ?? $frequencyMinutes.' minutes' }}</small></div></div>
<button class="btn btn-secondary" type="submit"><i data-lucide="settings-2"></i>Save Automation Settings</button>
</form>
</section>

<aside class="card dr-run-card">
<span class="dr-eyebrow">Last Pipeline Run</span>
<div class="dr-run-card__icon"><i data-lucide="{{ $lastRunStatus==='success'?'badge-check':($lastRunStatus==='failed'?'circle-x':'activity') }}"></i></div>
<h3>{{ ucwords(str_replace('_',' ',$lastRunStatus ?: 'Never')) }}</h3>
<p>{{ $lastRunMessage ?: 'No pipeline run message recorded yet.' }}</p>
<dl><div><dt>Started</dt><dd>{{ $lastRunStartedAt ? \Carbon\Carbon::parse($lastRunStartedAt)->format('M j, H:i') : '—' }}</dd></div><div><dt>Finished</dt><dd>{{ $lastRunFinishedAt ? \Carbon\Carbon::parse($lastRunFinishedAt)->format('M j, H:i') : '—' }}</dd></div><div><dt>Duration</dt><dd>{{ $lastRunDuration ? $lastRunDuration.' sec' : '—' }}</dd></div></dl>
<form method="POST" action="{{ route('admin.system.automation-monitor.run-now') }}">@csrf<button class="btn btn-primary"><i data-lucide="play"></i>Run Now</button></form>
</aside>
</div>

@if($processing['available'])
<section class="dr-kpis dr-processing">
@foreach([['Pending',$processing['pending'],'clock'],['Processing',$processing['processing'],'loader-circle'],['Processed',$processing['processed'],'check-check'],['Failed',$processing['failed'],'circle-x']] as [$label,$value,$icon])
<article class="dr-mini-stat"><span><i data-lucide="{{ $icon }}"></i></span><div><strong>{{ number_format($value) }}</strong><small>{{ $label }}</small></div></article>
@endforeach
</section>
@endif

<section class="card dr-table-card">
<header class="dr-card-head"><div><span class="dr-eyebrow">Source Operations</span><h2>Collection sources</h2><p>Current source state and fetch health.</p></div><span class="dr-count">{{ $sources->count() }} sources</span></header>
@if($sources->count())
<div class="table-wrap"><table class="data-table dr-table"><thead><tr><th>Source</th><th>Status</th><th>Last Fetch</th><th>Failure Signal</th></tr></thead><tbody>
@foreach($sources as $source)
<tr><td><div class="dr-record"><span><i data-lucide="rss"></i></span><div><strong>{{ $source->name }}</strong><small>{{ \Illuminate\Support\Str::limit($source->url,60) }}</small></div></div></td><td><span class="dr-status {{ $source->status==='active'?'is-good':'' }}">{{ ucfirst($source->status) }}</span></td><td><span class="dr-muted">{{ $source->last_fetched_at?->diffForHumans() ?? $source->last_success_at?->diffForHumans() ?? 'Never' }}</span></td><td>@if($source->last_error)<span class="dr-status is-bad" title="{{ $source->last_error }}"><i data-lucide="triangle-alert"></i>Error recorded</span>@else<span class="dr-muted">Clear</span>@endif</td></tr>
@endforeach
</tbody></table></div>
@else<div class="dr-empty"><span><i data-lucide="rss"></i></span><h3>No collection sources</h3><p>Add News Sources before enabling automated collection.</p></div>@endif
</section>
</div>
@endsection
