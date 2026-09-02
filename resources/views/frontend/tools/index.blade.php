@extends('frontend.layouts.app')

@php
    $toolsHasFilters = request()->hasAny([
        'q',
        'category',
        'pricing',
        'rating',
        'company',
        'platform',
        'feature',
        'verified_tech',
        'sort',
        'view',
    ]);

    $toolsSeoTitle = 'AI Tools Directory — Discover and Compare the Best AI Tools | AI Orbit';

    if (!$toolsHasFilters && $tools->currentPage() > 1) {
        $toolsSeoTitle = 'AI Tools Directory — Page '
            . $tools->currentPage()
            . ' | AI Orbit';
    }

    $toolsSeoDescription = 'Explore AI tools by category, pricing, rating, company, platform and capability. Compare top AI products and find the right tool for your workflow.';

    $toolsCanonical = route('tools.index');

    if (!$toolsHasFilters && $tools->currentPage() > 1) {
        $toolsCanonical = $tools->url($tools->currentPage());
    }

    $toolsCollectionSchema = [
        '@' . 'context' => 'https://schema.org',
        '@' . 'type' => 'CollectionPage',
        'name' => 'AI Tools Directory',
        'description' => $toolsSeoDescription,
        'url' => $toolsCanonical,
    ];

    $toolsBreadcrumbSchema = [
        '@' . 'context' => 'https://schema.org',
        '@' . 'type' => 'BreadcrumbList',
        'itemListElement' => [
            [
                '@' . 'type' => 'ListItem',
                'position' => 1,
                'name' => 'Home',
                'item' => route('home'),
            ],
            [
                '@' . 'type' => 'ListItem',
                'position' => 2,
                'name' => 'AI Tools',
                'item' => route('tools.index'),
            ],
        ],
    ];
@endphp

@section('title', $toolsSeoTitle)
@section('meta_description', $toolsSeoDescription)
@section('canonical', $toolsCanonical)

@section(
    'robots',
    $toolsHasFilters
        ? 'noindex,follow'
        : 'index,follow,max-image-preview:large,max-snippet:-1,max-video-preview:-1'
)

@push('head')
<script type="application/ld+json">{!! json_encode(
    $toolsCollectionSchema,
    JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
) !!}</script>

<script type="application/ld+json">{!! json_encode(
    $toolsBreadcrumbSchema,
    JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
) !!}</script>
@endpush
@push('styles')
<link rel="stylesheet" href="{{ asset('css/frontend/tools.css') }}?v=20260901-seo4">
@endpush

@section('content')
@php
    $activeFilters = collect(['category','pricing','rating','company','platform','feature','verified_tech'])
        ->filter(fn ($key) => request()->filled($key))
        ->count();
@endphp

