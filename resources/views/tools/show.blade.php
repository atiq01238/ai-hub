@extends('layouts.admin')
@section('title', $tool->name . ' · Tool Detail')

@php
    use Illuminate\Support\Facades\Storage;
@endphp

@section('content')

<x-page-header
    title="{{ $tool->name }}"
    subtitle="{{ $tool->company->name ?? '—' }} · {{ $tool->category->name ?? '—' }} · {{ ucfirst($tool->status) }}"
    :breadcrumb="['AI Management', 'AI Tools', $tool->name]">
    <x-slot:actions>
        <button class="btn btn-secondary btn-sm" data-bs-toggle="modal" data-bs-target="#previewModal"><i data-lucide="eye"></i> Preview</button>
        <a href="{{ route('admin.tools.edit', $tool->id) }}" class="btn btn-primary btn-sm"><i data-lucide="pencil"></i> Edit Tool</a>
        {{-- Public link a signed-in visitor would use to leave a rating --}}
        <a href="{{ route('reviews.create', $tool->id) }}" class="btn btn-secondary btn-sm" target="_blank"><i data-lucide="star"></i> Leave a Review (preview)</a>
    </x-slot:actions>
</x-page-header>

@if (session('status'))
    <div class="alert alert-success" style="margin-bottom:16px;">{{ session('status') }}</div>
@endif

<div class="kpi-grid" style="grid-template-columns:repeat(4,1fr); margin-bottom:20px;">
    <x-kpi-card icon="eye" label="Total Views" value="—" delta="" trend="up" />
    <x-kpi-card icon="star" label="Avg Rating" value="{{ number_format($tool->rating, 1) }}" delta="" trend="up" />
    <x-kpi-card icon="columns-3" label="Comparisons" value="—" delta="" trend="up" />
    <x-kpi-card icon="message-square-heart" label="Reviews" value="—" delta="" trend="up" />
</div>
{{-- Views / Comparisons / Reviews counts aren't tracked in the database yet —
     those need their own tables (page_views, comparisons, reviews) later. --}}

<div class="tabs">
    <div class="tab is-active">Overview</div>
    <div class="tab">Pricing</div>
    <div class="tab">Reviews</div>
    <div class="tab">Comparisons</div>
    <div class="tab">Related News</div>
    <div class="tab">Analytics</div>
</div>

<div class="grid-12">
    <div class="col-8 card card-pad">
        @if ($tool->logo_path || $tool->cover_image_path)
        <div class="flex items-center gap-12" style="margin-bottom:16px;">
            @if ($tool->logo_path)
                <img src="{{ Storage::url($tool->logo_path) }}" alt="{{ $tool->name }} logo" style="width:48px;height:48px;border-radius:10px;object-fit:cover;">
            @endif
            @if ($tool->cover_image_path)
                <img src="{{ Storage::url($tool->cover_image_path) }}" alt="{{ $tool->name }} cover" style="height:48px;border-radius:8px;object-fit:cover;">
            @endif
        </div>
        @endif
        <div class="section-title">About {{ $tool->name }}</div>
        <p class="text-sub" style="font-size:13.5px; line-height:1.7;">
            {{ $tool->description ?: $tool->short_description ?: 'No description added yet.' }}
        </p>

        @if ($tool->website)
            <div class="divider"></div>
            <div class="section-title">Website</div>
            <a href="{{ $tool->website }}" target="_blank" rel="noopener">{{ $tool->website }}</a>
        @endif

        <div class="divider"></div>
        <div class="section-title">Pricing</div>
        <div class="flex gap-8" style="flex-wrap:wrap;">
            @forelse ($tool->pricing_models ?? [] as $model)
                <span class="badge badge-neutral">{{ $model }}</span>
            @empty
                <span class="text-sub">Not set</span>
            @endforelse
        </div>

        <div class="divider"></div>
        <div class="section-title">Capabilities</div>
        <div class="flex gap-8" style="flex-wrap:wrap;">
            @forelse ($tool->capabilities ?? [] as $c)
                <span class="badge badge-neutral">{{ $c }}</span>
            @empty
                <span class="text-sub">Not set</span>
            @endforelse
        </div>

        <div class="divider"></div>
        <div class="section-title">Platforms</div>
        <div class="flex gap-8" style="flex-wrap:wrap;">
            @forelse ($tool->platforms ?? [] as $p)
                <span class="badge badge-neutral">{{ $p }}</span>
            @empty
                <span class="text-sub">Not set</span>
            @endforelse
        </div>

        @if (!empty($tool->tags))
        <div class="divider"></div>
        <div class="section-title">Tags</div>
        <div class="flex gap-8" style="flex-wrap:wrap;">
            @foreach ($tool->tags as $tag)
                <span class="badge badge-neutral">{{ $tag }}</span>
            @endforeach
        </div>
        @endif
    </div>

    <div class="col-4 card card-pad">
        <div class="section-title">Ratings Breakdown</div>
        @php
            // Falls back to placeholder numbers until reviews feed real scores in.
            $breakdown = $tool->rating_breakdown ?: ['Quality' => 0, 'Speed' => 0, 'Features' => 0, 'Ease of Use' => 0, 'Value' => 0];
        @endphp
        @foreach ($breakdown as $label => $val)
        <div style="margin-bottom:12px;">
            <div class="flex items-center justify-between" style="margin-bottom:5px;"><span class="text-sub" style="font-size:12.5px;">{{ is_string($label) ? $label : $label }}</span><span class="mono" style="font-size:12.5px;">{{ $val }}</span></div>
            <div class="progress"><span style="width:{{ $val }}%;"></span></div>
        </div>
        @endforeach

        <div class="divider"></div>
        <div class="section-title">Details</div>
        <div class="text-sub" style="font-size:12.5px; line-height:2;">
            <div>Launch date: {{ $tool->launch_date?->format('M j, Y') ?? '—' }}</div>
            <div>Subcategory: {{ $tool->subcategory ?? '—' }}</div>
            <div>Popularity: {{ $tool->popularity }}%</div>
            <div>Slug: {{ $tool->slug }}</div>
        </div>
    </div>
</div>

@endsection
