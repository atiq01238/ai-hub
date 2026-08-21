@extends('layouts.admin')
@section('title', 'AI Models')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/pages/models.css') }}">
@endpush

@section('content')
@php
    $hasFilters = request()->filled('search') || request()->filled('company_id') || request()->filled('tool_id') || request()->filled('capability') || request()->filled('status');
@endphp

<x-page-header
    title="AI Models"
    subtitle="Manage model versions, capabilities, commercial pricing and benchmark intelligence"
    :breadcrumb="['AI Management', 'AI Models']"
>
    <x-slot:actions>
        @if(auth()->user()->canAccessModule('AI Models', 'Add'))
            <a href="{{ route('admin.models.create') }}" class="btn btn-primary btn-sm">
                <i data-lucide="plus"></i>
                Add Model
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

<section class="models-summary" aria-label="Model directory summary">
    <div class="models-summary__item">
        <span class="models-summary__label">Models in view</span>
        <strong>{{ number_format($models->total()) }}</strong>
    </div>
    <div class="models-summary__item">
        <span class="models-summary__label">Companies</span>
        <strong>{{ number_format($companies->count()) }}</strong>
    </div>
    <div class="models-summary__item">
        <span class="models-summary__label">Linked tools</span>
        <strong>{{ number_format($tools->count()) }}</strong>
    </div>
    <div class="models-summary__item models-summary__item--accent">
        <span class="models-summary__label">Directory mode</span>
        <strong>{{ $hasFilters ? 'Filtered' : 'All models' }}</strong>
    </div>
</section>

<form method="GET" action="{{ route('admin.models.index') }}" class="models-filter card" aria-label="Filter AI models">
    <div class="models-filter__search">
        <i data-lucide="search"></i>
        <input
            class="input"
            type="search"
            name="search"
            value="{{ request('search') }}"
            placeholder="Search by model, version or context window..."
            aria-label="Search AI models"
        >
    </div>

    <select class="select" name="company_id" aria-label="Filter by company">
        <option value="">All companies</option>
        @foreach($companies as $company)
            <option value="{{ $company->id }}" @selected((string) request('company_id') === (string) $company->id)>
                {{ $company->name }}
            </option>
        @endforeach
    </select>

    <select class="select" name="tool_id" aria-label="Filter by tool">
        <option value="">All tools</option>
        @foreach($tools as $tool)
            <option value="{{ $tool->id }}" @selected((string) request('tool_id') === (string) $tool->id)>
                {{ $tool->name }}
            </option>
        @endforeach
    </select>

    <select class="select" name="capability" aria-label="Filter by capability">
        <option value="">Any capability</option>
        @foreach(['Reasoning', 'Vision', 'Audio', 'Image', 'Video', 'API Support'] as $capability)
            <option value="{{ $capability }}" @selected(request('capability') === $capability)>
                {{ $capability }}
            </option>
        @endforeach
    </select>

    <select class="select" name="status" aria-label="Filter by status">
        <option value="">All statuses</option>
        <option value="active" @selected(request('status') === 'active')>Active</option>
        <option value="preview" @selected(request('status') === 'preview')>Preview</option>
        <option value="deprecated" @selected(request('status') === 'deprecated')>Deprecated</option>
    </select>

    <div class="models-filter__actions">
        <button type="submit" class="btn btn-primary btn-sm">
            <i data-lucide="sliders-horizontal"></i>
            Filter
        </button>
        @if($hasFilters)
            <a class="btn btn-secondary btn-sm" href="{{ route('admin.models.index') }}">
                <i data-lucide="rotate-ccw"></i>
                Reset
            </a>
        @endif
    </div>
</form>

