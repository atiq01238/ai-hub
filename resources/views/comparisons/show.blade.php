@extends('layouts.admin')
@section('title', $comparison->title . ' · Comparison')

@section('content')

<style>
    .comparison-detail {
        --cd-border: var(--border-soft, rgba(148,163,184,.14));
        --cd-muted: var(--muted, #8d98ad);
        --cd-text: var(--text, #eef2ff);
        --cd-blue: #6d8cff;
        --cd-cyan: #22d3ee;
    }

    .comparison-detail__hero {
        position: relative;
        overflow: hidden;
        display: flex;
        align-items: flex-end;
        justify-content: space-between;
        gap: 20px;
        margin-bottom: 15px;
        padding: 21px;
        border: 1px solid var(--cd-border);
        border-radius: 17px;
        background:
            radial-gradient(circle at 88% 0%, rgba(109,140,255,.17), transparent 28%),
            radial-gradient(circle at 60% 120%, rgba(34,211,238,.055), transparent 30%),
            linear-gradient(145deg, rgba(255,255,255,.045), rgba(255,255,255,.012));
        box-shadow: 0 16px 42px rgba(0,0,0,.07);
    }

    .comparison-detail__hero-copy {
        min-width: 0;
    }

    .comparison-detail__eyebrow {
        display: flex;
        align-items: center;
        gap: 7px;
        margin-bottom: 8px;
        color: var(--cd-cyan);
        font-size: 8px;
        font-weight: 800;
        letter-spacing: .13em;
        text-transform: uppercase;
    }

    .comparison-detail__dot {
        width: 6px;
        height: 6px;
        border-radius: 50%;
        background: #32d583;
        box-shadow: 0 0 0 4px rgba(50,213,131,.1);
    }

    .comparison-detail__hero-title {
        margin: 0;
        max-width: 850px;
        overflow: hidden;
        color: var(--cd-text);
        font-size: clamp(21px, 3vw, 29px);
        line-height: 1.15;
        letter-spacing: -.035em;
        font-weight: 800;
        text-overflow: ellipsis;
    }

    .comparison-detail__meta {
        display: flex;
        align-items: center;
        gap: 7px;
        flex-wrap: wrap;
        margin-top: 9px;
        color: var(--cd-muted);
        font-size: 8px;
    }

    .comparison-detail__meta-pill {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: 5px 8px;
        border: 1px solid var(--cd-border);
        border-radius: 6px;
        background: rgba(255,255,255,.018);
    }

    .comparison-detail__meta-pill svg {
        width: 11px;
        height: 11px;
    }

    .comparison-detail__hero-actions {
        display: flex;
        gap: 7px;
        flex-shrink: 0;
    }

    .comparison-detail__hero-actions .btn {
        min-height: 37px;
        border-radius: 9px;
    }

    .comparison-detail__intro {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        margin-bottom: 12px;
        padding: 11px 13px;
        border: 1px solid var(--cd-border);
        border-radius: 10px;
        background: rgba(255,255,255,.018);
    }

    .comparison-detail__intro-left {
        display: flex;
        align-items: center;
        gap: 9px;
        min-width: 0;
    }

    .comparison-detail__intro-icon {
        width: 29px;
        height: 29px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        flex: 0 0 29px;
        border-radius: 8px;
        color: #a9b7ff;
        background: rgba(109,140,255,.08);
    }

    .comparison-detail__intro-icon svg {
        width: 14px;
        height: 14px;
    }

    .comparison-detail__intro-title {
        color: var(--cd-text);
        font-size: 9.5px;
        font-weight: 800;
    }

    .comparison-detail__intro-subtitle {
        margin-top: 2px;
        color: var(--cd-muted);
        font-size: 7.5px;
    }

    .comparison-detail__type {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 6px 9px;
        border: 1px solid rgba(109,140,255,.14);
        border-radius: 7px;
        color: #aebcff;
        background: rgba(109,140,255,.055);
        font-size: 7.5px;
        font-weight: 750;
        white-space: nowrap;
    }

    .comparison-detail__type--model {
        border-color: rgba(34,211,238,.14);
        color: #8fe6f0;
        background: rgba(34,211,238,.045);
    }

    .comparison-detail__type svg {
        width: 11px;
        height: 11px;
    }

    .comparison-detail__table-card {
        overflow: hidden;
        border: 1px solid var(--cd-border);
        border-radius: 15px;
        background: linear-gradient(145deg, rgba(255,255,255,.035), rgba(255,255,255,.012));
        box-shadow: 0 14px 38px rgba(0,0,0,.055);
    }

    .comparison-detail__table-wrap {
        overflow-x: auto;
    }

    .comparison-detail__table {
        width: 100%;
        min-width: 760px;
        border-collapse: separate;
        border-spacing: 0;
    }

    .comparison-detail__table th,
    .comparison-detail__table td {
        border-right: 1px solid rgba(148,163,184,.065);
        border-bottom: 1px solid rgba(148,163,184,.075);
    }

    .comparison-detail__table th:last-child,
    .comparison-detail__table td:last-child {
        border-right: 0;
    }

    .comparison-detail__table tbody tr:last-child td {
        border-bottom: 0;
    }

    .comparison-detail__metric-head {
        width: 150px;
        padding: 14px;
        color: #77839a;
        background: rgba(255,255,255,.018);
        font-size: 7.5px;
        font-weight: 800;
        letter-spacing: .08em;
        text-transform: uppercase;
        vertical-align: bottom;
    }

    .comparison-detail__item-head {
        min-width: 190px;
        padding: 13px 14px;
        background:
            linear-gradient(180deg, rgba(109,140,255,.065), rgba(255,255,255,.012));
        vertical-align: top;
    }

    .comparison-detail__item {
        display: flex;
        align-items: center;
        gap: 9px;
    }

    .comparison-detail__item-avatar {
        width: 35px;
        height: 35px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        flex: 0 0 35px;
        border: 1px solid rgba(109,140,255,.16);
        border-radius: 9px;
        color: #aab8ff;
        background: rgba(109,140,255,.08);
        font-size: 8px;
        font-weight: 850;
        text-transform: uppercase;
    }

    .comparison-detail__item-info {
        min-width: 0;
    }

    .comparison-detail__item-name {
        display: block;
        overflow: hidden;
        color: var(--cd-text);
        font-size: 9.5px;
        font-weight: 800;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .comparison-detail__item-company {
        display: block;
        margin-top: 3px;
        overflow: hidden;
        color: var(--cd-muted);
        font-size: 7px;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .comparison-detail__metric {
        padding: 13px 14px;
        color: var(--cd-muted);
        background: rgba(255,255,255,.012);
        font-size: 8px;
        font-weight: 650;
        white-space: nowrap;
    }

    .comparison-detail__value {
        padding: 13px 14px;
        color: var(--cd-text);
        background: rgba(255,255,255,.008);
        font-size: 9px;
        vertical-align: middle;
    }

    .comparison-detail__value--number {
        font-weight: 750;
        font-variant-numeric: tabular-nums;
    }

    .comparison-detail__company {
        color: var(--cd-text);
        font-size: 8.5px;
        font-weight: 650;
    }

    .comparison-detail__score {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        color: var(--cd-text);
        font-weight: 800;
    }

    .comparison-detail__star {
        color: #f5c451;
    }

    .comparison-detail__progress {
        width: 115px;
        height: 6px;
        overflow: hidden;
        border-radius: 20px;
        background: rgba(255,255,255,.07);
    }

    .comparison-detail__progress span {
        display: block;
        height: 100%;
        border-radius: inherit;
        background: linear-gradient(90deg, var(--cd-blue), var(--cd-cyan));
    }

    .comparison-detail__price {
        color: #aebcff;
        font-weight: 750;
    }

    .comparison-detail__capabilities {
        display: flex;
        gap: 5px;
        flex-wrap: wrap;
    }

    .comparison-detail__capabilities .badge {
        font-size: 7px;
        padding: 4px 6px;
    }

    .comparison-detail__footer {
        display: flex;
        align-items: center;
        gap: 7px;
        padding: 10px 13px;
        border-top: 1px solid var(--cd-border);
        color: var(--cd-muted);
        background: rgba(255,255,255,.012);
        font-size: 7.5px;
    }

    .comparison-detail__footer svg {
        width: 12px;
        height: 12px;
        color: #7f91b7;
    }

    @media (max-width: 700px) {
        .comparison-detail__hero {
            align-items: flex-start;
            flex-direction: column;
        }

        .comparison-detail__hero-actions {
            width: 100%;
        }

        .comparison-detail__hero-actions .btn {
            flex: 1;
        }

        .comparison-detail__intro {
            align-items: flex-start;
            flex-direction: column;
        }

        .comparison-detail__type {
            width: 100%;
            justify-content: center;
        }
    }

    @media (max-width: 450px) {
        .comparison-detail__hero {
            padding: 17px;
        }

        .comparison-detail__hero-actions {
            flex-direction: column;
        }

        .comparison-detail__hero-actions .btn {
            width: 100%;
        }
    }
</style>

<div class="comparison-detail">

    {{-- HERO --}}
    <section class="comparison-detail__hero">

        <div class="comparison-detail__hero-copy">

            <div class="comparison-detail__eyebrow">
                <span class="comparison-detail__dot"></span>
                Comparison & Benchmarks · Analysis
            </div>

            <h1 class="comparison-detail__hero-title">
                {{ $comparison->title }}
            </h1>

            <div class="comparison-detail__meta">

                <span class="comparison-detail__meta-pill">
                    <i data-lucide="eye"></i>
                    {{ number_format($comparison->views) }} views
                </span>

                <span class="comparison-detail__meta-pill">
                    <i data-lucide="clock-3"></i>
                    Updated {{ $comparison->updated_at->diffForHumans() }}
                </span>

                <x-status-badge
                    status="{{ ucfirst($comparison->status) }}"
                    type="{{ $comparison->status === 'published' ? 'pos' : 'neutral' }}"
                />

            </div>

        </div>

        <div class="comparison-detail__hero-actions">

            <a
                href="{{ route('admin.comparisons.edit', $comparison->id) }}"
                class="btn btn-primary btn-sm"
            >
                <i data-lucide="pencil"></i>
                Edit
            </a>

            <form
                action="{{ route('admin.comparisons.destroy', $comparison->id) }}"
                method="POST"
                onsubmit="return confirm('Delete this comparison?')"
            >

                @csrf
                @method('DELETE')

                <button type="submit" class="btn btn-danger btn-sm">
                    <i data-lucide="trash-2"></i>
                    Delete
                </button>

            </form>

        </div>

    </section>

    {{-- COMPARISON INFO --}}
    <div class="comparison-detail__intro">

        <div class="comparison-detail__intro-left">

            <span class="comparison-detail__intro-icon">
                <i data-lucide="scale"></i>
            </span>

            <div>

                <div class="comparison-detail__intro-title">
                    Side-by-Side Comparison
                </div>

                <div class="comparison-detail__intro-subtitle">
                    Review key performance, pricing and capability metrics
                </div>

            </div>

        </div>

        @if ($comparison->comparable_type === 'tool')

            <span class="comparison-detail__type">
                <i data-lucide="wrench"></i>
                Tool vs Tool
            </span>

        @else

            <span class="comparison-detail__type comparison-detail__type--model">
                <i data-lucide="cpu"></i>
                Model vs Model
            </span>

        @endif

    </div>

    {{-- COMPARISON TABLE --}}
    <section class="comparison-detail__table-card">

        <div class="comparison-detail__table-wrap">

            <table class="comparison-detail__table">

                <thead>

                    <tr>

                        <th class="comparison-detail__metric-head">
                            Metric
                        </th>

                        @foreach ($items as $item)

                            <th class="comparison-detail__item-head">

                                <div class="comparison-detail__item">

                                    <div class="comparison-detail__item-avatar">
                                        {{ substr($item->name, 0, 2) }}
                                    </div>

                                    <div class="comparison-detail__item-info">

                                        <span class="comparison-detail__item-name">
                                            {{ $item->name }}
                                        </span>

                                        <span class="comparison-detail__item-company">
                                            {{ $item->company->name ?? 'Company not assigned' }}
                                        </span>

                                    </div>

                                </div>

                            </th>

                        @endforeach

                    </tr>

                </thead>

                <tbody>

                @if ($comparison->comparable_type === 'tool')

                    {{-- COMPANY --}}
                    <tr>

                        <td class="comparison-detail__metric">
                            Company
                        </td>

                        @foreach ($items as $item)

                            <td class="comparison-detail__value">
                                <span class="comparison-detail__company">
                                    {{ $item->company->name ?? '—' }}
                                </span>
                            </td>

                        @endforeach

                    </tr>

                    {{-- RATING --}}
                    <tr>

                        <td class="comparison-detail__metric">
                            Rating
                        </td>

                        @foreach ($items as $item)

                            <td class="comparison-detail__value">

                                <span class="comparison-detail__score">
                                    <span class="comparison-detail__star">★</span>
                                    {{ number_format($item->rating, 1) }}
                                </span>

                            </td>

                        @endforeach

                    </tr>

                    {{-- POPULARITY --}}
                    <tr>

                        <td class="comparison-detail__metric">
                            Popularity
                        </td>

                        @foreach ($items as $item)

                            <td class="comparison-detail__value">

                                <div style="display:flex; align-items:center; gap:8px;">

                                    <div class="comparison-detail__progress">
                                        <span style="width:{{ $item->popularity }}%;"></span>
                                    </div>

                                    <span style="font-size:7.5px; color:var(--cd-muted);">
                                        {{ $item->popularity }}%
                                    </span>

                                </div>

                            </td>

                        @endforeach

                    </tr>

                    {{-- PRICING --}}
                    <tr>

                        <td class="comparison-detail__metric">
                            Pricing
                        </td>

                        @foreach ($items as $item)

                            <td class="comparison-detail__value">

                                @if (!empty($item->pricing_models))

                                    <div class="comparison-detail__capabilities">

                                        @foreach ($item->pricing_models as $pricing)

                                            <span class="badge badge-neutral">
                                                {{ $pricing }}
                                            </span>

                                        @endforeach

                                    </div>

                                @else
                                    <span class="text-sub">—</span>
                                @endif

                            </td>

                        @endforeach

                    </tr>

                    {{-- CAPABILITIES --}}
                    <tr>

                        <td class="comparison-detail__metric">
                            Capabilities
                        </td>

                        @foreach ($items as $item)

                            <td class="comparison-detail__value">

                                <div class="comparison-detail__capabilities">

                                    @forelse ($item->capabilities ?? [] as $c)

                                        <span class="badge badge-neutral">
                                            {{ $c }}
                                        </span>

                                    @empty

                                        <span class="text-sub">—</span>

                                    @endforelse

                                </div>

                            </td>

                        @endforeach

                    </tr>

                @else

                    {{-- COMPANY --}}
                    <tr>

                        <td class="comparison-detail__metric">
                            Company
                        </td>

                        @foreach ($items as $item)

                            <td class="comparison-detail__value">

                                <span class="comparison-detail__company">
                                    {{ $item->company->name ?? '—' }}
                                </span>

                            </td>

                        @endforeach

                    </tr>

                    {{-- BENCHMARK --}}
                    <tr>

                        <td class="comparison-detail__metric">
                            Benchmark Score
                        </td>

                        @foreach ($items as $item)

                            <td class="comparison-detail__value comparison-detail__value--number">

                                {{ number_format($item->benchmark_score, 1) }}

                            </td>

                        @endforeach

                    </tr>

                    {{-- CONTEXT --}}
                    <tr>

                        <td class="comparison-detail__metric">
                            Context Window
                        </td>

                        @foreach ($items as $item)

                            <td class="comparison-detail__value comparison-detail__value--number">
                                {{ $item->context_window ?? '—' }}
                            </td>

                        @endforeach

                    </tr>

                    {{-- INPUT PRICE --}}
                    <tr>

                        <td class="comparison-detail__metric">
                            Input $ / 1M
                        </td>

                        @foreach ($items as $item)

                            <td class="comparison-detail__value">

                                @if ($item->input_price_per_million !== null)

                                    <span class="comparison-detail__price">
                                        ${{ number_format($item->input_price_per_million, 2) }}
                                    </span>

                                @else

                                    <span class="text-sub">—</span>

                                @endif

                            </td>

                        @endforeach

                    </tr>

                    {{-- OUTPUT PRICE --}}
                    <tr>

                        <td class="comparison-detail__metric">
                            Output $ / 1M
                        </td>

                        @foreach ($items as $item)

                            <td class="comparison-detail__value">

                                @if ($item->output_price_per_million !== null)

                                    <span class="comparison-detail__price">
                                        ${{ number_format($item->output_price_per_million, 2) }}
                                    </span>

                                @else

                                    <span class="text-sub">—</span>

                                @endif

                            </td>

                        @endforeach

                    </tr>

                    {{-- CAPABILITIES --}}
                    <tr>

                        <td class="comparison-detail__metric">
                            Capabilities
                        </td>

                        @foreach ($items as $item)

                            <td class="comparison-detail__value">

                                <div class="comparison-detail__capabilities">

                                    @forelse ($item->capabilities ?? [] as $c)

                                        <span class="badge badge-violet">
                                            {{ $c }}
                                        </span>

                                    @empty

                                        <span class="text-sub">—</span>

                                    @endforelse

                                </div>

                            </td>

                        @endforeach

                    </tr>

                @endif

                </tbody>

            </table>

        </div>

        <div class="comparison-detail__footer">

            <i data-lucide="info"></i>

            <span>
                Comparison data is based on the information currently stored in your AI Hub database.
            </span>

        </div>

    </section>

</div>

@endsection