@extends('frontend.layouts.app')
@section('title', html_entity_decode($seo['title'], ENT_QUOTES | ENT_HTML5, 'UTF-8') . ' | AI Orbit')
@section('meta_description', html_entity_decode($seo['description'], ENT_QUOTES | ENT_HTML5, 'UTF-8'))
@section('canonical', route('models.show', $model))
@section('robots', 'index,follow,max-image-preview:large,max-snippet:-1,max-video-preview:-1')
@section('og_type', 'website')
@section('og_image', $model->logo_url)
@push('head')
@foreach($seoSchemas as $schema)
    @php
        $schemaWithContext = array_merge(
            ['@' . 'context' => 'https://schema.org'],
            $schema
        );
    @endphp

    <script type="application/ld+json">{!! json_encode(
        $schemaWithContext,
        JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
    ) !!}</script>
@endforeach
@endpush
@push('styles')<link rel="stylesheet" href="{{ asset('css/frontend/models.css') }}?v=20260903-p56">@endpush
@section('content')
<section class="model-detail-hero model-detail-hero-wave">
<div class="model-detail-wave-art" aria-hidden="true"></div>
<div class="model-detail-wave-shade" aria-hidden="true"></div>
<div class="model-detail-logo-aura" aria-hidden="true" style="background-image:url('{{ $model->logo_url }}')"></div>
<div class="model-wrap"><div class="model-breadcrumb"><a href="{{ route('home') }}">Home</a><i data-lucide="chevron-right"></i><a href="{{ route('models.index') }}">AI Models</a>@if($model->company && in_array($model->company->status, ['active','acquired'], true))<i data-lucide="chevron-right"></i><a href="{{ route('companies.show',$model->company) }}">{{ $model->company->name }}</a>@endif<i data-lucide="chevron-right"></i><span>{{ $model->name }}</span></div><div class="model-detail-main"><div class="model-detail-id"><img src="{{ $model->logo_url }}" alt="{{ $model->name }} AI model logo"><div><div class="detail-badges"><span class="status-pill {{ $model->status }}">{{ ucfirst($model->status) }}</span>@if($model->release_date)<span>Released {{ $model->release_date->format('M j, Y') }}</span>@endif @if($lastUpdated)<span><i data-lucide="shield-check"></i> Updated {{ $lastUpdated->format('M j, Y') }}</span>@endif</div><h1>{{ $model->name }}</h1><p>By @if($model->company && in_array($model->company->status, ['active','acquired'], true))<a class="model-provider-link" href="{{ route('companies.show',$model->company) }}">{{ $model->company->name }}</a>@else<strong>{{ $model->company?->name ?? 'Independent' }}</strong>@endif @if($model->version) · {{ $model->version }}@endif</p></div></div><div class="model-detail-actions"><button type="button" class="save-item-btn detail-save" data-save-item data-save-type="model" data-save-id="{{ $model->id }}" aria-pressed="false"><i data-lucide="bookmark"></i><span data-save-label data-default-label="Save">Save</span></button><a href="{{ route('comparisons.builder', ['type' => 'model', 'item' => $model->id]) }}"><i data-lucide="scale"></i> Compare</a><a href="#benchmarks"><i data-lucide="bar-chart-3"></i> Benchmarks</a>@if($model->tool && $model->tool->status === 'published')<a class="primary" href="{{ route('tools.show',$model->tool) }}">View {{ $model->tool->name }} <i data-lucide="arrow-up-right"></i></a>@endif</div></div>
@include('frontend.partials.quick-rating', [
    'type' => 'model',
    'id' => $model->id,
    'summary' => $quickRating,
    'label' => 'Rate '.$model->name,
])
@php
    $isTokenPricing = $model->hasTokenPricing();
    $evidenceSources = $evidenceSources ?? collect();
    $pricingSourcesForView = $model->pricingSources ?? collect();
    $pricingSourceCount = $pricingSourcesForView->count();
    $pricingEvidence = $evidenceSources->firstWhere('evidence_type', 'pricing');
    $lastPricingCheck = collect([
        $model->pricing_verified_at,
        $pricingSourcesForView->pluck('last_checked_at')->filter()->sortDesc()->first(),
    ])->filter()->sortDesc()->first();
