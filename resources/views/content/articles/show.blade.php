@extends('layouts.admin')
@section('title', $article->title)

@php
    use Illuminate\Support\Facades\Storage;
@endphp

@section('content')

<x-page-header
    title="{{ $article->title }}"
    subtitle="By {{ $article->author->name ?? '—' }} · {{ ucfirst($article->status) }}{{ $article->published_at ? ' · '.$article->published_at->format('M j, Y') : '' }}"
    :breadcrumb="['Content', 'News Articles', $article->title]">
    <x-slot:actions>
        <a href="{{ route('admin.content.articles.editor.edit', $article->id) }}" class="btn btn-primary btn-sm"><i data-lucide="pencil"></i> Edit</a>
        <form action="{{ route('admin.content.articles.destroy', $article->id) }}" method="POST" onsubmit="return confirm('Delete this article?')">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-danger btn-sm"><i data-lucide="trash-2"></i> Delete</button>
        </form>
    </x-slot:actions>
</x-page-header>

@if (session('status'))
    <div class="alert alert-success" style="margin-bottom:16px;">{{ session('status') }}</div>
@endif

<div class="grid-12">
    <div class="col-8 card card-pad">
        @if ($article->featured_image_path)
            <img src="{{ Storage::url($article->featured_image_path) }}" alt="" style="width:100%; border-radius:10px; margin-bottom:16px;">
        @endif
        @if ($article->summary)
            <p class="text-sub" style="font-size:13.5px; font-style:italic; margin-bottom:14px;">{{ $article->summary }}</p>
        @endif
        <div style="font-size:14px; line-height:1.8; white-space:pre-line;">{{ $article->content ?: 'No content added yet.' }}</div>
    </div>

    <div class="col-4 card card-pad">
        <div class="section-title">Details</div>
        <div class="flex items-center justify-between" style="padding:9px 0; border-bottom:1px solid var(--border-soft);"><span class="cell-sub">Category</span><b style="font-size:13px;">{{ $article->category ?? '—' }}</b></div>
        <div class="flex items-center justify-between" style="padding:9px 0; border-bottom:1px solid var(--border-soft);"><span class="cell-sub">Company</span><b style="font-size:13px;">{{ $article->company->name ?? '—' }}</b></div>
        <div class="flex items-center justify-between" style="padding:9px 0;"><span class="cell-sub">Status</span><x-status-badge status="{{ ucfirst($article->status) }}" type="{{ $article->status === 'published' ? 'pos' : 'neutral' }}" /></div>

        @if (!empty($article->tags))
        <div class="divider"></div>
        <div class="cell-sub" style="margin-bottom:6px;">Tags</div>
        <div class="flex gap-8" style="flex-wrap:wrap;">
            @foreach ($article->tags as $tag)<span class="badge badge-neutral">{{ $tag }}</span>@endforeach
        </div>
        @endif

        @if (!empty($article->related_tools))
        <div class="divider"></div>
        <div class="cell-sub" style="margin-bottom:6px;">Related Tools</div>
        <div class="flex gap-8" style="flex-wrap:wrap;">
            @foreach ($article->related_tools as $tool)<span class="badge badge-neutral">{{ $tool }}</span>@endforeach
        </div>
        @endif
    </div>
</div>
@endsection
