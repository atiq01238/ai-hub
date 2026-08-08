@extends('layouts.admin')
@section('title', 'Benchmarks')

@section('content')

<x-page-header title="Benchmarks" subtitle="Model performance rankings across standardized tests" :breadcrumb="['Comparison & Benchmarks', 'Benchmarks']">
    <x-slot:actions>
        <button class="btn btn-secondary btn-sm"><i data-lucide="shapes"></i> Categories</button>
        <button class="btn btn-primary btn-sm"><i data-lucide="plus"></i> Create Benchmark</button>
    </x-slot:actions>
</x-page-header>

<div class="filter-bar">
    <span class="chip is-active">MMLU Pro</span>
    <span class="chip">HumanEval</span>
    <span class="chip">GPQA Diamond</span>
    <span class="chip">MATH</span>
    <span class="chip">SWE-bench</span>
</div>

<div class="grid-12">
    <div class="col-8 card">
        <div class="card-head"><h3>Rankings — MMLU Pro</h3></div>
        <div class="table-wrap">
        <table class="data-table">
            <thead><tr><th>Rank</th><th>Model</th><th>Score</th><th>Previous</th><th>Change</th></tr></thead>
            <tbody>
                @php
                $rank = [
                    ['GPT-5.2 Turbo','OpenAI',96.4,94.1],
                    ['Claude Opus 4.8','Anthropic',95.8,95.2],
                    ['Gemini 3 Pro','Google DeepMind',94.9,93.0],
                    ['GPT-4.1','OpenAI',88.2,88.0],
                ];
                @endphp
                @foreach($rank as $i => $r)
                <tr>
                    <td class="mono" style="font-weight:700;">#{{ $i+1 }}</td>
                    <td><div class="row-media"><div class="thumb">{{ substr($r[0],0,2) }}</div><div><b>{{ $r[0] }}</b><div class="cell-sub">{{ $r[1] }}</div></div></div></td>
                    <td class="mono" style="font-weight:700;">{{ $r[2] }}</td>
                    <td class="mono text-muted">{{ $r[3] }}</td>
                    <td>
                        @php $diff = round($r[2]-$r[3],1); @endphp
                        <span class="badge {{ $diff>=0?'badge-pos':'badge-neg' }}">{{ $diff>=0?'+':'' }}{{ $diff }}</span>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        </div>
    </div>
    <div class="col-4 card card-pad">
        <div class="section-title">Score Trend — Top 3</div>
        <canvas id="benchChart" height="220"></canvas>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
<script>
new Chart(document.getElementById('benchChart'), {
    type:'line',
    data:{ labels:['Apr','May','Jun','Jul','Aug'],
        datasets:[
            {label:'GPT-5.2 Turbo', data:[89,91,92,94,96], borderColor:'#5b7fff', pointRadius:0, tension:.4, borderWidth:2},
            {label:'Claude Opus 4.8', data:[90,92,93,95,96], borderColor:'#8b5cf6', pointRadius:0, tension:.4, borderWidth:2},
            {label:'Gemini 3 Pro', data:[85,88,90,93,95], borderColor:'#22d3ee', pointRadius:0, tension:.4, borderWidth:2},
        ]},
    options:{ plugins:{legend:{labels:{color:'#9aa3b8', boxWidth:8, font:{size:10}}}}, scales:{x:{grid:{color:'rgba(255,255,255,.04)'},ticks:{color:'#5c6580'}},y:{grid:{color:'rgba(255,255,255,.04)'},ticks:{color:'#5c6580'}}} }
});
</script>
@endpush
