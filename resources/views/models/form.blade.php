@extends('layouts.admin')
@section('title', isset($model) ? 'Edit AI Model' : 'Add AI Model')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/pages/models.css') }}">
@endpush

@section('content')
@php
    $model ??= null;
    $field = fn ($key, $default = null) => old($key, $model->{$key} ?? $default);
    $selectedFeatureIds = collect(old('feature_ids', $model?->featureTerms?->pluck('id')->all() ?? []))->map(fn($id)=>(int)$id)->all();
    $selectedUseCaseIds = collect(old('use_case_ids', $model?->useCaseTerms?->pluck('id')->all() ?? []))->map(fn($id)=>(int)$id)->all();
    $selectedTagIds = collect(old('tag_ids', $model?->tagTerms?->pluck('id')->all() ?? []))->map(fn($id)=>(int)$id)->all();
@endphp

<form
    action="{{ $model ? route('admin.models.update', $model->id) : route('admin.models.store') }}"
    method="POST"
    class="model-editor"
>
    @csrf
    @if($model)
        @method('PUT')
    @endif

    <x-page-header
        title="{{ $model ? 'Edit AI Model' : 'Add AI Model' }}"
        subtitle="{{ $model ? 'Update model specifications, pricing and capability intelligence.' : 'Create a structured AI model profile for the intelligence catalog.' }}"
        :breadcrumb="['AI Management', 'AI Models', $model ? 'Edit' : 'Add']"
    >
        <x-slot:actions>
            <a href="{{ $model ? route('admin.models.show', $model->id) : route('admin.models.index') }}" class="btn btn-secondary btn-sm">
                Cancel
            </a>
            <button name="status" value="preview" type="submit" class="btn btn-secondary btn-sm">
                <i data-lucide="eye"></i>
                Save Preview
            </button>
            <button name="status" value="active" type="submit" class="btn btn-primary btn-sm">
                <i data-lucide="send"></i>
                {{ $model ? 'Update & Publish' : 'Publish Model' }}
            </button>
        </x-slot:actions>
    </x-page-header>

    @if($errors->any())
        <div class="alert alert-danger model-editor__errors">
            <i data-lucide="triangle-alert"></i>
            <div>
                <strong>Please review the highlighted model information.</strong>
                <ul>
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif

    <div class="model-editor__layout">
        <main class="model-editor__main">
            <section class="card model-panel">
                <div class="model-panel__header">
                    <div class="model-panel__icon"><i data-lucide="brain-circuit"></i></div>
                    <div>
                        <span class="eyebrow">IDENTITY</span>
                        <h2>Model profile</h2>
                        <p>Define the model, ownership and release identity.</p>
                    </div>
                </div>

                <div class="model-form-grid model-form-grid--2">
                    <div class="form-field model-form-field--wide">
                        <label for="name">Model name <span class="required">*</span></label>
                        <input id="name" class="input" name="name" required value="{{ $field('name') }}" placeholder="e.g. Claude Sonnet, GPT, Gemini Pro">
                        @error('name')<small class="field-error">{{ $message }}</small>@enderror
                    </div>

                    <div class="form-field">
                        <label for="company_id">Company</label>
                        <select class="select" id="company_id" name="company_id">
                            <option value="">Select company...</option>
                            @foreach($companies as $company)
                                <option value="{{ $company->id }}" @selected((string) $field('company_id') === (string) $company->id)>
                                    {{ $company->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('company_id')<small class="field-error">{{ $message }}</small>@enderror
                    </div>

                    <div class="form-field">
                        <label for="tool_id">Linked AI tool</label>
                        <select class="select" id="tool_id" name="tool_id">
                            <option value="">No linked tool</option>
                            @foreach($tools as $tool)
                                <option
                                    value="{{ $tool->id }}"
                                    data-company="{{ $tool->company_id }}"
                                    @selected((string) $field('tool_id') === (string) $tool->id)
                                >
                                    {{ $tool->name }}
                                </option>
                            @endforeach
                        </select>
                        <small class="text-sub" id="toolFilterHint">Tools are filtered to the selected company.</small>
                        @error('tool_id')<small class="field-error">{{ $message }}</small>@enderror
                    </div>

                    <div class="form-field">
                        <label for="version">Version</label>
                        <input id="version" class="input" name="version" value="{{ $field('version') }}" placeholder="e.g. 4.1, 2.5 Pro, 2026-08">
                        @error('version')<small class="field-error">{{ $message }}</small>@enderror
                    </div>

                    <div class="form-field">
                        <label for="release_date">Release date</label>
                        <input
                            id="release_date"
                            class="input"
                            type="date"
                            name="release_date"
                            value="{{ $field('release_date') ? \Illuminate\Support\Carbon::parse($field('release_date'))->format('Y-m-d') : '' }}"
                        >
                        @error('release_date')<small class="field-error">{{ $message }}</small>@enderror
                    </div>

                    <div class="form-field model-form-field--wide">
                        <label for="context_window">Context window</label>
                        <div class="model-input-with-icon">
                            <i data-lucide="panel-top"></i>
                            <input id="context_window" class="input" name="context_window" value="{{ $field('context_window') }}" placeholder="e.g. 128K, 200K, 1M tokens">
                        </div>
                        <small class="text-sub">Store the published context capacity as a readable value.</small>
                        @error('context_window')<small class="field-error">{{ $message }}</small>@enderror
                    </div>
                </div>
            </section>

            <section class="card model-panel">
                <div class="model-panel__header">
                    <div class="model-panel__icon"><i data-lucide="sparkles"></i></div>
                    <div>
                        <span class="eyebrow">NORMALIZED CAPABILITIES</span>
                        <h2>Features & capabilities</h2>
                        <p>Models and tools now share the same controlled capability vocabulary.</p>
                    </div>
                </div>

                @foreach($features->groupBy(fn($feature) => $feature->group ?: 'Other') as $groupName => $groupFeatures)
                    <div class="model-notes-field"><strong>{{ $groupName }}</strong></div>
                    <div class="model-capability-picker">
                        @foreach($groupFeatures as $feature)
                            <label class="model-capability-option">
                                <input type="checkbox" name="feature_ids[]" value="{{ $feature->id }}" @checked(in_array((int)$feature->id, $selectedFeatureIds, true))>
                                <span class="model-capability-option__visual">
                                    <i data-lucide="{{ $feature->icon ?: 'sparkles' }}"></i>
                                    <span><strong>{{ $feature->name }}</strong><small>{{ $feature->description ?: 'Normalized AI capability' }}</small></span>
                                    <i class="model-capability-option__check" data-lucide="check"></i>
                                </span>
                            </label>
                        @endforeach
                    </div>
                @endforeach

                <div class="form-field model-notes-field">
                    <label for="capability_notes">Capability notes</label>
                    <textarea id="capability_notes" class="input" rows="5" name="capability_notes" placeholder="Add important limitations, modality details, API behavior or operational notes...">{{ $field('capability_notes') }}</textarea>
                    @error('capability_notes')<small class="field-error">{{ $message }}</small>@enderror
                </div>
            </section>

            <section class="card model-panel">
                <div class="model-panel__header">
                    <div class="model-panel__icon"><i data-lucide="target"></i></div>
                    <div><span class="eyebrow">DISCOVERY</span><h2>Use cases</h2><p>Connect the model to the tasks it can help users accomplish.</p></div>
                </div>
                <div class="model-capability-picker">
                    @foreach($useCases as $useCase)
                        <label class="model-capability-option">
                            <input type="checkbox" name="use_case_ids[]" value="{{ $useCase->id }}" @checked(in_array((int)$useCase->id, $selectedUseCaseIds, true))>
                            <span class="model-capability-option__visual"><i data-lucide="{{ $useCase->icon ?: 'target' }}"></i><span><strong>{{ $useCase->name }}</strong><small>{{ $useCase->short_description }}</small></span><i class="model-capability-option__check" data-lucide="check"></i></span>
                        </label>
                    @endforeach
                </div>
            </section>

            <section class="card model-panel">
                <div class="model-panel__header">
                    <div class="model-panel__icon"><i data-lucide="tags"></i></div>
                    <div><span class="eyebrow">DISCOVERY LABELS</span><h2>Tags</h2><p>Add only controlled discovery labels; pricing and capability data stay in their own fields.</p></div>
                </div>
                <div class="model-capability-picker">
                    @foreach($tags as $tag)
                        <label class="model-capability-option">
                            <input type="checkbox" name="tag_ids[]" value="{{ $tag->id }}" @checked(in_array((int)$tag->id, $selectedTagIds, true))>
                            <span class="model-capability-option__visual"><i data-lucide="tag"></i><span><strong>{{ $tag->name }}</strong><small>{{ $tag->description }}</small></span><i class="model-capability-option__check" data-lucide="check"></i></span>
                        </label>
                    @endforeach
                </div>
            </section>
        </main>

        <aside class="model-editor__aside">
            <section class="card model-panel model-panel--compact">
                <div class="model-panel__header">
                    <div class="model-panel__icon"><i data-lucide="badge-dollar-sign"></i></div>
                    <div>
                        <span class="eyebrow">COMMERCIAL</span>
                        <h2>Pricing</h2>
                    </div>
                </div>

                <div class="model-price-editor">
                    <div class="form-field">
                        <label for="input_price_per_million">Input / 1M tokens</label>
                        <div class="model-money-input">
                            <span>$</span>
                            <input id="input_price_per_million" class="input" type="number" step="0.01" min="0" name="input_price_per_million" value="{{ $field('input_price_per_million') }}" placeholder="0.00">
                        </div>
                        @error('input_price_per_million')<small class="field-error">{{ $message }}</small>@enderror
                    </div>

                    <div class="form-field">
                        <label for="output_price_per_million">Output / 1M tokens</label>
                        <div class="model-money-input">
                            <span>$</span>
                            <input id="output_price_per_million" class="input" type="number" step="0.01" min="0" name="output_price_per_million" value="{{ $field('output_price_per_million') }}" placeholder="0.00">
                        </div>
                        @error('output_price_per_million')<small class="field-error">{{ $message }}</small>@enderror
                    </div>
                </div>
            </section>

            <section class="card model-panel model-panel--compact">
                <div class="model-panel__header">
                    <div class="model-panel__icon"><i data-lucide="gauge"></i></div>
                    <div>
                        <span class="eyebrow">EVALUATION</span>
                        <h2>Benchmark score</h2>
                    </div>
                </div>

                <div class="form-field">
                    <label for="benchmark_score">Overall score</label>
                    <div class="model-score-input">
                        <input id="benchmark_score" class="input" type="number" step="0.1" min="0" max="100" name="benchmark_score" value="{{ $field('benchmark_score') }}" placeholder="0.0">
                        <span>/100</span>
                    </div>
                    <small class="text-sub">Use the normalized overall score currently supported by the model controller.</small>
                    @error('benchmark_score')<small class="field-error">{{ $message }}</small>@enderror
                </div>
            </section>

            <section class="card model-panel model-panel--compact model-integrity">
                <div class="model-panel__header">
                    <div class="model-panel__icon"><i data-lucide="shield-check"></i></div>
                    <div>
                        <span class="eyebrow">INTEGRITY</span>
                        <h2>Data rules</h2>
                    </div>
                </div>

                <ul class="model-integrity__list">
                    <li><i data-lucide="check"></i><span>Slug is generated automatically from model name and version.</span></li>
                    <li><i data-lucide="check"></i><span>Duplicate slugs are resolved automatically.</span></li>
                    <li><i data-lucide="check"></i><span>A linked tool must belong to the selected company.</span></li>
                    <li><i data-lucide="check"></i><span>If company is empty, the selected tool can supply it automatically.</span></li>
                </ul>
            </section>

            @if($model)
                <section class="card model-panel model-panel--compact model-record-meta">
                    <span class="eyebrow">RECORD</span>
                    <div class="model-record-meta__row"><span>Model ID</span><strong class="mono">#{{ $model->id }}</strong></div>
                    <div class="model-record-meta__row"><span>Slug</span><strong class="mono">{{ $model->slug }}</strong></div>
                    <div class="model-record-meta__row"><span>Current status</span><strong>{{ ucfirst($model->status) }}</strong></div>
                </section>
            @endif
        </aside>
    </div>
</form>
@endsection

@push('scripts')
<script>
(function () {
    const company = document.getElementById('company_id');
    const tool = document.getElementById('tool_id');
    if (!company || !tool) return;

    function filterTools() {
        const companyId = company.value;

        [...tool.options].forEach((option, index) => {
            if (index === 0) {
                option.hidden = false;
                return;
            }

            const optionCompany = option.dataset.company || '';
            option.hidden = Boolean(companyId) && Boolean(optionCompany) && optionCompany !== companyId;
        });

        if (tool.selectedOptions[0]?.hidden) {
            tool.value = '';
        }
    }

    company.addEventListener('change', filterTools);
    filterTools();
})();
</script>
@endpush