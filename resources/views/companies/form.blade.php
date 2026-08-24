@extends('layouts.admin')

@section('title', isset($company) ? 'Edit Company' : 'Add Company')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/pages/companies.css') }}">
@endpush

@section('content')
@php
    $company ??= null;
    $value = fn($key, $default = null) => old($key, $company?->{$key} ?? $default);
@endphp

<div class="companies-page company-editor">
    <x-page-header
        :title="$company ? 'Edit Company' : 'Add Company'"
        :subtitle="$company ? 'Update organization identity and operational profile.' : 'Create a structured company profile for the AI intelligence registry.'"
        :breadcrumb="['AI Management', 'AI Companies', $company ? 'Edit' : 'Add']"
    >
        <x-slot:actions>
            <a href="{{ $company ? route('admin.companies.show', $company->id) : route('admin.companies.index') }}" class="btn btn-secondary">
                <i data-lucide="arrow-left"></i>
                Cancel
            </a>
        </x-slot:actions>
    </x-page-header>

    @if($errors->any())
        <div class="alert alert-danger company-editor__errors">
            <i data-lucide="circle-alert"></i>
            <div>
                <strong>Please review the highlighted information.</strong>
                <ul>
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif

    <form
        action="{{ $company ? route('admin.companies.update', $company->id) : route('admin.companies.store') }}"
        method="POST"
        enctype="multipart/form-data"
        class="company-editor__form"
    >
        @csrf
        @if($company)
            @method('PUT')
        @endif

        <div class="company-editor__layout">
            <main class="company-editor__main">
                <section class="card company-editor__section">
                    <div class="company-editor__section-head">
                        <span class="company-editor__section-icon"><i data-lucide="building-2"></i></span>
                        <div>
                            <span class="companies-eyebrow">Identity</span>
                            <h2>Company profile</h2>
                            <p>Core organization information used throughout the AI Orbit.</p>
                        </div>
                    </div>

                    <div class="company-editor__fields">
                        <label class="company-field">
                            <span>Company name <b>*</b></span>
                            <input class="input" name="name" value="{{ $value('name') }}" placeholder="e.g. Anthropic" required>
                            <small>Use the official organization or brand name.</small>
                        </label>

                        <label class="company-field">
                            <span>Website</span>
                            <div class="company-input-icon">
                                <i data-lucide="globe-2"></i>
                                <input class="input" type="url" name="website" value="{{ $value('website') }}" placeholder="https://example.com">
                            </div>
                            <small>Public company website. Must include http:// or https://.</small>
                        </label>

                        <label class="company-field">
                            <span>Founded year</span>
                            <input
                                class="input"
                                type="number"
                                name="founded_year"
                                min="1800"
                                max="{{ date('Y') + 1 }}"
                                value="{{ $value('founded_year') }}"
                                placeholder="{{ date('Y') }}"
                            >
                            <small>Optional historical reference for the company profile.</small>
                        </label>

                        <label class="company-field">
                            <span>Operational status <b>*</b></span>
                            <select class="select" name="status" required>
                                @foreach(['active' => 'Active', 'acquired' => 'Acquired', 'inactive' => 'Inactive'] as $status => $label)
                                    <option value="{{ $status }}" @selected($value('status', 'active') === $status)>{{ $label }}</option>
                                @endforeach
                            </select>
                            <small>Describes the current organizational state.</small>
                        </label>

                        <label class="company-field company-field--full">
                            <span>Company overview</span>
                            <textarea
                                class="textarea"
                                name="description"
                                rows="8"
                                placeholder="Summarize the company, its AI focus and product portfolio..."
                            >{{ $value('description') }}</textarea>
                            <small>Keep this concise and useful for internal intelligence and company detail views.</small>
                        </label>
                    </div>
                </section>
            </main>

            <aside class="company-editor__aside">
                <section class="card company-editor__media">
                    <div class="company-editor__section-head company-editor__section-head--compact">
                        <span class="company-editor__section-icon"><i data-lucide="image"></i></span>
                        <div>
                            <span class="companies-eyebrow">Brand</span>
                            <h2>Company logo</h2>
                        </div>
                    </div>

                    <div class="company-logo-upload">
                        <div class="company-logo-upload__preview" id="companyLogoPreview">
                            @if($company?->logo_path)
                                <img src="{{ $company->logo_url }}" alt="{{ $company->name }} logo">
                            @else
                                <span class="company-logo-upload__placeholder">
                                    <i data-lucide="image-plus"></i>
                                    <small>No logo uploaded</small>
                                </span>
                            @endif
                        </div>

                        <label class="btn btn-secondary company-logo-upload__button">
                            <i data-lucide="upload"></i>
                            Choose logo
                            <input id="companyLogoInput" type="file" name="logo" accept="image/*" hidden>
                        </label>

                        <p>Square PNG, JPG or WebP works best. Maximum file size: 2 MB.</p>
                    </div>
                </section>

                <section class="card company-editor__snapshot">
                    <span class="companies-eyebrow">Record snapshot</span>
                    <dl>
                        <div>
                            <dt>Mode</dt>
                            <dd>{{ $company ? 'Editing' : 'Creating' }}</dd>
                        </div>
                        <div>
                            <dt>Status</dt>
                            <dd>{{ ucfirst($value('status', 'active')) }}</dd>
                        </div>
                        @if($company)
                            <div>
                                <dt>Slug</dt>
                                <dd class="mono">{{ $company->slug }}</dd>
                            </div>
                        @endif
                    </dl>

                    <div class="company-editor__note">
                        <i data-lucide="shield-check"></i>
                        <span>The slug is generated automatically from the company name. Replacing the logo removes the previous stored logo.</span>
                    </div>
                </section>
            </aside>
        </div>

        <div class="company-editor__actions">
            <a href="{{ $company ? route('admin.companies.show', $company->id) : route('admin.companies.index') }}" class="btn btn-secondary">
                Cancel
            </a>
            <button class="btn btn-primary" type="submit">
                <i data-lucide="{{ $company ? 'save' : 'plus' }}"></i>
                {{ $company ? 'Save changes' : 'Create company' }}
            </button>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const input = document.getElementById('companyLogoInput');
    const preview = document.getElementById('companyLogoPreview');

    if (!input || !preview) return;

    input.addEventListener('change', function () {
        const file = this.files && this.files[0];
        if (!file || !file.type.startsWith('image/')) return;

        const reader = new FileReader();
        reader.onload = function (event) {
            preview.innerHTML = '';
            const image = document.createElement('img');
            image.src = event.target.result;
            image.alt = 'Selected company logo preview';
            preview.appendChild(image);
        };
        reader.readAsDataURL(file);
    });
});
</script>
@endpush