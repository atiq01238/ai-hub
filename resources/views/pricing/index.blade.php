@extends('layouts.admin')
@section('title', $onlyApi ? 'API Pricing' : 'Pricing Intelligence')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/pages/pricing.css') }}">
@endpush

@section('content')
@php
    $pagePlans = $plans->getCollection();
    $monitored = $pagePlans->filter(fn($plan) => $plan->sources->where('enabled', true)->count() > 0)->count();
    $pending = $pagePlans->sum('pending_changes_count');
    $withApi = $pagePlans->filter(fn($plan) => filled($plan->api_price_label))->count();
@endphp

<div class="pricing-page">
    <x-page-header
        :title="$onlyApi ? 'API Pricing Intelligence' : 'Pricing Intelligence'"
        :subtitle="$onlyApi ? 'Monitor API pricing labels and official pricing sources across AI products.' : 'Manage approved plans, official sources and admin-reviewed pricing changes.'"
        :breadcrumb="['Pricing', $onlyApi ? 'API Pricing' : 'Pricing Plans']"
    >
        <x-slot:actions>
            <a href="{{ route('admin.pricing.changes') }}" class="btn btn-secondary">
                <i data-lucide="radar"></i>
                Review Changes
                @if($pending)
                    <span class="pricing-action-count">{{ $pending }}</span>
                @endif
            </a>
            @if(auth()->user()->canAccessModule('Pricing', 'Add'))
                <a href="{{ route('admin.pricing.create') }}" class="btn btn-primary">
                    <i data-lucide="plus"></i>
                    Add Pricing Plan
                </a>
            @endif
        </x-slot:actions>
    </x-page-header>

    @if(session('status'))
        <div class="alert alert-success pricing-flash">
            <i data-lucide="check-circle-2"></i>
            <span>{{ session('status') }}</span>
        </div>
    @endif

    <nav class="pricing-tabs" aria-label="Pricing sections">
        <a href="{{ route('admin.pricing.index') }}" class="{{ !$onlyApi ? 'is-active' : '' }}">
            <i data-lucide="credit-card"></i>
            Pricing Plans
        </a>
        <a href="{{ route('admin.pricing.api') }}" class="{{ $onlyApi ? 'is-active' : '' }}">
            <i data-lucide="server-cog"></i>
            API Pricing
        </a>
        <a href="{{ route('admin.pricing.changes') }}">
            <i data-lucide="scan-search"></i>
            Detected Changes
        </a>
        <a href="{{ route('admin.pricing.history') }}">
            <i data-lucide="history"></i>
            Price History
        </a>
    </nav>

    <section class="pricing-summary-grid">
        <article class="pricing-stat">
            <span class="pricing-stat__icon"><i data-lucide="layers-3"></i></span>
            <div>
                <span class="pricing-eyebrow">Directory</span>
                <strong>{{ number_format($plans->total()) }}</strong>
                <small>{{ $onlyApi ? 'API-enabled plans' : 'Pricing plans' }}</small>
            </div>
        </article>
        <article class="pricing-stat">
            <span class="pricing-stat__icon pricing-stat__icon--green"><i data-lucide="radio-tower"></i></span>
            <div>
                <span class="pricing-eyebrow">Monitoring</span>
                <strong>{{ $monitored }}</strong>
                <small>Monitored on current page</small>
            </div>
        </article>
        <article class="pricing-stat">
            <span class="pricing-stat__icon pricing-stat__icon--cyan"><i data-lucide="braces"></i></span>
            <div>
                <span class="pricing-eyebrow">API</span>
                <strong>{{ $withApi }}</strong>
                <small>API prices on current page</small>
            </div>
        </article>
        <article class="pricing-stat">
            <span class="pricing-stat__icon pricing-stat__icon--amber"><i data-lucide="circle-alert"></i></span>
            <div>
                <span class="pricing-eyebrow">Review queue</span>
                <strong>{{ number_format($pending) }}</strong>
                <small>Pending changes on current page</small>
            </div>
        </article>
    </section>

    <form method="GET" action="{{ $onlyApi ? route('admin.pricing.api') : route('admin.pricing.index') }}" class="card pricing-filter">
        <div class="pricing-search">
            <i data-lucide="search"></i>
            <input class="input" name="search" value="{{ request('search') }}" placeholder="Search tool or pricing plan...">
        </div>
        <button class="btn btn-secondary" type="submit">
            <i data-lucide="search"></i>
            Search
        </button>
        @if(request('search'))
            <a class="btn btn-ghost" href="{{ $onlyApi ? route('admin.pricing.api') : route('admin.pricing.index') }}">
                <i data-lucide="rotate-ccw"></i>
                Clear
            </a>
        @endif
    </form>

    <section class="card pricing-table-card">
        <div class="pricing-section-head">
            <div>
                <span class="pricing-eyebrow">{{ $onlyApi ? 'API Catalogue' : 'Approved Pricing' }}</span>
                <h2>{{ $onlyApi ? 'API pricing plans' : 'Pricing plan registry' }}</h2>
                <p>Approved values stay live until an administrator accepts a detected source change.</p>
            </div>
            <span class="pricing-count-pill">{{ number_format($plans->total()) }} records</span>
        </div>

        @if($plans->count())
            <div class="table-wrap">
                <table class="data-table pricing-table">
                    <thead>
                        <tr>
                            <th>Product / Plan</th>
                            <th>Monthly</th>
                            <th>Yearly</th>
                            <th>API Pricing</th>
                            <th>Monitoring</th>
                            <th>Review</th>
                            <th>Updated</th>
                            <th class="pricing-table__actions">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($plans as $plan)
                            @php $enabledSources = $plan->sources->where('enabled', true)->count(); @endphp
                            <tr>
                                <td>
                                    <div class="pricing-record">
                                        <span class="pricing-record__icon"><i data-lucide="package-2"></i></span>
                                        <div>
                                            <strong>{{ $plan->tool->name ?? 'Unlinked Tool' }}</strong>
                                            <span>{{ $plan->plan_name }}</span>
                                            @if($plan->credits || $plan->limits)
                                                <small>{{ collect([$plan->credits, $plan->limits])->filter()->implode(' · ') }}</small>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td><span class="pricing-money">{{ $plan->monthly_price !== null ? '$'.number_format((float)$plan->monthly_price, 2) : '—' }}</span></td>
                                <td><span class="pricing-money">{{ $plan->yearly_price !== null ? '$'.number_format((float)$plan->yearly_price, 2) : '—' }}</span></td>
                                <td><span class="pricing-api-label">{{ $plan->api_price_label ?: '—' }}</span></td>
                                <td>
                                    @if($enabledSources)
                                        <a class="pricing-monitor is-active" href="{{ route('admin.pricing.sources', $plan->id) }}">
                                            <i data-lucide="radio-tower"></i>
                                            {{ $enabledSources }} source{{ $enabledSources === 1 ? '' : 's' }}
                                        </a>
                                    @else
                                        <a class="pricing-monitor" href="{{ route('admin.pricing.sources', $plan->id) }}">
                                            <i data-lucide="circle-dashed"></i>
                                            Manual only
                                        </a>
                                    @endif
                                </td>
                                <td>
                                    @if($plan->pending_changes_count)
                                        <a href="{{ route('admin.pricing.changes', ['status' => 'pending']) }}" class="pricing-review-pill">
                                            <i data-lucide="triangle-alert"></i>
                                            {{ $plan->pending_changes_count }} review
                                        </a>
                                    @else
                                        <span class="pricing-muted">Clear</span>
                                    @endif
                                </td>
                                <td><span class="pricing-muted">{{ $plan->updated_at->format('M j, Y') }}</span></td>
                                <td>
                                    <div class="pricing-actions">
                                        <a href="{{ route('admin.pricing.sources', $plan->id) }}" class="icon-btn" title="Automatic sources">
                                            <i data-lucide="radar"></i>
                                        </a>
                                        @if(auth()->user()->canAccessModule('Pricing', 'Edit'))
                                            <a href="{{ route('admin.pricing.edit', $plan->id) }}" class="icon-btn" title="Edit pricing plan">
                                                <i data-lucide="pencil"></i>
                                            </a>
                                        @endif
                                        @if(auth()->user()->canAccessModule('Pricing', 'Delete'))
                                            <form action="{{ route('admin.pricing.destroy', $plan->id) }}" method="POST" onsubmit="return confirm('Remove this pricing plan?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="icon-btn icon-btn--danger" title="Delete pricing plan">
                                                    <i data-lucide="trash-2"></i>
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="pricing-pagination">
                <span>Showing {{ $plans->firstItem() ?? 0 }}–{{ $plans->lastItem() ?? 0 }} of {{ $plans->total() }}</span>
                <div>{{ $plans->links() }}</div>
            </div>
        @else
            <div class="pricing-empty">
                <span><i data-lucide="{{ $onlyApi ? 'braces' : 'credit-card' }}"></i></span>
                <h3>{{ request('search') ? 'No matching pricing plans' : ($onlyApi ? 'No API pricing yet' : 'No pricing plans yet') }}</h3>
                <p>{{ request('search') ? 'Try another tool or plan name.' : 'Add an approved pricing plan, then attach an official monitoring source.' }}</p>
                @if(auth()->user()->canAccessModule('Pricing', 'Add'))
                    <a class="btn btn-primary" href="{{ route('admin.pricing.create') }}"><i data-lucide="plus"></i>Add Pricing Plan</a>
                @endif
            </div>
        @endif
    </section>
</div>
@endsection
