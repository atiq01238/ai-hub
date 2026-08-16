@extends('layouts.admin')
@section('title', 'Article Editor')

@php use Illuminate\Support\Facades\Storage; @endphp

@section('content')
@php
    $old = fn ($key, $default = null) => old($key, $article->{$key} ?? $default);
    $selectedTags = collect(old('tag_ids', $article?->tagTerms?->pluck('id')->all() ?? []))->map(fn($v)=>(int)$v)->all();
    $selectedTools = collect(old('tool_ids', $article?->relatedToolTerms?->pluck('id')->all() ?? []))->map(fn($v)=>(int)$v)->all();
    $selectedModels = collect(old('model_ids', $article?->relatedModelTerms?->pluck('id')->all() ?? []))->map(fn($v)=>(int)$v)->all();
    $approved = ($article?->approval_status ?? 'draft') === 'approved';
@endphp

<form action="{{ $article ? route('admin.content.articles.update', $article->id) : route('admin.content.articles.store') }}" method="POST" enctype="multipart/form-data">
@csrf
@if($article) @method('PUT') @endif

<x-page-header title="Article Editor" subtitle="Taxonomy-linked content with approval-aware publishing" :breadcrumb="['Content','News Articles','Editor']">
    <x-slot:actions>
        <button type="submit" name="status" value="draft" class="btn btn-secondary btn-sm"><i data-lucide="save"></i> Save Draft</button>
        <button type="submit" name="status" value="scheduled" class="btn btn-secondary btn-sm" {{ !$approved ? 'disabled' : '' }}><i data-lucide="calendar-clock"></i> Schedule</button>
        <button type="submit" name="status" value="published" class="btn btn-primary btn-sm" {{ !$approved ? 'disabled' : '' }}><i data-lucide="check"></i> Publish</button>
    </x-slot:actions>
</x-page-header>

@if($errors->any())
<div class="alert alert-danger" style="margin-bottom:16px;"><ul style="margin:0;padding-left:18px;">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
@endif

@if(!$approved)
<div class="alert" style="margin-bottom:16px;border:1px solid var(--border);background:var(--surface);">
    <b>Approval required before publishing.</b> Save the article first, then submit it through <a href="{{ route('admin.content.approval-workflow') }}">Approval Workflow</a>. Current approval status: <b>{{ ucwords(str_replace('_',' ', $article?->approval_status ?? 'draft')) }}</b>.
</div>
@endif

<div class="grid-12">
<div class="col-8">
    <div class="card card-pad" style="margin-bottom:16px;">
        <div class="form-field" style="margin-bottom:14px;"><label>Title</label><input class="input" name="title" style="font-size:16px;padding:12px 14px;" placeholder="Article headline..." value="{{ $old('title') }}" required></div>
        <div class="form-field" style="margin-bottom:14px;"><label>Featured Image</label>
            @if($article?->featured_image_path)<img src="{{ Storage::url($article->featured_image_path) }}" alt="" style="width:100%;max-width:260px;border-radius:8px;object-fit:cover;margin-bottom:8px;display:block;">@endif
            <input class="input" type="file" name="featured_image" accept="image/*">
        </div>
        <div class="form-field"><label>Content</label><textarea class="input" name="content" rows="16" placeholder="Write your article...">{{ $old('content') }}</textarea></div>
    </div>
    <div class="card card-pad"><div class="form-field"><label>Summary</label><textarea class="input" name="summary" rows="4" placeholder="Short summary for listings...">{{ $old('summary') }}</textarea></div></div>
</div>

<div class="col-4">
    <div class="card card-pad" style="margin-bottom:16px;">
        <div class="form-section__title">Organize</div>
        <div class="form-field" style="margin-bottom:12px;"><label>Category</label><select class="select" name="category_id"><option value="">Select category...</option>@foreach($categories as $category)<option value="{{ $category->id }}" @selected((int)$old('category_id')===$category->id)>{{ $category->name }}</option>@endforeach</select></div>
        <div class="form-field" style="margin-bottom:12px;"><label>Tags</label><select class="select" name="tag_ids[]" multiple size="5">@foreach($tags as $tag)<option value="{{ $tag->id }}" @selected(in_array($tag->id,$selectedTags,true))>{{ $tag->name }}</option>@endforeach</select><span class="cell-sub">Ctrl/Cmd + click to select multiple.</span></div>
        <div class="form-field" style="margin-bottom:12px;"><label>Related Tools</label><select class="select" name="tool_ids[]" multiple size="6">@foreach($tools as $tool)<option value="{{ $tool->id }}" @selected(in_array($tool->id,$selectedTools,true))>{{ $tool->name }}</option>@endforeach</select></div>
        <div class="form-field" style="margin-bottom:12px;"><label>Related Models</label><select class="select" name="model_ids[]" multiple size="6">@foreach($models as $model)<option value="{{ $model->id }}" @selected(in_array($model->id,$selectedModels,true))>{{ $model->name }}</option>@endforeach</select></div>
        <div class="form-field"><label>Related Company</label><select class="select" name="company_id"><option value="">Select company...</option>@foreach($companies as $company)<option value="{{ $company->id }}" @selected((int)$old('company_id')===$company->id)>{{ $company->name }}</option>@endforeach</select></div>
    </div>

    <div class="card card-pad" style="margin-bottom:16px;">
        <div class="form-section__title">SEO</div>
        <div class="form-field" style="margin-bottom:12px;"><label>SEO Title</label><input class="input" name="seo_title" value="{{ $old('seo_title') }}"></div>
        <div class="form-field"><label>Meta Description</label><textarea class="input" name="meta_description" rows="3">{{ $old('meta_description') }}</textarea></div>
    </div>

    <div class="card card-pad">
        <div class="form-section__title">Publish Settings</div>
        <div class="form-field" style="margin-bottom:12px;"><label>Author</label><select class="select" name="user_id" required>@foreach($authors as $author)<option value="{{ $author->id }}" @selected((int)$old('user_id',$article->user_id ?? auth()->id())===$author->id)>{{ $author->name }}</option>@endforeach</select></div>
        <div class="form-field"><label>Publish / Schedule Date</label><input class="input" type="datetime-local" name="published_at" value="{{ old('published_at', $article?->published_at?->format('Y-m-d\TH:i')) }}"><span class="cell-sub">Required for scheduled publishing.</span></div>
    </div>
</div>
</div>
</form>
@endsection
