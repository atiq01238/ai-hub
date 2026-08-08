@extends('layouts.admin')
@section('title', 'Price History')

@section('content')

<x-page-header title="Price History &amp; Changes" subtitle="Track pricing movement across the industry" :breadcrumb="['Pricing', 'Price History']" />

<div class="card card-pad" style="margin-bottom:20px;">
    <div class="section-title">ChatGPT Plus — Price Timeline</div>
    <canvas id="priceChart" height="80"></canvas>
</div>

<div class="card">
    <div class="card-head"><h3>Recent Price Changes</h3></div>
    <div class="table-wrap">
    <table class="data-table">
        <thead><tr><th>Tool</th><th>Old Price</th><th>New Price</th><th>Change</th><th>Type</th><th>Date</th></tr></thead>
        <tbody>
            <tr><td><b>ChatGPT Plus</b></td><td class="mono text-muted">$20</td><td class="mono">$22</td><td><span class="badge badge-neg">+10%</span></td><td><span class="badge badge-warn">Price Increase</span></td><td class="cell-sub">Aug 5</td></tr>
            <tr><td><b>Midjourney Pro</b></td><td class="mono text-muted">$60</td><td class="mono">$48</td><td><span class="badge badge-pos">-20%</span></td><td><span class="badge badge-pos">Price Decrease</span></td><td class="cell-sub">Aug 3</td></tr>
            <tr><td><b>Claude Team</b></td><td class="mono text-muted">—</td><td class="mono">$30</td><td><span class="badge badge-info">New</span></td><td><span class="badge badge-info">New Plan</span></td><td class="cell-sub">Aug 1</td></tr>
            <tr><td><b>Runway Basic</b></td><td class="mono text-muted">$0</td><td class="mono">—</td><td><span class="badge badge-neutral">Removed</span></td><td><span class="badge badge-neutral">Removed Plan</span></td><td class="cell-sub">Jul 28</td></tr>
        </tbody>
    </table>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
<script>
new Chart(document.getElementById('priceChart'), {
    type:'line',
    data:{ labels:['Feb','Mar','Apr','May','Jun','Jul','Aug'], datasets:[{label:'Monthly Price ($)', data:[20,20,20,20,20,20,22], borderColor:'#5b7fff', backgroundColor:'rgba(91,127,255,.08)', fill:true, stepped:true, borderWidth:2, pointRadius:3}] },
    options:{ plugins:{legend:{labels:{color:'#9aa3b8'}}}, scales:{x:{grid:{color:'rgba(255,255,255,.04)'},ticks:{color:'#5c6580'}},y:{grid:{color:'rgba(255,255,255,.04)'},ticks:{color:'#5c6580'}}} }
});
</script>
@endpush
