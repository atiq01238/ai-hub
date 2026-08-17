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

<style>
    .analytics-page .analytics-readiness { margin-bottom: 20px; padding: 14px 16px; display:flex; gap:12px; align-items:flex-start; border:1px solid rgba(255,255,255,.07); border-radius:13px; background:rgba(255,255,255,.022); }
    .analytics-page .analytics-readiness.good { border-color:rgba(34,197,94,.16); background:rgba(34,197,94,.035); }
    .analytics-page .analytics-readiness.partial { border-color:rgba(245,158,11,.16); background:rgba(245,158,11,.03); }
    .analytics-page .analytics-readiness.missing { border-color:rgba(239,68,68,.16); background:rgba(239,68,68,.03); }
    .analytics-page .analytics-readiness-icon { width:34px; height:34px; flex:0 0 34px; display:flex; align-items:center; justify-content:center; border-radius:9px; background:rgba(255,255,255,.04); color:#aab4c7; }
    .analytics-page .analytics-readiness-icon svg { width:16px; height:16px; }
    .analytics-page .analytics-readiness h4 { margin:0 0 4px; color:#e8edf6; font-size:12px; font-weight:700; }
    .analytics-page .analytics-readiness p { margin:0; color:#8591a6; font-size:11px; line-height:1.55; }
    .analytics-page .analytics-empty { padding:34px 20px; text-align:center; color:#778398; font-size:12px; }
    .analytics-page .analytics-empty strong { display:block; margin-bottom:5px; color:#cbd3e1; font-size:13px; }
    .analytics-page .analytics-kpi-delta.neutral { color:#94a3b8; background:rgba(148,163,184,.08); }
    .analytics-page .analytics-period-note { color:#68748b; font-size:10px; }
    .analytics-page .analytics-table tbody td:first-child { color:#edf1f7; font-weight:650; }
</style>


<div class="analytics-page">
@php
    $tab = $tab ?? (request()->is('*tools*') ? 'tools' : (request()->is('*search*') ? 'search' : (request()->is('*comparisons*') ? 'comparisons' : (request()->is('*content*') ? 'content' : (request()->is('*trending*') ? 'trending' : 'website')))));
    $titles = ['website'=>'Website Analytics','tools'=>'Tool Analytics','search'=>'Search Analytics','comparisons'=>'Comparison Analytics','content'=>'Content Analytics','trending'=>'Trending Searches'];
    $days = $days ?? 30;
@endphp

    <div class="analytics-header">
        <div class="analytics-header-inner">
            <div class="analytics-heading">
                <div class="analytics-heading-icon"><i data-lucide="chart-no-axes-combined"></i></div>
                <div>
                    <div class="analytics-kicker">Performance Center</div>
                    <h1 class="analytics-title">{{ $titles[$tab] }}</h1>
                    <p class="analytics-subtitle">{{ $period['label'] ?? 'Last 30 days' }} · compared with the previous equivalent period</p>
                </div>
            </div>

            <div class="analytics-actions">
                <form method="GET" action="{{ url()->current() }}" id="analyticsPeriodForm">
                    <select class="analytics-period select" name="days" onchange="this.form.submit()">
                        <option value="7" {{ $days === 7 ? 'selected' : '' }}>7 Days</option>
                        <option value="30" {{ $days === 30 ? 'selected' : '' }}>30 Days</option>
                        <option value="90" {{ $days === 90 ? 'selected' : '' }}>3 Months</option>
                        <option value="365" {{ $days === 365 ? 'selected' : '' }}>1 Year</option>
                    </select>
                </form>

                <a href="{{ route('admin.analytics.export', ['tab' => $tab, 'days' => $days]) }}" class="btn btn-secondary btn-sm analytics-export">
                    <i data-lucide="download"></i> Export CSV
                </a>
            </div>
        </div>
    </div>

    <div class="analytics-tabs">
        <a href="{{ route('admin.analytics.website', ['days'=>$days]) }}" class="analytics-tab {{ $tab==='website'?'is-active':'' }}"><i data-lucide="globe-2"></i>Website</a>
        <a href="{{ route('admin.analytics.tools', ['days'=>$days]) }}" class="analytics-tab {{ $tab==='tools'?'is-active':'' }}"><i data-lucide="wrench"></i>Tool</a>
        <a href="{{ route('admin.analytics.search', ['days'=>$days]) }}" class="analytics-tab {{ $tab==='search'?'is-active':'' }}"><i data-lucide="search"></i>Search</a>
        <a href="{{ route('admin.analytics.comparisons', ['days'=>$days]) }}" class="analytics-tab {{ $tab==='comparisons'?'is-active':'' }}"><i data-lucide="columns-3"></i>Comparison</a>
        <a href="{{ route('admin.analytics.content', ['days'=>$days]) }}" class="analytics-tab {{ $tab==='content'?'is-active':'' }}"><i data-lucide="file-text"></i>Content</a>
        <a href="{{ route('admin.analytics.trending', ['days'=>$days]) }}" class="analytics-tab {{ $tab==='trending'?'is-active':'' }}"><i data-lucide="flame"></i>Trending Searches</a>
    </div>

    @if(!empty($readiness))
        <div class="analytics-readiness {{ $readiness['level'] ?? 'partial' }}">
            <div class="analytics-readiness-icon">
                <i data-lucide="{{ ($readiness['level'] ?? '') === 'good' ? 'database-zap' : (($readiness['level'] ?? '') === 'missing' ? 'triangle-alert' : 'info') }}"></i>
            </div>
            <div>
                <h4>{{ $readiness['title'] ?? 'Analytics data status' }}</h4>
                <p>{{ $readiness['message'] ?? '' }}</p>
            </div>
        </div>
    @endif

    <div class="analytics-kpis">
        @foreach($kpis ?? [] as $kpi)
            <div class="analytics-kpi">
                <div class="analytics-kpi-top">
                    <div class="analytics-kpi-icon"><i data-lucide="{{ $kpi['icon'] ?? 'activity' }}"></i></div>
                    @if(!empty($kpi['delta']))
                        <span class="analytics-kpi-delta {{ $kpi['delta']['direction'] === 'down' ? 'down' : 'up' }}">
                            {{ $kpi['delta']['direction'] === 'down' ? '↘' : '↗' }} {{ $kpi['delta']['label'] }}
                        </span>
                    @else
                        <span class="analytics-kpi-delta neutral">Live</span>
                    @endif
                </div>
                <div class="analytics-kpi-label">{{ $kpi['label'] }}</div>
                <div class="analytics-kpi-value">{{ $kpi['value'] }}</div>
            </div>
        @endforeach
    </div>

    <div class="analytics-chart-card">
        <div class="analytics-chart-head">
            <div>
                <h3 class="analytics-chart-title">{{ $chart['title'] ?? 'Performance Trend' }}</h3>
                <div class="analytics-period-note">{{ $chart['series_label'] ?? 'Value' }} · {{ $period['label'] ?? '' }}</div>
            </div>
            <div class="analytics-chart-meta"><span class="analytics-chart-dot"></span> Database-backed</div>
        </div>
        <div class="analytics-chart-body"><canvas id="analyticsChart" height="88"></canvas></div>
    </div>

    <div class="analytics-card">
        <div class="analytics-card-head">
            <h3>{{ $table['title'] ?? 'Analytics Details' }}</h3>
            <span>{{ count($table['rows'] ?? []) }} records shown</span>
        </div>
        @if(!empty($table['rows']))
            <div class="analytics-table-wrap">
                <table class="analytics-table">
                    <thead><tr>@foreach($table['headers'] ?? [] as $header)<th>{{ $header }}</th>@endforeach</tr></thead>
                    <tbody>
                    @foreach($table['rows'] as $row)
                        <tr>@foreach($row as $value)<td>{{ $value }}</td>@endforeach</tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="analytics-empty">
                <strong>No tracked records yet</strong>
                The current database has no event rows for this analytics source. Values will appear here once tracking data is stored.
            </div>
        @endif
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const el = document.getElementById('analyticsChart');
    if (el && typeof Chart !== 'undefined') {
        new Chart(el, {
            type: 'bar',
            data: {
                labels: @json(collect($chart['points'] ?? [])->pluck('label')->all()),
                datasets: [{
                    label: @json($chart['series_label'] ?? 'Value'),
                    data: @json(collect($chart['points'] ?? [])->pluck('value')->all()),
                    backgroundColor: 'rgba(99,102,241,.55)',
                    hoverBackgroundColor: 'rgba(129,140,248,.85)',
                    borderRadius: 7,
                    borderSkipped: false,
                    maxBarThickness: 30
                }]
            },
            options: {
                responsive: true,
                interaction: { intersect: false, mode: 'index' },
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: '#171b27', titleColor: '#f4f7fb', bodyColor: '#aab3c4',
                        borderColor: 'rgba(255,255,255,.08)', borderWidth: 1, padding: 11, displayColors: false
                    }
                },
                scales: {
                    x: { border:{display:false}, grid:{display:false}, ticks:{color:'#69758b', font:{size:10}, maxRotation:0, autoSkip:true, maxTicksLimit:12} },
                    y: { beginAtZero:true, border:{display:false}, grid:{color:'rgba(255,255,255,.045)'}, ticks:{color:'#69758b', font:{size:10}, precision:0} }
                }
            }
        });
    }
    if (typeof lucide !== 'undefined') lucide.createIcons();
});
</script>
@endpush
