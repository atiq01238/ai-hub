@extends('layouts.admin')
@section('title', isset($tool) ? 'Edit AI Tool' : 'Add AI Tool')

@php
    use Illuminate\Support\Facades\Storage;
@endphp

@section('content')

@php
    $tool ??= null; // null when creating, a Tool model when editing
    $old = fn ($key, $default = null) => old($key, $tool->{$key} ?? $default);
@endphp

<form action="{{ $tool ? route('admin.tools.update', $tool->id) : route('admin.tools.store') }}" method="POST" enctype="multipart/form-data">
    @csrf
    @if ($tool) @method('PUT') @endif

<x-page-header title="{{ $tool ? 'Edit AI Tool' : 'Add AI Tool' }}" subtitle="Fill in the details below to list a new AI tool" :breadcrumb="['AI Management', 'AI Tools', $tool ? 'Edit' : 'Add']">
    <x-slot:actions>
        <button type="button" class="btn btn-secondary btn-sm" data-bs-toggle="modal" data-bs-target="#previewModal"><i data-lucide="eye"></i> Preview</button>
        <button type="submit" name="status" value="draft" class="btn btn-secondary btn-sm"><i data-lucide="save"></i> Save Draft</button>
        <button type="submit" name="status" value="published" class="btn btn-primary btn-sm"><i data-lucide="check"></i> Publish</button>
    </x-slot:actions>
</x-page-header>

@if ($errors->any())
    <div class="alert alert-danger" style="margin-bottom:16px;">
        <ul style="margin:0; padding-left:18px;">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="grid-12">
