@extends('frontend.layouts.app')

@section('title', 'AI Orbit — Compare AI Tools, Models, Pricing & Benchmarks')
@section('meta_description', 'Research AI tools and models, compare pricing and verified benchmarks, and follow source-aware AI news with AI Orbit methodology and verification standards.')

@section('content')
<section class="hero home-hero home-hero-reference home-hero-compact">
    <div class="hero-grid"></div>
    <div class="hero-glow glow-a"></div>
    <div class="hero-glow glow-b"></div>
    <div class="hero-wave" aria-hidden="true"></div>

    <img class="hero-neural-brain"
         src="{{ asset('images/frontend/ai-neural-brain-560.webp') }}"
         srcset="{{ asset('images/frontend/ai-neural-brain-360.webp') }} 360w, {{ asset('images/frontend/ai-neural-brain-560.webp') }} 560w"
         sizes="(max-width: 600px) 300px, 470px"
         width="560" height="199"
         alt="" aria-hidden="true"
         fetchpriority="high" decoding="async">

    <div class="home-hero-shell">
        <div class="hero-content home-hero-copy">
            <h1>Discover. <span>Compare.</span> Master <em>AI.</em></h1>
            <p>Research AI tools and models, compare pricing and verified benchmarks, and follow source-aware AI news — all in one place.</p>

            <form class="global-search home-hero-search search-intelligence-shell" action="{{ route('search.index') }}" method="get" data-search-shell>
                <div class="hero-search-icon"><i data-lucide="search"></i></div>
                <input id="home-global-search" name="q" type="search" placeholder="Search AI tools, models, companies, news..." autocomplete="off" data-search-autocomplete>
                <button type="submit" aria-label="Search AI Orbit"><i data-lucide="search"></i><span>Search</span></button>
                <div class="search-live-results home-search-live-results" data-search-suggestions hidden></div>
            </form>

            <div class="home-hero-actions" aria-label="Primary homepage actions">
                <a class="home-hero-primary" href="{{ route('tools.index') }}">Explore AI Tools <i data-lucide="arrow-right"></i></a>
                <a class="home-hero-secondary" href="{{ route('comparisons.builder') }}">Compare AI <i data-lucide="git-compare-arrows"></i></a>
            </div>

            @if($categories->isNotEmpty())
                <div class="quick-chips home-hero-chips home-hero-chips-compact" aria-label="Popular AI categories">
                    @foreach($categories as $category)
                        <a href="{{ route('categories.show', $category) }}">{{ $category->name }}</a>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</section>

