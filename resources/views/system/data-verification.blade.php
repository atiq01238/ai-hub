@extends('layouts.admin')
@section('title', 'Data Verification Center')

@section('content')

<x-page-header title="Data Verification Center" subtitle="24 items pending across all data types" :breadcrumb="['System', 'Data Verification']" />

<div class="filter-bar">
    <span class="chip is-active">All Types</span>
    <span class="chip">News</span>
    <span class="chip">Pricing</span>
    <span class="chip">Benchmarks</span>
    <span class="chip">AI Tools</span>
    <span class="chip">AI Models</span>
    <span class="chip">Company Information</span>
</div>

<div class="card">
    <div class="table-wrap">
    <table class="data-table">
        <thead><tr><th>Data</th><th>Type</th><th>Source</th><th>Confidence</th><th>Last Verified</th><th>Status</th><th></th></tr></thead>
        <tbody>
        @php
        $items = [
            ['GPT-5.2 Turbo — 2M context window claim','News','The Information',94,'Never','Needs Verification'],
            ['ChatGPT Plus price → $22/mo','Pricing','OpenAI Billing Page',88,'2 hr ago','Verified'],
            ['Claude Opus 4.8 — MMLU Pro 95.8','Benchmarks','Anthropic Blog',97,'1 day ago','Verified'],
            ['Runway Gen-4 — supports 4K output','AI Tools','runwayml.com',71,'Never','Unverified'],
            ['Gemini 3 Pro release date','AI Models','DeepMind Blog',62,'Never','Needs Verification'],
            ['Midjourney Inc. — HQ location','Company Information','Crunchbase',54,'12 days ago','Rejected'],
        ];
        @endphp
        @foreach($items as $i)
        <tr>
            <td><b>{{ $i[0] }}</b></td>
            <td><span class="badge badge-neutral">{{ $i[1] }}</span></td>
            <td class="text-sub">{{ $i[2] }}</td>
            <td><x-score-meter :value="$i[3]" :segments="5" /></td>
            <td class="cell-sub">{{ $i[4] }}</td>
            <td><x-status-badge :status="$i[5]" :type="$i[5]==='Verified' ? 'pos' : ($i[5]==='Rejected' ? 'neg' : 'warn')" /></td>
            <td>
                <div class="flex gap-8">
                    <button class="btn btn-secondary btn-sm">Verify</button>
                    <button class="btn btn-ghost btn-sm">Reject</button>
                    <button class="btn btn-ghost btn-sm">Edit</button>
                </div>
            </td>
        </tr>
        @endforeach
        </tbody>
    </table>
    </div>
</div>
@endsection
