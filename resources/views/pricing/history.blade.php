@extends('layouts.admin')
@section('title', 'Price History')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/pages/pricing.css') }}">
@endpush

@section('content')
<div class="pricing-page pricing-history">
    <x-page-header
        title="Price History"
        subtitle="Audit approved and manual pricing changes by product, plan and pricing metric."
        :breadcrumb="['Pricing', 'Price History']"
    >
        <x-slot:actions>
            <a href="{{ route('admin.pricing.index') }}" class="btn btn-secondary"><i data-lucide="credit-card"></i>Pricing Plans</a>
            <a href="{{ route('admin.pricing.changes') }}" class="btn btn-secondary"><i data-lucide="scan-search"></i>Detected Changes</a>
        </x-slot:actions>
    </x-page-header>

    <form method="GET" action="{{ route('admin.pricing.history') }}" class="card pricing-history-filter">
        <label>
            <span>Tool</span>
            <select class="select" name="tool_id" onchange="this.form.submit()">
                @foreach($tools as $tool)
                    <option value="{{ $tool->id }}" @selected($selectedTool && $selectedTool->id === $tool->id)>{{ $tool->name }}</option>
                @endforeach
            </select>
        </label>

        <label>
            <span>Plan</span>
            <select class="select" name="plan_name" onchange="this.form.submit()">
                @foreach($planNames as $name)
                    <option value="{{ $name }}" @selected($selectedPlanName === $name)>{{ $name }}</option>
                @endforeach
            </select>
        </label>

        <label>
            <span>Metric</span>
            <select class="select" name="metric" onchange="this.form.submit()">
                <option value="monthly_price" @selected($metric === 'monthly_price')>Monthly Price</option>
                <option value="yearly_price" @selected($metric === 'yearly_price')>Yearly Price</option>
                <option value="api_price_label" @selected($metric === 'api_price_label')>API Price</option>
            </select>
        </label>
    </form>

    <section class="card pricing-chart-card">
        <div class="pricing-section-head">
            <div>
                <span class="pricing-eyebrow">Timeline</span>
                <h2>{{ $selectedTool->name ?? 'No tool' }} · {{ $selectedPlanName ?? 'No plan' }}</h2>
                <p>{{ ucwords(str_replace('_',' ',$metric)) }} change history.</p>
            </div>
            <span class="pricing-count-pill">{{ $timeline->count() }} points</span>
        </div>

        @if($timeline->isEmpty())
            <div class="pricing-chart-empty">
                <i data-lucide="chart-no-axes-column"></i>
                <strong>No numeric timeline yet</strong>
                <span>Numeric history appears after approved monthly or yearly price changes are recorded.</span>
            </div>
        @else
            <div class="pricing-chart-wrap">
                <canvas id="priceChart" height="90"></canvas>
            </div>
        @endif
    </section>

    <section class="card pricing-table-card">
        <div class="pricing-section-head">
            <div>
                <span class="pricing-eyebrow">Audit Trail</span>
                <h2>Change log</h2>
                <p>Historical record of pricing transitions and their evidence source.</p>
            </div>
            <span class="pricing-count-pill">{{ number_format($changes->total()) }} records</span>
        </div>

        @if($changes->count())
            <div class="table-wrap">
                <table class="data-table pricing-history-table">
                    <thead>
                        <tr>
                            <th>Product / Plan</th>
                            <th>Metric</th>
                            <th>Old</th>
                            <th>New</th>
                            <th>Change</th>
                            <th>Source</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($changes as $change)
                            <tr>
                                <td>
                                    <div class="pricing-record pricing-record--compact">
                                        <span class="pricing-record__icon"><i data-lucide="history"></i></span>
                                        <div><strong>{{ $change->tool->name ?? 'Unknown Tool' }}</strong><span>{{ $change->plan_name }}</span></div>
                                    </div>
                                </td>
                                <td><span class="pricing-metric-pill">{{ ucwords(str_replace('_',' ',$change->metric ?? 'monthly_price')) }}</span></td>
                                <td><span class="pricing-money">{{ $change->old_value ?? ($change->old_price !== null ? '$'.number_format((float)$change->old_price,2) : '—') }}</span></td>
                                <td><span class="pricing-money">{{ $change->new_value ?? ($change->new_price !== null ? '$'.number_format((float)$change->new_price,2) : '—') }}</span></td>
                                <td>
                                    <span class="pricing-change-type pricing-change-type--{{ $change->change_type }}">
                                        <i data-lucide="{{ $change->change_type === 'decrease' ? 'trending-down' : ($change->change_type === 'removed_plan' ? 'circle-minus' : ($change->change_type === 'new_plan' ? 'circle-plus' : 'trending-up')) }}"></i>
                                        {{ ucwords(str_replace('_',' ',$change->change_type)) }}
                                    </span>
                                </td>
                                <td>
                                    @if($change->source_url)
                                        <a class="pricing-source-link" href="{{ $change->source_url }}" target="_blank" rel="noopener noreferrer"><i data-lucide="external-link"></i>Source</a>
                                    @else
                                        <span class="pricing-muted">Manual / internal</span>
                                    @endif
                                </td>
                                <td><span class="pricing-muted">{{ $change->created_at->format('M j, Y H:i') }}</span></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="pricing-pagination">
                <span>Showing {{ $changes->firstItem() ?? 0 }}–{{ $changes->lastItem() ?? 0 }} of {{ $changes->total() }}</span>
                <div>{{ $changes->links() }}</div>
            </div>
        @else
            <div class="pricing-empty">
                <span><i data-lucide="history"></i></span>
                <h3>No history records</h3>
                <p>No pricing changes have been recorded for this selection yet.</p>
            </div>
        @endif
    </section>
</div>
@endsection

@if(!$timeline->isEmpty())
@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const canvas = document.getElementById('priceChart');
    if (!canvas || typeof Chart === 'undefined') return;

    const labels = @json($timeline->map(fn($item) => $item->created_at->format('M j, Y'))->values());
    const values = @json($timeline->map(fn($item) => (float)$item->new_price)->values());

    new Chart(canvas, {
        type: 'line',
        data: {
            labels,
            datasets: [{
                label: 'Approved price',
                data: values,
                fill: true,
                stepped: true,
                borderWidth: 2,
                pointRadius: 3,
                pointHoverRadius: 5,
                tension: 0
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: { intersect: false, mode: 'index' },
            plugins: {
                legend: { labels: { color: '#9aa6ba', boxWidth: 12, usePointStyle: true } },
                tooltip: {
                    callbacks: {
                        label: ctx => ` $${Number(ctx.parsed.y).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}`
                    }
                }
            },
            scales: {
                x: { grid: { display: false }, ticks: { color: '#758198' } },
                y: {
                    beginAtZero: false,
                    grid: { color: 'rgba(148,163,184,.08)' },
                    ticks: {
                        color: '#758198',
                        callback: value => '$' + Number(value).toLocaleString()
                    }
                }
            }
        }
    });
});
</script>
@endpush
@endif
