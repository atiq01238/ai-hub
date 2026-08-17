@extends('layouts.admin')
@section('title', $article ? 'Edit Article' : 'Create Article')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/pages/content.css') }}">
@endpush

@section('content')
@php
    $article ??= null;
    $old = fn($key,$default=null) => old($key, $article?->{$key} ?? $default);
    $selectedTags = array_map('intval', old('tag_ids', $article?->tagTerms?->pluck('id')->all() ?? []));
    $selectedTools = array_map('intval', old('tool_ids', $article?->relatedToolTerms?->pluck('id')->all() ?? []));
    $selectedModels = array_map('intval', old('model_ids', $article?->relatedModelTerms?->pluck('id')->all() ?? []));
@endphp

<div class="content-page content-editor">
<form action="{{ $article ? route('admin.content.articles.update',$article->id) : route('admin.content.articles.store') }}" method="POST" enctype="multipart/form-data">
@csrf
@if($article) @method('PUT') @endif

<x-page-header
    :title="$article ? 'Edit Article' : 'Create Article'"
    subtitle="Build governed content with taxonomy, related AI entities, SEO and approval-aware publishing."
    :breadcrumb="['Content','Articles',$article ? 'Edit' : 'Create']"
>
    <x-slot:actions>
        <a href="{{ $article ? route('admin.content.articles.show',$article->id) : route('admin.content.articles.index') }}" class="btn btn-secondary"><i data-lucide="arrow-left"></i>Cancel</a>
        <button class="btn btn-primary" type="submit"><i data-lucide="save"></i>{{ $article ? 'Save Changes' : 'Create Article' }}</button>
    </x-slot:actions>
</x-page-header>

@if($errors->any())
<div class="alert alert-danger content-errors"><i data-lucide="circle-alert"></i><div><strong>Please review the article fields.</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div></div>
@endif

<div class="content-editor__layout">
<main class="content-editor__main">
    <section class="card content-panel">
        <div class="content-section-head"><div><span class="content-eyebrow">Story</span><h2>Article content</h2><p>Write the core editorial content and summary.</p></div><span class="content-panel__icon"><i data-lucide="file-text"></i></span></div>
        <div class="content-form-grid">
            <label class="content-field content-field--full"><span>Title <b>*</b></span><input class="input" name="title" value="{{ $old('title') }}" required placeholder="Clear, specific article title"></label>
            <label class="content-field content-field--full"><span>Summary</span><textarea class="textarea" name="summary" rows="4" placeholder="Short executive summary...">{{ $old('summary') }}</textarea></label>
            <label class="content-field content-field--full"><span>Body</span><textarea class="textarea content-editor__body" name="content" rows="18" placeholder="Write the article body...">{{ $old('content') }}</textarea></label>
        </div>
    </section>

    <section class="card content-panel">
        <div class="content-section-head"><div><span class="content-eyebrow">Connections</span><h2>Taxonomy & related AI</h2><p>Connect this article to the entities and topics it discusses.</p></div><span class="content-panel__icon"><i data-lucide="network"></i></span></div>
        <div class="content-form-grid">
            <label class="content-field"><span>Category</span><select class="select" name="category_id"><option value="">Select category...</option>@foreach($categories as $category)<option value="{{ $category->id }}" @selected((string)$old('category_id') === (string)$category->id)>{{ $category->name }}</option>@endforeach</select></label>
            <label class="content-field"><span>Related Company</span><select class="select" name="company_id"><option value="">Select company...</option>@foreach($companies as $company)<option value="{{ $company->id }}" @selected((string)$old('company_id') === (string)$company->id)>{{ $company->name }}</option>@endforeach</select></label>
            <label class="content-field"><span>Tags</span><select class="select" name="tag_ids[]" multiple size="7">@foreach($tags as $tag)<option value="{{ $tag->id }}" @selected(in_array((int)$tag->id,$selectedTags,true))>{{ $tag->name }}</option>@endforeach</select></label>
            <label class="content-field"><span>Related Tools</span><select class="select" name="tool_ids[]" multiple size="7">@foreach($tools as $tool)<option value="{{ $tool->id }}" @selected(in_array((int)$tool->id,$selectedTools,true))>{{ $tool->name }}</option>@endforeach</select></label>
            <label class="content-field content-field--full"><span>Related Models</span><select class="select" name="model_ids[]" multiple size="7">@foreach($models as $model)<option value="{{ $model->id }}" @selected(in_array((int)$model->id,$selectedModels,true))>{{ $model->name }}</option>@endforeach</select></label>
        </div>
    </section>

    <section class="card content-panel">
        <div class="content-section-head"><div><span class="content-eyebrow">Search</span><h2>SEO metadata</h2><p>Optional metadata for public search and sharing.</p></div><span class="content-panel__icon"><i data-lucide="search-check"></i></span></div>
        <div class="content-form-grid">
            <label class="content-field content-field--full"><span>SEO Title</span><input class="input" name="seo_title" value="{{ $old('seo_title') }}"></label>
            <label class="content-field content-field--full"><span>Meta Description</span><textarea class="textarea" name="meta_description" rows="3">{{ $old('meta_description') }}</textarea></label>
        </div>
    </section>
</main>

<aside class="content-editor__aside">
    <section class="card content-editor__publish">
        <span class="content-eyebrow">Publishing</span>
        <div class="content-editor__publish-icon"><i data-lucide="send"></i></div>
        <label class="content-field"><span>Author <b>*</b></span><select class="select" name="user_id" required>@foreach($authors as $author)<option value="{{ $author->id }}" @selected((string)$old('user_id',$article?->user_id ?? auth()->id()) === (string)$author->id)>{{ $author->name }}</option>@endforeach</select></label>
        <label class="content-field"><span>Publication Status <b>*</b></span><select class="select" name="status" required><option value="draft" @selected($old('status','draft')==='draft')>Draft</option><option value="scheduled" @selected($old('status')==='scheduled')>Scheduled</option><option value="published" @selected($old('status')==='published')>Published</option></select><small>Scheduled/published states require article approval. Controller will safely force unapproved content back to draft.</small></label>
        <label class="content-field"><span>Publish / Schedule Date</span><input class="input" type="datetime-local" name="published_at" value="{{ old('published_at',$article?->published_at?->format('Y-m-d\TH:i')) }}"></label>
        <label class="content-field"><span>Featured Image</span>@if($article?->featured_image_path)<img class="content-editor__preview" src="{{ \Illuminate\Support\Facades\Storage::url($article->featured_image_path) }}" alt="">@endif<input class="input" type="file" name="featured_image" accept="image/*"></label>
        @if($article)
        <div class="content-editor__approval">
            <span>Approval state</span>
            <strong>{{ ucwords(str_replace('_',' ',$article->approval_status ?? 'draft')) }}</strong>
        </div>
        @endif
    </section>
</aside>
</div>
</form>
</div>
@endsection
