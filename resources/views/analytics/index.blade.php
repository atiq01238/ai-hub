@extends('layouts.admin')
@section('title', 'Analytics')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/pages/analytics.css') }}">
@endpush

@section('content')
@php
    $tab = $tab ?? 'website';
    $days = $days ?? 30;
    $meta = [
        'website' => ['title'=>'Website Analytics','eyebrow'=>'Audience Intelligence','icon'=>'globe-2','desc'=>'Monitor real visitors, page views, sessions, traffic sources, devices and the public pages attracting attention.'],
        'tools' => ['title'=>'Tool Analytics','eyebrow'=>'Catalog Intelligence','icon'=>'wrench','desc'=>'Track publishing velocity, ratings, reviews and catalog momentum across AI tools.'],
        'search' => ['title'=>'Search Analytics','eyebrow'=>'Discovery Intelligence','icon'=>'search','desc'=>'Understand what visitors search for, where discovery fails and which queries convert.'],
        'comparisons' => ['title'=>'Comparison Analytics','eyebrow'=>'Decision Intelligence','icon'=>'columns-3','desc'=>'Measure comparison creation, recorded views and the assets driving evaluation activity.'],
        'content' => ['title'=>'Content Analytics','eyebrow'=>'Editorial Intelligence','icon'=>'file-text','desc'=>'Monitor publishing throughput, reviews, social distribution and approval operations.'],
        'trending' => ['title'=>'Trending Searches','eyebrow'=>'Momentum Intelligence','icon'=>'flame','desc'=>'Surface catalog momentum while preparing for true search-trend tracking.'],
    ];
    $current = $meta[$tab] ?? $meta['website'];
    $points = collect($chart['points'] ?? []);
    $totalSeries = (float)$points->sum('value');
    $peak = $points->sortByDesc('value')->first();
    $secondaryPoints = collect($chart['secondary_points'] ?? []);
@endphp

