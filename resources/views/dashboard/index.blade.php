@extends('layouts.admin')
@section('title', 'Dashboard')

@section('content')

<x-page-header title="Dashboard" subtitle="Welcome back, Sarah. Here's what's happening across the AI industry today.">
    <x-slot:actions>
        <button class="btn btn-secondary btn-sm"><i data-lucide="calendar"></i> Today</button>
        <button class="btn btn-primary btn-sm"><i data-lucide="download"></i> Export Report</button>
    </x-slot:actions>
</x-page-header>

{{-- KPI ROW --}}
<div class="kpi-grid">
    <x-kpi-card icon="wrench" label="Total AI Tools" value="1,284" delta="+4.2%" trend="up" />
    <x-kpi-card icon="brain-circuit" label="Total AI Models" value="642" delta="+2.8%" trend="up" />
    <x-kpi-card icon="building-2" label="AI Companies" value="318" delta="+1.1%" trend="up" />
    <x-kpi-card icon="columns-3" label="Total Comparisons" value="905" delta="+6.7%" trend="up" />
    <x-kpi-card icon="newspaper" label="AI News (24h)" value="187" delta="+18.9%" trend="up" />
    <x-kpi-card icon="star" label="AI Reviews" value="3,942" delta="+3.4%" trend="up" />
    <x-kpi-card icon="users" label="Registered Users" value="58,204" delta="+0.6%" trend="up" />
    <x-kpi-card icon="file-text" label="Published Articles" value="2,116" delta="-1.2%" trend="down" />
</div>

{{-- AI INDUSTRY OVERVIEW --}}
<div class="card" style="margin-bottom:20px;">
    <div class="card-head">
        <div>
            <h3>AI Industry Overview</h3>
            <div class="card-head__sub">Website views · tool searches · news activity · comparison activity</div>
        </div>
        <div class="filter-bar" style="margin:0;">
            <span class="chip">Today</span>
            <span class="chip">7 Days</span>
            <span class="chip is-active">30 Days</span>
            <span class="chip">3 Months</span>
            <span class="chip">1 Year</span>
        </div>
    </div>
    <div class="card-pad">
        <canvas id="industryChart" height="90"></canvas>
    </div>
</div>

<div class="grid-12" style="margin-bottom:20px;">
    {{-- TOP AI TOOLS BY VIEWS --}}
    <div class="col-8 card">
        <div class="card-head">
            <h3>Top AI Tools by Views</h3>
            <a href="{{ url('/analytics/tools') }}" class="btn btn-ghost btn-sm">View all</a>
        </div>
        <div class="table-wrap">
        <table class="data-table">
            <thead><tr><th>#</th><th>Tool</th><th>Category</th><th>Views</th><th>Growth</th></tr></thead>
            <tbody>
                @php
                    $topTools = [
                        ['rank'=>1,'name'=>'ChatGPT','init'=>'GP','cat'=>'Chatbot','views'=>'4.2M','growth'=>'+12.4%'],
                        ['rank'=>2,'name'=>'Claude','init'=>'CL','cat'=>'Chatbot','views'=>'3.1M','growth'=>'+22.1%'],
                        ['rank'=>3,'name'=>'Midjourney','init'=>'MJ','cat'=>'Image Gen','views'=>'2.4M','growth'=>'+8.7%'],
                        ['rank'=>4,'name'=>'Perplexity','init'=>'PX','cat'=>'Search','views'=>'1.8M','growth'=>'+31.5%'],
                        ['rank'=>5,'name'=>'Runway','init'=>'RW','cat'=>'Video Gen','views'=>'1.2M','growth'=>'-2.3%'],
                    ];
                @endphp
                @foreach($topTools as $t)
                <tr>
                    <td class="mono text-muted">{{ $t['rank'] }}</td>
                    <td><div class="row-media"><div class="thumb">{{ $t['init'] }}</div><b>{{ $t['name'] }}</b></div></td>
                    <td class="text-sub">{{ $t['cat'] }}</td>
                    <td class="mono">{{ $t['views'] }}</td>
                    <td><span class="badge {{ str_starts_with($t['growth'],'-') ? 'badge-neg' : 'badge-pos' }}">{{ $t['growth'] }}</span></td>
                </tr>
                @endforeach
            </tbody>
        </table>
        </div>
    </div>

    {{-- TRENDING AI --}}
    <div class="col-4 card">
        <div class="card-head"><h3>Trending AI</h3></div>
        <div class="card-pad" style="display:flex; flex-direction:column; gap:14px;">
            @php
                $trending = [
                    ['name'=>'Sora 2','type'=>'Model','trend'=>92,'move'=>'↑ 4'],
                    ['name'=>'Claude Opus 4.8','type'=>'Model','trend'=>88,'move'=>'↑ 1'],
                    ['name'=>'Genspark','type'=>'Tool','trend'=>81,'move'=>'↑ 9'],
                    ['name'=>'Ideogram v3','type'=>'Tool','trend'=>74,'move'=>'↓ 2'],
                ];
            @endphp
            @foreach($trending as $item)
            <div class="flex items-center justify-between">
                <div>
                    <div style="font-weight:600; font-size:13px;">{{ $item['name'] }}</div>
                    <div class="cell-sub">{{ $item['type'] }} · {{ $item['move'] }} rank</div>
                </div>
                <x-score-meter :value="$item['trend']" :segments="6" />
            </div>
            @endforeach
        </div>
    </div>
