@extends('layouts.admin')
@section('title', isset($comparison) ? 'Edit Comparison' : 'New Comparison')

@section('content')

@php
    $comparison ??= null;
    $selectedToolIds = old('tool_ids', $comparison && $comparison->comparable_type === 'tool' ? $comparison->item_ids : []);
    $selectedModelIds = old('model_ids', $comparison && $comparison->comparable_type === 'model' ? $comparison->item_ids : []);
@endphp

<form action="{{ $comparison ? route('admin.comparisons.update', $comparison->id) : route('admin.comparisons.store') }}" method="POST">
    @csrf
    @if ($comparison) @method('PUT') @endif

<x-page-header title="{{ $comparison ? 'Edit Comparison' : 'New Comparison' }}" subtitle="Pick a title, then select 2–4 tools OR 2–4 models to compare" :breadcrumb="['Comparison & Benchmarks', 'Comparisons', $comparison ? 'Edit' : 'New']">
    <x-slot:actions>
        <button type="submit" name="status" value="draft" class="btn btn-secondary btn-sm"><i data-lucide="save"></i> Save Draft</button>
        <button type="submit" name="status" value="published" class="btn btn-primary btn-sm"><i data-lucide="check"></i> Publish</button>
    </x-slot:actions>
</x-page-header>

@if ($errors->any())
    <div class="alert alert-danger" style="margin-bottom:16px;">
        <ul style="margin:0; padding-left:18px;">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="card card-pad form-section" style="margin-bottom:16px;">
    <div class="form-section__title">Title</div>
    <input class="input" name="title" value="{{ old('title', $comparison->title ?? '') }}" placeholder="e.g. ChatGPT vs Claude" required>
</div>

<div class="grid-2" style="gap:16px;">
    <div class="card card-pad">
        <div class="form-section__title">Option A — Compare Tools</div>
        <p class="text-sub" style="font-size:12px; margin-bottom:10px;">Select 2–4. Leave empty if comparing models instead.</p>
        <div style="max-height:320px; overflow-y:auto; display:flex; flex-direction:column; gap:8px;">
            @foreach ($tools as $tool)
                <label class="flex items-center gap-8" style="font-size:13px;">
                    <input type="checkbox" name="tool_ids[]" value="{{ $tool->id }}" {{ in_array($tool->id, $selectedToolIds) ? 'checked' : '' }}>
                    {{ $tool->name }} <span class="cell-sub">({{ $tool->company->name ?? '—' }})</span>
                </label>
            @endforeach
        </div>
    </div>

    <div class="card card-pad">
        <div class="form-section__title">Option B — Compare Models</div>
        <p class="text-sub" style="font-size:12px; margin-bottom:10px;">Select 2–4. Leave empty if comparing tools instead.</p>
        <div style="max-height:320px; overflow-y:auto; display:flex; flex-direction:column; gap:8px;">
            @foreach ($models as $model)
                <label class="flex items-center gap-8" style="font-size:13px;">
                    <input type="checkbox" name="model_ids[]" value="{{ $model->id }}" {{ in_array($model->id, $selectedModelIds) ? 'checked' : '' }}>
                    {{ $model->name }} <span class="cell-sub">({{ $model->company->name ?? '—' }})</span>
                </label>
            @endforeach
        </div>
    </div>
</div>

</form>
@endsection
