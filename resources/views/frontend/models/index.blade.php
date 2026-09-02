@extends('frontend.layouts.app')
@php
    $modelsHasFilters = request()->hasAny([
        'q',
        'company',
        'status',
        'context',
        'price',
        'capability',
        'sort',
    ]);

    $modelsSeoTitle = 'AI Models Directory — Compare Leading AI Models | AI Orbit';

    if (!$modelsHasFilters && $models->currentPage() > 1) {
        $modelsSeoTitle = 'AI Models Directory — Page '
            . $models->currentPage()
            . ' | AI Orbit';
    }

    $modelsSeoDescription = 'Explore and compare leading AI models by provider, context window, API pricing, capabilities and benchmark score.';

    $modelsCanonical = route('models.index');

    if (!$modelsHasFilters && $models->currentPage() > 1) {
        $modelsCanonical = $models->url($models->currentPage());
    }

    $modelsCollectionSchema = [
        '@' . 'context' => 'https://schema.org',
        '@' . 'type' => 'CollectionPage',
        'name' => 'AI Models Directory',
        'description' => $modelsSeoDescription,
        'url' => $modelsCanonical,
    ];

    $modelsBreadcrumbSchema = [
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
                'name' => 'AI Models',
                'item' => route('models.index'),
            ],
        ],
    ];
@endphp

@section('title', $modelsSeoTitle)
@section('meta_description', $modelsSeoDescription)
@section('canonical', $modelsCanonical)

@section(
    'robots',
    $modelsHasFilters
        ? 'noindex,follow'
        : 'index,follow,max-image-preview:large,max-snippet:-1,max-video-preview:-1'
)

@push('head')
<script type="application/ld+json">{!! json_encode(
    $modelsCollectionSchema,
    JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
) !!}</script>

<script type="application/ld+json">{!! json_encode(
    $modelsBreadcrumbSchema,
    JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
) !!}</script>
@endpush
@push('styles')<link rel="stylesheet" href="{{ asset('css/frontend/models.css') }}?v=20260902-discovery-ui">@endpush
@section('content')
<section class="model-hero model-hero-wave">
<div class="model-wave-art" aria-hidden="true"></div><div class="model-wave-shade" aria-hidden="true"></div>
<div class="model-wrap">
<nav class="model-hero-breadcrumb"><a href="{{ route('home') }}">Home</a><i data-lucide="chevron-right"></i><span>AI Models</span></nav>
<div class="model-hero-copy">
<div class="model-eyebrow"><i data-lucide="sparkles"></i> CURATED AI MODELS DIRECTORY</div>
<h1>Explore Leading <span>AI Models.</span></h1>
<p>Compare the most advanced AI models across reasoning, coding, multimodality, context length, pricing and benchmark performance.</p>
<form class="model-search" method="GET" action="{{ route('models.index') }}"><i data-lucide="search"></i><input name="q" value="{{ request('q') }}" placeholder="Search AI models, companies, capabilities..."><button>Search</button></form>
<div class="model-hero-chips">
<a href="{{ route('models.index',['capability'=>'reasoning']) }}"><i data-lucide="brain-circuit"></i> Reasoning</a>
<a href="{{ route('models.index',['capability'=>'multimodal']) }}"><i data-lucide="layout-grid"></i> Multimodal</a>
<a href="{{ route('models.index',['capability'=>'code-generation']) }}"><i data-lucide="code-2"></i> Coding</a>
<a href="{{ route('models.index',['capability'=>'image-understanding']) }}"><i data-lucide="eye"></i> Vision</a>
<a href="{{ route('models.index',['context'=>'1m']) }}"><i data-lucide="infinity"></i> Long Context</a>
<a href="#model-directory"><i data-lucide="sliders-horizontal"></i> More Filters</a>
</div></div>
<div class="model-stats">
<div><span class="model-stat-icon"><i data-lucide="box"></i></span><span><strong>{{ number_format($stats['models']) }}+</strong><small>AI Models</small></span></div>
<div><span class="model-stat-icon"><i data-lucide="building-2"></i></span><span><strong>{{ number_format($stats['providers']) }}+</strong><small>Providers</small></span></div>
<div><span class="model-stat-icon"><i data-lucide="trophy"></i></span><span><strong>{{ $stats['topScore'] !== null ? number_format((float)$stats['topScore'],1) : '—' }}</strong><small>Top Benchmark</small></span></div>
<div><span class="model-stat-icon"><i data-lucide="activity"></i></span><span><strong>Updated</strong><small>Fresh Model Data</small></span></div>
</div></div></section>

