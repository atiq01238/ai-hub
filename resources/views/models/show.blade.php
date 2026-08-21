@extends('layouts.admin')
@section('title', $model->name . ' · AI Model')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/pages/models.css') }}">
@endpush

@section('content')
@php
    $capabilities = collect($model->capabilities ?? [])->filter()->values();
    $benchmarks = collect($model->benchmarks ?? []);
    $score = $model->benchmark_score !== null ? (float) $model->benchmark_score : null;
    $scoreTone = $score === null ? 'neutral' : ($score >= 80 ? 'positive' : ($score >= 60 ? 'warning' : 'critical'));
@endphp

<x-page-header
    title="{{ $model->name }}"
    subtitle="AI model specification, commercial profile and benchmark intelligence"
    :breadcrumb="['AI Management', 'AI Models', $model->name]"
>
    <x-slot:actions>
        <a href="{{ route('admin.models.index') }}" class="btn btn-secondary btn-sm">
            <i data-lucide="arrow-left"></i>
            All Models
        </a>
        @if(auth()->user()->canAccessModule('AI Models', 'Edit'))
            <a href="{{ route('admin.models.edit', $model->id) }}" class="btn btn-primary btn-sm">
                <i data-lucide="pencil"></i>
                Edit Model
            </a>
        @endif
    </x-slot:actions>
</x-page-header>

@if(session('status'))
    <div class="alert alert-success models-flash">
        <i data-lucide="circle-check-big"></i>
        <span>{{ session('status') }}</span>
    </div>
@endif

<section class="model-hero card">
    <div class="model-hero__identity">
        <div class="model-hero__mark">
            <img
                src="{{ $model->logo_url }}"
                alt="{{ $model->name }} logo"
                onerror="this.style.display='none'; this.nextElementSibling.style.display='grid';"
            >
            <span class="model-hero__fallback" aria-hidden="true">{{ strtoupper(mb_substr($model->name, 0, 1)) }}</span>
        </div>
        <div class="model-hero__copy">
            <div class="model-hero__eyebrow">
                <span>{{ $model->company?->name ?? 'Independent model' }}</span>
                @if($model->version)
                    <span class="model-hero__version mono">v{{ $model->version }}</span>
                @endif
                <x-status-badge
                    status="{{ ucfirst($model->status) }}"
                    type="{{ $model->status === 'active' ? 'pos' : ($model->status === 'preview' ? 'warn' : 'neutral') }}"
                />
            </div>
            <h1>{{ $model->name }}</h1>
            <p>
                {{ $model->tool?->name ? 'Linked to '.$model->tool->name.'. ' : '' }}
                {{ $capabilities->isNotEmpty() ? 'Supports '.$capabilities->take(4)->join(', ').'.' : 'Capability classification has not been completed yet.' }}
            </p>
        </div>
    </div>

    <div class="model-hero__score model-hero__score--{{ $scoreTone }}">
        <span>Benchmark score</span>
        <strong>{{ $score !== null ? number_format($score, 1) : '—' }}</strong>
        <small>/ 100</small>
        <div class="model-hero__scorebar"><i style="width: {{ $score !== null ? min(100, max(0, $score)) : 0 }}%"></i></div>
    </div>
</section>

<section class="model-kpis" aria-label="Model overview">
    <article class="card model-kpi">
        <span class="model-kpi__icon"><i data-lucide="panel-top"></i></span>
        <div><small>Context window</small><strong class="mono">{{ $model->context_window ?: '—' }}</strong></div>
    </article>
    <article class="card model-kpi">
        <span class="model-kpi__icon"><i data-lucide="calendar-days"></i></span>
        <div><small>Release date</small><strong>{{ $model->release_date?->format('M d, Y') ?? '—' }}</strong></div>
    </article>
    <article class="card model-kpi">
        <span class="model-kpi__icon"><i data-lucide="arrow-down-to-line"></i></span>
        <div><small>Input / 1M</small><strong class="mono">{{ $model->input_price_per_million !== null ? '$'.number_format((float) $model->input_price_per_million, 2) : '—' }}</strong></div>
    </article>
    <article class="card model-kpi">
        <span class="model-kpi__icon"><i data-lucide="arrow-up-from-line"></i></span>
        <div><small>Output / 1M</small><strong class="mono">{{ $model->output_price_per_million !== null ? '$'.number_format((float) $model->output_price_per_million, 2) : '—' }}</strong></div>
    </article>
</section>

