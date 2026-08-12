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
        --cp-border: var(--border-soft, rgba(148,163,184,.14));
        --cp-text: var(--text, #eef2ff);
        --cp-muted: var(--muted, #8d98ad);
        --cp-blue: #6d8cff;
        --cp-cyan: #22d3ee;
        --cp-green: #32d583;
    }

    .comparison-page__hero {
        position: relative;
        overflow: hidden;
        display: flex;
        align-items: flex-end;
        justify-content: space-between;
        gap: 20px;
        margin-bottom: 15px;
        padding: 20px 21px;
        border: 1px solid var(--cp-border);
        border-radius: 17px;
        background:
            radial-gradient(circle at 90% 5%, rgba(109,140,255,.17), transparent 28%),
            radial-gradient(circle at 62% 120%, rgba(34,211,238,.06), transparent 28%),
            linear-gradient(145deg, rgba(255,255,255,.045), rgba(255,255,255,.012));
        box-shadow: 0 16px 42px rgba(0,0,0,.07);
    }

    .comparison-page__hero-copy {
        position: relative;
        z-index: 1;
    }

    .comparison-page__eyebrow {
        display: flex;
        align-items: center;
        gap: 7px;
        margin-bottom: 7px;
        color: var(--cp-cyan);
        font-size: 8.5px;
        font-weight: 800;
        letter-spacing: .13em;
        text-transform: uppercase;
    }

    .comparison-page__live {
        width: 6px;
        height: 6px;
        border-radius: 50%;
        background: var(--cp-green);
        box-shadow: 0 0 0 4px rgba(50,213,131,.10);
    }

    .comparison-page__title {
        margin: 0;
        color: var(--cp-text);
        font-size: clamp(22px, 3vw, 29px);
        line-height: 1.15;
        letter-spacing: -.035em;
        font-weight: 800;
    }

    .comparison-page__subtitle {
        margin: 7px 0 0;
        color: var(--cp-muted);
        font-size: 9px;
    }

    .comparison-page__hero-actions {
        position: relative;
        z-index: 1;
        display: flex;
        gap: 7px;
    }

    .comparison-page__hero-actions .btn {
        min-height: 37px;
        border-radius: 9px;
    }

    .comparison-page__notice {
        display: flex;
        align-items: flex-start;
        gap: 9px;
        margin-bottom: 14px;
        padding: 11px 13px;
        border: 1px solid rgba(255,90,90,.18);
        border-radius: 10px;
        color: #ff9d9d;
        background: rgba(255,90,90,.055);
        font-size: 9px;
    }

    .comparison-page__notice svg {
        width: 14px;
        height: 14px;
        flex: 0 0 14px;
        margin-top: 1px;
    }

    .comparison-page__title-card {
        position: relative;
        margin-bottom: 14px;
        padding: 17px;
        border: 1px solid var(--cp-border);
        border-radius: 14px;
        background: linear-gradient(145deg, rgba(255,255,255,.035), rgba(255,255,255,.012));
        box-shadow: 0 12px 34px rgba(0,0,0,.055);
    }

    .comparison-page__section-head {
        display: flex;
        align-items: center;
        gap: 8px;
        margin-bottom: 11px;
    }

    .comparison-page__section-icon {
        width: 28px;
        height: 28px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border: 1px solid rgba(109,140,255,.16);
        border-radius: 8px;
        color: #9eafff;
        background: rgba(109,140,255,.07);
    }

    .comparison-page__section-icon svg {
        width: 14px;
        height: 14px;
    }

    .comparison-page__section-title {
        color: var(--cp-text);
        font-size: 10.5px;
        font-weight: 800;
    }

    .comparison-page__section-subtitle {
        margin-top: 2px;
        color: var(--cp-muted);
        font-size: 7.5px;
    }

    .comparison-page__title-input {
        width: 100%;
        min-height: 45px;
        padding: 0 13px;
        border: 1px solid var(--cp-border);
        border-radius: 10px;
        outline: none;
        color: var(--cp-text);
        background: rgba(255,255,255,.025);
        font-size: 11px;
        transition: .16s ease;
    }

    .comparison-page__title-input:focus {
        border-color: rgba(109,140,255,.45);
        box-shadow: 0 0 0 3px rgba(109,140,255,.08);
    }

    .comparison-page__title-input::placeholder {
        color: #68748a;
    }

    .comparison-page__columns {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 14px;
    }

    .comparison-page__selector {
        overflow: hidden;
        border: 1px solid var(--cp-border);
        border-radius: 14px;
        background: linear-gradient(145deg, rgba(255,255,255,.035), rgba(255,255,255,.012));
        box-shadow: 0 12px 34px rgba(0,0,0,.055);
    }

    .comparison-page__selector-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        padding: 15px 16px;
        border-bottom: 1px solid var(--cp-border);
    }

    .comparison-page__selector-title {
        display: flex;
        align-items: center;
        gap: 9px;
        min-width: 0;
    }

    .comparison-page__option {
        width: 29px;
        height: 29px;
        display: flex;
        align-items: center;
        justify-content: center;
        flex: 0 0 29px;
        border-radius: 8px;
        color: #aab8ff;
        background: rgba(109,140,255,.09);
        border: 1px solid rgba(109,140,255,.14);
        font-size: 9px;
        font-weight: 800;
    }

    .comparison-page__option--model {
        color: #91e9f4;
        background: rgba(34,211,238,.07);
        border-color: rgba(34,211,238,.14);
    }

    .comparison-page__selector-name {
        color: var(--cp-text);
        font-size: 10px;
        font-weight: 800;
    }

    .comparison-page__selector-desc {
        margin-top: 2px;
        color: var(--cp-muted);
        font-size: 7.5px;
    }

    .comparison-page__count {
        padding: 5px 7px;
        border: 1px solid var(--cp-border);
        border-radius: 6px;
        color: var(--cp-muted);
        background: rgba(255,255,255,.018);
        font-size: 7px;
        font-weight: 700;
        white-space: nowrap;
    }

    .comparison-page__list {
        max-height: 340px;
        overflow-y: auto;
        padding: 8px;
        scrollbar-width: thin;
    }

    .comparison-page__item {
        position: relative;
        display: flex;
        align-items: center;
        gap: 10px;
        min-height: 48px;
        padding: 8px 10px;
        border: 1px solid transparent;
        border-radius: 9px;
        cursor: pointer;
        transition: .16s ease;
    }

    .comparison-page__item:hover {
        border-color: var(--cp-border);
        background: rgba(255,255,255,.025);
    }

    .comparison-page__item:has(input:checked) {
        border-color: rgba(109,140,255,.20);
        background: rgba(109,140,255,.065);
    }

    .comparison-page__item input {
        width: 15px;
        height: 15px;
        flex: 0 0 15px;
        accent-color: var(--cp-blue);
        cursor: pointer;
    }

    .comparison-page__item-info {
        min-width: 0;
        flex: 1;
    }

    .comparison-page__item-name {
        display: block;
        overflow: hidden;
        color: var(--cp-text);
        font-size: 9.5px;
        font-weight: 700;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .comparison-page__item-company {
        display: block;
        margin-top: 3px;
        overflow: hidden;
        color: var(--cp-muted);
        font-size: 7.5px;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .comparison-page__selected {
        display: none;
        width: 18px;
        height: 18px;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
        color: #fff;
        background: var(--cp-blue);
    }

    .comparison-page__item:has(input:checked) .comparison-page__selected {
        display: inline-flex;
    }

    .comparison-page__selected svg {
        width: 10px;
        height: 10px;
    }

    .comparison-page__footer-note {
        display: flex;
        align-items: center;
        gap: 7px;
        margin-top: 14px;
        padding: 10px 12px;
        border: 1px dashed var(--cp-border);
        border-radius: 9px;
        color: var(--cp-muted);
        background: rgba(255,255,255,.012);
        font-size: 8px;
    }

    .comparison-page__footer-note svg {
        width: 13px;
        height: 13px;
        color: #7f91b7;
    }

    @media (max-width: 800px) {
        .comparison-page__hero {
            align-items: flex-start;
            flex-direction: column;
        }

        .comparison-page__hero-actions {
            width: 100%;
        }

        .comparison-page__hero-actions .btn {
            flex: 1;
        }

        .comparison-page__columns {
            grid-template-columns: 1fr;
        }
    }

    @media (max-width: 500px) {
        .comparison-page__hero {
            padding: 17px;
        }

        .comparison-page__hero-actions {
            flex-direction: column;
        }

        .comparison-page__hero-actions .btn {
            width: 100%;
        }
    }
</style>

<div class="comparison-page">

    {{-- HERO --}}
    <section class="comparison-page__hero">

        <div class="comparison-page__hero-copy">

            <div class="comparison-page__eyebrow">
                <span class="comparison-page__live"></span>
                Comparison & Benchmarks · Intelligence
            </div>

            <h1 class="comparison-page__title">
                {{ $comparison ? 'Edit Comparison' : 'New Comparison' }}
            </h1>

            <p class="comparison-page__subtitle">
                Compare 2–4 AI tools or 2–4 AI models side by side
            </p>

        </div>

        <div class="comparison-page__hero-actions">

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
                <i data-lucide="check"></i>
                Publish
            </button>

        </div>

    </section>

    @if ($errors->any())

        <div class="comparison-page__notice">

            <i data-lucide="circle-alert"></i>

            <div>
                <strong style="display:block; margin-bottom:4px;">
                    Please check the following fields
                </strong>

                <ul style="margin:0; padding-left:16px;">
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
        <div class="comparison-page__title-card">

            <div class="comparison-page__section-head">

                <span class="comparison-page__section-icon">
                    <i data-lucide="heading"></i>
                </span>

                <div>
                    <div class="comparison-page__section-title">
                        Comparison Title
                    </div>

                    <div class="comparison-page__section-subtitle">
                        Give this comparison a clear and searchable title
                    </div>
                </div>

            </div>

            <input
                class="comparison-page__title-input"
                name="title"
                value="{{ old('title', $comparison->title ?? '') }}"
                placeholder="e.g. ChatGPT vs Claude"
                required
            >

        </div>

        {{-- SELECTORS --}}
        <div class="comparison-page__columns">

            {{-- TOOLS --}}
            <section class="comparison-page__selector">

                <div class="comparison-page__selector-head">

                    <div class="comparison-page__selector-title">

                        <span class="comparison-page__option">
                            A
                        </span>

                        <div>

                            <div class="comparison-page__selector-name">
                                Compare AI Tools
                            </div>

                            <div class="comparison-page__selector-desc">
                                Select 2–4 tools
                            </div>

                        </div>

                    </div>

                    <span class="comparison-page__count">
                        {{ count($selectedToolIds) }} selected
                    </span>

                </div>

                <div class="comparison-page__list">

                    @forelse ($tools as $tool)

                        <label class="comparison-page__item">

                            <input
                                type="checkbox"
                                name="tool_ids[]"
                                value="{{ $tool->id }}"
                                {{ in_array($tool->id, $selectedToolIds) ? 'checked' : '' }}
                            >

                            <div class="comparison-page__item-info">

                                <span class="comparison-page__item-name">
                                    {{ $tool->name }}
                                </span>

                                <span class="comparison-page__item-company">
                                    {{ $tool->company->name ?? 'No company assigned' }}
                                </span>

                            </div>

                            <span class="comparison-page__selected">
                                <i data-lucide="check"></i>
                            </span>

                        </label>

                    @empty

                        <div class="text-sub" style="padding:20px; text-align:center; font-size:9px;">
                            No AI tools available.
                        </div>

                    @endforelse

                </div>

            </section>

            {{-- MODELS --}}
            <section class="comparison-page__selector">

                <div class="comparison-page__selector-head">

                    <div class="comparison-page__selector-title">

                        <span class="comparison-page__option comparison-page__option--model">
                            B
                        </span>

                        <div>

                            <div class="comparison-page__selector-name">
                                Compare AI Models
                            </div>

                            <div class="comparison-page__selector-desc">
                                Select 2–4 models
                            </div>

                        </div>

                    </div>

                    <span class="comparison-page__count">
                        {{ count($selectedModelIds) }} selected
                    </span>

                </div>

                <div class="comparison-page__list">

                    @forelse ($models as $model)

                        <label class="comparison-page__item">

                            <input
                                type="checkbox"
                                name="model_ids[]"
                                value="{{ $model->id }}"
                                {{ in_array($model->id, $selectedModelIds) ? 'checked' : '' }}
                            >

                            <div class="comparison-page__item-info">

                                <span class="comparison-page__item-name">
                                    {{ $model->name }}
                                </span>

                                <span class="comparison-page__item-company">
                                    {{ $model->company->name ?? 'No company assigned' }}
                                </span>

                            </div>

                            <span class="comparison-page__selected">
                                <i data-lucide="check"></i>
                            </span>

                        </label>

                    @empty

                        <div class="text-sub" style="padding:20px; text-align:center; font-size:9px;">
                            No AI models available.
                        </div>

                    @endforelse

                </div>

            </section>

        </div>

        <div class="comparison-page__footer-note">
            <i data-lucide="info"></i>
            <span>
                Choose either tools or models. Select between 2 and 4 items for the comparison.
            </span>
        </div>

    </form>

</div>

@endsection