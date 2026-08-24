@extends('frontend.layouts.app')
@section('title','AI Test Lab — Independent AI Model Tests | AI Orbit')
@section('meta_description','Explore controlled AI model experiments with locked prompts, test-specific rubrics, multi-run scoring, evidence and transparent verification.')
@push('head')
<script type="application/ld+json">{!! json_encode(['@context'=>'https://schema.org','@type'=>'CollectionPage','name'=>'AI Test Lab','description'=>'Independent AI model experiments with shared prompts, transparent scoring and evidence.','url'=>route('testlab.index')], JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE) !!}</script>
@endpush
@push('styles')<link rel="stylesheet" href="{{ asset('css/frontend/testlab.css') }}">@endpush
@section('content')
<section class="lab-hero"><div class="lab-wrap"><div class="lab-hero-grid">
    <div class="lab-hero-copy"><span class="lab-eyebrow"><i data-lucide="flask-conical"></i> INDEPENDENT AI TESTING</span><h1>Same task. Same rules. <span>Visible evidence.</span></h1><p>Compare AI models on controlled experiments with locked prompts, test-specific rubrics, optional multi-run verification, evidence and transparent result metadata.</p><form class="lab-search" method="GET"><i data-lucide="search"></i><input name="q" value="{{ request('q') }}" placeholder="Search tests, prompts, use cases..."><button>Search tests</button></form><div class="lab-hero-links"><a href="{{ route('testlab.leaderboard') }}"><i data-lucide="trophy"></i>Model leaderboard</a><a href="{{ route('methodology') }}#testlab"><i data-lucide="book-open-check"></i>Testing methodology</a></div></div>
    <div class="lab-hero-panel"><div class="lab-signal"><span>PUBLIC LAB INDEX</span><b>{{ number_format($stats['results']) }}</b><small>complete model results</small></div><div class="lab-mini-stats"><div><b>{{ $stats['tests'] }}</b><span>Tests</span></div><div><b>{{ $stats['models'] }}</b><span>Models</span></div><div><b>{{ $stats['verified_results'] }}</b><span>Verified</span></div><div><b>{{ $stats['categories'] }}</b><span>Categories</span></div></div></div>
</div></div></section>

