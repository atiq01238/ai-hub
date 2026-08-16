@extends('layouts.admin')
@section('title', 'Pricing Management')

@section('content')
<x-page-header title="Pricing Management" subtitle="Approved prices, official sources, and automatic change monitoring" :breadcrumb="['Pricing', $onlyApi ? 'API Pricing' : 'Pricing Plans']">
    <x-slot:actions>
        <a href="{{ route('admin.pricing.changes') }}" class="btn btn-secondary btn-sm"><i data-lucide="radar"></i> Review Detected Changes</a>
        <a href="{{ route('admin.pricing.create') }}" class="btn btn-primary btn-sm"><i data-lucide="plus"></i> Add Pricing Plan</a>
    </x-slot:actions>
</x-page-header>

@if (session('status'))
    <div class="alert alert-success" style="margin-bottom:16px;">{{ session('status') }}</div>
@endif

<div class="tabs">
    <a href="{{ route('admin.pricing.index') }}" class="tab {{ !$onlyApi ? 'is-active' : '' }}">Pricing Plans</a>
    <a href="{{ route('admin.pricing.api') }}" class="tab {{ $onlyApi ? 'is-active' : '' }}">API Pricing</a>
    <a href="{{ route('admin.pricing.changes') }}" class="tab">Detected Changes</a>
    <a href="{{ route('admin.pricing.history') }}" class="tab">Price History</a>
</div>

<form method="GET" class="filter-bar" style="margin-bottom:16px;">
    <div style="display:flex; gap:10px; width:100%;">
        <input class="input" name="search" value="{{ request('search') }}" placeholder="Search tool or plan..." style="max-width:360px;">
        <button class="btn btn-secondary btn-sm" type="submit"><i data-lucide="search"></i> Search</button>
        @if(request('search'))
            <a class="btn btn-ghost btn-sm" href="{{ $onlyApi ? route('admin.pricing.api') : route('admin.pricing.index') }}">Clear</a>
        @endif
    </div>
</form>

<div class="card">
    <div class="table-wrap">
    <table class="data-table">
        <thead><tr><th>Tool</th><th>Plan</th><th>Monthly</th><th>Yearly</th><th>API</th><th>Monitoring</th><th>Pending</th><th>Last Updated</th><th></th></tr></thead>
        <tbody>
        @forelse ($plans as $plan)
        <tr>
            <td><b>{{ $plan->tool->name ?? '—' }}</b></td>
            <td class="text-sub">{{ $plan->plan_name }}</td>
            <td class="mono">{{ $plan->monthly_price !== null ? '$'.number_format((float)$plan->monthly_price, 2) : '—' }}</td>
            <td class="mono">{{ $plan->yearly_price !== null ? '$'.number_format((float)$plan->yearly_price, 2) : '—' }}</td>
            <td class="mono">{{ $plan->api_price_label ?? '—' }}</td>
            <td>
                @if($plan->sources->where('enabled', true)->count())
                    <span class="badge badge-pos">{{ $plan->sources->where('enabled', true)->count() }} source{{ $plan->sources->where('enabled', true)->count() === 1 ? '' : 's' }}</span>
                @else
                    <span class="badge badge-neutral">Manual only</span>
                @endif
            </td>
            <td>
                @if($plan->pending_changes_count)
                    <a href="{{ route('admin.pricing.changes') }}" class="badge badge-warn">{{ $plan->pending_changes_count }} review</a>
                @else
                    <span class="text-sub">—</span>
                @endif
            </td>
            <td class="cell-sub">{{ $plan->updated_at->format('M j, Y') }}</td>
            <td>
                <div class="flex gap-8">
                    <a href="{{ route('admin.pricing.sources', $plan->id) }}" class="icon-btn" title="Automatic sources"><i data-lucide="radar" style="width:14px;height:14px;"></i></a>
                    <a href="{{ route('admin.pricing.edit', $plan->id) }}" class="icon-btn" title="Edit"><i data-lucide="pencil" style="width:14px;height:14px;"></i></a>
                    <form action="{{ route('admin.pricing.destroy', $plan->id) }}" method="POST" onsubmit="return confirm('Remove this pricing plan?')" style="display:inline;">
                        @csrf @method('DELETE')
                        <button type="submit" class="icon-btn" title="Delete"><i data-lucide="trash-2" style="width:14px;height:14px;"></i></button>
                    </form>
                </div>
            </td>
        </tr>
        @empty
        <tr><td colspan="9" class="text-sub" style="text-align:center; padding:32px;">No pricing plans found.</td></tr>
        @endforelse
        </tbody>
    </table>
    </div>
    <div class="pager">
        <span>Showing {{ $plans->firstItem() ?? 0 }}–{{ $plans->lastItem() ?? 0 }} of {{ $plans->total() }}</span>
        <div class="pager-btns">{{ $plans->links() }}</div>
    </div>
</div>
@endsection
