@extends('layouts.admin')
@section('title', 'Analytics')

@section('content')
@php
    $tab = request()->is('*tools*') ? 'tools' : (request()->is('*search*') ? 'search' : (request()->is('*comparisons*') ? 'comparisons' : (request()->is('*content*') ? 'content' : (request()->is('*trending*') ? 'trending' : 'website'))));
    $titles = ['website'=>'Website Analytics','tools'=>'Tool Analytics','search'=>'Search Analytics','comparisons'=>'Comparison Analytics','content'=>'Content Analytics','trending'=>'Trending Searches'];
@endphp

<x-page-header :title="$titles[$tab]" subtitle="Last 30 days · compared to prior period" :breadcrumb="['Analytics', $titles[$tab]]">
    <x-slot:actions>
        <select class="select"><option>30 Days</option><option>7 Days</option><option>3 Months</option><option>1 Year</option></select>
        <button class="btn btn-secondary btn-sm"><i data-lucide="download"></i> Export</button>
    </x-slot:actions>
</x-page-header>

<div class="tabs">
    <a href="{{ url('/analytics/website') }}" class="tab {{ $tab==='website'?'is-active':'' }}">Website</a>
    <a href="{{ url('/analytics/tools') }}" class="tab {{ $tab==='tools'?'is-active':'' }}">Tool</a>
    <a href="{{ url('/analytics/search') }}" class="tab {{ $tab==='search'?'is-active':'' }}">Search</a>
    <a href="{{ url('/analytics/comparisons') }}" class="tab {{ $tab==='comparisons'?'is-active':'' }}">Comparison</a>
    <a href="{{ url('/analytics/content') }}" class="tab {{ $tab==='content'?'is-active':'' }}">Content</a>
    <a href="{{ url('/analytics/trending') }}" class="tab {{ $tab==='trending'?'is-active':'' }}">Trending Searches</a>
</div>

@if($tab !== 'search' && $tab !== 'trending')
<div class="kpi-grid" style="grid-template-columns:repeat(4,1fr);">
    @if($tab==='website')
        <x-kpi-card icon="users" label="Visitors" value="482K" delta="+9.2%" trend="up" />
        <x-kpi-card icon="eye" label="Page Views" value="1.9M" delta="+11.4%" trend="up" />
        <x-kpi-card icon="mouse-pointer-click" label="CTR" value="4.8%" delta="+0.4%" trend="up" />
        <x-kpi-card icon="timer" label="Avg. Session" value="3m 42s" delta="-6%" trend="down" />
    @elseif($tab==='tools')
        <x-kpi-card icon="wrench" label="Tool Views" value="6.1M" delta="+7.8%" trend="up" />
        <x-kpi-card icon="star" label="Avg Rating" value="4.4" delta="+0.1" trend="up" />
        <x-kpi-card icon="columns-3" label="Compare Clicks" value="212K" delta="+15.2%" trend="up" />
        <x-kpi-card icon="external-link" label="Outbound Clicks" value="88K" delta="+4.1%" trend="up" />
    @elseif($tab==='comparisons')
        <x-kpi-card icon="columns-3" label="Comparison Views" value="904K" delta="+6.7%" trend="up" />
        <x-kpi-card icon="square-stack" label="Comparisons Built" value="905" delta="+3.2%" trend="up" />
        <x-kpi-card icon="share-2" label="Shares" value="12.4K" delta="+21%" trend="up" />
        <x-kpi-card icon="clock" label="Avg Time on Page" value="2m 18s" delta="+9%" trend="up" />
    @else
        <x-kpi-card icon="file-text" label="Article Views" value="3.4M" delta="+5.5%" trend="up" />
        <x-kpi-card icon="message-square-heart" label="Review Views" value="1.1M" delta="+2.9%" trend="up" />
        <x-kpi-card icon="share-2" label="Social Shares" value="41K" delta="+18%" trend="up" />
        <x-kpi-card icon="bar-chart-3" label="Avg. Read Time" value="4m 05s" delta="+2%" trend="up" />
    @endif
</div>

<div class="card card-pad" style="margin-bottom:20px;">
    <div class="section-title">{{ $titles[$tab] }} Trend</div>
    <canvas id="analyticsChart" height="90"></canvas>
</div>
@endif

