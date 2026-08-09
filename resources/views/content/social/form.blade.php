@extends('layouts.admin')
@section('title', isset($post) ? 'Edit Post' : 'New Social Post')

@php
    use Illuminate\Support\Facades\Storage;
@endphp

@section('content')

@php
    $post ??= null;
    $newsItem ??= $post?->newsItem;
    $old = fn ($key, $default = null) => old($key, $post->{$key} ?? $default);
    // Pre-fill content from the chosen news item when creating fresh.
    $defaultContent = $post ? $post->content : ($newsItem ? $newsItem->headline . ' — ' . $newsItem->summary : '');
@endphp

<form action="{{ $post ? route('admin.content.social.update', $post->id) : route('admin.content.social.store') }}" method="POST" enctype="multipart/form-data">
    @csrf
    @if ($post) @method('PUT') @endif
    @if ($newsItem && !$post) <input type="hidden" name="news_item_id" value="{{ $newsItem->id }}"> @endif

<x-page-header title="{{ $post ? 'Edit Post' : 'New Social Post' }}" :breadcrumb="['Content', 'Social Posts', $post ? 'Edit' : 'New']">
    <x-slot:actions>
        <button type="submit" name="status" value="draft" class="btn btn-secondary btn-sm"><i data-lucide="save"></i> Save Draft</button>
        <button type="submit" name="status" value="scheduled" class="btn btn-secondary btn-sm"><i data-lucide="calendar-clock"></i> Schedule</button>
        <button type="submit" name="status" value="published" class="btn btn-primary btn-sm"><i data-lucide="check"></i> Mark Published</button>
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

@if ($newsItem)
<div class="card card-pad" style="margin-bottom:16px; background:var(--surface-2);">
    <div class="cell-sub">Drafting from news item:</div>
    <b>{{ $newsItem->headline }}</b>
</div>
@endif

<div class="card card-pad" style="max-width:640px;">
    <div class="form-grid">
        <div class="form-field">
            <label>Platform</label>
            <select class="select" name="platform" required>
                @foreach (['x'=>'X','facebook'=>'Facebook','instagram'=>'Instagram','linkedin'=>'LinkedIn','youtube'=>'YouTube','tiktok'=>'TikTok'] as $val => $label)
                    <option value="{{ $val }}" @selected($old('platform') === $val)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="form-field"><label>Schedule For</label><input class="input" type="datetime-local" name="scheduled_at" value="{{ $old('scheduled_at', $post && $post->scheduled_at ? $post->scheduled_at->format('Y-m-d\TH:i') : '') }}"></div>
        <div class="form-field col-span-2"><label>Content</label><textarea class="input" name="content" rows="5" required>{{ $old('content', $defaultContent) }}</textarea></div>
        <div class="form-field col-span-2">
            <label>Image</label>
            @if ($post && $post->image_path)
                <img src="{{ Storage::url($post->image_path) }}" alt="" style="width:160px;border-radius:8px;object-fit:cover;margin-bottom:6px;display:block;">
            @endif
            <input class="input" type="file" name="image" accept="image/*">
        </div>
    </div>
</div>

</form>
@endsection
