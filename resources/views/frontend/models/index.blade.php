@extends('frontend.layouts.app')
@section('title','AI Models Directory — Compare Leading AI Models | AI Hub')
@section('meta_description','Explore and compare leading AI models by provider, context window, API pricing, capabilities and benchmark score.')
@push('styles')<link rel="stylesheet" href="{{ asset('css/frontend/models.css') }}">@endpush
@section('content')
<section class="model-hero"><div class="model-wrap"><div class="model-eyebrow"><i data-lucide="cpu"></i> MODEL INTELLIGENCE</div><h1>Explore the world’s leading <span>AI models</span></h1><p>Compare model capabilities, context windows, API pricing and benchmark performance with one research-focused directory.</p>
<form class="model-search" method="GET" action="{{ route('models.index') }}"><i data-lucide="search"></i><input name="q" value="{{ request('q') }}" placeholder="Search GPT, Claude, Gemini, Mistral, provider..."><button>Search models</button></form>
<div class="model-stats"><div><strong>{{ number_format($stats['models']) }}</strong><span>Active models</span></div><div><strong>{{ number_format($stats['providers']) }}</strong><span>Providers</span></div><div><strong>{{ number_format((float)$stats['topScore'],1) }}</strong><span>Top benchmark</span></div><div><strong>Updated</strong><span>Model intelligence</span></div></div></div></section>
<section class="model-directory"><div class="model-wrap">
<div class="directory-head"><div><span class="section-kicker">MODEL DATABASE</span><h2>AI Model Directory</h2><p>{{ number_format($models->total()) }} models match your current selection.</p></div><div class="directory-actions"><button class="mobile-filter" data-model-filter-open><i data-lucide="sliders-horizontal"></i> Filters</button><form method="GET">@foreach(request()->except('sort','page') as $k=>$v)<input type="hidden" name="{{ $k }}" value="{{ $v }}">@endforeach<select name="sort" onchange="this.form.submit()"><option value="benchmark">Best benchmark</option><option value="newest" @selected(request('sort')==='newest')>Newest</option><option value="price_low" @selected(request('sort')==='price_low')>Lowest input price</option><option value="name" @selected(request('sort')==='name')>Name A–Z</option></select></form><div class="model-view"><button class="active" data-model-view="grid"><i data-lucide="grid-2x2"></i></button><button data-model-view="list"><i data-lucide="list"></i></button></div></div></div>
<div class="model-layout"><div class="model-overlay" data-model-overlay></div><aside class="model-filters" data-model-filters><div class="filter-mobile-title"><strong>Filter models</strong><button data-model-filter-close><i data-lucide="x"></i></button></div><form method="GET" action="{{ route('models.index') }}">@if(request('q'))<input type="hidden" name="q" value="{{ request('q') }}">@endif
<div class="mf-head"><strong><i data-lucide="sliders-horizontal"></i> Filters</strong><a href="{{ route('models.index') }}">Reset</a></div>
<div class="mf-group"><h3>Provider</h3>@foreach($companies as $company)<label><span><input type="radio" name="company" value="{{ $company->slug }}" @checked(request('company')===$company->slug)>{{ $company->name }}</span><small>{{ $company->models_count }}</small></label>@endforeach</div>
<div class="mf-group"><h3>Status</h3><label><span><input type="radio" name="status" value="active" @checked(request('status')==='active')>Active</span></label><label><span><input type="radio" name="status" value="preview" @checked(request('status')==='preview')>Preview</span></label></div>
<div class="mf-group"><h3>Context window</h3>@foreach(['128k'=>'128K','200k'=>'200K','256k'=>'256K','1m'=>'1M'] as $v=>$l)<label><span><input type="radio" name="context" value="{{ $v }}" @checked(request('context')===$v)>{{ $l }}</span></label>@endforeach</div>
<div class="mf-group"><h3>Input price / 1M</h3><label><span><input type="radio" name="price" value="under1" @checked(request('price')==='under1')>Under $1</span></label><label><span><input type="radio" name="price" value="under5" @checked(request('price')==='under5')>Under $5</span></label></div>
<div class="mf-group"><h3>Capability</h3>@foreach($capabilities->take(10) as $cap)<label><span><input type="radio" name="capability" value="{{ $cap }}" @checked(request('capability')===$cap)>{{ $cap }}</span></label>@endforeach</div><button class="apply-model-filters">Apply filters</button></form></aside>
<div class="model-results">
@if(request()->except('sort','page'))<div class="model-active-filters"><span>Filtered results</span><a href="{{ route('models.index') }}">Clear all <i data-lucide="x"></i></a></div>@endif
@if($models->count())<div class="model-grid" data-model-grid>@foreach($models as $model)@php($caps=collect($model->capabilities ?? [])->take(3))<article class="model-directory-card"><div class="model-directory-card-top"><img src="{{ asset($model->logo_path ?: ($model->company?->logo_path ?: 'storage/ai-hub/companies/openai.png')) }}" alt="{{ $model->name }}" loading="lazy"><div><span class="model-provider">{{ $model->company?->name ?? 'Independent' }}</span><h3>{{ $model->name }}</h3><p>{{ $model->version ? 'Version '.$model->version : 'AI foundation model' }}</p></div><span class="status-pill {{ $model->status }}">{{ ucfirst($model->status) }}</span></div><div class="model-score"><span>AI Hub benchmark</span><strong>{{ number_format((float)$model->benchmark_score,1) }}<small>/100</small></strong><div><i style="width:{{ min(100,(float)$model->benchmark_score) }}%"></i></div></div><div class="model-specs"><div><i data-lucide="braces"></i><span>Context<strong>{{ $model->context_window ?: '—' }}</strong></span></div><div><i data-lucide="arrow-down-to-line"></i><span>Input / 1M<strong>{{ $model->input_price_per_million !== null ? '$'.number_format((float)$model->input_price_per_million,2) : '—' }}</strong></span></div><div><i data-lucide="arrow-up-from-line"></i><span>Output / 1M<strong>{{ $model->output_price_per_million !== null ? '$'.number_format((float)$model->output_price_per_million,2) : '—' }}</strong></span></div></div><div class="model-caps">@foreach($caps as $cap)<span>{{ $cap }}</span>@endforeach</div><div class="model-directory-card-foot"><span><i data-lucide="calendar-days"></i>{{ $model->release_date?->format('M Y') ?? 'Release N/A' }}</span><a href="{{ route('models.show',$model) }}">View model <i data-lucide="arrow-right"></i></a></div></article>@endforeach</div><div class="model-pagination">
@if($models->hasPages())
<nav class="model-pager" aria-label="Model directory pagination">
    @if($models->onFirstPage())
        <span class="pager-disabled"><i data-lucide="chevron-left"></i> Previous</span>
    @else
        <a href="{{ $models->previousPageUrl() }}"><i data-lucide="chevron-left"></i> Previous</a>
    @endif
    <div class="pager-pages">
        @foreach(range(1, $models->lastPage()) as $page)
            @if($page === $models->currentPage())
                <span class="active">{{ $page }}</span>
            @else
                <a href="{{ $models->url($page) }}">{{ $page }}</a>
            @endif
        @endforeach
    </div>
    @if($models->hasMorePages())
        <a href="{{ $models->nextPageUrl() }}">Next <i data-lucide="chevron-right"></i></a>
    @else
        <span class="pager-disabled">Next <i data-lucide="chevron-right"></i></span>
    @endif