@endphp
<div class="detail-metrics">
    <div><span>{{ $benchmarkPrimaryClass ? \App\Models\Benchmark::classLabel($benchmarkPrimaryClass).' composite' : 'Benchmark composite' }}</span><strong>{{ $model->benchmark_score !== null ? number_format((float)$model->benchmark_score,1) : '—' }}</strong><small>{{ $model->benchmark_score !== null ? '/100 verified composite' : 'No verified claim' }}</small></div>
    <div><span>Context window</span><strong>{{ $model->context_window ?: '—' }}</strong><small>{{ $model->context_window ? 'tokens' : 'Not applicable / not pinned' }}</small></div>
    @if($isTokenPricing)
        <div><span>Input price</span><strong>{{ $model->input_price_per_million !== null ? '$'.number_format((float)$model->input_price_per_million,2) : '—' }}</strong><small>per 1M tokens</small></div>
        <div><span>Output price</span><strong>{{ $model->output_price_per_million !== null ? '$'.number_format((float)$model->output_price_per_million,2) : '—' }}</strong><small>per 1M tokens</small></div>
    @else
        <div><span>Pricing model</span><strong class="metric-text">{{ $model->pricing_type_label }}</strong><small>{{ $model->pricing_unit_label ?: 'Provider terms' }}</small></div>
        <div><span>Pricing evidence</span><strong class="metric-text">{{ $model->pricing_verification_label }}</strong><small>{{ $model->pricing_verified_at?->format('M j, Y') ?? 'Not verified' }}</small></div>
    @endif
</div></div></section>
<nav class="model-detail-nav"><div class="model-wrap"><a href="#overview">Overview</a><a href="#capabilities">Capabilities</a><a href="#benchmarks">Benchmarks</a><a href="#pricing">Pricing</a><a href="#evidence">Evidence</a>@if($relatedComparisons->isNotEmpty())<a href="#comparisons">Comparisons</a>@endif<a href="#related">Related models</a></div></nav>
<section class="model-detail-body"><div class="model-wrap detail-layout"><main><section id="overview" class="detail-block"><span class="section-kicker">MODEL OVERVIEW</span><h2>About {{ $model->name }}</h2><div class="detail-lead model-intelligence-copy"><p>{{ $contentSeo['intro'] }}</p>@if($contentSeo['profile_summary'])<p>{{ $contentSeo['profile_summary'] }}</p>@endif @if($contentSeo['capability_summary'])<p>{{ $contentSeo['capability_summary'] }}</p>@endif @if($contentSeo['performance_summary'])<p>{{ $contentSeo['performance_summary'] }}</p>@endif @if($contentSeo['pricing_summary'])<p>{{ $contentSeo['pricing_summary'] }}</p>@endif @if($contentSeo['ecosystem_summary'])<p>{{ $contentSeo['ecosystem_summary'] }}</p>@endif</div>@if($contentSeo['facts']->isNotEmpty())<div class="model-knowledge-facts" aria-label="{{ $model->name }} key facts">@foreach($contentSeo['facts'] as $fact)<div><span>{{ $fact['label'] }}</span><strong>{{ $fact['value'] }}</strong></div>@endforeach</div>@endif<div class="spec-table"><div><span>Provider</span><strong>@if($model->company && in_array($model->company->status, ['active','acquired'], true))<a href="{{ route('companies.show',$model->company) }}">{{ $model->company->name }}</a>@else{{ $model->company?->name ?? '—' }}@endif</strong></div><div><span>Version</span><strong>{{ $model->version ?: '—' }}</strong></div><div><span>Release date</span><strong>{{ $model->release_date?->format('F j, Y') ?? '—' }}</strong></div><div><span>Status</span><strong>{{ ucfirst($model->status) }}</strong></div><div><span>Context window</span><strong>{{ $model->context_window ?: '—' }}</strong></div><div><span>Associated product</span><strong>@if($model->tool && $model->tool->status === 'published')<a href="{{ route('tools.show',$model->tool) }}">{{ $model->tool->name }}</a>@else{{ $model->tool?->name ?? '—' }}@endif</strong></div></div></section>
<section class="model-trust-panel" aria-label="AI Orbit model confidence">
    <div class="model-trust-score {{ $modelConfidence['class'] }}">
        <span class="trust-icon"><i data-lucide="shield-check"></i></span>
        <div><small>AI ORBIT MODEL CONFIDENCE</small><strong>{{ $modelConfidence['score'] }}<em>/100</em></strong><span>{{ $modelConfidence['label'] }}</span></div>
    </div>
    <div class="model-trust-meter"><i style="width:{{ $modelConfidence['score'] }}%"></i></div>
    <div class="model-trust-checks">
        @foreach($modelConfidence['checks'] as $check)
            @if($check['applicable'])
            <div class="{{ $check['verified'] ? 'verified' : 'missing' }}">
                <i data-lucide="{{ $check['verified'] ? 'check-circle-2' : 'circle-alert' }}"></i>
                <span><strong>{{ $check['label'] }}</strong><small>{{ $check['detail'] }}</small></span>
            </div>
            @endif
        @endforeach
    </div>
