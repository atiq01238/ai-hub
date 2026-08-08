@extends('layouts.admin')
@section('title', isset($item) ? 'Edit News Item' : 'Add News Item')

@section('content')

@php
    $item ??= null;
    $old = fn ($key, $default = null) => old($key, $item->{$key} ?? $default);
@endphp

<form action="{{ $item ? route('admin.news.update', $item->id) : route('admin.news.store') }}" method="POST">
    @csrf
    @if ($item) @method('PUT') @endif

<x-page-header title="{{ $item ? 'Edit News Item' : 'Add News Item' }}" :breadcrumb="['AI Intelligence', 'News Feed', $item ? 'Edit' : 'Add']">
    <x-slot:actions>
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
        <div class="form-section__title">Story</div>
        <div class="form-grid">
            <div class="form-field col-span-2"><label>Headline</label><input class="input" name="headline" value="{{ $old('headline') }}" placeholder="e.g. OpenAI announces GPT-5.2 Turbo" required></div>
            <div class="form-field col-span-2"><label>Summary</label><textarea class="input" name="summary" rows="4" placeholder="What happened...">{{ $old('summary') }}</textarea></div>
            <div class="form-field col-span-2"><label>Why It Matters</label><textarea class="input" name="why_it_matters" rows="2" placeholder="Why this is significant...">{{ $old('why_it_matters') }}</textarea></div>
        </div>
    </div>

    <div class="card card-pad form-section">
        <div class="form-section__title">Classification</div>
        <div class="form-grid">
            <div class="form-field">
                <label>Category</label>
                <select class="select" name="category">
                    <option value="">Select category...</option>
                    @foreach (['Breaking News','New Model','Product Launch','Product Update','New Feature','Pricing Change','AI Review','Benchmark','Research','Funding','Acquisition','Security','Policy','Regulation'] as $cat)
                        <option value="{{ $cat }}" @selected($old('category') === $cat)>{{ $cat }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-field">
                <label>Company</label>
                <select class="select" name="company_id">
                    <option value="">Select company...</option>
                    @foreach ($companies as $company)
                        <option value="{{ $company->id }}" @selected($old('company_id') == $company->id)>{{ $company->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-field"><label>Sentiment</label>
                <select class="select" name="sentiment">
                    @foreach (['positive'=>'Positive','neutral'=>'Neutral','negative'=>'Negative'] as $val => $label)
                        <option value="{{ $val }}" @selected($old('sentiment','neutral') === $val)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-field"><label>Importance (0–100)</label><input class="input" type="number" min="0" max="100" name="importance" value="{{ $old('importance', 50) }}"></div>
            <div class="form-field"><label>Verification Status</label>
                <select class="select" name="verification_status">
                    @foreach (['unverified'=>'Unverified','needs_verification'=>'Needs Verification','verified'=>'Verified'] as $val => $label)
                        <option value="{{ $val }}" @selected($old('verification_status','unverified') === $val)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-field col-span-2"><label>Related Tools</label><input class="input" name="related_tools_input" value="{{ $old('related_tools_input', $item && $item->related_tools ? implode(', ', $item->related_tools) : '') }}" placeholder="ChatGPT, Claude..."></div>
            <div class="form-field col-span-2"><label>Tags</label><input class="input" name="tags_input" value="{{ $old('tags_input', $item && $item->tags ? implode(', ', $item->tags) : '') }}" placeholder="agents, llm, enterprise..."></div>
        </div>
    </div>
</div>

<div class="col-4">
    <div class="card card-pad" style="margin-bottom:16px;">
        <div class="form-section__title" style="margin-bottom:12px;">Source</div>
        <div class="form-field" style="margin-bottom:12px;"><label>Source Name</label><input class="input" name="source" value="{{ $old('source') }}" placeholder="e.g. TechCrunch"></div>
        <div class="form-field"><label>Source URL</label><input class="input" name="source_url" value="{{ $old('source_url') }}" placeholder="https://"></div>
    </div>
</div>
</div>

</form>
@endsection