<section class="tools-hero tools-hero-cinematic">
    <div class="tools-wavefield" aria-hidden="true">
        <svg viewBox="0 0 1600 520" preserveAspectRatio="none">
            <defs>
                <linearGradient id="toolsWaveBlue" x1="0" x2="1">
                    <stop offset="0" stop-color="#215bff" stop-opacity=".05"/>
                    <stop offset=".45" stop-color="#2ab7ff" stop-opacity=".9"/>
                    <stop offset="1" stop-color="#7657ff" stop-opacity=".35"/>
                </linearGradient>
                <linearGradient id="toolsWavePurple" x1="0" x2="1">
                    <stop offset="0" stop-color="#7b35ff" stop-opacity=".22"/>
                    <stop offset=".5" stop-color="#d83cff" stop-opacity=".82"/>
                    <stop offset="1" stop-color="#24c9ff" stop-opacity=".28"/>
                </linearGradient>
                <filter id="toolsWaveGlow"><feGaussianBlur stdDeviation="5" result="blur"/><feMerge><feMergeNode in="blur"/><feMergeNode in="SourceGraphic"/></feMerge></filter>
            </defs>
            <path d="M-80 330 C180 245 350 430 590 315 S970 150 1210 300 S1510 420 1690 245" fill="none" stroke="url(#toolsWaveBlue)" stroke-width="4" filter="url(#toolsWaveGlow)"/>
            <path d="M-90 380 C170 300 330 455 560 365 S930 210 1160 330 S1480 430 1690 285" fill="none" stroke="url(#toolsWavePurple)" stroke-width="3" filter="url(#toolsWaveGlow)"/>
            <path d="M-50 286 C230 205 380 365 650 282 S1030 155 1260 270 S1500 340 1660 215" fill="none" stroke="#286fff" stroke-opacity=".22" stroke-width="1.3"/>
            <path d="M-40 420 C210 350 390 470 640 402 S980 265 1210 390 S1500 455 1670 338" fill="none" stroke="#c33cff" stroke-opacity=".18" stroke-width="1.2"/>
        </svg>
    </div>

    <div class="tools-page-container tools-hero-inner">
        <nav class="tools-breadcrumb" aria-label="Breadcrumb">
            <a href="{{ route('home') }}">Home</a><i data-lucide="chevron-right"></i><span>AI Tools</span>
        </nav>

        <div class="tools-hero-layout">
            <div class="tools-hero-content">
                <span class="tools-eyebrow"><i data-lucide="sparkles"></i> Curated AI Tools Directory</span>

                {{-- Heading split into two controlled lines, matching reference composition --}}
                <h1>Discover the best<br><span>AI tools</span> for every task.</h1>

                <p>Search, compare and explore trusted AI products for chat, image, video, coding, voice, writing, agents and more.</p>

                <form class="tools-search" method="GET" action="{{ route('tools.index') }}" role="search">
                    @foreach(['category','pricing','rating','company','platform','feature','sort','view'] as $param)
                        @if(request()->filled($param))<input type="hidden" name="{{ $param }}" value="{{ request($param) }}">@endif
                    @endforeach
                    <i data-lucide="search"></i>
                    <input type="search" name="q" value="{{ request('q') }}" placeholder="Search 1,000+ AI tools, brands, categories..." aria-label="Search AI tools">
                    @if(request()->filled('q'))
                        <a class="search-clear" href="{{ route('tools.index', request()->except('q','page')) }}" aria-label="Clear search"><i data-lucide="x"></i></a>
                    @endif
                    <button type="submit"><i data-lucide="search"></i> Search</button>
                </form>

                <div class="tools-hero-chips" aria-label="Popular AI tool categories">
                    @foreach($categories->take(7) as $category)
                        @php
                            $categoryIcon = match(true) {
                                str_contains(strtolower($category->name), 'chat') => 'message-circle',
                                str_contains(strtolower($category->name), 'image') => 'image',
                                str_contains(strtolower($category->name), 'video') => 'play-circle',
                                str_contains(strtolower($category->name), 'coding') || str_contains(strtolower($category->name), 'code') => 'code-2',
                                str_contains(strtolower($category->name), 'voice') => 'mic-2',
                                str_contains(strtolower($category->name), 'writing') => 'pen-line',
                                str_contains(strtolower($category->name), 'agent') => 'bot',
                                default => 'sparkles',
                            };
                        @endphp
                        <a href="{{ route('tools.index', ['category' => $category->slug]) }}"><i data-lucide="{{ $categoryIcon }}"></i>{{ $category->name }}</a>
                    @endforeach
                    <a href="{{ route('tools.index') }}" class="tools-chip-more">More <i data-lucide="chevron-down"></i></a>
                </div>
            </div>

            
        </div>

        <div class="tools-hero-stats">
            <div><span class="stat-icon"><i data-lucide="box"></i></span><span><strong>{{ number_format($stats['tools']) }}+</strong><small>Published tools</small></span></div>
            <div><span class="stat-icon"><i data-lucide="layout-grid"></i></span><span><strong>{{ number_format($stats['categories']) }}+</strong><small>Categories</small></span></div>
            <div><span class="stat-icon"><i data-lucide="sparkles"></i></span><span><strong>{{ number_format($stats['free']) }}+</strong><small>Free options</small></span></div>
            <div><span class="stat-icon"><i data-lucide="trophy"></i></span><span><strong>{{ number_format($stats['topRated']) }}</strong><small>Top rated 4.5+</small></span></div>
        </div>
    </div>
</section>

