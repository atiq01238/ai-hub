@extends('layouts.admin')
@section('title', 'Comparisons')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/pages/comparison-benchmarks.css') }}">
@endpush

@section('content')
<div class="cb-page">
    <x-page-header
        title="AI Comparisons"
        subtitle="Build, manage and publish side-by-side decision intelligence across tools and models."
        :breadcrumb="['Comparison & Benchmarks', 'Comparisons']"
    >
        <x-slot:actions>
            <a href="{{ route('admin.comparisons.metrics') }}" class="btn btn-secondary">
                <i data-lucide="chart-no-axes-combined"></i>
                Metrics
            </a>
            @if(auth()->user()->canAccessModule('Comparisons', 'Add'))
                <a href="{{ route('admin.comparisons.builder') }}" class="btn btn-primary">
                    <i data-lucide="plus"></i>
                    New Comparison
                </a>
            @endif
        </x-slot:actions>
    </x-page-header>

    @if(session('status'))
        <div class="alert alert-success cb-flash">
            <i data-lucide="check-circle-2"></i>
            <span>{{ session('status') }}</span>
        </div>
    @endif

    <section class="cb-summary-grid">
        <article class="cb-summary-card">
            <span class="cb-summary-card__icon"><i data-lucide="git-compare-arrows"></i></span>
            <div>
                <span class="cb-eyebrow">Library</span>
                <strong>{{ number_format($comparisons->total()) }}</strong>
                <small>Total tracked comparisons</small>
            </div>
        </article>
        <article class="cb-summary-card">
            <span class="cb-summary-card__icon cb-summary-card__icon--violet"><i data-lucide="wrench"></i></span>
            <div>
                <span class="cb-eyebrow">Type</span>
                <strong>{{ $comparisons->getCollection()->where('comparable_type', 'tool')->count() }}</strong>
                <small>Tool comparisons on page</small>
            </div>
        </article>
        <article class="cb-summary-card">
            <span class="cb-summary-card__icon cb-summary-card__icon--cyan"><i data-lucide="brain-circuit"></i></span>
            <div>
                <span class="cb-eyebrow">Type</span>
                <strong>{{ $comparisons->getCollection()->where('comparable_type', 'model')->count() }}</strong>
                <small>Model comparisons on page</small>
            </div>
        </article>
        <article class="cb-summary-card">
            <span class="cb-summary-card__icon cb-summary-card__icon--green"><i data-lucide="eye"></i></span>
            <div>
                <span class="cb-eyebrow">Reach</span>
                <strong>{{ number_format($comparisons->getCollection()->sum('views')) }}</strong>
                <small>Views across current page</small>
            </div>
        </article>
    </section>

    <form method="GET" action="{{ route('admin.comparisons.index') }}" class="card cb-filterbar">
        <div class="cb-search">
            <i data-lucide="search"></i>
            <input class="input" type="search" name="search" value="{{ request('search') }}" placeholder="Search comparison title...">
        </div>

        <select class="select" name="type">
            <option value="">All types</option>
            <option value="tool" @selected(request('type') === 'tool')>Tool vs Tool</option>
            <option value="model" @selected(request('type') === 'model')>Model vs Model</option>
        </select>

        <select class="select" name="status">
            <option value="">All statuses</option>
            <option value="published" @selected(request('status') === 'published')>Published</option>
            <option value="draft" @selected(request('status') === 'draft')>Draft</option>
        </select>

        <button type="submit" class="btn btn-secondary">
            <i data-lucide="sliders-horizontal"></i>
            Apply
        </button>

        @if(request()->anyFilled(['search','type','status']))
            <a class="btn btn-ghost" href="{{ route('admin.comparisons.index') }}">
                <i data-lucide="rotate-ccw"></i>
                Reset
            </a>
        @endif
    </form>

    <section class="card cb-table-card">
        <div class="cb-section-head">
            <div>
                <span class="cb-eyebrow">Decision Library</span>
                <h2>Comparison records</h2>
                <p>Manage analysis sets used to evaluate products and models side-by-side.</p>
            </div>
            <span class="cb-count-pill">{{ number_format($comparisons->total()) }} records</span>
        </div>

        @if($comparisons->count())
            <div class="table-wrap">
                <table class="data-table cb-table">
                    <thead>
                        <tr>
                            <th>Comparison</th>
                            <th>Type</th>
                            <th>Items</th>
                            <th>Views</th>
                            <th>Updated</th>
                            <th>Status</th>
                            <th class="cb-table__actions">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($comparisons as $comparison)
                            <tr>
                                <td>
                                    <div class="cb-record">
                                        <span class="cb-record__icon"><i data-lucide="scale"></i></span>
                                        <div>
                                            <a href="{{ route('admin.comparisons.show', $comparison->id) }}">{{ $comparison->title }}</a>
                                            <small>{{ $comparison->slug }}</small>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="cb-type-badge {{ $comparison->comparable_type === 'model' ? 'cb-type-badge--model' : '' }}">
                                        <i data-lucide="{{ $comparison->comparable_type === 'tool' ? 'wrench' : 'brain-circuit' }}"></i>
                                        {{ $comparison->comparable_type === 'tool' ? 'Tools' : 'Models' }}
                                    </span>
                                </td>
                                <td>
                                    <span class="cb-item-count">{{ count($comparison->item_ids ?? []) }} items</span>
                                </td>
                                <td>
                                    <span class="cb-inline-stat"><i data-lucide="eye"></i>{{ number_format($comparison->views) }}</span>
                                </td>
                                <td><span class="cb-muted">{{ $comparison->updated_at->format('M j, Y') }}</span></td>
                                <td>
                                    <x-status-badge
                                        status="{{ ucfirst($comparison->status) }}"
                                        type="{{ $comparison->status === 'published' ? 'pos' : 'neutral' }}"
                                    />
                                </td>
                                <td>
                                    <div class="cb-actions">
                                        <a class="icon-btn" href="{{ route('admin.comparisons.show', $comparison->id) }}" title="View">
                                            <i data-lucide="eye"></i>
                                        </a>
                                        @if(auth()->user()->canAccessModule('Comparisons', 'Edit'))
                                            <a class="icon-btn" href="{{ route('admin.comparisons.edit', $comparison->id) }}" title="Edit">
                                                <i data-lucide="pencil"></i>
                                            </a>
                                        @endif
                                        @if(auth()->user()->canAccessModule('Comparisons', 'Delete'))
                                            <form method="POST" action="{{ route('admin.comparisons.destroy', $comparison->id) }}" onsubmit="return confirm('Delete this comparison?')">
                                                @csrf
                                                @method('DELETE')
                                                <button class="icon-btn icon-btn--danger" type="submit" title="Delete">
                                                    <i data-lucide="trash-2"></i>
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="cb-pagination">
                <span>Showing {{ $comparisons->firstItem() ?? 0 }}–{{ $comparisons->lastItem() ?? 0 }} of {{ $comparisons->total() }}</span>
                <div>{{ $comparisons->links() }}</div>
            </div>
        @else
            <div class="cb-empty">
                <span><i data-lucide="git-compare-arrows"></i></span>
                <h3>{{ request()->anyFilled(['search','type','status']) ? 'No matching comparisons' : 'No comparisons yet' }}</h3>
                <p>{{ request()->anyFilled(['search','type','status']) ? 'Clear or adjust the current filters.' : 'Create a 2–4 item comparison to start building decision intelligence.' }}</p>
                @if(auth()->user()->canAccessModule('Comparisons', 'Add'))
                    <a class="btn btn-primary" href="{{ route('admin.comparisons.builder') }}"><i data-lucide="plus"></i>New Comparison</a>
                @endif
            </div>
        @endif
    </section>
</div>
@endsection
