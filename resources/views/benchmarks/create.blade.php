@extends('layouts.admin')
@section('title', 'Create Benchmark')

@section('content')

<style>
    .benchmark-create {
        --bc-primary: #6366f1;
        --bc-primary-hover: #818cf8;
        --bc-text: #f3f5fa;
        --bc-muted: #8b95a8;
        --bc-border: rgba(255,255,255,.07);
        --bc-surface: rgba(255,255,255,.025);
        --bc-success: #22c55e;
    }

    .benchmark-create * {
        box-sizing: border-box;
    }

    /* =========================
       PAGE HEADER
    ========================== */

    .benchmark-create .bc-header {
        display: flex;
        align-items: flex-end;
        justify-content: space-between;
        gap: 20px;
        margin-bottom: 24px;
    }

    .benchmark-create .bc-title-wrap {
        display: flex;
        align-items: flex-start;
        gap: 14px;
    }

    .benchmark-create .bc-title-icon {
        width: 46px;
        height: 46px;
        flex: 0 0 46px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 14px;
        color: #a5b4fc;
        background: linear-gradient(
            145deg,
            rgba(99,102,241,.18),
            rgba(139,92,246,.08)
        );
        border: 1px solid rgba(129,140,248,.18);
        box-shadow: 0 12px 30px rgba(0,0,0,.14);
    }

    .benchmark-create .bc-title-icon svg {
        width: 21px;
        height: 21px;
    }

    .benchmark-create .bc-kicker {
        margin: 0 0 4px;
        color: #778298;
        font-size: 10px;
        font-weight: 750;
        letter-spacing: .12em;
        text-transform: uppercase;
    }

    .benchmark-create .bc-title {
        margin: 0;
        color: var(--bc-text);
        font-size: 25px;
        line-height: 1.2;
        font-weight: 750;
        letter-spacing: -.025em;
    }

    .benchmark-create .bc-subtitle {
        margin: 7px 0 0;
        color: var(--bc-muted);
        font-size: 13px;
    }

    .benchmark-create .bc-save-btn {
        min-height: 39px;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 0 16px;
        border-radius: 10px;
        font-size: 12px;
        font-weight: 700;
        box-shadow: 0 8px 22px rgba(99,102,241,.16);
    }

    .benchmark-create .bc-save-btn svg {
        width: 15px;
        height: 15px;
    }

    /* =========================
       ERROR
    ========================== */

    .benchmark-create .bc-error {
        display: flex;
        align-items: flex-start;
        gap: 11px;
        margin-bottom: 18px;
        padding: 13px 15px;
        color: #fecaca;
        background: rgba(239,68,68,.07);
        border: 1px solid rgba(239,68,68,.16);
        border-radius: 12px;
    }

    .benchmark-create .bc-error-icon {
        width: 28px;
        height: 28px;
        flex: 0 0 28px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #fca5a5;
        background: rgba(239,68,68,.10);
        border-radius: 8px;
    }

    .benchmark-create .bc-error-icon svg {
        width: 15px;
        height: 15px;
    }

    .benchmark-create .bc-error ul {
        margin: 0;
        padding-left: 16px;
        font-size: 12px;
        line-height: 1.7;
    }

    /* =========================
       MAIN CARD
    ========================== */

    .benchmark-create .bc-card {
        position: relative;
        overflow: hidden;
        padding: 0;
        background:
            linear-gradient(
                145deg,
                rgba(255,255,255,.038),
                rgba(255,255,255,.015)
            );
        border: 1px solid var(--bc-border);
        border-radius: 17px;
        box-shadow: 0 18px 45px rgba(0,0,0,.08);
    }

    .benchmark-create .bc-card::before {
        content: "";
        position: absolute;
        top: -130px;
        right: -100px;
        width: 260px;
        height: 260px;
        border-radius: 50%;
        background: rgba(99,102,241,.055);
        filter: blur(4px);
        pointer-events: none;
    }

    .benchmark-create .bc-card-head {
        position: relative;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 20px;
        padding: 20px 22px;
        border-bottom: 1px solid rgba(255,255,255,.055);
    }

    .benchmark-create .bc-card-heading {
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .benchmark-create .bc-card-icon {
        width: 36px;
        height: 36px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #a5b4fc;
        background: rgba(99,102,241,.09);
        border: 1px solid rgba(129,140,248,.11);
        border-radius: 10px;
    }

    .benchmark-create .bc-card-icon svg {
        width: 17px;
        height: 17px;
    }

    .benchmark-create .bc-card-title {
        margin: 0;
        color: #eef1f7;
        font-size: 14px;
        font-weight: 700;
    }

    .benchmark-create .bc-card-description {
        margin: 3px 0 0;
        color: #717d92;
        font-size: 11px;
    }

    .benchmark-create .bc-status {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 6px 9px;
        color: #86efac;
        background: rgba(34,197,94,.055);
        border: 1px solid rgba(34,197,94,.10);
        border-radius: 7px;
        font-size: 9px;
        font-weight: 750;
        text-transform: uppercase;
        letter-spacing: .05em;
    }

    .benchmark-create .bc-status-dot {
        width: 6px;
        height: 6px;
        border-radius: 50%;
        background: #22c55e;
        box-shadow: 0 0 8px rgba(34,197,94,.55);
    }

    .benchmark-create .bc-card-body {
        position: relative;
        padding: 24px 22px 22px;
    }

    /* =========================
       TYPE SWITCH
    ========================== */

    .benchmark-create .bc-type-section {
        margin-bottom: 25px;
    }

    .benchmark-create .bc-label {
        display: block;
        margin-bottom: 9px;
        color: #cbd3e0;
        font-size: 11px;
        font-weight: 700;
    }

    .benchmark-create .bc-type-switch {
        display: inline-flex;
        gap: 5px;
        padding: 5px;
        background: rgba(0,0,0,.12);
        border: 1px solid rgba(255,255,255,.06);
        border-radius: 11px;
    }

    .benchmark-create .bc-type-option {
        position: relative;
        min-width: 130px;
        min-height: 40px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        padding: 0 15px;
        color: #7e899d;
        background: transparent;
        border: 1px solid transparent;
        border-radius: 8px;
        cursor: pointer;
        font-size: 11px;
        font-weight: 650;
        transition: all .2s ease;
    }

    .benchmark-create .bc-type-option:hover {
        color: #dbe2ed;
        background: rgba(255,255,255,.025);
    }

    .benchmark-create .bc-type-option.is-on {
        color: #eef0ff;
        background: linear-gradient(
            135deg,
            rgba(99,102,241,.20),
            rgba(139,92,246,.11)
        );
        border-color: rgba(129,140,248,.16);
        box-shadow: 0 5px 16px rgba(0,0,0,.10);
    }

    .benchmark-create .bc-type-option input {
        position: absolute;
        opacity: 0;
        pointer-events: none;
    }

    .benchmark-create .bc-type-option svg {
        width: 15px;
        height: 15px;
    }

    /* =========================
       FORM GRID
    ========================== */

    .benchmark-create .bc-form-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 20px;
    }

    .benchmark-create .bc-field {
        min-width: 0;
    }

    .benchmark-create .bc-field label {
        display: block;
        margin-bottom: 8px;
        color: #cbd3e0;
        font-size: 11px;
        font-weight: 700;
    }

    .benchmark-create .bc-field-label {
        display: flex !important;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
    }

    .benchmark-create .bc-required {
        color: #7c86a0;
        font-size: 9px;
        font-weight: 500;
    }

    /* =========================
       INPUTS
    ========================== */

    .benchmark-create .bc-input-wrap {
        position: relative;
    }

    .benchmark-create .bc-input-icon {
        position: absolute;
        top: 50%;
        left: 13px;
        z-index: 2;
        color: #626e84;
        transform: translateY(-50%);
        pointer-events: none;
    }

    .benchmark-create .bc-input-icon svg {
        width: 15px;
        height: 15px;
    }

    .benchmark-create .bc-input,
    .benchmark-create .bc-select {
        width: 100%;
        min-height: 44px;
        color: #e8ecf5 !important;
        background-color: #171b27 !important;
        border: 1px solid rgba(255,255,255,.075);
        border-radius: 10px;
        outline: none;
        font-size: 12px;
        font-weight: 500;
        transition:
            border-color .2s ease,
            background-color .2s ease,
            box-shadow .2s ease;
    }

    .benchmark-create .bc-input {
        padding: 0 13px;
    }

    .benchmark-create .bc-input::placeholder {
        color: #566176;
    }

    .benchmark-create .bc-input:focus,
    .benchmark-create .bc-select:focus {
        background-color: #1b2030 !important;
        border-color: rgba(129,140,248,.45);
        box-shadow: 0 0 0 3px rgba(99,102,241,.08);
    }

    .benchmark-create .bc-input.has-icon {
        padding-left: 38px;
    }

    /* =========================
       DARK DROPDOWN
    ========================== */

    .benchmark-create .bc-select {
        min-height: 44px;
        padding: 0 38px 0 38px;
        cursor: pointer;
        appearance: auto;
        -webkit-appearance: auto;
    }

    .benchmark-create .bc-select:hover {
        background-color: #1b2030 !important;
        border-color: rgba(129,140,248,.22);
    }

    .benchmark-create .bc-select option {
        color: #e8ecf5 !important;
        background-color: #171b27 !important;
        font-size: 12px;
    }

    .benchmark-create .bc-select option:first-child {
        color: #7d879a !important;
        background-color: #171b27 !important;
    }

    .benchmark-create .bc-select option:checked {
        color: #ffffff !important;
        background-color: #4f46e5 !important;
    }

    .benchmark-create .bc-select:disabled {
        color: #555f73 !important;
        background-color: #11151f !important;
        opacity: .65;
        cursor: not-allowed;
    }

    /* =========================
       SCORE
    ========================== */

    .benchmark-create .bc-score-wrap {
        position: relative;
    }

    .benchmark-create .bc-score-suffix {
        position: absolute;
        top: 50%;
        right: 13px;
        color: #626e84;
        font-size: 11px;
        font-weight: 700;
        transform: translateY(-50%);
        pointer-events: none;
    }

    .benchmark-create .bc-score-input {
        padding-right: 40px;
    }

    /* =========================
       INFO BOX
    ========================== */

    .benchmark-create .bc-helper {
        display: flex;
        align-items: flex-start;
        gap: 9px;
        margin: 22px 0 0;
        padding: 13px 14px;
        color: #7f8ba0;
        background: rgba(255,255,255,.018);
        border: 1px solid rgba(255,255,255,.05);
        border-radius: 10px;
        font-size: 11px;
        line-height: 1.65;
    }

    .benchmark-create .bc-helper-icon {
        width: 27px;
        height: 27px;
        flex: 0 0 27px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #8b9cff;
        background: rgba(99,102,241,.08);
        border-radius: 7px;
    }

    .benchmark-create .bc-helper-icon svg {
        width: 14px;
        height: 14px;
    }

    /* =========================
       FOOTER
    ========================== */

    .benchmark-create .bc-footer {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 15px;
        margin-top: 18px;
        color: #667187;
        font-size: 10px;
    }

    .benchmark-create .bc-footer-item {
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }

    .benchmark-create .bc-footer-item svg {
        width: 13px;
        height: 13px;
        color: #77839a;
    }

    /* =========================
       RESPONSIVE
    ========================== */

    @media (max-width: 850px) {

        .benchmark-create .bc-header {
            align-items: flex-start;
            flex-direction: column;
        }

        .benchmark-create .bc-form-grid {
            grid-template-columns: 1fr;
        }

        .benchmark-create .bc-save-btn {
            width: 100%;
            justify-content: center;
        }
    }

    @media (max-width: 600px) {

        .benchmark-create .bc-title {
            font-size: 21px;
        }

        .benchmark-create .bc-title-icon {
            width: 40px;
            height: 40px;
            flex-basis: 40px;
        }

        .benchmark-create .bc-card-head {
            align-items: flex-start;
            flex-direction: column;
        }

        .benchmark-create .bc-status {
            align-self: flex-start;
        }

        .benchmark-create .bc-card-body {
            padding: 19px 15px;
        }

        .benchmark-create .bc-type-switch {
            display: flex;
            width: 100%;
        }

        .benchmark-create .bc-type-option {
            min-width: 0;
            flex: 1;
        }

        .benchmark-create .bc-footer {
            align-items: flex-start;
            flex-direction: column;
        }
    }
