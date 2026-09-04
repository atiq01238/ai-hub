@extends('frontend.layouts.app')

@section('title', html_entity_decode($seo['title'], ENT_QUOTES | ENT_HTML5, 'UTF-8') . ' | AI Orbit')
@section('meta_description', html_entity_decode($seo['description'], ENT_QUOTES | ENT_HTML5, 'UTF-8'))
@section('canonical', route('tools.show', $tool))
@section('robots', 'index,follow,max-image-preview:large,max-snippet:-1,max-video-preview:-1')
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

@push('styles')
<link rel="stylesheet" href="{{ asset('css/frontend/tools-show.css') }}?v=20260903-mobile-order-v4">
@endpush

@section('content')
@php
    $logo = $tool->logo_url;
    $cover = $tool->cover_image_url;
    $publishedReviews = $tool->reviews;
    $pros = collect($editorReview?->pros ?? [])->filter();
    $cons = collect($editorReview?->cons ?? [])->filter();
    // Defensive fallback: the controller normally provides this value, but keep the view safe
    // for alternate render paths, cached views, or partial controller integrations.
    $productStatusSource = $productStatusSource ?? null;
    $technicalProfile = $technicalProfile ?? null;
    $integrations = $integrations ?? collect();
    $dataConfidence = $dataConfidence ?? ['score'=>0,'label'=>'Low','freshness'=>'unverified','verified_sources'=>0,'total_sources'=>0,'verified_claims'=>0,'known_claims'=>0,'last_verified_at'=>null,'sections'=>[]];
    $factEvidenceMap = $factEvidenceMap ?? collect();
    $factVerified = fn($type, $key) => (($factEvidenceMap->get($type.'.'.$key)?->verification_status ?? 'pending') === 'verified');
    $sourceFor = fn($id) => $id ? ($sourceMap->get((int)$id) ?? null) : null;
    $apiSource = $sourceFor($technicalProfile?->api_source_id);
    $repositorySource = $sourceFor($technicalProfile?->repository_source_id);
    $deploymentSource = $sourceFor($technicalProfile?->deployment_source_id);
    $termsSource = $sourceFor($technicalProfile?->terms_source_id);
    $availabilitySource = $sourceFor($technicalProfile?->availability_source_id);
    $privacySource = $sourceFor($technicalProfile?->privacy_source_id);
    $securitySource = $sourceFor($technicalProfile?->security_source_id);
    $hasTechnicalIntel = $technicalProfile && (
        $technicalProfile->api_status !== 'unknown' || $technicalProfile->open_source_status !== 'unknown' ||
        $technicalProfile->self_hosting_status !== 'unknown' || $technicalProfile->commercial_use_status !== 'unknown' ||
        !empty($technicalProfile->deployment_modes) || !empty($technicalProfile->supported_languages) || !empty($technicalProfile->region_availability)
    );
    $hasTrustIntel = $technicalProfile && (
        $technicalProfile->data_training_policy !== 'unknown' || $technicalProfile->privacy_summary || $technicalProfile->data_retention_note ||
        $technicalProfile->security_summary || $technicalProfile->sso_status !== 'unknown' || !empty($technicalProfile->security_certifications) ||
        !empty($technicalProfile->compliance_certifications) || !empty($technicalProfile->data_residency)
    );
@endphp

<section class="tool-detail-hero tool-detail-hero-network">
    <div class="tool-network-art" aria-hidden="true"></div>
    @if($cover)<div class="tool-hero-cover" style="background-image:url('{{ $cover }}')"></div>@endif
    <div class="tool-logo-aura" aria-hidden="true" style="background-image:url('{{ $logo }}')"></div>
    <div class="tool-hero-grid"></div><div class="tool-hero-glow"></div>
    <div class="tool-detail-wrap hero-wrap">
        <nav class="tool-breadcrumb" aria-label="Breadcrumb">
            <a href="{{ route('home') }}">Home</a><i data-lucide="chevron-right"></i>
            <a href="{{ route('tools.index') }}">AI Tools</a><i data-lucide="chevron-right"></i>
            @if($tool->category)<a href="{{ route('categories.show', $tool->category) }}">{{ $tool->category->name }}</a><i data-lucide="chevron-right"></i>@endif
            <span>{{ $tool->name }}</span>
        </nav>

        <div class="tool-hero-main">
            <div class="tool-identity-block">
                <img class="tool-detail-logo" src="{{ $logo }}" alt="{{ $tool->name }} logo">
                <div class="tool-detail-title">
                    <div class="tool-eyebrow-row">
                        @if($tool->category)<span class="category-pill">{{ $tool->category->name }}</span>@endif
                        @if($verifiedIdentitySource)<span class="verified-pill" title="Verified from an official source"><i data-lucide="badge-check"></i> Source verified</span>@endif
                        @if(($tool->product_status ?? 'unknown') !== 'unknown')
                            <span class="lifecycle-pill lifecycle-pill--{{ $tool->product_status }}" title="{{ $tool->product_status_verified_at ? 'Lifecycle status verified '. $tool->product_status_verified_at->format('M j, Y') : 'Lifecycle status saved; verification pending' }}"><i data-lucide="activity"></i>{{ $tool->product_status_label }}@if($tool->product_status_verified_at)<i data-lucide="badge-check"></i>@endif</span>
                        @endif
                        @if($tool->launch_date)<span class="launch-pill">Since {{ $tool->launch_date->format('Y') }}</span>@endif
                    </div>
                    <h1>{{ $tool->name }}</h1>
                    <p class="tool-company-line">by @if($tool->company && in_array($tool->company->status, ['active','acquired'], true))<a href="{{ route('companies.show',$tool->company) }}"><strong>{{ $tool->company->name }}</strong></a>@elseif($tool->company)<strong>{{ $tool->company->name }}</strong>@else<strong>Independent</strong>@endif @if($tool->subcategoryTerm)<span>•</span>{{ $tool->subcategoryTerm->name }}@elseif($tool->subcategory)<span>•</span>{{ $tool->subcategory }}@endif</p>
                    <p class="tool-hero-description">{{ $tool->short_description ?: Str::limit(strip_tags($tool->description), 220) }}</p>
                </div>
            </div>

        </div>


        <div class="tool-hero-bottom">
            <div class="tool-quick-facts">
                <span><i data-lucide="badge-dollar-sign"></i><small>Pricing</small><b>{{ $priceLabel }}</b></span>
                <span><i data-lucide="shield-check"></i><small>Data confidence</small><b>{{ $dataConfidence['score'] }}/100</b></span>
                <span><i data-lucide="monitor-smartphone"></i><small>Platforms</small><b>{{ $platforms->take(2)->join(' + ') ?: 'Web' }}</b></span>
                @if($tool->company)<span><i data-lucide="building-2"></i><small>Company</small><b>@if(in_array($tool->company->status, ['active','acquired'], true))<a href="{{ route('companies.show',$tool->company) }}">{{ $tool->company->name }}</a>@else{{ $tool->company->name }}@endif</b></span>@endif
            </div>
            <div class="tool-hero-actions">
                <button type="button" class="detail-secondary-btn" data-save-item data-save-type="tool" data-save-id="{{ $tool->id }}" aria-pressed="false"><i data-lucide="bookmark"></i><span data-save-label data-default-label="Save">Save</span></button>
                <a href="{{ route('comparisons.builder', ['type' => 'tool', 'item' => $tool->id]) }}" class="detail-secondary-btn"><i data-lucide="scale"></i><span>Compare</span></a>
                <a href="#pricing" class="detail-secondary-btn tool-pricing-action"><i data-lucide="badge-dollar-sign"></i><span>Pricing</span></a>
                @if($tool->website)<a href="{{ $tool->website }}" target="_blank" rel="noopener noreferrer nofollow" class="detail-primary-btn">Visit Website<i data-lucide="arrow-up-right"></i></a>@endif
            </div>
        </div>

        @include('frontend.partials.quick-rating', [
            'type' => 'tool',
            'id' => $tool->id,
            'summary' => $quickRating,
            'label' => 'Rate '.$tool->name,
        ])
    </div>
