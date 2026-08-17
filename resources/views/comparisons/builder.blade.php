@extends('layouts.admin')
@section('title', isset($comparison) ? 'Edit Comparison' : 'New Comparison')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/pages/comparison-benchmarks.css') }}">
@endpush

@section('content')
@php
    $comparison ??= null;
    $selectedToolIds = array_map('intval', old('tool_ids', $comparison && $comparison->comparable_type === 'tool' ? $comparison->item_ids : []));
    $selectedModelIds = array_map('intval', old('model_ids', $comparison && $comparison->comparable_type === 'model' ? $comparison->item_ids : []));
    $selectedType = count($selectedModelIds) ? 'model' : 'tool';
@endphp

<div class="cb-page cb-builder">
    <x-page-header
        :title="$comparison ? 'Edit Comparison' : 'Build Comparison'"
        :subtitle="$comparison ? 'Update the selected items, title or publishing state.' : 'Select 2–4 tools or 2–4 models and create a structured comparison set.'"
        :breadcrumb="['Comparison & Benchmarks', 'Comparisons', $comparison ? 'Edit' : 'Builder']"
    >
        <x-slot:actions>
            <a href="{{ $comparison ? route('admin.comparisons.show', $comparison->id) : route('admin.comparisons.index') }}" class="btn btn-secondary">
                <i data-lucide="arrow-left"></i>
                Cancel
            </a>
        </x-slot:actions>
    </x-page-header>

    @if($errors->any())
        <div class="alert alert-danger cb-error">
            <i data-lucide="circle-alert"></i>
            <div>
                <strong>Comparison could not be saved.</strong>
                <ul>
                    @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                </ul>
            </div>
        </div>
    @endif

    <form
        method="POST"
        action="{{ $comparison ? route('admin.comparisons.update', $comparison->id) : route('admin.comparisons.store') }}"
        class="cb-builder__form"
        id="comparisonForm"
    >
        @csrf
        @if($comparison) @method('PUT') @endif

        <div class="cb-builder__layout">
            <main class="cb-builder__main">
                <section class="card cb-panel">
                    <div class="cb-section-head">
                        <div>
                            <span class="cb-eyebrow">Step 1</span>
                            <h2>Comparison identity</h2>
                            <p>Give the analysis a clear title and decide whether it is ready to publish.</p>
                        </div>
                        <span class="cb-panel__icon"><i data-lucide="file-pen-line"></i></span>
                    </div>

                    <div class="cb-form-grid">
                        <label class="cb-field cb-field--full">
                            <span>Comparison title <b>*</b></span>
                            <input class="input" name="title" value="{{ old('title', $comparison?->title) }}" placeholder="e.g. Claude 4 vs GPT-5 for coding workflows" required>
                            <small>Use a title that makes the decision context obvious.</small>
                        </label>

                        <label class="cb-field">
                            <span>Status <b>*</b></span>
                            <select class="select" name="status" required>
                                <option value="draft" @selected(old('status', $comparison?->status ?? 'draft') === 'draft')>Draft</option>
                                <option value="published" @selected(old('status', $comparison?->status ?? 'draft') === 'published')>Published</option>
                            </select>
                            <small>Drafts remain internal; published comparisons are ready for use.</small>
                        </label>

                        <div class="cb-field">
                            <span>Selection rules</span>
                            <div class="cb-rule-card">
                                <i data-lucide="shield-check"></i>
                                <span>Select between <strong>2 and 4</strong> unique items from one type only.</span>
                            </div>
                        </div>
                    </div>
                </section>

                <section class="card cb-panel">
                    <div class="cb-section-head">
                        <div>
                            <span class="cb-eyebrow">Step 2</span>
                            <h2>Select comparison type</h2>
                            <p>Tools and models cannot be mixed in the same comparison.</p>
                        </div>
                        <span class="cb-panel__icon"><i data-lucide="split"></i></span>
                    </div>

                    <div class="cb-type-switch" role="radiogroup" aria-label="Comparison type">
                        <label class="cb-type-option {{ $selectedType === 'tool' ? 'is-active' : '' }}" data-type-option="tool">
                            <input type="radio" name="comparison_type_ui" value="tool" @checked($selectedType === 'tool')>
                            <span class="cb-type-option__icon"><i data-lucide="wrench"></i></span>
                            <span>
                                <strong>AI Tools</strong>
                                <small>Compare products, platforms and assistants.</small>
                            </span>
                        </label>

                        <label class="cb-type-option {{ $selectedType === 'model' ? 'is-active' : '' }}" data-type-option="model">
                            <input type="radio" name="comparison_type_ui" value="model" @checked($selectedType === 'model')>
                            <span class="cb-type-option__icon"><i data-lucide="brain-circuit"></i></span>
                            <span>
                                <strong>AI Models</strong>
                                <small>Compare foundational model capabilities and scores.</small>
                            </span>
                        </label>
                    </div>
                </section>

                <section class="card cb-panel" id="toolSelectionPanel">
                    <div class="cb-section-head">
                        <div>
                            <span class="cb-eyebrow">Tool set</span>
                            <h2>Select AI Tools</h2>
                            <p>Choose 2–4 products for side-by-side analysis.</p>
                        </div>
                        <span class="cb-selection-count" data-count-for="tool">0 selected</span>
                    </div>

                    <div class="cb-select-grid">
                        @foreach($tools as $tool)
                            <label class="cb-select-card">
                                <input type="checkbox" name="tool_ids[]" value="{{ $tool->id }}" @checked(in_array((int)$tool->id, $selectedToolIds, true))>
                                <span class="cb-select-card__check"><i data-lucide="check"></i></span>
                                <span class="cb-select-card__icon"><i data-lucide="wrench"></i></span>
                                <span class="cb-select-card__copy">
                                    <strong>{{ $tool->name }}</strong>
                                    <small>{{ $tool->company?->name ?? 'Independent / Unknown company' }}</small>
                                </span>
                            </label>
                        @endforeach
                    </div>
                </section>

                <section class="card cb-panel" id="modelSelectionPanel">
                    <div class="cb-section-head">
                        <div>
                            <span class="cb-eyebrow">Model set</span>
                            <h2>Select AI Models</h2>
                            <p>Choose 2–4 models for side-by-side analysis.</p>
                        </div>
                        <span class="cb-selection-count" data-count-for="model">0 selected</span>
                    </div>

                    <div class="cb-select-grid">
                        @foreach($models as $model)
                            <label class="cb-select-card">
                                <input type="checkbox" name="model_ids[]" value="{{ $model->id }}" @checked(in_array((int)$model->id, $selectedModelIds, true))>
                                <span class="cb-select-card__check"><i data-lucide="check"></i></span>
                                <span class="cb-select-card__icon cb-select-card__icon--model"><i data-lucide="brain-circuit"></i></span>
                                <span class="cb-select-card__copy">
                                    <strong>{{ $model->name }}</strong>
                                    <small>{{ $model->company?->name ?? 'Unknown company' }}{{ $model->version ? ' · '.$model->version : '' }}</small>
                                </span>
                            </label>
                        @endforeach
                    </div>
                </section>
            </main>

            <aside class="cb-builder__aside">
                <section class="card cb-builder__review">
                    <span class="cb-eyebrow">Build summary</span>
                    <div class="cb-builder__review-icon"><i data-lucide="git-compare-arrows"></i></div>
                    <h3 id="comparisonTypeLabel">{{ $selectedType === 'tool' ? 'Tool Comparison' : 'Model Comparison' }}</h3>
                    <p id="comparisonSelectionText">Select 2–4 items to continue.</p>

                    <div class="cb-builder__review-list">
                        <div><span>Minimum items</span><strong>2</strong></div>
                        <div><span>Maximum items</span><strong>4</strong></div>
                        <div><span>Mixed types</span><strong>Not allowed</strong></div>
                    </div>

                    <button class="btn btn-primary cb-builder__save" type="submit">
                        <i data-lucide="{{ $comparison ? 'save' : 'sparkles' }}"></i>
                        {{ $comparison ? 'Save Comparison' : 'Create Comparison' }}
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
    const typeRadios = [...document.querySelectorAll('input[name="comparison_type_ui"]')];
    const toolPanel = document.getElementById('toolSelectionPanel');
    const modelPanel = document.getElementById('modelSelectionPanel');
    const typeLabel = document.getElementById('comparisonTypeLabel');
    const selectionText = document.getElementById('comparisonSelectionText');

    const checkboxes = {
        tool: [...document.querySelectorAll('input[name="tool_ids[]"]')],
        model: [...document.querySelectorAll('input[name="model_ids[]"]')]
    };

    function currentType() {
        return document.querySelector('input[name="comparison_type_ui"]:checked')?.value || 'tool';
    }

    function sync() {
        const type = currentType();
        const inactive = type === 'tool' ? 'model' : 'tool';

        toolPanel.hidden = type !== 'tool';
        modelPanel.hidden = type !== 'model';

        checkboxes[inactive].forEach(box => {
            box.checked = false;
            box.disabled = true;
        });
        checkboxes[type].forEach(box => box.disabled = false);

        document.querySelectorAll('[data-type-option]').forEach(label => {
            label.classList.toggle('is-active', label.dataset.typeOption === type);
        });

        ['tool','model'].forEach(key => {
            const count = checkboxes[key].filter(box => box.checked).length;
            const target = document.querySelector(`[data-count-for="${key}"]`);
            if (target) target.textContent = `${count} selected`;
        });

        const count = checkboxes[type].filter(box => box.checked).length;
        typeLabel.textContent = type === 'tool' ? 'Tool Comparison' : 'Model Comparison';
        selectionText.textContent = count
            ? `${count} ${type === 'tool' ? 'tool' : 'model'}${count === 1 ? '' : 's'} selected.`
            : 'Select 2–4 items to continue.';

        checkboxes[type].forEach(box => {
            box.closest('.cb-select-card')?.classList.toggle('is-selected', box.checked);
        });
        checkboxes[inactive].forEach(box => {
            box.closest('.cb-select-card')?.classList.remove('is-selected');
        });
    }

    typeRadios.forEach(radio => radio.addEventListener('change', sync));
    [...checkboxes.tool, ...checkboxes.model].forEach(box => {
        box.addEventListener('change', () => {
            const type = currentType();
            const selected = checkboxes[type].filter(item => item.checked);
            if (selected.length > 4) {
                box.checked = false;
            }
            sync();
        });
    });

    sync();
});
</script>
@endpush
