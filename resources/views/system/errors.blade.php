@extends('layouts.admin')
@section('title','Error Monitoring')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/pages/system-operations.css') }}">
@endpush

@section('content')
<div class="so-page">
<x-page-header title="Error Monitoring" subtitle="Group recurring application exceptions, prioritize severity and track investigation status." :breadcrumb="['System','Error Monitoring']" />

@if(session('status'))<div class="alert alert-success so-flash"><i data-lucide="check-circle-2"></i><span>{{ session('status') }}</span></div>@endif

<section class="so-kpis so-kpis--errors">
@foreach([
['Error groups',$stats['total'],'bug',''],
['Critical',$stats['critical'],'siren','red'],
['Open',$stats['open'],'circle-alert','red'],
['Investigating',$stats['investigating'],'search','amber'],
['Resolved',$stats['resolved'],'badge-check','green'],
['Occurrences · 24h',$stats['occurrences_24h'],'activity','violet'],
] as [$label,$value,$icon,$tone])
<article class="so-kpi so-kpi--{{ $tone }}"><span><i data-lucide="{{ $icon }}"></i></span><div><small>{{ $label }}</small><strong>{{ number_format($value) }}</strong></div></article>
@endforeach
</section>

<div class="so-grid so-grid--trend">
<section class="card so-panel">
<header class="so-card-head"><div><span class="so-eyebrow">Occurrence Trend</span><h2>Last 7 days</h2><p>Total grouped-error occurrences by last-seen date.</p></div><i data-lucide="chart-no-axes-column-increasing"></i></header>
<div class="so-chart-wrap"><canvas id="errorTrend"></canvas></div>
</section>
<aside class="card so-resolution">
<span class="so-eyebrow">Resolution Coverage</span>
@php($resolvedPct=$stats['total'] ? (int)round(($stats['resolved']/$stats['total'])*100) : 100)
<div class="so-resolution__value"><strong>{{ $resolvedPct }}%</strong><span>resolved</span></div>
<div class="so-progress"><span style="width:{{ $resolvedPct }}%"></span></div>
<p>{{ number_format($stats['resolved']) }} of {{ number_format($stats['total']) }} grouped errors are currently resolved.</p>
</aside>
</div>

<form class="card so-filterbar" method="GET">
<div class="so-search"><i data-lucide="search"></i><input class="input" name="q" value="{{ request('q') }}" placeholder="Search exception, message or URL..."></div>
<select class="select" name="status"><option value="">All statuses</option>@foreach(['open','investigating','resolved'] as $s)<option value="{{ $s }}" @selected(request('status')===$s)>{{ ucfirst($s) }}</option>@endforeach</select>
<select class="select" name="severity"><option value="">All severities</option>@foreach(['critical','medium','low'] as $s)<option value="{{ $s }}" @selected(request('severity')===$s)>{{ ucfirst($s) }}</option>@endforeach</select>
<button class="btn btn-secondary"><i data-lucide="filter"></i>Apply</button>
@if(request()->query())<a href="{{ route('admin.system.errors.index') }}" class="btn btn-ghost"><i data-lucide="rotate-ccw"></i>Reset</a>@endif
</form>

<section class="card so-table-card">
<header class="so-card-head"><div><span class="so-eyebrow">Exception Groups</span><h2>Error queue</h2><p>Sorted by the most recently observed occurrence.</p></div><span class="so-record-count">{{ number_format($errors->total()) }} groups</span></header>
@if($errors->count())
<div class="table-wrap"><table class="data-table so-table"><thead><tr><th>Error</th><th>Location</th><th>Severity</th><th>Occurrences</th><th>Last Seen</th><th>Status</th><th></th></tr></thead><tbody>
@foreach($errors as $error)
<tr>
<td><div class="so-error-name"><span><i data-lucide="bug"></i></span><div><a href="{{ route('admin.system.errors.show',$error->id) }}">{{ class_basename($error->exception_class) }}</a><small>{{ \Illuminate\Support\Str::limit($error->message ?: 'No message',90) }}</small></div></div></td>
<td><code>{{ basename($error->file ?? '') }}:{{ $error->line }}</code></td>
<td><span class="so-severity so-severity--{{ $error->severity }}">{{ ucfirst($error->severity) }}</span></td>
<td><strong>{{ number_format($error->occurrence_count) }}×</strong></td>
<td><span class="so-muted">{{ $error->last_seen_at->diffForHumans() }}</span></td>
<td><span class="so-status {{ $error->status==='resolved'?'is-good':($error->status==='investigating'?'is-warning':'is-bad') }}">{{ ucfirst($error->status) }}</span></td>
<td><a href="{{ route('admin.system.errors.show',$error->id) }}" class="btn btn-secondary btn-sm"><i data-lucide="scan-search"></i>Investigate</a></td>
</tr>
@endforeach
</tbody></table></div>
<div class="so-pagination"><span>Showing {{ $errors->firstItem() ?? 0 }}–{{ $errors->lastItem() ?? 0 }} of {{ $errors->total() }}</span><div>{{ $errors->onEachSide(1)->links() }}</div></div>
@else<div class="so-empty"><span><i data-lucide="shield-check"></i></span><h3>No matching error groups</h3><p>The current error queue is clear for these filters.</p></div>@endif
</section>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded',()=>{
const canvas=document.getElementById('errorTrend'); const rows=@json($trend);
if(canvas&&typeof Chart!=='undefined'){const ctx=canvas.getContext('2d');const gradient=ctx.createLinearGradient(0,0,0,250);gradient.addColorStop(0,'rgba(251,113,133,.30)');gradient.addColorStop(1,'rgba(251,113,133,0)');new Chart(canvas,{type:'line',data:{labels:rows.map(x=>x.label),datasets:[{data:rows.map(x=>x.count),borderColor:'#fb7185',backgroundColor:gradient,fill:true,tension:.38,borderWidth:2,pointRadius:0,pointHoverRadius:4}]},options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{display:false}},scales:{x:{grid:{display:false},border:{display:false},ticks:{color:'#69758b'}},y:{beginAtZero:true,border:{display:false},grid:{color:'rgba(255,255,255,.045)'},ticks:{color:'#69758b',precision:0}}}}});}
});
</script>
@endpush
