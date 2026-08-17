@extends('layouts.admin')
@section('title','Source Reliability')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/pages/data-reliability.css') }}">
@endpush

@section('content')
@php
$excellent=$sources->where('score','>=',90)->count();
$poor=$sources->where('score','<',60)->count();
$avg=$sources->count() ? round($sources->avg('score')) : 0;
@endphp
<div class="dr-page">
<x-page-header title="Source Reliability" subtitle="Rank news-source trust using measurable collection, processing, duplicate and verification telemetry." :breadcrumb="['System','Source Reliability']">
<x-slot:actions><a href="{{ route('admin.system.news-sources') }}" class="btn btn-secondary"><i data-lucide="rss"></i>Manage Sources</a></x-slot:actions>
</x-page-header>

<section class="dr-kpis">
@foreach([
['Tracked Sources',$sources->count(),'rss',''],
['Average Reliability',$avg.'%','gauge','violet'],
['Excellent Sources',$excellent,'shield-check','green'],
['Poor Sources',$poor,'shield-alert','red'],
] as [$label,$value,$icon,$tone])
<article class="dr-kpi dr-kpi--{{ $tone }}"><span><i data-lucide="{{ $icon }}"></i></span><div><small>{{ $label }}</small><strong>{{ $value }}</strong></div></article>
@endforeach
</section>

<section class="dr-reliability-legend">
<div><span class="is-excellent"></span>Excellent 90–100%</div><div><span class="is-good"></span>Good 75–89%</div><div><span class="is-average"></span>Average 60–74%</div><div><span class="is-poor"></span>Poor &lt;60%</div>
</section>

@if($sources->isEmpty())
<div class="card dr-empty"><span><i data-lucide="rss"></i></span><h3>No news sources found</h3><p>Add sources in News Sources before reliability can be calculated.</p></div>
@else
<section class="dr-reliability-grid">
@foreach($sources as $s)
@php
$tone=$s->score>=90?'excellent':($s->score>=75?'good':($s->score>=60?'average':'poor'));
$label=$tone==='excellent'?'Excellent':($tone==='good'?'Good':($tone==='average'?'Average':'Poor'));
@endphp
<article class="card dr-reliability-card dr-reliability-card--{{ $tone }}">
<div class="dr-reliability-card__head"><div class="dr-record"><span><i data-lucide="rss"></i></span><div><strong>{{ $s->name }}</strong><small>{{ ucfirst($s->status) }} source</small></div></div><span class="dr-reliability-label">{{ $label }}</span></div>
<div class="dr-reliability-score"><div class="dr-score-ring" style="--score:{{ $s->score }}"><strong>{{ $s->score }}</strong><span>/100</span></div><div><span class="dr-eyebrow">Reliability Score</span><p>Weighted from measurable local collection and article telemetry.</p></div></div>
<div class="dr-reliability-metrics">
<div><span>Feed Health</span><strong>{{ $s->health }}%</strong></div><div><span>Verification</span><strong>{{ $s->verification_rate }}%</strong></div><div><span>Duplicate Rate</span><strong>{{ $s->duplicate_rate }}%</strong></div><div><span>Failure Signals</span><strong>{{ $s->failed_reports }}</strong></div>
</div>
<div class="dr-reliability-foot"><span>{{ number_format($s->total_articles) }} collected</span><span>Last success {{ $s->last_success_at?->diffForHumans() ?? 'never' }}</span></div>
@if($s->last_error)<div class="dr-source-error"><i data-lucide="triangle-alert"></i>{{ \Illuminate\Support\Str::limit($s->last_error,120) }}</div>@endif
</article>
@endforeach
</section>
@endif
</div>
@endsection
