@extends('layouts.admin')
@section('title', isset($company) ? 'Edit Company' : 'Add Company')

@php
    use Illuminate\Support\Facades\Storage;
@endphp

@section('content')

@php
    $company ??= null;
    $old = fn ($key, $default = null) => old($key, $company->{$key} ?? $default);
@endphp

<style>
    /* AI HUB — ADVANCED COMPANY EDITOR
       UI-only enhancement. Existing form action, fields,
       routes, Storage logic and backend behaviour preserved. */

    .company-editor {
        --ce-border: var(--border-soft, rgba(148,163,184,.14));
        --ce-text: var(--text, #eef2ff);
        --ce-muted: var(--muted, #8d98ad);
        --ce-blue: #6d8cff;
        --ce-cyan: #22d3ee;
        --ce-green: #32d583;
        --ce-orange: #f5a524;
        max-width: 1050px;
    }

    .company-editor__hero {
        position: relative;
        overflow: hidden;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 22px;
        min-height: 125px;
        margin-bottom: 16px;
        padding: 20px 22px;
        border: 1px solid var(--ce-border);
        border-radius: 17px;
        background:
            radial-gradient(circle at 90% 5%, rgba(109,140,255,.18), transparent 28%),
            radial-gradient(circle at 60% 120%, rgba(34,211,238,.07), transparent 30%),
            linear-gradient(145deg, rgba(255,255,255,.045), rgba(255,255,255,.012));
        box-shadow: 0 16px 42px rgba(0,0,0,.08);
    }

    .company-editor__hero:after {
        content: "";
        position: absolute;
        width: 250px;
        height: 250px;
        right: -105px;
        top: -155px;
        border: 1px solid rgba(109,140,255,.12);
        border-radius: 50%;
        box-shadow: 0 0 0 30px rgba(109,140,255,.025), 0 0 0 60px rgba(109,140,255,.012);
        pointer-events: none;
    }

    .company-editor__hero-copy,
    .company-editor__hero-actions {
        position: relative;
        z-index: 1;
    }

    .company-editor__eyebrow {
        display: flex;
        align-items: center;
        gap: 7px;
        margin-bottom: 7px;
        color: var(--ce-cyan);
        font-size: 9px;
        font-weight: 800;
        letter-spacing: .13em;
        text-transform: uppercase;
    }

    .company-editor__dot {
        width: 6px;
        height: 6px;
        border-radius: 50%;
        background: var(--ce-green);
        box-shadow: 0 0 0 4px rgba(50,213,131,.10);
    }

    .company-editor__title {
        margin: 0;
        color: var(--ce-text);
        font-size: clamp(22px, 3vw, 29px);
        line-height: 1.15;
        letter-spacing: -.035em;
        font-weight: 800;
    }

    .company-editor__subtitle {
        margin: 7px 0 0;
        color: var(--ce-muted);
        font-size: 10px;
    }

    .company-editor__hero-actions {
        display: flex;
        align-items: center;
        flex-shrink: 0;
    }

    .company-editor__hero-actions .btn {
        min-height: 37px;
        border-radius: 9px;
    }

    .company-editor__error {
        display: flex;
        gap: 9px;
        align-items: flex-start;
        margin-bottom: 14px;
        padding: 12px 14px;
        border: 1px solid rgba(249,112,104,.20);
        border-radius: 10px;
        color: #ff9d97;
        background: rgba(249,112,104,.055);
        font-size: 10px;
        line-height: 1.5;
    }

    .company-editor__error > i {
        width: 15px;
        height: 15px;
        flex: 0 0 15px;
        margin-top: 1px;
    }

    .company-editor__error ul {
        margin: 0;
        padding-left: 16px;
    }

    .company-editor__layout {
        display: grid;
        grid-template-columns: minmax(0, 1fr) 285px;
        gap: 15px;
        align-items: start;
    }

    .company-editor__card {
        border: 1px solid var(--ce-border);
        border-radius: 14px;
        background: linear-gradient(145deg, rgba(255,255,255,.035), rgba(255,255,255,.012));
        box-shadow: 0 10px 30px rgba(0,0,0,.055);
    }

    .company-editor__card-head {
        display: flex;
        align-items: center;
        gap: 9px;
        padding: 14px 16px;
        border-bottom: 1px solid var(--ce-border);
    }

    .company-editor__icon {
        width: 28px;
        height: 28px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border: 1px solid rgba(109,140,255,.17);
        border-radius: 8px;
        color: #a7b6ff;
        background: rgba(109,140,255,.07);
    }

    .company-editor__icon svg {
        width: 14px;
        height: 14px;
    }

    .company-editor__section-title {
        color: var(--ce-text);
        font-size: 10.5px;
        font-weight: 800;
    }

    .company-editor__section-subtitle {
        margin-top: 2px;
        color: var(--ce-muted);
        font-size: 8px;
    }

    .company-editor__body {
        padding: 16px;
    }

    .company-editor__grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 13px;
    }

    .company-editor__field {
        min-width: 0;
    }

    .company-editor__field.full {
        grid-column: 1 / -1;
    }

    .company-editor__field label {
        display: flex;
        align-items: center;
        gap: 5px;
        margin-bottom: 6px;
        color: var(--ce-text);
        font-size: 9px;
        font-weight: 700;
    }

    .company-editor__required {
        color: #ff8f87;
    }

    .company-editor__input,
    .company-editor__select {
        width: 100%;
        min-height: 39px;
        box-sizing: border-box;
        border: 1px solid var(--ce-border);
        border-radius: 9px;
        color: var(--ce-text);
        background: rgba(255,255,255,.025);
        outline: none;
        font-size: 10px;
        transition: border-color .18s ease, box-shadow .18s ease, background .18s ease;
    }

    .company-editor__input {
        padding: 0 11px;
    }

    .company-editor__select {
        padding: 0 10px;
    }

    .company-editor__input::placeholder {
        color: #68748a;
    }

    .company-editor__input:focus,
    .company-editor__select:focus {
        border-color: rgba(109,140,255,.52);
        background: rgba(109,140,255,.025);
        box-shadow: 0 0 0 3px rgba(109,140,255,.07);
    }

    .company-editor__select {
        color-scheme: dark;
    }

    .company-editor__select option,
    .company-editor__select optgroup {
        color: #182033;
        background: #fff;
    }

    .company-editor__select option:checked,
    .company-editor__select option:hover {
        color: #fff;
        background: #536ff0;
    }

    .company-editor__textarea {
        width: 100%;
        min-height: 115px;
        resize: vertical;
        padding: 10px 11px;
    }

    .company-editor__field-help {
        margin-top: 5px;
        color: var(--ce-muted);
        font-size: 8px;
        line-height: 1.45;
    }

    .company-editor__sidebar {
        position: sticky;
        top: 16px;
    }

    .company-editor__logo-panel {
        padding: 16px;
    }

    .company-editor__logo-preview {
        position: relative;
        width: 92px;
        height: 92px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 2px auto 13px;
        overflow: hidden;
        border: 1px solid rgba(109,140,255,.20);
        border-radius: 19px;
        background:
            radial-gradient(circle at 25% 20%, rgba(109,140,255,.23), transparent 56%),
            linear-gradient(145deg, rgba(109,140,255,.10), rgba(34,211,238,.035));
        box-shadow: 0 12px 28px rgba(0,0,0,.12);
    }

    .company-editor__logo-preview img {
        width: 100%;
        height: 100%;
        display: block;
        object-fit: cover;
    }

    .company-editor__logo-placeholder {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 6px;
        color: #8fa0c4;
    }

    .company-editor__logo-placeholder svg {
        width: 23px;
        height: 23px;
    }

    .company-editor__logo-placeholder span {
        font-size: 7px;
        font-weight: 700;
        letter-spacing: .08em;
        text-transform: uppercase;
    }

    .company-editor__upload {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 7px;
        min-height: 36px;
        padding: 0 10px;
        border: 1px dashed rgba(109,140,255,.25);
        border-radius: 9px;
        color: #9eafff;
        background: rgba(109,140,255,.035);
        cursor: pointer;
        font-size: 9px;
        font-weight: 700;
        transition: border-color .18s ease, background .18s ease;
    }

    .company-editor__upload:hover {
        border-color: rgba(109,140,255,.50);
        background: rgba(109,140,255,.07);
    }

    .company-editor__upload svg {
        width: 14px;
        height: 14px;
    }

    .company-editor__upload input {
        display: none;
    }

    .company-editor__logo-note {
        margin-top: 7px;
        text-align: center;
        color: var(--ce-muted);
        font-size: 7.5px;
        line-height: 1.4;
    }

    .company-editor__status {
        margin-top: 12px;
        padding: 13px;
        border: 1px solid var(--ce-border);
        border-radius: 11px;
        background: rgba(255,255,255,.018);
    }

    .company-editor__status-title {
        margin-bottom: 9px;
        color: var(--ce-text);
        font-size: 9px;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: .07em;
    }

    .company-editor__status-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 8px;
        color: var(--ce-muted);
        font-size: 8.5px;
    }

    .company-editor__status-row + .company-editor__status-row {
        margin-top: 8px;
        padding-top: 8px;
        border-top: 1px solid var(--ce-border);
    }

    .company-editor__status-value {
        color: var(--ce-text);
        font-weight: 650;
    }

    .company-editor__bottom {
        display: flex;
        justify-content: flex-end;
        gap: 8px;
        margin-top: 13px;
    }

    .company-editor__bottom .btn {
        min-height: 36px;
        border-radius: 9px;
    }

    @media (max-width: 850px) {
        .company-editor__layout {
            grid-template-columns: 1fr;
        }

        .company-editor__sidebar {
            position: static;
        }
    }

    @media (max-width: 600px) {
        .company-editor__hero {
            align-items: flex-start;
            flex-direction: column;
        }

        .company-editor__hero-actions,
        .company-editor__hero-actions .btn {
            width: 100%;
        }

        .company-editor__grid {
            grid-template-columns: 1fr;
        }

        .company-editor__field.full {
            grid-column: auto;
        }

        .company-editor__bottom {
            flex-direction: column;
        }

        .company-editor__bottom .btn {
            width: 100%;
            justify-content: center;
        }
    }
