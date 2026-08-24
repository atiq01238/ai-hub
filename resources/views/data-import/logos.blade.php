@extends('layouts.admin')

@section('title', 'Logo & Media Review')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/pages/data-import.css') }}">
<style>
.logo-review-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(290px, 1fr));
    gap: 14px;
}
.logo-review-card {
    padding: 16px;
}
.logo-candidate {
    width: 76px;
    height: 76px;
    object-fit: contain;
    background: #fff;
    border: 1px solid var(--border, #e5e7eb);
    border-radius: 14px;
    padding: 6px;
    margin: 10px 0;
}
.logo-review-card small {
    display: block;
    color: var(--text-muted, #64748b);
    overflow-wrap: anywhere;
}
.logo-tabs {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
    margin-bottom: 16px;
}
.logo-source-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    margin: 4px 0 10px;
    padding: 5px 8px;
    border-radius: 999px;
    font-size: 12px;
    font-weight: 700;
    border: 1px solid var(--border, #263044);
}
.logo-source-badge.is-model {
    color: #86efac;
    background: rgba(34, 197, 94, .08);
}
.logo-source-badge.is-company {
    color: #93c5fd;
    background: rgba(59, 130, 246, .08);
}
.logo-source-badge.is-missing {
    color: #fca5a5;
    background: rgba(239, 68, 68, .08);
}
.logo-summary {
    margin-bottom: 16px;
    padding: 14px 16px;
}
.logo-summary__stats {
    display: flex;
    gap: 18px;
    flex-wrap: wrap;
    margin-bottom: 6px;
}
.model-family-meta {
    display: flex;
    gap: 6px;
    flex-wrap: wrap;
    margin: 8px 0;
}
.model-family-meta span {
    border: 1px solid var(--border, #263044);
    border-radius: 999px;
    padding: 4px 7px;
    font-size: 11px;
    color: var(--text-muted, #94a3b8);
}
.model-logo-upload {
    margin-top: 12px;
    padding-top: 12px;
    border-top: 1px solid var(--border, #263044);
}
.model-logo-upload input[type="file"] {
    width: 100%;
    font-size: 12px;
    margin-bottom: 9px;
}
.model-logo-actions {
    display: flex;
    gap: 7px;
    flex-wrap: wrap;
}
.model-logo-actions .btn {
    flex: 1 1 115px;
    justify-content: center;
}
.model-logo-remove {
    margin-top: 8px;
}
.model-logo-remove .btn {
    width: 100%;
    justify-content: center;
}
.logo-help {
    margin-top: 8px;
    font-size: 11px;
    line-height: 1.45;
}
@media (max-width: 640px) {
    .logo-review-grid {
        grid-template-columns: 1fr;
    }
}
</style>
@endpush

@section('content')
<div class="import-page">
    <x-page-header
        title="Logo & Media Review"
        subtitle="Review company and tool logo candidates, or upload official dedicated logos for AI model families."
        :breadcrumb="['AI Management', 'Data Import', 'Logos & Media']"
    >
        <x-slot:actions>
            <a href="{{ route('admin.data-import.index') }}" class="btn btn-secondary">
                Back to Import Center
            </a>
        </x-slot:actions>
    </x-page-header>

    <div class="logo-tabs">
        <a
            class="btn {{ $type === 'company' ? 'btn-primary' : 'btn-secondary' }}"
            href="{{ route('admin.data-import.logos.index', ['type' => 'company']) }}"
        >
            Companies missing · {{ $counts['company'] }}
        </a>

        <a
            class="btn {{ $type === 'tool' ? 'btn-primary' : 'btn-secondary' }}"
            href="{{ route('admin.data-import.logos.index', ['type' => 'tool']) }}"
        >
            Tools missing · {{ $counts['tool'] }}
        </a>

        <a
            class="btn {{ $type === 'model' ? 'btn-primary' : 'btn-secondary' }}"
            href="{{ route('admin.data-import.logos.index', ['type' => 'model']) }}"
        >
            Models · {{ $counts['model'] }}
        </a>
    </div>

    @if(session('error'))
        <div class="alert alert-danger">
            {{ session('error') }}
        </div>
    @endif

    @if(session('status'))
        <div class="alert alert-success">
            {{ session('status') }}
        </div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger">
            <strong>Logo could not be saved.</strong>
            @foreach($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
    @endif

    @if($type === 'model')
        <section class="card logo-summary">
            <div class="logo-summary__stats">
                <strong>Dedicated model logos: {{ $counts['model_dedicated'] }}</strong>
                <strong>Company fallback: {{ $counts['model_fallback'] }}</strong>
                <strong>Unresolved: {{ $counts['model_unresolved'] }}</strong>
            </div>
            <small>
                Upload an official PNG/JPG/WebP logo once and apply it to a single model or its whole family.
                If no dedicated logo is saved, AI Orbit continues using the company logo automatically.
            </small>
        </section>
    @endif

    <section class="logo-review-grid">
        @forelse($items as $item)
            <article class="card logo-review-card">
                <span class="import-eyebrow">{{ strtoupper($type) }}</span>
                <h3>{{ $item->name }}</h3>

                @if($type === 'model')
                    @php
                        $family = $modelFamilyMeta[$item->id] ?? [
                            'name' => $item->name,
                            'count' => 1,
                            'dedicated' => $item->logo_path ? 1 : 0,
                        ];
                    @endphp

                    <small>{{ $item->company?->name ?: 'Independent' }}</small>

                    <div class="model-family-meta">
                        <span>Family: {{ $family['name'] }}</span>
                        <span>{{ $family['count'] }} model{{ $family['count'] === 1 ? '' : 's' }}</span>
                        <span>{{ $family['dedicated'] }}/{{ $family['count'] }} dedicated</span>
                    </div>

                    <img
                        class="logo-candidate js-model-logo-preview"
                        src="{{ $item->logo_url }}"
                        alt="{{ $item->name }} logo"
                    >

                    @if($item->logo_path)
                        <span class="logo-source-badge is-model">
                            <i data-lucide="badge-check"></i>
                            Dedicated model logo
                        </span>
                    @elseif($item->company?->logo_path)
                        <span class="logo-source-badge is-company">
                            <i data-lucide="building-2"></i>
                            Company fallback
                        </span>
                    @else
                        <span class="logo-source-badge is-missing">
                            <i data-lucide="circle-alert"></i>
                            No saved logo
                        </span>
                    @endif

                    <form
                        class="model-logo-upload js-model-logo-form"
                        method="POST"
                        action="{{ route('admin.data-import.logos.model-upload', $item->id) }}"
                        enctype="multipart/form-data"
                    >
                        @csrf

                        <input
                            class="js-model-logo-input"
                            type="file"
                            name="logo"
                            accept=".png,.jpg,.jpeg,.webp,image/png,image/jpeg,image/webp"
                            required
                        >

                        <div class="model-logo-actions">
                            <button class="btn btn-primary btn-sm" type="submit" name="scope" value="model">
                                <i data-lucide="save"></i>
                                Save this model
                            </button>

                            <button
                                class="btn btn-secondary btn-sm"
                                type="submit"
                                name="scope"
                                value="family"
                                onclick="return confirm('Apply this logo to all {{ $family['count'] }} models in the {{ addslashes($family['name']) }} family?')"
                            >
                                <i data-lucide="layers-3"></i>
                                Apply to family
                            </button>
                        </div>

                        <small class="logo-help">
                            Max 2 MB. Use an official transparent/square brand asset when available.
                        </small>
                    </form>

                    @if($item->logo_path)
                        <form
                            class="model-logo-remove"
                            method="POST"
                            action="{{ route('admin.data-import.logos.model-remove', $item->id) }}"
                        >
                            @csrf
                            @method('DELETE')

                            <div class="model-logo-actions">
                                <button
                                    class="btn btn-ghost btn-sm"
                                    type="submit"
                                    name="scope"
                                    value="model"
                                    onclick="return confirm('Remove the dedicated logo from {{ addslashes($item->name) }} and return to company fallback?')"
                                >
                                    Remove this logo
                                </button>

                                @if($family['count'] > 1)
                                    <button
                                        class="btn btn-ghost btn-sm"
                                        type="submit"
                                        name="scope"
                                        value="family"
                                        onclick="return confirm('Remove dedicated logos from the entire {{ addslashes($family['name']) }} family?')"
                                    >
                                        Clear family logos
                                    </button>
                                @endif
                            </div>
                        </form>
                    @endif
                @else
                    @php
                        $candidate = \App\Http\Controllers\Admin\LogoImportController::candidateUrl($item);
                        $saveRoute = $type === 'company'
                            ? route('admin.data-import.logos.company-save', $item->id)
                            : route('admin.data-import.logos.tool-save', $item->id);
                    @endphp

                    @if($candidate)
                        <img class="logo-candidate" src="{{ $candidate }}" alt="{{ $item->name }} logo candidate">
                        <small>{{ $item->website }}</small>

                        <form method="POST" action="{{ $saveRoute }}">
                            @csrf
                            <button
                                class="btn btn-primary"
                                type="submit"
                                onclick="return confirm('Save this reviewed logo candidate locally?')"
                            >
                                Save Logo
                            </button>
                        </form>
                    @else
                        <small>No website/domain is available to create a logo candidate.</small>
                    @endif
                @endif
            </article>
        @empty
            <article class="card logo-review-card">
                <h3>No records found</h3>
            </article>
        @endforelse
    </section>

    <div style="margin-top:18px">
        {{ $items->links() }}
    </div>
</div>
@endsection

@push('scripts')
<script>
document.querySelectorAll('.js-model-logo-input').forEach(function (input) {
    input.addEventListener('change', function () {
        const file = input.files && input.files[0];
        if (!file) return;

        const form = input.closest('.js-model-logo-form');
        const card = form ? form.closest('.logo-review-card') : null;
        const preview = card ? card.querySelector('.js-model-logo-preview') : null;
        if (!preview) return;

        const reader = new FileReader();
        reader.onload = function (event) {
            preview.src = event.target.result;
        };
        reader.readAsDataURL(file);
    });
});
</script>
@endpush
