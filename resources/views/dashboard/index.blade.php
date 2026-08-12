@extends('layouts.admin')
@section('title', 'Dashboard')

@section('content')

<style>
    /* =========================================================
       AI HUB — ADVANCED ADMIN DASHBOARD UI
       Presentation-only layer. Existing Blade logic/routes
       and database variables are intentionally preserved.
       ========================================================= */

    .ai-dashboard {
        --dash-card: var(--surface, #101522);
        --dash-card-2: var(--surface-2, #0c111d);
        --dash-border: var(--border-soft, rgba(148,163,184,.14));
        --dash-text: var(--text, #eef2ff);
        --dash-muted: var(--muted, #8d98ad);
        --dash-blue: #6d8cff;
        --dash-cyan: #22d3ee;
        --dash-purple: #9b7cff;
        --dash-green: #32d583;
        --dash-orange: #f5a524;
        --dash-red: #f97068;
        color: var(--dash-text);
    }

    .ai-dashboard .dashboard-hero {
        position: relative;
        overflow: hidden;
        padding: 24px;
        margin-bottom: 20px;
        border: 1px solid var(--dash-border);
        border-radius: 18px;
        background:
            radial-gradient(circle at 88% 15%, rgba(109,140,255,.18), transparent 30%),
            radial-gradient(circle at 65% 100%, rgba(34,211,238,.08), transparent 28%),
            linear-gradient(135deg, rgba(255,255,255,.045), rgba(255,255,255,.015));
        box-shadow: 0 18px 50px rgba(0,0,0,.14);
    }

    .ai-dashboard .dashboard-hero:after {
        content: "";
        position: absolute;
        width: 220px;
        height: 220px;
        right: -80px;
        top: -100px;
        border-radius: 50%;
        border: 1px solid rgba(109,140,255,.15);
        box-shadow: 0 0 0 35px rgba(109,140,255,.025), 0 0 0 70px rgba(109,140,255,.015);
        pointer-events: none;
    }

    .ai-dashboard .hero-inner {
        position: relative;
        z-index: 1;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 20px;
    }

    .ai-dashboard .hero-kicker {
        display: flex;
        align-items: center;
        gap: 8px;
        margin-bottom: 8px;
        color: var(--dash-cyan);
        font-size: 11px;
        font-weight: 800;
        letter-spacing: .12em;
        text-transform: uppercase;
    }

    .ai-dashboard .live-dot {
        width: 7px;
        height: 7px;
        border-radius: 50%;
        background: var(--dash-green);
        box-shadow: 0 0 0 5px rgba(50,213,131,.10), 0 0 14px rgba(50,213,131,.45);
    }

    .ai-dashboard .hero-title {
        margin: 0;
        font-size: clamp(24px, 3vw, 32px);
        line-height: 1.15;
        letter-spacing: -.035em;
        font-weight: 800;
    }

    .ai-dashboard .hero-subtitle {
        max-width: 680px;
        margin: 8px 0 0;
        color: var(--dash-muted);
        font-size: 13px;
        line-height: 1.65;
    }

    .ai-dashboard .hero-actions {
        display: flex;
        align-items: center;
        gap: 9px;
        flex-shrink: 0;
    }

    .ai-dashboard .advanced-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 7px;
        min-height: 38px;
        padding: 0 14px;
        border-radius: 10px;
        text-decoration: none;
        font-size: 12px;
        font-weight: 700;
        transition: .2s ease;
    }

    .ai-dashboard .advanced-btn svg {
        width: 15px;
        height: 15px;
    }

    .ai-dashboard .advanced-btn.primary {
        color: #fff;
        background: linear-gradient(135deg, #6d8cff, #536ff0);
        border: 1px solid rgba(255,255,255,.12);
        box-shadow: 0 8px 24px rgba(83,111,240,.22);
    }

    .ai-dashboard .advanced-btn.primary:hover {
        transform: translateY(-1px);
        box-shadow: 0 12px 28px rgba(83,111,240,.32);
    }

    .ai-dashboard .advanced-btn.secondary {
        color: var(--dash-text);
        background: rgba(255,255,255,.035);
        border: 1px solid var(--dash-border);
    }

    .ai-dashboard .advanced-btn.secondary:hover {
        background: rgba(255,255,255,.07);
        border-color: rgba(109,140,255,.35);
    }

    .ai-dashboard .section-label {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        margin: 0 0 10px;
    }

    .ai-dashboard .section-label h2 {
        margin: 0;
        font-size: 13px;
        font-weight: 800;
        letter-spacing: -.01em;
    }

    .ai-dashboard .section-label span {
        color: var(--dash-muted);
        font-size: 11px;
    }

    .ai-dashboard .advanced-kpi-grid {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 12px;
        margin-bottom: 20px;
    }

    .ai-dashboard .advanced-kpi {
        position: relative;
        min-width: 0;
        overflow: hidden;
        padding: 16px;
        border: 1px solid var(--dash-border);
        border-radius: 15px;
        background: linear-gradient(145deg, rgba(255,255,255,.045), rgba(255,255,255,.012));
        transition: transform .2s ease, border-color .2s ease, box-shadow .2s ease;
    }

    .ai-dashboard .advanced-kpi:hover {
        transform: translateY(-2px);
        border-color: rgba(109,140,255,.32);
        box-shadow: 0 15px 35px rgba(0,0,0,.14);
    }

    .ai-dashboard .advanced-kpi:after {
        content: "";
        position: absolute;
        width: 90px;
        height: 90px;
        right: -35px;
        bottom: -42px;
        border-radius: 50%;
        background: rgba(109,140,255,.08);
        pointer-events: none;
    }

    .ai-dashboard .kpi-top {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
        margin-bottom: 15px;
    }

    .ai-dashboard .kpi-icon-modern {
        display: grid;
        place-items: center;
        width: 34px;
        height: 34px;
        border-radius: 10px;
        color: #9fb1ff;
        background: rgba(109,140,255,.11);
        border: 1px solid rgba(109,140,255,.15);
    }

    .ai-dashboard .kpi-icon-modern svg {
        width: 17px;
        height: 17px;
    }

    .ai-dashboard .kpi-index {
        color: var(--dash-muted);
        font: 700 9px/1 ui-monospace, SFMono-Regular, Menlo, monospace;
        letter-spacing: .12em;
    }

    .ai-dashboard .kpi-label-modern {
        color: var(--dash-muted);
        font-size: 11px;
        font-weight: 650;
        margin-bottom: 4px;
    }

    .ai-dashboard .kpi-value-modern {
        font-size: 25px;
        line-height: 1;
        font-weight: 800;
        letter-spacing: -.04em;
    }

    .ai-dashboard .attention-card {
        display: flex;
        align-items: center;
        gap: 13px;
        padding: 14px 16px;
        margin-bottom: 20px;
        border: 1px solid rgba(245,165,36,.28);
        border-radius: 14px;
        background:
            radial-gradient(circle at 0% 50%, rgba(245,165,36,.09), transparent 35%),
            rgba(245,165,36,.035);
    }

    .ai-dashboard .attention-icon {
        display: grid;
        place-items: center;
        width: 38px;
        height: 38px;
        flex: 0 0 38px;
        border-radius: 11px;
        color: var(--dash-orange);
        background: rgba(245,165,36,.10);
        border: 1px solid rgba(245,165,36,.16);
    }

    .ai-dashboard .attention-copy {
        flex: 1;
        min-width: 0;
    }

    .ai-dashboard .attention-copy strong {
        display: block;
        margin-bottom: 3px;
        font-size: 12.5px;
    }

    .ai-dashboard .attention-copy span {
        color: var(--dash-muted);
        font-size: 11.5px;
    }

    .ai-dashboard .panel {
        overflow: hidden;
        border: 1px solid var(--dash-border);
        border-radius: 15px;
        background: linear-gradient(145deg, rgba(255,255,255,.035), rgba(255,255,255,.012));
        box-shadow: 0 12px 35px rgba(0,0,0,.07);
    }

    .ai-dashboard .panel-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        padding: 15px 17px;
        border-bottom: 1px solid var(--dash-border);
    }

    .ai-dashboard .panel-title {
        display: flex;
        align-items: center;
        gap: 9px;
    }

    .ai-dashboard .panel-title-mark {
        width: 3px;
        height: 18px;
        border-radius: 99px;
        background: linear-gradient(180deg, var(--dash-blue), var(--dash-cyan));
    }

    .ai-dashboard .panel-head h3 {
        margin: 0;
        font-size: 13px;
        font-weight: 800;
        letter-spacing: -.01em;
    }

    .ai-dashboard .panel-head p {
        margin: 3px 0 0;
        color: var(--dash-muted);
        font-size: 10.5px;
    }

    .ai-dashboard .panel-body {
        padding: 16px;
    }

    .ai-dashboard .chart-panel {
        margin-bottom: 20px;
    }

    .ai-dashboard .chart-wrap {
        position: relative;
        min-height: 265px;
    }

    .ai-dashboard .chart-toolbar {
        display: flex;
        align-items: center;
        gap: 6px;
        padding: 10px 16px 0;
    }

    .ai-dashboard .chart-chip {
        padding: 5px 8px;
        color: var(--dash-muted);
        border: 1px solid var(--dash-border);
        border-radius: 7px;
        font-size: 9.5px;
        font-weight: 700;
    }

    .ai-dashboard .chart-chip.active {
        color: #b8c5ff;
        border-color: rgba(109,140,255,.25);
        background: rgba(109,140,255,.08);
    }

    .ai-dashboard .content-grid {
        display: grid;
        grid-template-columns: minmax(0, 1.75fr) minmax(280px, .85fr);
        gap: 20px;
        margin-bottom: 20px;
    }

    .ai-dashboard .content-grid.equal {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .ai-dashboard .table-wrap {
        overflow-x: auto;
    }

    .ai-dashboard .advanced-table {
        width: 100%;
        border-collapse: collapse;
    }

    .ai-dashboard .advanced-table th {
        padding: 10px 16px;
        color: var(--dash-muted);
        background: rgba(255,255,255,.018);
        border-bottom: 1px solid var(--dash-border);
        font-size: 9.5px;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: .07em;
        white-space: nowrap;
        text-align: left;
    }

    .ai-dashboard .advanced-table td {
        padding: 12px 16px;
        border-bottom: 1px solid rgba(148,163,184,.08);
        font-size: 11.5px;
        vertical-align: middle;
    }

    .ai-dashboard .advanced-table tbody tr {
        transition: background .18s ease;
    }

    .ai-dashboard .advanced-table tbody tr:hover {
        background: rgba(109,140,255,.035);
    }

    .ai-dashboard .advanced-table tbody tr:last-child td {
        border-bottom: 0;
    }

    .ai-dashboard .tool-cell {
        display: flex;
        align-items: center;
        gap: 10px;
        min-width: 160px;
    }

    .ai-dashboard .tool-avatar {
        display: grid;
        place-items: center;
        width: 32px;
        height: 32px;
        flex: 0 0 32px;
        border-radius: 9px;
        color: #c7d2fe;
        background: linear-gradient(135deg, rgba(109,140,255,.20), rgba(34,211,238,.09));
        border: 1px solid rgba(109,140,255,.17);
        font-size: 10px;
        font-weight: 800;
        text-transform: uppercase;
    }

    .ai-dashboard .tool-name {
        color: inherit;
        text-decoration: none;
        font-weight: 750;
    }

    .ai-dashboard .tool-name:hover {
        color: #9fb1ff;
    }

    .ai-dashboard .muted-cell {
        color: var(--dash-muted);
    }

    .ai-dashboard .mono-cell {
        font-family: ui-monospace, SFMono-Regular, Menlo, monospace;
        font-size: 10.5px;
    }

    .ai-dashboard .rating-cell {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        font-weight: 750;
    }

    .ai-dashboard .rating-cell svg {
        width: 12px;
        height: 12px;
        color: var(--dash-orange);
        fill: currentColor;
    }

    .ai-dashboard .model-list {
        display: flex;
        flex-direction: column;
        gap: 3px;
    }

    .ai-dashboard .model-row {
        display: flex;
        align-items: center;
        gap: 11px;
        padding: 11px 4px;
        border-bottom: 1px solid rgba(148,163,184,.08);
    }

    .ai-dashboard .model-row:last-child {
        border-bottom: 0;
    }

    .ai-dashboard .model-badge {
        display: grid;
        place-items: center;
        width: 30px;
        height: 30px;
        flex: 0 0 30px;
        border-radius: 9px;
        color: #b9c6ff;
        background: rgba(109,140,255,.08);
        border: 1px solid rgba(109,140,255,.14);
    }

    .ai-dashboard .model-info {
        flex: 1;
        min-width: 0;
    }

    .ai-dashboard .model-name {
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
        font-size: 11.5px;
        font-weight: 750;
    }

    .ai-dashboard .model-company {
        margin-top: 3px;
        color: var(--dash-muted);
        font-size: 10px;
    }

    .ai-dashboard .score-wrap {
        flex: 0 0 auto;
        min-width: 78px;
    }

    .ai-dashboard .news-list {
        display: flex;
        flex-direction: column;
    }

    .ai-dashboard .news-item {
        display: flex;
        align-items: flex-start;
        gap: 11px;
        padding: 13px 0;
        border-bottom: 1px solid rgba(148,163,184,.08);
    }

    .ai-dashboard .news-item:last-child {
        border-bottom: 0;
    }

    .ai-dashboard .news-thumb {
        display: grid;
        place-items: center;
        width: 38px;
        height: 38px;
        flex: 0 0 38px;
        border-radius: 10px;
        color: #b9c6ff;
        background: linear-gradient(135deg, rgba(139,92,246,.13), rgba(34,211,238,.07));
        border: 1px solid rgba(139,92,246,.16);
        font-size: 10px;
        font-weight: 800;
        text-transform: uppercase;
    }

    .ai-dashboard .news-main {
        flex: 1;
        min-width: 0;
    }

    .ai-dashboard .news-meta {
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: 6px;
        margin-bottom: 4px;
    }

    .ai-dashboard .news-category {
        display: inline-flex;
        padding: 3px 6px;
        border-radius: 5px;
        color: #a9c2ff;
        background: rgba(109,140,255,.08);
        border: 1px solid rgba(109,140,255,.13);
        font-size: 8.5px;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: .05em;
    }

    .ai-dashboard .news-source,
    .ai-dashboard .news-time {
        color: var(--dash-muted);
        font-size: 9.5px;
    }

    .ai-dashboard .news-link {
        display: block;
        color: inherit;
        text-decoration: none;
        font-size: 11.5px;
        line-height: 1.5;
        font-weight: 700;
    }

    .ai-dashboard .news-link:hover {
        color: #aebdff;
    }

    .ai-dashboard .queue-list {
        display: flex;
        flex-direction: column;
        gap: 9px;
    }

    .ai-dashboard .queue-item {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        padding: 11px 12px;
        border: 1px solid rgba(148,163,184,.09);
        border-radius: 10px;
        background: rgba(255,255,255,.018);
    }

    .ai-dashboard .queue-title {
        font-size: 11px;
        font-weight: 750;
    }

    .ai-dashboard .queue-sub {
        margin-top: 3px;
        color: var(--dash-muted);
        font-size: 9.5px;
    }

    .ai-dashboard .queue-number {
        display: grid;
        place-items: center;
        min-width: 30px;
        height: 25px;
        padding: 0 7px;
        border-radius: 7px;
        font: 800 10px/1 ui-monospace, SFMono-Regular, Menlo, monospace;
    }

    .ai-dashboard .queue-number.warn {
        color: #ffc86b;
        background: rgba(245,165,36,.10);
        border: 1px solid rgba(245,165,36,.16);
    }

    .ai-dashboard .queue-number.ok {
        color: #70e3a4;
        background: rgba(50,213,131,.08);
        border: 1px solid rgba(50,213,131,.14);
    }

    .ai-dashboard .empty-state {
        padding: 30px 18px;
        color: var(--dash-muted);
        text-align: center;
        font-size: 11px;
    }

    .ai-dashboard .empty-state svg {
        display: block;
        margin: 0 auto 8px;
        opacity: .5;
    }

    @media (max-width: 1200px) {
        .ai-dashboard .advanced-kpi-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .ai-dashboard .content-grid {
            grid-template-columns: minmax(0, 1fr);
        }

        .ai-dashboard .content-grid.equal {
            grid-template-columns: minmax(0, 1fr);
        }
    }

    @media (max-width: 700px) {
        .ai-dashboard .dashboard-hero {
            padding: 18px;
        }

        .ai-dashboard .hero-inner {
            align-items: flex-start;
            flex-direction: column;
        }

        .ai-dashboard .hero-actions {
            width: 100%;
        }

        .ai-dashboard .hero-actions .advanced-btn {
            flex: 1;
        }

        .ai-dashboard .advanced-kpi-grid {
            grid-template-columns: 1fr 1fr;
            gap: 9px;
        }

        .ai-dashboard .advanced-kpi {
            padding: 13px;
        }

        .ai-dashboard .kpi-value-modern {
            font-size: 21px;
        }

        .ai-dashboard .attention-card {
            align-items: flex-start;
            flex-wrap: wrap;
        }

        .ai-dashboard .attention-copy {
            flex-basis: calc(100% - 52px);
        }

        .ai-dashboard .attention-card .advanced-btn {
            width: 100%;
        }

        .ai-dashboard .panel-head {
            padding: 13px;
        }

        .ai-dashboard .panel-body {
            padding: 13px;
        }
    }

    @media (max-width: 430px) {
        .ai-dashboard .advanced-kpi-grid {
            grid-template-columns: 1fr;
        }

        .ai-dashboard .hero-actions {
            flex-direction: column;
        }

        .ai-dashboard .hero-actions .advanced-btn {
            width: 100%;
        }
    }
</style>

<div class="ai-dashboard">

    {{-- =====================================================
         DASHBOARD HERO
         ===================================================== --}}
    <section class="dashboard-hero">
        <div class="hero-inner">
            <div>
                <div class="hero-kicker">
                    <span class="live-dot"></span>
                    AI Hub Control Center
                </div>
                <h1 class="hero-title">Dashboard</h1>
                <p class="hero-subtitle">
                    Here's what's happening across your AI Hub right now.
                    Monitor content, tools, models, news and moderation activity from one place.
                </p>
            </div>

            <div class="hero-actions">
                <a href="{{ route('admin.tools.create') }}" class="advanced-btn primary">
                    <i data-lucide="plus"></i>
                    Add Tool
                </a>
            </div>
        </div>
    </section>

    {{-- =====================================================
         KPI OVERVIEW
         ===================================================== --}}
    <div class="section-label">
        <h2>Platform Overview</h2>
        <span>Live database totals</span>
    </div>

    <div class="advanced-kpi-grid">

        <div class="advanced-kpi">
            <div class="kpi-top">
                <div class="kpi-icon-modern"><i data-lucide="wrench"></i></div>
                <span class="kpi-index">01</span>
            </div>
            <div class="kpi-label-modern">Total AI Tools</div>
            <div class="kpi-value-modern">{{ number_format($kpis['tools']) }}</div>
        </div>

        <div class="advanced-kpi">
            <div class="kpi-top">
                <div class="kpi-icon-modern"><i data-lucide="brain-circuit"></i></div>
                <span class="kpi-index">02</span>
            </div>
            <div class="kpi-label-modern">Total AI Models</div>
            <div class="kpi-value-modern">{{ number_format($kpis['models']) }}</div>
        </div>

        <div class="advanced-kpi">
            <div class="kpi-top">
                <div class="kpi-icon-modern"><i data-lucide="building-2"></i></div>
                <span class="kpi-index">03</span>
            </div>
            <div class="kpi-label-modern">AI Companies</div>
            <div class="kpi-value-modern">{{ number_format($kpis['companies']) }}</div>
        </div>

        <div class="advanced-kpi">
            <div class="kpi-top">
                <div class="kpi-icon-modern"><i data-lucide="columns-3"></i></div>
                <span class="kpi-index">04</span>
            </div>
            <div class="kpi-label-modern">Total Comparisons</div>
            <div class="kpi-value-modern">{{ number_format($kpis['comparisons']) }}</div>
        </div>

        <div class="advanced-kpi">
            <div class="kpi-top">
                <div class="kpi-icon-modern"><i data-lucide="newspaper"></i></div>
                <span class="kpi-index">05</span>
            </div>
            <div class="kpi-label-modern">AI News (24h)</div>
            <div class="kpi-value-modern">{{ number_format($kpis['news24h']) }}</div>
        </div>

        <div class="advanced-kpi">
            <div class="kpi-top">
                <div class="kpi-icon-modern"><i data-lucide="star"></i></div>
                <span class="kpi-index">06</span>
            </div>
            <div class="kpi-label-modern">AI Reviews</div>
            <div class="kpi-value-modern">{{ number_format($kpis['reviews']) }}</div>
        </div>

        <div class="advanced-kpi">
            <div class="kpi-top">
                <div class="kpi-icon-modern"><i data-lucide="users"></i></div>
                <span class="kpi-index">07</span>
            </div>
            <div class="kpi-label-modern">Registered Users</div>
            <div class="kpi-value-modern">{{ number_format($kpis['users']) }}</div>
        </div>

        <div class="advanced-kpi">
            <div class="kpi-top">
                <div class="kpi-icon-modern"><i data-lucide="file-text"></i></div>
                <span class="kpi-index">08</span>
            </div>
            <div class="kpi-label-modern">Published Articles</div>
            <div class="kpi-value-modern">{{ number_format($kpis['articles']) }}</div>
        </div>

    </div>

    {{-- =====================================================
         PENDING APPROVALS
         ===================================================== --}}
    @if ($pending['reviews'] > 0 || $pending['submissions'] > 0)
        <div class="attention-card">
            <div class="attention-icon">
                <i data-lucide="clock-3"></i>
            </div>

            <div class="attention-copy">
                <strong>Needs your attention</strong>
                <span>
                    {{ $pending['reviews'] }} review(s) awaiting approval ·
                    {{ $pending['submissions'] }} tool suggestion(s) pending
                </span>
            </div>

            <a href="{{ route('admin.content.reviews.index') }}" class="advanced-btn secondary">
                Review Queue
            </a>

            <a href="{{ route('admin.submissions.index') }}" class="advanced-btn secondary">
                Submissions
            </a>
        </div>
    @endif

    {{-- =====================================================
         CONTENT GROWTH
         ===================================================== --}}
    <section class="panel chart-panel">
        <div class="panel-head">
            <div class="panel-title">
                <span class="panel-title-mark"></span>
                <div>
                    <h3>Content Growth</h3>
                    <p>Tools, news, and articles added per day — last 30 days</p>
                </div>
            </div>

            <span class="chart-chip active">30 DAYS</span>
        </div>

        <div class="chart-toolbar">
            <span class="chart-chip active">All Content</span>
            <span class="chart-chip">Tools</span>
            <span class="chart-chip">News</span>
            <span class="chart-chip">Articles</span>
        </div>

        <div class="panel-body chart-wrap">
            <canvas id="growthChart" height="90"></canvas>
        </div>
    </section>

    {{-- =====================================================
         TOP TOOLS + MODELS
         ===================================================== --}}
    <div class="content-grid">

        <section class="panel">
            <div class="panel-head">
                <div class="panel-title">
                    <span class="panel-title-mark"></span>
                    <div>
                        <h3>Top Rated AI Tools</h3>
                        <p>Highest-rated published tools</p>
                    </div>
                </div>

                <a href="{{ route('admin.tools.index') }}" class="advanced-btn secondary">
                    View all
                </a>
            </div>

            <div class="table-wrap">
                <table class="advanced-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Tool</th>
                            <th>Company</th>
                            <th>Reviews</th>
                            <th>Rating</th>
                        </tr>
                    </thead>

                    <tbody>
                    @forelse ($topRatedTools as $i => $tool)
                        <tr>
                            <td class="mono-cell muted-cell">{{ $i + 1 }}</td>

                            <td>
                                <div class="tool-cell">
                                    <div class="tool-avatar">{{ substr($tool->name, 0, 2) }}</div>
                                    <a href="{{ route('admin.tools.show', $tool->id) }}" class="tool-name">
                                        {{ $tool->name }}
                                    </a>
                                </div>
                            </td>

                            <td class="muted-cell">{{ $tool->company->name ?? '—' }}</td>

                            <td class="mono-cell">{{ $tool->reviews()->count() }}</td>

                            <td>
                                <span class="rating-cell">
                                    <i data-lucide="star"></i>
                                    {{ number_format($tool->rating, 1) }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5">
                                <div class="empty-state">
                                    <i data-lucide="package-open"></i>
                                    No published tools yet.
                                </div>
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <section class="panel">
            <div class="panel-head">
                <div class="panel-title">
                    <span class="panel-title-mark"></span>
                    <div>
                        <h3>Latest AI Models</h3>
                        <p>Recently added models</p>
                    </div>
                </div>
            </div>

            <div class="panel-body">
                <div class="model-list">
                    @forelse ($latestModels as $model)
                        <div class="model-row">
                            <div class="model-badge">
                                <i data-lucide="brain-circuit"></i>
                            </div>

                            <div class="model-info">
                                <div class="model-name">{{ $model->name }}</div>
                                <div class="model-company">{{ $model->company->name ?? '—' }}</div>
                            </div>

                            <div class="score-wrap">
                                <x-score-meter :value="(int) $model->benchmark_score" :segments="6" />
                            </div>
                        </div>
                    @empty
                        <div class="empty-state">
                            <i data-lucide="brain"></i>
                            No models yet.
                        </div>
                    @endforelse
                </div>
            </div>
        </section>

    </div>

    {{-- =====================================================
         NEWS + APPROVAL QUEUE
         ===================================================== --}}
    <div class="content-grid">

        <section class="panel">
            <div class="panel-head">
                <div class="panel-title">
                    <span class="panel-title-mark"></span>
                    <div>
                        <h3>Recent AI News</h3>
                        <p>Latest published intelligence</p>
                    </div>
                </div>

                <a href="{{ route('admin.news.index') }}" class="advanced-btn secondary">
                    Open News Feed
                </a>
            </div>

            <div class="panel-body">
                <div class="news-list">
                    @forelse ($recentNews as $n)
                        <div class="news-item">
                            <div class="news-thumb">
                                {{ substr($n->source ?? $n->headline, 0, 2) }}
                            </div>

                            <div class="news-main">
                                <div class="news-meta">
                                    @if ($n->category)
                                        <span class="news-category">{{ $n->category }}</span>
                                    @endif

                                    <span class="news-source">{{ $n->source }}</span>
                                    <span class="news-time">{{ $n->published_at?->diffForHumans() }}</span>
                                </div>

                                <a href="{{ route('admin.news.show', $n->id) }}" class="news-link">
                                    {{ $n->headline }}
                                </a>
                            </div>

                            <x-score-meter :value="$n->importance" :segments="5" />
                        </div>
                    @empty
                        <div class="empty-state">
                            <i data-lucide="newspaper"></i>
                            No published news yet.
                        </div>
                    @endforelse
                </div>
            </div>
        </section>

        <section class="panel">
            <div class="panel-head">
                <div class="panel-title">
                    <span class="panel-title-mark"></span>
                    <div>
                        <h3>Approval Queue</h3>
                        <p>Content requiring moderation</p>
                    </div>
                </div>
            </div>

            <div class="panel-body">
                <div class="queue-list">

                    <div class="queue-item">
                        <div>
                            <div class="queue-title">Reviews pending</div>
                            <div class="queue-sub">Awaiting moderation</div>
                        </div>

                        <span class="queue-number {{ $pending['reviews'] > 0 ? 'warn' : 'ok' }}">
                            {{ $pending['reviews'] }}
                        </span>
                    </div>

                    <div class="queue-item">
                        <div>
                            <div class="queue-title">Tool suggestions pending</div>
                            <div class="queue-sub">Awaiting review</div>
                        </div>

                        <span class="queue-number {{ $pending['submissions'] > 0 ? 'warn' : 'ok' }}">
                            {{ $pending['submissions'] }}
                        </span>
                    </div>

                </div>
            </div>
        </section>

    </div>

    {{-- =====================================================
         RECENT TOOLS + PRICE CHANGES
         ===================================================== --}}
    <div class="content-grid equal">

        <section class="panel">
            <div class="panel-head">
                <div class="panel-title">
                    <span class="panel-title-mark"></span>
                    <div>
                        <h3>Recently Added AI Tools</h3>
                        <p>Latest additions to the directory</p>
                    </div>
                </div>
            </div>

            <div class="table-wrap">
                <table class="advanced-table">
                    <thead>
                        <tr>
                            <th>Tool</th>
                            <th>Company</th>
                            <th>Added</th>
                            <th>Status</th>
                        </tr>
                    </thead>

                    <tbody>
                    @forelse ($recentTools as $tool)
                        <tr>
                            <td>
                                <a href="{{ route('admin.tools.show', $tool->id) }}" class="tool-name">
                                    {{ $tool->name }}
                                </a>
                            </td>

                            <td class="muted-cell">{{ $tool->company->name ?? '—' }}</td>

                            <td class="muted-cell">{{ $tool->created_at->format('M j') }}</td>

                            <td>
                                <x-status-badge
                                    status="{{ ucfirst($tool->status) }}"
                                    type="{{ $tool->status === 'published' ? 'pos' : 'neutral' }}"
                                />
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4">
                                <div class="empty-state">
                                    <i data-lucide="package"></i>
                                    No tools yet.
                                </div>
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <section class="panel">
            <div class="panel-head">
                <div class="panel-title">
                    <span class="panel-title-mark"></span>
                    <div>
                        <h3>Recent Price Changes</h3>
                        <p>Latest pricing activity</p>
                    </div>
                </div>

                <a href="{{ route('admin.pricing.history') }}" class="advanced-btn secondary">
                    View all
                </a>
            </div>

            <div class="table-wrap">
                <table class="advanced-table">
                    <thead>
                        <tr>
                            <th>Tool</th>
                            <th>Old</th>
                            <th>New</th>
                            <th>Change</th>
                        </tr>
                    </thead>

                    <tbody>
                    @forelse ($priceChanges as $change)
                        @php
                            $pct = ($change->old_price && $change->new_price)
                                ? round((($change->new_price - $change->old_price) / $change->old_price) * 100)
                                : null;
                        @endphp

                        <tr>
                            <td>
                                <b>{{ $change->tool->name ?? '—' }}</b>
                            </td>

                            <td class="mono-cell muted-cell">
                                {{ $change->old_price !== null ? '$'.number_format($change->old_price, 0) : '—' }}
                            </td>

                            <td class="mono-cell">
                                {{ $change->new_price !== null ? '$'.number_format($change->new_price, 0) : '—' }}
                            </td>

                            <td>
                                @if ($pct !== null)
                                    <span class="badge {{ $pct > 0 ? 'badge-neg' : 'badge-pos' }}">
                                        {{ $pct > 0 ? '+' : '' }}{{ $pct }}%
                                    </span>
                                @else
                                    <span class="badge badge-neutral">
                                        {{ $change->change_type === 'new_plan' ? 'New Plan' : 'Removed' }}
                                    </span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4">
                                <div class="empty-state">
                                    <i data-lucide="badge-dollar-sign"></i>
                                    No price changes recorded yet.
                                </div>
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </section>

    </div>

</div>

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>

<script>
    const growthCanvas = document.getElementById('growthChart');

    if (growthCanvas) {
        new Chart(growthCanvas, {
            type: 'line',
            data: {
                labels: {!! json_encode($chart['labels']) !!},
                datasets: [
                    {
                        label: 'Tools',
                        data: {!! json_encode($chart['tools']) !!},
                        borderColor: '#6d8cff',
                        backgroundColor: 'rgba(109,140,255,.08)',
                        tension: .42,
                        fill: true,
                        pointRadius: 0,
                        pointHoverRadius: 4,
                        borderWidth: 2
                    },
                    {
                        label: 'News',
                        data: {!! json_encode($chart['news']) !!},
                        borderColor: '#22d3ee',
                        backgroundColor: 'transparent',
                        tension: .42,
                        pointRadius: 0,
                        pointHoverRadius: 4,
                        borderWidth: 2
                    },
                    {
                        label: 'Articles',
                        data: {!! json_encode($chart['articles']) !!},
                        borderColor: '#9b7cff',
                        backgroundColor: 'transparent',
                        tension: .42,
                        pointRadius: 0,
                        pointHoverRadius: 4,
                        borderWidth: 2
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: {
                    mode: 'index',
                    intersect: false
                },
                plugins: {
                    legend: {
                        position: 'top',
                        align: 'start',
                        labels: {
                            color: '#9aa3b8',
                            usePointStyle: true,
                            pointStyle: 'circle',
                            boxWidth: 7,
                            boxHeight: 7,
                            padding: 18,
                            font: {
                                size: 10,
                                weight: '600'
                            }
                        }
                    },
                    tooltip: {
                        backgroundColor: 'rgba(10,14,24,.94)',
                        borderColor: 'rgba(148,163,184,.16)',
                        borderWidth: 1,
                        titleColor: '#eef2ff',
                        bodyColor: '#aab3c5',
                        padding: 10,
                        displayColors: true,
                        usePointStyle: true
                    }
                },
                scales: {
                    x: {
                        grid: {
                            display: false
                        },
                        border: {
                            display: false
                        },
                        ticks: {
                            color: '#5c6580',
                            font: {
                                size: 10
                            },
                            maxTicksLimit: 10
                        }
                    },
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(255,255,255,.045)'
                        },
                        border: {
                            display: false
                        },
                        ticks: {
                            color: '#5c6580',
                            font: {
                                size: 10
                            },
                            precision: 0
                        }
                    }
                }
            }
        });
    }
</script>
@endpush