@extends('layouts.admin')
@section('title', 'Social Posts')

@php
    use Illuminate\Support\Facades\Storage;
    $platformIcons = ['x'=>'twitter','facebook'=>'facebook','instagram'=>'instagram','linkedin'=>'linkedin','youtube'=>'youtube','tiktok'=>'music-2'];
    $platformLabels = ['x'=>'X','facebook'=>'Facebook','instagram'=>'Instagram','linkedin'=>'LinkedIn','youtube'=>'YouTube','tiktok'=>'TikTok'];
@endphp

@section('content')

<x-page-header title="Social Content Management" subtitle="{{ $posts->total() }} posts" :breadcrumb="['Content', 'Social Posts']">
    <x-slot:actions><a href="{{ route('admin.content.social.create') }}" class="btn btn-primary btn-sm"><i data-lucide="share-2"></i> New Post</a></x-slot:actions>
</x-page-header>

@if (session('status'))
    <div class="alert alert-success" style="margin-bottom:16px;">{{ session('status') }}</div>
@endif

<div class="filter-bar">
    <a href="{{ route('admin.content.social.index') }}" class="chip {{ !request('platform') ? 'is-active' : '' }}">All</a>
    @foreach ($platformIcons as $key => $icon)
        <a href="{{ route('admin.content.social.index', ['platform' => $key]) }}" class="chip {{ request('platform') === $key ? 'is-active' : '' }}"><i data-lucide="{{ $icon }}" style="width:13px;height:13px;"></i> {{ $platformLabels[$key] }}</a>
    @endforeach
</div>

<div class="card card-pad" style="margin-bottom:20px; background:linear-gradient(135deg, rgba(91,127,255,.08), rgba(139,92,246,.08)); border-color:var(--brand-1);">
    <div class="flex items-center gap-12">
        <div class="kpi-icon" style="width:40px;height:40px;"><i data-lucide="wand-2"></i></div>
        <div style="flex:1;">
            <b>Turn News Into Social Post</b>
            <div class="text-sub" style="font-size:12.5px;">Pick a recent news item to pre-fill a draft post.</div>
        </div>
        <select onchange="if(this.value) window.location = '{{ route('admin.content.social.create') }}?news_id=' + this.value" class="select" style="max-width:260px;">
            <option value="">Choose news...</option>
            @foreach ($recentNews as $news)
                <option value="{{ $news->id }}">{{ $news->headline }}</option>
            @endforeach
        </select>
    </div>
</div>

<div class="grid-3">
@forelse ($posts as $post)
    <div class="card card-pad">
        <div class="flex items-center justify-between" style="margin-bottom:10px;">
            <span class="badge badge-neutral"><i data-lucide="{{ $platformIcons[$post->platform] }}" style="width:11px;height:11px;"></i> {{ $platformLabels[$post->platform] }}</span>
            <x-status-badge status="{{ ucfirst($post->status) }}" type="{{ $post->status === 'published' ? 'pos' : ($post->status === 'scheduled' ? 'info' : 'neutral') }}" />
        </div>
        @if ($post->image_path)
            <img src="{{ Storage::url($post->image_path) }}" alt="" style="width:100%; height:120px; object-fit:cover; border-radius:10px; margin-bottom:10px;">
        @else
            <div class="thumb lg" style="width:100%; height:120px; border-radius:10px; margin-bottom:10px;"><i data-lucide="image"></i></div>
        @endif
        <p style="font-size:13px; line-height:1.5; margin:0 0 10px;">{{ \Illuminate\Support\Str::limit($post->content, 140) }}</p>
        <div class="cell-sub" style="margin-bottom:10px;">
            {{ $post->status === 'published' ? $post->published_at?->format('M j, g:i A') : ($post->status === 'scheduled' ? $post->scheduled_at?->format('M j, g:i A') : '—') }}
            @if ($post->newsItem) · from "{{ \Illuminate\Support\Str::limit($post->newsItem->headline, 30) }}" @endif
        </div>
        <div class="flex gap-8">
            <a href="{{ route('admin.content.social.edit', $post->id) }}" class="btn btn-ghost btn-sm">Edit</a>
            <form action="{{ route('admin.content.social.destroy', $post->id) }}" method="POST" onsubmit="return confirm('Delete this post?')">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-ghost btn-sm">Delete</button>
            </form>
        </div>
    </div>
@empty
    <div class="card card-pad text-sub" style="text-align:center; padding:32px; grid-column:1/-1;">No posts yet — create your first one.</div>
@endforelse
</div>

<div class="pager" style="margin-top:16px;">{{ $posts->links() }}</div>
@endsection
