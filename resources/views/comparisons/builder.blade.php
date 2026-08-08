@extends('layouts.admin')
@section('title', 'Comparison Builder')

@section('content')

<x-page-header title="Comparison Builder" subtitle="Build a premium side-by-side comparison in 6 steps" :breadcrumb="['Comparison & Benchmarks', 'Comparison Builder']" />

<div class="card card-pad" style="margin-bottom:20px;">
    <div class="flex items-center justify-between" style="max-width:820px; margin:0 auto;">
        @foreach(['Type','Select Items','Metrics','Scores','Preview','Publish'] as $i => $step)
        <div class="flex items-center" style="flex:1;">
            <div style="display:flex; flex-direction:column; align-items:center; gap:6px;">
                <div style="width:32px;height:32px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:13px;
                    background:{{ $i===0 ? 'var(--grad-brand)' : 'var(--surface-2)' }}; color:{{ $i===0 ? '#fff' : 'var(--text-lo)' }}; border:1px solid {{ $i===0 ? 'transparent' : 'var(--border)' }};">
                    {{ $i+1 }}
                </div>
                <span style="font-size:11.5px; color:{{ $i===0 ? 'var(--text-hi)' : 'var(--text-lo)' }}; font-weight:600;">{{ $step }}</span>
            </div>
            @if(!$loop->last)<div style="flex:1; height:2px; background:var(--border); margin:0 8px 20px;"></div>@endif
        </div>
        @endforeach
    </div>
</div>

<div class="card card-pad">
    <div class="section-title">Step 1 — Select Comparison Type</div>
    <div class="grid-3">
        @foreach([
            ['Tool vs Tool','columns-3'], ['Model vs Model','brain-circuit'], ['Tool vs Multiple Tools','layout-grid'],
            ['Image AI','image'], ['Video AI','video'], ['Coding AI','code'],
            ['Pricing Comparison','tag'],
        ] as $i => $type)
        <div class="card card-pad" style="cursor:pointer; border-color:{{ $i===0 ? 'var(--brand-1)' : 'var(--border)' }}; background:{{ $i===0 ? 'rgba(91,127,255,.08)' : 'var(--surface)' }};">
            <div class="kpi-icon" style="margin-bottom:10px;"><i data-lucide="{{ $type[1] }}"></i></div>
            <div style="font-weight:650; font-size:14px;">{{ $type[0] }}</div>
        </div>
        @endforeach
    </div>
    <div class="divider"></div>
    <div class="flex justify-between">
        <button class="btn btn-ghost btn-sm" disabled><i data-lucide="arrow-left"></i> Back</button>
        <button class="btn btn-primary btn-sm">Continue <i data-lucide="arrow-right"></i></button>
    </div>
</div>

<div class="card card-pad" style="margin-top:20px;">
    <div class="section-title">Live Comparison Preview</div>
    <div class="table-wrap">
    <table class="data-table">
        <thead>
            <tr>
                <th>Metric</th>
                <th><div class="row-media"><div class="thumb">CG</div>ChatGPT</div></th>
                <th><div class="row-media"><div class="thumb">CL</div>Claude</div></th>
            </tr>
        </thead>
        <tbody>
            @foreach(['Price'=>['$20/mo','$20/mo'],'Quality'=>[92,95],'Speed'=>[88,84],'Accuracy'=>[90,93],'Ease of Use'=>[96,92],'Value'=>[85,88]] as $metric => $vals)
            <tr>
                <td class="text-sub">{{ $metric }}</td>
                <td>{{ is_numeric($vals[0]) ? '' : $vals[0] }}@if(is_numeric($vals[0]))<div class="progress" style="width:100px;"><span style="width:{{ $vals[0] }}%;"></span></div>@endif</td>
                <td>{{ is_numeric($vals[1]) ? '' : $vals[1] }}@if(is_numeric($vals[1]))<div class="progress" style="width:100px;"><span style="width:{{ $vals[1] }}%;"></span></div>@endif</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    </div>
</div>

@endsection