<div class="page-body home-compact-page">
    <section class="homepage-proof homepage-proof-compact panel" aria-label="AI Orbit research coverage and trust standards">
        <div class="homepage-proof-heading">
            <span class="homepage-proof-kicker"><i data-lucide="shield-check"></i> Research-driven AI discovery</span>
            <strong>Source-aware data. Clear verification. Practical comparisons.</strong>
        </div>

        <div class="homepage-proof-metrics" aria-label="AI Orbit catalog coverage">
            <a href="{{ route('tools.index') }}"><b>{{ number_format($homepageStats['tools']) }}</b><span>Tools</span></a>
            <a href="{{ route('models.index') }}"><b>{{ number_format($homepageStats['models']) }}</b><span>Models</span></a>
            <a href="{{ route('pricing.index') }}"><b>{{ number_format($homepageStats['pricing_plans']) }}</b><span>Pricing plans</span></a>
            <a href="{{ route('benchmarks.index') }}"><b>{{ number_format($homepageStats['verified_benchmarks']) }}</b><span>Verified results</span></a>
        </div>

        <div class="homepage-proof-links homepage-proof-links-compact">
            <a href="{{ route('methodology') }}">Methodology</a>
            <a href="{{ route('sourcing-verification') }}">Verification</a>
            <a href="{{ route('corrections-policy') }}">Corrections</a>
        </div>
    </section>

    @if($trendingTools->isNotEmpty())
        <section class="trending-strip panel home-trending-compact">
            <div class="trend-label"><span>🔥</span><strong>Trending on AI Orbit</strong></div>
            <div class="trend-items">
                @foreach($trendingTools as $tool)
                    <a href="{{ route('tools.show', $tool) }}" class="trend-item">
                        <img loading="lazy" decoding="async" src="{{ $tool->logo_url }}" alt="{{ $tool->name }} logo">
                        <span>{{ $tool->name }}</span>
                        @if($tool->trend_is_hot)
                            <span class="trend-hot-fire" aria-label="Gaining search clicks on AI Orbit" title="Gaining search clicks on AI Orbit">🔥</span>
                        @endif
                    </a>
                @endforeach
            </div>
            <a class="ghost-link" href="{{ route('trending.index') }}">View All <i data-lucide="arrow-right"></i></a>
        </section>
    @endif

    @if($bestTools->isNotEmpty())
        <div class="home-tools-showcase home-tools-showcase-classic home-tools-showcase-best-only">
            <section id="best-tools" class="panel tools-panel home-section-compact home-best-tools-classic home-best-tools-fullwidth">
                <div class="section-heading row-heading">
                    <div class="heading-left">
                        <div class="heading-icon gold"><i data-lucide="trophy"></i></div>
                        <div><h2>Best AI Tools</h2><p>Top rated tools across AI Orbit</p></div>
                    </div>
                    <a class="text-link" href="{{ route('tools.index') }}">View All <i data-lucide="arrow-right"></i></a>
                </div>

                @if($categories->isNotEmpty())
                    <div class="filter-tabs home-best-tool-tabs home-best-tool-tabs-desktop" data-tool-tabs aria-label="Filter Best AI Tools by category">
                        <button class="active" data-filter="all">All</button>
                        @foreach($categories->take(5) as $category)
                            <button data-filter="{{ $category->slug }}">{{ $category->name }}</button>
                        @endforeach
                    </div>
                @endif

                {{-- Desktop/tablet: keep the curated Best Tools ranking. --}}
                <div class="tool-grid home-classic-tool-grid home-best-tools-desktop" data-tool-grid>
                    @foreach($bestToolsPool as $tool)
                        <article class="tool-card home-classic-tool-card"
                                 data-category="{{ $tool->category?->slug }}"
                                 data-home-best-tool="1"
                                 data-default-visible="{{ $bestTools->contains('id', $tool->id) ? '1' : '0' }}"
                                 data-search="{{ strtolower($tool->name.' '.$tool->short_description.' '.($tool->company?->name ?? '')) }}">
                            <div class="tool-card-top">
                                <img loading="lazy" decoding="async" class="tool-logo" src="{{ $tool->logo_url }}" alt="{{ $tool->name }} logo">
                                <div class="tool-title">
                                    <h3><a class="home-entity-link" href="{{ route('tools.show', $tool) }}">{{ $tool->name }}</a></h3>
                                    <span>{{ $tool->subcategory ?: $tool->category?->name }}</span>
                                </div>
                                <div class="tool-rating">
                                    @if((float) $tool->rating > 0)
                                        <span>★ {{ number_format((float)$tool->rating, 1) }}/5</span>
                                    @else
                                        <span class="home-best-tool-unrated">Popular</span>
                                    @endif
                                </div>
                            </div>

                            @php($pricingLabels = array_values(array_filter(array_slice($tool->pricing_models ?? [], 0, 2))))
                            @if(!empty($pricingLabels))
                                <div class="tool-card-meta-line">
                                    <span class="badge">{{ implode(' + ', $pricingLabels) }}</span>
                                </div>
                            @endif

                            <p>{{ $tool->short_description }}</p>
                            <div class="card-actions">
                                <a class="primary-btn" href="{{ route('tools.show', $tool) }}">{{ \Illuminate\Support\Str::limit($tool->name, 20) }} details</a>
                                <a class="secondary-btn" href="{{ route('comparisons.builder', ['type' => 'tool', 'item' => $tool->id]) }}">Compare</a>
                            </div>
                        </article>
                    @endforeach
                </div>

                {{-- Mobile: exactly five random published tools, one card per row. --}}
                <div class="tool-grid home-classic-tool-grid home-best-tools-mobile" aria-label="Random AI tools to explore">
                    @foreach($mobileBestTools as $tool)
                        <article class="tool-card home-classic-tool-card">
                            <div class="tool-card-top">
                                <img loading="lazy" decoding="async" class="tool-logo" src="{{ $tool->logo_url }}" alt="{{ $tool->name }} logo">
                                <div class="tool-title">
                                    <h3><a class="home-entity-link" href="{{ route('tools.show', $tool) }}">{{ $tool->name }}</a></h3>
                                    <span>{{ $tool->subcategory ?: $tool->category?->name }}</span>
                                </div>
                                <div class="tool-rating">
                                    @if((float) $tool->rating > 0)
                                        <span>★ {{ number_format((float)$tool->rating, 1) }}/5</span>
                                    @else
                                        <span class="home-best-tool-unrated">Popular</span>
                                    @endif
                                </div>
                            </div>

                            @php($pricingLabels = array_values(array_filter(array_slice($tool->pricing_models ?? [], 0, 2))))
                            @if(!empty($pricingLabels))
                                <div class="tool-card-meta-line">
                                    <span class="badge">{{ implode(' + ', $pricingLabels) }}</span>
                                </div>
                            @endif

                            <p>{{ $tool->short_description }}</p>
                            <div class="card-actions">
                                <a class="primary-btn" href="{{ route('tools.show', $tool) }}">{{ \Illuminate\Support\Str::limit($tool->name, 20) }} details</a>
                                <a class="secondary-btn" href="{{ route('comparisons.builder', ['type' => 'tool', 'item' => $tool->id]) }}">Compare</a>
                            </div>
                        </article>
                    @endforeach
                </div>
                <div class="empty-state" data-empty-state hidden>No tools match this category.</div>
            </section>
        </div>
    @endif

    @if($comparisons->isNotEmpty())
        <section id="comparisons" class="panel home-section-compact home-comparisons-panel">
            <div class="section-heading row-heading">
                <div class="heading-left">
                    <div class="heading-icon purple"><i data-lucide="git-compare-arrows"></i></div>
                    <div><h2>Popular Comparisons</h2><p>Quick head-to-head choices with deeper comparison pages one click away</p></div>
                </div>
                <a class="text-link" href="{{ route('comparisons.index') }}">View All <i data-lucide="arrow-right"></i></a>
            </div>

            <div class="comparison-grid-wide home-comparison-grid">
                @foreach($comparisons as $comparison)
                    @php($items = $comparison->resolved_items->take(2))
                    <article class="comparison-mini-card home-comparison-card">
                        <div class="comparison-icons">
                            @foreach($items as $item)
                                <img loading="lazy" decoding="async" src="{{ $item->logo_url }}" alt="{{ $item->name }}">
                            @endforeach
                            <span>VS</span>
                        </div>
                        <h3>{{ $comparison->title }}</h3>
                        <p>Features · Pricing · Evidence · Fit</p>
                        <a href="{{ route('comparisons.show', $comparison) }}">Open comparison <i data-lucide="arrow-right"></i></a>
                    </article>
                @endforeach
            </div>
        </section>
    @endif

    @if($latestNews->isNotEmpty() || $featuredArticles->isNotEmpty())
        <section id="latest-insights" class="panel home-section-compact home-insights-panel">
            <div class="section-heading row-heading">
                <div class="heading-left">
                    <div class="heading-icon cyan"><i data-lucide="book-open-text"></i></div>
                    <div><h2>Latest Insights</h2><p>Fresh AI news and practical guides without turning the homepage into a feed</p></div>
                </div>
                <div class="home-insight-links">
                    <a class="text-link" href="{{ route('news.index') }}">News</a>
                    <a class="text-link" href="{{ route('articles.index') }}">Guides</a>
                </div>
            </div>

            <div class="home-insights-grid">
                @if($latestNews->isNotEmpty())
                    <div class="home-insight-column">
                        <div class="home-insight-column-title"><i data-lucide="newspaper"></i><strong>AI News</strong></div>
                        @foreach($latestNews as $news)
                            @php($newsImage = $news->image_url ?: '/images/frontend/content-placeholder.svg')
                            <a class="home-insight-row" href="{{ route('news.show', $news) }}">
                                <img src="{{ $newsImage }}" alt="{{ $news->headline }}" loading="lazy" decoding="async">
                                <div>
                                    <span>{{ strtoupper($news->category ?? 'AI UPDATE') }}</span>
                                    <h3>{{ $news->headline }}</h3>
                                    <small>{{ $news->company?->name ?? $news->source }} · {{ optional($news->published_at)->diffForHumans() }}</small>
                                </div>
                                <i data-lucide="arrow-right"></i>
                            </a>
                        @endforeach
                    </div>
                @endif

                @if($featuredArticles->isNotEmpty())
                    <div class="home-insight-column">
                        <div class="home-insight-column-title"><i data-lucide="book-open"></i><strong>Guides</strong></div>
                        @foreach($featuredArticles as $article)
                            @php($articleImage = $article->featured_image_url ?: '/images/frontend/content-placeholder.svg')
                            <a class="home-insight-row" href="{{ route('articles.show', $article) }}">
                                <img src="{{ $articleImage }}" alt="{{ $article->title }}" loading="lazy" decoding="async">
                                <div>
                                    <span>{{ strtoupper($article->category ?: 'GUIDE') }}</span>
                                    <h3>{{ $article->title }}</h3>
                                    <small>{{ $article->company?->name ?? 'AI Orbit' }} · {{ optional($article->published_at)->diffForHumans() }}</small>
                                </div>
                                <i data-lucide="arrow-right"></i>
                            </a>
                        @endforeach
                    </div>
                @endif
            </div>
        </section>
    @endif
</div>
@endsection
