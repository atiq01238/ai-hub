@extends('frontend.layouts.app')

@section('title', $seo['title'] . ' | AI Hub')
@section('meta_description', $seo['description'])

@push('head')
<link rel="canonical" href="{{ route('tools.show', $tool) }}">
<meta name="robots" content="index,follow,max-image-preview:large,max-snippet:-1,max-video-preview:-1">
<meta property="og:type" content="website">
<meta property="og:title" content="{{ $seo['title'] }}">
<meta property="og:description" content="{{ $seo['description'] }}">
<meta property="og:url" content="{{ route('tools.show', $tool) }}">
<meta property="og:image" content="{{ $tool->og_image_url ?: $tool->logo_url }}">
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="{{ $seo['title'] }}">
<meta name="twitter:description" content="{{ $seo['description'] }}">
<meta name="twitter:image" content="{{ $tool->og_image_url ?: $tool->logo_url }}">
@foreach($seoSchemas as $schema)
<script type="application/ld+json">{!! json_encode(['@context'=>'https://schema.org'] + $schema, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE) !!}</script>
@endforeach
@endpush

@push('styles')
<link rel="stylesheet" href="{{ asset('css/frontend/tools-show.css') }}">
@endpush

@section('content')
@php
    $logo = $tool->logo_url;
    $cover = $tool->cover_image_url;
    $pricing = collect($tool->pricing_models ?? []);
    $priceLabel = $pricing->contains('Free') ? ($pricing->contains('Paid') ? 'Free + Paid' : 'Free') : ($pricing->first() ?: 'Pricing varies');
    $publishedReviews = $tool->reviews;
    $reviewCount = $publishedReviews->count();
    $pros = collect($editorReview?->pros ?? [])->filter();
    $cons = collect($editorReview?->cons ?? [])->filter();
    $benchmarkResults = $tool->benchmarkResults->filter(fn($result) => $result->benchmark);
    $benchmarkMax = max(1, (float) ($benchmarkResults->max(fn($result) => $result->benchmark?->max_score) ?: 100));
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
            @if($tool->category)<a href="{{ route('tools.index', ['category'=>$tool->category->slug]) }}">{{ $tool->category->name }}</a><i data-lucide="chevron-right"></i>@endif
            <span>{{ $tool->name }}</span>
        </nav>

        <div class="tool-hero-main">
            <div class="tool-identity-block">
                <img class="tool-detail-logo" src="{{ $logo }}" alt="{{ $tool->name }} logo">
                <div class="tool-detail-title">
                    <div class="tool-eyebrow-row">
                        @if($tool->category)<span class="category-pill">{{ $tool->category->name }}</span>@endif
                        @if($tool->status === 'published')<span class="verified-pill"><i data-lucide="badge-check"></i> Published</span>@endif
                        @if($tool->launch_date)<span class="launch-pill">Since {{ $tool->launch_date->format('Y') }}</span>@endif
                    </div>
                    <h1>{{ $tool->name }}</h1>
                    <p class="tool-company-line">by @if($tool->company)<strong>{{ $tool->company->name }}</strong>@else<strong>Independent</strong>@endif @if($tool->subcategoryTerm)<span>•</span>{{ $tool->subcategoryTerm->name }}@elseif($tool->subcategory)<span>•</span>{{ $tool->subcategory }}@endif</p>
                    <p class="tool-hero-description">{{ $tool->short_description ?: Str::limit(strip_tags($tool->description), 220) }}</p>
                </div>
            </div>

            <div class="tool-score-panel">
                <div class="hero-rating"><i data-lucide="star"></i><strong>{{ number_format((float)$tool->rating, 1) }}</strong><span>/ 5</span></div>
                <div class="hero-rating-copy"><b>AI Hub rating</b><small>{{ $reviewCount ? $reviewCount . ' published ' . Str::plural('review', $reviewCount) : 'Editorial profile' }}</small></div>
                @if($tool->benchmark_score)<div class="hero-benchmark"><span>Benchmark</span><strong>{{ number_format((float)$tool->benchmark_score,1) }}</strong></div>@endif
            </div>
        </div>

        <div class="tool-hero-bottom">
            <div class="tool-quick-facts">
                <span><i data-lucide="badge-dollar-sign"></i><small>Pricing</small><b>{{ $priceLabel }}</b></span>
                <span><i data-lucide="trending-up"></i><small>Popularity</small><b>{{ number_format((float)$tool->popularity,0) }}%</b></span>
                <span><i data-lucide="monitor-smartphone"></i><small>Platforms</small><b>{{ $platforms->take(2)->join(' + ') ?: 'Web' }}</b></span>
                @if($tool->company)<span><i data-lucide="building-2"></i><small>Company</small><b>{{ $tool->company->name }}</b></span>@endif
            </div>
            <div class="tool-hero-actions">
                <button type="button" class="detail-secondary-btn" data-save-item data-save-type="tool" data-save-id="{{ $tool->id }}" aria-pressed="false"><i data-lucide="bookmark"></i><span data-save-label data-default-label="Save">Save</span></button>
                <a href="{{ route('comparisons.builder', ['type' => 'tool', 'item' => $tool->id]) }}" class="detail-secondary-btn"><i data-lucide="scale"></i><span>Compare</span></a>
                @if($tool->website)<a href="{{ $tool->website }}" target="_blank" rel="noopener noreferrer nofollow" class="detail-primary-btn">Visit Website<i data-lucide="arrow-up-right"></i></a>@endif
            </div>
        </div>
    </div>
