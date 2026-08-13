@extends('layouts.admin')
@section('title', 'Benchmarks')

@section('content')

<style>
    .benchmarks-page {
        --bm-primary: #6366f1;
        --bm-primary-light: #a5b4fc;
        --bm-text: #eef1f7;
        --bm-muted: #7f8ba0;
        --bm-border: rgba(255,255,255,.065);
        --bm-surface: rgba(255,255,255,.025);
    }

    .benchmarks-page * {
        box-sizing: border-box;
    }

    /* =========================
       HEADER
    ========================== */

    .benchmarks-page .bm-header {
        display: flex;
        align-items: flex-end;
        justify-content: space-between;
        gap: 20px;
        margin-bottom: 24px;
    }

    .benchmarks-page .bm-title-wrap {
        display: flex;
        align-items: flex-start;
        gap: 14px;
    }

    .benchmarks-page .bm-title-icon {
        width: 46px;
        height: 46px;
        flex: 0 0 46px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #a5b4fc;
        background: linear-gradient(
            145deg,
            rgba(99,102,241,.18),
            rgba(139,92,246,.08)
        );
        border: 1px solid rgba(129,140,248,.17);
        border-radius: 14px;
        box-shadow: 0 12px 30px rgba(0,0,0,.14);
    }

    .benchmarks-page .bm-title-icon svg {
        width: 21px;
        height: 21px;
    }

    .benchmarks-page .bm-kicker {
        margin-bottom: 4px;
        color: #778298;
        font-size: 10px;
        font-weight: 750;
        letter-spacing: .12em;
        text-transform: uppercase;
    }

    .benchmarks-page .bm-title {
        margin: 0;
        color: var(--bm-text);
        font-size: 25px;
        line-height: 1.2;
        font-weight: 750;
        letter-spacing: -.025em;
    }

    .benchmarks-page .bm-subtitle {
        margin: 7px 0 0;
        color: var(--bm-muted);
        font-size: 13px;
    }

    .benchmarks-page .bm-create-btn {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        min-height: 39px;
        padding: 0 15px;
        border-radius: 10px;
        font-size: 12px;
        font-weight: 700;
        box-shadow: 0 8px 22px rgba(99,102,241,.16);
    }

    .benchmarks-page .bm-create-btn svg {
        width: 15px;
        height: 15px;
    }

    /* =========================
       SUCCESS
    ========================== */

    .benchmarks-page .bm-success {
        display: flex;
        align-items: center;
        gap: 10px;
        margin-bottom: 18px;
        padding: 12px 14px;
        color: #86efac;
        background: rgba(34,197,94,.055);
        border: 1px solid rgba(34,197,94,.12);
        border-radius: 11px;
        font-size: 12px;
    }

    .benchmarks-page .bm-success-icon {
        width: 27px;
        height: 27px;
        display: flex;
        align-items: center;
        justify-content: center;
        flex: 0 0 27px;
        background: rgba(34,197,94,.09);
        border-radius: 7px;
    }

    .benchmarks-page .bm-success-icon svg {
        width: 14px;
        height: 14px;
    }

    /* =========================
       FILTER PANEL
    ========================== */

    .benchmarks-page .bm-filter-panel {
        position: relative;
        overflow: hidden;
        margin-bottom: 20px;
        padding: 16px;
        background:
            linear-gradient(
                145deg,
                rgba(255,255,255,.035),
                rgba(255,255,255,.015)
            );
        border: 1px solid var(--bm-border);
        border-radius: 14px;
    }

    .benchmarks-page .bm-filter-panel::after {
        content: "";
        position: absolute;
        top: -90px;
        right: -70px;
        width: 190px;
        height: 190px;
        border-radius: 50%;
        background: rgba(99,102,241,.04);
        pointer-events: none;
    }

    .benchmarks-page .bm-filter-row {
        position: relative;
        z-index: 1;
        display: flex;
        align-items: center;
        gap: 10px;
        flex-wrap: wrap;
    }

    .benchmarks-page .bm-filter-row + .bm-filter-row {
        margin-top: 13px;
        padding-top: 13px;
        border-top: 1px solid rgba(255,255,255,.045);
    }

    .benchmarks-page .bm-filter-label {
        min-width: 72px;
        color: #68758b;
        font-size: 10px;
        font-weight: 750;
        text-transform: uppercase;
        letter-spacing: .08em;
    }

    .benchmarks-page .bm-chip {
        display: inline-flex;
        align-items: center;
        gap: 7px;
        min-height: 31px;
        padding: 0 11px;
        color: #8792a6;
        background: rgba(255,255,255,.018);
        border: 1px solid rgba(255,255,255,.055);
        border-radius: 8px;
        font-size: 10.5px;
        font-weight: 650;
        text-decoration: none;
        transition: all .18s ease;
    }

    .benchmarks-page .bm-chip:hover {
        color: #dce2ed;
        background: rgba(255,255,255,.04);
        border-color: rgba(255,255,255,.09);
        transform: translateY(-1px);
    }

    .benchmarks-page .bm-chip.is-active {
        color: #eef0ff;
        background: linear-gradient(
            135deg,
            rgba(99,102,241,.20),
            rgba(139,92,246,.10)
        );
        border-color: rgba(129,140,248,.20);
        box-shadow: 0 5px 15px rgba(0,0,0,.10);
    }

    .benchmarks-page .bm-chip.is-active::before {
        content: "";
        width: 5px;
        height: 5px;
        border-radius: 50%;
        background: #818cf8;
        box-shadow: 0 0 8px rgba(129,140,248,.7);
    }

    /* =========================
       CONTENT GRID
    ========================== */

    .benchmarks-page .bm-grid {
        display: grid;
        grid-template-columns: minmax(0, 1.75fr) minmax(280px, .8fr);
        gap: 20px;
    }

    .benchmarks-page .bm-card {
        position: relative;
        overflow: hidden;
        background:
            linear-gradient(
                145deg,
                rgba(255,255,255,.035),
                rgba(255,255,255,.015)
            );
        border: 1px solid var(--bm-border);
        border-radius: 16px;
        box-shadow: 0 15px 40px rgba(0,0,0,.07);
    }

    /* =========================
       CARD HEADER
    ========================== */

    .benchmarks-page .bm-card-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 15px;
        padding: 17px 19px;
        border-bottom: 1px solid rgba(255,255,255,.055);
    }

    .benchmarks-page .bm-heading {
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .benchmarks-page .bm-heading-icon {
        width: 32px;
        height: 32px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #9faaff;
        background: rgba(99,102,241,.08);
        border: 1px solid rgba(129,140,248,.10);
        border-radius: 9px;
    }

    .benchmarks-page .bm-heading-icon svg {
        width: 15px;
        height: 15px;
    }

    .benchmarks-page .bm-card-title {
        margin: 0;
        color: #e9edf5;
        font-size: 13px;
        font-weight: 700;
    }

    .benchmarks-page .bm-card-title .bm-type {
        color: #68758a;
        font-weight: 450;
    }

    .benchmarks-page .bm-result-count {
        padding: 5px 8px;
        color: #7f8ba0;
        background: rgba(255,255,255,.025);
        border: 1px solid rgba(255,255,255,.045);
        border-radius: 7px;
        font-size: 9px;
        font-weight: 650;
    }

    /* =========================
       TABLE
    ========================== */

    .benchmarks-page .bm-table-wrap {
        overflow-x: auto;
    }

    .benchmarks-page .bm-table {
        width: 100%;
        border-collapse: collapse;
    }

    .benchmarks-page .bm-table th {
        height: 38px;
        padding: 0 17px;
        color: #68758a;
        background: rgba(0,0,0,.07);
        border-bottom: 1px solid rgba(255,255,255,.045);
        font-size: 9px;
        font-weight: 750;
        letter-spacing: .07em;
        text-align: left;
        text-transform: uppercase;
        white-space: nowrap;
    }

    .benchmarks-page .bm-table td {
        height: 64px;
        padding: 9px 17px;
        color: #cdd4df;
        border-bottom: 1px solid rgba(255,255,255,.04);
        font-size: 11.5px;
        vertical-align: middle;
    }

    .benchmarks-page .bm-table tbody tr {
        transition: background .16s ease;
    }

    .benchmarks-page .bm-table tbody tr:hover {
        background: rgba(255,255,255,.022);
    }

    .benchmarks-page .bm-table tbody tr:last-child td {
        border-bottom: 0;
    }

    /* =========================
       RANK
    ========================== */

    .benchmarks-page .bm-rank {
        width: 70px;
        color: #707c91 !important;
        font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
        font-size: 11px !important;
        font-weight: 700;
    }

    .benchmarks-page .bm-rank.top-1 {
        color: #facc15 !important;
    }

    .benchmarks-page .bm-rank.top-2 {
        color: #cbd5e1 !important;
    }

    .benchmarks-page .bm-rank.top-3 {
        color: #d6a46b !important;
    }

    /* =========================
       ITEM
    ========================== */

    .benchmarks-page .bm-item {
        display: flex;
        align-items: center;
        gap: 11px;
        min-width: 180px;
    }

    .benchmarks-page .bm-thumb {
        width: 36px;
        height: 36px;
        flex: 0 0 36px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #c7d2fe;
        background:
            linear-gradient(
                145deg,
                rgba(99,102,241,.17),
                rgba(139,92,246,.08)
            );
        border: 1px solid rgba(129,140,248,.12);
        border-radius: 10px;
        font-size: 10px;
        font-weight: 800;
        text-transform: uppercase;
    }

    .benchmarks-page .bm-item-name {
        display: block;
        color: #e3e8f1;
        font-size: 11.5px;
        font-weight: 650;
        line-height: 1.3;
    }

    .benchmarks-page .bm-company {
        margin-top: 3px;
        color: #68758a;
        font-size: 9.5px;
    }

    /* =========================
       SCORE
    ========================== */

    .benchmarks-page .bm-score {
        display: inline-flex;
        align-items: baseline;
        gap: 3px;
        padding: 6px 9px;
        color: #e5e7ff;
        background: rgba(99,102,241,.065);
        border: 1px solid rgba(129,140,248,.09);
        border-radius: 7px;
        font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
        font-size: 12px;
        font-weight: 750;
    }

    .benchmarks-page .bm-score-max {
        color: #626e83;
        font-size: 8px;
        font-weight: 500;
    }

    /* =========================
       EMPTY STATE
    ========================== */

    .benchmarks-page .bm-empty {
        padding: 50px 20px !important;
        text-align: center;
    }

    .benchmarks-page .bm-empty-icon {
        width: 44px;
        height: 44px;
        margin: 0 auto 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #69758b;
        background: rgba(255,255,255,.025);
        border: 1px solid rgba(255,255,255,.055);
        border-radius: 12px;
    }

    .benchmarks-page .bm-empty-icon svg {
        width: 19px;
        height: 19px;
    }

    .benchmarks-page .bm-empty-title {
        margin: 0 0 5px;
        color: #aeb7c7;
        font-size: 12px;
        font-weight: 650;
    }

    .benchmarks-page .bm-empty-text {
        margin: 0;
        color: #68758a;
        font-size: 10.5px;
    }

    /* =========================
       TOP 5 PANEL
    ========================== */

    .benchmarks-page .bm-side-card {
        padding: 19px;
    }

    .benchmarks-page .bm-side-head {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 10px;
        margin-bottom: 18px;
    }

    .benchmarks-page .bm-side-title {
        margin: 0;
        color: #e7ebf3;
        font-size: 13px;
        font-weight: 700;
    }

    .benchmarks-page .bm-side-subtitle {
        margin: 4px 0 0;
        color: #68758a;
        font-size: 10px;
    }

    .benchmarks-page .bm-trophy {
        width: 31px;
        height: 31px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #facc15;
        background: rgba(250,204,21,.07);
        border: 1px solid rgba(250,204,21,.09);
        border-radius: 9px;
    }

    .benchmarks-page .bm-trophy svg {
        width: 15px;
        height: 15px;
    }

    .benchmarks-page .bm-top-item {
        position: relative;
        margin-bottom: 13px;
        padding: 11px;
        background: rgba(255,255,255,.018);
        border: 1px solid rgba(255,255,255,.045);
        border-radius: 10px;
        transition: all .18s ease;
    }

    .benchmarks-page .bm-top-item:hover {
        background: rgba(255,255,255,.03);
        border-color: rgba(255,255,255,.075);
        transform: translateY(-1px);
    }

    .benchmarks-page .bm-top-item:last-child {
        margin-bottom: 0;
    }

    .benchmarks-page .bm-top-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
        margin-bottom: 9px;
    }

    .benchmarks-page .bm-top-name {
        overflow: hidden;
        color: #cfd6e2;
        font-size: 10.5px;
        font-weight: 600;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .benchmarks-page .bm-top-score {
        flex: 0 0 auto;
        color: #dfe3ff;
        font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
        font-size: 10.5px;
        font-weight: 750;
    }

    .benchmarks-page .bm-meter {
        height: 5px;
        overflow: hidden;
        background: rgba(255,255,255,.055);
        border-radius: 999px;
    }

    .benchmarks-page .bm-meter-fill {
        height: 100%;
        width: var(--score);
        background: linear-gradient(
            90deg,
            #4f46e5,
            #818cf8
        );
        border-radius: inherit;
        box-shadow: 0 0 10px rgba(99,102,241,.25);
    }

    .benchmarks-page .bm-top-rank {
        position: absolute;
        top: 10px;
        left: -1px;
        width: 3px;
        height: 25px;
        background: #6366f1;
        border-radius: 0 4px 4px 0;
    }

    .benchmarks-page .bm-side-empty {
        padding: 24px 5px 8px;
        color: #68758a;
        text-align: center;
        font-size: 10.5px;
        line-height: 1.6;
    }

    /* =========================
       RESPONSIVE
    ========================== */

    @media (max-width: 900px) {

        .benchmarks-page .bm-grid {
            grid-template-columns: 1fr;
        }
    }

    @media (max-width: 650px) {

        .benchmarks-page .bm-header {
            align-items: flex-start;
            flex-direction: column;
        }

        .benchmarks-page .bm-create-btn {
            width: 100%;
            justify-content: center;
        }

        .benchmarks-page .bm-filter-label {
            width: 100%;
        }

        .benchmarks-page .bm-table th,
        .benchmarks-page .bm-table td {
            padding-left: 12px;
            padding-right: 12px;
        }
    }
</style>


<div class="benchmarks-page">

    {{-- =========================
         PAGE HEADER
    ========================== --}}

    <div class="bm-header">

        <div class="bm-title-wrap">

            <div class="bm-title-icon">
                <i data-lucide="gauge"></i>
            </div>

            <div>
                <div class="bm-kicker">
                    Comparison & Benchmarks
                </div>

                <h1 class="bm-title">
                    Benchmarks
                </h1>

                <p class="bm-subtitle">
                    Performance rankings across standardized AI tests
                </p>
            </div>

        </div>

        <a
            href="{{ route('admin.benchmarks.create') }}"
            class="btn btn-primary btn-sm bm-create-btn"
        >
            <i data-lucide="plus"></i>
            Create Benchmark
        </a>

    </div>


    {{-- =========================
         SUCCESS MESSAGE
    ========================== --}}

    @if (session('status'))

        <div class="bm-success">

            <div class="bm-success-icon">
                <i data-lucide="check-circle-2"></i>
            </div>

            <span>
                {{ session('status') }}
            </span>

        </div>

    @endif


    {{-- =========================
         FILTERS
    ========================== --}}

    <div class="bm-filter-panel">

        {{-- TYPE FILTER --}}
        <div class="bm-filter-row">

            <span class="bm-filter-label">
                Category
            </span>

            <a
                href="{{ route('admin.benchmarks.index', ['type' => 'model']) }}"
                class="bm-chip {{ $type === 'model' ? 'is-active' : '' }}"
            >
                <i data-lucide="brain"></i>
                AI Models
            </a>

            <a
                href="{{ route('admin.benchmarks.index', ['type' => 'tool']) }}"
                class="bm-chip {{ $type === 'tool' ? 'is-active' : '' }}"
            >
                <i data-lucide="wrench"></i>
                AI Tools
            </a>

        </div>


        {{-- BENCHMARK FILTER --}}
        <div class="bm-filter-row">

            <span class="bm-filter-label">
                Benchmark
            </span>

            @foreach ($benchmarks as $benchmark)

                <a
                    href="{{ route('admin.benchmarks.index', [
                        'benchmark' => $benchmark,
                        'type' => $type
                    ]) }}"
                    class="bm-chip {{ $benchmark === $selected ? 'is-active' : '' }}"
                >
                    {{ $benchmark }}
                </a>

            @endforeach

        </div>

    </div>


    {{-- =========================
         MAIN CONTENT
    ========================== --}}

    <div class="bm-grid">

        {{-- =========================
             RANKINGS TABLE
        ========================== --}}

        <div class="bm-card">

            <div class="bm-card-head">

                <div class="bm-heading">

                    <div class="bm-heading-icon">
                        <i data-lucide="list-ordered"></i>
                    </div>

                    <h2 class="bm-card-title">

                        Rankings — {{ $selected }}

                        <span class="bm-type">
                            ({{ $type === 'tool' ? 'AI Tools' : 'AI Models' }})
                        </span>

                    </h2>

                </div>

                <span class="bm-result-count">
                    {{ $rankings->count() }} results
                </span>

            </div>


            <div class="bm-table-wrap">

                <table class="bm-table">

                    <thead>

                        <tr>

                            <th>Rank</th>

                            <th>
                                {{ $type === 'tool' ? 'Tool' : 'Model' }}
                            </th>

                            <th>
                                Score
                            </th>

                        </tr>

                    </thead>


                    <tbody>

                        @forelse ($rankings as $i => $item)

                            <tr>

                                <td
                                    class="bm-rank
                                    {{ $i === 0 ? 'top-1' : '' }}
                                    {{ $i === 1 ? 'top-2' : '' }}
                                    {{ $i === 2 ? 'top-3' : '' }}"
                                >
                                    #{{ $i + 1 }}
                                </td>


                                <td>

                                    <div class="bm-item">

                                        <div class="bm-thumb">
                                            {{ substr($item->name, 0, 2) }}
                                        </div>

                                        <div>

                                            <span class="bm-item-name">
                                                {{ $item->name }}
                                            </span>

                                            <div class="bm-company">
                                                {{ $item->company?->name ?? 'Independent' }}
                                            </div>

                                        </div>

                                    </div>

                                </td>


                                <td>

                                    <span class="bm-score">

                                        {{ $item->benchmarks[$selected] }}

                                        <span class="bm-score-max">
                                            /100
                                        </span>

                                    </span>

                                </td>

                            </tr>

                        @empty

                            <tr>

                                <td
                                    colspan="3"
                                    class="bm-empty"
                                >

                                    <div class="bm-empty-icon">
                                        <i data-lucide="database-zap"></i>
                                    </div>

                                    <p class="bm-empty-title">
                                        No benchmark scores yet
                                    </p>

                                    <p class="bm-empty-text">
                                        No scores recorded for {{ $selected }} yet.
                                    </p>

                                </td>

                            </tr>

                        @endforelse

                    </tbody>

                </table>

            </div>

        </div>


        {{-- =========================
             TOP 5
        ========================== --}}

        <div class="bm-card bm-side-card">

            <div class="bm-side-head">

                <div>

                    <h2 class="bm-side-title">
                        Top 5 — {{ $selected }}
                    </h2>

                    <p class="bm-side-subtitle">
                        Highest performing {{ $type === 'tool' ? 'tools' : 'models' }}
                    </p>

                </div>

                <div class="bm-trophy">
                    <i data-lucide="trophy"></i>
                </div>

            </div>


            @forelse ($rankings->take(5) as $index => $item)

                @php
                    $score = (float) $item->benchmarks[$selected];
                @endphp

                <div class="bm-top-item">

                    <span class="bm-top-rank"></span>

                    <div class="bm-top-head">

                        <span class="bm-top-name">
                            {{ $item->name }}
                        </span>

                        <span class="bm-top-score">
                            {{ $item->benchmarks[$selected] }}
                        </span>

                    </div>

                    <div class="bm-meter">

                        <div
                            class="bm-meter-fill"
                            style="--score: {{ min(100, max(0, $score)) }}%;"
                        ></div>

                    </div>

                </div>

            @empty

                <div class="bm-side-empty">

                    Nothing to show yet.

                    <br>

                    Add a score from the
                    <strong>Create Benchmark</strong>
                    form.

                </div>

            @endforelse

        </div>

    </div>

</div>


<script>
document.addEventListener('DOMContentLoaded', function () {

    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }

});
</script>

@endsection