<div class="model-detail-layout">
    <main class="model-detail-main">
        <section class="card model-detail-card">
            <div class="model-detail-card__header">
                <div>
                    <span class="eyebrow">CAPABILITY MAP</span>
                    <h2>Model capabilities</h2>
                    <p>Current modality and interface classification for this model.</p>
                </div>
                <span class="model-detail-card__count mono">{{ $capabilities->count() }} classified</span>
            </div>

            @if($capabilities->isNotEmpty())
                <div class="model-capability-grid">
                    @foreach($capabilities as $capability)
                        @php
                            $icon = match($capability) {
                                'Reasoning' => 'brain-circuit',
                                'Vision' => 'eye',
                                'Audio' => 'audio-lines',
                                'Image' => 'image',
                                'Video' => 'video',
                                'API Support' => 'code-2',
                                default => 'sparkles',
                            };
                        @endphp
                        <div class="model-capability-card">
                            <i data-lucide="{{ $icon }}"></i>
                            <strong>{{ $capability }}</strong>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="model-inline-empty">
                    <i data-lucide="sparkles"></i>
                    <div><strong>No capabilities recorded</strong><span>Add capability classifications from the Edit Model screen.</span></div>
                </div>
            @endif

            @if($model->capability_notes)
                <div class="model-capability-notes">
                    <span class="eyebrow">OPERATING NOTES</span>
                    <p>{{ $model->capability_notes }}</p>
                </div>
            @endif
        </section>

        <section class="card model-detail-card">
            <div class="model-detail-card__header">
                <div>
                    <span class="eyebrow">EVALUATION</span>
                    <h2>Benchmark breakdown</h2>
                    <p>Stored per-benchmark intelligence, where available.</p>
                </div>
                <span class="model-detail-card__count mono">{{ $benchmarks->count() }} entries</span>
            </div>

            @if($benchmarks->isNotEmpty())
                <div class="model-benchmark-list">
                    @foreach($benchmarks as $name => $value)
                        @php
                            $label = is_string($name) ? $name : 'Benchmark '.($loop->iteration);
                            $displayValue = is_array($value) ? json_encode($value, JSON_UNESCAPED_SLASHES) : $value;
                            $numericValue = is_numeric($value) ? max(0, min(100, (float) $value)) : null;
                        @endphp
                        <div class="model-benchmark-row">
                            <div class="model-benchmark-row__head">
                                <strong>{{ $label }}</strong>
                                <span class="mono">{{ $displayValue }}</span>
                            </div>
                            @if($numericValue !== null)
                                <div class="model-benchmark-row__track"><i style="width: {{ $numericValue }}%"></i></div>
                            @endif
                        </div>
                    @endforeach
                </div>
            @else
                <div class="model-inline-empty">
                    <i data-lucide="chart-no-axes-combined"></i>
                    <div><strong>No detailed benchmark results yet</strong><span>The overall benchmark score is available, but no per-benchmark JSON data has been recorded.</span></div>
                </div>
            @endif
        </section>
    </main>

    <aside class="model-detail-aside">
        <section class="card model-detail-card model-spec-card">
            <div class="model-detail-card__header model-detail-card__header--compact">
                <div>
                    <span class="eyebrow">SPECIFICATION</span>
                    <h2>Model data</h2>
                </div>
            </div>

            <dl class="model-spec-list">
                <div><dt>Company</dt><dd>{{ $model->company?->name ?? '—' }}</dd></div>
                <div><dt>Linked tool</dt><dd>{{ $model->tool?->name ?? '—' }}</dd></div>
                <div><dt>Version</dt><dd class="mono">{{ $model->version ?: '—' }}</dd></div>
                <div><dt>Release</dt><dd>{{ $model->release_date?->format('M d, Y') ?? '—' }}</dd></div>
                <div><dt>Context</dt><dd class="mono">{{ $model->context_window ?: '—' }}</dd></div>
                <div><dt>Status</dt><dd>{{ ucfirst($model->status) }}</dd></div>
                <div><dt>Slug</dt><dd class="mono model-spec-list__slug">{{ $model->slug }}</dd></div>
            </dl>
        </section>

        <section class="card model-detail-card model-commercial-card">
            <div class="model-detail-card__header model-detail-card__header--compact">
                <div>
                    <span class="eyebrow">COMMERCIAL PROFILE</span>
                    <h2>Token pricing</h2>
                </div>
            </div>

            <div class="model-commercial-card__grid">
                <div>
                    <span>Input</span>
                    <strong class="mono">{{ $model->input_price_per_million !== null ? '$'.number_format((float) $model->input_price_per_million, 2) : '—' }}</strong>
                    <small>per 1M tokens</small>
                </div>
                <div>
                    <span>Output</span>
                    <strong class="mono">{{ $model->output_price_per_million !== null ? '$'.number_format((float) $model->output_price_per_million, 2) : '—' }}</strong>
                    <small>per 1M tokens</small>
                </div>
            </div>
        </section>

        @if($model->tool)
            <section class="card model-detail-card model-linked-tool">
                <span class="eyebrow">CONNECTED PRODUCT</span>
                <div class="model-linked-tool__body">
                    <div class="model-linked-tool__icon"><i data-lucide="boxes"></i></div>
                    <div>
                        <strong>{{ $model->tool->name }}</strong>
                        <span>{{ $model->company?->name ?? 'AI tool' }}</span>
                    </div>
                </div>
                <a href="{{ route('admin.tools.show', $model->tool->id) }}" class="btn btn-secondary btn-sm model-linked-tool__action">
                    View Tool
                    <i data-lucide="arrow-up-right"></i>
                </a>
            </section>
        @endif

        <section class="card model-detail-card model-record-card">
            <span class="eyebrow">RECORD</span>
            <div class="model-record-card__line"><span>ID</span><strong class="mono">#{{ $model->id }}</strong></div>
            <div class="model-record-card__line"><span>Last updated</span><strong>{{ $model->updated_at?->format('M d, Y') ?? '—' }}</strong></div>
        </section>
    </aside>
</div>
@endsection