</section>

<div class="tool-sticky-nav" data-detail-nav>
    <div class="tool-detail-wrap sticky-nav-inner">
        <div class="detail-nav-links">
            <a href="#overview" class="active">Overview</a>
            @if($capabilities->isNotEmpty() || $tool->featureTerms->isNotEmpty())<a href="#features">Features</a>@endif
            @if($hasTechnicalIntel)<a href="#technical">Technical</a>@endif
            @if($hasTrustIntel)<a href="#trust">Trust</a>@endif
            @if($integrations->isNotEmpty())<a href="#integrations">Integrations</a>@endif
            <a href="#pricing">Pricing</a>
            @if($benchmarkResults->isNotEmpty() || $tool->benchmark_score)<a href="#benchmarks">Benchmarks</a>@endif
            @if($editorialReviews->isNotEmpty() || $pros->isNotEmpty() || $cons->isNotEmpty())
                <a href="#reviews">Reviews</a>
            @elseif($publishedReviews->where('review_type','user')->isNotEmpty())
                <a href="#community-reviews">Reviews</a>
            @endif
            @if($relatedComparisons->isNotEmpty())<a href="#comparisons">Comparisons</a>@endif
            @if($relatedArticles->isNotEmpty())<a href="#guides">Guides</a>@endif
            @if($relatedTools->isNotEmpty())<a href="#alternatives">Alternatives</a>@endif
        </div>
    </div>
</div>

