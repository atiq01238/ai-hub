@extends('frontend.layouts.app')

@section('title', $query !== '' ? 'Search: '.$query.' — AI Orbit' : 'Search AI Orbit')
@section('meta_description', 'Search AI tools, models, companies, news, articles, comparisons, benchmarks and independent Test Lab experiments across AI Orbit.')
@section('robots', 'noindex,follow')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/frontend/discovery.css') }}">
@endpush


@push('styles')<link rel="stylesheet" href="{{ asset('css/frontend/intelligence.css') }}">@endpush
@section('content')
@if(session('status'))<div class="search-smart-flash">{{ session('status') }}</div>@endif
@if(auth()->check() && $query)
<div class="search-smartbar">
    <div><strong>Search intelligence</strong><span>{{ number_format($total) }} results across AI Orbit</span></div>
    <form method="POST" action="{{ route('search.save') }}">@csrf<input type="hidden" name="query" value="{{ $query }}"><input type="hidden" name="type" value="{{ $type }}"><button type="submit"><i data-lucide="bookmark-plus"></i> Save this search</button></form>
</div>
@endif
@if(auth()->check() && ($recentSearches->isNotEmpty() || $savedSearches->isNotEmpty()))
<div class="search-memory">
 <div><b>Recent</b>@foreach($recentSearches as $term)<a href="{{ route('search.index',['q'=>$term]) }}">{{ $term }}</a>@endforeach</div>
 <div><b>Saved</b>@foreach($savedSearches as $saved)<span><a href="{{ route('search.index',['q'=>$saved->query,'type'=>$saved->type]) }}">{{ $saved->query }}</a><form method="POST" action="{{ route('search.saved.destroy',$saved) }}">@csrf @method('DELETE')<button>×</button></form></span>@endforeach</div>
</div>
@endif

<section class="discovery-hero search-hero">
    <div class="discovery-hero-grid"></div>
    <div class="discovery-hero-copy">
        <span class="eyebrow"><i data-lucide="search"></i> Global AI Search</span>
        <h1>Search the entire <span>AI Orbit.</span></h1>
        <p>Find tools, models, companies, news, guides, comparisons, benchmark data and independent Test Lab experiments from one research-driven index.</p>
        <form class="discovery-search search-intelligence-shell" action="{{ route('search.index') }}" method="get" data-search-shell>
            <i data-lucide="search"></i>
            <input name="q" type="search" value="{{ $query }}" placeholder="Search ChatGPT, Claude, image generators, AI news..." autocomplete="off" autofocus data-search-autocomplete>
            @if($type !== 'all')<input type="hidden" name="type" value="{{ $type }}">@endif
            <button type="submit">Search <i data-lucide="arrow-right"></i></button>
            <div class="search-live-results discovery-search-live-results" data-search-suggestions hidden></div>
        </form>
        @if($query !== '')
            <div class="search-summary"><strong>{{ number_format($total) }}</strong> matching results for <span>“{{ $query }}”</span></div>
        @endif
    </div>
</section>