</section>

<div class="tool-sticky-nav" data-detail-nav>
    <div class="tool-detail-wrap sticky-nav-inner">
        <div class="detail-nav-links">
            <a href="#overview" class="active">Overview</a>
            @if($capabilities->isNotEmpty() || $tool->featureTerms->isNotEmpty())<a href="#features">Features</a>@endif
            <a href="#pricing">Pricing</a>
            @if($benchmarkResults->isNotEmpty() || $tool->benchmark_score)<a href="#benchmarks">Benchmarks</a>@endif
            @if($publishedReviews->isNotEmpty() || $pros->isNotEmpty() || $cons->isNotEmpty())<a href="#reviews">Reviews</a>@endif
            @if($relatedTools->isNotEmpty())<a href="#alternatives">Alternatives</a>@endif
        </div>
    </div>
</div>

<section class="tool-detail-wrap tool-detail-content">
    <div class="tool-detail-main">
        <section class="detail-panel overview-panel" id="overview">
            <div class="detail-section-head"><div><span>Overview</span><h2>What is {{ $tool->name }}?</h2></div><i data-lucide="sparkles"></i></div>
            <div class="rich-description">{!! nl2br(e($tool->overview)) !!}</div>
            @if($capabilities->isNotEmpty())
            <div class="best-for-box"><span><i data-lucide="target"></i>Best for</span><div>@foreach($capabilities->take(5) as $capability)<b>{{ $capability }}</b>@endforeach</div></div>
            @endif
        </section>

        @if($capabilities->isNotEmpty() || $tool->featureTerms->isNotEmpty())
        <section class="detail-panel" id="features">
            <div class="detail-section-head"><div><span>Capabilities</span><h2>Features & use cases</h2><p>Core capabilities listed for {{ $tool->name }}.</p></div><i data-lucide="blocks"></i></div>
            <div class="feature-detail-grid">
                @forelse($capabilities as $capability)
                    <article><span><i data-lucide="check"></i></span><div><h3>{{ $capability }}</h3><p>Available as part of {{ $tool->name }}'s current capability set.</p></div></article>
                @empty
                    @forelse($tool->featureTerms as $feature)<article><span><i data-lucide="check"></i></span><div><h3>{{ $feature->name }}</h3><p>{{ $feature->description ?? 'Supported capability.' }}</p></div></article>@empty<p class="detail-empty">Capability details have not been published yet.</p>@endforelse
                @endforelse
            </div>
            @if($tool->featureTerms->isNotEmpty())
            <div class="taxonomy-link-row"><strong>Explore capabilities</strong><div>@foreach($tool->featureTerms->take(10) as $feature)<a href="{{ route('features.show',$feature) }}"><i data-lucide="{{ $feature->icon ?: 'sparkles' }}"></i>{{ $feature->name }}</a>@endforeach</div></div>
            @endif
            @if($tool->useCaseTerms->isNotEmpty())
            <div class="taxonomy-link-row use-cases"><strong>Best use cases</strong><div>@foreach($tool->useCaseTerms->take(10) as $useCase)<a href="{{ route('use-cases.show',$useCase) }}"><i data-lucide="target"></i>{{ $useCase->name }}</a>@endforeach</div></div>
            @endif
            @if($platforms->isNotEmpty() || $tags->isNotEmpty())
            <div class="platform-tag-row">
                @foreach($platforms as $platform)<span><i data-lucide="monitor"></i>{{ $platform }}</span>@endforeach
                @foreach($tags->take(8) as $tag)<span class="soft-tag">#{{ $tag }}</span>@endforeach
            </div>
            @endif
        </section>
        @endif

        <section class="detail-panel" id="pricing">
            <div class="detail-section-head"><div><span>Pricing</span><h2>{{ $tool->name }} pricing plans</h2><p>Pricing stored in AI Hub's pricing database. Always verify final rates on the provider website.</p></div><i data-lucide="badge-dollar-sign"></i></div>
            @if($pricingPlans->isNotEmpty())
            <div class="pricing-detail-grid">
                @foreach($pricingPlans as $plan)
                <article class="pricing-detail-card {{ $loop->index === 1 ? 'featured' : '' }}">
                    @if($loop->index === 1)<span class="plan-badge">Popular</span>@endif
                    <small>{{ $tool->name }}</small><h3>{{ $plan->plan_name }}</h3>
                    <div class="plan-price">@if((float)$plan->monthly_price === 0.0)<strong>Free</strong>@elseif($plan->monthly_price !== null)<strong>{{ strtoupper($plan->currency ?? 'USD') }} {{ rtrim(rtrim(number_format((float)$plan->monthly_price,2), '0'), '.') }}</strong><span>/month</span>@else<strong>Custom</strong>@endif</div>
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
        <section class="detail-panel" id="benchmarks">
            <div class="detail-section-head"><div><span>Performance</span><h2>Benchmarks & scores</h2><p>Verified benchmark results and AI Hub scoring for this tool.</p></div><i data-lucide="gauge"></i></div>
            @if($benchmarkResults->isNotEmpty())
            <div class="benchmark-detail-list">
                @foreach($benchmarkResults->take(8) as $result)
                @php $max=(float)($result->benchmark->max_score ?: 100); $pct=max(0,min(100,((float)$result->score/max(1,$max))*100)); @endphp
                <article><div class="benchmark-copy"><span>{{ $result->benchmark->category ?: 'Benchmark' }}</span><h3>{{ $result->benchmark->name }}</h3>@if($result->tested_at)<small>Tested {{ $result->tested_at->format('M Y') }}@if($result->source_name) • {{ $result->source_name }}@endif</small>@endif</div><div class="benchmark-score"><strong>{{ number_format((float)$result->score,1) }}</strong><small>/ {{ number_format($max,0) }}</small></div><div class="benchmark-track"><i style="width:{{ $pct }}%"></i></div></article>
                @endforeach
            </div>
            @elseif($tool->benchmark_score)
            <div class="single-score-card"><div class="score-ring" style="--score:{{ max(0,min(100,(float)$tool->benchmark_score)) }}"><span>{{ number_format((float)$tool->benchmark_score,1) }}</span></div><div><small>AI Hub benchmark score</small><h3>{{ $tool->name }} overall performance</h3><p>Detailed benchmark rows have not been published yet, but an aggregate score is available.</p></div></div>
            @else
            <p class="detail-empty">No verified benchmark results are published for {{ $tool->name }} yet.</p>
            @endif
        </section>
        @endif

        @if($publishedReviews->isNotEmpty() || $pros->isNotEmpty() || $cons->isNotEmpty())
        <section class="detail-panel" id="reviews">
            <div class="detail-section-head"><div><span>Reviews</span><h2>What reviewers say</h2><p>Published reviews and rating evidence for {{ $tool->name }}.</p></div><i data-lucide="messages-square"></i></div>
            @if($pros->isNotEmpty() || $cons->isNotEmpty())
            <div class="pros-cons-grid">
                <div class="pros-box"><h3><i data-lucide="circle-check-big"></i>Pros</h3>@forelse($pros as $item)<p><i data-lucide="check"></i>{{ $item }}</p>@empty<p>No editorial pros published yet.</p>@endforelse</div>
                <div class="cons-box"><h3><i data-lucide="circle-minus"></i>Cons</h3>@forelse($cons as $item)<p><i data-lucide="minus"></i>{{ $item }}</p>@empty<p>No editorial cons published yet.</p>@endforelse</div>
            </div>
            @endif
            @if($publishedReviews->isNotEmpty())
            <div class="review-detail-list">
                @foreach($publishedReviews->take(4) as $review)
                <article><div class="review-detail-head"><div class="review-avatar">{{ strtoupper(substr($review->user?->name ?: ($review->review_type === 'editorial' ? 'AI Hub' : 'R'),0,1)) }}</div><div><h3>{{ $review->user?->name ?: ($review->review_type === 'editorial' ? 'AI Hub Editorial' : 'Verified reviewer') }}</h3><span>{{ ucfirst($review->review_type) }} review • {{ $review->created_at?->format('M j, Y') }}</span></div><b><i data-lucide="star"></i>{{ number_format((float)$review->rating,1) }}</b></div>@if($review->verdict)<h4>{{ $review->verdict }}</h4>@endif<p>{{ $review->body }}</p></article>
                @endforeach
            </div>
            @else
            <p class="detail-empty">No published reviews are available yet.</p>
            @endif
        </section>
        @endif

        @if($relatedTools->isNotEmpty())
        <section class="detail-panel" id="alternatives">
            <div class="detail-section-head"><div><span>Alternatives</span><h2>Similar AI tools</h2><p>Other highly rated tools in related categories or from the same company.</p></div><i data-lucide="shuffle"></i></div>
            <div class="alternative-grid">
                @foreach($relatedTools as $related)
                <a href="{{ route('tools.show', $related) }}" class="alternative-card"><img src="{{ $related->logo_url }}" alt="{{ $related->name }} logo"><div><h3>{{ $related->name }}</h3><p>{{ $related->category?->name ?: 'AI Tool' }} • {{ $related->company?->name ?: 'Independent' }}</p><span><i data-lucide="star"></i>{{ number_format((float)$related->rating,1) }} <b>{{ collect($related->pricing_models ?? [])->contains('Free') ? 'Free option' : 'Paid' }}</b></span></div><i data-lucide="arrow-up-right"></i></a>
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
            </dl>
        </section>

        @if($tool->company)
        <section class="sidebar-card company-card-detail">
            <div class="sidebar-title"><span>Company</span><i data-lucide="building-2"></i></div>
            <div class="company-detail-row">@if($tool->company->logo_path)<img src="{{ $tool->company->logo_url }}" alt="{{ $tool->company->name }} logo">@else<div class="company-letter">{{ strtoupper(substr($tool->company->name,0,1)) }}</div>@endif<div><h3>{{ $tool->company->name }}</h3>@if($tool->company->founded_year)<span>Founded {{ $tool->company->founded_year }}</span>@endif</div></div>
            @if($tool->company->description)<p>{{ Str::limit($tool->company->description,160) }}</p>@endif
            <div class="company-mini-stats"><span><b>{{ $tool->company->tools()->where('status','published')->count() }}</b>Tools</span><span><b>{{ $tool->company->models()->whereIn('status',['active','preview'])->count() }}</b>Models</span></div>
        </section>
        @endif

        @if($tool->models->isNotEmpty())
        <section class="sidebar-card">
            <div class="sidebar-title"><span>Related models</span><i data-lucide="cpu"></i></div>
            <div class="related-model-list">@foreach($tool->models->take(4) as $model)<div><img src="{{ $model->logo_url }}" alt="{{ $model->name }}"><span><b>{{ $model->name }}</b><small>@if($model->context_window){{ $model->context_window }} context @else{{ $model->version ?: 'AI model' }}@endif</small></span>@if($model->benchmark_score)<em>{{ number_format((float)$model->benchmark_score,1) }}</em>@endif</div>@endforeach</div>
        </section>
        @endif

        @if($latestNews->isNotEmpty())
        <section class="sidebar-card">
            <div class="sidebar-title"><span>Latest news</span><i data-lucide="radio"></i></div>
            <div class="tool-news-list">@foreach($latestNews as $news)<article>@if($news->image_path)<img src="{{ $news->image_url }}" alt="">@endif<div><span>{{ $news->category ?: 'AI News' }} @if($news->published_at)• {{ $news->published_at->diffForHumans() }}@endif</span><h3>{{ Str::limit($news->headline,75) }}</h3></div></article>@endforeach</div>
        </section>
        @endif
    </aside>