<section class="tool-detail-wrap tool-detail-content">
    <div class="tool-detail-main">
        <section class="detail-panel overview-panel" id="overview">
            <div class="detail-section-head"><div><span>Overview</span><h2>What is {{ $tool->name }}?</h2></div><i data-lucide="sparkles"></i></div>
            <div class="rich-description">{!! nl2br(e($tool->overview)) !!}</div>
            @if($tool->useCaseTerms->isNotEmpty())
            <div class="best-for-box"><span><i data-lucide="target"></i>Best for</span><div>@foreach($tool->useCaseTerms->take(5) as $useCase)<b title="{{ $useCase->pivot?->fit_note ?: ($useCase->short_description ?: 'AI Orbit use-case classification') }}">{{ $useCase->name }}@if(($useCase->pivot?->verification_status ?? 'pending') === 'verified') <i data-lucide="badge-check"></i>@endif</b>@endforeach</div></div>
            @endif
        </section>

        @if($capabilities->isNotEmpty() || $tool->featureTerms->isNotEmpty())
        <section class="detail-panel" id="features">
            <div class="detail-section-head"><div><span>Capabilities</span><h2>Features & use cases</h2><p>Core capabilities listed for {{ $tool->name }}.</p></div><i data-lucide="blocks"></i></div>
            <div class="feature-detail-grid">
                @forelse($tool->featureTerms as $feature)
                    @php
                        $featureDescription = trim((string) ($feature->pivot?->description ?? '')) ?: ($feature->short_description ?: $feature->description);
                        $featureEvidence = !empty($feature->pivot?->tool_source_id) ? $sourceMap->get($feature->pivot->tool_source_id) : null;
                        $featureVerified = ($feature->pivot?->verification_status ?? 'pending') === 'verified';
                    @endphp
                    <article>
                        <span><i data-lucide="check"></i></span>
                        <div>
                            <h3>{{ $feature->name }}</h3>
                            <p>{{ $featureDescription ?: 'Capability description has not been verified for this tool yet.' }}</p>
                            <div class="feature-evidence-row">
                                @if($featureVerified)<span class="evidence-state evidence-state--verified"><i data-lucide="badge-check"></i>Verified capability</span>@else<span class="evidence-state"><i data-lucide="clock-3"></i>Evidence pending</span>@endif
                                @if($featureEvidence)<a href="{{ $featureEvidence->source_url }}" target="_blank" rel="noopener noreferrer nofollow">Source<i data-lucide="arrow-up-right"></i></a>@endif
                            </div>
                        </div>
                    </article>
                @empty
                    @forelse($capabilities as $capability)
                        <article><span><i data-lucide="circle-help"></i></span><div><h3>{{ $capability }}</h3><p>Legacy capability label. Structured evidence has not been attached yet.</p><div class="feature-evidence-row"><span class="evidence-state"><i data-lucide="clock-3"></i>Evidence pending</span></div></div></article>
                    @empty<p class="detail-empty">Capability details have not been published yet.</p>@endforelse
                @endforelse
            </div>
            @if($tool->featureTerms->isNotEmpty())
            <div class="taxonomy-link-row"><strong>Explore capabilities</strong><div>@foreach($tool->featureTerms->take(10) as $feature)<a href="{{ route('features.show',$feature) }}"><i data-lucide="{{ $feature->icon ?: 'sparkles' }}"></i>{{ $feature->name }}</a>@endforeach</div></div>
            @endif
            @if($tool->useCaseTerms->isNotEmpty())
            <div class="taxonomy-link-row use-cases"><strong>Best use cases</strong><div>@foreach($tool->useCaseTerms->take(10) as $useCase)<a href="{{ route('use-cases.show',$useCase) }}"><i data-lucide="target"></i>{{ $useCase->name }}@if(($useCase->pivot?->verification_status ?? 'pending') === 'verified')<i data-lucide="badge-check"></i>@endif</a>@endforeach</div></div>
            @if($tool->useCaseTerms->contains(fn($useCase) => trim((string)($useCase->pivot?->fit_note ?? '')) !== ''))
                <div class="use-case-fit-list">
                    @foreach($tool->useCaseTerms->filter(fn($useCase) => trim((string)($useCase->pivot?->fit_note ?? '')) !== '')->take(6) as $useCase)
                        @php $useCaseEvidence = !empty($useCase->pivot?->tool_source_id) ? $sourceMap->get($useCase->pivot->tool_source_id) : null; @endphp
                        <div><strong>{{ $useCase->name }}</strong><p>{{ $useCase->pivot->fit_note }}</p><span>{{ ($useCase->pivot?->verification_status ?? 'pending') === 'verified' ? 'Verified fit' : 'Evidence pending' }}@if($useCaseEvidence) · <a href="{{ $useCaseEvidence->source_url }}" target="_blank" rel="noopener noreferrer nofollow">source</a>@endif</span></div>
                    @endforeach
                </div>
            @endif
            @endif
            @if($platforms->isNotEmpty() || $tags->isNotEmpty())
            <div class="platform-tag-row">
                @foreach($platforms as $platform)<span><i data-lucide="monitor"></i>{{ $platform }}</span>@endforeach
                @foreach($tags->take(8) as $tag)<span class="soft-tag">#{{ $tag }}</span>@endforeach
            </div>
            @endif
        </section>
        @endif

        @if($hasTechnicalIntel)
        @php
            $apiVerified = $factVerified('technical','api_status');
            $openSourceVerified = $factVerified('technical','open_source_status');
            $deploymentVerified = $factVerified('technical','self_hosting_status');
            $commercialVerified = $factVerified('technical','commercial_use_status');
            $sameEvidenceUrl = function ($first, $second) {
                if (!$first || !$second) return false;
                return rtrim(strtolower(trim((string)$first)), '/') === rtrim(strtolower(trim((string)$second)), '/');
            };
        @endphp
        <section class="detail-panel technical-profile-panel" id="technical">
            <div class="detail-section-head"><div><span>Technical profile</span><h2>Access, deployment & licensing</h2><p>Structured product facts with evidence status. Unknown facts are never guessed.</p></div><i data-lucide="terminal-square"></i></div>
            <div class="feature-detail-grid technical-fact-grid">
                <article class="technical-fact-card {{ $apiVerified ? 'is-verified' : 'is-pending' }}">
                    <span class="technical-fact-card__icon"><i data-lucide="braces"></i></span>
                    <div class="technical-fact-card__content">
                        <div class="technical-fact-card__head"><h3>API access</h3><span class="technical-fact-state {{ $apiVerified ? 'is-verified' : 'is-pending' }}"><i data-lucide="{{ $apiVerified ? 'badge-check' : 'clock-3' }}"></i>{{ $apiVerified ? 'Verified' : 'Pending evidence' }}</span></div>
                        <p>{{ \App\Models\ToolTechnicalProfile::API_STATUSES[$technicalProfile->api_status] ?? Str::headline($technicalProfile->api_status) }}</p>
                        @if($technicalProfile->api_docs_url || ($apiSource && !$sameEvidenceUrl($technicalProfile->api_docs_url, $apiSource->source_url)))
                        <div class="technical-fact-actions">
                            @if($technicalProfile->api_docs_url)<a href="{{ $technicalProfile->api_docs_url }}" target="_blank" rel="noopener noreferrer nofollow">API docs <i data-lucide="arrow-up-right"></i></a>@endif
                            @if($apiSource && !$sameEvidenceUrl($technicalProfile->api_docs_url, $apiSource->source_url))<a href="{{ $apiSource->source_url }}" target="_blank" rel="noopener noreferrer nofollow">Evidence source <i data-lucide="arrow-up-right"></i></a>@endif
                        </div>
                        @endif
                    </div>
                </article>

                <article class="technical-fact-card {{ $openSourceVerified ? 'is-verified' : 'is-pending' }}">
                    <span class="technical-fact-card__icon"><i data-lucide="git-fork"></i></span>
                    <div class="technical-fact-card__content">
                        <div class="technical-fact-card__head"><h3>Open source & license</h3><span class="technical-fact-state {{ $openSourceVerified ? 'is-verified' : 'is-pending' }}"><i data-lucide="{{ $openSourceVerified ? 'badge-check' : 'clock-3' }}"></i>{{ $openSourceVerified ? 'Verified' : 'Pending evidence' }}</span></div>
                        <p>{{ \App\Models\ToolTechnicalProfile::OPEN_SOURCE_STATUSES[$technicalProfile->open_source_status] ?? Str::headline($technicalProfile->open_source_status) }}@if($technicalProfile->license_name) · {{ $technicalProfile->license_name }}@endif</p>
                        @if($technicalProfile->repository_url || ($repositorySource && !$sameEvidenceUrl($technicalProfile->repository_url, $repositorySource->source_url)))
                        <div class="technical-fact-actions">
                            @if($technicalProfile->repository_url)<a href="{{ $technicalProfile->repository_url }}" target="_blank" rel="noopener noreferrer nofollow">Repository <i data-lucide="arrow-up-right"></i></a>@endif
                            @if($repositorySource && !$sameEvidenceUrl($technicalProfile->repository_url, $repositorySource->source_url))<a href="{{ $repositorySource->source_url }}" target="_blank" rel="noopener noreferrer nofollow">Evidence source <i data-lucide="arrow-up-right"></i></a>@endif
                        </div>
                        @endif
                    </div>
                </article>

                <article class="technical-fact-card {{ $deploymentVerified ? 'is-verified' : 'is-pending' }}">
                    <span class="technical-fact-card__icon"><i data-lucide="server-cog"></i></span>
                    <div class="technical-fact-card__content">
                        <div class="technical-fact-card__head"><h3>Deployment</h3><span class="technical-fact-state {{ $deploymentVerified ? 'is-verified' : 'is-pending' }}"><i data-lucide="{{ $deploymentVerified ? 'badge-check' : 'clock-3' }}"></i>{{ $deploymentVerified ? 'Verified' : 'Pending evidence' }}</span></div>
                        <p>{{ \App\Models\ToolTechnicalProfile::SELF_HOSTING_STATUSES[$technicalProfile->self_hosting_status] ?? Str::headline($technicalProfile->self_hosting_status) }}</p>
                        @if(!empty($technicalProfile->deployment_modes))<div class="technical-mode-list">@foreach($technicalProfile->deployment_modes as $mode)<span>{{ $mode }}</span>@endforeach</div>@endif
                        @if($deploymentSource)<div class="technical-fact-actions"><a href="{{ $deploymentSource->source_url }}" target="_blank" rel="noopener noreferrer nofollow">Deployment source <i data-lucide="arrow-up-right"></i></a></div>@endif
                    </div>
                </article>

                <article class="technical-fact-card {{ $commercialVerified ? 'is-verified' : 'is-pending' }}">
                    <span class="technical-fact-card__icon"><i data-lucide="badge-dollar-sign"></i></span>
                    <div class="technical-fact-card__content">
                        <div class="technical-fact-card__head"><h3>Commercial use</h3><span class="technical-fact-state {{ $commercialVerified ? 'is-verified' : 'is-pending' }}"><i data-lucide="{{ $commercialVerified ? 'badge-check' : 'clock-3' }}"></i>{{ $commercialVerified ? 'Verified' : 'Pending evidence' }}</span></div>
                        <p>{{ \App\Models\ToolTechnicalProfile::COMMERCIAL_USE_STATUSES[$technicalProfile->commercial_use_status] ?? Str::headline($technicalProfile->commercial_use_status) }}</p>
                        @if($termsSource)<div class="technical-fact-actions"><a href="{{ $termsSource->source_url }}" target="_blank" rel="noopener noreferrer nofollow">Terms evidence <i data-lucide="arrow-up-right"></i></a></div>@endif
                    </div>
                </article>
            </div>

            @if(!empty($technicalProfile->supported_languages) || !empty($technicalProfile->region_availability))
            <div class="technical-availability-card">
                <div class="technical-availability-card__head">
                    <span class="technical-availability-card__title"><i data-lucide="globe-2"></i>Availability</span>
                    @if($availabilitySource)<span class="technical-fact-state {{ $availabilitySource->verification_status === 'verified' ? 'is-verified' : 'is-pending' }}"><i data-lucide="{{ $availabilitySource->verification_status === 'verified' ? 'badge-check' : 'clock-3' }}"></i>{{ $availabilitySource->verification_status === 'verified' ? 'Verified source' : 'Source pending' }}</span>@endif
                </div>
                <div class="technical-availability-card__chips">@foreach($technicalProfile->supported_languages ?? [] as $language)<b>{{ $language }}</b>@endforeach @foreach($technicalProfile->region_availability ?? [] as $region)<b>{{ $region }}</b>@endforeach</div>
                @if($availabilitySource)<a class="technical-availability-card__source" href="{{ $availabilitySource->source_url }}" target="_blank" rel="noopener noreferrer nofollow">View availability source <i data-lucide="arrow-up-right"></i></a>@endif
            </div>
            @endif
        </section>
        @endif

        @if($hasTrustIntel)
        <section class="detail-panel" id="trust">
            <div class="detail-section-head"><div><span>Trust intelligence</span><h2>Privacy, security & compliance</h2><p>Provider policies and certifications are shown only when recorded with evidence.</p></div><i data-lucide="shield-check"></i></div>
            <div class="feature-detail-grid">
                <article><span><i data-lucide="database"></i></span><div><h3>Data training policy</h3><p>{{ \App\Models\ToolTechnicalProfile::TRAINING_POLICIES[$technicalProfile->data_training_policy] ?? Str::headline($technicalProfile->data_training_policy) }}</p>@if($technicalProfile->data_retention_note)<small>{{ $technicalProfile->data_retention_note }}</small>@endif <small>{{ $factVerified('privacy','data_training_policy') ? 'Verified fact' : 'Evidence pending' }}</small>@if($privacySource)<a href="{{ $privacySource->source_url }}" target="_blank" rel="noopener noreferrer nofollow">Privacy source <i data-lucide="arrow-up-right"></i></a>@endif</div></article>
                <article><span><i data-lucide="key-round"></i></span><div><h3>SSO / enterprise access</h3><p>{{ \App\Models\ToolTechnicalProfile::SSO_STATUSES[$technicalProfile->sso_status] ?? Str::headline($technicalProfile->sso_status) }}</p><small>{{ $factVerified('security','sso_status') ? 'Verified fact' : 'Evidence pending' }}</small>@if($securitySource)<a href="{{ $securitySource->source_url }}" target="_blank" rel="noopener noreferrer nofollow">Security source <i data-lucide="arrow-up-right"></i></a>@endif</div></article>
            </div>
            @if($technicalProfile->privacy_summary)<div class="rich-description"><strong>Privacy:</strong> {{ $technicalProfile->privacy_summary }}</div>@endif
            @if($technicalProfile->security_summary)<div class="rich-description"><strong>Security:</strong> {{ $technicalProfile->security_summary }}</div>@endif
            @if(!empty($technicalProfile->security_certifications) || !empty($technicalProfile->compliance_certifications) || !empty($technicalProfile->data_residency))
            <div class="best-for-box"><span><i data-lucide="badge-check"></i>Recorded assurances</span><div>@foreach(array_merge($technicalProfile->security_certifications ?? [], $technicalProfile->compliance_certifications ?? [], $technicalProfile->data_residency ?? []) as $item)<b>{{ $item }}</b>@endforeach</div></div>
            @endif
        </section>
        @endif

        @if($integrations->isNotEmpty())
        <section class="detail-panel" id="integrations">
            <div class="detail-section-head"><div><span>Compatibility</span><h2>Integrations</h2><p>Structured integrations recorded for {{ $tool->name }}. A check mark means the integration mapping is tied to a verified source.</p></div><i data-lucide="plug-zap"></i></div>
            <div class="best-for-box"><span><i data-lucide="network"></i>Works with</span><div>@foreach($integrations as $integration)<b title="{{ ucfirst($integration->pivot?->verification_status ?? 'pending') }}">{{ $integration->name }}@if(($integration->pivot?->verification_status ?? 'pending') === 'verified') <i data-lucide="badge-check"></i>@endif</b>@endforeach</div></div>
        </section>
        @endif

        <section class="detail-panel" id="pricing">
            <div class="detail-section-head"><div><span>Pricing</span><h2>{{ $tool->name }} pricing plans</h2><p>Pricing stored in AI Orbit's pricing database. Always verify final rates on the provider website.</p></div><i data-lucide="badge-dollar-sign"></i></div>
            @if($pricingPlans->isNotEmpty())
            <div class="pricing-detail-grid">
                @foreach($pricingPlans as $plan)
                <article class="pricing-detail-card">
                    <small>{{ $tool->name }}</small><h3>{{ $plan->plan_name }}</h3>
                    <div class="plan-price">@if(($plan->billing_type ?? '') === 'usage')<strong>Usage-based</strong>@elseif(($plan->billing_type ?? '') === 'custom')<strong>Custom</strong>@elseif(($plan->billing_type ?? '') === 'included')<strong>Included</strong>@elseif((float)$plan->monthly_price === 0.0 && $plan->monthly_price !== null)<strong>Free</strong>@elseif($plan->monthly_price !== null)<strong>{{ strtoupper($plan->currency ?? 'USD') }} {{ rtrim(rtrim(number_format((float)$plan->monthly_price,2), '0'), '.') }}</strong><span>/month</span>@else<strong>Custom</strong>@endif</div>
                    @if($plan->yearly_price)<p class="yearly-price">{{ strtoupper($plan->currency ?? 'USD') }} {{ number_format((float)$plan->yearly_price,2) }} billed yearly</p>@endif
                    @if($plan->api_price_label)<p class="api-price"><i data-lucide="code-2"></i>{{ $plan->api_price_label }}</p>@endif
                    <p class="api-price"><i data-lucide="shield-check"></i>{{ ucfirst($plan->freshness) }}@if($plan->last_verified_at) · verified {{ $plan->last_verified_at->diffForHumans() }}@endif</p>
                    <ul>
                        @foreach(preg_split('/[\r\n,;]+/', (string)$plan->limits, -1, PREG_SPLIT_NO_EMPTY) ?: [] as $limit)<li><i data-lucide="check"></i>{{ trim($limit) }}</li>@endforeach
                        @if($plan->credits)<li><i data-lucide="check"></i>{{ $plan->credits }}</li>@endif
                    </ul>
                </article>
                @endforeach
            </div>
            @else
            <div class="pricing-fallback"><div><i data-lucide="wallet-cards"></i></div><div><h3>{{ $priceLabel }}</h3><p>Detailed plan-level pricing has not been added yet.</p></div></div>
            @endif
        </section>

        @if($benchmarkResults->isNotEmpty() || $tool->benchmark_score)
        <section class="detail-panel benchmark-intelligence-panel" id="benchmarks">
            <div class="detail-section-head"><div><span>Evidence-separated performance</span><h2>Verified benchmarks & scores</h2><p>AI Orbit keeps technical tests, product-experience evidence and independent research in separate semantic classes. Only comparable results may contribute to the same composite.</p></div><i data-lucide="gauge"></i></div>
            @if($benchmarkResults->isNotEmpty())
                @foreach($benchmarkGroups as $benchmarkClass => $groupResults)
                    @php
                        $benchmarkClassLabel = \App\Models\Benchmark::classLabel($benchmarkClass);
                        $benchmarkClassDescription = match($benchmarkClass) {
                            \App\Models\Benchmark::CLASS_TECHNICAL => 'Measured technical performance such as accuracy, reasoning, coding, latency or task execution.',
                            \App\Models\Benchmark::CLASS_PRODUCT_EXPERIENCE => 'User/reviewer product-experience evidence. This is not treated as a technical benchmark.',
                            \App\Models\Benchmark::CLASS_INDEPENDENT_RESEARCH => 'Independent research or third-party evaluation evidence shown separately from technical composites.',
                            \App\Models\Benchmark::CLASS_AI_ORBIT_TESTED => 'First-party AI Orbit testing. It remains separate from external technical benchmarks.',
                            default => 'Evidence not yet safe to place in a comparable benchmark class; excluded from universal composites.',
                        };
                        $classComposite = $benchmarkClassComposites[$benchmarkClass] ?? null;
                    @endphp
                    <div class="benchmark-class-group">
                        <div class="benchmark-class-head">
                            <div><span>{{ $benchmarkClassLabel }}</span><p>{{ $benchmarkClassDescription }}</p></div>
                            @if($classComposite !== null && $benchmarkClass !== \App\Models\Benchmark::CLASS_UNCLASSIFIED)
                                <strong>{{ number_format((float)$classComposite,1) }}<small>/100 class composite</small></strong>
                            @else
                                <strong class="is-evidence-only">Evidence only</strong>
                            @endif
                        </div>
                        <div class="benchmark-intel-list">
                            @foreach($groupResults->take(8) as $result)
                            @php
                                $benchmark = $result->benchmark;
                                $context = $benchmarkContexts->get($result->id, []);
                                $min = (float) ($benchmark->min_score ?? 0);
                                $max = (float) ($benchmark->max_score ?: 100);
                                $range = max(.000001, $max - $min);
                                $rawPosition = (((float) $result->score - $min) / $range) * 100;
                                $pct = max(0, min(100, $benchmark->higher_is_better ? $rawPosition : 100 - $rawPosition));
                                $score = (float) $result->score;
                                $scoreDecimals = abs($score - round($score, 1)) > .0001 ? 2 : 1;
                                $gap = isset($context['gap']) ? (float) $context['gap'] : null;
                                $gapDecimals = $gap !== null && abs($gap - round($gap, 1)) > .0001 ? 2 : 1;
                                $sourceType = match($result->source_type) {
                                    'research_paper' => 'Research paper',
                                    'benchmark_org' => 'Benchmark org',
                                    'official' => 'Official source',
                                    'independent' => 'Independent',
                                    'ai_hub' => 'AI Orbit',
                                    'community' => 'Community evidence',
                                    default => $result->source_type ? Str::headline($result->source_type) : 'Verified source',
                                };
                                $sourceUrl = $result->source_url ?: $benchmark->official_url;
                                $methodologyUrl = $benchmark->methodology_url;
                            @endphp
                            <article class="benchmark-intel-card">
                                <div class="benchmark-intel-top">
                                    <div class="benchmark-intel-copy">
                                        <div class="benchmark-badges">
                                            <span class="benchmark-category-chip">{{ $benchmark->category ?: 'Benchmark' }}</span>
                                            <span class="benchmark-class-chip">{{ $benchmark->benchmark_class_label }}</span>
                                            <span class="benchmark-verified-chip"><i data-lucide="badge-check"></i>Verified</span>
                                            <span class="benchmark-source-chip">{{ $sourceType }}</span>
                                        </div>
                                        <h3>@if($benchmark->is_active && $benchmark->slug)<a class="benchmark-profile-link" href="{{ route('benchmarks.show',$benchmark) }}">{{ $benchmark->name }}<i data-lucide="arrow-up-right"></i></a>@else{{ $benchmark->name }}@endif</h3>
                                        <div class="benchmark-tested-version"><span>Tested product / version</span><strong>{{ $result->model_version ?: $tool->name }}</strong></div>
                                    </div>
                                    <div class="benchmark-intel-score">
                                        <div><strong>{{ number_format($score, $scoreDecimals) }}</strong><small>@if($benchmark->unit && !in_array($benchmark->unit,['%','points'],true)) {{ $benchmark->unit }}@elseif($max > 0) / {{ number_format($max,0) }}@endif</small></div>
                                        @if(!empty($context['rank']))<span class="benchmark-rank-chip">#{{ $context['rank'] }} of {{ $context['total'] }}</span>@endif
                                    </div>
                                </div>

                                <div class="benchmark-performance-track" aria-label="Normalized position within this benchmark"><i style="width:{{ $pct }}%"></i></div>

                                <div class="benchmark-context-grid">
                                    <div><span>Same-benchmark rank</span><strong>@if(!empty($context['rank']))#{{ $context['rank'] }} of {{ $context['total'] }} tools @else Not available @endif</strong></div>
                                    <div><span>Scoring direction</span><strong>{{ $benchmark->higher_is_better ? 'Higher is better' : 'Lower is better' }}</strong></div>
                                    <div><span>Leader context</span><strong>
                                        @if(($context['total'] ?? 0) === 1)Only verified tool
                                        @elseif(!empty($context['is_leader']))Current verified leader
                                        @elseif($gap !== null && !empty($context['leader_name'])){{ number_format($gap,$gapDecimals) }} pts behind {{ $context['leader_name'] }}
                                        @else Comparison unavailable
                                        @endif
                                    </strong></div>
                                    <div><span>Tested</span><strong>{{ $result->tested_at?->format('M j, Y') ?: 'Date not published' }}</strong></div>
                                </div>

                                <div class="benchmark-intel-foot">
                                    <div>
                                        @if($result->source_name)<span><i data-lucide="database"></i>{{ $result->source_name }}</span>@endif
                                        @if($result->verified_at)<span><i data-lucide="shield-check"></i>AI Orbit verified {{ $result->verified_at->format('M j, Y') }}</span>@endif
                                    </div>
                                    <div class="benchmark-source-actions">
                                        @if($sourceUrl)<a href="{{ $sourceUrl }}" target="_blank" rel="noopener noreferrer">View source<i data-lucide="arrow-up-right"></i></a>@endif
                                        @if($methodologyUrl && $methodologyUrl !== $sourceUrl)<a href="{{ $methodologyUrl }}" target="_blank" rel="noopener noreferrer">Methodology<i data-lucide="book-open-check"></i></a>@endif
                                    </div>
                                </div>
                            </article>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            @elseif($tool->benchmark_score)
                <div class="single-score-card"><div class="score-ring" style="--score:{{ max(0,min(100,(float)$tool->benchmark_score)) }}"><span>{{ number_format((float)$tool->benchmark_score,1) }}</span></div><div><small>Legacy benchmark summary</small><h3>{{ $tool->name }} stored benchmark score</h3><p>No structured benchmark evidence is attached yet. This legacy value is shown only for compatibility and must not be interpreted as a cross-methodology quality score.</p></div></div>
            @else
                <p class="detail-empty">No verified benchmark results are published for {{ $tool->name }} yet.</p>
            @endif
        </section>
        @endif

        @if($editorialReviews->isNotEmpty() || $pros->isNotEmpty() || $cons->isNotEmpty())
        <section class="detail-panel" id="reviews">
            <div class="detail-section-head"><div><span>Editorial reviews</span><h2>Expert & editorial reviews</h2><p>AI Orbit editorial assessment is shown separately from community ratings.</p></div><i data-lucide="messages-square"></i></div>
            @if($pros->isNotEmpty() || $cons->isNotEmpty())
            <div class="pros-cons-grid">
                <div class="pros-box"><h3><i data-lucide="circle-check-big"></i>Pros</h3>@forelse($pros as $item)<p><i data-lucide="check"></i>{{ $item }}</p>@empty<p>No editorial pros published yet.</p>@endforelse</div>
                <div class="cons-box"><h3><i data-lucide="circle-minus"></i>Cons</h3>@forelse($cons as $item)<p><i data-lucide="minus"></i>{{ $item }}</p>@empty<p>No editorial cons published yet.</p>@endforelse</div>
            </div>
            @endif
            @if($editorialReviews->isNotEmpty())
            <div class="review-detail-list">
                @foreach($editorialReviews->take(4) as $review)
                <article><div class="review-detail-head"><div class="review-avatar">{{ strtoupper(substr($review->user?->name ?: 'AI Orbit',0,1)) }}</div><div><h3>{{ $review->user?->name ?: 'AI Orbit Editorial' }}</h3><span>Editorial review • {{ $review->created_at?->format('M j, Y') }}</span></div><b><i data-lucide="star"></i>{{ number_format((float)$review->rating,1) }}</b></div>@if($review->verdict)<h4>{{ $review->verdict }}</h4>@endif<p>{{ $review->body }}</p></article>
                @endforeach
            </div>
            @endif
        </section>
        @endif

        @if($relatedComparisons->isNotEmpty())
        <section class="detail-panel" id="comparisons">
            <div class="detail-section-head"><div><span>Comparisons</span><h2>{{ $tool->name }} side-by-side comparisons</h2><p>Published comparisons connect this tool with other current catalog products.</p></div><i data-lucide="scale"></i></div>
            <div class="tool-comparison-grid">
                @foreach($relatedComparisons as $comparison)
                    <a href="{{ route('comparisons.show',$comparison) }}">
                        <span><i data-lucide="scale"></i><b>{{ $comparison->title }}</b><small>{{ $comparison->last_verified_at ? 'Verified '.$comparison->last_verified_at->format('M j, Y') : 'Published comparison' }}</small></span>
                        <i data-lucide="arrow-up-right"></i>
                    </a>
                @endforeach
            </div>
        </section>
        @endif

        @if($relatedArticles->isNotEmpty())
        <section class="detail-panel semantic-guides-panel" id="guides">
            <div class="detail-section-head"><div><span>Guides & analysis</span><h2>Research related to {{ $tool->name }}</h2><p>Only published guides with a direct tool, provider or category relationship are shown here.</p></div><i data-lucide="book-open-check"></i></div>
            <div class="semantic-guide-grid">
                @foreach($relatedArticles as $article)
                <a href="{{ route('articles.show',$article) }}" class="semantic-guide-card">
                    <span class="semantic-guide-meta"><b>{{ $article->category ?: 'AI Guide' }}</b>@if($article->published_at)<small>{{ $article->published_at->format('M j, Y') }}</small>@endif</span>
                    <h3>{{ $article->title }}</h3>
                    <p>{{ Str::limit($article->summary,120) }}</p>
                    <span class="semantic-guide-link">Read guide <i data-lucide="arrow-right"></i></span>
                </a>
                @endforeach
            </div>
        </section>
        @endif

        @if($relatedTools->isNotEmpty())
        <section class="detail-panel" id="alternatives">
            <div class="detail-section-head"><div><span>Alternatives</span><h2>Evidence-based similar tools</h2><p>Ranked by use-case, capability, pricing, platform, taxonomy and compatible benchmark overlap—not popularity alone.</p></div><i data-lucide="shuffle"></i></div>
            <div class="alternative-grid">
                @foreach($relatedTools as $related)
                <a href="{{ route('tools.show', $related) }}" class="alternative-card"><img src="{{ $related->logo_url }}" alt="{{ $related->name }} logo"><div><h3>{{ $related->name }}</h3><p>{{ $related->category?->name ?: 'AI Tool' }} • {{ $related->company?->name ?: 'Independent' }}</p><span><i data-lucide="scan-search"></i><b>{{ number_format((float)$related->alternative_match_score,0) }}% match</b>@if(!empty($related->alternative_match_reasons))<small class="alternative-reasons">{{ implode(' · ', $related->alternative_match_reasons) }}</small>@endif</span></div><i data-lucide="arrow-up-right"></i></a>
                @endforeach
            </div>
        </section>
        @endif
    </div>

    <aside class="tool-detail-sidebar">
        <section class="sidebar-card summary-card">
            <div class="sidebar-title"><span>At a glance</span><i data-lucide="scan-eye"></i></div>
            <dl>
                <div><dt>Rating</dt><dd><i data-lucide="star"></i>{{ number_format((float)$tool->rating,1) }}/5</dd></div>
                <div><dt>Pricing</dt><dd>{{ $priceLabel }}</dd></div>
                <div><dt>Category</dt><dd>{{ $tool->category?->name ?: 'AI Tool' }}</dd></div>
                @if($tool->launch_date)<div><dt>Launched</dt><dd>{{ $tool->launch_date->format('M Y') }}</dd></div>@endif
                <div><dt>Platforms</dt><dd>{{ $platforms->join(', ') ?: 'Not specified' }}</dd></div>
                @if($tool->company)<div><dt>Developer</dt><dd>{{ $tool->company->name }}</dd></div>@endif
                <div><dt>Product status</dt><dd>@if(($tool->product_status ?? 'unknown') === 'unknown')Not yet verified@else{{ $tool->product_status_label }} @if($tool->product_status_verified_at)<i data-lucide="badge-check"></i>@endif @endif</dd></div>
                @if($productStatusSource && ($tool->product_status ?? 'unknown') !== 'unknown')<div><dt>Lifecycle evidence</dt><dd><a href="{{ $productStatusSource->source_url }}" target="_blank" rel="noopener noreferrer nofollow">{{ $tool->product_status_verified_at ? 'Verified source' : 'Source · pending verification' }}</a></dd></div>@endif
                @if($primarySource)<div><dt>Source</dt><dd><a href="{{ $primarySource->source_url }}" target="_blank" rel="noopener noreferrer nofollow">{{ $primarySource->verification_status === 'verified' ? 'Verified official source' : 'Official source · verification pending' }}</a></dd></div>@endif
            </dl>
        </section>

        <section class="sidebar-card data-confidence-panel" aria-label="AI Orbit data confidence">
            <header class="dc-head">
                <div class="dc-heading">
                    <span class="dc-icon" aria-hidden="true"><i data-lucide="shield-check"></i></span>
                    <div>
                        <span class="dc-kicker">Data quality</span>
                        <h3>AI Orbit confidence</h3>
                    </div>
                </div>
                <span class="dc-status">{{ $dataConfidence['label'] }}</span>
            </header>

            <div class="dc-score-row">
                <div class="dc-score" aria-label="Confidence score {{ $dataConfidence['score'] }} out of 100">
                    <strong>{{ $dataConfidence['score'] }}</strong><span>/100</span>
                </div>
                <div class="dc-score-copy">
                    <strong>Profile confidence</strong>
                    <span>Evidence coverage &amp; freshness</span>
                </div>
            </div>

            <div class="dc-meter" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ (int) $dataConfidence['score'] }}">
                <span style="width: {{ max(0, min(100, (int) $dataConfidence['score'])) }}%"></span>
            </div>

            <div class="dc-facts">
                <div class="dc-fact">
                    <span class="dc-fact-label"><i data-lucide="refresh-cw"></i>Freshness</span>
                    <strong><span class="dc-dot"></span>{{ Str::headline($dataConfidence['freshness']) }}</strong>
                </div>
                <div class="dc-fact">
                    <span class="dc-fact-label"><i data-lucide="link-2"></i>Verified sources</span>
                    <strong>{{ $dataConfidence['verified_sources'] }}/{{ $dataConfidence['total_sources'] }}</strong>
                </div>
                <div class="dc-fact">
                    <span class="dc-fact-label"><i data-lucide="badge-check"></i>Verified claims</span>
                    <strong>{{ $dataConfidence['verified_claims'] }}/{{ $dataConfidence['known_claims'] }}</strong>
                </div>
                <div class="dc-fact">
                    <span class="dc-fact-label"><i data-lucide="calendar-check"></i>Last verified</span>
                    <strong>{{ $dataConfidence['last_verified_at']?->format('M j, Y') ?? 'Not yet verified' }}</strong>
                </div>
            </div>

            <footer class="dc-note">
                <i data-lucide="info"></i>
                <span>Confidence reflects profile evidence and completeness, not product quality.</span>
            </footer>
        </section>

        @if($tool->company)
        <section class="sidebar-card company-card-detail">
            <div class="sidebar-title"><span>Company</span><i data-lucide="building-2"></i></div>
            <div class="company-detail-row">@if($tool->company->logo_path)<img src="{{ $tool->company->logo_url }}" alt="{{ $tool->company->name }} logo">@else<div class="company-letter">{{ strtoupper(substr($tool->company->name,0,1)) }}</div>@endif<div><h3>@if(in_array($tool->company->status, ['active','acquired'], true))<a href="{{ route('companies.show',$tool->company) }}">{{ $tool->company->name }}</a>@else{{ $tool->company->name }}@endif</h3>@if($tool->company->founded_year)<span>Founded {{ $tool->company->founded_year }}</span>@endif</div></div>
            @if($tool->company->description)<p>{{ Str::limit($tool->company->description,160) }}</p>@endif
            <div class="company-mini-stats"><span><b>{{ $tool->company->published_tools_count ?? 0 }}</b>Tools</span><span><b>{{ $tool->company->active_models_count ?? 0 }}</b>Models</span></div>
            @if(in_array($tool->company->status, ['active','acquired'], true))<a class="sidebar-entity-link" href="{{ route('companies.show',$tool->company) }}">Explore {{ $tool->company->name }} profile <i data-lucide="arrow-right"></i></a>@endif
        </section>
        @endif

        @if($tool->models->isNotEmpty())
        <section class="sidebar-card">
            <div class="sidebar-title"><span>Related models</span><i data-lucide="cpu"></i></div>
            <div class="related-model-list">@foreach($tool->models->take(4) as $model)<a href="{{ route('models.show',$model) }}"><img src="{{ $model->logo_url }}" alt="{{ $model->name }} AI model logo"><span><b>{{ $model->name }}</b><small>@if($model->context_window){{ $model->context_window }} context @else{{ $model->version ?: 'AI model' }}@endif</small></span>@if($model->benchmark_score)<em>{{ number_format((float)$model->benchmark_score,1) }}</em>@endif</a>@endforeach</div>
        </section>
        @endif

        @if($latestNews->isNotEmpty())
        <section class="sidebar-card">
            <div class="sidebar-title"><span>Latest relevant updates</span><i data-lucide="radio"></i></div>
            <div class="tool-news-list">@foreach($latestNews as $news)<a href="{{ route('news.show',$news) }}"><article>@if($news->image_path)<img src="{{ $news->image_url }}" alt="{{ $news->headline }}">@endif<div><span>{{ $news->category ?: 'AI News' }} @if($news->published_at)• {{ $news->published_at->diffForHumans() }}@endif</span><h3>{{ Str::limit($news->headline,75) }}</h3></div></article></a>@endforeach</div>
        </section>
        @endif
    </aside>
