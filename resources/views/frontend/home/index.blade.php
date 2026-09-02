@extends('frontend.layouts.app')

@section('title', 'AI Orbit — Compare AI Tools, Models, Pricing & Benchmarks')
@section('meta_description', 'Discover and compare AI tools and models, track pricing, explore verified benchmarks and follow AI news on AI Orbit.')


@section('content')
<section class="hero home-hero home-hero-reference">
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
            <p>Explore the latest AI tools, models, news, reviews, pricing and real-world comparisons — all in one place.</p>

            <form class="global-search home-hero-search search-intelligence-shell" action="{{ route('search.index') }}" method="get" data-search-shell>
                <div class="hero-search-icon"><i data-lucide="search"></i></div>
                <input id="home-global-search" name="q" type="search" placeholder="Search AI tools, models, companies, news..." autocomplete="off" data-search-autocomplete>
                <button type="submit" aria-label="Search AI Orbit"><i data-lucide="search"></i><span>Search</span></button>
                <div class="search-live-results home-search-live-results" data-search-suggestions hidden></div>
            </form>

            <div class="quick-chips home-hero-chips">
                @foreach($categories->take(7) as $category)
                    <a href="{{ route('categories.show', $category) }}">{{ $category->name }}</a>
                @endforeach
            </div>
        </div>
    </div>
</section>

