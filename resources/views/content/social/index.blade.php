@extends('layouts.admin')
@section('title','Social Posts')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/pages/content.css') }}">
@endpush

@section('content')
@php
$platformIcons=['x'=>'twitter','facebook'=>'facebook','instagram'=>'instagram','linkedin'=>'linkedin','youtube'=>'youtube','tiktok'=>'music-2'];
$platformLabels=['x'=>'X','facebook'=>'Facebook','instagram'=>'Instagram','linkedin'=>'LinkedIn','youtube'=>'YouTube','tiktok'=>'TikTok'];
@endphp
<div class="content-page">
<x-page-header title="Social Content" subtitle="Draft, schedule and track social distribution derived from AI Hub intelligence." :breadcrumb="['Content','Social Posts']">
<x-slot:actions>@if(auth()->user()->canAccessModule('Content','Add'))<a href="{{ route('admin.content.social.create') }}" class="btn btn-primary"><i data-lucide="share-2"></i>New Post</a>@endif</x-slot:actions>
</x-page-header>
@if(session('status'))<div class="alert alert-success content-flash"><i data-lucide="check-circle-2"></i><span>{{ session('status') }}</span></div>@endif

<nav class="content-tabs">
<a href="{{ route('admin.content.social.index') }}" class="{{ !request('platform')?'is-active':'' }}">All</a>
@foreach($platformIcons as $key=>$icon)<a href="{{ route('admin.content.social.index',['platform'=>$key]) }}" class="{{ request('platform')===$key?'is-active':'' }}"><i data-lucide="{{ $icon }}"></i>{{ $platformLabels[$key] }}</a>@endforeach
</nav>

<section class="card content-news-convert">
<span class="content-news-convert__icon"><i data-lucide="wand-sparkles"></i></span>
<div><span class="content-eyebrow">News → Social</span><h2>Turn recent intelligence into a draft</h2><p>Choose a news item to pre-fill a new social post.</p></div>
<select class="select" onchange="if(this.value) window.location='{{ route('admin.content.social.create') }}?news_id='+this.value"><option value="">Choose recent news...</option>@foreach($recentNews as $news)<option value="{{ $news->id }}">{{ $news->headline }}</option>@endforeach</select>
</section>

<section class="content-social-grid">
@forelse($posts as $post)
<article class="card content-social-card">
<div class="content-social-card__head"><span class="content-platform"><i data-lucide="{{ $platformIcons[$post->platform] }}"></i>{{ $platformLabels[$post->platform] }}</span><x-status-badge status="{{ ucfirst($post->status) }}" type="{{ $post->status==='published'?'pos':($post->status==='scheduled'?'info':'neutral') }}" /></div>
@if($post->image_path)<img class="content-social-card__image" src="{{ $post->image_url }}" alt="">@else<div class="content-social-card__placeholder"><i data-lucide="image"></i></div>@endif
<p>{{ \Illuminate\Support\Str::limit($post->content,170) }}</p>
<div class="content-social-card__meta">{{ $post->status==='published' ? ($post->published_at?->format('M j, g:i A') ?? 'Published') : ($post->status==='scheduled' ? ($post->scheduled_at?->format('M j, g:i A') ?? 'Schedule pending') : 'Draft') }}@if($post->newsItem) · from “{{ \Illuminate\Support\Str::limit($post->newsItem->headline,34) }}”@endif</div>
<div class="content-social-card__actions">@if(auth()->user()->canAccessModule('Content','Edit'))<a href="{{ route('admin.content.social.edit',$post->id) }}" class="btn btn-secondary btn-sm"><i data-lucide="pencil"></i>Edit</a>@endif @if(auth()->user()->canAccessModule('Content','Delete'))<form action="{{ route('admin.content.social.destroy',$post->id) }}" method="POST" onsubmit="return confirm('Delete this post?')">@csrf @method('DELETE')<button class="icon-btn icon-btn--danger"><i data-lucide="trash-2"></i></button></form>@endif</div>
</article>
@empty
<div class="card content-empty content-empty--grid"><span><i data-lucide="share-2"></i></span><h3>No social posts yet</h3><p>Create the first distribution draft.</p></div>
@endforelse
</section>
<div class="content-pagination"><span>{{ number_format($posts->total()) }} posts</span><div>{{ $posts->links() }}</div></div>
</div>
@endsection
