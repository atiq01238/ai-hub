@extends('layouts.admin')
@section('title','Benchmark Results')
@section('content')
<style>
.br-card{background:rgba(255,255,255,.025);border:1px solid rgba(255,255,255,.07);border-radius:14px;overflow:hidden}.br-filters{display:grid;grid-template-columns:1.4fr 1fr 1fr auto;gap:10px;padding:14px}.br-table{width:100%;border-collapse:collapse}.br-table th,.br-table td{padding:13px 14px;border-top:1px solid rgba(255,255,255,.055);text-align:left;font-size:12px}.br-table th{color:#7f8ba0;font-size:10px;text-transform:uppercase;letter-spacing:.07em}.br-badge{display:inline-flex;padding:4px 8px;border-radius:999px;font-size:10px}.br-badge.ok{background:rgba(52,211,153,.1);color:#6ee7b7}.br-badge.no{background:rgba(245,158,11,.1);color:#fbbf24}.br-source{max-width:240px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}@media(max-width:800px){.br-filters{grid-template-columns:1fr}.br-table{min-width:900px}.br-scroll{overflow:auto}}
</style>
<x-page-header title="Benchmark Results" subtitle="Historical scores, evidence and verification" :breadcrumb="['Comparison & Benchmarks','Benchmark Results']">
<x-slot:actions><a href="{{ route('admin.benchmarks.create') }}" class="btn btn-primary btn-sm"><i data-lucide="plus"></i> Add Result</a></x-slot:actions></x-page-header>
@if(session('status'))<div class="alert alert-success">{{ session('status') }}</div>@endif
<div class="br-card">
<form method="GET" class="br-filters">
<select class="select" name="benchmark"><option value="">All Benchmarks</option>@foreach($benchmarks as $benchmark)<option value="{{ $benchmark->id }}" @selected((string)request('benchmark')===(string)$benchmark->id)>{{ $benchmark->name }}</option>@endforeach</select>
<select class="select" name="type"><option value="">All Types</option><option value="model" @selected(request('type')==='model')>AI Models</option><option value="tool" @selected(request('type')==='tool')>AI Tools</option></select>
<select class="select" name="verified"><option value="">All Verification</option><option value="1" @selected(request('verified')==='1')>Verified</option><option value="0" @selected(request('verified')==='0')>Unverified</option></select>
<button class="btn btn-secondary btn-sm"><i data-lucide="filter"></i> Filter</button>
</form>
<div class="br-scroll"><table class="br-table"><thead><tr><th>Date</th><th>Item</th><th>Benchmark</th><th>Score</th><th>Source</th><th>Status</th><th></th></tr></thead><tbody>
@forelse($results as $result)<tr><td>{{ optional($result->tested_at)->format('d M Y') ?? '—' }}</td><td><b>{{ $result->benchmarkable?->name ?? 'Deleted item' }}</b><br><small>{{ class_basename($result->benchmarkable_type)==='AiModel'?'Model':'Tool' }}</small></td><td>{{ $result->benchmark?->name }}</td><td><b>{{ rtrim(rtrim(number_format((float)$result->score,2,'.',''),'0'),'.') }}</b></td><td class="br-source">@if($result->source_url)<a href="{{ $result->source_url }}" target="_blank" rel="noopener">{{ $result->source_name ?: 'Open source' }}</a>@else{{ $result->source_name ?: '—' }}@endif</td><td><span class="br-badge {{ $result->verified?'ok':'no' }}">{{ $result->verified?'Verified':'Unverified' }}</span></td><td><form method="POST" action="{{ route('admin.benchmarks.results.destroy',$result->id) }}" onsubmit="return confirm('Delete this benchmark history record?')">@csrf @method('DELETE')<button class="btn btn-danger btn-sm"><i data-lucide="trash-2"></i></button></form></td></tr>@empty<tr><td colspan="7">No benchmark history found.</td></tr>@endforelse
</tbody></table></div></div>
<div style="margin-top:14px">{{ $results->links() }}</div>
@endsection