</div>

<div class="grid-12" style="margin-bottom:20px;">
    {{-- RECENT AI NEWS --}}
    <div class="col-8 card">
        <div class="card-head">
            <h3>Recent AI News</h3>
            <a href="{{ url('/news') }}" class="btn btn-ghost btn-sm">Open News Feed</a>
        </div>
        <div class="card-pad" style="display:flex; flex-direction:column; gap:0;">
            @php
                $recentNews = [
                    ['badge'=>'Breaking','tone'=>'neg','headline'=>'OpenAI announces GPT-5.2 Turbo with native agent orchestration','source'=>'The Information','time'=>'12m ago','cat'=>'New Model','importance'=>96],
                    ['badge'=>'New','tone'=>'info','headline'=>'Anthropic opens Claude Opus 4.8 to enterprise API tier','source'=>'Anthropic Blog','time'=>'44m ago','cat'=>'Product Update','importance'=>81],
                    ['badge'=>'Update','tone'=>'violet','headline'=>'Google DeepMind publishes new reasoning benchmark results','source'=>'DeepMind','time'=>'1h ago','cat'=>'Research','importance'=>72],
                    ['badge'=>'New','tone'=>'info','headline'=>'Runway raises $150M Series E for video generation research','source'=>'TechCrunch','time'=>'2h ago','cat'=>'Funding','importance'=>65],
                ];
            @endphp
            @foreach($recentNews as $n)
            <div class="flex items-center gap-12" style="padding:12px 0; border-bottom:1px solid var(--border-soft);">
                <div class="thumb lg">{{ substr($n['source'],0,2) }}</div>
                <div style="flex:1; min-width:0;">
                    <div class="flex items-center gap-8" style="margin-bottom:3px;">
                        <span class="badge badge-{{ $n['tone'] }}">{{ $n['badge'] }}</span>
                        <span class="cell-sub">{{ $n['cat'] }}</span>
                    </div>
                    <div style="font-size:13.5px; font-weight:600; line-height:1.4;">{{ $n['headline'] }}</div>
                    <div class="cell-sub">{{ $n['source'] }} · {{ $n['time'] }}</div>
                </div>
                <x-score-meter :value="$n['importance']" :segments="5" />
            </div>
            @endforeach
        </div>
    </div>

    {{-- TOP SEARCH QUERIES --}}
    <div class="col-4 card">
        <div class="card-head"><h3>Top Search Queries</h3></div>
        <div class="card-pad" style="display:flex; flex-direction:column; gap:13px;">
            @php
                $queries = [
                    ['q'=>'best AI video generator','vol'=>'12,450','growth'=>'+38%'],
                    ['q'=>'claude vs chatgpt','vol'=>'9,820','growth'=>'+21%'],
                    ['q'=>'free ai image generator','vol'=>'8,110','growth'=>'+14%'],
                    ['q'=>'ai coding assistant','vol'=>'6,730','growth'=>'+9%'],
                    ['q'=>'ai agents for business','vol'=>'5,290','growth'=>'+52%'],
                ];
            @endphp
            @foreach($queries as $q)
            <div class="flex items-center justify-between">
                <div>
                    <div style="font-size:13px; font-weight:600;">{{ $q['q'] }}</div>
                    <div class="cell-sub">{{ $q['vol'] }} searches</div>
                </div>
                <span class="badge badge-pos">{{ $q['growth'] }}</span>
            </div>
            @endforeach
        </div>
    </div>
