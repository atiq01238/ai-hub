@extends('layouts.admin')
@section('title', $tool->name.' · AI Tool')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/pages/tools.css') }}">
@endpush

@section('content')
@php
    $statusType = $tool->status === 'published' ? 'pos' : ($tool->status === 'draft' ? 'warn' : 'neutral');
    $pricingModels = (array) ($tool->pricing_models ?? []);
    $platforms = (array) ($tool->platforms ?? []);
@endphp

<x-page-header title="{{ $tool->name }}" subtitle="Product profile, taxonomy, linked models and directory quality signals." :breadcrumb="['AI Management','AI Tools',$tool->name]">
    <x-slot:actions>
        <a href="{{ route('admin.tools.index') }}" class="btn btn-ghost btn-sm"><i data-lucide="arrow-left"></i> Back</a>
        @if(auth()->user()->canAccessModule('AI Tools','Edit'))
            <a href="{{ route('admin.tools.edit', $tool->id) }}" class="btn btn-primary btn-sm"><i data-lucide="pencil"></i> Edit Tool</a>
        @endif
    </x-slot:actions>
</x-page-header>

@if(session('status'))
    <div class="alert alert-success tools-alert"><i data-lucide="check-circle-2"></i><span>{{ session('status') }}</span></div>
@endif

<section class="tool-profile-hero card">
    @if($tool->cover_image_path)
        <div class="tool-profile-cover"><img src="{{ $tool->cover_image_url }}" alt="{{ $tool->name }} cover"></div>
    @endif
    <div class="tool-profile-main">
        <div class="tool-profile-logo">
            @if($tool->logo_path)<img src="{{ $tool->logo_url }}" alt="{{ $tool->name }} logo">@else<span>{{ Str::upper(Str::substr($tool->name, 0, 2)) }}</span>@endif
        </div>
        <div class="tool-profile-copy">
            <div class="tool-profile-badges"><x-status-badge status="{{ ucfirst($tool->status) }}" type="{{ $statusType }}" />@if($tool->category)<span class="badge badge-violet">{{ $tool->category->name }}</span>@endif</div>
            <h2>{{ $tool->name }}</h2>
            <p>{{ $tool->short_description ?: 'No short description has been added yet.' }}</p>
            <div class="tool-profile-links">
                @if($tool->website)<a href="{{ $tool->website }}" target="_blank" rel="noopener"><i data-lucide="external-link"></i> Visit Website</a>@endif
                @if($tool->company)<span><i data-lucide="building-2"></i>{{ $tool->company->name }}</span>@endif
                @if($tool->launch_date)<span><i data-lucide="calendar-days"></i>Launched {{ $tool->launch_date->format('M Y') }}</span>@endif
            </div>
        </div>
    </div>
</section>

<section class="tool-kpi-grid">
    <div class="tool-kpi card"><span><i data-lucide="star"></i></span><div><small>Rating</small><strong>{{ number_format((float)$tool->rating, 1) }}</strong></div></div>
    <div class="tool-kpi card"><span><i data-lucide="brain"></i></span><div><small>Connected Models</small><strong>{{ $tool->models_count }}</strong></div></div>
    <div class="tool-kpi card"><span><i data-lucide="message-square"></i></span><div><small>Reviews</small><strong>{{ $tool->reviews_count }}</strong></div></div>
    <div class="tool-kpi card"><span><i data-lucide="activity"></i></span><div><small>Directory State</small><strong>{{ ucfirst($tool->status) }}</strong></div></div>
</section>

<div class="tool-detail-grid">
    <main class="tool-detail-main">
        <section class="card tool-detail-card">
            <div class="tool-section-heading"><div><span class="tools-eyebrow">Product Intelligence</span><h3>Overview</h3></div><i data-lucide="file-text"></i></div>
            <div class="tool-long-copy">{!! nl2br(e($tool->description ?: $tool->short_description ?: 'No description yet.')) !!}</div>
            @if($tool->featureTerms->isNotEmpty())
                <div class="tool-token-section"><span>Capabilities</span><div>@foreach($tool->featureTerms as $feature)<span class="tool-token tool-token--feature"><i data-lucide="sparkles"></i>{{ $feature->name }}</span>@endforeach</div></div>
            @endif
        </section>

        <section class="card tool-detail-card tool-models-card">
            <div class="tool-section-heading"><div><span class="tools-eyebrow">Relationships</span><h3>Connected Models</h3></div><span class="tool-section-count">{{ $tool->models_count }}</span></div>
            <div class="table-wrap">
                <table class="data-table tools-table">
                    <thead><tr><th>Model</th><th>Version</th><th>Status</th><th></th></tr></thead>
                    <tbody>
                    @forelse($tool->models as $model)
                        <tr>
                            <td><strong class="tools-cell-primary">{{ $model->name }}</strong></td>
                            <td>{{ $model->version ?: '—' }}</td>
                            <td>{{ ucfirst($model->status) }}</td>
                            <td><a class="icon-btn" href="{{ route('admin.models.show', $model->id) }}" title="View model"><i data-lucide="arrow-up-right"></i></a></td>
                        </tr>
                    @empty
                        <tr><td colspan="4"><div class="tools-empty tools-empty--compact"><span><i data-lucide="brain-circuit"></i></span><h3>No models linked</h3><p>Connected AI models will appear here.</p></div></td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </main>

    <aside class="tool-detail-side">
        <section class="card tool-detail-card">
            <div class="tool-section-heading"><div><span class="tools-eyebrow">Directory Data</span><h3>Classification</h3></div><i data-lucide="network"></i></div>
            <dl class="tool-data-list">
                <div><dt>Company</dt><dd>{{ $tool->company?->name ?? 'Independent' }}</dd></div>
                <div><dt>Category</dt><dd>{{ $tool->category?->name ?? 'Uncategorized' }}</dd></div>
                <div><dt>Subcategory</dt><dd>{{ $tool->subcategoryTerm?->name ?? $tool->subcategory ?? '—' }}</dd></div>
                <div><dt>Published</dt><dd>{{ $tool->published_at?->format('M j, Y') ?? 'Not published' }}</dd></div>
                <div><dt>Slug</dt><dd class="mono">{{ $tool->slug }}</dd></div>
            </dl>
        </section>

        <section class="card tool-detail-card">
            <div class="tool-section-heading"><div><span class="tools-eyebrow">Availability</span><h3>Pricing & Platforms</h3></div><i data-lucide="layers-3"></i></div>
            <div class="tool-token-section tool-token-section--first"><span>Pricing Models</span><div>@forelse($pricingModels as $pricing)<span class="tool-token">{{ $pricing }}</span>@empty<span class="tools-cell-secondary">No pricing models set.</span>@endforelse</div></div>
            <div class="tool-token-section"><span>Platforms</span><div>@forelse($platforms as $platform)<span class="tool-token">{{ $platform }}</span>@empty<span class="tools-cell-secondary">No platforms set.</span>@endforelse</div></div>
        </section>

        <section class="card tool-detail-card">
            <div class="tool-section-heading"><div><span class="tools-eyebrow">Discovery</span><h3>Tags</h3></div><i data-lucide="tags"></i></div>
            <div class="tool-token-section tool-token-section--first"><div>@forelse($tool->tagTerms as $tag)<span class="tool-token">{{ $tag->name }}</span>@empty<span class="tools-cell-secondary">No tags assigned.</span>@endforelse</div></div>
        </section>
    </aside>
</div>
@endsection