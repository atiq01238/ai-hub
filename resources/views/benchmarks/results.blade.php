@extends('layouts.admin')
@section('title', 'Benchmark Results')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/pages/comparison-benchmarks.css') }}">
@endpush

@section('content')
<div class="cb-page">
    <x-page-header
        title="Benchmark Evidence History"
        subtitle="Review historical scores, source evidence and verification status."
        :breadcrumb="['Comparison & Benchmarks', 'Benchmarks', 'Results']"
    >
        <x-slot:actions>
            <a href="{{ route('admin.benchmarks.index') }}" class="btn btn-secondary"><i data-lucide="trophy"></i>Rankings</a>
            @if(auth()->user()->canAccessModule('Benchmarks', 'Add'))
                <a href="{{ route('admin.benchmarks.create') }}" class="btn btn-primary"><i data-lucide="plus"></i>Add Result</a>
            @endif
        </x-slot:actions>
    </x-page-header>

    @if(session('status'))
        <div class="alert alert-success cb-flash"><i data-lucide="check-circle-2"></i><span>{{ session('status') }}</span></div>
    @endif

    <form method="GET" action="{{ route('admin.benchmarks.results') }}" class="card cb-filterbar cb-filterbar--results">
        <select class="select" name="benchmark">
            <option value="">All benchmarks</option>
            @foreach($benchmarks as $benchmark)
                <option value="{{ $benchmark->id }}" @selected((string)request('benchmark') === (string)$benchmark->id)>{{ $benchmark->name }}</option>
            @endforeach
        </select>

        <select class="select" name="type">
            <option value="">All entity types</option>
            <option value="model" @selected(request('type') === 'model')>AI Models</option>
            <option value="tool" @selected(request('type') === 'tool')>AI Tools</option>
        </select>

        <select class="select" name="benchmark_class">
            <option value="">All semantic classes</option>
            @foreach($benchmarkClasses as $value => $label)
                <option value="{{ $value }}" @selected(request('benchmark_class') === $value)>{{ $label }}</option>
            @endforeach
        </select>

        <select class="select" name="verified">
            <option value="">All verification states</option>
            <option value="1" @selected(request('verified') === '1')>Verified</option>
            <option value="0" @selected(request('verified') === '0')>Unverified</option>
        </select>

        <button class="btn btn-secondary" type="submit"><i data-lucide="filter"></i>Filter</button>

        @if(request()->anyFilled(['benchmark','type','benchmark_class','verified']))
            <a class="btn btn-ghost" href="{{ route('admin.benchmarks.results') }}"><i data-lucide="rotate-ccw"></i>Reset</a>
        @endif
    </form>

    <section class="card cb-table-card">
        <div class="cb-section-head">
            <div><span class="cb-eyebrow">Evidence Ledger</span><h2>Benchmark history</h2><p>Each record preserves its measured score, date and source reference.</p></div>
            <span class="cb-count-pill">{{ number_format($results->total()) }} records</span>
        </div>

        @if($results->count())
            <div class="table-wrap">
                <table class="data-table cb-results-table">
                    <thead>
                        <tr>
                            <th>Tested</th>
                            <th>Entity</th>
                            <th>Benchmark</th>
                            <th>Class</th>
                            <th>Score</th>
                            <th>Source</th>
                            <th>Verification</th>
                            <th class="cb-table__actions">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($results as $result)
                            @php $isModel = class_basename($result->benchmarkable_type) === 'AiModel'; @endphp
                            <tr>
                                <td><span class="cb-muted">{{ optional($result->tested_at)->format('M j, Y') ?? '—' }}</span></td>
                                <td>
                                    <div class="cb-record cb-record--compact">
                                        <span class="cb-record__icon"><i data-lucide="{{ $isModel ? 'brain-circuit' : 'wrench' }}"></i></span>
                                        <div>
                                            @if($result->benchmarkable)
                                                <a href="{{ $isModel ? route('admin.models.show', $result->benchmarkable->id) : route('admin.tools.show', $result->benchmarkable->id) }}">{{ $result->benchmarkable->name }}</a>
                                            @else
                                                <strong>Deleted item</strong>
                                            @endif
                                            <small>{{ $isModel ? 'AI Model' : 'AI Tool' }}</small>
                                        </div>
                                    </div>
                                </td>
                                <td>{{ $result->benchmark?->name ?? '—' }}</td>
                                <td><span class="cb-muted">{{ $result->benchmark?->benchmark_class_label ?? 'Unclassified' }}</span></td>
                                <td><span class="cb-score-pill">{{ rtrim(rtrim(number_format((float)$result->score,2,'.',''),'0'),'.') }}</span></td>
                                <td>
                                    @if($result->source_url)
                                        <a class="cb-source-link" href="{{ $result->source_url }}" target="_blank" rel="noopener noreferrer">
                                            {{ $result->source_name ?: 'Open source' }} <i data-lucide="external-link"></i>
                                        </a>
                                    @else
                                        <span class="cb-muted">{{ $result->source_name ?: '—' }}</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="cb-verify {{ $result->verified ? 'is-verified' : '' }}">
                                        <i data-lucide="{{ $result->verified ? 'badge-check' : 'circle-dashed' }}"></i>
                                        {{ $result->verified ? 'Verified' : 'Unverified' }}
                                    </span>
                                </td>
                                <td>
                                    @if(auth()->user()->canAccessModule('Benchmarks', 'Delete'))
                                        <form class="cb-actions" method="POST" action="{{ route('admin.benchmarks.results.destroy', $result->id) }}" onsubmit="return confirm('Delete this benchmark history record?')">
                                            @csrf
                                            @method('DELETE')
                                            <button class="icon-btn icon-btn--danger" type="submit" title="Delete result"><i data-lucide="trash-2"></i></button>
                                        </form>
                                    @else
                                        <span class="cb-muted">—</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="cb-pagination">
                <span>Showing {{ $results->firstItem() ?? 0 }}–{{ $results->lastItem() ?? 0 }} of {{ $results->total() }}</span>
                <div>{{ $results->links() }}</div>
            </div>
        @else
            <div class="cb-empty">
                <span><i data-lucide="history"></i></span>
                <h3>No benchmark history found</h3>
                <p>Try changing the filters or add the first benchmark result.</p>
            </div>
        @endif
    </section>
</div>
@endsection