</section>
<section id="capabilities" class="detail-block"><span class="section-kicker">WHAT IT CAN DO</span><h2>Capabilities & use cases</h2><div class="capability-grid">@if($model->featureTerms->isNotEmpty()) @foreach($model->featureTerms as $feature)<a class="taxonomy-cap-card" href="{{ route('features.show',$feature) }}"><i data-lucide="{{ $feature->icon ?: 'sparkles' }}"></i><strong>{{ $feature->name }}</strong><span>{{ $feature->description ?: 'Supported model capability' }}</span></a>@endforeach @else @forelse($capabilities as $cap)<div><i data-lucide="sparkles"></i><strong>{{ $cap }}</strong><span>Supported model capability</span></div>@empty<div class="inline-empty">Capability data has not been added yet.</div>@endforelse @endif</div>@if($model->useCaseTerms->isNotEmpty())<div class="taxonomy-usecase-links"><strong>Useful for</strong><div>@foreach($model->useCaseTerms->take(12) as $useCase)<a href="{{ route('use-cases.show',$useCase) }}"><i data-lucide="target"></i>{{ $useCase->name }}</a>@endforeach</div></div>@endif</section>
<section id="benchmarks" class="detail-block"><span class="section-kicker">PERFORMANCE</span><h2>Verified benchmark profile</h2><p class="block-intro">AI Orbit only presents benchmark rows here when the underlying result is marked verified. Scores should still be interpreted alongside methodology and test date.</p>
@if($model->benchmarkResults->isNotEmpty())
<div class="benchmark-list">@foreach($model->benchmarkResults as $result)<div><span><strong>{{ $result->benchmark?->name ?? 'Benchmark' }}</strong><small>{{ $result->benchmark?->benchmark_class_label ?? 'Unclassified' }} · {{ $result->source_name ?: 'Verified result' }}{{ $result->tested_at ? ' · '.$result->tested_at->format('M Y') : '' }}</small></span><div><i style="width:{{ min(100,(float)$result->score) }}%"></i></div><b>{{ number_format((float)$result->score,1) }}</b></div>@endforeach</div>
@elseif($model->benchmark_score !== null)
<div class="benchmark-composite-only"><span><i data-lucide="badge-check"></i><strong>Verified AI Orbit composite</strong><small>A composite score is available, while a detailed verified benchmark breakdown is not currently published on this profile.</small></span><b>{{ number_format((float)$model->benchmark_score,1) }}<small>/100</small></b></div>
@else
<div class="verified-empty-state"><i data-lucide="shield-question"></i><div><strong>No verified benchmark claim yet</strong><p>AI Orbit leaves this section empty rather than displaying an unverified legacy breakdown.</p></div></div>
@endif
</section>
<section id="pricing" class="detail-block model-pricing-block">
    <span class="section-kicker">API ECONOMICS</span>
    <div class="pricing-title-row"><div><h2>Pricing & availability</h2><p class="block-intro">Commercial terms are shown in the unit that actually applies to this model instead of forcing every model into text-token pricing.</p></div><span class="pricing-verify-badge"><i data-lucide="shield-check"></i>{{ $model->pricing_verification_label }}</span></div>

    <div class="pricing-profile-strip">
        <div><span>Pricing model</span><strong>{{ $model->pricing_type_label }}</strong></div>
        <div><span>Billing unit</span><strong>{{ $model->pricing_unit_label ?: 'Not classified' }}</strong></div>
        <div><span>Verified</span><strong>{{ $model->pricing_verified_at?->format('M j, Y') ?? 'Pending' }}</strong></div>
    </div>

    @if($isTokenPricing)
    <div class="pricing-pair">
        <div><i data-lucide="arrow-down-to-line"></i><span>Input tokens<small>Per 1 million tokens</small></span><strong>{{ $model->input_price_per_million !== null ? '$'.number_format((float)$model->input_price_per_million,2) : 'Not listed' }}</strong></div>
        <div><i data-lucide="arrow-up-from-line"></i><span>Output tokens<small>Per 1 million tokens</small></span><strong>{{ $model->output_price_per_million !== null ? '$'.number_format((float)$model->output_price_per_million,2) : 'Not listed' }}</strong></div>
    </div>
    @else
    <div class="specialized-pricing-card">
        <span class="specialized-pricing-icon"><i data-lucide="wallet-cards"></i></span>
        <div><small>VERIFIED PRICING BASIS</small><strong>{{ $model->pricing_basis ?: $model->pricing_type_label }}</strong><p>{{ $model->pricing_summary ?: 'The applicable commercial model has been classified, but an exact universal numeric price is not pinned for this model.' }}</p></div>
    </div>
    @endif

    @if($isTokenPricing && $model->pricing_summary)
    <p class="pricing-evidence-copy"><i data-lucide="info"></i><span>{{ $model->pricing_summary }}</span></p>
    @elseif(!$isTokenPricing && $model->pricing_basis && $model->pricing_summary)
    <p class="pricing-evidence-copy"><i data-lucide="info"></i><span>{{ $model->pricing_summary }}</span></p>
    @endif

    <div class="pricing-source-row">
        <span><i data-lucide="database-zap"></i>{{ $pricingSourceCount ? $pricingSourceCount.' token price source '.\Illuminate\Support\Str::plural('monitor', $pricingSourceCount) : 'Verified economics evidence' }}@if($lastPricingCheck) · checked {{ $lastPricingCheck->format('M j, Y') }}@endif</span>
        @if($pricingEvidence?->source_url)<a href="{{ $pricingEvidence->source_url }}" target="_blank" rel="noopener noreferrer">Official source <i data-lucide="external-link"></i></a>@elseif($model->official_source_url)<a href="{{ $model->official_source_url }}" target="_blank" rel="noopener noreferrer">Official source <i data-lucide="external-link"></i></a>@endif
    </div>
