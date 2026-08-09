@extends('layouts.admin')
@section('title', 'Pricing Management')

@section('content')

<x-page-header title="Pricing Management" subtitle="Plans, API pricing, and credits across all tools" :breadcrumb="['Pricing', 'Pricing Plans']">
    <x-slot:actions><a href="{{ route('admin.pricing.create') }}" class="btn btn-primary btn-sm"><i data-lucide="plus"></i> Add Pricing Plan</a></x-slot:actions>
</x-page-header>

@if (session('status'))
    <div class="alert alert-success" style="margin-bottom:16px;">{{ session('status') }}</div>
@endif

<div class="tabs">
    <a href="{{ route('admin.pricing.index') }}" class="tab {{ !$onlyApi ? 'is-active' : '' }}">Pricing Plans</a>
    <a href="{{ route('admin.pricing.api') }}" class="tab {{ $onlyApi ? 'is-active' : '' }}">API Pricing</a>
    <a href="{{ route('admin.pricing.history') }}" class="tab">Price History</a>
</div>

<div class="card">
    <div class="table-wrap">
    <table class="data-table">
        <thead><tr><th>Tool</th><th>Plan</th><th>Monthly</th><th>Yearly</th><th>API</th><th>Credits</th><th>Limits</th><th>Last Updated</th><th></th></tr></thead>
        <tbody>
        @forelse ($plans as $plan)
        <tr>
            <td><b>{{ $plan->tool->name ?? '—' }}</b></td>
            <td class="text-sub">{{ $plan->plan_name }}</td>
            <td class="mono">{{ $plan->monthly_price !== null ? '$'.number_format($plan->monthly_price, 0) : '—' }}</td>
            <td class="mono">{{ $plan->yearly_price !== null ? '$'.number_format($plan->yearly_price, 0) : '—' }}</td>
            <td class="mono">{{ $plan->api_price_label ?? '—' }}</td>
            <td class="text-sub">{{ $plan->credits ?? '—' }}</td>
            <td class="cell-sub">{{ $plan->limits ?? '—' }}</td>
            <td class="cell-sub">{{ $plan->updated_at->format('M j') }}</td>
            <td>
                <div class="flex gap-8">
                    <a href="{{ route('admin.pricing.edit', $plan->id) }}" class="icon-btn" style="width:28px;height:28px;"><i data-lucide="pencil" style="width:14px;height:14px;"></i></a>
                    <form action="{{ route('admin.pricing.destroy', $plan->id) }}" method="POST" onsubmit="return confirm('Remove this pricing plan?')" style="display:inline;">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="icon-btn" style="width:28px;height:28px;"><i data-lucide="trash-2" style="width:14px;height:14px;"></i></button>
                    </form>
                </div>
            </td>
        </tr>
        @empty
        <tr><td colspan="9" class="text-sub" style="text-align:center; padding:32px;">No pricing plans yet.</td></tr>
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
