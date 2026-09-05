@extends('frontend.layouts.app')

@php
    $newsHasFilters = request()->hasAny([
        'q',
        'tab',
        'category',
        'company',
        'source',
        'period',
        'verification',
        'sentiment',
        'sort',
    ]);

    $newsSeoTitle = 'AI News Today (Sep 2026) — Model Releases, Funding & Research';

    if (!$newsHasFilters && $news->currentPage() > 1) {
        $newsSeoTitle = 'AI News — Page '
            . $news->currentPage()
            . ' | AI Orbit';
    }

    $newsSeoDescription = 'Daily AI news with sources verified — new model releases, funding rounds, pricing changes and security updates. Updated today.';

    $newsCanonical = route('news.index');

    if (!$newsHasFilters && $news->currentPage() > 1) {
        $newsCanonical = $news->url($news->currentPage());
    }

    $newsCollectionSchema = [
        '@' . 'context' => 'https://schema.org',
        '@' . 'type' => 'CollectionPage',
        'name' => 'AI News',
        'description' => $newsSeoDescription,
        'url' => $newsCanonical,
    ];

    $newsBreadcrumbSchema = [
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
                'name' => 'AI News',
                'item' => route('news.index'),
            ],
        ],
    ];
@endphp

@section('title', $newsSeoTitle)
@section('meta_description', $newsSeoDescription)
@section('canonical', $newsCanonical)

@section(
    'robots',
    $newsHasFilters
        ? 'noindex,follow'
        : 'index,follow,max-image-preview:large,max-snippet:-1,max-video-preview:-1'
)

@push('head')
<script type="application/ld+json">{!! json_encode(
    $newsCollectionSchema,
    JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
) !!}</script>

<script type="application/ld+json">{!! json_encode(
    $newsBreadcrumbSchema,
    JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
) !!}</script>
@endpush

@push('styles')
<link rel="stylesheet" href="{{ asset('css/frontend/news.css') }}">
@endpush

@php
    $imageFor = fn ($item) => $item->image_url;
    $activeTab = request('tab', 'latest');
    $tabUrl = fn ($tab) => route('news.index', array_filter(array_merge(request()->except('page', 'tab'), ['tab' => $tab === 'latest' ? null : $tab])));
@endphp

@section('content')
<section class="news-hero">
    <div class="news-hero-grid"></div>
    <div class="news-hero-glow"></div>
    <div class="news-container news-hero-inner">
        <span class="news-kicker"><i data-lucide="radio-tower"></i> AI intelligence desk <b>Live</b></span>
        <h1>Know what matters in <span>AI.</span></h1>
        <p>Product launches, model releases, research, security, funding and pricing changes — organized with context so you can understand the signal, not just the headline.</p>

        <form class="news-search" method="get" action="{{ route('news.index') }}">
            <i data-lucide="search"></i>
            <input type="search" name="q" value="{{ request('q') }}" placeholder="Search AI news, companies, models, topics..." aria-label="Search AI news">
            @if(request('tab'))<input type="hidden" name="tab" value="{{ request('tab') }}">@endif
            <button type="submit">Search news <i data-lucide="arrow-right"></i></button>
        </form>

        <div class="news-stats">
            <div><strong>{{ number_format((int) $stats['published']) }}</strong><span>Published briefs</span></div>
            <div><strong>{{ number_format((int) $stats['today']) }}</strong><span>New today</span></div>
            <div><strong>{{ number_format((int) $stats['verified']) }}</strong><span>Verified</span></div>
            <div><strong>{{ number_format((int) $stats['sources']) }}</strong><span>Active sources</span></div>
        </div>
    </div>
</section>