</section>

<section class="tool-detail-wrap tool-detail-cta">
    <div><span><i data-lucide="scale"></i>Make a confident choice</span><h2>Is {{ $tool->name }} right for your workflow?</h2><p>Compare features, pricing and alternatives before you decide.</p></div>
    <div><a href="{{ route('tools.index') }}" class="detail-secondary-btn">Explore more tools</a>@if($tool->website)<a href="{{ $tool->website }}" target="_blank" rel="noopener noreferrer nofollow" class="detail-primary-btn">Try {{ $tool->name }}<i data-lucide="arrow-up-right"></i></a>@endif</div>
</section>



<section class="detail-panel seo-faq-panel" id="faq">
    <div class="detail-section-head">
        <div><span>Common questions</span><h2>{{ $tool->name }} FAQ</h2><p>Quick answers based on the information currently available in this AI Orbit profile.</p></div>
        <i data-lucide="circle-help"></i>
    </div>
    <div class="seo-faq-list">
        @foreach($seo['faq'] as $item)
            <details>
                <summary>{{ $item['q'] }}<i data-lucide="chevron-down"></i></summary>
                <p>{{ $item['a'] }}</p>
            </details>
        @endforeach
    </div>
</section>
@endsection

@push('scripts')
<script src="{{ asset('js/frontend/tools-show.js') }}"></script>
@endpush
