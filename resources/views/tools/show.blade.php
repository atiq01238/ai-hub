@extends('layouts.admin')
@section('title', $tool->name.' · AI Tool')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/pages/tools.css') }}">
@endpush

@section('content')
@php
    $statusType = $tool->status === 'published' ? 'pos' : ($tool->status === 'draft' ? 'warn' : 'neutral');
    $pricingModels = (array) ($tool->pricing_models ?? []);
    $platforms = $tool->platformTerms->pluck('name')->all();
    if (!$platforms) $platforms = (array) ($tool->platforms ?? []);
    $primarySource = $tool->sources->firstWhere('is_primary', true) ?: $tool->sources->first();
    $sourceMap = $tool->sources->keyBy('id');
    $verifiedFeatureCount = $tool->featureTerms->filter(fn($feature) => ($feature->pivot?->verification_status ?? 'pending') === 'verified')->count();
    $verifiedUseCaseCount = $tool->useCaseTerms->filter(fn($useCase) => ($useCase->pivot?->verification_status ?? 'pending') === 'verified')->count();
    $technical = $tool->technicalProfile;
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
            <div class="tool-profile-badges"><x-status-badge status="{{ ucfirst($tool->status) }}" type="{{ $statusType }}" />@if($tool->category)<span class="badge badge-violet">{{ $tool->category->name }}</span>@endif @if(($tool->product_status ?? 'unknown') !== 'unknown')<span class="badge {{ $tool->product_status_verified_at ? 'badge-green' : 'badge-amber' }}">{{ $tool->product_status_label }}{{ $tool->product_status_verified_at ? ' · Verified' : ' · Pending' }}</span>@endif</div>
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
    <div class="tool-kpi card"><span><i data-lucide="shield-check"></i></span><div><small>Profile Completeness</small><strong>{{ $dataConfidence['profile_completeness'] }}%</strong></div></div>
</section>

