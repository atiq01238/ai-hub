@extends('layouts.admin')
@section('title', 'News Source Management')

@section('content')

<x-page-header title="News Source Management" subtitle="42 sources · 2 currently failing" :breadcrumb="['AI Intelligence', 'News Sources']">
    <x-slot:actions><button class="btn btn-primary btn-sm"><i data-lucide="plus"></i> Add Source</button></x-slot:actions>
</x-page-header>

<div class="tabs">
    <div class="tab is-active">News Sources</div>
    <div class="tab">API Sources</div>
    <div class="tab">Source Status</div>
    <div class="tab">Source Reliability</div>
</div>

<div class="card">
    <div class="table-wrap">
    <table class="data-table">
        <thead><tr><th>Source</th><th>Type</th><th>Status</th><th>Last Fetched</th><th>Articles Collected</th><th>Error Count</th><th>Reliability</th><th></th></tr></thead>
        <tbody>
        @php
        $sources = [
            ['The Information','API','Connected','3 min ago',4820,0,97],
            ['TechCrunch AI','RSS','Error','1 hr ago',3104,12,74],
            ['Anthropic Blog','RSS','Connected','8 min ago',218,0,99],
            ['The Verge','API','Connected','5 min ago',2966,2,91],
            ['VentureBeat','RSS','Warning','40 min ago',1877,5,82],
            ['DeepMind Blog','RSS','Connected','12 min ago',312,0,98],
        ];
        @endphp
        @foreach($sources as $s)
        <tr>
            <td><div class="row-media"><div class="thumb">{{ substr($s[0],0,2) }}</div><b>{{ $s[0] }}</b></div></td>
            <td><span class="badge badge-neutral">{{ $s[1] }}</span></td>
            <td><x-status-badge :status="$s[2]" :type="$s[2]==='Connected' ? 'pos' : ($s[2]==='Warning' ? 'warn' : 'neg')" /></td>
            <td class="cell-sub">{{ $s[3] }}</td>
            <td class="mono">{{ number_format($s[4]) }}</td>
            <td class="mono" style="color:{{ $s[5] > 5 ? 'var(--neg)' : 'var(--text-hi)' }};">{{ $s[5] }}</td>
            <td><x-score-meter :value="$s[6]" :segments="5" /></td>
            <td>
                <div class="flex gap-8">
                    <button class="icon-btn" style="width:28px;height:28px;"><i data-lucide="settings" style="width:14px;height:14px;"></i></button>
                    <button class="icon-btn" style="width:28px;height:28px;"><i data-lucide="zap" style="width:14px;height:14px;"></i></button>
                    <button class="icon-btn" style="width:28px;height:28px;"><i data-lucide="history" style="width:14px;height:14px;"></i></button>
                </div>
            </td>
        </tr>
        @endforeach
        </tbody>
    </table>
    </div>
</div>

<div class="card card-pad" style="margin-top:20px;">
    <div class="section-title">Source Reliability — Legend</div>
    <div class="flex gap-12" style="flex-wrap:wrap;">
        <span class="badge badge-pos">Excellent 90–100%</span>
        <span class="badge badge-info">Good 75–89%</span>
        <span class="badge badge-warn">Average 60–74%</span>
        <span class="badge badge-neg">Poor &lt;60%</span>
    </div>
</div>
@endsection
