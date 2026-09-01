@extends('frontend.layouts.app')

@php
    $benchmarksHasFilters = request()->hasAny([
        'type',
        'category',
        'verified',
        'class',
    ]);

    $benchmarksSeoTitle = 'AI Benchmarks and Leaderboards | AI Orbit';
    $benchmarksSeoDescription = 'Compare verified AI model and AI tool benchmark results across reasoning, coding, product quality and more.';
    $benchmarksCanonical = route('benchmarks.index');

    $benchmarksCollectionSchema = [
        '@' . 'context' => 'https://schema.org',
        '@' . 'type' => 'CollectionPage',
        'name' => 'AI Benchmarks and Leaderboards',
        'description' => $benchmarksSeoDescription,
        'url' => $benchmarksCanonical,
    ];

    $benchmarksBreadcrumbSchema = [
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
                'name' => 'AI Benchmarks',
                'item' => $benchmarksCanonical,
            ],
        ],
    ];
@endphp

@section('title', $benchmarksSeoTitle)
@section('meta_description', $benchmarksSeoDescription)
@section('canonical', $benchmarksCanonical)

@section(
    'robots',
    $benchmarksHasFilters
        ? 'noindex,follow'
        : 'index,follow,max-image-preview:large,max-snippet:-1,max-video-preview:-1'
)

@push('head')
<script type="application/ld+json">{!! json_encode(
    $benchmarksCollectionSchema,
    JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
) !!}</script>
<script type="application/ld+json">{!! json_encode(
    $benchmarksBreadcrumbSchema,
    JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
) !!}</script>
@endpush

@push('styles')
<link rel="stylesheet" href="{{ asset('css/frontend/intelligence.css') }}">
@endpush

@section('content')
<section class="intel-hero benchmark-hero">
    <div class="intel-grid-bg"></div>
    <div class="intel-hero-inner">
        <span class="intel-eyebrow"><i data-lucide="bar-chart-3"></i> Independent performance intelligence</span>
        <h1>AI Benchmarks <span>& Leaderboards</span></h1>
        <p>Compare AI models and tools using structured benchmark results, source verification and weighted performance scoring — without reducing every product to a single unexplained number.</p>
        <div class="intel-stat-row">
            <div><strong>{{ number_format($stats['benchmark_count']) }}</strong><span>Active benchmarks</span></div>
            <div><strong>{{ number_format($stats['verified_results']) }}</strong><span>Verified results</span></div>
            <div><strong>{{ number_format($stats['model_count']) }}</strong><span>Ranked models</span></div>
            <div><strong>{{ number_format($stats['tool_count']) }}</strong><span>Ranked tools</span></div>
        </div>
    </div>
</section>

