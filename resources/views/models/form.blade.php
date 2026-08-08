@extends('layouts.admin')
@section('title', isset($model) ? 'Edit AI Model' : 'Add AI Model')

@section('content')

@php
    $model ??= null;
    $old = fn ($key, $default = null) => old($key, $model->{$key} ?? $default);
@endphp

<form action="{{ $model ? route('admin.models.update', $model->id) : route('admin.models.store') }}" method="POST">
    @csrf
    @if ($model) @method('PUT') @endif

<x-page-header title="{{ $model ? 'Edit AI Model' : 'Add AI Model' }}" :breadcrumb="['AI Management', 'AI Models', $model ? 'Edit' : 'Add']">
    <x-slot:actions>
        <button type="submit" name="status" value="preview" class="btn btn-secondary btn-sm"><i data-lucide="save"></i> Save Draft</button>
        <button type="submit" name="status" value="active" class="btn btn-primary btn-sm"><i data-lucide="check"></i> Publish</button>
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

<div class="grid-12">
<div class="col-8">
    <div class="card card-pad form-section" style="margin-bottom:16px;">
        <div class="form-section__title">Model Information</div>
        <div class="form-grid">
            <div class="form-field"><label>Model Name</label><input class="input" name="name" value="{{ $old('name') }}" placeholder="e.g. GPT-5.2 Turbo" required></div>
            <div class="form-field">
                <label>Company</label>
                <select class="select" name="company_id">
                    <option value="">Select company...</option>
                    @foreach ($companies as $company)
                        <option value="{{ $company->id }}" @selected($old('company_id') == $company->id)>{{ $company->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-field">
                <label>Tool</label>
                <select class="select" name="tool_id">
                    <option value="">Select tool...</option>
                    @foreach ($tools as $tool)
                        <option value="{{ $tool->id }}" @selected($old('tool_id') == $tool->id)>{{ $tool->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-field"><label>Version</label><input class="input" name="version" value="{{ $old('version') }}" placeholder="5.2"></div>
            <div class="form-field"><label>Release Date</label><input class="input" type="date" name="release_date" value="{{ $old('release_date') ? \Illuminate\Support\Carbon::parse($old('release_date'))->format('Y-m-d') : '' }}"></div>
            <div class="form-field"><label>Context Window</label><input class="input" name="context_window" value="{{ $old('context_window') }}" placeholder="2M"></div>
            <div class="form-field"><label>Input Price ($/1M tokens)</label><input class="input" type="number" step="0.01" name="input_price_per_million" value="{{ $old('input_price_per_million') }}" placeholder="3.00"></div>
            <div class="form-field"><label>Output Price ($/1M tokens)</label><input class="input" type="number" step="0.01" name="output_price_per_million" value="{{ $old('output_price_per_million') }}" placeholder="12.00"></div>
            <div class="form-field"><label>Benchmark Score</label><input class="input" type="number" step="0.1" min="0" max="100" name="benchmark_score" value="{{ $old('benchmark_score') }}" placeholder="94.2"></div>
        </div>
    </div>

    <div class="card card-pad form-section">
        <div class="form-section__title">Capabilities</div>
        @php $selectedCaps = $old('capabilities', $model->capabilities ?? ['API Support', 'Reasoning', 'Vision']); @endphp
        <div class="flex gap-8" style="flex-wrap:wrap; margin-bottom:16px;">
            @foreach(['API Support','Reasoning','Vision','Audio','Image','Video'] as $cap)
                <label class="toggle-pill {{ in_array($cap, $selectedCaps) ? 'is-on' : '' }}">
                    <input type="checkbox" name="capabilities[]" value="{{ $cap }}" {{ in_array($cap, $selectedCaps) ? 'checked' : '' }} style="accent-color:var(--brand-1);">{{ $cap }}
                </label>
            @endforeach
        </div>
        <div class="form-field col-span-2"><label>Capability Notes</label><textarea class="input" name="capability_notes" rows="3" placeholder="Describe unique model capabilities...">{{ $old('capability_notes') }}</textarea></div>
    </div>
</div>

<div class="col-4 card card-pad">
    <div class="form-section__title" style="margin-bottom:12px;">Benchmark Score Preview</div>
    <div style="text-align:center; padding:10px 0;">
        <div class="font-display" style="font-size:38px; font-weight:700;">{{ $old('benchmark_score', '0.0') }}</div>
        <div class="text-sub" style="font-size:12.5px; margin-bottom:14px;">Composite Benchmark Score</div>
        <x-score-meter :value="(int) $old('benchmark_score', 0)" :segments="10" />
    </div>
    <div class="divider"></div>
    <p class="text-sub" style="font-size:12px;">Per-benchmark breakdown (MMLU, HumanEval, etc.) isn't editable here yet — coming in a future update.</p>
</div>
</div>

</form>
@endsection
