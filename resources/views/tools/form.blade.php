@extends('layouts.admin')
@section('title', isset($tool) ? 'Edit AI Tool' : 'Add AI Tool')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/pages/tools.css') }}">
@endpush

@section('content')
@php
    $tool ??= null;
    $old = fn($key, $default = null) => old($key, $tool->{$key} ?? $default);
    $selectedFeatures = collect(old('feature_ids', $tool?->featureTerms?->pluck('id')->all() ?? []))->map(fn($id)=>(int)$id)->all();
    $selectedTags = collect(old('tag_ids', $tool?->tagTerms?->pluck('id')->all() ?? []))->map(fn($id)=>(int)$id)->all();
    $pricingModels = (array) $old('pricing_models', []);
    $platforms = (array) $old('platforms', []);
@endphp

<form action="{{ $tool ? route('admin.tools.update', $tool->id) : route('admin.tools.store') }}" method="POST" enctype="multipart/form-data" class="tool-editor">
    @csrf
    @if($tool) @method('PUT') @endif

    <x-page-header title="{{ $tool ? 'Edit AI Tool' : 'Add AI Tool' }}" subtitle="Maintain product identity, taxonomy, capabilities, media and publishing metadata." :breadcrumb="['AI Management','AI Tools',$tool ? 'Edit' : 'Add']">
        <x-slot:actions>
            <a href="{{ $tool ? route('admin.tools.show', $tool->id) : route('admin.tools.index') }}" class="btn btn-ghost btn-sm"><i data-lucide="x"></i> Cancel</a>
            <button class="btn btn-secondary btn-sm" type="submit" name="status" value="draft"><i data-lucide="file-text"></i> Save Draft</button>
            <button class="btn btn-primary btn-sm" type="submit" name="status" value="published"><i data-lucide="send"></i> Publish</button>
        </x-slot:actions>
    </x-page-header>

    @if($errors->any())
        <div class="alert alert-danger tools-alert tools-errors">
            <i data-lucide="circle-alert"></i>
            <div><strong>Please fix the following fields:</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
        </div>
    @endif

    <div class="tool-editor-grid">
        <main class="tool-editor-main">
            <section class="card tool-form-card">
                <div class="tool-form-heading"><span class="tool-form-icon"><i data-lucide="badge-info"></i></span><div><h3>Product Identity</h3><p>Core directory information shown across the admin and public product experience.</p></div></div>
                <div class="tool-form-grid tool-form-grid--2">
                    <div class="form-field"><label for="tool-name">Tool Name <span>*</span></label><input id="tool-name" class="input" name="name" required value="{{ $old('name') }}" placeholder="e.g. ChatGPT"></div>
                    <div class="form-field"><label for="tool-slug">Slug</label><input id="tool-slug" class="input" name="slug" value="{{ $old('slug') }}" placeholder="Auto-generated when empty"></div>
                    <div class="form-field"><label for="tool-company">Company</label><select id="tool-company" class="select" name="company_id"><option value="">Independent / No company</option>@foreach($companies as $company)<option value="{{ $company->id }}" @selected((string)$old('company_id') === (string)$company->id)>{{ $company->name }}</option>@endforeach</select></div>
                    <div class="form-field"><label for="tool-website">Website</label><input id="tool-website" class="input" type="url" name="website" value="{{ $old('website') }}" placeholder="https://example.com"></div>
                    <div class="form-field"><label for="tool-category">Category</label><select id="tool-category" class="select" name="category_id"><option value="">No category</option>@foreach($categories as $category)<option value="{{ $category->id }}" @selected((string)$old('category_id') === (string)$category->id)>{{ $category->name }}</option>@endforeach</select></div>
                    <div class="form-field"><label for="tool-subcategory">Subcategory</label><select id="tool-subcategory" class="select" name="subcategory_id"><option value="">No subcategory</option>@foreach($subcategories as $subcategory)<option value="{{ $subcategory->id }}" @selected((string)$old('subcategory_id') === (string)$subcategory->id)>{{ $subcategory->name }}</option>@endforeach</select></div>
                    <div class="form-field"><label for="tool-launch">Launch Date</label><input id="tool-launch" class="input" type="date" name="launch_date" value="{{ $old('launch_date') ? \Illuminate\Support\Carbon::parse($old('launch_date'))->format('Y-m-d') : '' }}"></div>
                    <div class="form-field tool-form-grid__wide"><label for="tool-short-description">Short Description</label><input id="tool-short-description" class="input" name="short_description" maxlength="255" value="{{ $old('short_description') }}" placeholder="One concise sentence describing the product"></div>
                </div>
                <div class="form-field tool-description-field"><label for="tool-description">Full Description</label><textarea id="tool-description" class="input" rows="8" name="description" placeholder="Explain the product, core use cases and audience...">{{ $old('description') }}</textarea></div>
            </section>

            <section class="card tool-form-card">
                <div class="tool-form-heading"><span class="tool-form-icon"><i data-lucide="sparkles"></i></span><div><h3>Features & Capabilities</h3><p>Select normalized capabilities used for discovery and comparisons.</p></div></div>
                <div class="tool-check-grid">
                    @forelse($features as $feature)
                        <label class="tool-check-card {{ in_array((int)$feature->id, $selectedFeatures, true) ? 'is-selected' : '' }}">
                            <input type="checkbox" name="feature_ids[]" value="{{ $feature->id }}" @checked(in_array((int)$feature->id, $selectedFeatures, true))>
                            <span><i data-lucide="check"></i></span><strong>{{ $feature->name }}</strong>
                        </label>
                    @empty
                        <div class="tool-inline-empty"><i data-lucide="info"></i> No features yet. Add them from AI Management → Features.</div>
                    @endforelse
                </div>
            </section>

            <section class="card tool-form-card">
                <div class="tool-form-heading"><span class="tool-form-icon"><i data-lucide="tags"></i></span><div><h3>Tags</h3><p>Use controlled tags to improve search, curation and related-content matching.</p></div></div>
                <div class="tool-check-grid tool-check-grid--tags">
                    @forelse($tags as $tag)
                        <label class="tool-check-card {{ in_array((int)$tag->id, $selectedTags, true) ? 'is-selected' : '' }}">
                            <input type="checkbox" name="tag_ids[]" value="{{ $tag->id }}" @checked(in_array((int)$tag->id, $selectedTags, true))>
                            <span><i data-lucide="check"></i></span><strong>{{ $tag->name }}</strong>
                        </label>
                    @empty
                        <div class="tool-inline-empty"><i data-lucide="info"></i> No tags yet. Add them from AI Management → Tags.</div>
                    @endforelse
                </div>
            </section>
        </main>

        <aside class="tool-editor-side">
            <section class="card tool-form-card tool-side-card">
                <div class="tool-form-heading tool-form-heading--compact"><span class="tool-form-icon"><i data-lucide="wallet-cards"></i></span><div><h3>Commercial Profile</h3><p>Pricing and supported platforms.</p></div></div>
                <div class="tool-side-group"><label>Pricing Models</label><div class="tool-option-stack">@foreach(['Free','Freemium','Paid','Enterprise'] as $pricing)<label class="tool-option"><input type="checkbox" name="pricing_models[]" value="{{ $pricing }}" @checked(in_array($pricing, $pricingModels, true))><span></span>{{ $pricing }}</label>@endforeach</div></div>
                <div class="tool-side-group"><label>Platforms</label><div class="tool-option-stack">@foreach(['Web','Windows','macOS','Linux','iOS','Android','API'] as $platform)<label class="tool-option"><input type="checkbox" name="platforms[]" value="{{ $platform }}" @checked(in_array($platform, $platforms, true))><span></span>{{ $platform }}</label>@endforeach</div></div>
            </section>

            <section class="card tool-form-card tool-side-card">
                <div class="tool-form-heading tool-form-heading--compact"><span class="tool-form-icon"><i data-lucide="image"></i></span><div><h3>Media Assets</h3><p>Upload product artwork for directory presentation and sharing.</p></div></div>
                @foreach(['logo'=>'Logo','cover_image'=>'Cover Image','og_image'=>'Open Graph Image'] as $field => $label)
                    <div class="form-field tool-file-field"><label for="{{ $field }}">{{ $label }}</label><input id="{{ $field }}" class="input" type="file" name="{{ $field }}" accept="image/*"></div>
                @endforeach
                @if($tool && ($tool->logo_path || $tool->cover_image_path || $tool->og_image_path))
                    <div class="tool-media-existing"><span>Existing assets</span><div>@if($tool->logo_path)<img src="{{ $tool->logo_url }}" alt="Current logo">@endif @if($tool->cover_image_path)<img src="{{ $tool->cover_image_url }}" alt="Current cover">@endif @if($tool->og_image_path)<img src="{{ $tool->og_image_url }}" alt="Current Open Graph image">@endif</div></div>
                @endif
            </section>

            <section class="card tool-form-card tool-side-card">
                <div class="tool-form-heading tool-form-heading--compact"><span class="tool-form-icon"><i data-lucide="search-check"></i></span><div><h3>SEO Metadata</h3><p>Search and social metadata for the product profile.</p></div></div>
                <div class="form-field"><label for="seo-title">SEO Title</label><input id="seo-title" class="input" name="seo_title" value="{{ $old('seo_title') }}" maxlength="255"></div>
                <div class="form-field"><label for="meta-description">Meta Description</label><textarea id="meta-description" class="input" name="meta_description" rows="4" maxlength="255">{{ $old('meta_description') }}</textarea></div>
            </section>
        </aside>
    </div>
</form>
@endsection

@push('scripts')
<script>
document.addEventListener('change', function (event) {
    const input = event.target.closest('.tool-check-card input[type="checkbox"]');
    if (input) input.closest('.tool-check-card').classList.toggle('is-selected', input.checked);
});
</script>
@endpush