<div class="tool-detail-grid">
    <main class="tool-detail-main">
        <section class="card tool-detail-card">
            <div class="tool-section-heading"><div><span class="tools-eyebrow">Product Intelligence</span><h3>Overview</h3></div><i data-lucide="file-text"></i></div>
            <div class="tool-long-copy">{!! nl2br(e($tool->description ?: $tool->short_description ?: 'No description yet.')) !!}</div>
            @if($tool->featureTerms->isNotEmpty())
                <div class="tool-token-section"><span>Capabilities</span><div>@foreach($tool->featureTerms as $feature)<span class="tool-token tool-token--feature" title="{{ ($feature->pivot?->verification_status ?? 'pending') === 'verified' ? 'Verified capability mapping' : 'Evidence pending' }}"><i data-lucide="{{ ($feature->pivot?->verification_status ?? 'pending') === 'verified' ? 'badge-check' : 'sparkles' }}"></i>{{ $feature->name }}</span>@endforeach</div></div>
            @endif
        </section>

        <section class="card tool-detail-card">
            <div class="tool-section-heading"><div><span class="tools-eyebrow">Evidence Intelligence</span><h3>Capabilities & Best-for Verification</h3></div><i data-lucide="badge-check"></i></div>
            <div class="tool-intelligence-summary">
                <span><b>{{ $verifiedFeatureCount }}/{{ $tool->featureTerms->count() }}</b> verified capabilities</span>
                <span><b>{{ $verifiedUseCaseCount }}/{{ $tool->useCaseTerms->count() }}</b> verified use cases</span>
            </div>
            <div class="tool-profile-intelligence-table">
                @forelse($tool->featureTerms as $feature)
                    @php $evidence = !empty($feature->pivot?->tool_source_id) ? $sourceMap->get($feature->pivot->tool_source_id) : null; @endphp
                    <div><span><i data-lucide="sparkles"></i><strong>{{ $feature->name }}</strong><small>{{ $feature->pivot?->description ?: ($feature->short_description ?: 'Canonical capability; tool-specific description pending.') }}</small></span><em class="{{ ($feature->pivot?->verification_status ?? 'pending') === 'verified' ? 'is-verified' : '' }}">{{ ucfirst($feature->pivot?->verification_status ?? 'pending') }}</em>@if($evidence)<a href="{{ $evidence->source_url }}" target="_blank" rel="noopener noreferrer"><i data-lucide="external-link"></i></a>@endif</div>
                @empty
                    <span class="tools-cell-secondary">No structured capability mappings.</span>
                @endforelse
            </div>
            @if($tool->useCaseTerms->isNotEmpty())
                <div class="tool-token-section"><span>Best-for / Use Cases</span><div>@foreach($tool->useCaseTerms as $useCase)<span class="tool-token" title="{{ $useCase->pivot?->fit_note ?: 'Fit note pending' }}"><i data-lucide="{{ ($useCase->pivot?->verification_status ?? 'pending') === 'verified' ? 'badge-check' : 'target' }}"></i>{{ $useCase->name }}</span>@endforeach</div></div>
            @endif
        </section>


        <section class="card tool-detail-card">
            <div class="tool-section-heading"><div><span class="tools-eyebrow">Advanced Intelligence</span><h3>Technical, Privacy & Security Profile</h3></div><i data-lucide="scan-search"></i></div>
            @if($technical)
                <dl class="tool-data-list">
                    <div><dt>API</dt><dd>{{ \App\Models\ToolTechnicalProfile::API_STATUSES[$technical->api_status] ?? Str::headline($technical->api_status) }}</dd></div>
                    <div><dt>Open Source</dt><dd>{{ \App\Models\ToolTechnicalProfile::OPEN_SOURCE_STATUSES[$technical->open_source_status] ?? Str::headline($technical->open_source_status) }}@if($technical->license_name) · {{ $technical->license_name }}@endif</dd></div>
                    <div><dt>Self Hosting</dt><dd>{{ \App\Models\ToolTechnicalProfile::SELF_HOSTING_STATUSES[$technical->self_hosting_status] ?? Str::headline($technical->self_hosting_status) }}</dd></div>
                    <div><dt>Commercial Use</dt><dd>{{ \App\Models\ToolTechnicalProfile::COMMERCIAL_USE_STATUSES[$technical->commercial_use_status] ?? Str::headline($technical->commercial_use_status) }}</dd></div>
                    <div><dt>Training Policy</dt><dd>{{ \App\Models\ToolTechnicalProfile::TRAINING_POLICIES[$technical->data_training_policy] ?? Str::headline($technical->data_training_policy) }}</dd></div>
                    <div><dt>SSO / SAML</dt><dd>{{ \App\Models\ToolTechnicalProfile::SSO_STATUSES[$technical->sso_status] ?? Str::headline($technical->sso_status) }}</dd></div>
                </dl>
                @if(!empty($technical->deployment_modes))<div class="tool-token-section"><span>Deployment</span><div>@foreach($technical->deployment_modes as $mode)<span class="tool-token">{{ $mode }}</span>@endforeach</div></div>@endif
                @if(!empty($technical->security_certifications) || !empty($technical->compliance_certifications))<div class="tool-token-section"><span>Security / Compliance</span><div>@foreach(array_merge($technical->security_certifications ?? [], $technical->compliance_certifications ?? []) as $item)<span class="tool-token">{{ $item }}</span>@endforeach</div></div>@endif
            @else
                <span class="tools-cell-secondary">Advanced profile row has not been backfilled yet.</span>
            @endif
            <div class="tool-token-section"><span>Integrations</span><div>@forelse($tool->integrationTerms as $integration)<span class="tool-token" title="{{ ucfirst($integration->pivot?->verification_status ?? 'pending') }}">{{ $integration->name }}</span>@empty<span class="tools-cell-secondary">No structured integrations recorded yet.</span>@endforelse</div></div>
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
                <div><dt>Product Status</dt><dd>{{ $tool->product_status_label }}@if($tool->product_status_verified_at) · Verified {{ $tool->product_status_verified_at->format('M j, Y') }}@endif</dd></div>
                @if($tool->product_status_note)<div><dt>Lifecycle Note</dt><dd>{{ $tool->product_status_note }}</dd></div>@endif
                <div><dt>Slug</dt><dd class="mono">{{ $tool->slug }}</dd></div>
            </dl>
        </section>

        <section class="card tool-detail-card">
            <div class="tool-section-heading"><div><span class="tools-eyebrow">Availability</span><h3>Pricing & Platforms</h3></div><i data-lucide="layers-3"></i></div>
            <div class="tool-token-section tool-token-section--first"><span>Pricing Models</span><div>@forelse($pricingModels as $pricing)<span class="tool-token">{{ $pricing }}</span>@empty<span class="tools-cell-secondary">No pricing models set.</span>@endforelse</div></div>
            <div class="tool-token-section"><span>Platforms</span><div>@forelse($platforms as $platform)<span class="tool-token">{{ $platform }}</span>@empty<span class="tools-cell-secondary">No platforms set.</span>@endforelse</div></div>
        </section>

        <section class="card tool-detail-card">
            <div class="tool-section-heading"><div><span class="tools-eyebrow">Trust Layer</span><h3>AI Orbit Verification</h3></div><i data-lucide="shield-check"></i></div>
            <dl class="tool-data-list">
                <div><dt>Status</dt><dd>{{ $dataConfidence['can_show_confidence'] ? 'Evidence-backed' : 'Verification pending' }}</dd></div>
                <div><dt>Profile completeness</dt><dd>{{ $dataConfidence['profile_completeness'] }}% · {{ $dataConfidence['profile_completeness_label'] }}</dd></div>
                <div><dt>Evidence confidence</dt><dd>{{ $dataConfidence['can_show_confidence'] ? (($dataConfidence['confidence_score'] ?? 0).'/100 · '.$dataConfidence['confidence_label']) : 'Pending — needs 1 verified source + 2 verified claims' }}</dd></div>
                <div><dt>Freshness</dt><dd>{{ Str::headline($dataConfidence['freshness']) }}</dd></div>
                <div><dt>Verified sources</dt><dd>{{ $dataConfidence['verified_sources'] }}/{{ $dataConfidence['total_sources'] }}</dd></div>
                <div><dt>Verified claims</dt><dd>{{ $dataConfidence['verified_claims'] }}/{{ $dataConfidence['known_claims'] }}</dd></div>
                <div><dt>Last verified</dt><dd>{{ $dataConfidence['last_verified_at']?->format('M j, Y') ?? 'Not yet verified' }}</dd></div>
            </dl>
        </section>

        <section class="card tool-detail-card">
            <div class="tool-section-heading"><div><span class="tools-eyebrow">Evidence</span><h3>Source Verification</h3></div><i data-lucide="badge-check"></i></div>
            @if($primarySource)
                <dl class="tool-data-list">
                    <div><dt>Status</dt><dd>{{ ucfirst($primarySource->verification_status) }}</dd></div>
                    <div><dt>Type</dt><dd>{{ Str::headline($primarySource->source_type) }}</dd></div>
                    <div><dt>Verified</dt><dd>{{ $primarySource->verified_at?->format('M j, Y') ?? 'Pending' }}</dd></div>
                </dl>
                <div class="tool-profile-links"><a href="{{ $primarySource->source_url }}" target="_blank" rel="noopener noreferrer"><i data-lucide="external-link"></i> Open source</a></div>
            @else
                <span class="tools-cell-secondary">No evidence source saved yet. Run the V3 source backfill or add an official source in Edit Tool.</span>
            @endif
        </section>

        <section class="card tool-detail-card">
            <div class="tool-section-heading"><div><span class="tools-eyebrow">Discovery</span><h3>Tags</h3></div><i data-lucide="tags"></i></div>
            <div class="tool-token-section tool-token-section--first"><div>@forelse($tool->tagTerms as $tag)<span class="tool-token">{{ $tag->name }}</span>@empty<span class="tools-cell-secondary">No tags assigned.</span>@endforelse</div></div>
        </section>
    </aside>
</div>
@endsection