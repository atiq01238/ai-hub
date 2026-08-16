@extends('layouts.admin')
@section('title', 'Detected Price Changes')
@section('content')
<x-page-header title="Detected Price Changes" subtitle="External changes are held here until an admin approves or rejects them" :breadcrumb="['Pricing', 'Price Changes']">
    <x-slot:actions>
        <form method="POST" action="{{ route('admin.pricing.scan') }}">@csrf<button class="btn btn-primary btn-sm" type="submit"><i data-lucide="radar"></i> Scan All Sources Now</button></form>
    </x-slot:actions>
</x-page-header>

@if(session('status'))<div class="alert alert-success" style="margin-bottom:16px;">{{ session('status') }}</div>@endif

<div class="tabs">
    <a class="tab {{ $status==='pending'?'is-active':'' }}" href="{{ route('admin.pricing.changes',['status'=>'pending']) }}">Pending ({{ $counts['pending'] }})</a>
    <a class="tab {{ $status==='approved'?'is-active':'' }}" href="{{ route('admin.pricing.changes',['status'=>'approved']) }}">Approved ({{ $counts['approved'] }})</a>
    <a class="tab {{ $status==='rejected'?'is-active':'' }}" href="{{ route('admin.pricing.changes',['status'=>'rejected']) }}">Rejected ({{ $counts['rejected'] }})</a>
    <a class="tab {{ $status==='all'?'is-active':'' }}" href="{{ route('admin.pricing.changes',['status'=>'all']) }}">All</a>
</div>

<div class="card">
<div class="table-wrap"><table class="data-table">
<thead><tr><th>Tool / Plan</th><th>Metric</th><th>Current</th><th>Detected</th><th>Source</th><th>Detected</th><th>Status / Review</th></tr></thead>
<tbody>
@forelse($changes as $change)
<tr>
    <td><b>{{ $change->tool->name ?? '—' }}</b><div class="text-sub">{{ $change->plan->plan_name ?? '—' }}</div></td>
    <td><span class="badge badge-info">{{ ucwords(str_replace('_',' ',$change->metric)) }}</span></td>
    <td class="mono">{{ $change->current_value ?? '—' }}</td>
    <td class="mono" style="font-weight:700;">{{ $change->detected_value ?? '—' }}</td>
    <td>@if($change->source_url)<a href="{{ $change->source_url }}" target="_blank" rel="noopener noreferrer" class="btn btn-secondary btn-sm"><i data-lucide="external-link"></i> Official Source</a>@else — @endif</td>
    <td class="cell-sub">{{ $change->detected_at?->format('M j, Y H:i') ?? $change->created_at->format('M j, Y H:i') }}</td>
    <td style="min-width:260px;">
        @if($change->status === 'pending')
            <div style="display:flex;gap:8px;flex-wrap:wrap;">
                <form method="POST" action="{{ route('admin.pricing.changes.approve',$change->id) }}" onsubmit="return confirm('Approve this detected value and update the live pricing plan?')">@csrf<button class="btn btn-primary btn-sm" type="submit"><i data-lucide="check"></i> Approve & Update</button></form>
                <form method="POST" action="{{ route('admin.pricing.changes.reject',$change->id) }}">@csrf<button class="btn btn-secondary btn-sm" type="submit"><i data-lucide="x"></i> Reject</button></form>
            </div>
        @else
            <span class="badge {{ $change->status==='approved'?'badge-pos':'badge-neutral' }}">{{ ucfirst($change->status) }}</span>
            @if($change->reviewer)<div class="text-sub" style="font-size:11px;margin-top:5px;">by {{ $change->reviewer->name }}</div>@endif
        @endif
    </td>
</tr>
@empty<tr><td colspan="7" class="text-sub" style="text-align:center;padding:34px;">No {{ $status==='all'?'':$status }} detected price changes.</td></tr>@endforelse
</tbody></table></div>
<div class="pager">{{ $changes->links() }}</div>
</div>
@endsection