</nav>
<p class="pager-summary">Showing {{ $models->firstItem() }}–{{ $models->lastItem() }} of {{ $models->total() }} models</p>
@endif
</div>@else<div class="model-empty"><i data-lucide="search-x"></i><h3>No models found</h3><p>Try removing a filter or searching for another provider or model.</p><a href="{{ route('models.index') }}">Reset directory</a></div>@endif
</div><aside class="leader-panel"><div class="leader-head"><i data-lucide="trophy"></i><div><strong>Model leaderboard</strong><span>Overall benchmark</span></div></div>@foreach($leaders as $i=>$leader)<a href="{{ route('models.show',$leader) }}"><b>#{{ $i+1 }}</b><img src="{{ asset($leader->logo_path ?: ($leader->company?->logo_path ?: 'storage/ai-hub/companies/openai.png')) }}" alt=""><span><strong>{{ $leader->name }}</strong><small>{{ $leader->company?->name }}</small></span><em>{{ number_format((float)$leader->benchmark_score,1) }}</em></a>@endforeach<div class="leader-note"><i data-lucide="info"></i>Scores summarize seeded benchmark data for development and should be replaced by verified production measurements.</div></aside></div></div></section>
@endsection
@push('scripts')<script src="{{ asset('js/frontend/models.js') }}"></script>@endpush