<section class="tools-page-container category-rail-wrap">
    <div class="category-rail-head"><span>Browse by category</span><small>Quick access to the most popular AI workflows</small></div>
    <div class="category-rail" aria-label="AI tool categories">
        <a href="{{ route('tools.index', array_filter(['q'=>request('q'),'sort'=>request('sort')])) }}" class="{{ request('category') ? '' : 'active' }}"><i data-lucide="layout-grid"></i><span>All Tools<small>{{ $stats['tools'] }} tools</small></span></a>
        @foreach($categories->take(9) as $category)
            <a href="{{ route('tools.index', array_merge(request()->except('page','category'), ['category'=>$category->slug])) }}" class="{{ request('category') === $category->slug ? 'active' : '' }}">
                <i data-lucide="{{ match(true) { str_contains(strtolower($category->name),'image') => 'image', str_contains(strtolower($category->name),'video') => 'video', str_contains(strtolower($category->name),'coding') => 'code-2', str_contains(strtolower($category->name),'voice') => 'audio-lines', str_contains(strtolower($category->name),'search') => 'search', str_contains(strtolower($category->name),'productivity') => 'workflow', default => 'bot' } }}"></i>
                <span>{{ $category->name }}<small>{{ $category->tools_count }} tools</small></span>
            </a>
        @endforeach
    </div>
</section>

@if(!$toolsHasFilters && ($categoryHubs->isNotEmpty() || $featureHubs->isNotEmpty()))
<section class="tools-page-container seo-discovery-hubs" aria-label="Canonical AI discovery hubs">
    <div class="seo-hub-group">
        <div class="seo-hub-copy">
            <span><i data-lucide="network"></i> Explore canonical category hubs</span>
            <p>Browse dedicated category pages with tools, related models, guides and current intelligence.</p>
        </div>
        <div class="seo-hub-links">
            @foreach($categoryHubs as $categoryHub)
                <a href="{{ route('categories.show', $categoryHub) }}">{{ $categoryHub->name }} AI tools</a>
            @endforeach
            <a class="seo-hub-more" href="{{ route('categories.index') }}">All AI categories <i data-lucide="arrow-right"></i></a>
        </div>
    </div>

    @if($featureHubs->isNotEmpty())
    <div class="seo-hub-group">
        <div class="seo-hub-copy">
            <span><i data-lucide="sparkles"></i> Browse by capability</span>
            <p>Use normalized capability pages instead of temporary filter URLs when exploring a topic.</p>
        </div>
        <div class="seo-hub-links">
            @foreach($featureHubs as $featureHub)
                <a href="{{ route('features.show', $featureHub) }}">{{ $featureHub->name }} AI tools</a>
            @endforeach
            <a class="seo-hub-more" href="{{ route('features.index') }}">All AI features <i data-lucide="arrow-right"></i></a>
        </div>
    </div>
    @endif
</section>
@endif

@if(!request()->hasAny(['q','category','pricing','rating','company','platform','feature']) && $featuredTools->isNotEmpty())
<section class="tools-page-container featured-strip-section">
    <div class="section-heading-row">
        <div><span class="section-kicker"><i data-lucide="flame"></i> Trending now</span><h2>Popular AI tools this week</h2></div>
        <span class="section-note">Ranked by popularity and user rating</span>
    </div>
    <div class="featured-tool-strip">
        @foreach($featuredTools as $rank => $tool)
            <article class="featured-tool-mini">
                <span class="rank">#{{ $rank + 1 }}</span>
                <img src="{{ $tool->logo_url }}" alt="{{ $tool->name }} logo">
                <div><h3>{{ $tool->name }}</h3><p>{{ $tool->category?->name ?? 'AI Tool' }}</p></div>
                <span class="mini-score"><i data-lucide="star"></i>{{ number_format((float)$tool->rating, 1) }}</span>
                @if($tool->website)<a href="{{ $tool->website }}" target="_blank" rel="noopener" aria-label="Visit {{ $tool->name }}"><i data-lucide="arrow-up-right"></i></a>@endif
            </article>
        @endforeach
    </div>
</section>
@endif

