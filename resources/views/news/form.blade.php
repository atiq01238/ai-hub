@extends('layouts.admin')
@section('title', ($item ?? null) ? 'Edit News Item' : 'Add News Item')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/pages/news.css') }}">
@endpush

@section('content')
@php
    $item = $item ?? null;
    $old = fn ($key, $default = null) => old($key, $item->{$key} ?? $default);
    $categories = ['Breaking News','New Model','Product Launch','Product Update','New Feature','Pricing Change','AI Review','Benchmark','Research','Funding','Acquisition','Security','Policy','Regulation'];
@endphp

<div class="news-shell news-editor">
    <form action="{{ $item ? route('admin.news.update', $item->id) : route('admin.news.store') }}" method="POST" enctype="multipart/form-data">
        @csrf
        @if ($item) @method('PUT') @endif

        <x-page-header
            :title="$item ? 'Edit News Intelligence' : 'Create News Intelligence'"
            :subtitle="$item ? 'Refine classification, verification and source details without changing the underlying workflow.' : 'Turn a raw development into structured, searchable AI intelligence.'"
            :breadcrumb="['AI Intelligence', 'News', $item ? 'Edit' : 'Create']"
        >
            <x-slot:actions>
                <a href="{{ $item ? route('admin.news.show', $item->id) : route('admin.news.index') }}" class="btn btn-secondary btn-sm">
                    <i data-lucide="arrow-left"></i> Cancel
                </a>
                <button type="submit" name="status" value="draft" class="btn btn-secondary btn-sm">
                    <i data-lucide="save"></i> Save Draft
                </button>
                <button type="submit" name="status" value="published" class="btn btn-primary btn-sm">
                    <i data-lucide="send"></i> {{ $item && $item->status === 'published' ? 'Update & Publish' : 'Publish' }}
                </button>
            </x-slot:actions>
        </x-page-header>

        @if ($errors->any())
            <div class="alert alert-danger news-validation">
                <i data-lucide="triangle-alert"></i>
                <div>
                    <strong>Please fix the highlighted information.</strong>
                    <ul>
                        @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                    </ul>
                </div>
            </div>
        @endif

        <div class="news-editor__layout">
            <main class="news-editor__main">
                <section class="news-panel">
                    <div class="news-panel__header">
                        <div class="news-panel__icon"><i data-lucide="file-text"></i></div>
                        <div><h2>Story</h2><p>Capture the development and its editorial significance.</p></div>
                    </div>
                    <div class="news-panel__body news-form-grid">
                        <div class="form-field news-span-2">
                            <label for="headline">Headline <span class="news-required">*</span></label>
                            <input id="headline" class="input" name="headline" value="{{ $old('headline') }}" placeholder="e.g. OpenAI announces a new enterprise model" required>
                        </div>
                        <div class="form-field news-span-2">
                            <label for="summary">Summary</label>
                            <textarea id="summary" class="input news-textarea" name="summary" rows="5" placeholder="What happened, in concise factual language…">{{ $old('summary') }}</textarea>
                            <div class="news-help">Focus on the concrete development rather than commentary.</div>
                        </div>
                        <div class="form-field news-span-2">
                            <label for="why_it_matters">Why it matters</label>
                            <textarea id="why_it_matters" class="input news-textarea" name="why_it_matters" rows="3" placeholder="Explain the strategic or market significance…">{{ $old('why_it_matters') }}</textarea>
                        </div>
                    </div>
                </section>

                <section class="news-panel">
                    <div class="news-panel__header">
                        <div class="news-panel__icon"><i data-lucide="tags"></i></div>
                        <div><h2>Classification</h2><p>Make the story searchable, comparable and trustworthy.</p></div>
                    </div>
                    <div class="news-panel__body news-form-grid">
                        <div class="form-field">
                            <label for="category">Category</label>
                            <select id="category" class="select" name="category">
                                <option value="">Select category…</option>
                                @foreach ($categories as $cat)<option value="{{ $cat }}" @selected($old('category') === $cat)>{{ $cat }}</option>@endforeach
                            </select>
                        </div>
                        <div class="form-field">
                            <label for="company_id">Company</label>
                            <select id="company_id" class="select" name="company_id">
                                <option value="">No linked company</option>
                                @foreach ($companies as $company)<option value="{{ $company->id }}" @selected($old('company_id') == $company->id)>{{ $company->name }}</option>@endforeach
                            </select>
                        </div>
                        <div class="form-field">
                            <label for="sentiment">Sentiment <span class="news-required">*</span></label>
                            <select id="sentiment" class="select" name="sentiment" required>
                                @foreach (['positive'=>'Positive','neutral'=>'Neutral','negative'=>'Negative'] as $value => $label)<option value="{{ $value }}" @selected($old('sentiment','neutral') === $value)>{{ $label }}</option>@endforeach
                            </select>
                        </div>
                        <div class="form-field">
                            <label for="importance">Importance score <span class="news-required">*</span></label>
                            <div class="news-score-input">
                                <input id="importance" class="input" type="number" min="0" max="100" name="importance" value="{{ $old('importance', 50) }}" required>
                                <span>/100</span>
                            </div>
                        </div>
                        <div class="form-field news-span-2">
                            <label for="verification_status">Verification status <span class="news-required">*</span></label>
                            <select id="verification_status" class="select" name="verification_status" required>
                                @foreach (['unverified'=>'Unverified','needs_verification'=>'Needs Verification','verified'=>'Verified'] as $value => $label)<option value="{{ $value }}" @selected($old('verification_status','unverified') === $value)>{{ $label }}</option>@endforeach
                            </select>
                        </div>
                        <div class="form-field news-span-2">
                            <label for="related_tools_input">Related tools</label>
                            <input id="related_tools_input" class="input" name="related_tools_input" value="{{ $old('related_tools_input', $item && $item->related_tools ? implode(', ', $item->related_tools) : '') }}" placeholder="ChatGPT, Claude, Gemini…">
                            <div class="news-help">Separate tool names with commas.</div>
                        </div>
                        <div class="form-field news-span-2">
                            <label for="tags_input">Tags</label>
                            <input id="tags_input" class="input" name="tags_input" value="{{ $old('tags_input', $item && $item->tags ? implode(', ', $item->tags) : '') }}" placeholder="agents, llm, enterprise, pricing…">
                            <div class="news-help">Use concise topics for search and discovery.</div>
                        </div>
                    </div>
                </section>
            </main>

            <aside class="news-editor__sidebar">
                <section class="news-panel news-panel--sticky">
                    <div class="news-panel__header">
                        <div class="news-panel__icon"><i data-lucide="link-2"></i></div>
                        <div><h2>Source</h2><p>Record where this intelligence originated.</p></div>
                    </div>
                    <div class="news-panel__body news-stack">
                        <div class="form-field">
                            <label for="source">Source name</label>
                            <input id="source" class="input" name="source" value="{{ $old('source') }}" placeholder="e.g. TechCrunch">
                        </div>
                        <div class="form-field">
                            <label for="source_url">Source URL</label>
                            <input id="source_url" class="input" type="url" name="source_url" value="{{ $old('source_url') }}" placeholder="https://…">
                        </div>

                        <div class="form-field">
                            <label for="news_image">Story image</label>
                            <input id="news_image" class="input" type="file" name="image" accept="image/png,image/jpeg,image/webp">
                            <div class="news-help">Optional. If no image is provided, the public story will show a neutral no-image state instead of an unrelated stock image.</div>
                        </div>
                        @if($item?->image_url)
                            <div class="news-editor__image-preview">
                                <img src="{{ $item->image_url }}" alt="Current story image">
                                <label><input type="checkbox" name="remove_image" value="1"> Remove current image</label>
                            </div>
                        @endif

                        <div class="news-editor__status">
                            <div class="news-editor__status-row"><span>Mode</span><strong>{{ $item ? 'Editing' : 'Creating' }}</strong></div>
                            <div class="news-editor__status-row"><span>Current status</span><strong>{{ ucfirst($item->status ?? 'Draft') }}</strong></div>
                            <div class="news-editor__status-row"><span>Verification</span><strong>{{ ucfirst(str_replace('_', ' ', $old('verification_status', 'unverified'))) }}</strong></div>
                        </div>

                        <div class="news-editor__tip">
                            <i data-lucide="shield-check"></i>
                            <p>Verify the source and classification before publishing high-importance intelligence.</p>
                        </div>
                    </div>
                </section>
            </aside>
        </div>
    </form>
</div>
@endsection