<div class="an-page">
    <section class="an-hero">
        <div class="an-hero__main">
            <span class="an-hero__icon"><i data-lucide="{{ $current['icon'] }}"></i></span>
            <div>
                <span class="an-eyebrow">{{ $current['eyebrow'] }}</span>
                <h1>{{ $current['title'] }}</h1>
                <p>{{ $current['desc'] }}</p>
            </div>
        </div>
        <div class="an-hero__actions">
            <form method="GET" action="{{ url()->current() }}">
                <label class="an-period">
                    <i data-lucide="calendar-range"></i>
                    <select name="days" onchange="this.form.submit()">
                        <option value="1" @selected($days===1)>Today</option>
                        <option value="7" @selected($days===7)>Last 7 days</option>
                        <option value="30" @selected($days===30)>Last 30 days</option>
                        <option value="90" @selected($days===90)>Last 3 months</option>
                        <option value="365" @selected($days===365)>Last 12 months</option>
                    </select>
                </label>
            </form>
            <a class="btn btn-secondary" href="{{ route('admin.analytics.export',['tab'=>$tab,'days'=>$days]) }}"><i data-lucide="download"></i>Export CSV</a>
        </div>
    </section>

    <nav class="an-tabs" aria-label="Analytics sections">
        @foreach([
            'website'=>['Website','globe-2'],
            'tools'=>['Tools','wrench'],
            'search'=>['Search','search'],
            'comparisons'=>['Comparisons','columns-3'],
            'content'=>['Content','file-text'],
            'trending'=>['Trending','flame'],
        ] as $key=>$item)
            <a href="{{ route('admin.analytics.'.$key,['days'=>$days]) }}" class="{{ $tab===$key?'is-active':'' }}">
                <i data-lucide="{{ $item[1] }}"></i><span>{{ $item[0] }}</span>
            </a>
        @endforeach
    </nav>

    @if(!empty($readiness))
    <section class="an-readiness an-readiness--{{ $readiness['level'] ?? 'partial' }}">
        <span class="an-readiness__icon"><i data-lucide="{{ ($readiness['level'] ?? '')==='good'?'database-zap':(($readiness['level'] ?? '')==='missing'?'triangle-alert':'info') }}"></i></span>
        <div class="an-readiness__copy">
            <span class="an-eyebrow">Data Coverage</span>
            <strong>{{ $readiness['title'] ?? 'Analytics data status' }}</strong>
            <p>{{ $readiness['message'] ?? '' }}</p>
        </div>
        <span class="an-readiness__state">{{ ucfirst($readiness['level'] ?? 'partial') }}</span>
    </section>
    @endif

    <section class="an-kpis">
        @foreach($kpis ?? [] as $index=>$kpi)
        <article class="an-kpi">
            <div class="an-kpi__top">
                <span class="an-kpi__icon"><i data-lucide="{{ $kpi['icon'] ?? 'activity' }}"></i></span>
                @if(!empty($kpi['delta']))
                    <span class="an-delta {{ $kpi['delta']['direction']==='down'?'is-down':'is-up' }}">
                        <i data-lucide="{{ $kpi['delta']['direction']==='down'?'trending-down':'trending-up' }}"></i>{{ $kpi['delta']['label'] }}
                    </span>
                @else
                    <span class="an-delta is-live"><span></span>Live</span>
                @endif
            </div>
            <span class="an-kpi__label">{{ $kpi['label'] }}</span>
            <strong class="an-kpi__value">{{ $kpi['value'] }}</strong>
            <span class="an-kpi__foot">{{ !empty($kpi['delta']) ? 'vs previous equivalent period' : ($tab==='website' ? 'tracked public traffic' : 'current database value') }}</span>
        </article>
        @endforeach
    </section>

    <section class="an-grid">
        <article class="card an-chart-card">
            <header class="an-card-head">
                <div>
                    <span class="an-eyebrow">Time Series</span>
                    <h2>{{ $chart['title'] ?? 'Performance Trend' }}</h2>
                    <p>{{ $chart['series_label'] ?? 'Value' }} · {{ $period['label'] ?? '' }}</p>
                </div>
                <span class="an-source"><span></span>Database-backed</span>
            </header>
            <div class="an-chart-wrap"><canvas id="analyticsChart"></canvas></div>
        </article>

        <aside class="card an-summary">
            <header class="an-card-head">
                <div><span class="an-eyebrow">Period Summary</span><h2>Signal overview</h2></div>
                <i data-lucide="activity"></i>
            </header>
            <div class="an-summary__body">
                <div class="an-summary__metric">
                    <span>Reporting window</span>
                    <strong>{{ $period['label'] ?? 'Last 30 days' }}</strong>
                    <small>{{ isset($period['from']) ? $period['from']->format('M j') : '—' }} → {{ isset($period['to']) ? $period['to']->format('M j, Y') : '—' }}</small>
                </div>
                <div class="an-summary__metric">
                    <span>Series total</span>
                    <strong>{{ number_format($totalSeries) }}</strong>
                    <small>{{ $chart['series_label'] ?? 'Tracked values' }}</small>
                </div>
                <div class="an-summary__metric">
                    <span>Peak bucket</span>
                    <strong>{{ $peak['value'] ?? 0 }}</strong>
                    <small>{{ $peak['label'] ?? 'No activity yet' }}</small>
                </div>
                <div class="an-summary__metric">
                    <span>Data points</span>
                    <strong>{{ $points->count() }}</strong>
                    <small>Rendered in current trend</small>
                </div>
            </div>
        </aside>
    </section>

    @if($tab==='website' && !empty($websiteBreakdown))
    <section class="an-live-strip">
        @foreach([
            ['Active now',$websiteBreakdown['recent_visitors'] ?? 0,'Users active in the last 5 minutes','radio'],
            ['New visitors',$websiteBreakdown['new_visitors'] ?? 0,'First seen in this reporting window','user-plus'],
            ['Returning',$websiteBreakdown['returning_visitors'] ?? 0,'Visitors first seen before this window','rotate-ccw'],
            ['Pages / session',(float)($websiteBreakdown['pages_per_session'] ?? 0),'Average depth per tracked session','layers-3'],
        ] as [$label,$value,$help,$icon])
        <article class="an-live-stat">
            <span><i data-lucide="{{ $icon }}"></i></span>
            <div><small>{{ $label }}</small><strong>{{ is_numeric($value) ? number_format((float)$value, is_float($value) ? 2 : 0) : $value }}</strong><p>{{ $help }}</p></div>
        </article>
        @endforeach
    </section>

    <section class="an-breakdowns">
        @foreach([
            ['Visitor mix','users-round',$websiteBreakdown['audience'] ?? []],
            ['Devices','monitor-smartphone',$websiteBreakdown['devices'] ?? []],
            ['Browsers','compass',$websiteBreakdown['browsers'] ?? []],
            ['Traffic sources','waypoints',$websiteBreakdown['sources'] ?? []],
            ['Countries','map-pin',$websiteBreakdown['countries'] ?? []],
            ['Top AI profiles','sparkles',$websiteBreakdown['entities'] ?? []],
        ] as [$title,$icon,$rows])
        <article class="card an-breakdown-card">
            <header><div><i data-lucide="{{ $icon }}"></i><strong>{{ $title }}</strong></div><small>{{ count($rows) }} signals</small></header>
            <div class="an-breakdown-list">
                @forelse($rows as $row)
                <div class="an-breakdown-row">
                    <div class="an-breakdown-row__top"><span title="{{ $row['label'] }}">{{ $row['label'] }}</span><strong>{{ number_format((int)$row['value']) }} <em>{{ number_format((float)$row['share'],1) }}%</em></strong></div>
                    <div class="an-breakdown-bar"><span style="width:{{ max(0,min(100,(float)$row['share'])) }}%"></span></div>
                </div>
                @empty
                <div class="an-breakdown-empty">Traffic will appear here after public visits are recorded.</div>
                @endforelse
            </div>
        </article>
        @endforeach
    </section>
    @endif

    @if($tab==='content' && !empty($contentMetrics))
    <section class="card an-operations">
        <header class="an-card-head">
            <div><span class="an-eyebrow">Editorial Operations</span><h2>Content pipeline snapshot</h2><p>Current workload across publishing and moderation.</p></div>
            <i data-lucide="workflow"></i>
        </header>
        <div class="an-operations__grid">
            @foreach([
                ['Published Articles',$contentMetrics['published_articles'] ?? 0,'file-check-2'],
                ['Scheduled Articles',$contentMetrics['scheduled_articles'] ?? 0,'calendar-clock'],
                ['Pending Reviews',$contentMetrics['pending_reviews'] ?? 0,'messages-square'],
                ['Published Reviews',$contentMetrics['published_reviews'] ?? 0,'star'],
                ['Social Posts',$contentMetrics['social_posts'] ?? 0,'share-2'],
                ['Approval Queue',$contentMetrics['approval_queue'] ?? 0,'list-checks'],
            ] as [$label,$value,$icon])
            <div><span><i data-lucide="{{ $icon }}"></i></span><strong>{{ number_format($value) }}</strong><small>{{ $label }}</small></div>
            @endforeach
        </div>
    </section>
    @endif

    <section class="card an-table-card">
        <header class="an-card-head">
            <div>
                <span class="an-eyebrow">Ranked Detail</span>
                <h2>{{ $table['title'] ?? 'Analytics Details' }}</h2>
                <p>{{ count($table['rows'] ?? []) }} records available for this view.</p>
            </div>
            <span class="an-record-count">{{ count($table['rows'] ?? []) }} rows</span>
        </header>

        @if(!empty($table['rows']))
        <div class="an-table-wrap">
            <table class="an-table">
                <thead><tr><th>#</th>@foreach($table['headers'] ?? [] as $header)<th>{{ $header }}</th>@endforeach</tr></thead>
                <tbody>
                @foreach($table['rows'] as $rowIndex=>$row)
                    <tr>
                        <td><span class="an-rank">{{ str_pad($rowIndex+1,2,'0',STR_PAD_LEFT) }}</span></td>
                        @foreach($row as $cellIndex=>$value)
                            <td class="{{ $cellIndex===0?'is-primary':'' }}">{{ $value }}</td>
                        @endforeach
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        @else
        <div class="an-empty">
            <span><i data-lucide="{{ $tab==='search'?'search-x':'database' }}"></i></span>
            <h3>{{ $tab==='search' ? (($readiness['level'] ?? '')==='good' ? 'No searches in this period' : 'Search event tracking is not connected') : 'No tracked records yet' }}</h3>
            <p>{{ $tab==='search' ? (($readiness['level'] ?? '')==='good' ? 'Real search rows will appear as visitors use AI Orbit search.' : 'This screen stays at zero until real search-query events are persisted. No synthetic analytics are shown.') : 'Records will appear here when this analytics source contains data.' }}</p>
        </div>
        @endif
    </section>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const canvas = document.getElementById('analyticsChart');
    if (canvas && typeof Chart !== 'undefined') {
        const ctx = canvas.getContext('2d');
        const gradient = ctx.createLinearGradient(0,0,0,310);
        gradient.addColorStop(0,'rgba(129,140,248,.34)');
        gradient.addColorStop(1,'rgba(129,140,248,0)');
        new Chart(canvas,{
            type:'line',
            data:{
                labels:@json($points->pluck('label')->all()),
                datasets:[
                    {
                        label:@json($chart['series_label'] ?? 'Value'),
                        data:@json($points->pluck('value')->all()),
                        borderColor:'#818cf8',
                        backgroundColor:gradient,
                        borderWidth:2,
                        pointRadius:0,
                        pointHoverRadius:4,
                        pointHoverBackgroundColor:'#c7d2fe',
                        tension:.38,
                        fill:true
                    }
                    @if($secondaryPoints->isNotEmpty())
                    ,{
                        label:@json($chart['secondary_label'] ?? 'Secondary'),
                        data:@json($secondaryPoints->pluck('value')->all()),
                        borderColor:'#34d399',
                        backgroundColor:'transparent',
                        borderWidth:1.6,
                        borderDash:[5,4],
                        pointRadius:0,
                        pointHoverRadius:4,
                        tension:.34,
                        fill:false
                    }
                    @endif
                ]
            },
            options:{
                responsive:true,
                maintainAspectRatio:false,
                interaction:{intersect:false,mode:'index'},
                plugins:{
                    legend:{display:{{ $secondaryPoints->isNotEmpty() ? 'true' : 'false' }},labels:{color:'#8f9bb0',boxWidth:8,boxHeight:8,usePointStyle:true,font:{size:10}}},
                    tooltip:{backgroundColor:'#111827',titleColor:'#f8fafc',bodyColor:'#aeb9cc',borderColor:'rgba(255,255,255,.08)',borderWidth:1,padding:11,displayColors:false}
                },
                scales:{
                    x:{border:{display:false},grid:{display:false},ticks:{color:'#68758b',font:{size:10},maxRotation:0,autoSkip:true,maxTicksLimit:10}},
                    y:{beginAtZero:true,border:{display:false},grid:{color:'rgba(255,255,255,.045)'},ticks:{color:'#68758b',font:{size:10},precision:0}}
                }
            }
        });
    }
    if (typeof lucide !== 'undefined') lucide.createIcons();
});
</script>
@endpush