<section class="tools-page-container tools-directory-section">
    <div class="directory-toolbar">
        <div class="directory-title">
            <button class="mobile-filter-button" type="button" data-filter-open><i data-lucide="sliders-horizontal"></i>Filters @if($activeFilters)<span>{{ $activeFilters }}</span>@endif</button>
            <div>
                <h2>{{ $tools->total() }} AI {{ Str::plural('Tool', $tools->total()) }}</h2>
                <p>
                    @if(request()->filled('q')) Results for “{{ request('q') }}” @else Explore products matched to your needs @endif
                </p>
            </div>
        </div>
        <div class="directory-actions">
            <form method="GET" action="{{ route('tools.index') }}" class="sort-form">
                @foreach(request()->except('sort','page') as $key => $value)
                    @if(is_string($value))<input type="hidden" name="{{ $key }}" value="{{ $value }}">@endif
                @endforeach
                <label for="tool-sort">Sort by</label>
                <select name="sort" id="tool-sort" data-auto-submit>
                    <option value="popular" @selected(request('sort','popular')==='popular')>Most Popular</option>
                    <option value="rating" @selected(request('sort')==='rating')>Highest Rated</option>
                    <option value="newest" @selected(request('sort')==='newest')>Newest</option>
                    <option value="benchmark" @selected(request('sort')==='benchmark')>Benchmark Score</option>
                    <option value="name" @selected(request('sort')==='name')>Name A–Z</option>
                </select>
            </form>
            <div class="view-switcher" aria-label="View mode">
                <button type="button" class="active" data-view-mode="grid" aria-label="Grid view"><i data-lucide="grid-2x2"></i></button>
                <button type="button" data-view-mode="list" aria-label="List view"><i data-lucide="list"></i></button>
            </div>
        </div>
    </div>

    <div class="directory-layout">
        <div class="filter-overlay" data-filter-overlay></div>
        <aside class="tools-filter-panel" data-filter-panel>
            <div class="filter-mobile-head"><strong>Filter AI Tools</strong><button type="button" data-filter-close aria-label="Close filters"><i data-lucide="x"></i></button></div>
            <div class="filter-panel-head"><div><i data-lucide="sliders-horizontal"></i><strong>Filters</strong></div>@if($activeFilters)<a href="{{ route('tools.index', array_filter(['q'=>request('q'),'sort'=>request('sort')])) }}">Clear all</a>@endif</div>

            <form method="GET" action="{{ route('tools.index') }}" id="tool-filter-form">
                @if(request()->filled('q'))<input type="hidden" name="q" value="{{ request('q') }}">@endif
                @if(request()->filled('sort'))<input type="hidden" name="sort" value="{{ request('sort') }}">@endif

                <div class="filter-group open">
                    <button type="button" class="filter-group-toggle"><span>Category</span><i data-lucide="chevron-down"></i></button>
                    <div class="filter-options">
                        @foreach($categories as $category)
                            <label><span><input type="radio" name="category" value="{{ $category->slug }}" @checked(request('category')===$category->slug)><i></i>{{ $category->name }}</span><small>{{ $category->tools_count }}</small></label>
                        @endforeach
                    </div>
                </div>

                <div class="filter-group open">
                    <button type="button" class="filter-group-toggle"><span>Pricing</span><i data-lucide="chevron-down"></i></button>
                    <div class="filter-options compact-options">
                        <label><span><input type="radio" name="pricing" value="free" @checked(request('pricing')==='free')><i></i>Free / Free tier</span><small>{{ $stats['free'] }}</small></label>
                        <label><span><input type="radio" name="pricing" value="paid" @checked(request('pricing')==='paid')><i></i>Paid plans</span></label>
                    </div>
                </div>

                <div class="filter-group">
                    <button type="button" class="filter-group-toggle"><span>Rating</span><i data-lucide="chevron-down"></i></button>
                    <div class="filter-options compact-options">
                        <label><span><input type="radio" name="rating" value="4.5" @checked(request('rating')==='4.5')><i></i><b class="filter-stars">★★★★★</b> 4.5+</span></label>
                        <label><span><input type="radio" name="rating" value="4" @checked(request('rating')==='4')><i></i><b class="filter-stars">★★★★</b><b class="dim-star">★</b> 4.0+</span></label>
                    </div>
                </div>

                <div class="filter-group">
                    <button type="button" class="filter-group-toggle"><span>Company</span><i data-lucide="chevron-down"></i></button>
                    <div class="filter-options company-options">
                        @foreach($companies as $company)
                            <label><span><input type="radio" name="company" value="{{ $company->slug }}" @checked(request('company')===$company->slug)><i></i>{{ $company->name }}</span><small>{{ $company->tools_count }}</small></label>
                        @endforeach
                    </div>
                </div>

                <div class="filter-group">
                    <button type="button" class="filter-group-toggle"><span>Platform</span><i data-lucide="chevron-down"></i></button>
                    <div class="filter-options compact-options">
                        @foreach($platformFilters as $platform)
                            <label><span><input type="radio" name="platform" value="{{ $platform->slug }}" @checked(in_array(request('platform'), [$platform->slug, $platform->name], true))><i></i>{{ $platform->name }}</span></label>
                        @endforeach
                    </div>
                </div>

                @if($features->isNotEmpty())
                <div class="filter-group">
                    <button type="button" class="filter-group-toggle"><span>Capabilities</span><i data-lucide="chevron-down"></i></button>
                    <div class="filter-options feature-options">
                        @foreach($features as $feature)
                            <label><span><input type="radio" name="feature" value="{{ $feature->slug }}" @checked(request('feature')===$feature->slug)><i></i>{{ $feature->name }}</span><small>{{ $feature->tools_count }}</small></label>
                        @endforeach
                    </div>
                </div>
                @endif

                <div class="filter-group">
                    <button type="button" class="filter-group-toggle"><span>Verified technical facts</span><i data-lucide="chevron-down"></i></button>
                    <div class="filter-options compact-options">
                        <label><span><input type="radio" name="verified_tech" value="api" @checked(request('verified_tech')==='api')><i></i>Verified API access</span></label>
                        <label><span><input type="radio" name="verified_tech" value="open-source" @checked(request('verified_tech')==='open-source')><i></i>Verified open source</span></label>
                        <label><span><input type="radio" name="verified_tech" value="self-hosted" @checked(request('verified_tech')==='self-hosted')><i></i>Verified self-hosting</span></label>
                    </div>
                </div>

                <div class="filter-actions-mobile"><a href="{{ route('tools.index') }}">Reset</a><button type="submit">Show Results</button></div>
            </form>
        </aside>

        <div class="directory-results">
            @if($activeFilters || request()->filled('q'))
            <div class="active-filter-row">
                <span>Active filters:</span>
                @foreach(['category'=>'Category','pricing'=>'Pricing','rating'=>'Rating','company'=>'Company','platform'=>'Platform','feature'=>'Capability','verified_tech'=>'Verified technical'] as $key=>$label)
                    @if(request()->filled($key))
                        <a href="{{ route('tools.index', request()->except($key,'page')) }}">{{ $label }}: <strong>{{ request($key) }}</strong><i data-lucide="x"></i></a>
                    @endif
                @endforeach
                @if(request()->filled('q'))<a href="{{ route('tools.index', request()->except('q','page')) }}">Search: <strong>{{ request('q') }}</strong><i data-lucide="x"></i></a>@endif
            </div>
            @endif

            @if($tools->count())
                <div class="tool-directory-grid" data-directory-grid>
                    @foreach($tools as $tool)
                        @php
                            $pricing = collect($tool->pricing_models ?? []);
                            $isFree = $pricing->contains('Free');
                            $priceLabel = $isFree ? ($pricing->contains('Paid') ? 'Free + Paid' : 'Free') : ($pricing->first() ?? 'Pricing varies');
                            $capabilities = collect($tool->capabilities ?? [])->take(3);
                            $quickCapabilities = $tool->featureTerms->pluck('name')
                                ->merge(collect($tool->capabilities ?? []))
                                ->filter()->unique()->take(4)->values();
                            $quickUseCases = $tool->useCaseTerms->pluck('name')
                                ->filter()->unique()->take(4)->values();
                            $quickPlatforms = $tool->platformTerms->pluck('name')->filter()->unique()->take(4)->values();
                            if ($quickPlatforms->isEmpty()) {
                                $quickPlatforms = collect($tool->platforms ?? [])->filter()->unique()->take(4)->values();
                            }
                            $quickBenchmarks = $tool->benchmarkResults
                                ->filter(fn ($result) => $result->benchmark)
                                ->unique('benchmark_id')
                                ->take(6)
                                ->map(fn ($result) => [
                                    'id' => (int) $result->benchmark_id,
                                    'name' => $result->benchmark->name,
                                    'score' => (float) $result->score,
                                    'higher_is_better' => (bool) $result->benchmark->higher_is_better,
                                ])->values();
                            $cover = $tool->cover_image_url;
                        @endphp
                        <article class="tool-directory-card" data-tool-card
                            data-tool-id="{{ $tool->id }}"
                            data-tool-name="{{ $tool->name }}"
                            data-tool-logo="{{ $tool->logo_url }}"
                            data-tool-rating="{{ (float) $tool->rating > 0 ? number_format((float)$tool->rating,1) : '' }}"
                            data-tool-price="{{ $priceLabel }}"
                            data-tool-category="{{ $tool->category?->name ?? 'AI Tool' }}"
                            data-tool-company="{{ $tool->company?->name ?? 'Independent' }}"
                            data-tool-platforms="{{ $quickPlatforms->join('|') }}"
                            data-tool-capabilities="{{ $quickCapabilities->join('|') }}"
                            data-tool-use-cases="{{ $quickUseCases->join('|') }}"
                            data-tool-benchmarks="{{ $quickBenchmarks->toJson() }}">
                            <div class="tool-card-media" @if($cover) style="--tool-cover:url('{{ $cover }}')" @endif>
                                <div class="tool-media-shade"></div>
                                <span class="tool-rank-badge"><i data-lucide="trending-up"></i>{{ $tool->popularity }}% popular</span>
                                <button type="button" class="save-tool-btn" data-save-item data-save-type="tool" data-save-id="{{ $tool->id }}" aria-label="Save {{ $tool->name }}" aria-pressed="false"><i data-lucide="bookmark"></i></button>
                            </div>
                            <div class="tool-card-body">
                                <div class="tool-card-identity">
                                    <img src="{{ $tool->logo_url }}" alt="{{ $tool->name }} logo" loading="lazy">
                                    <div><h3><a class="entity-name-link" href="{{ route('tools.show', $tool) }}">{{ $tool->name }}</a></h3><p>{{ $tool->company?->name ?? 'Independent' }} <span>•</span> {{ $tool->category?->name ?? 'AI Tool' }}</p></div>
                                    <div class="tool-rating"><i data-lucide="star"></i><strong>{{ number_format((float)$tool->rating,1) }}</strong><small>/5</small></div>
                                </div>

                                <p class="tool-card-description">{{ Str::limit($tool->short_description ?: $tool->description, 115) }}</p>

                                <div class="tool-capabilities">
                                    @forelse($capabilities as $capability)<span>{{ $capability }}</span>@empty<span>AI Tool</span>@endforelse
                                </div>

                                <div class="tool-card-meta">
                                    <span class="price-badge {{ $isFree ? 'has-free' : '' }}"><i data-lucide="badge-dollar-sign"></i>{{ $priceLabel }}</span>
                                    @if($tool->benchmark_score)<span class="benchmark-badge"><i data-lucide="gauge"></i>{{ number_format((float)$tool->benchmark_score,1) }} score</span>@endif
                                </div>

                                <div class="tool-card-actions">
                                    <a class="tool-primary-action" href="{{ route('tools.show', $tool) }}">{{ \Illuminate\Support\Str::limit($tool->name, 28) }} details <i data-lucide="arrow-right"></i></a>
                                    <button class="compare-tool-btn" type="button" data-compare-tool><i data-lucide="scale"></i><span>Compare</span></button>
                                </div>
                            </div>
                        </article>
                    @endforeach
                </div>

                @if($tools->hasPages())
                <nav class="tool-pagination" aria-label="AI tools pagination">
                    @if($tools->onFirstPage())<span class="page-arrow disabled"><i data-lucide="chevron-left"></i> Previous</span>@else<a class="page-arrow" href="{{ $tools->previousPageUrl() }}"><i data-lucide="chevron-left"></i> Previous</a>@endif
                    <div class="page-numbers">
                        @php
                            $start = max(1, $tools->currentPage() - 2);
                            $end = min($tools->lastPage(), $tools->currentPage() + 2);
                        @endphp
                        @if($start > 1)<a href="{{ $tools->url(1) }}">1</a>@if($start > 2)<span>…</span>@endif @endif
                        @for($page=$start; $page<=$end; $page++)
                            <a href="{{ $tools->url($page) }}" class="{{ $page === $tools->currentPage() ? 'active' : '' }}">{{ $page }}</a>
                        @endfor
                        @if($end < $tools->lastPage())@if($end < $tools->lastPage()-1)<span>…</span>@endif<a href="{{ $tools->url($tools->lastPage()) }}">{{ $tools->lastPage() }}</a>@endif
                    </div>
                    @if($tools->hasMorePages())<a class="page-arrow" href="{{ $tools->nextPageUrl() }}">Next <i data-lucide="chevron-right"></i></a>@else<span class="page-arrow disabled">Next <i data-lucide="chevron-right"></i></span>@endif
                </nav>
                @endif
            @else
                <div class="tools-empty-state">
                    <div><i data-lucide="search-x"></i></div>
                    <span>No matching tools</span>
                    <h3>We couldn’t find a tool for those filters.</h3>
                    <p>Try broadening your search, removing a filter, or exploring all available AI tools.</p>
                    <a href="{{ route('tools.index') }}">Clear filters <i data-lucide="rotate-ccw"></i></a>
                </div>
            @endif
        </div>
    </div>