<div class="page-body">
    <section class="trending-strip panel">
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

    <div class="dashboard-grid">
        <div class="main-column">
            <section class="panel categories-panel">
                <div class="section-heading">
                    <div class="heading-icon purple"><i data-lucide="layout-grid"></i></div>
                    <div><h2>AI Categories</h2><p>Explore AI tools and resources by category</p></div>
                </div>
                <div class="category-grid">
                    @php
                        $categoryIcons = ['ai-chat'=>'messages-square','ai-image'=>'image','ai-video'=>'play-square','ai-writing'=>'pen-line','ai-coding'=>'code-2','ai-voice'=>'mic-2','ai-music'=>'music-2','ai-agents'=>'flask-conical','ai-search'=>'search','ai-productivity'=>'sparkles'];
                    @endphp
                    @foreach($categories as $category)
                        <a href="{{ route('categories.show', $category) }}" class="category-card">
                            <i data-lucide="{{ $categoryIcons[$category->slug] ?? 'sparkles' }}"></i>
                            <strong>{{ $category->name }}</strong><span>{{ number_format($category->tools_count) }} Tools</span>
                        </a>
                    @endforeach
                    <a class="category-card view-all-card" href="{{ route('categories.index') }}"><strong>View All</strong><i data-lucide="arrow-right"></i></a>
                </div>
            </section>

            <section id="best-tools" class="panel tools-panel">
                <div class="section-heading row-heading">
                    <div class="heading-left"><div class="heading-icon gold"><i data-lucide="trophy"></i></div><h2>Best AI Tools</h2></div>
                    <a class="text-link" href="{{ route('tools.index') }}">View All <i data-lucide="arrow-right"></i></a>
                </div>
                <div class="filter-tabs" data-tool-tabs>
                    <button class="active" data-filter="all">All</button>
                    @foreach($categories->take(5) as $category)
                        <button data-filter="{{ $category->slug }}">{{ $category->name }}</button>
                    @endforeach
                </div>
                <div class="tool-grid" data-tool-grid>
                    @foreach($bestTools as $tool)
                    <article class="tool-card" data-category="{{ $tool->category?->slug }}" data-search="{{ strtolower($tool->name.' '.$tool->short_description.' '.($tool->company?->name ?? '')) }}">
                        <div class="tool-card-top">
                            <img loading="lazy" decoding="async" class="tool-logo" src="{{ $tool->logo_url }}" alt="{{ $tool->name }} logo">
                            <div class="tool-title"><h3><a class="home-entity-link" href="{{ route('tools.show', $tool) }}">{{ $tool->name }}</a></h3><span>{{ $tool->subcategory ?: $tool->category?->name }}</span></div>
                            <div class="tool-rating"><span>★ {{ number_format((float)$tool->rating,1) }}/5</span></div>
                        </div>
                        <div class="tool-card-meta-line">
                            <span class="badge">{{ implode(' + ', array_slice($tool->pricing_models ?? [],0,2)) ?: 'Explore' }}</span>
                        </div>
                        <p>{{ $tool->short_description }}</p>
                        <div class="card-actions"><a class="primary-btn" href="{{ route('tools.show', $tool) }}">{{ \Illuminate\Support\Str::limit($tool->name, 24) }} details</a><a class="secondary-btn" href="{{ route('comparisons.builder', ['type' => 'tool', 'item' => $tool->id]) }}">Compare</a></div>
                    </article>
                    @endforeach
                </div>
                <div class="empty-state" data-empty-state hidden>No tools match this search or category.</div>
            </section>

            <div class="lower-grid">
                <section id="news" class="panel news-panel">
                    <div class="section-heading row-heading"><div class="heading-left"><div class="heading-icon purple"><i data-lucide="newspaper"></i></div><h2>Latest AI News</h2></div><a class="text-link" href="{{ route('news.index') }}">View All <i data-lucide="arrow-right"></i></a></div>
                    <div class="news-list">
                        @foreach($latestNews->take(3) as $news)
                            @php($newsImage = $news->image_url ?: '/images/frontend/content-placeholder.svg')
                            <a class="news-row" href="{{ route('news.show', $news) }}">
                                <div class="news-thumb">
                                    <img src="{{ $newsImage }}" alt="{{ $news->headline }}" loading="lazy" decoding="async">
                                </div>
                                <div><span class="news-badge">{{ strtoupper($news->category ?? 'UPDATE') }}</span><h3>{{ $news->headline }}</h3><p>{{ $news->company?->name ?? $news->source }} • {{ optional($news->published_at)->diffForHumans() }}</p></div>
                                <i data-lucide="arrow-right"></i>
                            </a>
                        @endforeach
                    </div>
                </section>

                @for($comparisonSlot = 0; $comparisonSlot < 2; $comparisonSlot++)
                    @php($comparison = $comparisons->get($comparisonSlot))
                    <section id="{{ $comparisonSlot === 0 ? 'comparisons' : 'comparison-highlight-2' }}" class="panel compare-panel">
                        <div class="section-heading row-heading">
                            <div class="heading-left"><div class="heading-icon purple"><i data-lucide="scale"></i></div><h2>{{ $comparisonSlot === 0 ? 'AI Comparisons' : 'Trending Comparison' }}</h2></div>
                            <a class="text-link" href="{{ route('comparisons.index') }}">View All <i data-lucide="arrow-right"></i></a>
                        </div>
                        @if($comparison)
                            <div class="versus-card">
                                @foreach($comparison->resolved_items->take(2) as $idx => $item)
                                    @if($idx === 1)<span class="vs">VS</span>@endif
                                    <div class="versus-item"><img loading="lazy" decoding="async" src="{{ $item->logo_url }}" alt="{{ $item->name }}"><strong>{{ $item->name }}</strong></div>
                                @endforeach
                            </div>
                            <p class="compare-copy">{{ $comparisonSlot === 0 ? 'Which AI product is better for your workflow?' : 'See how two popular AI choices compare side by side.' }}</p>
                            <a class="primary-pill" href="{{ route('comparisons.show', $comparison) }}">{{ \Illuminate\Support\Str::limit($comparison->title ?: 'Open AI comparison', 46) }}</a>
                            <div class="compare-chips">
                                @if($comparisonSlot === 0)<span>Features</span><span>Pricing</span><span>Benchmarks</span><span>Fit</span>
                                @else<span>Popular</span><span>Head-to-head</span><span>Performance</span><span>Value</span>@endif
                            </div>
                        @else
                            <div class="empty-state">More published comparisons will appear here.</div>
                            <a class="primary-pill" href="{{ route('comparisons.builder') }}">Create a Comparison</a>
                        @endif
                    </section>
                @endfor
            </div>

            <section id="models" class="panel models-panel">
                <div class="section-heading row-heading"><div class="heading-left"><div class="heading-icon cyan"><i data-lucide="cpu"></i></div><div><h2>Top AI Models</h2><p>Benchmark-ready model records with dedicated artwork</p></div></div><a class="text-link" href="{{ route('models.index') }}">View All <i data-lucide="arrow-right"></i></a></div>
                <div class="model-strip">
                    @foreach($featuredModels as $model)
                        <a class="model-card" href="{{ route('models.show', $model) }}"><img loading="lazy" decoding="async" src="{{ $model->logo_url }}" alt="{{ $model->name }}"><div><h3>{{ $model->name }}</h3><span>{{ $model->company?->name }} · {{ $model->context_window ?: 'Context N/A' }}</span></div><b>{{ $model->benchmark_score !== null ? number_format((float)$model->benchmark_score,1) : '—' }}</b></a>
                    @endforeach
                </div>
            </section>
        </div>

        <aside class="sidebar-column">
            <section class="panel side-panel popular-panel">
                <div class="side-title"><h2>🔥 <span>Popular AI Tools</span></h2><a href="{{ route('tools.index', ['sort' => 'popular']) }}">View All <i data-lucide="arrow-right"></i></a></div>
                <div class="rank-list">
                    @foreach($popularTools as $tool)
                        <a href="{{ route('tools.show', $tool) }}" class="rank-item"><span class="rank">{{ $loop->iteration }}</span><img loading="lazy" decoding="async" src="{{ $tool->logo_url }}" alt="{{ $tool->name }}"><div><strong>{{ $tool->name }}</strong><small>{{ $tool->category?->name }}</small></div><b>★ {{ number_format((float)$tool->rating,1) }}/5</b></a>
                    @endforeach
                </div>
            </section>

            <section class="panel side-panel news-categories">
                <div class="side-title"><h2><i data-lucide="newspaper"></i> AI News Categories</h2></div>
                @php($sideIcons=['Breaking News'=>'flame','New Models'=>'sparkles','Product Launch'=>'box','Pricing Change'=>'badge-dollar-sign','Research'=>'microscope','Funding'=>'chart-no-axes-combined','Security'=>'shield-check'])
                <div class="category-list">
                    @foreach($newsCategoryCounts as $name => $total)
                        <a href="{{ route('news.index', ['category' => $name]) }}"><span class="side-category-icon"><i data-lucide="{{ $sideIcons[$name] ?? 'newspaper' }}"></i></span><strong>{{ $name }}</strong><em>{{ $total }}</em><i data-lucide="chevron-right"></i></a>
                    @endforeach
                </div>
            </section>

            <section class="panel side-panel model-leaderboard">
                <div class="side-title"><h2><i data-lucide="sparkles"></i> Model Leaderboard</h2></div>
                @foreach($featuredModels->take(4) as $model)
                    <a class="mini-model" href="{{ route('models.show', $model) }}"><img loading="lazy" decoding="async" src="{{ $model->logo_url }}" alt="{{ $model->name }}"><div><strong>{{ $model->name }}</strong><small>{{ $model->company?->name }}</small></div><b>{{ $model->benchmark_score !== null ? number_format((float)$model->benchmark_score,1) : '—' }}</b></a>
                @endforeach
            </section>
        </aside>
    </div>

    <div class="home-expansion">
        <section class="panel expansion-panel releases-panel" id="latest-releases">
            <div class="section-heading row-heading">
                <div class="heading-left"><div class="heading-icon cyan"><i data-lucide="rocket"></i></div><div><h2>Latest AI Releases</h2><p>Recently published tools and newly released models</p></div></div>
                <a class="text-link" href="{{ route('trending.index', ['tab' => 'models']) }}">Explore Releases <i data-lucide="arrow-right"></i></a>
            </div>
            <div class="release-grid">
                @foreach($recentModels->take(3) as $model)
                    <a class="release-card model-release" href="{{ route('models.show', $model) }}">
                        <div class="release-top"><span class="release-label">NEW MODEL</span><span>{{ optional($model->release_date)->diffForHumans() }}</span></div>
                        <div class="release-main"><img loading="lazy" decoding="async" src="{{ $model->logo_url }}" alt="{{ $model->name }}"><div><h3>{{ $model->name }}</h3><p>{{ $model->company?->name }} · {{ $model->context_window }} context</p></div></div>
                        <div class="release-stats"><span><i data-lucide="gauge"></i>{{ $model->benchmark_score !== null ? number_format((float)$model->benchmark_score,1).' score' : 'Benchmark N/A' }}</span><span><i data-lucide="calendar-days"></i>{{ optional($model->release_date)->format('M j') }}</span></div>
                    </a>
                @endforeach
                @foreach($recentTools->take(3) as $tool)
                    <a class="release-card tool-release" href="{{ route('tools.show', $tool) }}">
                        <div class="release-top"><span class="release-label">TOOL UPDATE</span><span>{{ optional($tool->published_at)->diffForHumans() }}</span></div>
                        <div class="release-main"><img loading="lazy" decoding="async" src="{{ $tool->logo_url }}" alt="{{ $tool->name }}"><div><h3>{{ $tool->name }}</h3><p>{{ $tool->company?->name }} · {{ $tool->category?->name }}</p></div></div>
                        <div class="release-stats"><span><i data-lucide="star"></i>{{ number_format((float)$tool->rating,1) }}/5</span><span><i data-lucide="flame"></i>{{ $tool->popularity }} popularity</span></div>
                    </a>
                @endforeach
            </div>
        </section>

        <section class="panel expansion-panel benchmark-panel" id="benchmarks">
            <div class="section-heading row-heading">
                <div class="heading-left"><div class="heading-icon gold"><i data-lucide="chart-no-axes-combined"></i></div><div><h2>AI Benchmark Leaderboard</h2><p>Top verified model results across important evaluation suites</p></div></div>
                <a class="text-link" href="{{ route('benchmarks.index') }}">Full Benchmarks <i data-lucide="arrow-right"></i></a>
            </div>
            <div class="benchmark-grid">
                @forelse($benchmarkGroups as $rows)
                    @php($benchmark = $rows->first()?->benchmark)
                    <article class="benchmark-card">
                        <div class="benchmark-head"><div><span>{{ $benchmark?->category }}</span><h3>{{ $benchmark?->name }}</h3></div><i data-lucide="trophy"></i></div>
                        <div class="benchmark-list">
                            @foreach($rows as $result)
                                @php($model = $result->benchmarkable)
                                <div class="benchmark-row">
                                    <b>{{ $loop->iteration }}</b>
                                    <img loading="lazy" decoding="async" src="{{ $model?->logo_url }}" alt="{{ $model?->name }}">
                                    <div><strong>{{ $model?->name }}</strong><small>{{ $model?->company?->name }}</small></div>
                                    <em>{{ number_format((float)$result->score,1) }}</em>
                                </div>
                            @endforeach
                        </div>
                    </article>
                @empty
                    <div class="empty-state">Seed benchmark results to populate the leaderboard.</div>
                @endforelse
            </div>
        </section>

        <div class="insight-grid">
            <section class="panel expansion-panel pricing-panel" id="pricing">
                <div class="section-heading row-heading">
                    <div class="heading-left"><div class="heading-icon green"><i data-lucide="badge-dollar-sign"></i></div><div><h2>Best AI by Pricing</h2><p>Quick plan snapshots from the pricing database</p></div></div>
                    <a class="text-link" href="{{ route('pricing.index') }}">View Pricing <i data-lucide="arrow-right"></i></a>
                </div>
                <div class="pricing-grid">
                    @foreach($pricingPicks as $plan)
                        <article class="pricing-card">
                            <div class="pricing-tool"><img loading="lazy" decoding="async" src="{{ $plan->tool?->logo_url }}" alt="{{ $plan->tool?->name }}"><div><h3>{{ $plan->tool?->name }}</h3><span>{{ $plan->plan_name }}</span></div></div>
                            <div class="price-line">@if((float)$plan->monthly_price === 0.0)<strong>Free</strong>@else<strong>${{ number_format((float)$plan->monthly_price, 2) }}</strong><small>/mo</small>@endif</div>
                            <p>{{ $plan->credits ?: $plan->limits ?: 'Plan details available' }}</p>
                            <a href="{{ $plan->tool ? route('pricing.show', $plan->tool) : route('pricing.index') }}">Compare pricing <i data-lucide="arrow-right"></i></a>
                        </article>
                    @endforeach
                </div>
            </section>

            <section class="panel expansion-panel comparisons-wide">
                <div class="section-heading row-heading"><div class="heading-left"><div class="heading-icon purple"><i data-lucide="git-compare-arrows"></i></div><div><h2>Popular Comparisons</h2><p>Head-to-head choices users are exploring</p></div></div><a class="text-link" href="{{ route('comparisons.index') }}">View All <i data-lucide="arrow-right"></i></a></div>
                <div class="comparison-grid-wide">
                    @foreach($comparisons as $comparison)
                        @php($items = $comparison->resolved_items->take(2))
                        <article class="comparison-mini-card">
                            <div class="comparison-icons">
                                @foreach($items as $item)<img loading="lazy" decoding="async" src="{{ $item->logo_url }}" alt="{{ $item->name }}">@endforeach
                                <span>VS</span>
                            </div>
                            <h3>{{ $comparison->title }}</h3>
                            <p>{{ number_format($comparison->views) }} views · {{ ucfirst($comparison->comparable_type ?? 'AI') }}</p>
                            <a href="{{ route('comparisons.show', $comparison) }}">Open comparison <i data-lucide="arrow-right"></i></a>
                        </article>
                    @endforeach
                </div>
            </section>
        </div>

        <section class="panel expansion-panel companies-panel" id="companies">
            <div class="section-heading row-heading"><div class="heading-left"><div class="heading-icon cyan"><i data-lucide="building-2"></i></div><div><h2>Top AI Companies</h2><p>Explore the organizations building leading AI tools and models</p></div></div><a class="text-link" href="{{ route('companies.index') }}">View Companies <i data-lucide="arrow-right"></i></a></div>
            <div class="company-grid">
                @foreach($topCompanies as $company)
                    <a class="company-card" href="{{ route('companies.show', $company) }}"><img loading="lazy" decoding="async" src="{{ $company->logo_url }}" alt="{{ $company->name }}"><div><h3>{{ $company->name }}</h3><p>{{ $company->tools_count }} tools · {{ $company->models_count }} models</p></div><i data-lucide="arrow-up-right"></i></a>
                @endforeach
            </div>
        </section>

        <div class="content-grid-wide">
            <section class="panel expansion-panel reviews-panel" id="reviews">
                <div class="section-heading row-heading"><div class="heading-left"><div class="heading-icon gold"><i data-lucide="star"></i></div><div><h2>Latest Reviews</h2><p>Editorial and moderated community reviews across AI tools and models</p></div></div><a class="text-link" href="{{ route('reviews.index') }}">All Reviews <i data-lucide="arrow-right"></i></a></div>
                <div class="review-grid">
                    @foreach($latestReviews as $review)
                        @php($reviewedItem = $review->model ?: $review->tool)
                        <article class="review-card">
                            <div class="review-head"><img loading="lazy" decoding="async" src="{{ $reviewedItem?->logo_url }}" alt="{{ $reviewedItem?->name }}"><div><h3>{{ $reviewedItem?->name }}</h3><span>{{ $reviewedItem?->company?->name }} · {{ $review->model ? 'Model' : 'Tool' }}</span></div><b>★ {{ number_format((float)$review->rating,1) }}</b></div>
                            <h4>{{ $review->verdict ?: $reviewedItem?->name.' review' }}</h4><p>{{ $review->body ?: 'Published AI Orbit review.' }}</p>
                            <div class="review-foot"><span><i data-lucide="badge-check"></i>{{ $review->review_type === 'editorial' ? 'Editorial' : 'Community' }}</span><a href="{{ route('reviews.show', $review) }}">Read review</a></div>
                        </article>
                    @endforeach
                </div>
            </section>

            <section class="panel expansion-panel articles-panel" id="articles">
                <div class="section-heading row-heading"><div class="heading-left"><div class="heading-icon purple"><i data-lucide="book-open-text"></i></div><div><h2>Featured AI Articles</h2><p>Guides, explainers and practical AI intelligence</p></div></div><a class="text-link" href="{{ route('articles.index') }}">All Articles <i data-lucide="arrow-right"></i></a></div>
                <div class="article-grid">
                    @foreach($featuredArticles as $article)
                        @php($articleImage = $article->featured_image_url ?: '/images/frontend/content-placeholder.svg')
                        <article class="article-card">
                            <div class="article-image"><img loading="lazy" decoding="async" src="{{ $articleImage }}" alt="{{ $article->title }}"><span>{{ $article->category ?: 'Guide' }}</span></div>
                            <div class="article-copy"><h3>{{ $article->title }}</h3><p>{{ $article->summary }}</p><div><span>{{ $article->company?->name ?? 'AI Orbit' }} · {{ optional($article->published_at)->diffForHumans() }}</span><a href="{{ route('articles.show', $article) }}">Read <i data-lucide="arrow-right"></i></a></div></div>
                        </article>
                    @endforeach
                </div>
            </section>
        </div>

        <section class="home-cta panel">
            <div class="home-cta-art"><i data-lucide="brain-circuit"></i></div>
            <div><span class="footer-kicker"><i data-lucide="sparkles"></i> Built for AI discovery</span><h2>Find the right AI faster.</h2><p>Search tools, compare models, check pricing, follow AI news and validate decisions with benchmark data — without jumping between dozens of sites.</p></div>
            <div class="home-cta-actions"><a class="primary-btn" href="{{ route('tools.index') }}">Explore AI Tools</a><a class="secondary-btn" href="{{ route('comparisons.builder') }}">Start Comparing</a></div>
        </section>
    </div>

</div>
@endsection