</section>

<section class="tool-detail-wrap tool-detail-cta">
    <div><span><i data-lucide="scale"></i>Make a confident choice</span><h2>Is {{ $tool->name }} right for your workflow?</h2><p>Compare features, pricing and alternatives before you decide.</p></div>
    <div><a href="{{ route('tools.index') }}" class="detail-secondary-btn">Explore more tools</a>@if($tool->website)<a href="{{ $tool->website }}" target="_blank" rel="noopener noreferrer nofollow" class="detail-primary-btn">Try {{ $tool->name }}<i data-lucide="arrow-up-right"></i></a>@endif</div>
</section>

<script type="application/ld+json">
{!! json_encode([
    '@context' => 'https://schema.org',
    '@type' => 'SoftwareApplication',
    'name' => $tool->name,
    'applicationCategory' => $tool->category?->name ?: 'Artificial Intelligence',
    'operatingSystem' => $platforms->join(', ') ?: 'Web',
    'description' => strip_tags($tool->short_description ?: $tool->description),
    'url' => route('tools.show', $tool),
    'image' => $logo,
    'aggregateRating' => (float)$tool->rating > 0 ? ['@type'=>'AggregateRating','ratingValue'=>(float)$tool->rating,'bestRating'=>5,'ratingCount'=>max(1,$reviewCount)] : null,
], JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE) !!}
</script>

<section class="detail-panel seo-faq-panel" id="faq">
    <div class="detail-section-head">
        <div><span>Common questions</span><h2>{{ $tool->name }} FAQ</h2><p>Quick answers based on the information currently available in this AI Hub profile.</p></div>
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