</section>

<div class="compare-tray" data-compare-tray aria-live="polite">
    <div class="compare-tray-copy"><span><i data-lucide="scale"></i>Compare tools</span><small>Select 2–4 tools to compare side by side</small></div>
    <div class="compare-selected" data-compare-selected></div>
    <div class="compare-tray-actions"><button type="button" data-compare-clear>Clear</button><button type="button" class="compare-launch" data-compare-launch disabled>Compare now <span data-compare-count>0</span><i data-lucide="arrow-right"></i></button></div>
</div>

<div class="quick-compare-modal" data-quick-compare-modal aria-hidden="true">
    <div class="quick-compare-backdrop" data-quick-compare-close></div>
    <section class="quick-compare-dialog" role="dialog" aria-modal="true" aria-labelledby="quick-compare-title" data-quick-compare-dialog>
        <div class="quick-compare-head">
            <div>
                <span><i data-lucide="zap"></i> Quick comparison</span>
                <h2 id="quick-compare-title" data-quick-compare-title>Compare selected AI tools</h2>
                <p data-quick-compare-subtitle>Fast directory snapshot · capabilities, use cases, pricing and comparable benchmark evidence.</p>
            </div>
            <button type="button" data-quick-compare-close aria-label="Close comparison"><i data-lucide="x"></i></button>
        </div>

        <div class="quick-compare-table" data-quick-compare-table></div>
        <div class="quick-compare-evidence" data-quick-compare-evidence></div>

        <div class="quick-compare-footer">
            <div class="quick-compare-footnote"><i data-lucide="shield-check"></i><span>Benchmark scores are compared only when the selected tools share the same verified benchmark.</span></div>
            <div class="quick-compare-actions">
                <button type="button" class="quick-compare-secondary" data-quick-compare-change><i data-lucide="replace"></i> Change tools</button>
                <a class="quick-compare-primary" href="{{ route('comparisons.builder', ['type' => 'tool']) }}" data-quick-compare-full data-preview-url="{{ route('comparisons.preview') }}">View full comparison <i data-lucide="arrow-right"></i></a>
            </div>
        </div>
    </section>
</div>
@endsection

@push('scripts')
<script src="{{ asset('js/frontend/tools.js') }}?v=20260829-quickcompare2"></script>
@endpush