<div class="col-8">

    <div class="card card-pad form-section" style="margin-bottom:16px;">
        <div class="form-section__title">Basic Information</div>
        <div class="form-grid">
            <div class="form-field"><label>Tool Name</label><input class="input" name="name" value="{{ $old('name') }}" placeholder="e.g. ChatGPT" required></div>
            <div class="form-field">
                <label>Company</label>
                <select class="select" name="company_id">
                    <option value="">Select company...</option>
                    @foreach ($companies as $company)
                        <option value="{{ $company->id }}" @selected($old('company_id') == $company->id)>{{ $company->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-field">
                <label>Logo</label>
                @if ($tool && $tool->logo_path)
                    <img src="{{ Storage::url($tool->logo_path) }}" alt="Current logo" style="width:40px;height:40px;border-radius:8px;object-fit:cover;margin-bottom:6px;display:block;">
                @endif
                <input class="input" type="file" name="logo" accept="image/*">
            </div>
            <div class="form-field">
                <label>Cover Image</label>
                @if ($tool && $tool->cover_image_path)
                    <img src="{{ Storage::url($tool->cover_image_path) }}" alt="Current cover" style="width:100%;max-width:200px;border-radius:8px;object-fit:cover;margin-bottom:6px;display:block;">
                @endif
                <input class="input" type="file" name="cover_image" accept="image/*">
            </div>
            <div class="form-field"><label>Website</label><input class="input" name="website" value="{{ $old('website') }}" placeholder="https://"></div>
            <div class="form-field"><label>Launch Date</label><input class="input" type="date" name="launch_date" value="{{ $old('launch_date') ? \Illuminate\Support\Carbon::parse($old('launch_date'))->format('Y-m-d') : '' }}"></div>
            <div class="form-field col-span-2"><label>Short Description</label><input class="input" name="short_description" value="{{ $old('short_description') }}" placeholder="One-line summary shown in listings"></div>
            <div class="form-field col-span-2"><label>Description</label><textarea class="input" name="description" rows="4" placeholder="Full tool description...">{{ $old('description') }}</textarea></div>
            <div class="form-field">
                <label>Status</label>
                <select class="select" name="status">
                    @foreach (['draft' => 'Draft', 'published' => 'Published', 'archived' => 'Archived'] as $value => $label)
                        <option value="{{ $value }}" @selected($old('status', 'draft') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>

    <div class="card card-pad form-section" style="margin-bottom:16px;">
        <div class="form-section__title">Category</div>
        <div class="form-grid">
            <div class="form-field">
                <label>Category</label>
                <select class="select" name="category_id">
                    <option value="">Select category...</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->id }}" @selected($old('category_id') == $category->id)>{{ $category->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-field">
                <label>Subcategory</label>
                <input class="input" name="subcategory" value="{{ $old('subcategory') }}" placeholder="e.g. General Purpose">
            </div>
            <div class="form-field col-span-2">
                <label>Tags</label>
                <input class="input" name="tags_input" value="{{ $old('tags_input', $tool && $tool->tags ? implode(', ', $tool->tags) : '') }}" placeholder="llm, agents, enterprise...">
            </div>
        </div>
    </div>

    <div class="card card-pad form-section" style="margin-bottom:16px;">
        <div class="form-section__title">Capabilities</div>
        <div class="flex gap-8" style="flex-wrap:wrap;">
            @php $selectedCaps = $old('capabilities', $tool->capabilities ?? ['Text', 'Coding', 'API']); @endphp
            @foreach(['Text','Image','Video','Audio','Coding','Search','Research','Agents','Voice','API'] as $cap)
                <label class="toggle-pill {{ in_array($cap, $selectedCaps) ? 'is-on' : '' }}">
                    <input type="checkbox" name="capabilities[]" value="{{ $cap }}" {{ in_array($cap, $selectedCaps) ? 'checked' : '' }} style="accent-color:var(--brand-1);">{{ $cap }}
                </label>
            @endforeach
        </div>
    </div>

    <div class="card card-pad form-section" style="margin-bottom:16px;">
        <div class="form-section__title">Platforms</div>
        <div class="flex gap-8" style="flex-wrap:wrap;">
            @php $selectedPlatforms = $old('platforms', $tool->platforms ?? ['Web', 'API']); @endphp
            @foreach(['Web','Windows','macOS','iOS','Android','API'] as $p)
                <label class="toggle-pill {{ in_array($p, $selectedPlatforms) ? 'is-on' : '' }}">
                    <input type="checkbox" name="platforms[]" value="{{ $p }}" {{ in_array($p, $selectedPlatforms) ? 'checked' : '' }} style="accent-color:var(--brand-1);">{{ $p }}
                </label>
            @endforeach
        </div>
    </div>

    <div class="card card-pad form-section">
        <div class="form-section__title">SEO</div>
        <div class="form-grid">
            <div class="form-field col-span-2"><label>SEO Title</label><input class="input" name="seo_title" value="{{ $old('seo_title') }}"></div>
            <div class="form-field col-span-2"><label>Meta Description</label><textarea class="input" name="meta_description" rows="2">{{ $old('meta_description') }}</textarea></div>
            <div class="form-field"><label>Slug</label><input class="input" name="slug" value="{{ $old('slug') }}" placeholder="auto-generated from name if left blank"></div>
            <div class="form-field">
                <label>OG Image</label>
                @if ($tool && $tool->og_image_path)
                    <img src="{{ Storage::url($tool->og_image_path) }}" alt="Current OG image" style="width:100%;max-width:160px;border-radius:8px;object-fit:cover;margin-bottom:6px;display:block;">
                @endif
                <input class="input" type="file" name="og_image" accept="image/*">
            </div>
        </div>
    </div>

</div>

<div class="col-4">
    <div class="card card-pad" style="margin-bottom:16px;">
        <div class="form-section__title" style="margin-bottom:12px;">Pricing Model</div>
        <div class="flex gap-8" style="flex-wrap:wrap;">
            @php $selectedPricing = $old('pricing_models', $tool->pricing_models ?? ['Free', 'Freemium']); @endphp
            @foreach(['Free','Paid','Freemium','Enterprise'] as $model)
                <label class="toggle-pill {{ in_array($model, $selectedPricing) ? 'is-on' : '' }}">
                    <input type="checkbox" name="pricing_models[]" value="{{ $model }}" {{ in_array($model, $selectedPricing) ? 'checked' : '' }} style="accent-color:var(--brand-1);">{{ $model }}
                </label>
            @endforeach
        </div>
    </div>

    <div class="card card-pad">
        <div class="form-section__title" style="margin-bottom:12px;">Ratings</div>
        <p class="text-sub" style="font-size:12px;">Ratings aren't editable here yet — they'll come from user reviews later. Showing placeholder values for now.</p>
        @foreach(['Quality'=>92,'Speed'=>78,'Features'=>88,'Ease of Use'=>95,'Value'=>84,'Overall'=>90] as $label => $val)
        <div style="margin-bottom:12px;">
            <div class="flex items-center justify-between" style="margin-bottom:5px;"><span class="text-sub" style="font-size:12.5px;">{{ $label }}</span><span class="mono" style="font-size:12.5px;">{{ $val }}</span></div>
            <div class="progress"><span style="width:{{ $val }}%;"></span></div>
        </div>
        @endforeach
    </div>
</div>
</div>

</form>

@endsection
