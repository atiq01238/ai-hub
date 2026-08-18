@extends('frontend.layouts.app')
@section('title','AI Companies Directory — Leading AI Labs & Providers | AI Hub')
@section('meta_description','Explore leading AI companies, research labs and product providers by tools, models, founding year and latest AI activity.')
@push('styles')<link rel="stylesheet" href="{{ asset('css/frontend/companies.css') }}">@endpush
@section('content')
<section class="company-hero"><div class="company-wrap">
    <div class="company-eyebrow"><i data-lucide="building-2"></i> AI COMPANY INTELLIGENCE</div>
    <h1>Explore the companies <span>shaping AI</span></h1>
    <p>Research leading AI labs and product companies through their tools, models, launches and latest intelligence.</p>
    <form class="company-search" method="GET" action="{{ route('companies.index') }}"><i data-lucide="search"></i><input name="q" value="{{ request('q') }}" placeholder="Search OpenAI, Anthropic, Google, Mistral..."><button>Search companies</button></form>
    <div class="company-stats"><div><strong>{{ number_format($stats['companies']) }}</strong><span>Active companies</span></div><div><strong>{{ number_format($stats['tools']) }}</strong><span>Published tools</span></div><div><strong>{{ number_format($stats['models']) }}</strong><span>AI models</span></div><div><strong>{{ number_format($stats['news']) }}</strong><span>News signals</span></div></div>
</div></section>

<section class="company-directory"><div class="company-wrap">
    <div class="company-directory-head"><div><span class="company-kicker">COMPANY DATABASE</span><h2>AI Company Directory</h2><p>{{ number_format($companies->total()) }} companies match your current selection.</p></div><div class="company-directory-actions"><button class="company-filter-button" type="button" data-company-filter-open><i data-lucide="sliders-horizontal"></i> Filters</button><form method="GET">@foreach(request()->except('sort','page') as $k=>$v)<input type="hidden" name="{{ $k }}" value="{{ $v }}">@endforeach<select name="sort" onchange="this.form.submit()"><option value="featured">Featured</option><option value="tools" @selected(request('sort')==='tools')>Most tools</option><option value="models" @selected(request('sort')==='models')>Most models</option><option value="newest" @selected(request('sort')==='newest')>Newest companies</option><option value="name" @selected(request('sort')==='name')>Name A–Z</option></select></form></div></div>

    <div class="company-layout"><div class="company-overlay" data-company-overlay></div>
        <aside class="company-filters" data-company-filters><div class="company-filter-mobile"><strong>Filter companies</strong><button type="button" data-company-filter-close><i data-lucide="x"></i></button></div><form method="GET" action="{{ route('companies.index') }}">@if(request('q'))<input type="hidden" name="q" value="{{ request('q') }}">@endif
            <div class="company-filter-head"><strong><i data-lucide="sliders-horizontal"></i> Filters</strong><a href="{{ route('companies.index') }}">Reset</a></div>
            <div class="company-filter-group"><h3>Status</h3>@foreach(['active'=>'Active','acquired'=>'Acquired','inactive'=>'Inactive'] as $v=>$l)<label><span><input type="radio" name="status" value="{{ $v }}" @checked(request('status')===$v)>{{ $l }}</span></label>@endforeach</div>
            <div class="company-filter-group"><h3>Founded</h3>@foreach(['before2000'=>'Before 2000','2000s'=>'2000–2009','2010s'=>'2010–2019','2020s'=>'2020–2029'] as $v=>$l)<label><span><input type="radio" name="era" value="{{ $v }}" @checked(request('era')===$v)>{{ $l }}</span></label>@endforeach</div>
            <button class="company-apply">Apply filters</button>
        </form></aside>

        <div class="company-results">
            @if($companies->count())<div class="company-grid">@foreach($companies as $company)
                @php $logo=$company->logo_path && file_exists(public_path($company->logo_path)) ? asset($company->logo_path) : asset('favicon.ico'); @endphp
                <article class="company-card"><div class="company-card-top"><img src="{{ $logo }}" alt="{{ $company->name }} logo"><span class="company-status status-{{ $company->status }}">{{ ucfirst($company->status) }}</span></div><div class="company-card-body"><span class="company-founded">{{ $company->founded_year ? 'Founded '.$company->founded_year : 'AI company' }}</span><h3>{{ $company->name }}</h3><p>{{ \Illuminate\Support\Str::limit($company->description ?: 'AI company developing products, models and developer services.', 118) }}</p><div class="company-metrics"><div><strong>{{ $company->published_tools_count }}</strong><span>Tools</span></div><div><strong>{{ $company->active_models_count }}</strong><span>Models</span></div><div><strong>{{ $company->published_news_count }}</strong><span>News</span></div></div></div><div class="company-card-foot"><a href="{{ route('companies.show',$company) }}">View company <i data-lucide="arrow-right"></i></a><button type="button" class="save-item-btn compact" data-save-item data-save-type="company" data-save-id="{{ $company->id }}" aria-label="Save {{ $company->name }}" aria-pressed="false"><i data-lucide="bookmark"></i></button>@if($company->website)<a class="company-web" href="{{ $company->website }}" target="_blank" rel="noopener" aria-label="Visit {{ $company->name }} website"><i data-lucide="external-link"></i></a>@endif</div></article>
            @endforeach</div>
            @else<div class="company-empty"><i data-lucide="building-2"></i><h3>No companies found</h3><p>Try clearing one or more filters.</p><a href="{{ route('companies.index') }}">Reset filters</a></div>@endif

            @if($companies->hasPages())<div class="company-pagination"><a class="{{ $companies->onFirstPage() ? 'disabled' : '' }}" href="{{ $companies->previousPageUrl() ?: '#' }}"><i data-lucide="chevron-left"></i> Previous</a><div>@foreach(range(1,$companies->lastPage()) as $page)<a class="{{ $companies->currentPage()===$page ? 'active' : '' }}" href="{{ $companies->url($page) }}">{{ $page }}</a>@endforeach</div><a class="{{ !$companies->hasMorePages() ? 'disabled' : '' }}" href="{{ $companies->nextPageUrl() ?: '#' }}">Next <i data-lucide="chevron-right"></i></a></div>@endif
        </div>

        <aside class="company-leaders"><div class="company-leader-title"><i data-lucide="trophy"></i><div><strong>Leading AI companies</strong><small>By active models & tools</small></div></div>@foreach($leaders as $i=>$leader)@php $logo=$leader->logo_path && file_exists(public_path($leader->logo_path)) ? asset($leader->logo_path) : asset('favicon.ico'); @endphp<a href="{{ route('companies.show',$leader) }}"><span>#{{ $i+1 }}</span><img src="{{ $logo }}" alt=""><div><strong>{{ $leader->name }}</strong><small>{{ $leader->active_models_count }} models · {{ $leader->published_tools_count }} tools</small></div><i data-lucide="chevron-right"></i></a>@endforeach</aside>
    </div>
</div></section>
@endsection
@push('scripts')<script src="{{ asset('js/frontend/companies.js') }}"></script>@endpush
