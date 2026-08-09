@extends('layouts.admin')
@section('title', 'Price History')

@section('content')

<x-page-header title="Price History &amp; Changes" subtitle="Track pricing movement across the industry" :breadcrumb="['Pricing', 'Price History']" />

<form method="GET" class="filter-bar">
    <select class="select" name="tool_id" onchange="this.form.submit()">
        @foreach ($tools as $tool)
            <option value="{{ $tool->id }}" @selected($selectedTool && $selectedTool->id === $tool->id)>{{ $tool->name }}</option>
        @endforeach
    </select>
</form>

<div class="card card-pad" style="margin-bottom:20px;">
    <div class="section-title">{{ $selectedTool->name ?? 'No tool selected' }} — Price Timeline</div>
    @if ($timeline->isEmpty())
        <p class="text-sub">No price history recorded yet for this tool. Once you edit a pricing plan's monthly price, it'll show up here.</p>
    @else
        <canvas id="priceChart" height="80"></canvas>
    @endif
</div>

<div class="card">
    <div class="card-head"><h3>Recent Price Changes</h3></div>
    <div class="table-wrap">
    <table class="data-table">
        <thead><tr><th>Tool</th><th>Old Price</th><th>New Price</th><th>Change</th><th>Type</th><th>Date</th></tr></thead>
        <tbody>
        @forelse ($changes as $change)
        @php
            $pct = ($change->old_price && $change->new_price)
                ? round((($change->new_price - $change->old_price) / $change->old_price) * 100)
                : null;
        @endphp
        <tr>
            <td><b>{{ $change->tool->name ?? '—' }}</b> <span class="text-sub">{{ $change->plan_name }}</span></td>
            <td class="mono text-muted">{{ $change->old_price !== null ? '$'.number_format($change->old_price, 0) : '—' }}</td>
            <td class="mono">{{ $change->new_price !== null ? '$'.number_format($change->new_price, 0) : '—' }}</td>
            <td>
                @if ($pct !== null)
                    <span class="badge {{ $pct > 0 ? 'badge-neg' : 'badge-pos' }}">{{ $pct > 0 ? '+' : '' }}{{ $pct }}%</span>
                @else
                    <span class="badge badge-info">{{ $change->change_type === 'new_plan' ? 'New' : 'Removed' }}</span>
                @endif
            </td>
            <td>
                <span class="badge {{ match($change->change_type) {
                    'increase' => 'badge-warn', 'decrease' => 'badge-pos',
                    'new_plan' => 'badge-info', default => 'badge-neutral',
                } }}">{{ ucfirst(str_replace('_', ' ', $change->change_type)) }}</span>
            </td>
            <td class="cell-sub">{{ $change->created_at->format('M j') }}</td>
        </tr>
        @empty
        <tr><td colspan="6" class="text-sub" style="text-align:center; padding:32px;">No price changes recorded yet.</td></tr>
        @endforelse
        </tbody>
    </table>
    </div>
    <div class="pager">{{ $changes->links() }}</div>
</div>
@endsection

@push('scripts')
@if ($timeline->isNotEmpty())
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
<script>
new Chart(document.getElementById('priceChart'), {
    type: 'line',
    data: {
        labels: {!! json_encode($timeline->map(fn($t) => $t->created_at->format('M j'))) !!},
        datasets: [{
            label: 'Monthly Price ($)',
            data: {!! json_encode($timeline->map(fn($t) => $t->new_price)) !!},
            borderColor: '#5b7fff', backgroundColor: 'rgba(91,127,255,.08)',
            fill: true, stepped: true, borderWidth: 2, pointRadius: 3,
        }],
    },
    options: {
        plugins: { legend: { labels: { color: '#9aa3b8' } } },
        scales: {
            x: { grid: { color: 'rgba(255,255,255,.04)' }, ticks: { color: '#5c6580' } },
            y: { grid: { color: 'rgba(255,255,255,.04)' }, ticks: { color: '#5c6580' } },
        },
    },
});
</script>
@endif
@endpush
