@extends('frontend.layouts.app')

@php
    $benchmarkCanonical = route('benchmarks.show', $benchmark);
    $benchmarkDatasetSchema = array_filter([
        '@' . 'context' => 'https://schema.org',
        '@' . 'type' => 'Dataset',
        'name' => $benchmark->name . ' benchmark results',
        'description' => $description,
        'url' => $benchmarkCanonical,
        'isAccessibleForFree' => true,
        'measurementTechnique' => $benchmark->name,
        'version' => $benchmark->version ?: null,
        'sameAs' => $benchmark->official_url ?: null,
        'dateModified' => $benchmark->updated_at?->toAtomString(),
    ], fn ($value) => $value !== null && $value !== '');

    $benchmarkBreadcrumbSchema = [
        '@' . 'context' => 'https://schema.org',
        '@' . 'type' => 'BreadcrumbList',
        'itemListElement' => [
            ['@' . 'type' => 'ListItem', 'position' => 1, 'name' => 'Home', 'item' => route('home')],
            ['@' . 'type' => 'ListItem', 'position' => 2, 'name' => 'Benchmarks', 'item' => route('benchmarks.index')],
            ['@' . 'type' => 'ListItem', 'position' => 3, 'name' => $benchmark->name, 'item' => $benchmarkCanonical],
        ],
    ];

    $isProductExperience = $benchmark->benchmark_class === \App\Models\Benchmark::CLASS_PRODUCT_EXPERIENCE;
    $isTechnical = $benchmark->benchmark_class === \App\Models\Benchmark::CLASS_TECHNICAL;
    $leaderScore = $benchmarkInsights['leader_score'] ?? null;
@endphp

@section('title', $title . ' | AI Orbit')
@section('meta_description', $description)
@section('canonical', $benchmarkCanonical)
@section('og_type', 'website')
@section('robots', $seoAssessment['robots'] ?? 'noindex,follow')