</style>


<div class="benchmark-create">

    {{-- PAGE HEADER --}}
    <div class="bc-header">

        <div class="bc-title-wrap">

            <div class="bc-title-icon">
                <i data-lucide="gauge"></i>
            </div>

            <div>
                <div class="bc-kicker">
                    Comparison & Benchmarks
                </div>

                <h1 class="bc-title">
                    Create Benchmark
                </h1>

                <p class="bc-subtitle">
                    Record a score on a standardized AI benchmark test
                </p>
            </div>

        </div>

        <button
            type="submit"
            form="benchmark-form"
            class="btn btn-primary btn-sm bc-save-btn"
        >
            <i data-lucide="save"></i>
            Save Score
        </button>

    </div>


    {{-- ERRORS --}}
    @if ($errors->any())

        <div class="bc-error">

            <div class="bc-error-icon">
                <i data-lucide="alert-circle"></i>
            </div>

            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>

        </div>

    @endif


    @php
        $selectedType = old('type', 'model');
    @endphp


    <form
        id="benchmark-form"
        action="{{ route('admin.benchmarks.store') }}"
        method="POST"
    >

        @csrf

        <div class="bc-card">

            {{-- CARD HEADER --}}
            <div class="bc-card-head">

                <div class="bc-card-heading">

                    <div class="bc-card-icon">
                        <i data-lucide="flask-conical"></i>
                    </div>

                    <div>

                        <h2 class="bc-card-title">
                            Benchmark Result
                        </h2>

                        <p class="bc-card-description">
                            Configure the item and record its benchmark performance.
                        </p>

                    </div>

                </div>

                <span class="bc-status">
                    <span class="bc-status-dot"></span>
                    Ready to record
                </span>

            </div>


            {{-- CARD BODY --}}
            <div class="bc-card-body">

                {{-- TYPE --}}
                <div class="bc-type-section">

                    <label class="bc-label">
                        Benchmark Type
                    </label>

                    <div class="bc-type-switch">

                        <label class="bc-type-option {{ $selectedType === 'model' ? 'is-on' : '' }}">

                            <input
                                type="radio"
                                name="type"
                                value="model"
                                {{ $selectedType === 'model' ? 'checked' : '' }}
                                onchange="toggleItemType('model')"
                            >

                            <i data-lucide="brain"></i>

                            AI Model

                        </label>


                        <label class="bc-type-option {{ $selectedType === 'tool' ? 'is-on' : '' }}">

                            <input
                                type="radio"
                                name="type"
                                value="tool"
                                {{ $selectedType === 'tool' ? 'checked' : '' }}
                                onchange="toggleItemType('tool')"
                            >

                            <i data-lucide="wrench"></i>

                            AI Tool

                        </label>

                    </div>

                </div>


                {{-- FORM GRID --}}
                <div class="bc-form-grid">

                    {{-- MODEL --}}
                    <div
                        class="bc-field"
                        id="model-field"
                        style="{{ $selectedType === 'tool' ? 'display:none;' : '' }}"
                    >

                        <label class="bc-field-label">

                            <span>Select Model</span>

                            <span class="bc-required">
                                Required
                            </span>

                        </label>

                        <div class="bc-input-wrap">

                            <span class="bc-input-icon">
                                <i data-lucide="brain"></i>
                            </span>

                            <select
                                class="bc-select"
                                name="item_id"
                                {{ $selectedType === 'tool' ? 'disabled' : 'required' }}
                            >

                                <option value="">
                                    Select model...
                                </option>

                                @foreach ($models as $model)

                                    <option
                                        value="{{ $model->id }}"
                                        @selected(
                                            old('item_id') == $model->id &&
                                            $selectedType === 'model'
                                        )
                                    >
                                        {{ $model->name }}

                                        @if($model->company)
                                            — {{ $model->company->name }}
                                        @endif
                                    </option>

                                @endforeach

                            </select>

                        </div>

                    </div>


                    {{-- TOOL --}}
                    <div
                        class="bc-field"
                        id="tool-field"
                        style="{{ $selectedType === 'model' ? 'display:none;' : '' }}"
                    >

                        <label class="bc-field-label">

                            <span>Select Tool</span>

                            <span class="bc-required">
                                Required
                            </span>

                        </label>

                        <div class="bc-input-wrap">

                            <span class="bc-input-icon">
                                <i data-lucide="wrench"></i>
                            </span>

                            <select
                                class="bc-select"
                                name="item_id"
                                {{ $selectedType === 'model' ? 'disabled' : 'required' }}
                            >

                                <option value="">
                                    Select tool...
                                </option>

                                @foreach ($tools as $tool)

                                    <option
                                        value="{{ $tool->id }}"
                                        @selected(
                                            old('item_id') == $tool->id &&
                                            $selectedType === 'tool'
                                        )
                                    >
                                        {{ $tool->name }}

                                        @if($tool->company)
                                            — {{ $tool->company->name }}
                                        @endif
                                    </option>

                                @endforeach

                            </select>

                        </div>

                    </div>


                    {{-- BENCHMARK NAME --}}
                    <div class="bc-field">

                        <label class="bc-field-label">

                            <span>Benchmark Name</span>

                            <span class="bc-required">
                                Required
                            </span>

                        </label>

                        <div class="bc-input-wrap">

                            <span class="bc-input-icon">
                                <i data-lucide="file-check-2"></i>
                            </span>

                            <input
                                class="bc-input has-icon"
                                list="benchmark-options"
                                name="benchmark_name"
                                value="{{ old('benchmark_name') }}"
                                placeholder="e.g. MMLU Pro"
                                required
                            >

                        </div>

                        <datalist id="benchmark-options">

                            @foreach ($benchmarks as $benchmark)

                                <option value="{{ $benchmark }}">

                            @endforeach

                        </datalist>

                    </div>


                    {{-- SCORE --}}
                    <div class="bc-field">

                        <label class="bc-field-label">

                            <span>Benchmark Score</span>

                            <span class="bc-required">
                                0–100
                            </span>

                        </label>

                        <div class="bc-score-wrap">

                            <input
                                class="bc-input bc-score-input"
                                type="number"
                                step="0.1"
                                min="0"
                                max="100"
                                name="score"
                                value="{{ old('score') }}"
                                placeholder="94.2"
                                required
                            >

                            <span class="bc-score-suffix">
                                / 100
                            </span>

                        </div>

                    </div>

                </div>


                {{-- INFORMATION --}}
                <div class="bc-helper">

                    <div class="bc-helper-icon">
                        <i data-lucide="info"></i>
                    </div>

                    <div>
                        Saving updates the selected item's per-benchmark breakdown
                        and recalculates its overall composite score as the average
                        of every benchmark recorded for it.
                    </div>

                </div>


                {{-- FOOTER --}}
                <div class="bc-footer">

                    <span class="bc-footer-item">
                        <i data-lucide="shield-check"></i>
                        Score will be stored securely
                    </span>

                    <span class="bc-footer-item">
                        <i data-lucide="calculator"></i>
                        Composite score recalculated automatically
                    </span>

                </div>

            </div>

        </div>

    </form>

</div>


<script>
function toggleItemType(type) {

    var modelField = document.getElementById('model-field');
    var toolField = document.getElementById('tool-field');

    var modelSelect = modelField.querySelector('select');
    var toolSelect = toolField.querySelector('select');

    var modelOption = document
        .querySelector('.bc-type-option input[value="model"]')
        .closest('.bc-type-option');

    var toolOption = document
        .querySelector('.bc-type-option input[value="tool"]')
        .closest('.bc-type-option');


    if (type === 'tool') {

        modelField.style.display = 'none';
        toolField.style.display = 'block';

        modelSelect.disabled = true;
        modelSelect.required = false;

        toolSelect.disabled = false;
        toolSelect.required = true;

        modelOption.classList.remove('is-on');
        toolOption.classList.add('is-on');

    } else {

        modelField.style.display = 'block';
        toolField.style.display = 'none';

        toolSelect.disabled = true;
        toolSelect.required = false;

        modelSelect.disabled = false;
        modelSelect.required = true;

        toolOption.classList.remove('is-on');
        modelOption.classList.add('is-on');
    }
}


document.addEventListener('DOMContentLoaded', function () {

    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }

});
</script>

@endsection