</section>
<section id="evidence" class="detail-block model-evidence-block">
    <div class="block-title-row"><div><span class="section-kicker">SOURCE TRANSPARENCY</span><h2>Verification evidence</h2></div><span class="evidence-count"><i data-lucide="files"></i>{{ $evidenceSources->count() }} source {{ \Illuminate\Support\Str::plural('record', $evidenceSources->count()) }}</span></div>
    <p class="block-intro">These records explain which official sources support identity, profile, pricing and lifecycle claims. Benchmark evidence remains tied to its individual verified result.</p>
    <div class="model-evidence-grid">
        @forelse($evidenceSources as $source)
        <a href="{{ $source->source_url }}" target="_blank" rel="noopener noreferrer" class="model-evidence-card">
            <span class="evidence-type-icon"><i data-lucide="{{ $source->evidence_type === 'pricing' ? 'badge-dollar-sign' : ($source->evidence_type === 'lifecycle' ? 'history' : 'file-check-2') }}"></i></span>
            <span><small>{{ strtoupper($source->evidence_type) }}</small><strong>{{ $source->source_name ?: 'Official source' }}</strong><em>{{ $source->verification_status ?: 'verified' }}{{ $source->verified_at ? ' · '.$source->verified_at->format('M j, Y') : '' }}</em></span>
            <i data-lucide="arrow-up-right"></i>
        </a>
        @empty
        <div class="verified-empty-state"><i data-lucide="file-warning"></i><div><strong>Evidence records not imported yet</strong><p>The profile can still use its official source URL, but structured evidence rows have not been attached.</p></div></div>
        @endforelse
    </div>
    @if($model->benchmarkResults->isNotEmpty())
    <div class="benchmark-evidence-links"><strong>Benchmark sources</strong><div>@foreach($model->benchmarkResults as $result)@if($result->source_url)<a href="{{ $result->source_url }}" target="_blank" rel="noopener noreferrer">{{ $result->benchmark?->name ?? 'Benchmark' }} <i data-lucide="external-link"></i></a>@endif @endforeach</div></div>
    @endif