@push('head')
<script type="application/ld+json">{!! json_encode($benchmarkDatasetSchema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>
<script type="application/ld+json">{!! json_encode($benchmarkBreadcrumbSchema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>
@endpush

@push('styles')
<link rel="stylesheet" href="{{ asset('css/frontend/intelligence.css') }}?v=20260921-adsense-benchmark-v1">
@endpush

@section('content')
<section class="benchmark-detail-hero">
    <div class="benchmark-detail-shell">
        <nav class="benchmark-detail-breadcrumb" aria-label="Breadcrumb">
            <a href="{{ route('home') }}">Home</a><i data-lucide="chevron-right"></i>
            <a href="{{ route('benchmarks.index') }}">Benchmarks</a><i data-lucide="chevron-right"></i>
            <span>{{ $benchmark->name }}</span>
        </nav>
        <div class="benchmark-detail-kicker"><i data-lucide="badge-check"></i> Verified AI benchmark</div>
        <h1>{{ $benchmark->name }} Leaderboard</h1>
        <p>{{ $benchmark->description ?: $description }}</p>
        <div class="benchmark-detail-facts">
            <span><small>Semantic class</small><strong>{{ $benchmark->benchmark_class_label }}</strong></span>
            @if($benchmark->category)<span><small>Category</small><strong>{{ $benchmark->category }}</strong></span>@endif
            <span><small>Direction</small><strong>{{ $benchmark->higher_is_better ? 'Higher is better' : 'Lower is better' }}</strong></span>
            @if($benchmark->version)<span><small>Version</small><strong>{{ $benchmark->version }}</strong></span>@endif
            <span><small>Verified entities</small><strong>{{ number_format($benchmarkInsights['result_count'] ?? 0) }}</strong></span>
        </div>
    </div>
</section>

<div class="benchmark-detail-shell benchmark-detail-page">
    <section class="benchmark-detail-interpretation">
        <div>
            <span class="intel-kicker">How to interpret this leaderboard</span>
            <h2>What this benchmark does—and does not—measure</h2>
            @if($isProductExperience)
                <p>This is a <strong>product-experience signal</strong>. It reflects the published user/reviewer rating represented by the source and is kept separate from technical model benchmarks. A high score here should not be read as proof of stronger reasoning, coding or model capability.</p>
            @elseif($isTechnical)
                <p>This is a <strong>technical-performance benchmark</strong>. Compare scores only within this benchmark definition and version; AI Orbit does not combine it with product-experience or otherwise incompatible semantic classes.</p>
            @else
                <p>This leaderboard preserves the benchmark's own metric, source and test date. Results are comparable only within the same benchmark definition and semantic class; missing data is never treated as a zero.</p>
            @endif
        </div>
        <div class="benchmark-detail-method-card">
            <h3>Method & source</h3>
            <dl>
                <div><dt>Unit</dt><dd>{{ $benchmark->unit ?: 'score' }}</dd></div>
                <div><dt>Results</dt><dd>{{ number_format($benchmarkInsights['result_count'] ?? 0) }}</dd></div>
                @if($benchmarkInsights['latest_tested_at'] ?? null)<div><dt>Latest test</dt><dd>{{ $benchmarkInsights['latest_tested_at']->format('M j, Y') }}</dd></div>@endif
                @if(($benchmarkInsights['source_count'] ?? 0) > 0)<div><dt>Distinct source links</dt><dd>{{ number_format($benchmarkInsights['source_count']) }}</dd></div>@endif
            </dl>
            @if($benchmark->methodology_url || $benchmark->official_url)
                <a href="{{ $benchmark->methodology_url ?: $benchmark->official_url }}" rel="nofollow noopener" target="_blank">Open official methodology / source <i data-lucide="arrow-up-right"></i></a>
            @endif
        </div>
    </section>

    @if(($benchmarkInsights['result_count'] ?? 0) > 0)
    <section class="benchmark-detail-summary" aria-label="Benchmark summary">
        <article><span><i data-lucide="trophy"></i></span><div><small>Leading score</small><strong>{{ $leaderScore !== null ? number_format((float)$leaderScore, 2) : '—' }}{{ $benchmark->unit ? ' '.$benchmark->unit : '' }}</strong></div></article>
        <article><span><i data-lucide="users"></i></span><div><small>Entities at top score</small><strong>{{ number_format($benchmarkInsights['leader_count'] ?? 0) }}</strong></div></article>
        <article><span><i data-lucide="calendar-check"></i></span><div><small>Evidence freshness</small><strong>{{ ($benchmarkInsights['latest_tested_at'] ?? null)?->format('M j, Y') ?? 'Not recorded' }}</strong></div></article>
    </section>
    @endif

    <section class="benchmark-detail-results">
        <div class="benchmark-detail-section-head">
            <div><span class="intel-kicker">Verified results</span><h2>{{ $benchmark->name }} rankings</h2><p>Only the latest verified result for each listed entity is shown. Equal scores receive the same displayed rank.</p></div>
        </div>

        @if($results->isEmpty())
            <div class="benchmark-detail-empty"><i data-lucide="database-zap"></i><div><h3>No verified results published yet</h3><p>This benchmark remains public for methodology reference, but AI Orbit does not invent placeholder rankings.</p></div></div>
        @else
            <div class="benchmark-detail-table-wrap">
                <table class="benchmark-detail-table">
                    <thead><tr><th>Rank</th><th>Model / Tool</th><th>Score</th><th>Tested</th><th>Evidence</th></tr></thead>
                    <tbody>
                    @foreach($results as $result)
                        @php
                            $entity = $result->benchmarkable;
                            $entityUrl = null;
                            if ($entity instanceof \App\Models\AiModel && in_array($entity->status, ['active','preview'], true)) {
                                $entityUrl = route('models.show', $entity);
                            } elseif ($entity instanceof \App\Models\Tool && $entity->status === 'published') {
                                $entityUrl = route('tools.show', $entity);
                            }
                            $isTiedLeader = (int)($result->display_rank ?? 0) === 1 && ($benchmarkInsights['leader_count'] ?? 0) > 1;
                        @endphp
                        <tr>
                            <td><span class="benchmark-rank">#{{ $result->display_rank ?? $loop->iteration }}</span>@if($isTiedLeader)<small class="benchmark-tie">Tied</small>@endif</td>
                            <td><strong>@if($entityUrl)<a href="{{ $entityUrl }}">{{ $entity?->name ?? 'Unavailable' }} <i data-lucide="arrow-up-right"></i></a>@else{{ $entity?->name ?? 'Unavailable' }}@endif</strong></td>
                            <td><b>{{ number_format((float)$result->score, 2) }}</b>@if($benchmark->unit)<small>{{ $benchmark->unit }}</small>@endif</td>
                            <td>{{ $result->tested_at?->format('M j, Y') ?? 'Not recorded' }}</td>
                            <td>@if($result->source_url)<a rel="nofollow noopener" target="_blank" href="{{ $result->source_url }}">{{ $result->source_name ?: 'Open source' }} <i data-lucide="external-link"></i></a>@else<span>{{ $result->source_name ?: 'Stored verified result' }}</span>@endif</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>

    <section class="benchmark-detail-notes">
        <article>
            <span class="intel-kicker">Limitations</span>
            <h2>What to keep in mind</h2>
            <p>A benchmark is one evidence signal, not a universal product verdict. Test conditions, benchmark versions, source methodology and date can all affect interpretation. Compare shared benchmarks and current product evidence before making a model or tool decision.</p>
        </article>
        <article>
            <span class="intel-kicker">Freshness</span>
            <h2>Why dates and sources are shown</h2>
            <p>AI Orbit retains the source and tested date for each verified result so readers can judge whether evidence is current enough for their use case. Older evidence is not silently presented as a new test.</p>
        </article>
    </section>

    @if($relatedBenchmarks->isNotEmpty())
    <section class="benchmark-detail-related">
        <div class="benchmark-detail-section-head"><div><span class="intel-kicker">Keep researching</span><h2>Related {{ $benchmark->benchmark_class_label }} benchmarks</h2></div><a href="{{ route('benchmarks.index') }}">All benchmarks <i data-lucide="arrow-right"></i></a></div>
        <div class="benchmark-related-grid">
            @foreach($relatedBenchmarks as $related)
                <a href="{{ route('benchmarks.show', $related) }}"><span>{{ $related->category ?: $related->benchmark_class_label }}</span><strong>{{ $related->name }}</strong><small>{{ number_format($related->verified_results_count) }} verified result{{ $related->verified_results_count === 1 ? '' : 's' }}</small><i data-lucide="arrow-up-right"></i></a>
            @endforeach
        </div>
    </section>
    @endif
</div>
@endsection
