@extends('layouts.admin')
@section('title', 'Comparison Metrics')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/pages/comparison-benchmarks.css') }}">
@endpush

@section('content')
<div class="cb-page">
    <x-page-header
        title="Comparison Metrics"
        subtitle="Operational performance and publishing health across the comparison library."
        :breadcrumb="['Comparison & Benchmarks', 'Comparison Metrics']"
    >
        <x-slot:actions>
            <a href="{{ route('admin.comparisons.index') }}" class="btn btn-secondary"><i data-lucide="library"></i>Library</a>
            @if(auth()->user()->canAccessModule('Comparisons', 'Add'))
                <a href="{{ route('admin.comparisons.builder') }}" class="btn btn-primary"><i data-lucide="plus"></i>New Comparison</a>
            @endif
        </x-slot:actions>
    </x-page-header>

    <section class="cb-metrics-grid">
        @foreach([
            ['label'=>'Total comparisons','value'=>$total,'icon'=>'git-compare-arrows','tone'=>''],
            ['label'=>'Published','value'=>$published,'icon'=>'circle-check','tone'=>'green'],
            ['label'=>'Drafts','value'=>$drafts,'icon'=>'file-clock','tone'=>'amber'],
            ['label'=>'Total views','value'=>$totalViews,'icon'=>'eye','tone'=>'cyan'],
            ['label'=>'Avg. views','value'=>$avgViews,'icon'=>'chart-no-axes-column-increasing','tone'=>'violet'],
            ['label'=>'Tool comparisons','value'=>$toolComparisons,'icon'=>'wrench','tone'=>''],
            ['label'=>'Model comparisons','value'=>$modelComparisons,'icon'=>'brain-circuit','tone'=>'cyan'],
        ] as $metric)
            <article class="card cb-metric-card cb-metric-card--{{ $metric['tone'] }}">
                <span><i data-lucide="{{ $metric['icon'] }}"></i></span>
                <div><small>{{ $metric['label'] }}</small><strong>{{ is_numeric($metric['value']) ? number_format($metric['value'], is_float($metric['value']) ? 1 : 0) : $metric['value'] }}</strong></div>
            </article>
        @endforeach
    </section>

    <div class="cb-metrics-layout">
        <section class="card cb-metric-list">
            <div class="cb-section-head">
                <div><span class="cb-eyebrow">Engagement</span><h2>Top comparisons</h2><p>Highest-viewed decision pages in the library.</p></div>
                <i data-lucide="trending-up"></i>
            </div>
            <div class="cb-metric-list__body">
                @forelse($topComparisons as $index => $item)
                    <a class="cb-ranking-row" href="{{ route('admin.comparisons.show', $item->id) }}">
                        <span class="cb-ranking-row__rank">{{ $index + 1 }}</span>
                        <div><strong>{{ $item->title }}</strong><small>{{ ucfirst($item->comparable_type) }} comparison · {{ ucfirst($item->status) }}</small></div>
                        <span class="cb-ranking-row__value"><i data-lucide="eye"></i>{{ number_format($item->views) }}</span>
                    </a>
                @empty
                    <div class="cb-empty cb-empty--small"><p>No comparison engagement yet.</p></div>
                @endforelse
            </div>
        </section>

        <section class="card cb-metric-list">
            <div class="cb-section-head">
                <div><span class="cb-eyebrow">Freshness</span><h2>Recently updated</h2><p>Latest comparison records by modification time.</p></div>
                <i data-lucide="clock-3"></i>
            </div>
            <div class="cb-metric-list__body">
                @forelse($recentComparisons as $item)
                    <a class="cb-recent-row" href="{{ route('admin.comparisons.show', $item->id) }}">
                        <span class="cb-record__icon"><i data-lucide="{{ $item->comparable_type === 'tool' ? 'wrench' : 'brain-circuit' }}"></i></span>
                        <div><strong>{{ $item->title }}</strong><small>{{ $item->updated_at->diffForHumans() }}</small></div>
                        <x-status-badge status="{{ ucfirst($item->status) }}" type="{{ $item->status === 'published' ? 'pos' : 'neutral' }}" />
                    </a>
                @empty
                    <div class="cb-empty cb-empty--small"><p>No comparisons yet.</p></div>
                @endforelse
            </div>
        </section>
    </div>
</div>
@endsection