</section>
<section id="faq" class="detail-block">
<span class="section-kicker">COMMON QUESTIONS</span>
<h2>{{ $model->name }} FAQ</h2>
<p class="block-intro">Quick answers based on the verified information currently available in this model profile.</p>
<div class="seo-faq-list">
@foreach($seo['faq'] as $item)
<details><summary>{{ $item['q'] }}<i data-lucide="chevron-down"></i></summary><p>{{ $item['a'] }}</p></details>
@endforeach
</div>
</section>
@if($relatedComparisons->isNotEmpty())<section id="comparisons" class="detail-block"><div class="block-title-row"><div><span class="section-kicker">SIDE-BY-SIDE RESEARCH</span><h2>{{ $model->name }} comparisons</h2></div><a href="{{ route('comparisons.index',['type'=>'model']) }}">All model comparisons <i data-lucide="arrow-right"></i></a></div><p class="block-intro">Compare {{ $model->name }} with other public AI models using linked specs, pricing and available benchmark evidence.</p><div class="model-comparison-links">@foreach($relatedComparisons as $comparison)<a href="{{ route('comparisons.show',$comparison) }}"><span><i data-lucide="scale"></i><strong>{{ $comparison->title }}</strong><small>{{ $comparison->last_verified_at ? 'Verified '.$comparison->last_verified_at->format('M j, Y') : 'Published comparison' }}</small></span><i data-lucide="arrow-up-right"></i></a>@endforeach</div></section>@endif
<section id="related" class="detail-block"><div class="block-title-row"><div><span class="section-kicker">KEEP EXPLORING</span><h2>Related AI models</h2></div><a href="{{ route('models.index') }}">View all models <i data-lucide="arrow-right"></i></a></div><div class="related-models">@foreach($relatedModels as $related)<a href="{{ route('models.show',$related) }}"><img src="{{ $related->logo_url }}" alt=""><span><strong>{{ $related->name }}</strong><small>{{ $related->company?->name }} · {{ $related->context_window ?: 'Context N/A' }}</small></span><b>{{ $related->benchmark_score !== null ? number_format((float)$related->benchmark_score,1) : '—' }}</b></a>@endforeach</div></section></main>
<aside class="model-detail-side"><div class="side-card provider-card"><span class="side-label">PROVIDER</span>@if($model->company?->logo_path)<img src="{{ $model->company->logo_url }}" alt="{{ $model->company->name }} logo">@endif<h3>{{ $model->company?->name ?? 'Independent' }}</h3><p>{{ Str::limit($model->company?->description,130) ?: 'Provider information will appear here when available.' }}</p>@if($model->company && in_array($model->company->status, ['active','acquired'], true))<a href="{{ route('companies.show',$model->company) }}">Explore {{ $model->company->name }} profile <i data-lucide="arrow-right"></i></a>@endif @if($model->company?->website)<a href="{{ $model->company->website }}" target="_blank" rel="noopener noreferrer">Official website <i data-lucide="external-link"></i></a>@endif</div>
@if($model->tool && $model->tool->status === 'published')<div class="side-card product-card"><span class="side-label">AVAILABLE IN</span><div><img src="{{ $model->tool->logo_url }}" alt=""><span><strong>{{ $model->tool->name }}</strong><small>{{ $model->tool->short_description }}</small></span></div><a href="{{ route('tools.show',$model->tool) }}">Explore product <i data-lucide="arrow-right"></i></a></div>@endif
<div class="side-card"><span class="side-label">LATEST PROVIDER NEWS</span><div class="side-news">@forelse($latestNews as $news)<a href="{{ route('news.show',$news) }}"><article>@if($news->image_path)<img src="{{ $news->image_url }}" alt="{{ $news->headline }}">@endif<div><strong>{{ Str::limit($news->headline,68) }}</strong><small>{{ $news->published_at?->diffForHumans() }}</small></div></article></a>@empty<p class="side-empty">No related published news yet.</p>@endforelse</div></div></aside></div></section>
@endsection
