@extends('layouts.admin')
@section('title', 'Automation Monitor')

@section('content')

<x-page-header title="Automated News Collection Monitor" subtitle="Live pipeline status across all connected sources" :breadcrumb="['AI Intelligence', 'Automation Monitor']">
    <x-slot:actions><button class="btn btn-primary btn-sm"><i data-lucide="refresh-cw"></i> Sync Now</button></x-slot:actions>
</x-page-header>

<div class="card card-pad" style="margin-bottom:20px;">
    <div class="section-title">Collection Workflow</div>
    <div style="display:flex; align-items:center; overflow-x:auto; gap:0; padding:6px 0;">
        @php
            $flow = [
                ['News Sources','satellite-dish','pos'],
                ['Collection','download','pos'],
                ['Duplicate Detection','copy','pos'],
                ['AI Classification','brain-circuit','pos'],
                ['Sentiment','smile','pos'],
                ['Importance','flame','pos'],
                ['Verification','badge-check','warn'],
                ['Admin Review','user-check','neutral'],
            ];
        @endphp
        @foreach($flow as $f)
        <div style="display:flex; align-items:center; flex-shrink:0;">
            <div style="display:flex; flex-direction:column; align-items:center; gap:8px; width:118px;">
                <div class="kpi-icon" style="width:42px;height:42px; background:var(--{{ $f[2] }}-bg); color:var(--{{ $f[2] }});"><i data-lucide="{{ $f[1] }}"></i></div>
                <span style="font-size:11.5px; font-weight:600; text-align:center;">{{ $f[0] }}</span>
            </div>
            @if(!$loop->last)<div style="width:34px; height:2px; background:var(--border); flex-shrink:0; margin-bottom:24px;"></div>@endif
        </div>
        @endforeach
    </div>
</div>

<div class="kpi-grid" style="grid-template-columns:repeat(5,1fr);">
    <x-kpi-card icon="clock" label="Last Collection" value="3 min ago" />
    <x-kpi-card icon="inbox" label="Articles Collected" value="1,204" delta="+187 today" trend="up" />
    <x-kpi-card icon="copy" label="Duplicates Detected" value="214" />
    <x-kpi-card icon="badge-check" label="Verification Pending" value="6" />
    <x-kpi-card icon="server-crash" label="Failed Sources" value="2" delta="TechCrunch, VentureBeat" trend="down" />
</div>

<div class="card">
    <div class="card-head"><h3>Automation Status — Live</h3></div>
    <div class="table-wrap">
    <table class="data-table">
        <thead><tr><th>Source</th><th>Processing Status</th><th>Articles (24h)</th><th>Last Run</th><th>Next Run</th></tr></thead>
        <tbody>
            <tr><td><b>The Information</b></td><td><span class="badge badge-pos"><span class="status-dot pulse" style="background:currentColor;"></span> Running</span></td><td class="mono">142</td><td class="cell-sub">3 min ago</td><td class="cell-sub">in 2 min</td></tr>
            <tr><td><b>TechCrunch AI</b></td><td><span class="badge badge-neg">Failed</span></td><td class="mono">0</td><td class="cell-sub">1 hr ago</td><td class="cell-sub">Retrying...</td></tr>
            <tr><td><b>Anthropic Blog</b></td><td><span class="badge badge-pos"><span class="status-dot pulse" style="background:currentColor;"></span> Running</span></td><td class="mono">8</td><td class="cell-sub">8 min ago</td><td class="cell-sub">in 7 min</td></tr>
            <tr><td><b>VentureBeat</b></td><td><span class="badge badge-warn">Degraded</span></td><td class="mono">31</td><td class="cell-sub">40 min ago</td><td class="cell-sub">in 5 min</td></tr>
        </tbody>
    </table>
    </div>
</div>
@endsection
