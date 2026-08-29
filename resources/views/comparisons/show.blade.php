@extends('layouts.admin')
@section('title', $comparison->title . ' · Comparison')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/pages/comparison-benchmarks.css') }}">
@endpush

@section('content')
@php
    $winnerId = $winner?->id;
@endphp

<div class="cb-page cb-detail">
    <x-page-header
        :title="$comparison->title"
        subtitle="Side-by-side decision analysis based on the latest stored product and model intelligence."
        :breadcrumb="['Comparison & Benchmarks', 'Comparisons', $comparison->title]"
    >
        <x-slot:actions>
            <a href="{{ route('admin.comparisons.index') }}" class="btn btn-secondary">
                <i data-lucide="arrow-left"></i>
                Comparisons
            </a>
            @if(auth()->user()->canAccessModule('Comparisons', 'Edit'))
                <a href="{{ route('admin.comparisons.edit', $comparison->id) }}" class="btn btn-primary">
                    <i data-lucide="pencil"></i>
                    Edit
                </a>
            @endif
        </x-slot:actions>
    </x-page-header>

    @if(session('status'))
        <div class="alert alert-success cb-flash"><i data-lucide="check-circle-2"></i><span>{{ session('status') }}</span></div>
    @endif

    <section class="card cb-detail__hero">
        <div>
            <div class="cb-detail__meta">
                <span class="cb-type-badge {{ $comparison->comparable_type === 'model' ? 'cb-type-badge--model' : '' }}">
                    <i data-lucide="{{ $comparison->comparable_type === 'tool' ? 'wrench' : 'brain-circuit' }}"></i>
                    {{ $comparison->comparable_type === 'tool' ? 'Tool Comparison' : 'Model Comparison' }}
                </span>
                <x-status-badge status="{{ ucfirst($comparison->status) }}" type="{{ $comparison->status === 'published' ? 'pos' : 'neutral' }}" />
            </div>
            <h1>{{ $comparison->title }}</h1>
            <p>{{ count($items) }} connected items · {{ number_format($comparison->views) }} views · Updated {{ $comparison->updated_at->format('M j, Y') }}</p>
        </div>

        <div class="cb-detail__signal">
            <span class="cb-eyebrow">Stored benchmark signal</span>
            @if($winner)
                <strong>{{ $winner->name }}</strong>
                <small>{{ number_format((float) $winner->benchmark_score, 1) }}/100 stored composite</small>
            @else
                <strong>No verified score</strong>
                <small>No positive stored composite benchmark is available for these items.</small>
            @endif
        </div>
    </section>

    @if($items->count())
        <section class="cb-item-grid">
            @foreach($items as $item)
                <article class="card cb-item-card {{ $winnerId === $item->id ? 'is-winner' : '' }}">
                    @if($winnerId === $item->id)
                        <span class="cb-item-card__winner"><i data-lucide="shield-check"></i>Highest stored benchmark</span>
                    @endif
                    <div class="cb-item-card__head">
                        <span class="cb-item-card__icon"><i data-lucide="{{ $comparison->comparable_type === 'tool' ? 'wrench' : 'brain-circuit' }}"></i></span>
                        <div>
                            <h2>{{ $item->name }}</h2>
                            <p>{{ $item->company?->name ?? 'Company not linked' }}</p>
                        </div>
                    </div>

                    <div class="cb-score">
                        <span>Verified composite benchmark</span>
                        @if($item->benchmark_score !== null && (float) $item->benchmark_score > 0)
                            <strong>{{ number_format((float) $item->benchmark_score, 1) }}</strong>
                            <div><span style="width: {{ min(100, max(0, (float) $item->benchmark_score)) }}%"></span></div>
                        @else
                            <strong>—</strong>
                            <small>Not verified</small>
                        @endif
                    </div>

                    <dl class="cb-item-card__facts">
                        @if($comparison->comparable_type === 'tool')
                            <div><dt>Rating</dt><dd>{{ !is_null($item->rating) ? number_format((float)$item->rating, 1) : '—' }}</dd></div>
                            <div><dt>Status</dt><dd>{{ ucfirst($item->status ?? 'unknown') }}</dd></div>
                            <div><dt>Pricing</dt><dd>{{ collect($item->pricing_models ?? [])->map(fn($v)=>ucfirst($v))->implode(', ') ?: '—' }}</dd></div>
                        @else
                            <div><dt>Version</dt><dd>{{ $item->version ?: '—' }}</dd></div>
                            <div><dt>Context</dt><dd>{{ filled($item->context_window) ? $item->context_window : '—' }}</dd></div>
                            <div><dt>Status</dt><dd>{{ ucfirst($item->status ?? 'unknown') }}</dd></div>
                        @endif
                    </dl>

                    <a class="cb-item-card__link" href="{{ $comparison->comparable_type === 'tool' ? route('admin.tools.show', $item->id) : route('admin.models.show', $item->id) }}">
                        Open full profile <i data-lucide="arrow-up-right"></i>
                    </a>
                </article>
            @endforeach
        </section>

        <section class="card cb-matrix">
            <div class="cb-section-head">
                <div>
                    <span class="cb-eyebrow">Decision Matrix</span>
                    <h2>Side-by-side intelligence</h2>
                    <p>Comparable attributes from the current AI Orbit records.</p>
                </div>
                <i data-lucide="table-properties"></i>
            </div>

            <div class="table-wrap">
                <table class="data-table cb-matrix__table">
                    <thead>
                        <tr>
                            <th>Metric</th>
                            @foreach($items as $item)<th>{{ $item->name }}</th>@endforeach
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Company</td>
                            @foreach($items as $item)<td>{{ $item->company?->name ?? '—' }}</td>@endforeach
                        </tr>
                        <tr>
                            <td>Verified composite score</td>
                            @foreach($items as $item)<td>@if($item->benchmark_score !== null && (float) $item->benchmark_score > 0)<strong>{{ number_format((float) $item->benchmark_score, 1) }}</strong>@else<span class="muted">Not verified</span>@endif</td>@endforeach
                        </tr>
                        <tr>
                            <td>Status</td>
                            @foreach($items as $item)<td>{{ ucfirst($item->status ?? 'unknown') }}</td>@endforeach
                        </tr>

                        @if($comparison->comparable_type === 'model')
                            <tr>
                                <td>Version</td>
                                @foreach($items as $item)<td>{{ $item->version ?: '—' }}</td>@endforeach
                            </tr>
                            <tr>
                                <td>Context window</td>
                                @foreach($items as $item)<td>{{ $item->context_window ?: '—' }}</td>@endforeach
                            </tr>
                            <tr>
                                <td>Input / 1M</td>
                                @foreach($items as $item)<td>{{ !is_null($item->input_price_per_million) ? '$'.number_format((float)$item->input_price_per_million, 2) : '—' }}</td>@endforeach
                            </tr>
                            <tr>
                                <td>Output / 1M</td>
                                @foreach($items as $item)<td>{{ !is_null($item->output_price_per_million) ? '$'.number_format((float)$item->output_price_per_million, 2) : '—' }}</td>@endforeach
                            </tr>
                        @else
                            <tr>
                                <td>Rating</td>
                                @foreach($items as $item)<td>{{ !is_null($item->rating) ? number_format((float)$item->rating, 1) : '—' }}</td>@endforeach
                            </tr>
                            <tr>
                                <td>Pricing models</td>
                                @foreach($items as $item)<td>{{ collect($item->pricing_models ?? [])->map(fn($v)=>ucfirst($v))->implode(', ') ?: '—' }}</td>@endforeach
                            </tr>
                            <tr>
                                <td>Platforms</td>
                                @foreach($items as $item)<td>{{ collect($item->platforms ?? [])->map(fn($v)=>ucfirst($v))->implode(', ') ?: '—' }}</td>@endforeach
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>
        </section>
    @else
        <div class="card cb-empty">
            <span><i data-lucide="triangle-alert"></i></span>
            <h3>No comparison items found</h3>
            <p>The records referenced by this comparison may have been removed.</p>
        </div>
    @endif
</div>
@endsection