</style>

<div class="company-editor">

    <form
        action="{{ $company ? route('admin.companies.update', $company->id) : route('admin.companies.store') }}"
        method="POST"
        enctype="multipart/form-data"
    >
        @csrf
        @if ($company) @method('PUT') @endif

        {{-- HERO --}}
        <section class="company-editor__hero">

            <div class="company-editor__hero-copy">
                <div class="company-editor__eyebrow">
                    <span class="company-editor__dot"></span>
                    AI Management · Company Registry
                </div>

                <h1 class="company-editor__title">
                    {{ $company ? 'Edit Company' : 'Add Company' }}
                </h1>

                <p class="company-editor__subtitle">
                    {{ $company ? 'Update the company profile and keep your AI intelligence registry accurate.' : 'Create a structured profile for an AI company in your intelligence registry.' }}
                </p>
            </div>

            <div class="company-editor__hero-actions">
                <button type="submit" class="btn btn-primary btn-sm">
                    <i data-lucide="check"></i>
                    Save Company
                </button>
            </div>

        </section>

        {{-- VALIDATION --}}
        @if ($errors->any())
            <div class="company-editor__error">
                <i data-lucide="circle-alert"></i>

                <div>
                    <strong>Please review the following:</strong>

                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        @endif

        <div class="company-editor__layout">

            {{-- MAIN FORM --}}
            <section class="company-editor__card">

                <div class="company-editor__card-head">
                    <span class="company-editor__icon">
                        <i data-lucide="building-2"></i>
                    </span>

                    <div>
                        <div class="company-editor__section-title">
                            Company Information
                        </div>

                        <div class="company-editor__section-subtitle">
                            Core identity, web presence and company overview
                        </div>
                    </div>
                </div>

                <div class="company-editor__body">

                    <div class="company-editor__grid">

                        {{-- NAME --}}
                        <div class="company-editor__field">

                            <label>
                                Company Name
                                <span class="company-editor__required">*</span>
                            </label>

                            <input
                                class="company-editor__input"
                                name="name"
                                value="{{ $old('name') }}"
                                placeholder="e.g. Anthropic"
                                required
                            >

                            <div class="company-editor__field-help">
                                Official company or organization name.
                            </div>

                        </div>

                        {{-- WEBSITE --}}
                        <div class="company-editor__field">

                            <label>Website</label>

                            <input
                                class="company-editor__input"
                                name="website"
                                value="{{ $old('website') }}"
                                placeholder="https://example.com"
                            >

                            <div class="company-editor__field-help">
                                Public company website.
                            </div>

                        </div>

                        {{-- FOUNDED --}}
                        <div class="company-editor__field">

                            <label>Founded Year</label>

                            <input
                                class="company-editor__input"
                                type="number"
                                name="founded_year"
                                value="{{ $old('founded_year') }}"
                                placeholder="e.g. 2021"
                            >

                            <div class="company-editor__field-help">
                                Year the company was founded.
                            </div>

                        </div>

                        {{-- STATUS --}}
                        <div class="company-editor__field">

                            <label>Status</label>

                            <select class="company-editor__select" name="status">

                                @foreach (['active' => 'Active', 'acquired' => 'Acquired', 'inactive' => 'Inactive'] as $value => $label)
                                    <option
                                        value="{{ $value }}"
                                        @selected($old('status', 'active') === $value)
                                    >
                                        {{ $label }}
                                    </option>
                                @endforeach

                            </select>

                            <div class="company-editor__field-help">
                                Current operational state of the company.
                            </div>

                        </div>

                        {{-- OVERVIEW --}}
                        <div class="company-editor__field full">

                            <label>Overview</label>

                            <textarea
                                class="company-editor__input company-editor__textarea"
                                name="description"
                                rows="4"
                                placeholder="Company description..."
                            >{{ $old('description') }}</textarea>

                            <div class="company-editor__field-help">
                                A concise overview used throughout the AI company profile.
                            </div>

                        </div>

                    </div>

                </div>

            </section>

            {{-- SIDEBAR --}}
            <aside class="company-editor__sidebar">

                <section class="company-editor__card">

                    <div class="company-editor__card-head">
                        <span class="company-editor__icon">
                            <i data-lucide="image"></i>
                        </span>

                        <div>
                            <div class="company-editor__section-title">
                                Company Logo
                            </div>

                            <div class="company-editor__section-subtitle">
                                Brand identity
                            </div>
                        </div>
                    </div>

                    <div class="company-editor__logo-panel">

                        <div class="company-editor__logo-preview">

                            @if ($company && $company->logo_path)
                                <img
                                    src="{{ Storage::url($company->logo_path) }}"
                                    alt="Current logo"
                                >
                            @else
                                <div class="company-editor__logo-placeholder">
                                    <i data-lucide="image-plus"></i>
                                    <span>No Logo</span>
                                </div>
                            @endif

                        </div>

                        <label class="company-editor__upload">

                            <i data-lucide="upload"></i>
                            Choose Logo

                            <input
                                type="file"
                                name="logo"
                                accept="image/*"
                            >

                        </label>

                        <div class="company-editor__logo-note">
                            Upload a square company logo for the best result.
                        </div>

                    </div>

                </section>

                <section class="company-editor__status">

                    <div class="company-editor__status-title">
                        Profile Snapshot
                    </div>

                    <div class="company-editor__status-row">
                        <span>Mode</span>
                        <span class="company-editor__status-value">
                            {{ $company ? 'Editing' : 'Creating' }}
                        </span>
                    </div>

                    <div class="company-editor__status-row">
                        <span>Company</span>
                        <span class="company-editor__status-value">
                            {{ $company ? 'Existing record' : 'New record' }}
                        </span>
                    </div>

                    <div class="company-editor__status-row">
                        <span>Status</span>
                        <span class="company-editor__status-value">
                            {{ ucfirst($old('status', 'active')) }}
                        </span>
                    </div>

                </section>

            </aside>

        </div>

        {{-- BOTTOM ACTION --}}
        <div class="company-editor__bottom">
            <button type="submit" class="btn btn-primary btn-sm">
                <i data-lucide="check"></i>
                {{ $company ? 'Update Company' : 'Create Company' }}
            </button>
        </div>

    </form>

</div>

@endsection