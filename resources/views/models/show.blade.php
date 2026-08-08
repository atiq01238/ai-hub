@extends('layouts.admin')
@section('title', $model->name . ' · Model Detail')

@section('content')

<x-page-header
    title="{{ $model->name }}"
    subtitle="{{ $model->company->name ?? '—' }} · {{ ucfirst($model->status) }}{{ $model->release_date ? ' · Released '.$model->release_date->format('M Y') : '' }}"
    :breadcrumb="['AI Management', 'AI Models', $model->name]">
    <x-slot:actions>
        <button class="btn btn-secondary btn-sm" data-bs-toggle="modal" data-bs-target="#previewModal"><i data-lucide="eye"></i> Preview</button>
        <a href="{{ route('admin.models.edit', $model->id) }}" class="btn btn-primary btn-sm"><i data-lucide="pencil"></i> Edit Model</a>
    </x-slot:actions>
</x-page-header>

@if (session('status'))
    <div class="alert alert-success" style="margin-bottom:16px;">{{ session('status') }}</div>
@endif

<div class="kpi-grid" style="grid-template-columns:repeat(4,1fr); margin-bottom:20px;">
    <x-kpi-card icon="brain-circuit" label="Benchmark Score" value="{{ number_format($model->benchmark_score, 1) }}" />
    <x-kpi-card icon="layers" label="Context Window" value="{{ $model->context_window ?? '—' }}" />
    <x-kpi-card icon="dollar-sign" label="Input / Output" value="${{ number_format($model->input_price_per_million ?? 0, 0) }} / ${{ number_format($model->output_price_per_million ?? 0, 0) }}" />
    <x-kpi-card icon="columns-3" label="Used in Comparisons" value="—" />
</div>
{{-- "Used in Comparisons" isn't tracked yet — needs a comparisons table, built later. --}}

<div class="grid-12">
    <div class="col-8 card card-pad">
        <div class="section-title">Capabilities</div>
        <div class="flex gap-8" style="flex-wrap:wrap; margin-bottom:20px;">
            @forelse ($model->capabilities ?? [] as $c)
                <span class="badge badge-violet">{{ $c }}</span>
            @empty
                <span class="text-sub">None listed</span>
            @endforelse
        </div>

        @if ($model->capability_notes)
        <div class="section-title">Notes</div>
        <p class="text-sub" style="font-size:13.5px; line-height:1.7; margin-bottom:20px;">{{ $model->capability_notes }}</p>
        @endif

        <div class="section-title">Benchmark Breakdown</div>
        @forelse ($model->benchmarks ?? [] as $b => $v)
        <div style="margin-bottom:12px;">
            <div class="flex items-center justify-between" style="margin-bottom:5px;"><span class="text-sub" style="font-size:12.5px;">{{ $b }}</span><span class="mono" style="font-size:12.5px;">{{ $v }}%</span></div>
            <div class="progress"><span style="width:{{ $v }}%;"></span></div>
        </div>
        @empty
            <p class="text-sub" style="font-size:13px;">No per-benchmark breakdown added yet.</p>
        @endforelse
    </div>
    <div class="col-4 card card-pad">
        <div class="section-title">Model Info</div>
        <div class="flex items-center justify-between" style="padding:9px 0; border-bottom:1px solid var(--border-soft);"><span class="cell-sub">Company</span><b style="font-size:13px;">{{ $model->company->name ?? '—' }}</b></div>
        <div class="flex items-center justify-between" style="padding:9px 0; border-bottom:1px solid var(--border-soft);"><span class="cell-sub">Tool</span><b style="font-size:13px;">{{ $model->tool->name ?? '—' }}</b></div>
        <div class="flex items-center justify-between" style="padding:9px 0; border-bottom:1px solid var(--border-soft);"><span class="cell-sub">Version</span><b style="font-size:13px;">{{ $model->version ?? '—' }}</b></div>
        <div class="flex items-center justify-between" style="padding:9px 0; border-bottom:1px solid var(--border-soft);"><span class="cell-sub">Release Date</span><b style="font-size:13px;">{{ $model->release_date?->format('M j, Y') ?? '—' }}</b></div>
        <div class="flex items-center justify-between" style="padding:9px 0;"><span class="cell-sub">Status</span><x-status-badge status="{{ ucfirst($model->status) }}" type="{{ $model->status === 'active' ? 'pos' : ($model->status === 'preview' ? 'warn' : 'neutral') }}" /></div>
    </div>
</div>
@endsection
