@extends('frontend.layouts.app')

@section('title', 'AI Tools Directory — Discover & Compare the Best AI Tools | AI Hub')
@section('meta_description', 'Explore AI tools by category, pricing, rating, company, platform and capability. Compare top AI products and find the right tool for your workflow.')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/frontend/tools.css') }}">
@endpush

@section('content')
@php
    $activeFilters = collect(['category','pricing','rating','company','platform','feature'])
        ->filter(fn ($key) => request()->filled($key))
        ->count();
@endphp

<section class="tools-hero">
    <div class="tools-hero-glow one"></div>
    <div class="tools-hero-glow two"></div>
    <div class="tools-page-container tools-hero-inner">
        <nav class="tools-breadcrumb" aria-label="Breadcrumb">
            <a href="{{ route('home') }}">Home</a><i data-lucide="chevron-right"></i><span>AI Tools</span>
        </nav>

        <div class="tools-hero-copy">
            <span class="tools-eyebrow"><i data-lucide="sparkles"></i> Curated AI Tools Directory</span>
            <h1>Find the right <span>AI tool</span> for any workflow.</h1>
            <p>Search, filter and compare trusted AI products for writing, coding, image generation, video, research, productivity and more.</p>
        </div>

        <form class="tools-search" method="GET" action="{{ route('tools.index') }}" role="search">
            @foreach(['category','pricing','rating','company','platform','feature','sort','view'] as $param)
                @if(request()->filled($param))<input type="hidden" name="{{ $param }}" value="{{ request($param) }}">@endif
            @endforeach
            <i data-lucide="search"></i>
            <input type="search" name="q" value="{{ request('q') }}" placeholder="Search ChatGPT, Midjourney, coding tools, voice AI..." aria-label="Search AI tools">
            @if(request()->filled('q'))
                <a class="search-clear" href="{{ route('tools.index', request()->except('q','page')) }}" aria-label="Clear search"><i data-lucide="x"></i></a>
            @endif
            <button type="submit">Search Tools <i data-lucide="arrow-right"></i></button>
        </form>

        <div class="tools-hero-stats">
            <div><strong>{{ number_format($stats['tools']) }}+</strong><span>Published tools</span></div>
            <div><strong>{{ number_format($stats['categories']) }}</strong><span>Categories</span></div>
            <div><strong>{{ number_format($stats['free']) }}+</strong><span>Free options</span></div>
            <div><strong>{{ number_format($stats['topRated']) }}</strong><span>Top rated 4.5+</span></div>
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
                <img src="{{ asset($tool->logo_path ?: 'storage/ai-hub/tools/logos/chatgpt.png') }}" alt="{{ $tool->name }} logo">
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
                        @foreach(['Web','API','Desktop','Mobile'] as $platform)
                            <label><span><input type="radio" name="platform" value="{{ $platform }}" @checked(request('platform')===$platform)><i></i>{{ $platform }}</span></label>
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

                <div class="filter-actions-mobile"><a href="{{ route('tools.index') }}">Reset</a><button type="submit">Show Results</button></div>
            </form>
        </aside>

        <div class="directory-results">
            @if($activeFilters || request()->filled('q'))
            <div class="active-filter-row">
                <span>Active filters:</span>
                @foreach(['category'=>'Category','pricing'=>'Pricing','rating'=>'Rating','company'=>'Company','platform'=>'Platform','feature'=>'Capability'] as $key=>$label)
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
                            $cover = $tool->cover_image_path ? asset($tool->cover_image_path) : null;
                        @endphp
                        <article class="tool-directory-card" data-tool-card data-tool-id="{{ $tool->id }}" data-tool-name="{{ $tool->name }}" data-tool-logo="{{ asset($tool->logo_path ?: 'storage/ai-hub/tools/logos/chatgpt.png') }}" data-tool-rating="{{ number_format((float)$tool->rating,1) }}" data-tool-price="{{ $priceLabel }}" data-tool-benchmark="{{ $tool->benchmark_score ? number_format((float)$tool->benchmark_score,1) : '—' }}" data-tool-category="{{ $tool->category?->name ?? 'AI Tool' }}" data-tool-company="{{ $tool->company?->name ?? 'Independent' }}">
                            <div class="tool-card-media" @if($cover) style="--tool-cover:url('{{ $cover }}')" @endif>
                                <div class="tool-media-shade"></div>
                                <span class="tool-rank-badge"><i data-lucide="trending-up"></i>{{ $tool->popularity }}% popular</span>
                                <button type="button" class="save-tool-btn" aria-label="Save {{ $tool->name }}"><i data-lucide="bookmark"></i></button>
                            </div>
                            <div class="tool-card-body">
                                <div class="tool-card-identity">
                                    <img src="{{ asset($tool->logo_path ?: 'storage/ai-hub/tools/logos/chatgpt.png') }}" alt="{{ $tool->name }} logo" loading="lazy">
                                    <div><h3>{{ $tool->name }}</h3><p>{{ $tool->company?->name ?? 'Independent' }} <span>•</span> {{ $tool->category?->name ?? 'AI Tool' }}</p></div>
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
                                    <a class="tool-primary-action" href="{{ route('tools.show', $tool) }}">View details <i data-lucide="arrow-right"></i></a>
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
    <section class="quick-compare-dialog" role="dialog" aria-modal="true" aria-labelledby="quick-compare-title">
        <div class="quick-compare-head">
            <div><span>Quick comparison</span><h2 id="quick-compare-title">Compare selected AI tools</h2><p>A fast side-by-side view using the directory data. The full comparison page can add deeper feature and benchmark analysis later.</p></div>
            <button type="button" data-quick-compare-close aria-label="Close comparison"><i data-lucide="x"></i></button>
        </div>
        <div class="quick-compare-table" data-quick-compare-table></div>
    </section>
</div>
@endsection

@push('scripts')
<script src="{{ asset('js/frontend/tools.js') }}"></script>
@endpush
