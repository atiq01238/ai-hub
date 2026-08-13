@extends('layouts.admin')
@section('title', isset($comparison) ? 'Edit Comparison' : 'New Comparison')

@section('content')

@php
    $comparison ??= null;
    $selectedToolIds = old('tool_ids', $comparison && $comparison->comparable_type === 'tool' ? $comparison->item_ids : []);
    $selectedModelIds = old('model_ids', $comparison && $comparison->comparable_type === 'model' ? $comparison->item_ids : []);
@endphp

<style>
    .comparison-page {
        --cmp-text: #eef2f7;
        --cmp-muted: #7d899d;
        --cmp-muted-2: #5e697d;
        --cmp-border: rgba(255,255,255,.065);
        --cmp-border-hover: rgba(99,102,241,.28);
        --cmp-primary: #6366f1;
        --cmp-primary-soft: rgba(99,102,241,.11);
        --cmp-cyan: #22d3ee;
        --cmp-green: #34d399;
        --cmp-card: rgba(15,20,31,.82);

        color: var(--cmp-text);
    }

    /* =========================
       HERO
    ========================= */

    .cmp-hero {
        position: relative;
        overflow: hidden;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 25px;
        min-height: 118px;
        margin-bottom: 20px;
        padding: 23px 25px;
        border: 1px solid var(--cmp-border);
        border-radius: 18px;
        background:
            radial-gradient(
                circle at 90% 0%,
                rgba(99,102,241,.19),
                transparent 30%
            ),
            radial-gradient(
                circle at 60% 120%,
                rgba(34,211,238,.055),
                transparent 28%
            ),
            linear-gradient(
                145deg,
                rgba(255,255,255,.045),
                rgba(255,255,255,.012)
            );
        box-shadow: 0 18px 45px rgba(0,0,0,.09);
    }

    .cmp-hero::before {
        content: "";
        position: absolute;
        width: 190px;
        height: 190px;
        right: 8%;
        top: -150px;
        border: 1px solid rgba(99,102,241,.08);
        border-radius: 50%;
        pointer-events: none;
    }

    .cmp-hero-content {
        position: relative;
        z-index: 2;
    }

    .cmp-eyebrow {
        display: flex;
        align-items: center;
        gap: 8px;
        margin-bottom: 7px;
        color: #8795ff;
        font-size: 8px;
        font-weight: 800;
        letter-spacing: .13em;
        text-transform: uppercase;
    }

    .cmp-status-dot {
        width: 6px;
        height: 6px;
        border-radius: 50%;
        background: var(--cmp-green);
        box-shadow: 0 0 0 4px rgba(52,211,153,.09);
    }

    .cmp-title {
        margin: 0;
        color: #f3f5f9;
        font-size: clamp(23px, 3vw, 29px);
        line-height: 1.15;
        font-weight: 800;
        letter-spacing: -.035em;
    }

    .cmp-subtitle {
        margin: 7px 0 0;
        color: var(--cmp-muted);
        font-size: 10px;
    }

    .cmp-actions {
        position: relative;
        z-index: 3;
        display: flex;
        gap: 8px;
        flex-shrink: 0;
    }

    .cmp-actions .btn {
        min-height: 38px;
        padding: 0 14px;
        border-radius: 9px;
        font-size: 10px;
        font-weight: 700;
    }

    .cmp-actions .btn i,
    .cmp-actions .btn svg {
        width: 14px;
        height: 14px;
    }

    /* =========================
       ERROR
    ========================= */

    .cmp-error {
        display: flex;
        gap: 11px;
        align-items: flex-start;
        margin-bottom: 18px;
        padding: 13px 15px;
        color: #ff9c9c;
        background: rgba(239,68,68,.055);
        border: 1px solid rgba(239,68,68,.15);
        border-radius: 11px;
        font-size: 10px;
    }

    .cmp-error-icon {
        width: 28px;
        height: 28px;
        display: flex;
        align-items: center;
        justify-content: center;
        flex: 0 0 28px;
        color: #f87171;
        background: rgba(239,68,68,.08);
        border-radius: 8px;
    }

    .cmp-error-icon svg {
        width: 14px;
        height: 14px;
    }

    .cmp-error-title {
        margin-bottom: 4px;
        color: #ffb0b0;
        font-size: 10px;
        font-weight: 750;
    }

    .cmp-error ul {
        margin: 0;
        padding-left: 16px;
    }

    .cmp-error li {
        margin: 2px 0;
    }

    /* =========================
       MAIN FORM
    ========================= */

    .cmp-title-card {
        margin-bottom: 17px;
        padding: 20px;
        background:
            linear-gradient(
                145deg,
                rgba(255,255,255,.035),
                rgba(255,255,255,.012)
            );
        border: 1px solid var(--cmp-border);
        border-radius: 16px;
        box-shadow: 0 14px 38px rgba(0,0,0,.055);
    }

    .cmp-section-heading {
        display: flex;
        align-items: center;
        gap: 10px;
        margin-bottom: 13px;
    }

    .cmp-section-icon {
        width: 32px;
        height: 32px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #9ba8ff;
        background: rgba(99,102,241,.09);
        border: 1px solid rgba(99,102,241,.14);
        border-radius: 9px;
    }

    .cmp-section-icon svg {
        width: 15px;
        height: 15px;
    }

    .cmp-section-title {
        color: #e8ecf3;
        font-size: 11px;
        font-weight: 750;
    }

    .cmp-section-desc {
        margin-top: 3px;
        color: var(--cmp-muted-2);
        font-size: 8px;
    }

    .cmp-title-input {
        width: 100%;
        height: 47px;
        padding: 0 14px;
        color: #edf1f7;
        background: rgba(0,0,0,.15);
        border: 1px solid var(--cmp-border);
        border-radius: 10px;
        outline: none;
        font-size: 11px;
        transition: .18s ease;
    }

    .cmp-title-input:hover {
        border-color: rgba(255,255,255,.10);
    }

    .cmp-title-input:focus {
        border-color: rgba(99,102,241,.45);
        background: rgba(99,102,241,.025);
        box-shadow: 0 0 0 3px rgba(99,102,241,.08);
    }

    .cmp-title-input::placeholder {
        color: #586477;
    }

    /* =========================
       SELECTOR GRID
    ========================= */

    .cmp-selector-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0,1fr));
        gap: 17px;
    }

    .cmp-selector {
        overflow: hidden;
        background:
            linear-gradient(
                145deg,
                rgba(255,255,255,.035),
                rgba(255,255,255,.012)
            );
        border: 1px solid var(--cmp-border);
        border-radius: 16px;
        box-shadow: 0 14px 38px rgba(0,0,0,.055);
    }

    .cmp-selector-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        padding: 17px;
        border-bottom: 1px solid rgba(255,255,255,.055);
        background: rgba(255,255,255,.008);
    }

    .cmp-selector-title {
        display: flex;
        align-items: center;
        gap: 11px;
        min-width: 0;
    }

    .cmp-type-icon {
        width: 36px;
        height: 36px;
        display: flex;
        align-items: center;
        justify-content: center;
        flex: 0 0 36px;
        color: #aeb9ff;
        background: rgba(99,102,241,.09);
        border: 1px solid rgba(99,102,241,.14);
        border-radius: 10px;
    }

    .cmp-type-icon.model {
        color: #8eeaf5;
        background: rgba(34,211,238,.07);
        border-color: rgba(34,211,238,.14);
    }

    .cmp-type-icon svg {
        width: 16px;
        height: 16px;
    }

    .cmp-selector-name {
        color: #e9edf4;
        font-size: 11px;
        font-weight: 750;
    }

    .cmp-selector-desc {
        margin-top: 3px;
        color: var(--cmp-muted-2);
        font-size: 8px;
    }

    .cmp-count {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: 6px 8px;
        color: #8b97aa;
        background: rgba(255,255,255,.025);
        border: 1px solid rgba(255,255,255,.055);
        border-radius: 7px;
        font-size: 8px;
        font-weight: 700;
        white-space: nowrap;
    }

    .cmp-count-number {
        color: #b8c2d4;
    }

    /* =========================
       SEARCH
    ========================= */

    .cmp-search {
        position: relative;
        margin: 11px 11px 4px;
    }

    .cmp-search input {
        width: 100%;
        height: 35px;
        padding: 0 11px 0 33px;
        color: #dfe5ef;
        background: rgba(0,0,0,.13);
        border: 1px solid rgba(255,255,255,.055);
        border-radius: 8px;
        outline: none;
        font-size: 9px;
    }

    .cmp-search input:focus {
        border-color: rgba(99,102,241,.30);
        box-shadow: 0 0 0 3px rgba(99,102,241,.055);
    }

    .cmp-search input::placeholder {
        color: #596578;
    }

    .cmp-search svg {
        position: absolute;
        top: 50%;
        left: 11px;
        width: 13px;
        height: 13px;
        color: #667287;
        transform: translateY(-50%);
        pointer-events: none;
    }

    /* =========================
       LIST
    ========================= */

    .cmp-list {
        max-height: 365px;
        overflow-y: auto;
        padding: 7px;
        scrollbar-width: thin;
        scrollbar-color: rgba(255,255,255,.10) transparent;
    }

    .cmp-list::-webkit-scrollbar {
        width: 5px;
    }

    .cmp-list::-webkit-scrollbar-track {
        background: transparent;
    }

    .cmp-list::-webkit-scrollbar-thumb {
        background: rgba(255,255,255,.10);
        border-radius: 20px;
    }

    .cmp-item {
        position: relative;
        display: flex;
        align-items: center;
        gap: 10px;
        min-height: 54px;
        padding: 8px 10px;
        margin-bottom: 3px;
        border: 1px solid transparent;
        border-radius: 10px;
        cursor: pointer;
        transition: .16s ease;
    }

    .cmp-item:last-child {
        margin-bottom: 0;
    }

    .cmp-item:hover {
        background: rgba(255,255,255,.025);
        border-color: rgba(255,255,255,.055);
    }

    .cmp-item:has(input:checked) {
        background:
            linear-gradient(
                90deg,
                rgba(99,102,241,.10),
                rgba(99,102,241,.035)
            );
        border-color: rgba(99,102,241,.20);
    }

    .cmp-item:has(input:checked)::before {
        content: "";
        position: absolute;
        left: -1px;
        top: 9px;
        bottom: 9px;
        width: 2px;
        background: #818cf8;
        border-radius: 0 3px 3px 0;
    }

    .cmp-checkbox {
        width: 16px;
        height: 16px;
        flex: 0 0 16px;
        accent-color: #6366f1;
        cursor: pointer;
    }

    .cmp-avatar {
        width: 34px;
        height: 34px;
        display: flex;
        align-items: center;
        justify-content: center;
        flex: 0 0 34px;
        color: #b9c2ff;
        background: linear-gradient(
            145deg,
            rgba(99,102,241,.16),
            rgba(99,102,241,.045)
        );
        border: 1px solid rgba(99,102,241,.13);
        border-radius: 9px;
        font-size: 9px;
        font-weight: 800;
        text-transform: uppercase;
    }

    .cmp-avatar.model {
        color: #9beaf3;
        background: linear-gradient(
            145deg,
            rgba(34,211,238,.13),
            rgba(34,211,238,.035)
        );
        border-color: rgba(34,211,238,.12);
    }

    .cmp-item-info {
        min-width: 0;
        flex: 1;
    }

    .cmp-item-name {
        display: block;
        overflow: hidden;
        color: #dfe5ee;
        font-size: 9.5px;
        font-weight: 700;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .cmp-item-company {
        display: block;
        overflow: hidden;
        margin-top: 3px;
        color: #687489;
        font-size: 7.5px;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .cmp-selected {
        width: 19px;
        height: 19px;
        display: none;
        align-items: center;
        justify-content: center;
        flex: 0 0 19px;
        color: white;
        background: #6366f1;
        border-radius: 50%;
        box-shadow: 0 4px 12px rgba(99,102,241,.25);
    }

    .cmp-item:has(input:checked) .cmp-selected {
        display: flex;
    }

    .cmp-selected svg {
        width: 10px;
        height: 10px;
    }

    /* =========================
       EMPTY
    ========================= */

    .cmp-empty {
        padding: 45px 20px;
        color: var(--cmp-muted-2);
        text-align: center;
        font-size: 9px;
    }

    .cmp-empty-icon {
        width: 42px;
        height: 42px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 10px;
        color: #667286;
        background: rgba(255,255,255,.025);
        border: 1px solid rgba(255,255,255,.055);
        border-radius: 11px;
    }

    .cmp-empty-icon svg {
        width: 17px;
        height: 17px;
    }

    .cmp-empty strong {
        display: block;
        margin-bottom: 4px;
        color: #aab3c2;
        font-size: 10px;
    }

    /* =========================
       FOOTER NOTE
    ========================= */

    .cmp-footer-note {
        display: flex;
        align-items: center;
        gap: 8px;
        margin-top: 15px;
        padding: 11px 13px;
        color: #68758a;
        background: rgba(255,255,255,.012);
        border: 1px dashed rgba(255,255,255,.075);
        border-radius: 9px;
        font-size: 8.5px;
    }

    .cmp-footer-note svg {
        width: 14px;
        height: 14px;
        flex: 0 0 14px;
        color: #7d8daf;
    }

    /* =========================
       RESPONSIVE
    ========================= */

    @media (max-width: 850px) {
        .cmp-hero {
            align-items: flex-start;
            flex-direction: column;
        }

        .cmp-actions {
            width: 100%;
        }

        .cmp-actions .btn {
            flex: 1;
        }

        .cmp-selector-grid {
            grid-template-columns: 1fr;
        }
    }

    @media (max-width: 520px) {
        .cmp-hero {
            padding: 18px;
        }

        .cmp-actions {
            flex-direction: column;
        }

        .cmp-actions .btn {
            width: 100%;
        }

        .cmp-title-card {
            padding: 15px;
        }

        .cmp-selector-head {
            padding: 14px;
        }
    }
</style>


<div class="comparison-page">

    {{-- HERO --}}
    <section class="cmp-hero">

        <div class="cmp-hero-content">

            <div class="cmp-eyebrow">
                <span class="cmp-status-dot"></span>
                Comparison Intelligence
            </div>

            <h1 class="cmp-title">
                {{ $comparison ? 'Edit Comparison' : 'New Comparison' }}
            </h1>

            <p class="cmp-subtitle">
                Compare 2–4 AI tools or 2–4 AI models side by side
            </p>

        </div>

        <div class="cmp-actions">

            <button
                type="submit"
                form="comparison-form"
                name="status"
                value="draft"
                class="btn btn-secondary btn-sm"
            >
                <i data-lucide="save"></i>
                Save Draft
            </button>

            <button
                type="submit"
                form="comparison-form"
                name="status"
                value="published"
                class="btn btn-primary btn-sm"
            >
                <i data-lucide="rocket"></i>
                Publish
            </button>

        </div>

    </section>


    {{-- ERRORS --}}
    @if ($errors->any())

        <div class="cmp-error">

            <div class="cmp-error-icon">
                <i data-lucide="circle-alert"></i>
            </div>

            <div>

                <div class="cmp-error-title">
                    Please check the following fields
                </div>

                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>

            </div>

        </div>

    @endif


    <form
        id="comparison-form"
        action="{{ $comparison ? route('admin.comparisons.update', $comparison->id) : route('admin.comparisons.store') }}"
        method="POST"
    >

        @csrf

        @if ($comparison)
            @method('PUT')
        @endif


        {{-- TITLE --}}
        <div class="cmp-title-card">

            <div class="cmp-section-heading">

                <span class="cmp-section-icon">
                    <i data-lucide="heading"></i>
                </span>

                <div>

                    <div class="cmp-section-title">
                        Comparison Title
                    </div>

                    <div class="cmp-section-desc">
                        Give this comparison a clear and searchable title
                    </div>

                </div>

            </div>

            <input
                class="cmp-title-input"
                name="title"
                value="{{ old('title', $comparison->title ?? '') }}"
                placeholder="e.g. ChatGPT vs Claude"
                required
            >

        </div>


        {{-- SELECTORS --}}
        <div class="cmp-selector-grid">


            {{-- AI TOOLS --}}
            <section class="cmp-selector">

                <div class="cmp-selector-head">

                    <div class="cmp-selector-title">

                        <span class="cmp-type-icon">
                            <i data-lucide="wrench"></i>
                        </span>

                        <div>

                            <div class="cmp-selector-name">
                                Compare AI Tools
                            </div>

                            <div class="cmp-selector-desc">
                                Select 2–4 tools
                            </div>

                        </div>

                    </div>

                    <span class="cmp-count">
                        <span class="cmp-count-number" id="tool-count">
                            {{ count($selectedToolIds) }}
                        </span>
                        selected
                    </span>

                </div>


                <div class="cmp-search">
                    <i data-lucide="search"></i>

                    <input
                        type="text"
                        placeholder="Search AI tools..."
                        data-search-target="tools-list"
                    >
                </div>


                <div class="cmp-list" id="tools-list">

                    @forelse ($tools as $tool)

                        <label
                            class="cmp-item"
                            data-search="{{ strtolower($tool->name . ' ' . ($tool->company->name ?? '')) }}"
                        >

                            <input
                                class="cmp-checkbox tool-checkbox"
                                type="checkbox"
                                name="tool_ids[]"
                                value="{{ $tool->id }}"
                                {{ in_array($tool->id, $selectedToolIds) ? 'checked' : '' }}
                            >

                            <div class="cmp-avatar">
                                {{ substr($tool->name, 0, 2) }}
                            </div>

                            <div class="cmp-item-info">

                                <span class="cmp-item-name">
                                    {{ $tool->name }}
                                </span>

                                <span class="cmp-item-company">
                                    {{ $tool->company->name ?? 'No company assigned' }}
                                </span>

                            </div>

                            <span class="cmp-selected">
                                <i data-lucide="check"></i>
                            </span>

                        </label>

                    @empty

                        <div class="cmp-empty">

                            <div class="cmp-empty-icon">
                                <i data-lucide="wrench"></i>
                            </div>

                            <strong>
                                No AI tools available
                            </strong>

                            Add AI tools first to create a comparison.

                        </div>

                    @endforelse

                </div>

            </section>


            {{-- AI MODELS --}}
            <section class="cmp-selector">

                <div class="cmp-selector-head">

                    <div class="cmp-selector-title">

                        <span class="cmp-type-icon model">
                            <i data-lucide="brain"></i>
                        </span>

                        <div>

                            <div class="cmp-selector-name">
                                Compare AI Models
                            </div>

                            <div class="cmp-selector-desc">
                                Select 2–4 models
                            </div>

                        </div>

                    </div>

                    <span class="cmp-count">
                        <span class="cmp-count-number" id="model-count">
                            {{ count($selectedModelIds) }}
                        </span>
                        selected
                    </span>

                </div>


                <div class="cmp-search">
                    <i data-lucide="search"></i>

                    <input
                        type="text"
                        placeholder="Search AI models..."
                        data-search-target="models-list"
                    >
                </div>


                <div class="cmp-list" id="models-list">

                    @forelse ($models as $model)

                        <label
                            class="cmp-item"
                            data-search="{{ strtolower($model->name . ' ' . ($model->company->name ?? '')) }}"
                        >

                            <input
                                class="cmp-checkbox model-checkbox"
                                type="checkbox"
                                name="model_ids[]"
                                value="{{ $model->id }}"
                                {{ in_array($model->id, $selectedModelIds) ? 'checked' : '' }}
                            >

                            <div class="cmp-avatar model">
                                {{ substr($model->name, 0, 2) }}
                            </div>

                            <div class="cmp-item-info">

                                <span class="cmp-item-name">
                                    {{ $model->name }}
                                </span>

                                <span class="cmp-item-company">
                                    {{ $model->company->name ?? 'No company assigned' }}
                                </span>

                            </div>

                            <span class="cmp-selected">
                                <i data-lucide="check"></i>
                            </span>

                        </label>

                    @empty

                        <div class="cmp-empty">

                            <div class="cmp-empty-icon">
                                <i data-lucide="brain"></i>
                            </div>

                            <strong>
                                No AI models available
                            </strong>

                            Add AI models first to create a comparison.

                        </div>

                    @endforelse

                </div>

            </section>

        </div>


        {{-- FOOTER --}}
        <div class="cmp-footer-note">

            <i data-lucide="info"></i>

            <span>
                Choose either tools or models. Select between 2 and 4 items for the comparison.
            </span>

        </div>

    </form>

</div>


@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {

    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }

    /* Search */
    document.querySelectorAll('.cmp-search input').forEach(function (input) {

        input.addEventListener('input', function () {

            const targetId = this.dataset.searchTarget;
            const target = document.getElementById(targetId);

            if (!target) return;

            const query = this.value.toLowerCase().trim();

            target.querySelectorAll('.cmp-item').forEach(function (item) {

                const text = item.dataset.search || '';

                item.style.display =
                    !query || text.includes(query)
                        ? ''
                        : 'none';

            });

        });

    });


    /* Selected counters */
    function updateCount(selector, target) {

        const count = document.querySelectorAll(
            selector + ':checked'
        ).length;

        const element = document.getElementById(target);

        if (element) {
            element.textContent = count;
        }
    }


    document.querySelectorAll('.tool-checkbox').forEach(function (checkbox) {

        checkbox.addEventListener('change', function () {
            updateCount('.tool-checkbox', 'tool-count');
        });

    });


    document.querySelectorAll('.model-checkbox').forEach(function (checkbox) {

        checkbox.addEventListener('change', function () {
            updateCount('.model-checkbox', 'model-count');
        });

    });

});
</script>
@endpush

@endsection