<div class="intel-page">
    <form class="intel-toolbar" method="get" action="{{ route('benchmarks.index') }}">
        <div class="intel-tabs" aria-label="Benchmark type">
            @foreach(['all'=>'All results','models'=>'AI Models','tools'=>'AI Tools'] as $value=>$label)
                <a class="{{ $type===$value ? 'active' : '' }}" href="{{ route('benchmarks.index', array_filter(['type'=>$value,'category'=>$category,'class'=>$benchmarkClass,'verified'=>$verifiedOnly?1:0], fn($v)=>$v!==''&&$v!==null)) }}">{{ $label }}</a>
            @endforeach
        </div>
        <div class="intel-filter-fields">
            <label><span>Semantic class</span><select name="class" onchange="this.form.submit()"><option value="">All classes</option>@foreach($benchmarkClasses as $value=>$label)<option value="{{ $value }}" @selected($benchmarkClass===$value)>{{ $label }}</option>@endforeach</select></label>
            <label><span>Category</span><select name="category" onchange="this.form.submit()"><option value="">All categories</option>@foreach($categories as $item)<option value="{{ $item }}" @selected($category===$item)>{{ $item }}</option>@endforeach</select></label>
            <label class="verify-toggle"><input type="hidden" name="verified" value="0"><input type="checkbox" name="verified" value="1" @checked($verifiedOnly) onchange="this.form.submit()"><span><i data-lucide="badge-check"></i> Verified only</span></label>
            <input type="hidden" name="type" value="{{ $type }}">
        </div>
    </form>

    @if($modelLeaderboard->isNotEmpty() && in_array($type,['all','models']))
    <section class="intel-section leaderboard-section">
        <div class="intel-section-head">
            <div><span class="intel-kicker"><i data-lucide="trophy"></i> {{ $leaderboardClassLabel }} composite</span><h2>Top AI Models</h2><p>Weighted score using only {{ strtolower($leaderboardClassLabel) }} benchmarks. Incompatible benchmark classes are never mixed.</p></div>
            <a href="{{ route('methodology') }}#models">Benchmark Methodology <i data-lucide="arrow-up-right"></i></a>
        </div>
        <div class="podium-grid">
            @foreach($modelLeaderboard->take(3) as $rank=>$row)
                @php($model=$row['entity'])
                <a class="podium-card rank-{{ $rank+1 }}" href="{{ route('models.show',$model) }}">
                    <span class="rank-medal">#{{ $rank+1 }}</span>
                    <img src="{{ $model->logo_url }}" alt="{{ $model->name }} logo">
                    <small>{{ $model->company?->name ?? 'AI Model' }}</small>
                    <h3>{{ $model->name }}</h3>
                    <strong>{{ number_format((float)$row['score'],1) }}</strong>
                    <span>{{ $row['result_count'] }} benchmark{{ $row['result_count']===1?'':'s' }} · {{ $row['verified_count'] }} verified</span>
                    <div class="score-track"><i style="width:{{ min(100,max(0,(float)$row['score'])) }}%"></i></div>
                </a>
            @endforeach
        </div>
        @if($modelLeaderboard->count()>3)
        <div class="ranking-table-wrap"><table class="ranking-table"><thead><tr><th>Rank</th><th>Model</th><th>Provider</th><th>Coverage</th><th>Verified</th><th>{{ $leaderboardClassLabel }} composite</th></tr></thead><tbody>
            @foreach($modelLeaderboard->slice(3,7) as $index=>$row)
                @php($model=$row['entity'])
                <tr><td><b>#{{ $index+4 }}</b></td><td><a class="rank-entity" href="{{ route('models.show',$model) }}"><img src="{{ $model->logo_url }}" alt=""><span>{{ $model->name }}<small>{{ $model->version ?: 'Current' }}</small></span></a></td><td>{{ $model->company?->name ?? '—' }}</td><td>{{ $row['result_count'] }} tests</td><td><span class="verified-pill"><i data-lucide="check"></i>{{ $row['verified_count'] }}</span></td><td><strong>{{ number_format((float)$row['score'],1) }}</strong></td></tr>
            @endforeach
        </tbody></table></div>
        @endif
    </section>
    @endif

    @if($toolLeaderboard->isNotEmpty() && in_array($type,['all','tools']))
    <section class="intel-section tool-rank-section">
        <div class="intel-section-head"><div><span class="intel-kicker"><i data-lucide="bot"></i> {{ $leaderboardClassLabel }}</span><h2>Top AI Tools</h2><p>This leaderboard combines only {{ strtolower($leaderboardClassLabel) }} results; review ratings and technical tests are kept separate.</p></div><a href="{{ route('tools.index') }}">AI Tools directory <i data-lucide="arrow-right"></i></a></div>
        <div class="compact-rank-grid">
            @foreach($toolLeaderboard->take(8) as $rank=>$row)
                @php($tool=$row['entity'])
                <a href="{{ route('tools.show',$tool) }}" class="compact-rank-card"><b>#{{ $rank+1 }}</b><img src="{{ $tool->logo_url }}" alt="{{ $tool->name }} logo"><div><small>{{ $tool->category?->name ?? $tool->company?->name }}</small><h3>{{ $tool->name }}</h3><span>{{ $row['result_count'] }} benchmark results</span></div><strong>{{ number_format((float)$row['score'],1) }}</strong></a>
            @endforeach
        </div>
    </section>
    @endif

    <section class="intel-section benchmark-catalog-section">
        <div class="intel-section-head"><div><span class="intel-kicker"><i data-lucide="scan-search"></i> Benchmark explorer</span><h2>Results by Benchmark</h2><p>See the leaderboard for each individual benchmark instead of relying only on an aggregate score.</p></div><span class="freshness"><i data-lucide="clock-3"></i> Latest test: {{ $stats['latest_tested_at'] ? \Illuminate\Support\Carbon::parse($stats['latest_tested_at'])->format('M j, Y') : 'Not recorded' }}</span></div>
        @if($benchmarks->isEmpty())
            <div class="intel-empty"><i data-lucide="bar-chart-3"></i><h3>No benchmark results match these filters.</h3><p>Try another category, include unverified results, or seed the benchmark validation dataset.</p></div>
        @else
            <div class="benchmark-card-grid">
                @foreach($benchmarks as $row)
                    @php($benchmark=$row['benchmark'])
                    <article class="benchmark-card">
                        <div class="benchmark-card-head"><div><small>{{ $benchmark->benchmark_class_label }} · {{ $benchmark->category }}</small><h3><a href="{{ route('benchmarks.show',$benchmark) }}">{{ $benchmark->name }}</a></h3></div><span>{{ $benchmark->higher_is_better ? 'Higher is better' : 'Lower is better' }}</span></div>
                        <p>{{ $benchmark->description ?: 'Structured performance benchmark tracked by AI Orbit.' }}</p>
                        <div class="mini-ranking">
                            @foreach($row['results']->take(5) as $rank=>$result)
                                @php($entity=$result->benchmarkable)
                                <div><b>#{{ $rank+1 }}</b><span>{{ $entity?->name ?? 'Unknown' }}<small>{{ class_basename($result->benchmarkable_type) === 'AiModel' ? 'AI Model' : 'AI Tool' }} @if($result->verified) · Verified @endif</small></span><strong>{{ number_format((float)$result->score,1) }}</strong></div>
                            @endforeach
                        </div>
                        <footer><span>{{ $benchmark->benchmark_class_label }}</span><span>Max score {{ number_format((float)$benchmark->max_score,0) }}</span><span>Weight {{ number_format((float)$benchmark->weight,2) }}×</span><a href="{{ route('benchmarks.discussion',$benchmark) }}"><i data-lucide="messages-square"></i> Discussion</a></footer>
                    </article>
                @endforeach
            </div>
        @endif
    </section>

    <section class="methodology-banner"><span><i data-lucide="shield-check"></i></span><div><small>How to read these rankings</small><h2>Benchmark scores are evidence, not the whole product.</h2><p>AI Orbit keeps individual results, benchmark coverage and verification visible so a single leaderboard number never hides the underlying data.</p></div><a href="{{ route('methodology') }}#models">View benchmark methodology <i data-lucide="arrow-right"></i></a></section>
</div>
@endsection
