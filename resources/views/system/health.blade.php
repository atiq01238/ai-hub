@extends('layouts.admin')
@section('title', 'System Health')

@section('content')

<x-page-header title="System Health" subtitle="Real-time infrastructure status" :breadcrumb="['System', 'System Health']" />

<div class="card card-pad" style="margin-bottom:20px; text-align:center; background:linear-gradient(135deg, rgba(52,211,153,.08), rgba(34,211,238,.05));">
    <div class="cell-sub" style="margin-bottom:6px;">Overall System Health</div>
    <div class="font-display" style="font-size:44px; font-weight:700; color:var(--pos);">99.98%</div>
    <div class="flex items-center justify-content-center gap-8" style="justify-content:center; margin-top:6px;">
        <span class="status-dot pulse" style="background:var(--pos);"></span>
        <span style="font-size:13px; font-weight:600; color:var(--pos);">All Systems Operational</span>
    </div>
</div>

<div class="grid-3">
    @php
    $services = [
        ['Database','database','Operational','pos','99.99%'],
        ['API Gateway','server','Operational','pos','99.97%'],
        ['News Collection','satellite-dish','Operational','pos','99.90%'],
        ['Search','search','Warning','warn','98.40%'],
        ['Storage','hard-drive','Operational','pos','100%'],
        ['Media Processing','image','Operational','pos','99.95%'],
        ['Email','mail','Operational','pos','99.88%'],
        ['Queue','list-ordered','Operational','pos','99.92%'],
        ['Background Jobs','cog','Critical','neg','91.20%'],
    ];
    @endphp
    @foreach($services as $s)
    <div class="card card-pad">
        <div class="flex items-center justify-between" style="margin-bottom:10px;">
            <div class="kpi-icon"><i data-lucide="{{ $s[1] }}"></i></div>
            <span class="badge badge-{{ $s[3] }}">{{ $s[2] }}</span>
        </div>
        <b style="font-size:14px;">{{ $s[0] }}</b>
        <div class="cell-sub" style="margin-top:4px;">Uptime {{ $s[4] }}</div>
    </div>
    @endforeach
</div>

<div class="card card-pad" style="margin-top:20px;">
    <div class="section-title">System Uptime — Last 30 Days</div>
    <canvas id="uptimeChart" height="70"></canvas>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
<script>
new Chart(document.getElementById('uptimeChart'), {
    type:'line',
    data:{ labels:Array.from({length:30},(_,i)=>i+1),
        datasets:[{ label:'Uptime %', data:Array.from({length:30},()=> (99.9 + Math.random()*0.1).toFixed(2)), borderColor:'#34d399', backgroundColor:'rgba(52,211,153,.08)', fill:true, borderWidth:2, pointRadius:0, tension:.3 }] },
    options:{ plugins:{legend:{display:false}}, scales:{ y:{min:99,max:100, grid:{color:'rgba(255,255,255,.04)'}, ticks:{color:'#5c6580'}}, x:{grid:{display:false}, ticks:{color:'#5c6580', maxTicksLimit:10}} } }
});
</script>
@endpush