<div class="discovery-page">
    @if($query !== '')
        @php($tabs=['all'=>'All','tools'=>'Tools','models'=>'Models','news'=>'News','companies'=>'Companies','articles'=>'Articles','comparisons'=>'Comparisons','benchmarks'=>'Benchmarks','tests'=>'Test Lab'])
        <nav class="result-tabs" aria-label="Search result types">
            @foreach($tabs as $key=>$label)
                <a class="{{ $type === $key ? 'active' : '' }}" href="{{ route('search.index', ['q'=>$query,'type'=>$key]) }}">
                    {{ $label }}
                    @if($key !== 'all')<span>{{ number_format($counts[$key]) }}</span>@else<span>{{ number_format($total) }}</span>@endif
                </a>
            @endforeach
        </nav>

        @if($discoveryPaths->isNotEmpty())
            <section class="search-discovery-paths">
                <div class="search-discovery-head"><span><i data-lucide="compass"></i></span><div><strong>Discovery paths</strong><small>Related categories, features and use cases</small></div></div>
                <div class="search-path-grid">
                    @foreach($discoveryPaths as $path)
                        <a href="{{ $path['url'] }}"><span>{{ $path['meta'] }}</span><strong>{{ $path['label'] }}</strong><i data-lucide="arrow-up-right"></i></a>
                    @endforeach
                </div>
            </section>
        @endif

        @if($total === 0)
            <section class="empty-discovery panelish">
                <span class="empty-icon"><i data-lucide="search-x"></i></span>
                <h2>No results found</h2>
                <p>Try a broader name, provider, category or product type. You can also browse the categories below.</p>
                @if($correction)
                    <a class="search-correction" href="{{ route('search.index',['q'=>$correction]) }}"><i data-lucide="wand-sparkles"></i> Did you mean <strong>{{ $correction }}</strong>?</a>
                @endif
                <a class="primary-action" href="{{ route('categories.index') }}">Browse Categories <i data-lucide="arrow-right"></i></a>
            </section>
        @else
            <div class="search-result-stack">
                @if(($type==='all'||$type==='tools') && $tools->isNotEmpty())
                    <section class="result-section">
                        <div class="section-bar"><div><span class="section-icon"><i data-lucide="bot"></i></span><h2>AI Tools</h2><small>{{ number_format($counts['tools']) }} matches</small></div><a href="{{ route('tools.index',['q'=>$query]) }}">View all tools <i data-lucide="arrow-right"></i></a></div>
                        <div class="entity-grid entity-grid-tools">
                            @foreach($tools as $tool)
                                <article class="search-entity-card">
                                    <a class="entity-top" href="{{ route('tools.show',$tool) }}" data-search-result data-search-query="{{ $query }}" data-search-target-type="tool" data-search-target-id="{{ $tool->id }}">
                                        <img src="{{ $tool->logo_url }}" alt="{{ $tool->name }} logo">
                                        <div><small>{{ $tool->company?->name ?? 'AI Tool' }}</small><h3>{{ $tool->name }}</h3><span>{{ $tool->category?->name ?? 'AI Software' }}</span></div>
                                        <b>{{ number_format((float)$tool->rating,1) }}</b>
                                    </a>
                                    <p>{{ \Illuminate\Support\Str::limit($tool->short_description ?: $tool->description, 112) }}</p>
                                    <div class="entity-meta"><span><i data-lucide="star"></i>{{ number_format((float)$tool->rating,1) }}</span><span><i data-lucide="flame"></i>{{ number_format((int)$tool->popularity) }}</span><a href="{{ route('tools.show',$tool) }}">View profile <i data-lucide="arrow-up-right"></i></a></div>
                                </article>
                            @endforeach
                        </div>
                    </section>
                @endif

                @if(($type==='all'||$type==='models') && $models->isNotEmpty())
                    <section class="result-section">
                        <div class="section-bar"><div><span class="section-icon cyan"><i data-lucide="cpu"></i></span><h2>AI Models</h2><small>{{ number_format($counts['models']) }} matches</small></div><a href="{{ route('models.index',['q'=>$query]) }}">View all models <i data-lucide="arrow-right"></i></a></div>
                        <div class="entity-grid">
                            @foreach($models as $model)
                                <article class="search-entity-card compact">
                                    <a class="entity-top" href="{{ route('models.show',$model) }}" data-search-result data-search-query="{{ $query }}" data-search-target-type="model" data-search-target-id="{{ $model->id }}">
                                        <img src="{{ $model->logo_url }}" alt="{{ $model->name }} logo">
                                        <div><small>{{ $model->company?->name ?? 'AI Model' }}</small><h3>{{ $model->name }}</h3><span>{{ $model->version ?: 'Current model' }}</span></div>
                                        <b>{{ $model->benchmark_score !== null ? number_format((float)$model->benchmark_score,1) : '—' }}</b>
                                    </a>
                                    <div class="model-stat-row"><span>Context <strong>{{ $model->context_window ?: '—' }}</strong></span><span>Input <strong>{{ $model->input_price_per_million !== null ? '$'.number_format((float)$model->input_price_per_million,2) : 'Not verified' }}</strong></span><span>Status <strong>{{ ucfirst($model->status) }}</strong></span></div>
                                </article>
                            @endforeach
                        </div>
                    </section>
                @endif

                @if(($type==='all'||$type==='news') && $news->isNotEmpty())
                    <section class="result-section">
                        <div class="section-bar"><div><span class="section-icon red"><i data-lucide="radio"></i></span><h2>AI News</h2><small>{{ number_format($counts['news']) }} matches</small></div><a href="{{ route('news.index',['q'=>$query]) }}">View all news <i data-lucide="arrow-right"></i></a></div>
                        <div class="story-grid">
                            @foreach($news as $item)
                                <article class="search-story-card">
                                    <a href="{{ route('news.show',$item) }}" class="story-image" data-search-result data-search-query="{{ $query }}" data-search-target-type="news" data-search-target-id="{{ $item->id }}"><img src="{{ $item->image_url ?: '/images/frontend/content-placeholder.svg' }}" alt="{{ $item->headline }}" onerror="this.style.display='none'"><span>{{ $item->category ?: 'AI News' }}</span></a>
                                    <div><small>{{ $item->company?->name ?? $item->source ?? 'AI Orbit' }} · {{ optional($item->published_at)->diffForHumans() }}</small><h3><a href="{{ route('news.show',$item) }}">{{ $item->headline }}</a></h3><p>{{ \Illuminate\Support\Str::limit($item->summary ?: $item->ai_summary, 125) }}</p></div>
                                </article>
                            @endforeach
                        </div>
                    </section>
                @endif

                @if(($type==='all'||$type==='companies') && $companies->isNotEmpty())
                    <section class="result-section">
                        <div class="section-bar"><div><span class="section-icon green"><i data-lucide="building-2"></i></span><h2>Companies</h2><small>{{ number_format($counts['companies']) }} matches</small></div><a href="{{ route('companies.index',['q'=>$query]) }}">View all companies <i data-lucide="arrow-right"></i></a></div>
                        <div class="company-search-grid">
                            @foreach($companies as $company)
                                <a class="company-search-card" href="{{ route('companies.show',$company) }}" data-search-result data-search-query="{{ $query }}" data-search-target-type="company" data-search-target-id="{{ $company->id }}"><img src="{{ $company->logo_url }}" alt="{{ $company->name }} logo"><div><h3>{{ $company->name }}</h3><p>{{ \Illuminate\Support\Str::limit($company->description, 90) }}</p><span>{{ $company->tools_count }} tools · {{ $company->models_count }} models</span></div><i data-lucide="arrow-up-right"></i></a>
                            @endforeach
                        </div>
                    </section>
                @endif

                @if(($type==='all'||$type==='articles') && $articles->isNotEmpty())
                    <section class="result-section">
                        <div class="section-bar"><div><span class="section-icon gold"><i data-lucide="newspaper"></i></span><h2>Articles & Guides</h2><small>{{ number_format($counts['articles']) }} matches</small></div><a href="{{ route('articles.index',['q'=>$query]) }}">View all articles <i data-lucide="arrow-right"></i></a></div>
                        <div class="story-grid">
                            @foreach($articles as $article)
                                <article class="search-story-card article-result">
                                    <a href="{{ route('articles.show',$article) }}" class="story-image" data-search-result data-search-query="{{ $query }}" data-search-target-type="article" data-search-target-id="{{ $article->id }}"><img src="{{ $article->featured_image_url ?: '/images/frontend/content-placeholder.svg' }}" alt="{{ $article->title }}" onerror="this.style.display='none'"><span>{{ $article->categoryTerm?->name ?? $article->category ?? 'Guide' }}</span></a>
                                    <div><small>{{ $article->author?->name ?? 'AI Orbit Editorial' }} · {{ optional($article->published_at)->format('M j, Y') }}</small><h3><a href="{{ route('articles.show',$article) }}">{{ $article->title }}</a></h3><p>{{ \Illuminate\Support\Str::limit($article->summary, 125) }}</p></div>
                                </article>
                            @endforeach
                        </div>
                    </section>
                @endif


                @if(($type==='all'||$type==='comparisons') && $comparisons->isNotEmpty())
                    <section class="result-section">
                        <div class="section-bar"><div><span class="section-icon cyan"><i data-lucide="scale"></i></span><h2>Comparisons</h2><small>{{ number_format($counts['comparisons']) }} matches</small></div><a href="{{ route('comparisons.index',['search'=>$query]) }}">View all comparisons <i data-lucide="arrow-right"></i></a></div>
                        <div class="entity-grid">
                            @foreach($comparisons as $comparison)
                                <article class="search-entity-card compact">
                                    <a class="entity-top" href="{{ route('comparisons.show',$comparison) }}" data-search-result data-search-query="{{ $query }}" data-search-target-type="comparison" data-search-target-id="{{ $comparison->id }}">
                                        <span class="category-orb"><i data-lucide="scale"></i></span>
                                        <div><small>{{ ucfirst($comparison->comparable_type) }} comparison</small><h3>{{ $comparison->title }}</h3><span>{{ number_format((int)$comparison->views) }} views</span></div>
                                        <b>VS</b>
                                    </a>
                                    <p>{{ \Illuminate\Support\Str::limit($comparison->summary ?: 'Side-by-side AI Orbit comparison with structured product intelligence.',112) }}</p>
                                </article>
                            @endforeach
                        </div>
                    </section>
                @endif

                @if(($type==='all'||$type==='benchmarks') && $benchmarks->isNotEmpty())
                    <section class="result-section">
                        <div class="section-bar"><div><span class="section-icon green"><i data-lucide="gauge"></i></span><h2>Benchmarks</h2><small>{{ number_format($counts['benchmarks']) }} matches</small></div><a href="{{ route('benchmarks.index') }}">View benchmark hub <i data-lucide="arrow-right"></i></a></div>
                        <div class="entity-grid">
                            @foreach($benchmarks as $benchmark)
                                <article class="search-entity-card compact">
                                    <a class="entity-top" href="{{ route('benchmarks.show',$benchmark) }}" data-search-result data-search-query="{{ $query }}" data-search-target-type="benchmark" data-search-target-id="{{ $benchmark->id }}">
                                        <span class="category-orb"><i data-lucide="gauge"></i></span>
                                        <div><small>{{ $benchmark->category ?: 'AI Benchmark' }}</small><h3>{{ $benchmark->name }}</h3><span>{{ $benchmark->version ?: ($benchmark->metric_type ?: 'Verified benchmark') }}</span></div>
                                        <b>{{ $benchmark->unit ?: 'Data' }}</b>
                                    </a>
                                    <p>{{ \Illuminate\Support\Str::limit($benchmark->description,112) }}</p>
                                </article>
                            @endforeach
                        </div>
                    </section>
                @endif

                @if(($type==='all'||$type==='tests') && $tests->isNotEmpty())
                    <section class="result-section">
                        <div class="section-bar"><div><span class="section-icon cyan"><i data-lucide="flask-conical"></i></span><h2>AI Test Lab</h2><small>{{ number_format($counts['tests']) }} matches</small></div><a href="{{ route('testlab.index',['q'=>$query]) }}">View all tests <i data-lucide="arrow-right"></i></a></div>
                        <div class="entity-grid">
                            @foreach($tests as $test)
                                <article class="search-entity-card compact">
                                    <a class="entity-top" href="{{ route('testlab.show',$test) }}" data-search-result data-search-query="{{ $query }}" data-search-target-type="test" data-search-target-id="{{ $test->id }}">
                                        <span class="category-orb"><i data-lucide="flask-conical"></i></span>
                                        <div><small>{{ $test->category }} · {{ config('test_lab.difficulties.'.$test->difficulty, ucfirst($test->difficulty)) }}</small><h3>{{ $test->name }}</h3><span>{{ $test->results_count }} completed model results</span></div>
                                        <b>{{ $test->is_verified ? '✓' : '—' }}</b>
                                    </a>
                                    <p>{{ \Illuminate\Support\Str::limit($test->short_description ?: $test->prompt,112) }}</p>
                                    <div class="entity-meta">@if($test->feature)<span><i data-lucide="sparkles"></i>{{ $test->feature->name }}</span>@endif @if($test->useCase)<span><i data-lucide="target"></i>{{ $test->useCase->name }}</span>@endif<a href="{{ route('testlab.show',$test) }}">View evidence <i data-lucide="arrow-up-right"></i></a></div>
                                </article>
                            @endforeach
                        </div>
                    </section>
                @endif
            </div>
        @endif
    @else
        <section class="browse-discovery-head"><div><span class="eyebrow"><i data-lucide="compass"></i> Discovery</span><h2>Explore what is popular right now</h2><p>Start with a category or jump into one of the most popular AI products.</p></div><a class="primary-action" href="{{ route('categories.index') }}">All Categories <i data-lucide="arrow-right"></i></a></section>
        @if($trendingSearches->isNotEmpty())
            <section class="search-trending-queries">
                <div><span><i data-lucide="trending-up"></i></span><strong>Trending searches</strong></div>
                <nav aria-label="Trending AI Orbit searches">
                    @foreach($trendingSearches as $trend)
                        <a href="{{ route('search.index',['q'=>$trend->query]) }}">{{ $trend->query }} <small>{{ number_format($trend->searches) }}</small></a>
                    @endforeach
                </nav>
            </section>
        @endif
        <div class="discovery-category-grid">
            @foreach($popularCategories as $category)
                <a class="discovery-category-card" href="{{ route('categories.show',$category) }}"><span class="category-orb"><i data-lucide="sparkles"></i></span><div><h3>{{ $category->name }}</h3><p>{{ number_format($category->tools_count) }} published AI tools</p></div><i data-lucide="arrow-up-right"></i></a>
            @endforeach
        </div>
        <section class="result-section trending-discovery">
            <div class="section-bar"><div><span class="section-icon"><i data-lucide="flame"></i></span><h2>Popular AI Tools</h2><small>High-interest products across AI Orbit</small></div><a href="{{ route('tools.index',['sort'=>'popular']) }}">Explore directory <i data-lucide="arrow-right"></i></a></div>
            <div class="entity-grid entity-grid-tools">
                @foreach($trendingTools as $tool)
                    <article class="search-entity-card"><a class="entity-top" href="{{ route('tools.show',$tool) }}" data-search-result data-search-query="{{ $query }}" data-search-target-type="tool" data-search-target-id="{{ $tool->id }}"><img src="{{ $tool->logo_url }}" alt="{{ $tool->name }} logo"><div><small>{{ $tool->company?->name }}</small><h3>{{ $tool->name }}</h3><span>Popular AI Tool</span></div><b>{{ number_format((float)$tool->rating,1) }}</b></a><p>{{ \Illuminate\Support\Str::limit($tool->short_description, 105) }}</p></article>
                @endforeach
            </div>
        </section>
    @endif
</div>
@endsection
