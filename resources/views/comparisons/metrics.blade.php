@extends('layouts.admin')
@section('title', 'Comparison Metrics')
@section('content')
<style>
.metrics-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:14px}.metric-card,.metric-panel{background:rgba(255,255,255,.025);border:1px solid rgba(255,255,255,.07);border-radius:14px;padding:18px}.metric-label{font-size:11px;color:#7f8ba0;text-transform:uppercase;letter-spacing:.08em}.metric-value{font-size:28px;font-weight:800;color:#f4f6fb;margin-top:6px}.metric-panels{display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-top:16px}.metric-panel h3{margin:0 0 14px}.metric-row{display:flex;align-items:center;justify-content:space-between;gap:12px;padding:11px 0;border-bottom:1px solid rgba(255,255,255,.055)}.metric-row:last-child{border:0}.metric-row a{color:#e9edf7;text-decoration:none}.muted{color:#7f8ba0;font-size:11px}@media(max-width:900px){.metrics-grid{grid-template-columns:1fr 1fr}.metric-panels{grid-template-columns:1fr}}@media(max-width:520px){.metrics-grid{grid-template-columns:1fr}}
</style>
<x-page-header title="Comparison Metrics" subtitle="Performance and engagement across AI comparisons" :breadcrumb="['Comparison & Benchmarks','Comparison Metrics']">
    <x-slot:actions><a href="{{ route('admin.comparisons.builder') }}" class="btn btn-primary btn-sm"><i data-lucide="plus"></i> New Comparison</a></x-slot:actions>
</x-page-header>
<div class="metrics-grid">
    <div class="metric-card"><div class="metric-label">Total Comparisons</div><div class="metric-value">{{ number_format($total) }}</div></div>
    <div class="metric-card"><div class="metric-label">Published</div><div class="metric-value">{{ number_format($published) }}</div></div>
    <div class="metric-card"><div class="metric-label">Total Views</div><div class="metric-value">{{ number_format($totalViews) }}</div></div>
    <div class="metric-card"><div class="metric-label">Avg. Views</div><div class="metric-value">{{ number_format($avgViews,1) }}</div></div>
    <div class="metric-card"><div class="metric-label">Tool Comparisons</div><div class="metric-value">{{ number_format($toolComparisons) }}</div></div>
    <div class="metric-card"><div class="metric-label">Model Comparisons</div><div class="metric-value">{{ number_format($modelComparisons) }}</div></div>
    <div class="metric-card"><div class="metric-label">Drafts</div><div class="metric-value">{{ number_format($drafts) }}</div></div>
    <div class="metric-card"><div class="metric-label">Publish Rate</div><div class="metric-value">{{ $total ? round(($published/$total)*100) : 0 }}%</div></div>
</div>
<div class="metric-panels">
    <section class="metric-panel"><h3>Most Viewed</h3>@forelse($topComparisons as $item)<div class="metric-row"><div><a href="{{ route('admin.comparisons.show',$item->id) }}">{{ $item->title }}</a><div class="muted">{{ ucfirst($item->comparable_type) }} · {{ ucfirst($item->status) }}</div></div><b>{{ number_format($item->views) }}</b></div>@empty<div class="muted">No comparison data yet.</div>@endforelse</section>
    <section class="metric-panel"><h3>Recently Updated</h3>@forelse($recentComparisons as $item)<div class="metric-row"><div><a href="{{ route('admin.comparisons.show',$item->id) }}">{{ $item->title }}</a><div class="muted">{{ $item->updated_at->diffForHumans() }}</div></div><span class="muted">{{ ucfirst($item->status) }}</span></div>@empty<div class="muted">No comparisons yet.</div>@endforelse</section>
</div>
@endsection
