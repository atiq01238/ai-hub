@extends('layouts.admin')
@section('title', 'Benchmarks')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/pages/comparison-benchmarks.css') }}">
@endpush

@section('content')
<div class="cb-page">
    <x-page-header
        title="Benchmark Rankings"
        subtitle="Compare AI model and tool performance across recorded benchmark datasets."
        :breadcrumb="['Comparison & Benchmarks', 'Benchmarks']"
    >
        <x-slot:actions>
            <a href="{{ route('admin.benchmarks.results') }}" class="btn btn-secondary"><i data-lucide="history"></i>History</a>
            @if(auth()->user()->canAccessModule('Benchmarks', 'Add'))
                <a href="{{ route('admin.benchmarks.create') }}" class="btn btn-primary"><i data-lucide="plus"></i>Add Result</a>
            @endif
        </x-slot:actions>
    </x-page-header>

    @if(session('status'))
        <div class="alert alert-success cb-flash"><i data-lucide="check-circle-2"></i><span>{{ session('status') }}</span></div>
    @endif

    <section class="card cb-benchmark-filter">
        <div class="cb-benchmark-filter__row">
            <span class="cb-filter-label">Entity type</span>
            <a class="cb-chip {{ $type === 'model' ? 'is-active' : '' }}" href="{{ route('admin.benchmarks.index', ['type'=>'model','benchmark'=>$selected]) }}">
                <i data-lucide="brain-circuit"></i>AI Models
            </a>
            <a class="cb-chip {{ $type === 'tool' ? 'is-active' : '' }}" href="{{ route('admin.benchmarks.index', ['type'=>'tool','benchmark'=>$selected]) }}">
                <i data-lucide="wrench"></i>AI Tools
            </a>
        </div>

        <div class="cb-benchmark-filter__row">
            <span class="cb-filter-label">Benchmark</span>
            <div class="cb-chip-scroll">
                @foreach($benchmarks as $benchmark)
                    <a class="cb-chip {{ $selected === $benchmark ? 'is-active' : '' }}" href="{{ route('admin.benchmarks.index', ['type'=>$type,'benchmark'=>$benchmark]) }}">
                        {{ $benchmark }}
                    </a>
                @endforeach
            </div>
        </div>
    </section>

    <div class="cb-benchmark-layout">
        <section class="card cb-ranking">
            <div class="cb-section-head">
                <div>
                    <span class="cb-eyebrow">Leaderboard</span>
                    <h2>{{ $selected }}</h2>
                    <p>Ranking by the latest score stored in each {{ $type === 'model' ? 'model' : 'tool' }} record.</p>
                </div>
                <span class="cb-count-pill">{{ $rankings->count() }} ranked</span>
            </div>

            <div class="cb-ranking__body">
                @forelse($rankings as $index => $item)
                    @php $score = (float)($item->benchmarks[$selected] ?? 0); @endphp
                    <article class="cb-leader-row {{ $index === 0 ? 'is-first' : '' }}">
                        <span class="cb-leader-row__rank">
                            @if($index === 0)<i data-lucide="trophy"></i>@else{{ $index + 1 }}@endif
                        </span>
                        <span class="cb-leader-row__icon"><i data-lucide="{{ $type === 'model' ? 'brain-circuit' : 'wrench' }}"></i></span>
                        <div class="cb-leader-row__copy">
                            <a href="{{ $type === 'model' ? route('admin.models.show', $item->id) : route('admin.tools.show', $item->id) }}">{{ $item->name }}</a>
                            <small>{{ $item->company?->name ?? 'Company not linked' }}</small>
                        </div>
                        <div class="cb-leader-row__score">
                            <strong>{{ rtrim(rtrim(number_format($score, 2, '.', ''), '0'), '.') }}</strong>
                            <div><span style="width: {{ min(100,max(0,$score)) }}%"></span></div>
                        </div>
                    </article>
                @empty
                    <div class="cb-empty">
                        <span><i data-lucide="gauge"></i></span>
                        <h3>No scores recorded</h3>
                        <p>No {{ $type }} has a {{ $selected }} score yet.</p>
                        @if(auth()->user()->canAccessModule('Benchmarks', 'Add'))
                            <a class="btn btn-primary" href="{{ route('admin.benchmarks.create') }}"><i data-lucide="plus"></i>Add Benchmark Result</a>
                        @endif
                    </div>
                @endforelse
            </div>
        </section>

        <aside class="card cb-benchmark-aside">
            <span class="cb-eyebrow">Benchmark context</span>
            <div class="cb-benchmark-aside__icon"><i data-lucide="gauge"></i></div>
            <h3>{{ $selected }}</h3>
            <p>Scores are normalized on the stored 0–100 scale. Ranking uses the score saved in the current item benchmark breakdown.</p>
            <dl>
                <div><dt>Entity</dt><dd>{{ $type === 'model' ? 'AI Models' : 'AI Tools' }}</dd></div>
                <div><dt>Ranked items</dt><dd>{{ $rankings->count() }}</dd></div>
                <div><dt>Available tests</dt><dd>{{ count($benchmarks) }}</dd></div>
            </dl>
            <a href="{{ route('admin.benchmarks.results', ['type'=>$type]) }}" class="cb-text-link">Open evidence history <i data-lucide="arrow-right"></i></a>
        </aside>
    </div>
</div>
@endsection