@if($tab==='search' || $tab==='trending')
<div class="kpi-grid" style="grid-template-columns:repeat(4,1fr);">
    <x-kpi-card icon="search" label="Total Searches" value="1.2M" delta="+24.1%" trend="up" />
    <x-kpi-card icon="flame" label="Trending Queries" value="86" delta="+12" trend="up" />
    <x-kpi-card icon="circle-slash" label="Zero-Result Searches" value="4,102" delta="+3.8%" trend="down" />
    <x-kpi-card icon="target" label="Search→Tool Conversion" value="38.4%" delta="+2.1%" trend="up" />
</div>

<div class="card">
    <div class="card-head"><h3>Top &amp; Trending Searches</h3></div>
    <div class="table-wrap">
    <table class="data-table">
        <thead><tr><th>Search Query</th><th>Volume</th><th>Growth</th><th>Related Tool</th></tr></thead>
        <tbody>
            <tr><td><b>best AI video generator</b></td><td class="mono">12,450</td><td><span class="badge badge-pos">+38%</span></td><td class="text-sub">Runway Gen-4</td></tr>
            <tr><td><b>claude vs chatgpt</b></td><td class="mono">9,820</td><td><span class="badge badge-pos">+21%</span></td><td class="text-sub">Claude</td></tr>
            <tr><td><b>free ai image generator</b></td><td class="mono">8,110</td><td><span class="badge badge-pos">+14%</span></td><td class="text-sub">Ideogram v3</td></tr>
            <tr><td><b>ai coding assistant</b></td><td class="mono">6,730</td><td><span class="badge badge-pos">+9%</span></td><td class="text-sub">CodePilot X</td></tr>
            <tr><td><b>ai agents for business</b></td><td class="mono">5,290</td><td><span class="badge badge-pos">+52%</span></td><td class="text-sub">—</td></tr>
        </tbody>
    </table>
    </div>
</div>
@else
<div class="grid-12">
    <div class="col-8 card">
        <div class="card-head"><h3>Top {{ $tab==='website' ? 'Pages' : ($tab==='tools' ? 'Tools' : ($tab==='comparisons' ? 'Comparisons' : 'Articles')) }}</h3></div>
        <div class="table-wrap">
        <table class="data-table">
            <thead><tr><th>#</th><th>{{ $tab==='website' ? 'Page' : 'Item' }}</th><th>Views</th><th>Growth</th></tr></thead>
            <tbody>
                <tr><td class="mono text-muted">1</td><td><b>ChatGPT vs Claude</b></td><td class="mono">128,402</td><td><span class="badge badge-pos">+18%</span></td></tr>
                <tr><td class="mono text-muted">2</td><td><b>Best AI Video Generators 2026</b></td><td class="mono">94,220</td><td><span class="badge badge-pos">+31%</span></td></tr>
                <tr><td class="mono text-muted">3</td><td><b>Midjourney Review</b></td><td class="mono">61,880</td><td><span class="badge badge-neg">-4%</span></td></tr>
            </tbody>
        </table>
        </div>
    </div>
    <div class="col-4 card card-pad">
        <div class="section-title">Traffic Sources</div>
        <canvas id="sourcesChart" height="180"></canvas>
    </div>
</div>
@endif

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
<script>
const el = document.getElementById('analyticsChart');
if (el) {
  new Chart(el, { type:'bar', data:{ labels:['Jul 08','Jul 12','Jul 16','Jul 20','Jul 24','Jul 28','Aug 01','Aug 05'],
    datasets:[{ label:'Value', data:[38,45,42,51,60,55,68,74], backgroundColor:'rgba(91,127,255,.55)', borderRadius:6, maxBarThickness:26 }] },
    options:{ plugins:{legend:{display:false}}, scales:{x:{grid:{display:false},ticks:{color:'#5c6580',font:{size:11}}},y:{grid:{color:'rgba(255,255,255,.04)'},ticks:{color:'#5c6580'}}} } });
}
const el2 = document.getElementById('sourcesChart');
if (el2) {
  new Chart(el2, { type:'doughnut', data:{ labels:['Organic Search','Direct','Social','Referral'], datasets:[{ data:[52,24,15,9], backgroundColor:['#5b7fff','#8b5cf6','#22d3ee','#5c6580'], borderWidth:0 }] },
    options:{ plugins:{legend:{position:'bottom',labels:{color:'#9aa3b8',boxWidth:8,font:{size:11}}}} } });
}
</script>
@endpush