@if($leaders->isNotEmpty())
<section class="model-trending-section">
    <div class="model-wrap">
        <div class="model-trending-head">
            <div>
                <span><i data-lucide="flame"></i> Trending models</span>
                <h2>Popular AI models right now</h2>
                <p>Quick access to leading models ranked by benchmark performance.</p>
            </div>
        </div>
        <div class="model-trending-grid">
            @foreach($leaders->take(5) as $i => $leader)
                <a href="{{ route('models.show',$leader) }}" class="model-trending-card">
                    <b>#{{ $i + 1 }}</b>
                    <img src="{{ $leader->logo_url }}" alt="{{ $leader->name }} logo">
                    <span>
                        <strong>{{ $leader->name }}</strong>
                        <small>{{ $leader->company?->name ?: 'Independent' }}</small>
                    </span>
                    <em><i data-lucide="trophy"></i>{{ number_format((float)$leader->benchmark_score,1) }}</em>
                    <i class="trend-arrow" data-lucide="arrow-up-right"></i>
                </a>
            @endforeach
        </div>
    </div>
</section>
@endif

@if(!$modelsHasFilters && ($providerHubs->isNotEmpty() || $featureHubs->isNotEmpty()))
<section class="model-wrap model-discovery-hubs" aria-label="Explore AI model providers and capabilities">
    @if($providerHubs->isNotEmpty())
    <article class="model-discovery-card model-discovery-card--providers">
        <div class="model-discovery-card-top">
            <div class="model-discovery-icon" aria-hidden="true"><i data-lucide="building-2"></i></div>
            <div class="model-discovery-title">
                <span>Discover by provider</span>
                <h2>Explore Model Providers</h2>
            </div>
            <a class="model-discovery-all" href="{{ route('companies.index') }}">View all <i data-lucide="arrow-right"></i></a>
        </div>
        <p class="model-discovery-description">Browse leading AI companies and jump directly to their models, tools and related intelligence.</p>
        <div class="model-discovery-chips" aria-label="AI model provider links">
            @foreach($providerHubs as $providerHub)
                <a href="{{ route('companies.show', $providerHub) }}"><i data-lucide="building"></i><span>{{ $providerHub->name }}</span></a>
            @endforeach
        </div>
    </article>
    @endif

    @if($featureHubs->isNotEmpty())
    <article class="model-discovery-card model-discovery-card--capabilities">
        <div class="model-discovery-card-top">
            <div class="model-discovery-icon" aria-hidden="true"><i data-lucide="sparkles"></i></div>
            <div class="model-discovery-title">
                <span>Discover by strength</span>
                <h2>Browse Model Capabilities</h2>
            </div>
            <a class="model-discovery-all" href="{{ route('features.index') }}">View all <i data-lucide="arrow-right"></i></a>
        </div>
        <p class="model-discovery-description">Find models by what they do best, from reasoning and coding to vision, audio and agent workflows.</p>
        <div class="model-discovery-chips" aria-label="AI model capability links">
            @foreach($featureHubs as $featureHub)
                <a href="{{ route('features.show', $featureHub) }}"><i data-lucide="zap"></i><span>{{ $featureHub->name }}</span></a>
            @endforeach
        </div>
    </article>
    @endif
</section>
@endif

