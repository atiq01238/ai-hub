@extends('layouts.admin')
@section('title', 'Comparisons')

@section('content')

<x-page-header title="Comparisons" subtitle="905 published comparisons" :breadcrumb="['Comparison & Benchmarks', 'Comparisons']">
    <x-slot:actions><a href="{{ url('/comparisons/builder') }}" class="btn btn-primary btn-sm"><i data-lucide="plus"></i> New Comparison</a></x-slot:actions>
</x-page-header>

<div class="filter-bar">
    <div class="input-search" style="background:var(--surface); border:1px solid var(--border); border-radius:var(--radius-sm); padding:8px 12px;">
        <i data-lucide="search"></i><input type="text" placeholder="Search comparisons...">
    </div>
    <select class="select"><option>All Types</option><option>Tool vs Tool</option><option>Model vs Model</option><option>Pricing</option></select>
    <select class="select"><option>All Status</option><option>Published</option><option>Draft</option></select>
</div>

<div class="card">
    <div class="table-wrap">
    <table class="data-table">
        <thead><tr><th>Comparison</th><th>Type</th><th>Views</th><th>Last Updated</th><th>Status</th></tr></thead>
        <tbody>
            <tr><td><b>ChatGPT vs Claude</b></td><td class="text-sub">Tool vs Tool</td><td class="mono">128,402</td><td class="cell-sub">Aug 4</td><td><x-status-badge status="Published" type="pos" /></td></tr>
            <tr><td><b>Midjourney vs Ideogram v3</b></td><td class="text-sub">Image AI</td><td class="mono">54,120</td><td class="cell-sub">Aug 2</td><td><x-status-badge status="Published" type="pos" /></td></tr>
            <tr><td><b>GPT-5.2 Turbo vs Claude Opus 4.8</b></td><td class="text-sub">Model vs Model</td><td class="mono">31,880</td><td class="cell-sub">Aug 5</td><td><x-status-badge status="Draft" type="neutral" /></td></tr>
            <tr><td><b>Best Coding AI 2026: 5-way</b></td><td class="text-sub">Coding AI</td><td class="mono">19,204</td><td class="cell-sub">Jul 30</td><td><x-status-badge status="Published" type="pos" /></td></tr>
        </tbody>
    </table>
    </div>
</div>
@endsection
