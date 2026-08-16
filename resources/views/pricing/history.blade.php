@extends('layouts.admin')
@section('title', 'Price History')
@section('content')
<x-page-header title="Price History" subtitle="Approved and manual pricing changes by tool, plan, and metric" :breadcrumb="['Pricing', 'Price History']" />

<form method="GET" class="filter-bar" style="display:flex;gap:10px;flex-wrap:wrap;">
    <select class="select" name="tool_id" onchange="this.form.submit()">
        @foreach($tools as $tool)<option value="{{ $tool->id }}" @selected($selectedTool && $selectedTool->id===$tool->id)>{{ $tool->name }}</option>@endforeach
    </select>
    <select class="select" name="plan_name" onchange="this.form.submit()">
        @foreach($planNames as $name)<option value="{{ $name }}" @selected($selectedPlanName===$name)>{{ $name }}</option>@endforeach
    </select>
    <select class="select" name="metric" onchange="this.form.submit()">
        <option value="monthly_price" @selected($metric==='monthly_price')>Monthly Price</option>
        <option value="yearly_price" @selected($metric==='yearly_price')>Yearly Price</option>
        <option value="api_price_label" @selected($metric==='api_price_label')>API Price</option>
    </select>
</form>

<div class="card card-pad" style="margin-bottom:20px;">
    <div class="section-title">{{ $selectedTool->name ?? 'No tool' }} — {{ $selectedPlanName ?? 'No plan' }} — {{ ucwords(str_replace('_',' ',$metric)) }}</div>
    @if($timeline->isEmpty())<p class="text-sub">No numeric timeline recorded for this selection yet.</p>@else<canvas id="priceChart" height="80"></canvas>@endif
</div>

<div class="card"><div class="card-head"><h3>Change Log</h3></div><div class="table-wrap"><table class="data-table">
<thead><tr><th>Tool / Plan</th><th>Metric</th><th>Old</th><th>New</th><th>Type</th><th>Source</th><th>Date</th></tr></thead>
<tbody>
@forelse($changes as $change)
<tr>
<td><b>{{ $change->tool->name ?? '—' }}</b><div class="text-sub">{{ $change->plan_name }}</div></td>
<td><span class="badge badge-info">{{ ucwords(str_replace('_',' ',$change->metric ?? 'monthly_price')) }}</span></td>
<td class="mono text-muted">{{ $change->old_value ?? ($change->old_price !== null ? '$'.$change->old_price : '—') }}</td>
<td class="mono">{{ $change->new_value ?? ($change->new_price !== null ? '$'.$change->new_price : '—') }}</td>
<td><span class="badge {{ match($change->change_type){'increase'=>'badge-warn','decrease'=>'badge-pos','new_plan'=>'badge-info',default=>'badge-neutral'} }}">{{ ucfirst(str_replace('_',' ',$change->change_type)) }}</span></td>
<td>@if($change->source_url)<a href="{{ $change->source_url }}" target="_blank" rel="noopener noreferrer" class="text-sub">Official source</a>@else<span class="text-sub">Manual</span>@endif</td>
<td class="cell-sub">{{ $change->created_at->format('M j, Y H:i') }}</td>
</tr>
@empty<tr><td colspan="7" class="text-sub" style="text-align:center;padding:32px;">No price history for this selection.</td></tr>@endforelse
</tbody></table></div><div class="pager">{{ $changes->links() }}</div></div>
@endsection

@push('scripts')
@if($timeline->isNotEmpty())
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
<script>
new Chart(document.getElementById('priceChart'),{type:'line',data:{labels:{!! json_encode($timeline->map(fn($t)=>$t->created_at->format('M j'))) !!},datasets:[{label:'Price',data:{!! json_encode($timeline->map(fn($t)=>$t->new_price)) !!},fill:true,stepped:true,borderWidth:2,pointRadius:3}]},options:{plugins:{legend:{labels:{color:'#9aa3b8'}}},scales:{x:{ticks:{color:'#5c6580'}},y:{ticks:{color:'#5c6580'}}}}});
</script>
@endif
@endpush
