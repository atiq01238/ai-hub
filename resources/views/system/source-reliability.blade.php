@extends('layouts.admin')
@section('title', 'Source Reliability')

@section('content')
<x-page-header title="Source Reliability System" subtitle="Reliability calculated from live collection and article telemetry" :breadcrumb="['System', 'Source Reliability']" />

<div class="filter-bar">
    <span class="badge badge-pos">Excellent 90–100%</span>
    <span class="badge badge-info">Good 75–89%</span>
    <span class="badge badge-warn">Average 60–74%</span>
    <span class="badge badge-neg">Poor &lt;60%</span>
</div>

@if($sources->isEmpty())
    <div class="card card-pad text-sub" style="text-align:center;">No news sources found. Add sources in News Sources first.</div>
@else
<div class="grid-3">
    @foreach($sources as $s)
        @php
            $tone = $s->score >= 90 ? 'pos' : ($s->score >= 75 ? 'info' : ($s->score >= 60 ? 'warn' : 'neg'));
            $label = $tone === 'pos' ? 'Excellent' : ($tone === 'info' ? 'Good' : ($tone === 'warn' ? 'Average' : 'Poor'));
        @endphp
        <div class="card card-pad">
            <div class="flex items-center justify-between" style="margin-bottom:12px;">
                <div class="row-media"><div class="thumb">{{ strtoupper(substr($s->name,0,2)) }}</div><b>{{ $s->name }}</b></div>
                <span class="badge badge-{{ $tone }}">{{ $label }}</span>
            </div>
            <x-score-meter :value="$s->score" :segments="10" />
            <div class="divider"></div>
            <div class="grid-2" style="gap:10px;">
                <div><div class="cell-sub">Feed Health</div><div class="mono" style="font-weight:700;">{{ $s->health }}%</div></div>
                <div><div class="cell-sub">Verification Rate</div><div class="mono" style="font-weight:700;">{{ $s->verification_rate }}%</div></div>
                <div><div class="cell-sub">Duplicate Rate</div><div class="mono" style="font-weight:700;">{{ $s->duplicate_rate }}%</div></div>
                <div><div class="cell-sub">Failure Signals</div><div class="mono" style="font-weight:700;">{{ $s->failed_reports }}</div></div>
            </div>
            <div class="divider"></div>
            <div class="cell-sub">{{ number_format($s->total_articles) }} collected · Last success {{ $s->last_success_at?->diffForHumans() ?? 'never' }}</div>
            @if($s->last_error)<div class="cell-sub" style="margin-top:6px;color:var(--neg);">{{ \Illuminate\Support\Str::limit($s->last_error, 100) }}</div>@endif
        </div>
    @endforeach
</div>
@endif
@endsection