<section class="model-directory" id="model-directory">
    <div class="model-wrap">
        <div class="directory-head">
            <div>
                <span class="section-kicker">MODEL DATABASE</span>
                <h2>Explore AI Models</h2>
                <p>{{ number_format($models->total()) }} {{ Str::plural('model', $models->total()) }} match your current selection.</p>
            </div>
            <div class="directory-actions">
                <button class="mobile-filter" type="button" data-model-filter-open>
                    <i data-lucide="sliders-horizontal"></i> Filters
                </button>
                <form method="GET" class="model-sort-form">
                    @foreach(request()->except('sort','page') as $k=>$v)
                        @if(is_string($v))<input type="hidden" name="{{ $k }}" value="{{ $v }}">@endif
                    @endforeach
                    <label for="model-sort">Sort by</label>
                    <select id="model-sort" name="sort" onchange="this.form.submit()">
                        <option value="benchmark" @selected(request('sort','benchmark')==='benchmark')>Best benchmark</option>
                        <option value="newest" @selected(request('sort')==='newest')>Newest</option>
                        <option value="price_low" @selected(request('sort')==='price_low')>Lowest input price</option>
                        <option value="name" @selected(request('sort')==='name')>Name A–Z</option>
                    </select>
                </form>
                <div class="model-view" aria-label="Model view">
                    <button type="button" class="active" data-model-view="grid" aria-label="Grid view"><i data-lucide="grid-2x2"></i></button>
                    <button type="button" data-model-view="list" aria-label="List view"><i data-lucide="list"></i></button>
                </div>
            </div>
        </div>

        <div class="model-layout">
            <div class="model-overlay" data-model-overlay></div>

            <aside class="model-filters" data-model-filters>
                <div class="filter-mobile-title">
                    <strong>Filter models</strong>
                    <button type="button" data-model-filter-close aria-label="Close filters"><i data-lucide="x"></i></button>
                </div>

                <form method="GET" action="{{ route('models.index') }}">
                    @if(request('q'))<input type="hidden" name="q" value="{{ request('q') }}">@endif
                    @if(request('sort'))<input type="hidden" name="sort" value="{{ request('sort') }}">@endif

                    <div class="mf-head">
                        <strong><i data-lucide="sliders-horizontal"></i> Filters</strong>
                        @if(request()->except('sort','page'))<a href="{{ route('models.index') }}">Clear all</a>@endif
                    </div>

                    <div class="mf-group">
                        <h3>Provider</h3>
                        <div class="mf-options">
                            @foreach($companies as $company)
                                <label>
                                    <span><input type="radio" name="company" value="{{ $company->slug }}" @checked(request('company')===$company->slug)><i></i>{{ $company->name }}</span>
                                    <small>{{ $company->models_count }}</small>
                                </label>
                            @endforeach
                        </div>
                    </div>

                    <div class="mf-group">
                        <h3>Status</h3>
                        <div class="mf-options compact">
                            <label><span><input type="radio" name="status" value="active" @checked(request('status')==='active')><i></i>Active</span></label>
                            <label><span><input type="radio" name="status" value="preview" @checked(request('status')==='preview')><i></i>Preview</span></label>
                        </div>
                    </div>

                    <div class="mf-group">
                        <h3>Context window</h3>
                        <div class="mf-options compact">
                            @foreach(['128k'=>'128K','200k'=>'200K','256k'=>'256K','1m'=>'1M+'] as $v=>$l)
                                <label><span><input type="radio" name="context" value="{{ $v }}" @checked(request('context')===$v)><i></i>{{ $l }}</span></label>
                            @endforeach
                        </div>
                    </div>

                    <div class="mf-group">
                        <h3>Input price / 1M</h3>
                        <div class="mf-options compact">
                            <label><span><input type="radio" name="price" value="under1" @checked(request('price')==='under1')><i></i>Under $1</span></label>
                            <label><span><input type="radio" name="price" value="under5" @checked(request('price')==='under5')><i></i>Under $5</span></label>
                        </div>
                    </div>

                    @if($capabilities->isNotEmpty())
                    <div class="mf-group">
                        <h3>Capability</h3>
                        <div class="mf-options">
                            @foreach($capabilities->take(10) as $cap)
                                <label><span><input type="radio" name="capability" value="{{ $cap->slug }}" @checked(request('capability')===$cap->slug)><i></i>{{ $cap->name }}</span><small>{{ $cap->models_count }}</small></label>
                            @endforeach
                        </div>
                    </div>
                    @endif

                    <div class="model-filter-actions">
                        <a href="{{ route('models.index') }}">Reset</a>
                        <button class="apply-model-filters" type="submit">Show results</button>
                    </div>
                </form>
            </aside>

            <div class="model-results">
                @if(request()->except('sort','page'))
                    <div class="model-active-filters">
                        <span><i data-lucide="filter"></i> Active filters</span>
                        <div>
                            @foreach(['company'=>'Provider','status'=>'Status','context'=>'Context','price'=>'Price','capability'=>'Capability'] as $key=>$label)
                                @if(request()->filled($key))
                                    <a href="{{ route('models.index', request()->except($key,'page')) }}">{{ $label }}: <strong>{{ request($key) }}</strong><i data-lucide="x"></i></a>
                                @endif
                            @endforeach
                            @if(request()->filled('q'))
                                <a href="{{ route('models.index', request()->except('q','page')) }}">Search: <strong>{{ request('q') }}</strong><i data-lucide="x"></i></a>
                            @endif
                        </div>
                    </div>
                @endif

                @if($models->count())
                    <div class="model-grid" data-model-grid>
                        @foreach($models as $model)
                            @php
                                $caps = collect($model->capabilities ?? [])->take(3);
                            @endphp
                            <article class="model-directory-card">
                                <div class="model-card-header">
                                    <img src="{{ $model->logo_url }}" alt="{{ $model->name }} logo" loading="lazy">
                                    <div class="model-card-title">
                                        <span>{{ $model->company?->name ?? 'Independent' }}</span>
                                        <h3><a class="entity-name-link" href="{{ route('models.show', $model) }}">{{ $model->name }}</a></h3>
                                        <p>{{ $model->version ? 'Version '.$model->version : 'AI foundation model' }}</p>
                                    </div>
                                    <button type="button" class="save-item-btn compact" data-save-item data-save-type="model" data-save-id="{{ $model->id }}" aria-label="Save {{ $model->name }}" aria-pressed="false"><i data-lucide="bookmark"></i></button>
                                </div>

                                <div class="model-card-badges">
                                    <span class="status-pill {{ $model->status }}">{{ ucfirst($model->status) }}</span>
                                    @if($model->benchmark_score)<span class="benchmark-chip"><i data-lucide="trophy"></i>{{ number_format((float)$model->benchmark_score,1) }}</span>@endif
                                </div>

                                <div class="model-benchmark-panel">
                                    <div class="model-benchmark-head">
                                        <span><i data-lucide="gauge"></i> AI Orbit Benchmark</span>
                                        <strong>{{ $model->benchmark_score !== null ? number_format((float)$model->benchmark_score,1) : '—' }}<small>{{ $model->benchmark_score !== null ? '/100' : 'Not verified' }}</small></strong>
                                    </div>
                                    <div class="model-benchmark-track" role="progressbar" aria-label="{{ $model->name }} benchmark score" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ $model->benchmark_score !== null ? min(100,(float)$model->benchmark_score) : 0 }}">
                                        <i style="width:{{ $model->benchmark_score !== null ? min(100,(float)$model->benchmark_score) : 0 }}%"></i>
                                    </div>
                                    <div class="model-benchmark-scale"><span>0</span><span>Performance score</span><span>100</span></div>
                                </div>

                                <div class="model-specs">
                                    <div><i data-lucide="braces"></i><span>Context<strong>{{ $model->context_window ?: '—' }}</strong></span></div>
                                    <div><i data-lucide="arrow-down-to-line"></i><span>Input / 1M<strong>{{ $model->input_price_per_million !== null ? '$'.number_format((float)$model->input_price_per_million,2) : '—' }}</strong></span></div>
                                    <div><i data-lucide="arrow-up-from-line"></i><span>Output / 1M<strong>{{ $model->output_price_per_million !== null ? '$'.number_format((float)$model->output_price_per_million,2) : '—' }}</strong></span></div>
                                </div>

                                <div class="model-caps">
                                    @forelse($caps as $cap)<span>{{ $cap }}</span>@empty<span>AI Model</span>@endforelse
                                </div>

                                <div class="model-directory-card-foot">
                                    <span><i data-lucide="calendar-days"></i>{{ $model->release_date?->format('M Y') ?? 'Release N/A' }}</span>
                                    <a href="{{ route('models.show',$model) }}">{{ \Illuminate\Support\Str::limit($model->name, 28) }} profile <i data-lucide="arrow-right"></i></a>
                                </div>
                            </article>
                        @endforeach
                    </div>

                    @if($models->hasPages())
                    <div class="model-pagination">
                        <nav class="model-pager" aria-label="Model directory pagination">
                            @if($models->onFirstPage())
                                <span class="pager-disabled"><i data-lucide="chevron-left"></i> Previous</span>
                            @else
                                <a href="{{ $models->previousPageUrl() }}"><i data-lucide="chevron-left"></i> Previous</a>
                            @endif

                            <div class="pager-pages">
                                @php
                                    $startPage=max(1,$models->currentPage()-2);
                                    $endPage=min($models->lastPage(),$models->currentPage()+2);
                                @endphp
                                @if($startPage>1)
                                    <a href="{{ $models->url(1) }}">1</a>
                                    @if($startPage>2)<span>…</span>@endif
                                @endif
                                @for($page=$startPage;$page<=$endPage;$page++)
                                    @if($page===$models->currentPage())<span class="active">{{ $page }}</span>@else<a href="{{ $models->url($page) }}">{{ $page }}</a>@endif
                                @endfor
                                @if($endPage<$models->lastPage())
                                    @if($endPage<$models->lastPage()-1)<span>…</span>@endif
                                    <a href="{{ $models->url($models->lastPage()) }}">{{ $models->lastPage() }}</a>
                                @endif
                            </div>

                            @if($models->hasMorePages())
                                <a href="{{ $models->nextPageUrl() }}">Next <i data-lucide="chevron-right"></i></a>
                            @else
                                <span class="pager-disabled">Next <i data-lucide="chevron-right"></i></span>
                            @endif
                        </nav>
                        <p class="pager-summary">Showing {{ $models->firstItem() }}–{{ $models->lastItem() }} of {{ $models->total() }} models</p>
                    </div>
                    @endif
                @else
                    <div class="model-empty">
                        <i data-lucide="search-x"></i>
                        <h3>No models found</h3>
                        <p>Try removing a filter or searching for another provider or model.</p>
                        <a href="{{ route('models.index') }}">Reset directory</a>
                    </div>
                @endif
            </div>
        </div>
    </div>
</section>

@endsection
@push('scripts')<script src="{{ asset('js/frontend/models.js') }}"></script>@endpush