<div class="news-container news-page">
    <nav class="news-tabs" aria-label="News sections">
        <a class="{{ $activeTab === 'latest' ? 'active' : '' }}" href="{{ $tabUrl('latest') }}"><i data-lucide="clock-3"></i>Latest</a>
        <a class="{{ $activeTab === 'breaking' ? 'active' : '' }}" href="{{ $tabUrl('breaking') }}"><i data-lucide="flame"></i>Breaking</a>
        <a class="{{ $activeTab === 'trending' ? 'active' : '' }}" href="{{ $tabUrl('trending') }}"><i data-lucide="trending-up"></i>Trending</a>
        <a class="{{ $activeTab === 'research' ? 'active' : '' }}" href="{{ $tabUrl('research') }}"><i data-lucide="microscope"></i>Research</a>
    </nav>

    @if($featured->isNotEmpty() && !request('q') && !request('category') && !request('company'))
    <section class="news-featured-section">
        <div class="news-section-head">
            <div><span>EDITOR'S SIGNAL</span><h2>Top stories to watch</h2></div>
            <p>Prioritized by recency, importance and verification context.</p>
        </div>
        <div class="news-featured-grid">
            @php($lead = $featured->first())
            <a class="news-lead-card" href="{{ route('news.show', $lead) }}">
                @if($imageFor($lead))<img src="{{ $imageFor($lead) }}" alt="{{ $lead->headline }}">@else<div class="news-media-placeholder news-media-placeholder--lead"><i data-lucide="image-off"></i><span>No story image provided</span></div>@endif
                <span class="news-media-shade"></span>
                <div class="news-lead-copy">
                    <div class="news-card-meta"><span class="news-category">{{ $lead->category ?? 'AI Update' }}</span>@if($lead->verification_status === 'verified')<span class="verified"><i data-lucide="badge-check"></i>Verified</span>@endif</div>
                    <h2>{{ $lead->headline }}</h2>
                    <p>{{ $lead->ai_summary ?: $lead->summary }}</p>
                    <div class="news-source-row">
                        @if($lead->company)<img src="{{ $lead->company->logo_url }}" alt="{{ $lead->company->name }} logo">@endif
                        <span>{{ $lead->source ?: ($lead->company?->name ?? 'AI Orbit Desk') }}</span><b>•</b><time>{{ optional($lead->published_at)->diffForHumans() }}</time>
                    </div>
                </div>
            </a>
            <div class="news-featured-stack">
                @foreach($featured->skip(1) as $item)
                <a class="news-featured-mini" href="{{ route('news.show', $item) }}">
                    <div class="news-featured-mini-img">@if($imageFor($item))<img src="{{ $imageFor($item) }}" alt="{{ $item->headline }}">@else<div class="news-media-placeholder"><i data-lucide="image-off"></i></div>@endif<span>{{ $item->importance }}</span></div>
                    <div><span class="news-category">{{ $item->category ?? 'AI Update' }}</span><h3>{{ $item->headline }}</h3><p>{{ Str::limit($item->ai_summary ?: $item->summary, 105) }}</p><small>{{ $item->source ?: ($item->company?->name ?? 'AI Orbit') }} • {{ optional($item->published_at)->diffForHumans() }}</small></div>
                </a>
                @endforeach
            </div>
        </div>
    </section>
    @endif

    <section class="news-directory-section">
        <div class="news-section-head news-directory-head">
            <div><span>NEWS DATABASE</span><h2>{{ ucfirst($activeTab) }} AI news</h2><small>{{ number_format($news->total()) }} stories match your current selection.</small></div>
            <button class="news-filter-toggle" type="button" data-news-filter-toggle><i data-lucide="sliders-horizontal"></i>Filters</button>
        </div>

        <div class="news-directory-layout">
            <aside class="news-filters" data-news-filters>
                <form method="get" action="{{ route('news.index') }}">
                    @if(request('tab'))<input type="hidden" name="tab" value="{{ request('tab') }}">@endif
                    @if(request('q'))<input type="hidden" name="q" value="{{ request('q') }}">@endif
                    <div class="filter-title"><span><i data-lucide="sliders-horizontal"></i> Refine news</span><a href="{{ route('news.index', request('tab') ? ['tab'=>request('tab')] : []) }}">Reset</a></div>

                    <label class="filter-group"><span>Category</span><select name="category"><option value="">All categories</option>@foreach($categories as $category)<option value="{{ $category->category }}" @selected(request('category')===$category->category)>{{ $category->category }} ({{ $category->total }})</option>@endforeach</select></label>
                    <label class="filter-group"><span>Company</span><select name="company"><option value="">All companies</option>@foreach($companies as $company)<option value="{{ $company->slug }}" @selected(request('company')===$company->slug)>{{ $company->name }} ({{ $company->news_items_count }})</option>@endforeach</select></label>
                    <label class="filter-group"><span>Source</span><select name="source"><option value="">All sources</option>@foreach($sources as $source)<option value="{{ $source->id }}" @selected((string)request('source')===(string)$source->id)>{{ $source->name }} ({{ $source->news_items_count }})</option>@endforeach</select></label>
                    <label class="filter-group"><span>Published</span><select name="period"><option value="">Any time</option><option value="24h" @selected(request('period')==='24h')>Past 24 hours</option><option value="7d" @selected(request('period')==='7d')>Past 7 days</option><option value="30d" @selected(request('period')==='30d')>Past 30 days</option></select></label>
                    <label class="filter-group"><span>Verification</span><select name="verification"><option value="">Any status</option><option value="verified" @selected(request('verification')==='verified')>Verified</option><option value="needs_verification" @selected(request('verification')==='needs_verification')>Needs verification</option><option value="unverified" @selected(request('verification')==='unverified')>Unverified</option></select></label>
                    <label class="filter-group"><span>Sentiment</span><select name="sentiment"><option value="">All sentiment</option><option value="positive" @selected(request('sentiment')==='positive')>Positive</option><option value="neutral" @selected(request('sentiment')==='neutral')>Neutral</option><option value="negative" @selected(request('sentiment')==='negative')>Negative</option></select></label>
                    <label class="filter-group"><span>Sort by</span><select name="sort"><option value="newest" @selected(request('sort','newest')==='newest')>Newest first</option><option value="importance" @selected(request('sort')==='importance')>Highest importance</option><option value="oldest" @selected(request('sort')==='oldest')>Oldest first</option></select></label>
                    <button class="filter-apply" type="submit">Apply filters <i data-lucide="arrow-right"></i></button>
                </form>
            </aside>

            <div class="news-results">
                @if($news->isEmpty())
                    <div class="news-empty"><i data-lucide="newspaper"></i><h3>No news found</h3><p>Try removing a filter or searching for a broader AI topic.</p><a href="{{ route('news.index') }}">Clear all filters</a></div>
                @else
                <div class="news-card-grid">
                    @foreach($news as $item)
                    <article class="news-card">
                        <a class="news-card-media" href="{{ route('news.show', $item) }}">@if($imageFor($item))<img src="{{ $imageFor($item) }}" alt="{{ $item->headline }}" loading="lazy">@else<div class="news-media-placeholder"><i data-lucide="image-off"></i><span>No story image</span></div>@endif<span class="importance-badge"><i data-lucide="activity"></i>{{ (int)$item->importance }}</span></a>
                        <div class="news-card-body">
                            <div class="news-card-meta"><span class="news-category">{{ $item->category ?? 'AI Update' }}</span>@if($item->verification_status === 'verified')<span class="verified"><i data-lucide="badge-check"></i>Verified</span>@endif<button type="button" class="save-item-btn compact" data-save-item data-save-type="news" data-save-id="{{ $item->id }}" aria-label="Save news" aria-pressed="false"><i data-lucide="bookmark"></i></button></div>
                            <h3><a href="{{ route('news.show', $item) }}">{{ $item->headline }}</a></h3>
                            <p>{{ Str::limit($item->ai_summary ?: $item->summary, 145) }}</p>
                            <div class="news-card-footer">
                                <div class="news-source-row">@if($item->company)<img src="{{ $item->company->logo_url }}" alt="{{ $item->company->name }} logo">@endif<span>{{ $item->source ?: ($item->company?->name ?? 'AI Orbit') }}</span></div>
                                <time>{{ optional($item->published_at)->diffForHumans() }}</time>
                            </div>
                        </div>
                    </article>
                    @endforeach
                </div>

                @if($news->hasPages())
                <nav class="news-pagination" aria-label="News pagination">
                    @if($news->onFirstPage())<span class="disabled"><i data-lucide="chevron-left"></i>Previous</span>@else<a href="{{ $news->previousPageUrl() }}"><i data-lucide="chevron-left"></i>Previous</a>@endif
                    <div>@foreach(range(1,$news->lastPage()) as $page)@if($page===1 || $page===$news->lastPage() || abs($page-$news->currentPage())<=1)<a class="{{ $page===$news->currentPage()?'active':'' }}" href="{{ $news->url($page) }}">{{ $page }}</a>@elseif(abs($page-$news->currentPage())===2)<span>…</span>@endif @endforeach</div>
                    @if($news->hasMorePages())<a href="{{ $news->nextPageUrl() }}">Next<i data-lucide="chevron-right"></i></a>@else<span class="disabled">Next<i data-lucide="chevron-right"></i></span>@endif
                </nav>
                @endif
                @endif
            </div>

            <aside class="news-sidebar">
                <div class="news-side-card">
                    <div class="side-card-head"><span><i data-lucide="trending-up"></i>Trending now</span><small>Signal score</small></div>
                    @foreach($trending as $index=>$item)
                    <a class="trend-row" href="{{ route('news.show',$item) }}"><b>#{{ $index+1 }}</b><div><strong>{{ Str::limit($item->headline, 58) }}</strong><small>{{ $item->company?->name ?? $item->source }} • {{ optional($item->published_at)->diffForHumans() }}</small></div><em>{{ (int)$item->importance }}</em></a>
                    @endforeach
                </div>
                <div class="news-side-card">
                    <div class="side-card-head"><span><i data-lucide="layers-3"></i>Topics</span><small>Published</small></div>
                    @foreach($categories->take(8) as $category)
                    <a class="topic-row" href="{{ route('news.index',['category'=>$category->category]) }}"><span>{{ $category->category }}</span><b>{{ $category->total }}</b></a>
                    @endforeach
                </div>
                <div class="news-method-card"><i data-lucide="shield-check"></i><h3>News you can inspect</h3><p>AI Orbit keeps source, verification and processing context visible instead of presenting every headline as equally reliable.</p><a href="{{ route('methodology') }}">Our methodology <i data-lucide="arrow-up-right"></i></a></div>
            </aside>
        </div>
    </section>
</div>
@endsection

@push('scripts')
<script src="{{ asset('js/frontend/news.js') }}"></script>
@endpush
