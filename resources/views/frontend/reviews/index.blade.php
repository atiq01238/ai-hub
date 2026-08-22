@extends('frontend.layouts.app')
@section('title','Independent AI Tool & Model Reviews | AI Hub')
@section('meta_description','Browse AI Hub editorial and community reviews for leading AI tools and models, with ratings, verdicts, strengths and trade-offs.')
@push('styles')<link rel="stylesheet" href="{{ asset('css/frontend/content.css') }}">@endpush

@section('content')
<section class="review-hero">
    <div class="content-wrap">
        <span class="content-eyebrow"><i data-lucide="star"></i> AI REVIEWS</span>
        <h1>Reviews built for <span>better decisions.</span></h1>
        <p>Editorial verdicts and moderated community feedback for the AI tools and models people actually use.</p>

        <form class="content-search" method="GET" action="{{ route('reviews.index') }}">
            <i data-lucide="search"></i>
            <input name="q" value="{{ request('q') }}" placeholder="Search ChatGPT, Claude, GPT models, verdicts...">
            <button>Search reviews</button>
        </form>

        <div class="review-stats">
            <div><strong>{{ number_format($stats['reviews']) }}</strong><span>Published reviews</span></div>
            <div><strong>{{ number_format($stats['editorial']) }}</strong><span>Editorial reviews</span></div>
            <div><strong>{{ number_format($stats['community']) }}</strong><span>Community reviews</span></div>
            <div><strong>{{ number_format($stats['average'],1) }}</strong><span>Average rating</span></div>
        </div>
    </div>
</section>

<section class="content-directory">
    <div class="content-wrap">
        <div class="content-toolbar">
            <div>
                <span class="content-kicker">REVIEW DATABASE</span>
                <h2>Latest AI Reviews</h2>
                <p>{{ number_format($reviews->total()) }} reviews match your selection.</p>
            </div>
            <div class="content-toolbar-actions">
                <button class="content-filter-open" type="button" data-content-filter-open><i data-lucide="sliders-horizontal"></i> Filters</button>
                <form method="GET">
                    @foreach(request()->except('sort','page') as $k=>$v)
                        <input type="hidden" name="{{ $k }}" value="{{ $v }}">
                    @endforeach
                    <select name="sort" onchange="this.form.submit()">
                        <option value="newest">Newest</option>
                        <option value="rating" @selected(request('sort')==='rating')>Highest rated</option>
                        <option value="oldest" @selected(request('sort')==='oldest')>Oldest</option>
                    </select>
                </form>
            </div>
        </div>

        <div class="content-layout review-layout">
            <div class="content-overlay" data-content-overlay></div>
            <aside class="content-filters" data-content-filters>
                <div class="content-filter-mobile">
                    <strong>Filter reviews</strong>
                    <button type="button" data-content-filter-close><i data-lucide="x"></i></button>
                </div>
                <form method="GET" action="{{ route('reviews.index') }}">
                    @if(request('q'))<input type="hidden" name="q" value="{{ request('q') }}">@endif
                    <div class="content-filter-head"><strong><i data-lucide="sliders-horizontal"></i> Filters</strong><a href="{{ route('reviews.index') }}">Reset</a></div>
                    <div class="content-filter-group">
                        <h3>Review type</h3>
                        @foreach(['editorial'=>'Editorial','user'=>'Community'] as $v=>$l)
                            <label><span><input type="radio" name="type" value="{{ $v }}" @checked(request('type')===$v)>{{ $l }}</span></label>
                        @endforeach
                    </div>
                    <div class="content-filter-group">
                        <h3>Minimum rating</h3>
                        @foreach(['4.5'=>'4.5+ stars','4'=>'4.0+ stars','3'=>'3.0+ stars'] as $v=>$l)
                            <label><span><input type="radio" name="rating" value="{{ $v }}" @checked(request('rating')===$v)>{{ $l }}</span></label>
                        @endforeach
                    </div>
                    <button class="content-apply">Apply filters</button>
                </form>
            </aside>

            <div class="review-results">
                @if($reviews->count())
                    <div class="review-directory-grid">
                        @foreach($reviews as $review)
                            @php
                                $reviewedItem = $review->model ?: $review->tool;
                                $reviewedType = $review->model ? 'AI Model' : 'AI Tool';
                                $logo = $reviewedItem?->logo_url ?? \App\Support\MediaUrl::placeholder();
                            @endphp
                            <article class="directory-review-card">
                                <div class="directory-review-top">
                                    <img src="{{ $logo }}" alt="{{ $reviewedItem?->name }} logo">
                                    <div>
                                        <small>{{ $reviewedItem?->company?->name ?? 'Independent' }} · {{ $reviewedType }}</small>
                                        <h3>{{ $reviewedItem?->name }}</h3>
                                    </div>
                                    <strong><i data-lucide="star"></i>{{ number_format((float)$review->rating,1) }}</strong>
                                </div>
                                <span class="review-type type-{{ $review->review_type }}">{{ $review->review_type==='editorial'?'Editorial review':'Community review' }}</span>
                                <h4>{{ $review->verdict ?: 'Review for '.$reviewedItem?->name }}</h4>
                                <p>{{ \Illuminate\Support\Str::limit($review->body ?: 'A published rating from the AI Hub review system.',155) }}</p>
                                <div class="directory-review-foot">
                                    <span>{{ $review->user?->name ?? 'AI Hub Editorial' }} · {{ $review->created_at->diffForHumans() }}</span>
                                    <a href="{{ route('reviews.show',$review) }}">Read review <i data-lucide="arrow-right"></i></a>
                                </div>
                            </article>
                        @endforeach
                    </div>
                @else
                    <div class="content-empty"><i data-lucide="star"></i><h3>No reviews found</h3><p>Try a different rating or review type.</p><a href="{{ route('reviews.index') }}">Reset filters</a></div>
                @endif

                @if($reviews->hasPages())
                    <div class="content-pagination">
                        <a class="{{ $reviews->onFirstPage()?'disabled':'' }}" href="{{ $reviews->previousPageUrl() ?: '#' }}"><i data-lucide="chevron-left"></i> Previous</a>
                        <div>@foreach(range(1,$reviews->lastPage()) as $page)<a class="{{ $reviews->currentPage()===$page?'active':'' }}" href="{{ $reviews->url($page) }}">{{ $page }}</a>@endforeach</div>
                        <a class="{{ !$reviews->hasMorePages()?'disabled':'' }}" href="{{ $reviews->nextPageUrl() ?: '#' }}">Next <i data-lucide="chevron-right"></i></a>
                    </div>
                @endif
            </div>

            <aside class="content-side">
                <div class="side-card">
                    <span class="side-label">TOP RATED TOOLS</span>
                    <h3>Best reviewed tools</h3>
                    @foreach($topTools as $tool)
                        <a class="top-review-tool" href="{{ route('tools.show',$tool) }}">
                            <img src="{{ $tool->logo_url }}" alt="{{ $tool->name }} logo">
                            <div><strong>{{ $tool->name }}</strong><small>{{ (int)$tool->review_count }} reviews</small></div>
                            <b>★ {{ number_format((float)$tool->review_avg,1) }}</b>
                        </a>
                    @endforeach
                </div>
                <div class="side-card editorial-card"><i data-lucide="shield-check"></i><h3>Moderated reviews</h3><p>Only reviews with <strong>published</strong> status and a public tool or model are visible here.</p></div>
            </aside>
        </div>
    </div>
</section>
@endsection

@push('scripts')<script src="{{ asset('js/frontend/content.js') }}"></script>@endpush