<section class="lab-directory"><div class="lab-wrap">
    <div class="lab-toolbar"><div><span>RESEARCH LIBRARY</span><h2>Controlled experiments</h2><p>{{ number_format($tests->total()) }} published tests</p></div><div class="lab-actions"><button type="button" data-lab-filter-open><i data-lucide="sliders-horizontal"></i> Filters</button><form method="GET">@foreach(request()->except('sort','page') as $k=>$v)<input type="hidden" name="{{ $k }}" value="{{ $v }}">@endforeach<select name="sort" onchange="this.form.submit()"><option value="newest">Newest</option><option value="score" @selected(request('sort')==='score')>Highest top score</option><option value="models" @selected(request('sort')==='models')>Most models</option><option value="name" @selected(request('sort')==='name')>Name A–Z</option></select></form></div></div>

    <div class="lab-layout"><div class="lab-overlay" data-lab-overlay></div>
        <aside class="lab-filters" data-lab-filters><div class="lab-filter-mobile"><b>Filter experiments</b><button data-lab-filter-close><i data-lucide="x"></i></button></div><form method="GET">@if(request('q'))<input type="hidden" name="q" value="{{ request('q') }}">@endif<div class="lab-filter-head"><b><i data-lucide="filter"></i> Filters</b><a href="{{ route('testlab.index') }}">Reset</a></div>
            <div class="lab-filter-group"><h3>Test category</h3><label><span><input type="radio" name="category" value="" @checked(!request('category'))>All tests</span><b>{{ $stats['tests'] }}</b></label>@foreach($categories as $cat=>$count)<label><span><input type="radio" name="category" value="{{ $cat }}" @checked(request('category')===$cat)>{{ $cat }}</span><b>{{ $count }}</b></label>@endforeach</div>
            <div class="lab-filter-group"><h3>Difficulty</h3><label><span><input type="radio" name="difficulty" value="" @checked(!request('difficulty'))>Any difficulty</span></label>@foreach($difficulties as $value=>$label)<label><span><input type="radio" name="difficulty" value="{{ $value }}" @checked(request('difficulty')===$value)>{{ $label }}</span></label>@endforeach</div>
            <div class="lab-filter-group"><h3>Trust</h3><label><span><input type="checkbox" name="verified" value="1" @checked(request('verified')==='1')>Verified methodology only</span></label></div>
            <button class="lab-apply">Apply filters</button>
        </form><div class="lab-method-card"><i data-lucide="shield-check"></i><h3>Transparent scoring</h3><p>Every published test exposes its locked prompt, rubric weights, completed run aggregates and verification state. Missing criteria are never replaced with neutral filler scores.</p></div></aside>

        <div class="lab-results">
            @forelse($tests as $test)
                @php($ranked=$test->completedResults->sortByDesc('overall_score')->values())
                <article class="lab-test-card {{ $test->is_featured ? 'featured' : '' }}">
                    <div class="lab-card-top"><div class="lab-card-badges"><span class="lab-category">{{ $test->testTypeLabel() }}</span><span>{{ $test->runModeLabel() }} · {{ $test->required_runs }}×</span><span>{{ $difficulties[$test->difficulty] ?? ucfirst($test->difficulty) }}</span>@if($test->is_verified)<span class="verified"><i data-lucide="badge-check"></i>Verified</span>@endif</div><span class="lab-model-count"><i data-lucide="layers-3"></i>{{ $test->results_count }} models</span></div>
                    <h3><a href="{{ route('testlab.show',$test) }}">{{ $test->name }}</a></h3>
                    <p class="lab-summary">{{ $test->short_description ?: \Illuminate\Support\Str::limit($test->prompt,150) }}</p>
                    <div class="lab-taxonomy">@if($test->feature)<a href="{{ route('features.show',$test->feature) }}"><i data-lucide="sparkles"></i>{{ $test->feature->name }}</a>@endif @if($test->useCase)<a href="{{ route('use-cases.show',$test->useCase) }}"><i data-lucide="target"></i>{{ $test->useCase->name }}</a>@endif</div>
                    <div class="lab-prompt"><i data-lucide="terminal-square"></i><span>{{ \Illuminate\Support\Str::limit($test->prompt,125) }}</span></div>
                    @if($ranked->isNotEmpty())<div class="lab-podium">@foreach($ranked->take(3) as $i=>$result)<div><span>#{{ $i+1 }}</span><img src="{{ $result->model?->logo_url ?: asset('favicon.ico') }}" alt=""><b>{{ $result->model?->name ?? 'Model' }}</b><strong>{{ number_format((float)$result->overall_score,1) }}</strong>@if($result->is_verified)<i data-lucide="badge-check"></i>@endif</div>@endforeach</div>@endif
                    <div class="lab-card-foot"><span><i data-lucide="calendar-days"></i>{{ ($test->published_at ?: $test->created_at)->format('M j, Y') }}</span><a href="{{ route('testlab.show',$test) }}">View evidence <i data-lucide="arrow-right"></i></a></div>
                </article>
            @empty<div class="lab-empty"><i data-lucide="flask-conical-off"></i><h3>No published tests found</h3><p>Try clearing your search or filters.</p></div>@endforelse
            @if($tests->hasPages())<div class="lab-pagination">{{ $tests->links() }}</div>@endif
        </div>

        <aside class="lab-side"><div class="lab-side-card"><div class="lab-side-title"><div><span class="side-kicker">MODEL LEADERBOARD</span><h3>Best in the lab</h3></div><a href="{{ route('testlab.leaderboard') }}">All</a></div>@forelse($leaderboard as $i=>$model)<a class="lab-leader-row" href="{{ route('models.show',$model) }}"><span class="lab-rank">{{ $i+1 }}</span><img src="{{ $model->logo_url }}" alt=""><span class="lab-model-name"><b>{{ $model->name }}</b><small>{{ $model->lab_tests }} tests · {{ $model->verified_lab_tests }} verified</small></span><strong>{{ number_format((float)$model->lab_average,1) }}</strong></a>@empty<p class="lab-side-empty">No completed results yet.</p>@endforelse</div>
        <div class="lab-side-card"><span class="side-kicker">HOW A TEST BECOMES PUBLIC</span><div class="lab-step"><b>01</b><p><strong>Lock</strong><span>One prompt and one test-specific 100% rubric.</span></p></div><div class="lab-step"><b>02</b><p><strong>Run</strong><span>Each model receives the exact same locked task.</span></p></div><div class="lab-step"><b>03</b><p><strong>Review</strong><span>Score applicable criteria; irrelevant evidence is explicit N/A.</span></p></div><div class="lab-step"><b>04</b><p><strong>Publish</strong><span>Only complete model aggregates enter the public ranking.</span></p></div></div></aside>
    </div>
</div></section>
@endsection
@push('scripts')<script src="{{ asset('js/frontend/testlab.js') }}"></script>@endpush
