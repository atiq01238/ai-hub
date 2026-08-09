@extends('layouts.admin')
@section('title', 'Article Editor')

@php
    use Illuminate\Support\Facades\Storage;
@endphp

@section('content')

@php
    $old = fn ($key, $default = null) => old($key, $article->{$key} ?? $default);
@endphp

<form action="{{ $article ? route('admin.content.articles.update', $article->id) : route('admin.content.articles.store') }}" method="POST" enctype="multipart/form-data">
    @csrf
    @if ($article) @method('PUT') @endif

<x-page-header title="Article Editor" :breadcrumb="['Content', 'News Articles', 'Editor']">
    <x-slot:actions>
        <button type="submit" name="status" value="draft" class="btn btn-secondary btn-sm"><i data-lucide="save"></i> Save Draft</button>
        <button type="submit" name="status" value="scheduled" class="btn btn-secondary btn-sm"><i data-lucide="calendar-clock"></i> Schedule</button>
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
    <div class="card card-pad" style="margin-bottom:16px;">
        <div class="form-field" style="margin-bottom:14px;"><label>Title</label><input class="input" name="title" style="font-size:16px; padding:12px 14px;" placeholder="Article headline..." value="{{ $old('title') }}" required></div>
        <div class="form-field" style="margin-bottom:14px;">
            <label>Featured Image</label>
            @if ($article && $article->featured_image_path)
                <img src="{{ Storage::url($article->featured_image_path) }}" alt="" style="width:100%;max-width:240px;border-radius:8px;object-fit:cover;margin-bottom:6px;display:block;">
            @endif
            <input class="input" type="file" name="featured_image" accept="image/*">
        </div>
        <div class="form-field">
            <label>Content</label>
            <textarea class="input" name="content" rows="14" placeholder="Write your article...">{{ $old('content') }}</textarea>
        </div>
    </div>

    <div class="card card-pad">
        <div class="form-field"><label>Summary</label><textarea class="input" name="summary" rows="3" placeholder="Short summary for listings...">{{ $old('summary') }}</textarea></div>
    </div>
</div>

<div class="col-4">
    <div class="card card-pad" style="margin-bottom:16px;">
        <div class="form-section__title">Organize</div>
        <div class="form-field" style="margin-bottom:12px;">
            <label>Category</label>
            <select class="select" name="category">
                <option value="">Select category...</option>
                @foreach (['New Model','Product Update','Pricing Change','Research','Benchmark','Guide','Opinion'] as $cat)
                    <option value="{{ $cat }}" @selected($old('category') === $cat)>{{ $cat }}</option>
                @endforeach
            </select>
        </div>
        <div class="form-field" style="margin-bottom:12px;"><label>Tags</label><input class="input" name="tags_input" value="{{ $old('tags_input', $article && $article->tags ? implode(', ', $article->tags) : '') }}" placeholder="agents, gpt-5, openai"></div>
        <div class="form-field" style="margin-bottom:12px;"><label>Related Tools</label><input class="input" name="related_tools_input" value="{{ $old('related_tools_input', $article && $article->related_tools ? implode(', ', $article->related_tools) : '') }}" placeholder="ChatGPT"></div>
        <div class="form-field" style="margin-bottom:12px;"><label>Related Models</label><input class="input" name="related_models_input" value="{{ $old('related_models_input', $article && $article->related_models ? implode(', ', $article->related_models) : '') }}" placeholder="GPT-5.2 Turbo"></div>
        <div class="form-field">
            <label>Related Company</label>
            <select class="select" name="company_id">
                <option value="">Select company...</option>
                @foreach ($companies as $company)
                    <option value="{{ $company->id }}" @selected($old('company_id') == $company->id)>{{ $company->name }}</option>
                @endforeach
            </select>
        </div>
    </div>

    <div class="card card-pad" style="margin-bottom:16px;">
        <div class="form-section__title">SEO</div>
        <div class="form-field" style="margin-bottom:12px;"><label>SEO Title</label><input class="input" name="seo_title" value="{{ $old('seo_title') }}"></div>
        <div class="form-field"><label>Meta Description</label><textarea class="input" name="meta_description" rows="3">{{ $old('meta_description') }}</textarea></div>
    </div>

    <div class="card card-pad">
        <div class="form-section__title">Publish Settings</div>
        <div class="form-field" style="margin-bottom:12px;">
            <label>Author</label>
            <select class="select" name="user_id" required>
                @foreach ($authors as $author)
                    <option value="{{ $author->id }}" @selected($old('user_id', $article->user_id ?? auth()->id()) == $author->id)>{{ $author->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="form-field"><label>Publish Date</label><input class="input" type="datetime-local" name="published_at" value="{{ $old('published_at', $article && $article->published_at ? $article->published_at->format('Y-m-d\TH:i') : '') }}"></div>
    </div>
</div>
</div>

</form>
@endsection
