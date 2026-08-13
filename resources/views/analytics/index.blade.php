@extends('layouts.admin')
@section('title', 'Analytics')

@section('content')
@php
    $tab = request()->is('*tools*') ? 'tools' : (request()->is('*search*') ? 'search' : (request()->is('*comparisons*') ? 'comparisons' : (request()->is('*content*') ? 'content' : (request()->is('*trending*') ? 'trending' : 'website'))));
    $titles = ['website'=>'Website Analytics','tools'=>'Tool Analytics','search'=>'Search Analytics','comparisons'=>'Comparison Analytics','content'=>'Content Analytics','trending'=>'Trending Searches'];
@endphp

<style>
    /* =========================================================
       ANALYTICS PAGE — UI UPGRADE
       Existing classes / functionality preserved
    ========================================================= */

    .analytics-page {
        --analytics-primary: #6366f1;
        --analytics-primary-soft: rgba(99, 102, 241, .10);
        --analytics-success: #22c55e;
        --analytics-danger: #ef4444;
        --analytics-warning: #f59e0b;
        --analytics-cyan: #06b6d4;
        --analytics-text: #f4f7fb;
        --analytics-muted: #8d98ad;
        --analytics-border: rgba(255,255,255,.07);
        --analytics-surface: rgba(255,255,255,.025);
    }

    .analytics-page * {
        box-sizing: border-box;
    }

    /* Header */
    .analytics-page .analytics-header {
        margin-bottom: 24px;
    }

    .analytics-page .analytics-header-inner {
        display: flex;
        align-items: flex-end;
        justify-content: space-between;
        gap: 20px;
    }

    .analytics-page .analytics-heading {
        display: flex;
        align-items: flex-start;
        gap: 14px;
    }

    .analytics-page .analytics-heading-icon {
        width: 46px;
        height: 46px;
        flex: 0 0 46px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 14px;
        color: #a5b4fc;
        background: linear-gradient(
            145deg,
            rgba(99,102,241,.18),
            rgba(139,92,246,.08)
        );
        border: 1px solid rgba(129,140,248,.18);
        box-shadow: 0 10px 30px rgba(0,0,0,.14);
    }

    .analytics-page .analytics-heading-icon svg {
        width: 21px;
        height: 21px;
    }

    .analytics-page .analytics-kicker {
        margin: 0 0 4px;
        color: #79849a;
        font-size: 11px;
        font-weight: 700;
        letter-spacing: .12em;
        text-transform: uppercase;
    }

    .analytics-page .analytics-title {
        margin: 0;
        color: var(--analytics-text);
        font-size: 26px;
        line-height: 1.2;
        font-weight: 750;
        letter-spacing: -.025em;
    }

    .analytics-page .analytics-subtitle {
        margin: 7px 0 0;
        color: var(--analytics-muted);
        font-size: 13px;
    }

    .analytics-page .analytics-actions {
        display: flex;
        align-items: center;
        gap: 9px;
        flex-wrap: wrap;
    }

    .analytics-page .analytics-period {
        height: 38px;
        min-width: 125px;
        padding: 0 35px 0 13px;
        color: #dbe2ee;
        background-color: rgba(255,255,255,.035);
        border: 1px solid var(--analytics-border);
        border-radius: 10px;
        font-size: 12px;
        font-weight: 600;
        outline: none;
        cursor: pointer;
    }

    .analytics-page .analytics-export {
        height: 38px;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 0 14px;
        border-radius: 10px;
        font-size: 12px;
        font-weight: 650;
    }

    .analytics-page .analytics-export svg {
        width: 15px;
        height: 15px;
    }

    /* Tabs */
    .analytics-page .analytics-tabs {
        display: flex;
        align-items: center;
        gap: 4px;
        padding: 5px;
        margin-bottom: 22px;
        overflow-x: auto;
        background: rgba(255,255,255,.025);
        border: 1px solid var(--analytics-border);
        border-radius: 13px;
        scrollbar-width: none;
    }

    .analytics-page .analytics-tabs::-webkit-scrollbar {
        display: none;
    }

    .analytics-page .analytics-tab {
        position: relative;
        min-height: 37px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 7px;
        padding: 0 14px;
        border-radius: 9px;
        color: #8994a9;
        text-decoration: none;
        white-space: nowrap;
        font-size: 12px;
        font-weight: 650;
        transition: all .2s ease;
    }

    .analytics-page .analytics-tab:hover {
        color: #e9edf6;
        background: rgba(255,255,255,.035);
    }

    .analytics-page .analytics-tab.is-active {
        color: #eef1ff;
        background: linear-gradient(
            135deg,
            rgba(99,102,241,.20),
            rgba(139,92,246,.12)
        );
        border: 1px solid rgba(129,140,248,.16);
        box-shadow: 0 5px 18px rgba(0,0,0,.10);
    }

    .analytics-page .analytics-tab.is-active::after {
        content: "";
        position: absolute;
        left: 50%;
        bottom: -6px;
        width: 18px;
        height: 2px;
        border-radius: 20px;
        transform: translateX(-50%);
        background: #818cf8;
    }

    /* KPI */
    .analytics-page .analytics-kpis {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 14px;
        margin-bottom: 20px;
    }

    .analytics-page .analytics-kpi {
        position: relative;
        min-height: 142px;
        padding: 18px;
        overflow: hidden;
        background:
            linear-gradient(
                145deg,
                rgba(255,255,255,.045),
                rgba(255,255,255,.018)
            );
        border: 1px solid var(--analytics-border);
        border-radius: 15px;
        box-shadow: 0 12px 30px rgba(0,0,0,.08);
        transition: transform .2s ease, border-color .2s ease;
    }

    .analytics-page .analytics-kpi:hover {
        transform: translateY(-2px);
        border-color: rgba(129,140,248,.20);
    }

    .analytics-page .analytics-kpi::before {
        content: "";
        position: absolute;
        width: 110px;
        height: 110px;
        top: -55px;
        right: -35px;
        border-radius: 50%;
        background: rgba(99,102,241,.08);
        filter: blur(3px);
    }

    .analytics-page .analytics-kpi-top {
        position: relative;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
    }

    .analytics-page .analytics-kpi-icon {
        width: 37px;
        height: 37px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #a5b4fc;
        background: rgba(99,102,241,.10);
        border: 1px solid rgba(129,140,248,.12);
        border-radius: 10px;
    }

    .analytics-page .analytics-kpi-icon svg {
        width: 17px;
        height: 17px;
    }

    .analytics-page .analytics-kpi-delta {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        padding: 5px 7px;
        border-radius: 7px;
        font-size: 10px;
        font-weight: 750;
    }

    .analytics-page .analytics-kpi-delta.up {
        color: #6ee7a0;
        background: rgba(34,197,94,.08);
    }

    .analytics-page .analytics-kpi-delta.down {
        color: #fca5a5;
        background: rgba(239,68,68,.08);
    }

    .analytics-page .analytics-kpi-label {
        position: relative;
        margin-top: 17px;
        color: #8994a9;
        font-size: 11px;
        font-weight: 600;
    }

    .analytics-page .analytics-kpi-value {
        position: relative;
        margin-top: 5px;
        color: #f5f7fb;
        font-size: 24px;
        line-height: 1.15;
        font-weight: 750;
        letter-spacing: -.025em;
    }

    /* Chart */
    .analytics-page .analytics-chart-card {
        padding: 0;
        overflow: hidden;
        margin-bottom: 20px;
        background:
            linear-gradient(
                145deg,
                rgba(255,255,255,.038),
                rgba(255,255,255,.018)
            );
        border: 1px solid var(--analytics-border);
        border-radius: 16px;
        box-shadow: 0 12px 35px rgba(0,0,0,.08);
    }

    .analytics-page .analytics-chart-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 20px;
        padding: 18px 20px;
        border-bottom: 1px solid rgba(255,255,255,.055);
    }

    .analytics-page .analytics-chart-title {
        margin: 0;
        color: #edf1f8;
        font-size: 14px;
        font-weight: 700;
    }

    .analytics-page .analytics-chart-meta {
        display: flex;
        align-items: center;
        gap: 7px;
        color: #788399;
        font-size: 10px;
    }

    .analytics-page .analytics-chart-dot {
        width: 7px;
        height: 7px;
        border-radius: 50%;
        background: #818cf8;
        box-shadow: 0 0 10px rgba(129,140,248,.6);
    }

    .analytics-page .analytics-chart-body {
        padding: 18px 20px 15px;
    }

    /* Cards */
    .analytics-page .analytics-card {
        overflow: hidden;
        background:
            linear-gradient(
                145deg,
                rgba(255,255,255,.038),
                rgba(255,255,255,.016)
            );
        border: 1px solid var(--analytics-border);
        border-radius: 16px;
        box-shadow: 0 12px 35px rgba(0,0,0,.07);
    }

    .analytics-page .analytics-card-head {
        min-height: 63px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 15px;
        padding: 0 19px;
        border-bottom: 1px solid rgba(255,255,255,.055);
    }

    .analytics-page .analytics-card-head h3 {
        margin: 0;
        color: #edf1f8;
        font-size: 14px;
        font-weight: 700;
    }

    .analytics-page .analytics-card-head span {
        color: #68748b;
        font-size: 10px;
    }

    /* Tables */
    .analytics-page .analytics-table-wrap {
        overflow-x: auto;
    }

    .analytics-page .analytics-table {
        width: 100%;
        min-width: 600px;
        border-collapse: collapse;
    }

    .analytics-page .analytics-table thead th {
        padding: 12px 18px;
        color: #69758b;
        background: rgba(255,255,255,.018);
        border-bottom: 1px solid rgba(255,255,255,.05);
        font-size: 9px;
        font-weight: 750;
        letter-spacing: .09em;
        text-align: left;
        text-transform: uppercase;
    }

    .analytics-page .analytics-table tbody td {
        padding: 14px 18px;
        color: #cbd3e1;
        border-bottom: 1px solid rgba(255,255,255,.045);
        font-size: 12px;
    }

    .analytics-page .analytics-table tbody tr:last-child td {
        border-bottom: 0;
    }

    .analytics-page .analytics-table tbody tr {
        transition: background .18s ease;
    }

    .analytics-page .analytics-table tbody tr:hover {
        background: rgba(255,255,255,.025);
    }

    .analytics-page .analytics-table .rank {
        color: #59657b;
        font-size: 11px;
        font-weight: 700;
    }

    .analytics-page .analytics-table .item-name {
        color: #edf1f7;
        font-weight: 650;
    }

    .analytics-page .analytics-table .number {
        color: #bfc8d8;
        font-variant-numeric: tabular-nums;
    }

    .analytics-page .analytics-growth {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: 4px 7px;
        border-radius: 6px;
        font-size: 10px;
        font-weight: 750;
    }

    .analytics-page .analytics-growth.positive {
        color: #6ee7a0;
        background: rgba(34,197,94,.075);
    }

    .analytics-page .analytics-growth.negative {
        color: #fca5a5;
        background: rgba(239,68,68,.075);
    }

    .analytics-page .analytics-related {
        color: #7f8ba0;
    }

    /* Search page */
    .analytics-page .search-kpis {
        margin-bottom: 20px;
    }

    .analytics-page .search-table-card {
        margin-bottom: 20px;
    }

    .analytics-page .query-cell {
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .analytics-page .query-icon {
        width: 28px;
        height: 28px;
        display: flex;
        align-items: center;
        justify-content: center;
        flex: 0 0 28px;
        color: #8b9cff;
        background: rgba(99,102,241,.08);
        border-radius: 8px;
    }

    .analytics-page .query-icon svg {
        width: 13px;
        height: 13px;
    }

    .analytics-page .query-text {
        color: #e6ebf4;
        font-weight: 650;
    }

    /* Lower Grid */
    .analytics-page .analytics-lower-grid {
        display: grid;
        grid-template-columns: minmax(0, 2fr) minmax(280px, 1fr);
        gap: 20px;
    }

    .analytics-page .sources-card {
        min-height: 100%;
    }

    .analytics-page .sources-body {
        padding: 18px 20px 12px;
    }

    .analytics-page .sources-chart-wrap {
        position: relative;
        height: 240px;
    }

    /* Mini status */
    .analytics-page .live-status {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 5px 8px;
        color: #7ee5a4;
        background: rgba(34,197,94,.06);
        border: 1px solid rgba(34,197,94,.10);
        border-radius: 7px;
        font-size: 9px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .05em;
    }

    .analytics-page .live-status-dot {
        width: 6px;
        height: 6px;
        border-radius: 50%;
        background: #22c55e;
        box-shadow: 0 0 8px rgba(34,197,94,.65);
    }

    /* Responsive */
    @media (max-width: 1100px) {
        .analytics-page .analytics-kpis {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .analytics-page .analytics-lower-grid {
            grid-template-columns: 1fr;
        }
    }

    @media (max-width: 760px) {
        .analytics-page .analytics-header-inner {
            align-items: flex-start;
            flex-direction: column;
        }

        .analytics-page .analytics-actions {
            width: 100%;
        }

        .analytics-page .analytics-period {
            flex: 1;
        }

        .analytics-page .analytics-export {
            justify-content: center;
        }

        .analytics-page .analytics-title {
            font-size: 22px;
        }

        .analytics-page .analytics-kpis {
            grid-template-columns: 1fr;
        }

        .analytics-page .analytics-chart-head {
            align-items: flex-start;
            flex-direction: column;
        }

        .analytics-page .analytics-chart-body {
            padding: 14px 10px;
        }
    }

    @media (max-width: 480px) {
        .analytics-page .analytics-heading-icon {
            width: 40px;
            height: 40px;
            flex-basis: 40px;
        }

        .analytics-page .analytics-title {
            font-size: 20px;
        }

        .analytics-page .analytics-actions {
            flex-direction: column;
            align-items: stretch;
        }

        .analytics-page .analytics-period,
        .analytics-page .analytics-export {
            width: 100%;
        }

        .analytics-page .analytics-tab {
            padding: 0 11px;
        }
    }
</style>

<div class="analytics-page">

    {{-- =========================================================
         PAGE HEADER
    ========================================================== --}}
    <div class="analytics-header">
        <div class="analytics-header-inner">

            <div class="analytics-heading">
                <div class="analytics-heading-icon">
                    <i data-lucide="chart-no-axes-combined"></i>
                </div>

                <div>
                    <div class="analytics-kicker">Performance Center</div>
                    <h1 class="analytics-title">{{ $titles[$tab] }}</h1>
                    <p class="analytics-subtitle">
                        Last 30 days · compared to prior period
                    </p>
                </div>
            </div>

            <div class="analytics-actions">
                <select class="analytics-period select">
                    <option>30 Days</option>
                    <option>7 Days</option>
                    <option>3 Months</option>
                    <option>1 Year</option>
                </select>

                <button class="btn btn-secondary btn-sm analytics-export">
                    <i data-lucide="download"></i>
                    Export Report
                </button>
            </div>

        </div>
    </div>

    {{-- =========================================================
         ANALYTICS TABS
    ========================================================== --}}
    <div class="analytics-tabs">

        <a href="{{ url('/analytics/website') }}"
           class="analytics-tab {{ $tab==='website'?'is-active':'' }}">
            <i data-lucide="globe-2"></i>
            Website
        </a>

        <a href="{{ url('/analytics/tools') }}"
           class="analytics-tab {{ $tab==='tools'?'is-active':'' }}">
            <i data-lucide="wrench"></i>
            Tool
        </a>

        <a href="{{ url('/analytics/search') }}"
           class="analytics-tab {{ $tab==='search'?'is-active':'' }}">
            <i data-lucide="search"></i>
            Search
        </a>

        <a href="{{ url('/analytics/comparisons') }}"
           class="analytics-tab {{ $tab==='comparisons'?'is-active':'' }}">
            <i data-lucide="columns-3"></i>
            Comparison
        </a>

        <a href="{{ url('/analytics/content') }}"
           class="analytics-tab {{ $tab==='content'?'is-active':'' }}">
            <i data-lucide="file-text"></i>
            Content
        </a>

        <a href="{{ url('/analytics/trending') }}"
           class="analytics-tab {{ $tab==='trending'?'is-active':'' }}">
            <i data-lucide="flame"></i>
            Trending Searches
        </a>

    </div>


    {{-- =========================================================
         WEBSITE / TOOLS / COMPARISONS / CONTENT
    ========================================================== --}}
    @if($tab !== 'search' && $tab !== 'trending')

        <div class="analytics-kpis">

            @if($tab==='website')

                <div class="analytics-kpi">
                    <div class="analytics-kpi-top">
                        <div class="analytics-kpi-icon">
                            <i data-lucide="users"></i>
                        </div>
                        <span class="analytics-kpi-delta up">↗ +9.2%</span>
                    </div>
                    <div class="analytics-kpi-label">Visitors</div>
                    <div class="analytics-kpi-value">482K</div>
                </div>

                <div class="analytics-kpi">
                    <div class="analytics-kpi-top">
                        <div class="analytics-kpi-icon">
                            <i data-lucide="eye"></i>
                        </div>
                        <span class="analytics-kpi-delta up">↗ +11.4%</span>
                    </div>
                    <div class="analytics-kpi-label">Page Views</div>
                    <div class="analytics-kpi-value">1.9M</div>
                </div>

                <div class="analytics-kpi">
                    <div class="analytics-kpi-top">
                        <div class="analytics-kpi-icon">
                            <i data-lucide="mouse-pointer-click"></i>
                        </div>
                        <span class="analytics-kpi-delta up">↗ +0.4%</span>
                    </div>
                    <div class="analytics-kpi-label">CTR</div>
                    <div class="analytics-kpi-value">4.8%</div>
                </div>

                <div class="analytics-kpi">
                    <div class="analytics-kpi-top">
                        <div class="analytics-kpi-icon">
                            <i data-lucide="timer"></i>
                        </div>
                        <span class="analytics-kpi-delta down">↘ -6%</span>
                    </div>
                    <div class="analytics-kpi-label">Avg. Session</div>
                    <div class="analytics-kpi-value">3m 42s</div>
                </div>

            @elseif($tab==='tools')

                <div class="analytics-kpi">
                    <div class="analytics-kpi-top">
                        <div class="analytics-kpi-icon">
                            <i data-lucide="wrench"></i>
                        </div>
                        <span class="analytics-kpi-delta up">↗ +7.8%</span>
                    </div>
                    <div class="analytics-kpi-label">Tool Views</div>
                    <div class="analytics-kpi-value">6.1M</div>
                </div>

                <div class="analytics-kpi">
                    <div class="analytics-kpi-top">
                        <div class="analytics-kpi-icon">
                            <i data-lucide="star"></i>
                        </div>
                        <span class="analytics-kpi-delta up">↗ +0.1</span>
                    </div>
                    <div class="analytics-kpi-label">Avg Rating</div>
                    <div class="analytics-kpi-value">4.4</div>
                </div>

                <div class="analytics-kpi">
                    <div class="analytics-kpi-top">
                        <div class="analytics-kpi-icon">
                            <i data-lucide="columns-3"></i>
                        </div>
                        <span class="analytics-kpi-delta up">↗ +15.2%</span>
                    </div>
                    <div class="analytics-kpi-label">Compare Clicks</div>
                    <div class="analytics-kpi-value">212K</div>
                </div>

                <div class="analytics-kpi">
                    <div class="analytics-kpi-top">
                        <div class="analytics-kpi-icon">
                            <i data-lucide="external-link"></i>
                        </div>
                        <span class="analytics-kpi-delta up">↗ +4.1%</span>
                    </div>
                    <div class="analytics-kpi-label">Outbound Clicks</div>
                    <div class="analytics-kpi-value">88K</div>
                </div>

            @elseif($tab==='comparisons')

                <div class="analytics-kpi">
                    <div class="analytics-kpi-top">
                        <div class="analytics-kpi-icon">
                            <i data-lucide="columns-3"></i>
                        </div>
                        <span class="analytics-kpi-delta up">↗ +6.7%</span>
                    </div>
                    <div class="analytics-kpi-label">Comparison Views</div>
                    <div class="analytics-kpi-value">904K</div>
                </div>

                <div class="analytics-kpi">
                    <div class="analytics-kpi-top">
                        <div class="analytics-kpi-icon">
                            <i data-lucide="square-stack"></i>
                        </div>
                        <span class="analytics-kpi-delta up">↗ +3.2%</span>
                    </div>
                    <div class="analytics-kpi-label">Comparisons Built</div>
                    <div class="analytics-kpi-value">905</div>
                </div>

                <div class="analytics-kpi">
                    <div class="analytics-kpi-top">
                        <div class="analytics-kpi-icon">
                            <i data-lucide="share-2"></i>
                        </div>
                        <span class="analytics-kpi-delta up">↗ +21%</span>
                    </div>
                    <div class="analytics-kpi-label">Shares</div>
                    <div class="analytics-kpi-value">12.4K</div>
                </div>

                <div class="analytics-kpi">
                    <div class="analytics-kpi-top">
                        <div class="analytics-kpi-icon">
                            <i data-lucide="clock"></i>
                        </div>
                        <span class="analytics-kpi-delta up">↗ +9%</span>
                    </div>
                    <div class="analytics-kpi-label">Avg Time on Page</div>
                    <div class="analytics-kpi-value">2m 18s</div>
                </div>

            @else

                <div class="analytics-kpi">
                    <div class="analytics-kpi-top">
                        <div class="analytics-kpi-icon">
                            <i data-lucide="file-text"></i>
                        </div>
                        <span class="analytics-kpi-delta up">↗ +5.5%</span>
                    </div>
                    <div class="analytics-kpi-label">Article Views</div>
                    <div class="analytics-kpi-value">3.4M</div>
                </div>

                <div class="analytics-kpi">
                    <div class="analytics-kpi-top">
                        <div class="analytics-kpi-icon">
                            <i data-lucide="message-square-heart"></i>
                        </div>
                        <span class="analytics-kpi-delta up">↗ +2.9%</span>
                    </div>
                    <div class="analytics-kpi-label">Review Views</div>
                    <div class="analytics-kpi-value">1.1M</div>
                </div>

                <div class="analytics-kpi">
                    <div class="analytics-kpi-top">
                        <div class="analytics-kpi-icon">
                            <i data-lucide="share-2"></i>
                        </div>
                        <span class="analytics-kpi-delta up">↗ +18%</span>
                    </div>
                    <div class="analytics-kpi-label">Social Shares</div>
                    <div class="analytics-kpi-value">41K</div>
                </div>

                <div class="analytics-kpi">
                    <div class="analytics-kpi-top">
                        <div class="analytics-kpi-icon">
                            <i data-lucide="bar-chart-3"></i>
                        </div>
                        <span class="analytics-kpi-delta up">↗ +2%</span>
                    </div>
                    <div class="analytics-kpi-label">Avg. Read Time</div>
                    <div class="analytics-kpi-value">4m 05s</div>
                </div>

            @endif

        </div>


        {{-- Trend Chart --}}
        <div class="analytics-chart-card">

            <div class="analytics-chart-head">
                <div>
                    <h3 class="analytics-chart-title">
                        {{ $titles[$tab] }} Trend
                    </h3>
                    <div class="analytics-chart-meta">
                        <span class="analytics-chart-dot"></span>
                        Performance over selected period
                    </div>
                </div>

                <span class="live-status">
                    <span class="live-status-dot"></span>
                    Updated
                </span>
            </div>

            <div class="analytics-chart-body">
                <canvas id="analyticsChart" height="90"></canvas>
            </div>

        </div>

    @endif


    {{-- =========================================================
         SEARCH / TRENDING
    ========================================================== --}}
    @if($tab==='search' || $tab==='trending')

        <div class="analytics-kpis search-kpis">

            <div class="analytics-kpi">
                <div class="analytics-kpi-top">
                    <div class="analytics-kpi-icon">
                        <i data-lucide="search"></i>
                    </div>
                    <span class="analytics-kpi-delta up">↗ +24.1%</span>
                </div>
                <div class="analytics-kpi-label">Total Searches</div>
                <div class="analytics-kpi-value">1.2M</div>
            </div>

            <div class="analytics-kpi">
                <div class="analytics-kpi-top">
                    <div class="analytics-kpi-icon">
                        <i data-lucide="flame"></i>
                    </div>
                    <span class="analytics-kpi-delta up">↗ +12</span>
                </div>
                <div class="analytics-kpi-label">Trending Queries</div>
                <div class="analytics-kpi-value">86</div>
            </div>

            <div class="analytics-kpi">
                <div class="analytics-kpi-top">
                    <div class="analytics-kpi-icon">
                        <i data-lucide="circle-slash"></i>
                    </div>
                    <span class="analytics-kpi-delta down">↘ +3.8%</span>
                </div>
                <div class="analytics-kpi-label">Zero-Result Searches</div>
                <div class="analytics-kpi-value">4,102</div>
            </div>

            <div class="analytics-kpi">
                <div class="analytics-kpi-top">
                    <div class="analytics-kpi-icon">
                        <i data-lucide="target"></i>
                    </div>
                    <span class="analytics-kpi-delta up">↗ +2.1%</span>
                </div>
                <div class="analytics-kpi-label">Search → Tool Conversion</div>
                <div class="analytics-kpi-value">38.4%</div>
            </div>

        </div>


        <div class="analytics-card search-table-card">

            <div class="analytics-card-head">
                <div>
                    <h3>Top &amp; Trending Searches</h3>
                </div>

                <span>Last 30 days</span>
            </div>

            <div class="analytics-table-wrap">
                <table class="analytics-table">

                    <thead>
                        <tr>
                            <th>Search Query</th>
                            <th>Volume</th>
                            <th>Growth</th>
                            <th>Related Tool</th>
                        </tr>
                    </thead>

                    <tbody>

                        <tr>
                            <td>
                                <div class="query-cell">
                                    <div class="query-icon">
                                        <i data-lucide="search"></i>
                                    </div>
                                    <span class="query-text">best AI video generator</span>
                                </div>
                            </td>
                            <td class="number">12,450</td>
                            <td>
                                <span class="analytics-growth positive">↗ +38%</span>
                            </td>
                            <td class="analytics-related">Runway Gen-4</td>
                        </tr>

                        <tr>
                            <td>
                                <div class="query-cell">
                                    <div class="query-icon">
                                        <i data-lucide="search"></i>
                                    </div>
                                    <span class="query-text">claude vs chatgpt</span>
                                </div>
                            </td>
                            <td class="number">9,820</td>
                            <td>
                                <span class="analytics-growth positive">↗ +21%</span>
                            </td>
                            <td class="analytics-related">Claude</td>
                        </tr>

                        <tr>
                            <td>
                                <div class="query-cell">
                                    <div class="query-icon">
                                        <i data-lucide="search"></i>
                                    </div>
                                    <span class="query-text">free ai image generator</span>
                                </div>
                            </td>
                            <td class="number">8,110</td>
                            <td>
                                <span class="analytics-growth positive">↗ +14%</span>
                            </td>
                            <td class="analytics-related">Ideogram v3</td>
                        </tr>

                        <tr>
                            <td>
                                <div class="query-cell">
                                    <div class="query-icon">
                                        <i data-lucide="search"></i>
                                    </div>
                                    <span class="query-text">ai coding assistant</span>
                                </div>
                            </td>
                            <td class="number">6,730</td>
                            <td>
                                <span class="analytics-growth positive">↗ +9%</span>
                            </td>
                            <td class="analytics-related">CodePilot X</td>
                        </tr>

                        <tr>
                            <td>
                                <div class="query-cell">
                                    <div class="query-icon">
                                        <i data-lucide="search"></i>
                                    </div>
                                    <span class="query-text">ai agents for business</span>
                                </div>
                            </td>
                            <td class="number">5,290</td>
                            <td>
                                <span class="analytics-growth positive">↗ +52%</span>
                            </td>
                            <td class="analytics-related">—</td>
                        </tr>

                    </tbody>

                </table>
            </div>

        </div>

    @else

        {{-- =====================================================
             TOP ITEMS + TRAFFIC SOURCES
        ====================================================== --}}
        <div class="analytics-lower-grid">

            <div class="analytics-card">

                <div class="analytics-card-head">

                    <h3>
                        Top
                        {{ $tab==='website'
                            ? 'Pages'
                            : ($tab==='tools'
                                ? 'Tools'
                                : ($tab==='comparisons'
                                    ? 'Comparisons'
                                    : 'Articles')) }}
                    </h3>

                    <span>Highest performing content</span>

                </div>

                <div class="analytics-table-wrap">

                    <table class="analytics-table">

                        <thead>
                            <tr>
                                <th>#</th>
                                <th>{{ $tab==='website' ? 'Page' : 'Item' }}</th>
                                <th>Views</th>
                                <th>Growth</th>
                            </tr>
                        </thead>

                        <tbody>

                            <tr>
                                <td class="rank">01</td>
                                <td class="item-name">ChatGPT vs Claude</td>
                                <td class="number">128,402</td>
                                <td>
                                    <span class="analytics-growth positive">↗ +18%</span>
                                </td>
                            </tr>

                            <tr>
                                <td class="rank">02</td>
                                <td class="item-name">Best AI Video Generators 2026</td>
                                <td class="number">94,220</td>
                                <td>
                                    <span class="analytics-growth positive">↗ +31%</span>
                                </td>
                            </tr>

                            <tr>
                                <td class="rank">03</td>
                                <td class="item-name">Midjourney Review</td>
                                <td class="number">61,880</td>
                                <td>
                                    <span class="analytics-growth negative">↘ -4%</span>
                                </td>
                            </tr>

                        </tbody>

                    </table>

                </div>

            </div>


            {{-- Traffic Sources --}}
            <div class="analytics-card sources-card">

                <div class="analytics-card-head">
                    <h3>Traffic Sources</h3>

                    <span>Acquisition</span>
                </div>

                <div class="sources-body">

                    <div class="sources-chart-wrap">
                        <canvas id="sourcesChart"></canvas>
                    </div>

                </div>

            </div>

        </div>

    @endif

</div>
@endsection


@push('scripts')

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function () {

    /* =========================================================
       MAIN ANALYTICS CHART
    ========================================================== */

    const el = document.getElementById('analyticsChart');

    if (el) {

        new Chart(el, {
            type: 'bar',

            data: {
                labels: [
                    'Jul 08',
                    'Jul 12',
                    'Jul 16',
                    'Jul 20',
                    'Jul 24',
                    'Jul 28',
                    'Aug 01',
                    'Aug 05'
                ],

                datasets: [{
                    label: 'Value',

                    data: [
                        38,
                        45,
                        42,
                        51,
                        60,
                        55,
                        68,
                        74
                    ],

                    backgroundColor: 'rgba(99,102,241,.55)',
                    hoverBackgroundColor: 'rgba(129,140,248,.85)',

                    borderRadius: 7,
                    borderSkipped: false,
                    maxBarThickness: 30
                }]
            },

            options: {

                responsive: true,

                interaction: {
                    intersect: false,
                    mode: 'index'
                },

                plugins: {

                    legend: {
                        display: false
                    },

                    tooltip: {
                        backgroundColor: '#171b27',
                        titleColor: '#f4f7fb',
                        bodyColor: '#aab3c4',
                        borderColor: 'rgba(255,255,255,.08)',
                        borderWidth: 1,
                        padding: 11,
                        displayColors: false,
                        titleFont: {
                            size: 11,
                            weight: '600'
                        },
                        bodyFont: {
                            size: 11
                        }
                    }
                },

                scales: {

                    x: {
                        border: {
                            display: false
                        },

                        grid: {
                            display: false
                        },

                        ticks: {
                            color: '#69758b',
                            font: {
                                size: 10
                            }
                        }
                    },

                    y: {

                        beginAtZero: true,

                        border: {
                            display: false
                        },

                        grid: {
                            color: 'rgba(255,255,255,.045)'
                        },

                        ticks: {
                            color: '#69758b',
                            font: {
                                size: 10
                            }
                        }
                    }
                }
            }
        });
    }


    /* =========================================================
       TRAFFIC SOURCES CHART
    ========================================================== */

    const el2 = document.getElementById('sourcesChart');

    if (el2) {

        new Chart(el2, {

            type: 'doughnut',

            data: {

                labels: [
                    'Organic Search',
                    'Direct',
                    'Social',
                    'Referral'
                ],

                datasets: [{
                    data: [
                        52,
                        24,
                        15,
                        9
                    ],

                    backgroundColor: [
                        '#6366f1',
                        '#8b5cf6',
                        '#22d3ee',
                        '#5c6580'
                    ],

                    borderWidth: 0,

                    hoverOffset: 5
                }]
            },

            options: {

                responsive: true,

                maintainAspectRatio: false,

                cutout: '72%',

                plugins: {

                    legend: {
                        position: 'bottom',

                        labels: {
                            color: '#8d98ad',
                            boxWidth: 8,
                            boxHeight: 8,
                            padding: 14,
                            font: {
                                size: 10
                            }
                        }
                    },

                    tooltip: {
                        backgroundColor: '#171b27',
                        titleColor: '#f4f7fb',
                        bodyColor: '#aab3c4',
                        borderColor: 'rgba(255,255,255,.08)',
                        borderWidth: 1,
                        padding: 10
                    }
                }
            }
        });
    }


    /* =========================================================
       REFRESH LUCIDE ICONS
    ========================================================== */

    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }

});
</script>

@endpush