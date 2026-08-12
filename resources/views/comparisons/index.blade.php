@extends('layouts.admin')
@section('title', 'Comparisons')

@section('content')

<style>
    .comparisons-page {
        --cmp-border: var(--border-soft, rgba(148,163,184,.14));
        --cmp-muted: var(--muted, #8d98ad);
        --cmp-text: var(--text, #eef2ff);
        --cmp-blue: #6d8cff;
        --cmp-cyan: #22d3ee;
    }

    .comparisons-hero {
        position: relative;
        overflow: hidden;
        display: flex;
        align-items: flex-end;
        justify-content: space-between;
        gap: 20px;
        margin-bottom: 15px;
        padding: 20px 21px;
        border: 1px solid var(--cmp-border);
        border-radius: 17px;
        background:
            radial-gradient(circle at 90% 5%, rgba(109,140,255,.17), transparent 28%),
            radial-gradient(circle at 65% 120%, rgba(34,211,238,.06), transparent 30%),
            linear-gradient(145deg, rgba(255,255,255,.045), rgba(255,255,255,.012));
        box-shadow: 0 16px 42px rgba(0,0,0,.07);
    }

    .comparisons-hero__eyebrow {
        display: flex;
        align-items: center;
        gap: 7px;
        margin-bottom: 7px;
        color: var(--cmp-cyan);
        font-size: 8.5px;
        font-weight: 800;
        letter-spacing: .13em;
        text-transform: uppercase;
    }

    .comparisons-hero__dot {
        width: 6px;
        height: 6px;
        border-radius: 50%;
        background: #32d583;
        box-shadow: 0 0 0 4px rgba(50,213,131,.1);
    }

    .comparisons-hero h1 {
        margin: 0;
        color: var(--cmp-text);
        font-size: clamp(22px, 3vw, 29px);
        line-height: 1.15;
        letter-spacing: -.035em;
        font-weight: 800;
    }

    .comparisons-hero p {
        margin: 7px 0 0;
        color: var(--cmp-muted);
        font-size: 9px;
    }

    .comparisons-hero__action {
        position: relative;
        z-index: 2;
    }

    .comparisons-hero__action .btn {
        min-height: 37px;
        border-radius: 9px;
    }

    .comparisons-notice {
        display: flex;
        align-items: center;
        gap: 8px;
        margin-bottom: 14px;
        padding: 10px 13px;
        border: 1px solid rgba(50,213,131,.17);
        border-radius: 9px;
        color: #82e6b0;
        background: rgba(50,213,131,.045);
        font-size: 9px;
    }

    .comparisons-notice svg {
        width: 14px;
        height: 14px;
    }

    .comparisons-filter {
        display: grid;
        grid-template-columns: minmax(220px, 1fr) 170px 170px auto auto;
        gap: 8px;
        align-items: center;
        margin-bottom: 14px;
        padding: 9px;
        border: 1px solid var(--cmp-border);
        border-radius: 13px;
        background: linear-gradient(145deg, rgba(255,255,255,.035), rgba(255,255,255,.012));
        box-shadow: 0 10px 30px rgba(0,0,0,.045);
    }

    .comparisons-search {
        position: relative;
        display: flex;
        align-items: center;
    }

    .comparisons-search svg {
        position: absolute;
        left: 11px;
        width: 14px;
        height: 14px;
        color: var(--cmp-muted);
        pointer-events: none;
    }

    .comparisons-search input {
        width: 100%;
        height: 37px;
        padding: 0 12px 0 34px;
        border: 1px solid var(--cmp-border);
        border-radius: 8px;
        outline: none;
        color: var(--cmp-text);
        background: rgba(255,255,255,.022);
        font-size: 9px;
    }

    .comparisons-search input:focus {
        border-color: rgba(109,140,255,.42);
        box-shadow: 0 0 0 3px rgba(109,140,255,.07);
    }

    .comparisons-filter .select {
        min-height: 37px;
        border-radius: 8px;
        font-size: 9px;
    }

    .comparisons-filter .btn {
        height: 37px;
        border-radius: 8px;
        white-space: nowrap;
    }

    .comparisons-table-card {
        overflow: hidden;
        border: 1px solid var(--cmp-border);
        border-radius: 14px;
        background: linear-gradient(145deg, rgba(255,255,255,.035), rgba(255,255,255,.012));
        box-shadow: 0 14px 38px rgba(0,0,0,.055);
    }

    .comparisons-table-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        padding: 13px 15px;
        border-bottom: 1px solid var(--cmp-border);
    }

    .comparisons-table-head__title {
        display: flex;
        align-items: center;
        gap: 8px;
        color: var(--cmp-text);
        font-size: 9.5px;
        font-weight: 800;
    }

    .comparisons-table-head__icon {
        width: 26px;
        height: 26px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 7px;
        color: #a9b7ff;
        background: rgba(109,140,255,.08);
    }

    .comparisons-table-head__icon svg {
        width: 13px;
        height: 13px;
    }

    .comparisons-table-head__count {
        padding: 5px 8px;
        border: 1px solid var(--cmp-border);
        border-radius: 6px;
        color: var(--cmp-muted);
        background: rgba(255,255,255,.015);
        font-size: 7px;
    }

    .comparisons-table-card .table-wrap {
        overflow-x: auto;
    }

    .comparisons-table-card .data-table {
        width: 100%;
        min-width: 720px;
    }

    .comparisons-table-card .data-table thead th {
        padding: 10px 14px;
        color: #7f8aa0;
        background: rgba(255,255,255,.018);
        font-size: 7.5px;
        font-weight: 800;
        letter-spacing: .07em;
        text-transform: uppercase;
        white-space: nowrap;
    }

    .comparisons-table-card .data-table tbody td {
        padding: 12px 14px;
        border-top: 1px solid rgba(148,163,184,.075);
        font-size: 9px;
    }

    .comparison-title {
        display: flex;
        align-items: center;
        gap: 10px;
        min-width: 230px;
    }

    .comparison-title__icon {
        width: 32px;
        height: 32px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        flex: 0 0 32px;
        border: 1px solid rgba(109,140,255,.15);
        border-radius: 9px;
        color: #a7b5ff;
        background: rgba(109,140,255,.07);
    }

    .comparison-title__icon svg {
        width: 15px;
        height: 15px;
    }

    .comparison-title__text {
        min-width: 0;
    }

    .comparison-title__text a {
        display: block;
        overflow: hidden;
        max-width: 360px;
        color: var(--cmp-text);
        font-size: 9.5px;
        font-weight: 750;
        text-decoration: none;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .comparison-title__text a:hover {
        color: #9fafef;
    }

    .comparison-title__sub {
        margin-top: 3px;
        color: var(--cmp-muted);
        font-size: 7.5px;
    }

    .comparison-type {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 5px 8px;
        border: 1px solid rgba(109,140,255,.13);
        border-radius: 6px;
        color: #aebcff;
        background: rgba(109,140,255,.055);
        font-size: 7.5px;
        font-weight: 700;
        white-space: nowrap;
    }

    .comparison-type--model {
        border-color: rgba(34,211,238,.13);
        color: #8fe6f0;
        background: rgba(34,211,238,.045);
    }

    .comparison-type svg {
        width: 11px;
        height: 11px;
    }

    .comparison-views {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        color: var(--cmp-text);
        font-size: 9px;
        font-weight: 700;
    }

    .comparison-views svg {
        width: 12px;
        height: 12px;
        color: var(--cmp-muted);
    }

    .comparison-date {
        color: var(--cmp-muted);
        font-size: 8px;
        white-space: nowrap;
    }

    .comparison-actions {
        display: flex;
        justify-content: flex-end;
        gap: 6px;
    }

    .comparison-actions .icon-btn {
        width: 29px !important;
        height: 29px !important;
        border-radius: 7px;
    }

    .comparison-actions .icon-btn:hover {
        border-color: rgba(109,140,255,.25);
        color: #aab8ff;
        background: rgba(109,140,255,.07);
    }

    .comparison-actions .icon-btn--danger:hover {
        border-color: rgba(255,90,90,.22);
        color: #ff9b9b;
        background: rgba(255,90,90,.06);
    }

    .comparisons-empty {
        padding: 52px 20px !important;
        text-align: center;
    }

    .comparisons-empty__icon {
        width: 46px;
        height: 46px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 10px;
        border-radius: 13px;
        color: #9eafff;
        background: rgba(109,140,255,.07);
    }

    .comparisons-empty__icon svg {
        width: 21px;
        height: 21px;
    }

    .comparisons-empty strong {
        display: block;
        color: var(--cmp-text);
        font-size: 10px;
    }

    .comparisons-empty span {
        display: block;
        margin-top: 5px;
        color: var(--cmp-muted);
        font-size: 8px;
    }

    .comparisons-pagination {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        padding: 12px 14px;
        border-top: 1px solid var(--cmp-border);
        color: var(--cmp-muted);
        font-size: 8px;
    }

    .comparisons-pagination__info strong {
        color: var(--cmp-text);
    }

    @media (max-width: 900px) {
        .comparisons-filter {
            grid-template-columns: 1fr 1fr;
        }

        .comparisons-search {
            grid-column: 1 / -1;
        }
    }

    @media (max-width: 600px) {
        .comparisons-hero {
            align-items: flex-start;
            flex-direction: column;
        }

        .comparisons-hero__action,
        .comparisons-hero__action .btn {
            width: 100%;
        }

        .comparisons-filter {
            grid-template-columns: 1fr;
        }

        .comparisons-search {
            grid-column: auto;
        }

        .comparisons-pagination {
            align-items: flex-start;
            flex-direction: column;
        }
    }
</style>

<div class="comparisons-page">

    {{-- HEADER --}}
    <section class="comparisons-hero">

        <div>
            <div class="comparisons-hero__eyebrow">
                <span class="comparisons-hero__dot"></span>
                Comparison & Benchmarks · Intelligence
            </div>

            <h1>AI Comparisons</h1>

            <p>
                {{ $comparisons->total() }}
                {{ Str::plural('comparison', $comparisons->total()) }}
                tracked across tools and models
            </p>
        </div>

        <div class="comparisons-hero__action">
            <a href="{{ route('admin.comparisons.builder') }}" class="btn btn-primary btn-sm">
                <i data-lucide="plus"></i>
                New Comparison
            </a>
        </div>

    </section>

    {{-- STATUS --}}
    @if (session('status'))
        <div class="comparisons-notice">
            <i data-lucide="circle-check"></i>
            <span>{{ session('status') }}</span>
        </div>
    @endif

    {{-- FILTERS --}}
    <form method="GET" class="comparisons-filter">

        <div class="comparisons-search">
            <i data-lucide="search"></i>

            <input
                type="text"
                name="search"
                value="{{ request('search') }}"
                placeholder="Search comparisons by title..."
            >
        </div>

        <select class="select" name="type" onchange="this.form.submit()">

            <option value="">All Types</option>

            <option value="tool" @selected(request('type') === 'tool')>
                Tool vs Tool
            </option>

            <option value="model" @selected(request('type') === 'model')>
                Model vs Model
            </option>

        </select>

        <select class="select" name="status" onchange="this.form.submit()">

            <option value="">All Status</option>

            <option value="published" @selected(request('status') === 'published')>
                Published
            </option>

            <option value="draft" @selected(request('status') === 'draft')>
                Draft
            </option>

        </select>

        <button type="submit" class="btn btn-secondary btn-sm">
            <i data-lucide="sliders-horizontal"></i>
            Filter
        </button>

        @if(request()->anyFilled(['search', 'type', 'status']))
            <a
                href="{{ route('admin.comparisons.index') }}"
                class="btn btn-ghost btn-sm"
            >
                <i data-lucide="x"></i>
                Clear
            </a>
        @endif

    </form>

    {{-- TABLE --}}
    <section class="comparisons-table-card">

        <div class="comparisons-table-head">

            <div class="comparisons-table-head__title">

                <span class="comparisons-table-head__icon">
                    <i data-lucide="git-compare-arrows"></i>
                </span>

                Comparison Library

            </div>

            <span class="comparisons-table-head__count">
                {{ $comparisons->total() }} total
            </span>

        </div>

        <div class="table-wrap">

            <table class="data-table">

                <thead>
                    <tr>
                        <th>Comparison</th>
                        <th>Type</th>
                        <th>Views</th>
                        <th>Last Updated</th>
                        <th>Status</th>
                        <th style="text-align:right;">Actions</th>
                    </tr>
                </thead>

                <tbody>

                @forelse ($comparisons as $comparison)

                    <tr>

                        {{-- TITLE --}}
                        <td>

                            <div class="comparison-title">

                                <span class="comparison-title__icon">
                                    <i data-lucide="scale"></i>
                                </span>

                                <div class="comparison-title__text">

                                    <a href="{{ route('admin.comparisons.show', $comparison->id) }}">
                                        {{ $comparison->title }}
                                    </a>

                                    <div class="comparison-title__sub">
                                        Comparison #{{ $comparison->id }}
                                    </div>

                                </div>

                            </div>

                        </td>

                        {{-- TYPE --}}
                        <td>

                            @if ($comparison->comparable_type === 'tool')

                                <span class="comparison-type">
                                    <i data-lucide="wrench"></i>
                                    Tool vs Tool
                                </span>

                            @else

                                <span class="comparison-type comparison-type--model">
                                    <i data-lucide="cpu"></i>
                                    Model vs Model
                                </span>

                            @endif

                        </td>

                        {{-- VIEWS --}}
                        <td>

                            <span class="comparison-views">
                                <i data-lucide="eye"></i>
                                {{ number_format($comparison->views) }}
                            </span>

                        </td>

                        {{-- DATE --}}
                        <td>

                            <span class="comparison-date">
                                {{ $comparison->updated_at->format('M j, Y') }}
                            </span>

                        </td>

                        {{-- STATUS --}}
                        <td>

                            <x-status-badge
                                status="{{ ucfirst($comparison->status) }}"
                                type="{{ $comparison->status === 'published' ? 'pos' : 'neutral' }}"
                            />

                        </td>

                        {{-- ACTIONS --}}
                        <td>

                            <div class="comparison-actions">

                                <a
                                    href="{{ route('admin.comparisons.show', $comparison->id) }}"
                                    class="icon-btn"
                                    title="View"
                                >
                                    <i data-lucide="eye"></i>
                                </a>

                                <a
                                    href="{{ route('admin.comparisons.edit', $comparison->id) }}"
                                    class="icon-btn"
                                    title="Edit"
                                >
                                    <i data-lucide="pencil"></i>
                                </a>

                                <form
                                    action="{{ route('admin.comparisons.destroy', $comparison->id) }}"
                                    method="POST"
                                    onsubmit="return confirm('Delete this comparison?')"
                                    style="display:inline;"
                                >

                                    @csrf
                                    @method('DELETE')

                                    <button
                                        type="submit"
                                        class="icon-btn icon-btn--danger"
                                        title="Delete"
                                    >
                                        <i data-lucide="trash-2"></i>
                                    </button>

                                </form>

                            </div>

                        </td>

                    </tr>

                @empty

                    <tr>

                        <td colspan="6" class="comparisons-empty">

                            <span class="comparisons-empty__icon">
                                <i data-lucide="git-compare"></i>
                            </span>

                            <strong>No comparisons found</strong>

                            <span>
                                Create your first AI tool or model comparison to get started.
                            </span>

                        </td>

                    </tr>

                @endforelse

                </tbody>

            </table>

        </div>

        {{-- PAGINATION --}}
        <div class="comparisons-pagination">

            <div class="comparisons-pagination__info">

                Showing
                <strong>{{ $comparisons->firstItem() ?? 0 }}</strong>
                –
                <strong>{{ $comparisons->lastItem() ?? 0 }}</strong>
                of
                <strong>{{ $comparisons->total() }}</strong>

            </div>

            <div>
                {{ $comparisons->links() }}
            </div>

        </div>

    </section>

</div>

@endsection