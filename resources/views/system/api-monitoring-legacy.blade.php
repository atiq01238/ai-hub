@extends('layouts.admin')
@section('title', 'API Monitoring')

@section('content')

<x-page-header title="API Monitoring" subtitle="5 connected APIs · usage refreshes every 5 min" :breadcrumb="['System', 'API Monitoring']" />

<div class="grid-3" style="margin-bottom:20px;">
    @php
    $apis = [
        ['name'=>'News API','status'=>'Connected','tone'=>'pos','req'=>'42,108','err'=>'0.2%'],
        ['name'=>'AI API','status'=>'Connected','tone'=>'pos','req'=>'118,402','err'=>'0.1%'],
        ['name'=>'Search API','status'=>'Warning','tone'=>'warn','req'=>'8,204','err'=>'3.4%'],
        ['name'=>'Analytics API','status'=>'Connected','tone'=>'pos','req'=>'54,900','err'=>'0.0%'],
        ['name'=>'Social API','status'=>'Error','tone'=>'neg','req'=>'1,022','err'=>'18.9%'],
    ];
    @endphp
    @foreach($apis as $a)
    <div class="card card-pad">
        <div class="flex items-center justify-between" style="margin-bottom:12px;">
            <div class="kpi-icon"><i data-lucide="plug-zap"></i></div>
            <x-status-badge :status="$a['status']" :type="$a['tone']" />
        </div>
        <b style="font-size:14px;">{{ $a['name'] }}</b>
        <div class="grid-2" style="margin-top:12px; gap:8px;">
            <div><div class="cell-sub">Requests Today</div><div class="mono" style="font-weight:700;">{{ $a['req'] }}</div></div>
            <div><div class="cell-sub">Error Rate</div><div class="mono" style="font-weight:700; color:{{ $a['tone']==='pos' ? 'var(--pos)' : ($a['tone']==='warn' ? 'var(--warn)' : 'var(--neg)') }};">{{ $a['err'] }}</div></div>
        </div>
        <div class="flex gap-8" style="margin-top:14px;">
            <button class="btn btn-secondary btn-sm" style="flex:1; justify-content:center;">Configure</button>
            <button class="btn btn-ghost btn-sm" style="flex:1; justify-content:center;">Test Connection</button>
        </div>
    </div>
    @endforeach
</div>

<div class="grid-12">
    <div class="col-8 card card-pad">
        <div class="section-title">Requests — Last 7 Days</div>
        <canvas id="apiChart" height="90"></canvas>
    </div>
    <div class="col-4 card card-pad">
        <div class="section-title">Usage Summary</div>
        <div style="margin-bottom:16px;">
            <div class="flex items-center justify-between" style="margin-bottom:6px;"><span class="text-sub" style="font-size:12.5px;">Requests Today</span><span class="mono">224,636</span></div>
            <div class="flex items-center justify-between" style="margin-bottom:6px;"><span class="text-sub" style="font-size:12.5px;">Requests This Month</span><span class="mono">4.8M</span></div>
            <div class="flex items-center justify-between" style="margin-bottom:6px;"><span class="text-sub" style="font-size:12.5px;">Error Percentage</span><span class="mono" style="color:var(--warn);">2.4%</span></div>
        </div>
        <div class="cell-sub" style="margin-bottom:6px;">Remaining Quota — AI API</div>
        <div class="progress" style="margin-bottom:6px;"><span style="width:68%;"></span></div>
        <div class="cell-sub">680K / 1M requests used</div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
<script>
new Chart(document.getElementById('apiChart'), {
    type:'bar',
    data:{ labels:['Mon','Tue','Wed','Thu','Fri','Sat','Sun'],
        datasets:[
            {label:'Successful', data:[38,42,40,46,50,33,29], backgroundColor:'rgba(52,211,153,.6)', borderRadius:4, stack:'a'},
            {label:'Errors', data:[1.2,0.8,2.1,0.6,3.4,0.4,0.3], backgroundColor:'rgba(248,113,113,.7)', borderRadius:4, stack:'a'},
        ]},
    options:{ plugins:{legend:{labels:{color:'#9aa3b8',boxWidth:8,font:{size:11}}}}, scales:{x:{grid:{display:false},ticks:{color:'#5c6580'}},y:{grid:{color:'rgba(255,255,255,.04)'},ticks:{color:'#5c6580'}}} }
});
</script>
@endpush
