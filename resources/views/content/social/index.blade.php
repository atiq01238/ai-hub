@extends('layouts.admin')
@section('title', 'Social Posts')

@section('content')

<x-page-header title="Social Content Management" subtitle="Schedule and publish across all platforms" :breadcrumb="['Content', 'Social Posts']">
    <x-slot:actions><button class="btn btn-primary btn-sm"><i data-lucide="share-2"></i> New Post</button></x-slot:actions>
</x-page-header>

<div class="filter-bar">
    @foreach(['X'=>'twitter','Facebook'=>'facebook','Instagram'=>'instagram','LinkedIn'=>'linkedin','YouTube'=>'youtube','TikTok'=>'music-2'] as $platform => $icon)
        <span class="chip"><i data-lucide="{{ $icon }}" style="width:13px;height:13px;"></i> {{ $platform }}</span>
    @endforeach
</div>

<div class="card card-pad" style="margin-bottom:20px; background:linear-gradient(135deg, rgba(91,127,255,.08), rgba(139,92,246,.08)); border-color:var(--brand-1);">
    <div class="flex items-center gap-12">
        <div class="kpi-icon" style="width:40px;height:40px;"><i data-lucide="wand-2"></i></div>
        <div style="flex:1;">
            <b>Turn News Into Social Post</b>
            <div class="text-sub" style="font-size:12.5px;">Instantly draft a post from a breaking AI news item.</div>
        </div>
        <button class="btn btn-primary btn-sm">Choose News <i data-lucide="arrow-right"></i></button>
    </div>
</div>

<div class="grid-3">
    @php
    $posts = [
        ['platform'=>'X','icon'=>'twitter','text'=>'OpenAI just dropped GPT-5.2 Turbo with native agent orchestration 🤯 Full breakdown on the blog →','status'=>'Scheduled','time'=>'Today, 3:00 PM'],
        ['platform'=>'LinkedIn','icon'=>'linkedin','text'=>'Anthropic opens Claude Opus 4.8 to enterprise API customers with new batch pricing.','status'=>'Published','time'=>'Aug 5, 9:15 AM'],
        ['platform'=>'Instagram','icon'=>'instagram','text'=>'5 AI video tools you need to try this month 🎬','status'=>'Draft','time'=>'—'],
    ];
    @endphp
    @foreach($posts as $p)
    <div class="card card-pad">
        <div class="flex items-center justify-between" style="margin-bottom:10px;">
            <span class="badge badge-neutral"><i data-lucide="{{ $p['icon'] }}" style="width:11px;height:11px;"></i> {{ $p['platform'] }}</span>
            <x-status-badge :status="$p['status']" :type="$p['status']==='Published' ? 'pos' : ($p['status']==='Scheduled' ? 'info' : 'neutral')" />
        </div>
        <div class="thumb lg" style="width:100%; height:120px; border-radius:10px; margin-bottom:10px;"><i data-lucide="image"></i></div>
        <p style="font-size:13px; line-height:1.5; margin:0 0 10px;">{{ $p['text'] }}</p>
        <div class="cell-sub">{{ $p['time'] }}</div>
    </div>
    @endforeach
</div>
@endsection