<div class="card models-directory">
    <div class="models-directory__header">
        <div>
            <span class="eyebrow">MODEL CATALOG</span>
            <h2>Model intelligence directory</h2>
            <p>Compare ownership, context capacity, pricing and benchmark readiness at a glance.</p>
        </div>
        <div class="models-directory__count mono">
            {{ number_format($models->total()) }} records
        </div>
    </div>

    <div class="table-wrap models-table-wrap">
        <table class="data-table models-table">
            <thead>
                <tr>
                    <th>Model</th>
                    <th>Organization</th>
                    <th>Context</th>
                    <th>Pricing / 1M</th>
                    <th>Capabilities</th>
                    <th>Benchmark</th>
                    <th>Status</th>
                    <th class="models-table__actions-head">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($models as $model)
                    @php
                        $caps = collect($model->capabilities ?? [])->filter()->values();
                        $score = $model->benchmark_score !== null ? (float) $model->benchmark_score : null;
                        $scoreClass = $score === null ? 'muted' : ($score >= 80 ? 'strong' : ($score >= 60 ? 'medium' : 'low'));
                    @endphp
                    <tr>
                        <td>
                            <div class="model-identity">
                                <div class="model-identity__mark">
                                    <img
                                        src="{{ $model->logo_url }}"
                                        alt="{{ $model->name }} logo"
                                        loading="lazy"
                                        onerror="this.style.display='none'; this.nextElementSibling.style.display='grid';"
                                    >
                                    <span class="model-identity__fallback" aria-hidden="true">
                                        {{ strtoupper(mb_substr($model->name, 0, 1)) }}
                                    </span>
                                </div>
                                <div class="model-identity__body">
                                    <a href="{{ route('admin.models.show', $model->id) }}" class="model-identity__name">
                                        {{ $model->name }}
                                    </a>
                                    <div class="model-identity__meta">
                                        <span>{{ $model->version ?: 'Version not set' }}</span>
                                        @if($model->release_date)
                                            <span>•</span>
                                            <span>{{ $model->release_date->format('M Y') }}</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </td>
                        <td>
                            <div class="model-org">
                                <strong>{{ $model->company?->name ?? 'Independent' }}</strong>
                                <span>{{ $model->tool?->name ?? 'No linked tool' }}</span>
                            </div>
                        </td>
                        <td>
                            <span class="model-context mono">{{ $model->context_window ?: '—' }}</span>
                        </td>
                        <td>
                            <div class="model-price-pair">
                                <span><small>IN</small> {{ $model->input_price_per_million !== null ? '$'.number_format((float) $model->input_price_per_million, 2) : '—' }}</span>
                                <span><small>OUT</small> {{ $model->output_price_per_million !== null ? '$'.number_format((float) $model->output_price_per_million, 2) : '—' }}</span>
                            </div>
                        </td>
                        <td>
                            <div class="model-capabilities">
                                @forelse($caps->take(3) as $capability)
                                    <span class="model-capability">{{ $capability }}</span>
                                @empty
                                    <span class="text-sub">Not classified</span>
                                @endforelse
                                @if($caps->count() > 3)
                                    <span class="model-capability model-capability--more">+{{ $caps->count() - 3 }}</span>
                                @endif
                            </div>
                        </td>
                        <td>
                            <div class="model-score model-score--{{ $scoreClass }}">
                                <strong>{{ $score !== null ? number_format($score, 1) : '—' }}</strong>
                                <span class="model-score__bar"><i style="width: {{ $score !== null ? min(100, max(0, $score)) : 0 }}%"></i></span>
                            </div>
                        </td>
                        <td>
                            <x-status-badge
                                status="{{ ucfirst($model->status) }}"
                                type="{{ $model->status === 'active' ? 'pos' : ($model->status === 'preview' ? 'warn' : 'neutral') }}"
                            />
                        </td>
                        <td>
                            <div class="models-row-actions">
                                <a class="icon-btn" href="{{ route('admin.models.show', $model->id) }}" title="View model" aria-label="View {{ $model->name }}">
                                    <i data-lucide="eye"></i>
                                </a>
                                @if(auth()->user()->canAccessModule('AI Models', 'Edit'))
                                    <a class="icon-btn" href="{{ route('admin.models.edit', $model->id) }}" title="Edit model" aria-label="Edit {{ $model->name }}">
                                        <i data-lucide="pencil"></i>
                                    </a>
                                @endif
                                @if(auth()->user()->canAccessModule('AI Models', 'Delete'))
                                    <form method="POST" action="{{ route('admin.models.destroy', $model->id) }}" onsubmit="return confirm('Delete this AI model? This action cannot be undone.')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="icon-btn icon-btn--danger" type="submit" title="Delete model" aria-label="Delete {{ $model->name }}">
                                            <i data-lucide="trash-2"></i>
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8">
                            <div class="models-empty">
                                <div class="models-empty__icon"><i data-lucide="brain-circuit"></i></div>
                                <h3>{{ $hasFilters ? 'No models match these filters' : 'No AI models yet' }}</h3>
                                <p>{{ $hasFilters ? 'Try widening your search or resetting the current filters.' : 'Create the first model record to begin building your model intelligence catalog.' }}</p>
                                @if($hasFilters)
                                    <a href="{{ route('admin.models.index') }}" class="btn btn-secondary btn-sm">Reset filters</a>
                                @elseif(auth()->user()->canAccessModule('AI Models', 'Add'))
                                    <a href="{{ route('admin.models.create') }}" class="btn btn-primary btn-sm">Add first model</a>
                                @endif
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($models->hasPages() || $models->total())
        <div class="models-pagination">
            <span>
                Showing <strong>{{ $models->firstItem() ?? 0 }}</strong>–<strong>{{ $models->lastItem() ?? 0 }}</strong> of <strong>{{ number_format($models->total()) }}</strong>
            </span>
            <div>{{ $models->links() }}</div>
        </div>
    @endif
</div>
@endsection