</div>

<div class="grid-12">
    {{-- RECENTLY ADDED TOOLS --}}
    <div class="col-6 card">
        <div class="card-head"><h3>Recently Added AI Tools</h3></div>
        <div class="table-wrap">
        <table class="data-table">
            <thead><tr><th>Tool</th><th>Company</th><th>Added</th><th>Status</th></tr></thead>
            <tbody>
                <tr><td><b>Lumen AI</b></td><td class="text-sub">Lumen Labs</td><td class="cell-sub">Aug 5</td><td><x-status-badge status="Published" type="pos" /></td></tr>
                <tr><td><b>PromptForge</b></td><td class="text-sub">Forge Inc.</td><td class="cell-sub">Aug 4</td><td><x-status-badge status="Draft" type="neutral" /></td></tr>
                <tr><td><b>VoiceCraft</b></td><td class="text-sub">Craft AI</td><td class="cell-sub">Aug 3</td><td><x-status-badge status="Published" type="pos" /></td></tr>
                <tr><td><b>CodePilot X</b></td><td class="text-sub">Pilot Labs</td><td class="cell-sub">Aug 2</td><td><x-status-badge status="Pending" type="warn" /></td></tr>
            </tbody>
        </table>
        </div>
    </div>

    {{-- PRICE CHANGES --}}
    <div class="col-6 card">
        <div class="card-head"><h3>Price Changes</h3></div>
        <div class="table-wrap">
        <table class="data-table">
            <thead><tr><th>Tool</th><th>Old</th><th>New</th><th>Change</th></tr></thead>
            <tbody>
                <tr><td><b>ChatGPT Plus</b></td><td class="mono text-muted">$20</td><td class="mono">$22</td><td><span class="badge badge-neg">+10%</span></td></tr>
                <tr><td><b>Midjourney Pro</b></td><td class="mono text-muted">$60</td><td class="mono">$48</td><td><span class="badge badge-pos">-20%</span></td></tr>
                <tr><td><b>Claude Pro</b></td><td class="mono text-muted">$20</td><td class="mono">$20</td><td><span class="badge badge-neutral">New Plan</span></td></tr>
                <tr><td><b>Runway Standard</b></td><td class="mono text-muted">$15</td><td class="mono">$12</td><td><span class="badge badge-pos">-20%</span></td></tr>
            </tbody>
        </table>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
<script>
const ctx = document.getElementById('industryChart');
new Chart(ctx, {
    type: 'line',
    data: {
        labels: ['Jul 08','Jul 12','Jul 16','Jul 20','Jul 24','Jul 28','Aug 01','Aug 05'],
        datasets: [
            { label:'Website Views', data:[42,55,49,63,71,68,80,92], borderColor:'#5b7fff', backgroundColor:'rgba(91,127,255,.08)', tension:.4, fill:true, pointRadius:0, borderWidth:2 },
            { label:'Tool Searches', data:[30,34,38,40,44,49,52,60], borderColor:'#22d3ee', backgroundColor:'transparent', tension:.4, pointRadius:0, borderWidth:2 },
            { label:'News Activity', data:[18,22,20,28,35,30,38,44], borderColor:'#8b5cf6', backgroundColor:'transparent', tension:.4, pointRadius:0, borderWidth:2 },
        ]
    },
    options: {
        plugins:{ legend:{ labels:{ color:'#9aa3b8', usePointStyle:true, boxWidth:8 } } },
        scales:{
            x:{ grid:{ color:'rgba(255,255,255,.04)' }, ticks:{ color:'#5c6580', font:{size:11} } },
            y:{ grid:{ color:'rgba(255,255,255,.04)' }, ticks:{ color:'#5c6580', font:{size:11} } }
        }
    }
});
</script>
@endpush
