@extends('layouts.admin')
@section('title', 'Add Benchmark Result')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/pages/comparison-benchmarks.css') }}">
@endpush

@section('content')
@php $selectedType = old('type', 'model'); @endphp

<div class="cb-page cb-benchmark-editor">
    <x-page-header
        title="Add Benchmark Result"
        subtitle="Record standardized performance evidence for an AI model or tool."
        :breadcrumb="['Comparison & Benchmarks', 'Benchmarks', 'Add Result']"
    >
        <x-slot:actions>
            <a href="{{ route('admin.benchmarks.index') }}" class="btn btn-secondary"><i data-lucide="arrow-left"></i>Cancel</a>
        </x-slot:actions>
    </x-page-header>

    @if($errors->any())
        <div class="alert alert-danger cb-error">
            <i data-lucide="circle-alert"></i>
            <div><strong>Please fix the benchmark record.</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
        </div>
    @endif

    <form action="{{ route('admin.benchmarks.store') }}" method="POST" id="benchmarkForm">
        @csrf

        <div class="cb-benchmark-editor__layout">
            <main class="cb-benchmark-editor__main">
                <section class="card cb-panel">
                    <div class="cb-section-head">
                        <div><span class="cb-eyebrow">Target</span><h2>Select item</h2><p>Choose whether this score belongs to a model or a tool.</p></div>
                        <span class="cb-panel__icon"><i data-lucide="crosshair"></i></span>
                    </div>

                    <div class="cb-type-switch cb-type-switch--compact">
                        <label class="cb-type-option {{ $selectedType === 'model' ? 'is-active' : '' }}" data-benchmark-type="model">
                            <input type="radio" name="type" value="model" @checked($selectedType === 'model')>
                            <span class="cb-type-option__icon"><i data-lucide="brain-circuit"></i></span>
                            <span><strong>AI Model</strong><small>Benchmark a model record.</small></span>
                        </label>
                        <label class="cb-type-option {{ $selectedType === 'tool' ? 'is-active' : '' }}" data-benchmark-type="tool">
                            <input type="radio" name="type" value="tool" @checked($selectedType === 'tool')>
                            <span class="cb-type-option__icon"><i data-lucide="wrench"></i></span>
                            <span><strong>AI Tool</strong><small>Benchmark a tool record.</small></span>
                        </label>
                    </div>

                    <div class="cb-form-grid">
                        <label class="cb-field cb-field--full" id="benchmarkModelField">
                            <span>Model <b>*</b></span>
                            <select class="select" name="item_id" data-item-select="model">
                                <option value="">Select model...</option>
                                @foreach($models as $model)
                                    <option value="{{ $model->id }}" @selected(old('item_id') == $model->id && $selectedType === 'model')>
                                        {{ $model->name }}{{ $model->company ? ' — '.$model->company->name : '' }}
                                    </option>
                                @endforeach
                            </select>
                        </label>

                        <label class="cb-field cb-field--full" id="benchmarkToolField">
                            <span>Tool <b>*</b></span>
                            <select class="select" name="item_id" data-item-select="tool">
                                <option value="">Select tool...</option>
                                @foreach($tools as $tool)
                                    <option value="{{ $tool->id }}" @selected(old('item_id') == $tool->id && $selectedType === 'tool')>
                                        {{ $tool->name }}{{ $tool->company ? ' — '.$tool->company->name : '' }}
                                    </option>
                                @endforeach
                            </select>
                        </label>
                    </div>
                </section>

                <section class="card cb-panel">
                    <div class="cb-section-head">
                        <div><span class="cb-eyebrow">Measurement</span><h2>Benchmark score</h2><p>Record the benchmark name and measured score.</p></div>
                        <span class="cb-panel__icon"><i data-lucide="gauge"></i></span>
                    </div>

                    <div class="cb-form-grid">
                        <label class="cb-field">
                            <span>Benchmark name <b>*</b></span>
                            <input class="input" list="benchmarkOptions" name="benchmark_name" value="{{ old('benchmark_name') }}" placeholder="e.g. MMLU Pro" required>
                            <datalist id="benchmarkOptions">@foreach($benchmarks as $benchmark)<option value="{{ $benchmark }}">@endforeach</datalist>
                        </label>

                        <label class="cb-field">
                            <span>Benchmark class <b>*</b></span>
                            <select class="select" name="benchmark_class" required>
                                @foreach($benchmarkClasses as $value => $label)
                                    <option value="{{ $value }}" @selected(old('benchmark_class', 'technical_performance') === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                            <small>Semantic class controls which scores may be combined.</small>
                        </label>

                        <label class="cb-field">
                            <span>Score <b>*</b></span>
                            <div class="cb-score-input"><input class="input" type="number" name="score" min="0" step="0.01" value="{{ old('score') }}" placeholder="Measured score" required></div>
                        </label>

                        <label class="cb-field">
                            <span>Tested date</span>
                            <input class="input" type="date" name="tested_at" value="{{ old('tested_at', now()->toDateString()) }}">
                        </label>

                        <label class="cb-field cb-check-field">
                            <span>Verification</span>
                            <label class="cb-check"><input type="checkbox" name="verified" value="1" @checked(old('verified'))><span><strong>Verified result</strong><small>Evidence has been reviewed and confirmed.</small></span></label>
                        </label>
                    </div>
                </section>

                <section class="card cb-panel">
                    <div class="cb-section-head">
                        <div><span class="cb-eyebrow">Evidence</span><h2>Source & notes</h2><p>Attach traceable evidence for future verification.</p></div>
                        <span class="cb-panel__icon"><i data-lucide="file-check-2"></i></span>
                    </div>

                    <div class="cb-form-grid">
                        <label class="cb-field">
                            <span>Source name</span>
                            <input class="input" name="source_name" value="{{ old('source_name') }}" placeholder="e.g. Official benchmark report">
                        </label>

                        <label class="cb-field">
                            <span>Source URL</span>
                            <input class="input" type="url" name="source_url" value="{{ old('source_url') }}" placeholder="https://...">
                        </label>

                        <label class="cb-field cb-field--full">
                            <span>Notes</span>
                            <textarea class="textarea" name="notes" rows="6" placeholder="Testing conditions, methodology or caveats...">{{ old('notes') }}</textarea>
                        </label>
                    </div>
                </section>
            </main>

            <aside class="cb-benchmark-editor__aside">
                <section class="card cb-builder__review">
                    <span class="cb-eyebrow">Evidence standard</span>
                    <div class="cb-builder__review-icon"><i data-lucide="shield-check"></i></div>
                    <h3>Record integrity</h3>
                    <p>Prefer source-backed scores with a test date and verification state.</p>
                    <div class="cb-builder__review-list">
                        <div><span>Score</span><strong>Raw metric</strong></div>
                        <div><span>History</span><strong>Preserved</strong></div>
                        <div><span>Composite</span><strong>Class-safe only</strong></div>
                    </div>
                    <button class="btn btn-primary cb-builder__save" type="submit">
                        <i data-lucide="save"></i>
                        Save Result
                    </button>
                </section>
            </aside>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const typeInputs = [...document.querySelectorAll('input[name="type"]')];
    const modelField = document.getElementById('benchmarkModelField');
    const toolField = document.getElementById('benchmarkToolField');
    const benchmarkClassMap = @json($benchmarkDefinitions->pluck('benchmark_class','name'));
    const benchmarkNameInput = document.querySelector('input[name="benchmark_name"]');
    const benchmarkClassSelect = document.querySelector('select[name="benchmark_class"]');

    function syncType() {
        const type = document.querySelector('input[name="type"]:checked')?.value || 'model';
        const modelSelect = document.querySelector('[data-item-select="model"]');
        const toolSelect = document.querySelector('[data-item-select="tool"]');

        modelField.hidden = type !== 'model';
        toolField.hidden = type !== 'tool';
        modelSelect.disabled = type !== 'model';
        toolSelect.disabled = type !== 'tool';
        modelSelect.required = type === 'model';
        toolSelect.required = type === 'tool';

        document.querySelectorAll('[data-benchmark-type]').forEach(label => {
            label.classList.toggle('is-active', label.dataset.benchmarkType === type);
        });
    }

    function syncBenchmarkClass() {
        const knownClass = benchmarkClassMap[benchmarkNameInput?.value || ''];
        if (knownClass && benchmarkClassSelect) benchmarkClassSelect.value = knownClass;
    }

    typeInputs.forEach(input => input.addEventListener('change', syncType));
    benchmarkNameInput?.addEventListener('change', syncBenchmarkClass);
    benchmarkNameInput?.addEventListener('input', syncBenchmarkClass);
    syncType();
    syncBenchmarkClass();
});
</script>
@endpush
