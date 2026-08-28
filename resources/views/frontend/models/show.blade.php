@extends('frontend.layouts.app')
@section('title', html_entity_decode($seo['title'], ENT_QUOTES | ENT_HTML5, 'UTF-8') . ' | AI Orbit')
@section('meta_description', html_entity_decode($seo['description'], ENT_QUOTES | ENT_HTML5, 'UTF-8'))
@section('og_type', 'article')
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
@push('styles')<link rel="stylesheet" href="{{ asset('css/frontend/models.css') }}">@endpush
@section('content')
<section class="model-detail-hero model-detail-hero-wave">
<div class="model-detail-wave-art" aria-hidden="true"></div>
<div class="model-detail-wave-shade" aria-hidden="true"></div>
<div class="model-detail-logo-aura" aria-hidden="true" style="background-image:url('{{ $model->logo_url }}')"></div>
<div class="model-wrap"><div class="model-breadcrumb"><a href="{{ route('home') }}">Home</a><i data-lucide="chevron-right"></i><a href="{{ route('models.index') }}">AI Models</a><i data-lucide="chevron-right"></i><span>{{ $model->name }}</span></div><div class="model-detail-main"><div class="model-detail-id"><img src="{{ $model->logo_url }}" alt="{{ $model->name }}"><div><div class="detail-badges"><span class="status-pill {{ $model->status }}">{{ ucfirst($model->status) }}</span>@if($model->release_date)<span>Released {{ $model->release_date->format('M j, Y') }}</span>@endif</div><h1>{{ $model->name }}</h1><p>By <strong>{{ $model->company?->name ?? 'Independent' }}</strong>@if($model->version) · {{ $model->version }}@endif</p></div></div><div class="model-detail-actions"><button type="button" class="save-item-btn detail-save" data-save-item data-save-type="model" data-save-id="{{ $model->id }}" aria-pressed="false"><i data-lucide="bookmark"></i><span data-save-label data-default-label="Save">Save</span></button><a href="{{ route('comparisons.builder', ['type' => 'model', 'item' => $model->id]) }}"><i data-lucide="scale"></i> Compare</a><a href="#benchmarks"><i data-lucide="bar-chart-3"></i> Benchmarks</a>@if($model->tool)<a class="primary" href="{{ route('tools.show',$model->tool) }}">View {{ $model->tool->name }} <i data-lucide="arrow-up-right"></i></a>@endif</div></div>
@include('frontend.partials.quick-rating', [
    'type' => 'model',
    'id' => $model->id,
    'summary' => $quickRating,
    'label' => 'Rate '.$model->name,
])
<div class="detail-metrics"><div><span>Overall benchmark</span><strong>{{ $model->benchmark_score !== null ? number_format((float)$model->benchmark_score,1) : '—' }}</strong><small>{{ $model->benchmark_score !== null ? '/100' : 'Not verified' }}</small></div><div><span>Context window</span><strong>{{ $model->context_window ?: '—' }}</strong><small>tokens</small></div><div><span>Input price</span><strong>{{ $model->input_price_per_million !== null ? '$'.number_format((float)$model->input_price_per_million,2) : '—' }}</strong><small>per 1M tokens</small></div><div><span>Output price</span><strong>{{ $model->output_price_per_million !== null ? '$'.number_format((float)$model->output_price_per_million,2) : '—' }}</strong><small>per 1M tokens</small></div></div></div></section>
<nav class="model-detail-nav"><div class="model-wrap"><a href="#overview">Overview</a><a href="#capabilities">Capabilities</a><a href="#benchmarks">Benchmarks</a><a href="#pricing">Pricing</a><a href="#related">Related models</a></div></nav>
<section class="model-detail-body"><div class="model-wrap detail-layout"><main><section id="overview" class="detail-block"><span class="section-kicker">MODEL OVERVIEW</span><h2>About {{ $model->name }}</h2><div class="detail-lead">{!! nl2br(e($model->overview)) !!}</div><div class="spec-table"><div><span>Provider</span><strong>{{ $model->company?->name ?? '—' }}</strong></div><div><span>Version</span><strong>{{ $model->version ?: '—' }}</strong></div><div><span>Release date</span><strong>{{ $model->release_date?->format('F j, Y') ?? '—' }}</strong></div><div><span>Status</span><strong>{{ ucfirst($model->status) }}</strong></div><div><span>Context window</span><strong>{{ $model->context_window ?: '—' }}</strong></div><div><span>Associated product</span><strong>{{ $model->tool?->name ?? '—' }}</strong></div></div></section>
<section id="capabilities" class="detail-block"><span class="section-kicker">WHAT IT CAN DO</span><h2>Capabilities & use cases</h2><div class="capability-grid">@if($model->featureTerms->isNotEmpty()) @foreach($model->featureTerms as $feature)<a class="taxonomy-cap-card" href="{{ route('features.show',$feature) }}"><i data-lucide="{{ $feature->icon ?: 'sparkles' }}"></i><strong>{{ $feature->name }}</strong><span>{{ $feature->description ?: 'Supported model capability' }}</span></a>@endforeach @else @forelse($capabilities as $cap)<div><i data-lucide="sparkles"></i><strong>{{ $cap }}</strong><span>Supported model capability</span></div>@empty<div class="inline-empty">Capability data has not been added yet.</div>@endforelse @endif</div>@if($model->useCaseTerms->isNotEmpty())<div class="taxonomy-usecase-links"><strong>Useful for</strong><div>@foreach($model->useCaseTerms->take(12) as $useCase)<a href="{{ route('use-cases.show',$useCase) }}"><i data-lucide="target"></i>{{ $useCase->name }}</a>@endforeach</div></div>@endif</section>
<section id="benchmarks" class="detail-block"><span class="section-kicker">PERFORMANCE</span><h2>Benchmark profile</h2><p class="block-intro">Benchmark results help compare model performance, but scores should always be interpreted alongside methodology and test date.</p><div class="benchmark-list">@forelse($model->benchmarkResults as $result)<div><span><strong>{{ $result->benchmark?->name ?? 'Benchmark' }}</strong><small>{{ $result->source_name ?: 'Verified result' }}{{ $result->tested_at ? ' · '.$result->tested_at->format('M Y') : '' }}</small></span><div><i style="width:{{ min(100,(float)$result->score) }}%"></i></div><b>{{ number_format((float)$result->score,1) }}</b></div>@empty @foreach($benchmarks as $bench)<div><span><strong>{{ $bench['name'] }}</strong><small>Model benchmark breakdown</small></span><div><i style="width:{{ min(100,$bench['score']) }}%"></i></div><b>{{ number_format((float)$bench['score'],1) }}</b></div>@endforeach @if($benchmarks->isEmpty())<div class="inline-empty">No benchmark breakdown is available yet.</div>@endif @endforelse</div></section>
<section id="pricing" class="detail-block">
    <span class="section-kicker">API ECONOMICS</span>
    <h2>Token pricing</h2>

    <div class="pricing-pair">
        <div>
            <i data-lucide="arrow-down-to-line"></i>
            <span>
                Input tokens
                <small>Per 1 million tokens</small>
            </span>
            <strong>
                {{ $model->input_price_per_million !== null
                    ? '$' . number_format((float) $model->input_price_per_million, 2)
                    : 'Not listed' }}
            </strong>
        </div>

        <div>
            <i data-lucide="arrow-up-from-line"></i>
            <span>
                Output tokens
                <small>Per 1 million tokens</small>
            </span>
            <strong>
                {{ $model->output_price_per_million !== null
                    ? '$' . number_format((float) $model->output_price_per_million, 2)
                    : 'Not listed' }}
            </strong>
        </div>
    </div>

    <p class="pricing-note">
        <i data-lucide="info"></i>
        Pricing can change. Production data should be checked against the provider’s official pricing source.
    </p>

    @php
        $pricingSourceCount = $model->pricingSources->count();
        $lastPricingCheck = $model->pricingSources
            ->pluck('last_checked_at')
            ->filter()
            ->sortDesc()
            ->first();

        $pricingSourceNote = '';

        if ($pricingSourceCount > 0) {
            $pricingSourceNote = $pricingSourceCount
                . ' official pricing '
                . \Illuminate\Support\Str::plural('source', $pricingSourceCount)
                . ' monitored';

            if ($lastPricingCheck) {
                $pricingSourceNote .= ' · last checked ' . $lastPricingCheck->diffForHumans();
            }

            $pricingSourceNote .= '.';
        }
    @endphp

    @if($pricingSourceNote !== '')
        <p class="pricing-note">
            <i data-lucide="shield-check"></i>
            {{ $pricingSourceNote }}
        </p>
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
<section id="related" class="detail-block"><div class="block-title-row"><div><span class="section-kicker">KEEP EXPLORING</span><h2>Related AI models</h2></div><a href="{{ route('models.index') }}">View all models <i data-lucide="arrow-right"></i></a></div><div class="related-models">@foreach($relatedModels as $related)<a href="{{ route('models.show',$related) }}"><img src="{{ $related->logo_url }}" alt=""><span><strong>{{ $related->name }}</strong><small>{{ $related->company?->name }} · {{ $related->context_window ?: 'Context N/A' }}</small></span><b>{{ $related->benchmark_score !== null ? number_format((float)$related->benchmark_score,1) : '—' }}</b></a>@endforeach</div></section></main>
<aside class="model-detail-side"><div class="side-card provider-card"><span class="side-label">PROVIDER</span>@if($model->company?->logo_path)<img src="{{ $model->company->logo_url }}" alt="">@endif<h3>{{ $model->company?->name ?? 'Independent' }}</h3><p>{{ Str::limit($model->company?->description,130) ?: 'Provider information will appear here when available.' }}</p>@if($model->company?->website)<a href="{{ $model->company->website }}" target="_blank" rel="noopener">Official website <i data-lucide="external-link"></i></a>@endif</div>
@if($model->tool)<div class="side-card product-card"><span class="side-label">AVAILABLE IN</span><div><img src="{{ $model->tool->logo_url }}" alt=""><span><strong>{{ $model->tool->name }}</strong><small>{{ $model->tool->short_description }}</small></span></div><a href="{{ route('tools.show',$model->tool) }}">Explore product <i data-lucide="arrow-right"></i></a></div>@endif
<div class="side-card"><span class="side-label">LATEST PROVIDER NEWS</span><div class="side-news">@forelse($latestNews as $news)<article>@if($news->image_path)<img src="{{ $news->image_url }}" alt="">@endif<div><strong>{{ Str::limit($news->headline,68) }}</strong><small>{{ $news->published_at?->diffForHumans() }}</small></div></article>@empty<p class="side-empty">No related published news yet.</p>@endforelse</div></div></aside></div></section>
@endsection
