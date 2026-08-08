@extends('layouts.admin')
@section('title', 'Source Reliability')

@section('content')

<x-page-header title="Source Reliability System" subtitle="Composite reliability scoring across all news sources" :breadcrumb="['System', 'Source Reliability']" />

<div class="filter-bar">
    <span class="badge badge-pos">Excellent 90–100%</span>
    <span class="badge badge-info">Good 75–89%</span>
    <span class="badge badge-warn">Average 60–74%</span>
    <span class="badge badge-neg">Poor &lt;60%</span>
</div>

<div class="grid-3">
    @php
    $sources = [
        ['name'=>'The Information','score'=>97,'accuracy'=>98,'verif'=>96,'dup'=>2,'failed'=>0],
        ['name'=>'Anthropic Blog','score'=>99,'accuracy'=>99,'verif'=>98,'dup'=>0,'failed'=>0],
        ['name'=>'DeepMind Blog','score'=>98,'accuracy'=>97,'verif'=>97,'dup'=>1,'failed'=>0],
        ['name'=>'The Verge','score'=>91,'accuracy'=>90,'verif'=>89,'dup'=>4,'failed'=>2],
        ['name'=>'VentureBeat','score'=>82,'accuracy'=>80,'verif'=>78,'dup'=>9,'failed'=>5],
        ['name'=>'TechCrunch AI','score'=>74,'accuracy'=>71,'verif'=>69,'dup'=>14,'failed'=>12],
    ];
    @endphp
    @foreach($sources as $s)
    @php $tone = $s['score']>=90?'pos':($s['score']>=75?'info':($s['score']>=60?'warn':'neg')); @endphp
    <div class="card card-pad">
        <div class="flex items-center justify-between" style="margin-bottom:12px;">
            <div class="row-media"><div class="thumb">{{ substr($s['name'],0,2) }}</div><b>{{ $s['name'] }}</b></div>
            <span class="badge badge-{{ $tone }}">{{ $tone==='pos'?'Excellent':($tone==='info'?'Good':($tone==='warn'?'Average':'Poor')) }}</span>
        </div>
        <x-score-meter :value="$s['score']" :segments="10" />
        <div class="divider"></div>
        <div class="grid-2" style="gap:10px;">
            <div><div class="cell-sub">Accuracy History</div><div class="mono" style="font-weight:700;">{{ $s['accuracy'] }}%</div></div>
            <div><div class="cell-sub">Verification Rate</div><div class="mono" style="font-weight:700;">{{ $s['verif'] }}%</div></div>
            <div><div class="cell-sub">Duplicate Rate</div><div class="mono" style="font-weight:700;">{{ $s['dup'] }}%</div></div>
            <div><div class="cell-sub">Failed Reports</div><div class="mono" style="font-weight:700; color:{{ $s['failed']>5 ? 'var(--neg)' : 'var(--text-hi)' }};">{{ $s['failed'] }}</div></div>
        </div>
    </div>
    @endforeach
</div>
@endsection
