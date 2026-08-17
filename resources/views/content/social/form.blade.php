@extends('layouts.admin')
@section('title', isset($post) ? 'Edit Social Post' : 'New Social Post')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/pages/content.css') }}">
@endpush

@section('content')
@php
$post ??= null;
$newsItem ??= $post?->newsItem;
$old = fn($key,$default=null) => old($key,$post?->{$key} ?? $default);
$defaultContent = $post ? $post->content : ($newsItem ? $newsItem->headline.' — '.$newsItem->summary : '');
@endphp
<div class="content-page content-social-editor">
<form action="{{ $post ? route('admin.content.social.update',$post->id) : route('admin.content.social.store') }}" method="POST" enctype="multipart/form-data">
@csrf @if($post) @method('PUT') @endif
@if($newsItem && !$post)<input type="hidden" name="news_item_id" value="{{ $newsItem->id }}">@endif

<x-page-header :title="$post ? 'Edit Social Post' : 'New Social Post'" subtitle="Prepare channel-specific distribution while preserving schedule and source context." :breadcrumb="['Content','Social Posts',$post?'Edit':'New']">
<x-slot:actions><a href="{{ route('admin.content.social.index') }}" class="btn btn-secondary"><i data-lucide="arrow-left"></i>Cancel</a><button type="submit" name="status" value="draft" class="btn btn-secondary"><i data-lucide="save"></i>Save Draft</button><button type="submit" name="status" value="scheduled" class="btn btn-secondary"><i data-lucide="calendar-clock"></i>Schedule</button><button type="submit" name="status" value="published" class="btn btn-primary"><i data-lucide="check"></i>Mark Published</button></x-slot:actions>
</x-page-header>

@if($errors->any())<div class="alert alert-danger content-errors"><i data-lucide="circle-alert"></i><div><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div></div>@endif
@if($newsItem)<div class="card content-source-banner"><i data-lucide="newspaper"></i><div><span class="content-eyebrow">Drafting from news</span><strong>{{ $newsItem->headline }}</strong></div></div>@endif

<div class="content-social-editor__layout">
<main>
<section class="card content-panel">
<div class="content-section-head"><div><span class="content-eyebrow">Post</span><h2>Distribution content</h2><p>Adapt the copy and media for the selected platform.</p></div><span class="content-panel__icon"><i data-lucide="share-2"></i></span></div>
<div class="content-form-grid">
<label class="content-field"><span>Platform <b>*</b></span><select class="select" name="platform" required>@foreach(['x'=>'X','facebook'=>'Facebook','instagram'=>'Instagram','linkedin'=>'LinkedIn','youtube'=>'YouTube','tiktok'=>'TikTok'] as $value=>$label)<option value="{{ $value }}" @selected($old('platform','x')===$value)>{{ $label }}</option>@endforeach</select></label>
<label class="content-field"><span>Schedule For</span><input class="input" type="datetime-local" name="scheduled_at" value="{{ old('scheduled_at',$post?->scheduled_at?->format('Y-m-d\TH:i')) }}"></label>
<label class="content-field content-field--full"><span>Content <b>*</b></span><textarea class="textarea" name="content" rows="10" maxlength="2000" required>{{ old('content',$defaultContent) }}</textarea><small>Maximum 2,000 characters at the backend validation layer.</small></label>
</div>
</section>
</main>

<aside class="content-social-editor__aside">
<section class="card content-editor__publish"><span class="content-eyebrow">Media</span><div class="content-editor__publish-icon"><i data-lucide="image-plus"></i></div>@if($post?->image_path)<img class="content-editor__preview" src="{{ \Illuminate\Support\Facades\Storage::url($post->image_path) }}" alt="">@endif<label class="content-field"><span>Post image</span><input class="input" type="file" name="image" accept="image/*"><small>Maximum 4 MB.</small></label></section>
</aside>
</div>
</form>
</div>
@endsection
