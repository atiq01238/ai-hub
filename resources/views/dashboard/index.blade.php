@extends('layouts.admin')
@section('title', 'Dashboard')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/pages/dashboard.css') }}">
@endpush

@section('content')
@php
    $pendingTotal = (int) ($pending['reviews'] ?? 0) + (int) ($pending['submissions'] ?? 0);
    $firstName = trim(explode(' ', auth()->user()->name ?? 'Admin')[0] ?? 'Admin');
@endphp

<div class="dashboard-v4">
    <section class="dashboard-command" aria-labelledby="dashboard-title">
        <div class="dashboard-command__content">
            <div class="dashboard-command__eyebrow">
                <span class="dashboard-live-dot" aria-hidden="true"></span>
                <span>AI Hub Command Center</span>
                <span class="dashboard-command__divider" aria-hidden="true"></span>
                <span class="dashboard-command__status">Live platform overview</span>
            </div>

            <h1 id="dashboard-title">Good to see you, {{ $firstName }}.</h1>
            <p>
                Track platform growth, moderation workload, AI intelligence and pricing activity
                from one operational view.
            </p>
        </div>

        <div class="dashboard-command__actions">
            <a href="{{ route('admin.news.index') }}" class="btn btn-secondary">
                <i data-lucide="radio-tower"></i>
                Intelligence Feed
            </a>
            <a href="{{ route('admin.tools.create') }}" class="btn btn-primary">
                <i data-lucide="plus"></i>
                Add AI Tool
            </a>
        </div>
    </section>

    <div class="dashboard-section-heading">
        <div>
            <span class="dashboard-section-heading__kicker">Platform pulse</span>
            <h2>Core metrics</h2>
        </div>
        <span class="dashboard-section-heading__meta">Live database totals</span>
    </div>

    <section class="dashboard-metrics" aria-label="Platform metrics">
        @php
            $metricCards = [
                ['key' => 'tools',       'label' => 'AI Tools',          'icon' => 'wrench',          'tone' => 'blue'],
                ['key' => 'models',      'label' => 'AI Models',         'icon' => 'brain-circuit',   'tone' => 'violet'],
                ['key' => 'companies',   'label' => 'Companies',         'icon' => 'building-2',      'tone' => 'cyan'],
                ['key' => 'comparisons', 'label' => 'Comparisons',       'icon' => 'columns-3',       'tone' => 'indigo'],
                ['key' => 'news24h',     'label' => 'News · 24h',        'icon' => 'newspaper',       'tone' => 'emerald'],
                ['key' => 'reviews',     'label' => 'AI Reviews',        'icon' => 'star',            'tone' => 'amber'],
                ['key' => 'users',       'label' => 'Registered Users',  'icon' => 'users',           'tone' => 'rose'],
                ['key' => 'articles',    'label' => 'Articles',          'icon' => 'file-text',       'tone' => 'sky'],
            ];
        @endphp

        @foreach ($metricCards as $index => $metric)
            <article class="dashboard-metric dashboard-metric--{{ $metric['tone'] }}">
                <div class="dashboard-metric__top">
                    <span class="dashboard-metric__icon"><i data-lucide="{{ $metric['icon'] }}"></i></span>
                    <span class="dashboard-metric__index">{{ str_pad($index + 1, 2, '0', STR_PAD_LEFT) }}</span>
                </div>
                <div class="dashboard-metric__value">{{ number_format($kpis[$metric['key']] ?? 0) }}</div>
                <div class="dashboard-metric__label">{{ $metric['label'] }}</div>
            </article>
        @endforeach
    </section>

    @if ($pendingTotal > 0)
        <section class="dashboard-attention" aria-label="Items needing attention">
            <div class="dashboard-attention__icon"><i data-lucide="inbox"></i></div>
            <div class="dashboard-attention__copy">
                <span class="dashboard-attention__eyebrow">Action required</span>
                <strong>{{ number_format($pendingTotal) }} item{{ $pendingTotal === 1 ? '' : 's' }} waiting for review</strong>
                <p>
                    {{ number_format($pending['reviews'] ?? 0) }} review(s) and
                    {{ number_format($pending['submissions'] ?? 0) }} tool submission(s) are currently pending.
                </p>
            </div>
            <div class="dashboard-attention__actions">
                <a href="{{ route('admin.content.reviews.index') }}" class="btn btn-secondary btn-sm">Review queue</a>
                <a href="{{ route('admin.submissions.index') }}" class="btn btn-secondary btn-sm">Submissions</a>
            </div>
        </section>
    @endif

    <section class="dashboard-panel dashboard-growth-panel">
        <header class="dashboard-panel__head">
            <div>
                <span class="dashboard-panel__kicker">Growth intelligence</span>
                <h2>Content growth</h2>
                <p>Daily additions across tools, news and articles over the last 30 days.</p>
            </div>

            <div class="dashboard-chart-controls" role="group" aria-label="Chart series filter">
                <button type="button" class="dashboard-chart-filter is-active" data-series="all">All</button>
                <button type="button" class="dashboard-chart-filter" data-series="0">Tools</button>
                <button type="button" class="dashboard-chart-filter" data-series="1">News</button>
                <button type="button" class="dashboard-chart-filter" data-series="2">Articles</button>
            </div>
        </header>
        <div class="dashboard-panel__body dashboard-chart-area">
            <canvas id="growthChart" aria-label="Content growth chart"></canvas>
        </div>
    </section>

    <div class="dashboard-grid dashboard-grid--wide">
        <section class="dashboard-panel">
            <header class="dashboard-panel__head">
                <div>
                    <span class="dashboard-panel__kicker">Quality leaders</span>
                    <h2>Top rated AI tools</h2>
                    <p>Highest-rated published tools in the directory.</p>
                </div>
                <a href="{{ route('admin.tools.index') }}" class="dashboard-text-link">View all <i data-lucide="arrow-up-right"></i></a>
            </header>

            <div class="dashboard-table-wrap">
                <table class="dashboard-table">
                    <thead>
                        <tr>
                            <th>Rank</th>
                            <th>Tool</th>
                            <th>Company</th>
                            <th>Reviews</th>
                            <th>Rating</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse ($topRatedTools as $i => $tool)
                        <tr>
                            <td><span class="dashboard-rank">{{ str_pad($i + 1, 2, '0', STR_PAD_LEFT) }}</span></td>
                            <td>
                                <div class="dashboard-entity">
                                    <span class="dashboard-entity__avatar">{{ strtoupper(substr($tool->name, 0, 2)) }}</span>
                                    <a href="{{ route('admin.tools.show', $tool->id) }}">{{ $tool->name }}</a>
                                </div>
                            </td>
                            <td class="dashboard-muted">{{ $tool->company->name ?? '—' }}</td>
                            <td class="dashboard-mono">{{ number_format($tool->reviews()->count()) }}</td>
                            <td><span class="dashboard-rating"><i data-lucide="star"></i>{{ number_format($tool->rating, 1) }}</span></td>
                        </tr>
                    @empty
                        <tr><td colspan="5"><div class="dashboard-empty"><i data-lucide="package-open"></i><span>No published tools yet.</span></div></td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <section class="dashboard-panel">
            <header class="dashboard-panel__head">
                <div>
                    <span class="dashboard-panel__kicker">Model registry</span>
                    <h2>Latest AI models</h2>
                    <p>Recently added models and benchmark strength.</p>
                </div>
            </header>

            <div class="dashboard-panel__body dashboard-list">
                @forelse ($latestModels as $model)
                    <article class="dashboard-list-item">
                        <span class="dashboard-list-item__icon"><i data-lucide="brain-circuit"></i></span>
                        <div class="dashboard-list-item__main">
                            <strong>{{ $model->name }}</strong>
                            <span>{{ $model->company->name ?? 'Independent' }}</span>
                        </div>
                        <div class="dashboard-list-item__score">
                            <span>{{ (int) $model->benchmark_score }}</span>
                            <x-score-meter :value="(int) $model->benchmark_score" :segments="6" />
                        </div>
                    </article>
                @empty
                    <div class="dashboard-empty"><i data-lucide="brain"></i><span>No models yet.</span></div>
                @endforelse
            </div>
        </section>
    </div>

    <div class="dashboard-grid dashboard-grid--wide">
        <section class="dashboard-panel">
            <header class="dashboard-panel__head">
                <div>
                    <span class="dashboard-panel__kicker">Intelligence stream</span>
                    <h2>Recent AI news</h2>
                    <p>Latest published intelligence from your news pipeline.</p>
                </div>
                <a href="{{ route('admin.news.index') }}" class="dashboard-text-link">Open feed <i data-lucide="arrow-up-right"></i></a>
            </header>

            <div class="dashboard-panel__body dashboard-news-list">
                @forelse ($recentNews as $n)
                    <article class="dashboard-news-item">
                        <span class="dashboard-news-item__avatar">{{ strtoupper(substr($n->source ?? $n->headline, 0, 2)) }}</span>
                        <div class="dashboard-news-item__content">
                            <div class="dashboard-news-item__meta">
                                @if ($n->category)<span class="dashboard-tag">{{ $n->category }}</span>@endif
                                @if ($n->source)<span>{{ $n->source }}</span>@endif
                                @if ($n->published_at)<span>{{ $n->published_at->diffForHumans() }}</span>@endif
                            </div>
                            <a href="{{ route('admin.news.show', $n->id) }}">{{ $n->headline }}</a>
                        </div>
                        <div class="dashboard-news-item__importance" title="Importance score">
                            <x-score-meter :value="$n->importance" :segments="5" />
                        </div>
                    </article>
                @empty
                    <div class="dashboard-empty"><i data-lucide="newspaper"></i><span>No published news yet.</span></div>
                @endforelse
            </div>
        </section>

        <section class="dashboard-panel dashboard-queue-panel">
            <header class="dashboard-panel__head">
                <div>
                    <span class="dashboard-panel__kicker">Moderation</span>
                    <h2>Approval queue</h2>
                    <p>Items currently waiting for administrator action.</p>
                </div>
            </header>

            <div class="dashboard-panel__body dashboard-queue">
                <a href="{{ route('admin.content.reviews.index') }}" class="dashboard-queue-item">
                    <span class="dashboard-queue-item__icon"><i data-lucide="message-square-text"></i></span>
                    <span class="dashboard-queue-item__copy"><strong>Reviews</strong><small>Awaiting moderation</small></span>
                    <span class="dashboard-queue-item__count {{ ($pending['reviews'] ?? 0) > 0 ? 'is-warning' : 'is-clear' }}">{{ number_format($pending['reviews'] ?? 0) }}</span>
                </a>

                <a href="{{ route('admin.submissions.index') }}" class="dashboard-queue-item">
                    <span class="dashboard-queue-item__icon"><i data-lucide="package-plus"></i></span>
                    <span class="dashboard-queue-item__copy"><strong>Tool submissions</strong><small>Awaiting review</small></span>
                    <span class="dashboard-queue-item__count {{ ($pending['submissions'] ?? 0) > 0 ? 'is-warning' : 'is-clear' }}">{{ number_format($pending['submissions'] ?? 0) }}</span>
                </a>

                <div class="dashboard-queue-summary">
                    <span><i data-lucide="activity"></i> Queue status</span>
                    <strong>{{ $pendingTotal > 0 ? 'Needs attention' : 'All clear' }}</strong>
                </div>
            </div>
        </section>
    </div>

    <div class="dashboard-grid dashboard-grid--equal">
        <section class="dashboard-panel">
            <header class="dashboard-panel__head">
                <div>
                    <span class="dashboard-panel__kicker">Directory activity</span>
                    <h2>Recently added tools</h2>
                    <p>Newest entries in the AI tools directory.</p>
                </div>
            </header>
            <div class="dashboard-table-wrap">
                <table class="dashboard-table">
                    <thead><tr><th>Tool</th><th>Company</th><th>Added</th><th>Status</th></tr></thead>
                    <tbody>
                    @forelse ($recentTools as $tool)
                        <tr>
                            <td><a href="{{ route('admin.tools.show', $tool->id) }}" class="dashboard-strong-link">{{ $tool->name }}</a></td>
                            <td class="dashboard-muted">{{ $tool->company->name ?? '—' }}</td>
                            <td class="dashboard-muted">{{ $tool->created_at->format('M j') }}</td>
                            <td><x-status-badge status="{{ ucfirst($tool->status) }}" type="{{ $tool->status === 'published' ? 'pos' : 'neutral' }}" /></td>
                        </tr>
                    @empty
                        <tr><td colspan="4"><div class="dashboard-empty"><i data-lucide="package"></i><span>No tools yet.</span></div></td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <section class="dashboard-panel">
            <header class="dashboard-panel__head">
                <div>
                    <span class="dashboard-panel__kicker">Pricing intelligence</span>
                    <h2>Recent price changes</h2>
                    <p>Latest tracked pricing movements across AI tools.</p>
                </div>
                <a href="{{ route('admin.pricing.history') }}" class="dashboard-text-link">View history <i data-lucide="arrow-up-right"></i></a>
            </header>
            <div class="dashboard-table-wrap">
                <table class="dashboard-table">
                    <thead><tr><th>Tool</th><th>Old</th><th>New</th><th>Change</th></tr></thead>
                    <tbody>
                    @forelse ($priceChanges as $change)
                        @php
                            $pct = ($change->old_price && $change->new_price)
                                ? round((($change->new_price - $change->old_price) / $change->old_price) * 100)
                                : null;
                        @endphp
                        <tr>
                            <td><strong>{{ $change->tool->name ?? '—' }}</strong></td>
                            <td class="dashboard-mono dashboard-muted">{{ $change->old_price !== null ? '$'.number_format($change->old_price, 0) : '—' }}</td>
                            <td class="dashboard-mono">{{ $change->new_price !== null ? '$'.number_format($change->new_price, 0) : '—' }}</td>
                            <td>
                                @if ($pct !== null)
                                    <span class="badge {{ $pct > 0 ? 'badge-neg' : 'badge-pos' }}">{{ $pct > 0 ? '+' : '' }}{{ $pct }}%</span>
                                @else
                                    <span class="badge badge-neutral">{{ $change->change_type === 'new_plan' ? 'New Plan' : 'Removed' }}</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4"><div class="dashboard-empty"><i data-lucide="badge-dollar-sign"></i><span>No price changes recorded yet.</span></div></td></tr>
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
document.addEventListener('DOMContentLoaded', () => {
    const canvas = document.getElementById('growthChart');
    if (!canvas || typeof Chart === 'undefined') return;

    const css = getComputedStyle(document.documentElement);
    const muted = css.getPropertyValue('--muted').trim() || '#8d98ad';
    const grid = css.getPropertyValue('--border-soft').trim() || 'rgba(148,163,184,.12)';

    const growthChart = new Chart(canvas, {
        type: 'line',
        data: {
            labels: @json($chart['labels'] ?? []),
            datasets: [
                {
                    label: 'Tools',
                    data: @json($chart['tools'] ?? []),
                    borderColor: '#6d8cff',
                    backgroundColor: 'rgba(109,140,255,.10)',
                    fill: true,
                    tension: .38,
                    borderWidth: 2,
                    pointRadius: 0,
                    pointHoverRadius: 4
                },
                {
                    label: 'News',
                    data: @json($chart['news'] ?? []),
                    borderColor: '#22d3ee',
                    backgroundColor: 'transparent',
                    fill: false,
                    tension: .38,
                    borderWidth: 2,
                    pointRadius: 0,
                    pointHoverRadius: 4
                },
                {
                    label: 'Articles',
                    data: @json($chart['articles'] ?? []),
                    borderColor: '#9b7cff',
                    backgroundColor: 'transparent',
                    fill: false,
                    tension: .38,
                    borderWidth: 2,
                    pointRadius: 0,
                    pointHoverRadius: 4
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: { mode: 'index', intersect: false },
            plugins: {
                legend: {
                    position: 'top',
                    align: 'start',
                    labels: {
                        color: muted,
                        usePointStyle: true,
                        pointStyle: 'circle',
                        boxWidth: 7,
                        boxHeight: 7,
                        padding: 18,
                        font: { size: 11, weight: 600 }
                    }
                },
                tooltip: {
                    backgroundColor: 'rgba(9,13,22,.96)',
                    borderColor: grid,
                    borderWidth: 1,
                    titleColor: '#f4f7ff',
                    bodyColor: '#c5cedd',
                    padding: 12,
                    displayColors: true
                }
            },
            scales: {
                x: {
                    grid: { display: false },
                    ticks: { color: muted, maxTicksLimit: 8, font: { size: 10 } },
                    border: { display: false }
                },
                y: {
                    beginAtZero: true,
                    grid: { color: grid, drawTicks: false },
                    ticks: { color: muted, precision: 0, padding: 8, font: { size: 10 } },
                    border: { display: false }
                }
            }
        }
    });

    document.querySelectorAll('.dashboard-chart-filter').forEach((button) => {
        button.addEventListener('click', () => {
            document.querySelectorAll('.dashboard-chart-filter').forEach((item) => item.classList.remove('is-active'));
            button.classList.add('is-active');

            const series = button.dataset.series;
            growthChart.data.datasets.forEach((dataset, index) => {
                growthChart.setDatasetVisibility(index, series === 'all' || Number(series) === index);
            });
            growthChart.update();
        });
    });
});
</script>
@endpush