@extends('layouts.admin')
@section('title', 'Detected Price Changes')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/pages/pricing.css') }}">
@endpush

@section('content')
<div class="pricing-page pricing-changes">
    <x-page-header
        title="Detected Price Changes"
        subtitle="Review official-source differences before they are allowed to update live pricing."
        :breadcrumb="['Pricing', 'Detected Changes']"
    >
        <x-slot:actions>
            <a href="{{ route('admin.pricing.index') }}" class="btn btn-secondary"><i data-lucide="credit-card"></i>Pricing Plans</a>
            <form method="POST" action="{{ route('admin.pricing.scan') }}">
                @csrf
                <button class="btn btn-primary" type="submit">
                    <i data-lucide="radar"></i>
                    Queue All Source Checks
                </button>
            </form>
        </x-slot:actions>
    </x-page-header>

    @if(session('status'))
        <div class="alert alert-success pricing-flash"><i data-lucide="check-circle-2"></i><span>{{ session('status') }}</span></div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger pricing-flash"><i data-lucide="circle-alert"></i><span>{{ session('error') }}</span></div>
    @endif

    <section class="pricing-review-summary">
        <a href="{{ route('admin.pricing.changes', ['status'=>'pending']) }}" class="pricing-review-stat {{ $status === 'pending' ? 'is-active' : '' }}">
            <span><i data-lucide="clock-3"></i></span>
            <div><small>Pending</small><strong>{{ number_format($counts['pending']) }}</strong></div>
        </a>
        <a href="{{ route('admin.pricing.changes', ['status'=>'approved']) }}" class="pricing-review-stat {{ $status === 'approved' ? 'is-active' : '' }}">
            <span class="is-green"><i data-lucide="badge-check"></i></span>
            <div><small>Approved</small><strong>{{ number_format($counts['approved']) }}</strong></div>
        </a>
        <a href="{{ route('admin.pricing.changes', ['status'=>'rejected']) }}" class="pricing-review-stat {{ $status === 'rejected' ? 'is-active' : '' }}">
            <span class="is-red"><i data-lucide="circle-x"></i></span>
            <div><small>Rejected</small><strong>{{ number_format($counts['rejected']) }}</strong></div>
        </a>
        <a href="{{ route('admin.pricing.changes', ['status'=>'all']) }}" class="pricing-review-stat {{ $status === 'all' ? 'is-active' : '' }}">
            <span><i data-lucide="list-filter"></i></span>
            <div><small>All</small><strong>{{ number_format(array_sum($counts)) }}</strong></div>
        </a>
    </section>

    <section class="card pricing-table-card">
        <div class="pricing-section-head">
            <div>
                <span class="pricing-eyebrow">Human Review Queue</span>
                <h2>{{ ucfirst($status) }} detected changes</h2>
                <p>Approve only when the official source is trustworthy and the detected value is correct.</p>
            </div>
            <span class="pricing-count-pill">{{ number_format($changes->total()) }} records</span>
        </div>

        @if($changes->count())
            <div class="table-wrap">
                <table class="data-table pricing-change-table">
                    <thead>
                        <tr>
                            <th>Product / Plan</th>
                            <th>Metric</th>
                            <th>Value Change</th>
                            <th>Official Source</th>
                            <th>Detected</th>
                            <th>Review State</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($changes as $change)
                            <tr>
                                <td>
                                    <div class="pricing-record">
                                        <span class="pricing-record__icon"><i data-lucide="scan-line"></i></span>
                                        <div>
                                            <strong>{{ $change->tool->name ?? 'Unknown Tool' }}</strong>
                                            <span>{{ $change->plan->plan_name ?? 'Unknown Plan' }}</span>
                                        </div>
                                    </div>
                                </td>
                                <td><span class="pricing-metric-pill">{{ ucwords(str_replace('_',' ',$change->metric)) }}</span></td>
                                <td>
                                    <div class="pricing-change-values">
                                        <div><small>Current</small><strong>{{ $change->current_value ?? '—' }}</strong></div>
                                        <i data-lucide="arrow-right"></i>
                                        <div class="is-new"><small>Detected</small><strong>{{ $change->detected_value ?? '—' }}</strong></div>
                                    </div>
                                </td>
                                <td>
                                    @if($change->source_url)
                                        <a class="pricing-source-link" href="{{ $change->source_url }}" target="_blank" rel="noopener noreferrer">
                                            <i data-lucide="external-link"></i>
                                            Open official source
                                        </a>
                                    @else
                                        <span class="pricing-muted">No source URL</span>
                                    @endif
                                </td>
                                <td><span class="pricing-muted">{{ $change->detected_at?->format('M j, Y H:i') ?? $change->created_at->format('M j, Y H:i') }}</span></td>
                                <td>
                                    @if($change->status === 'pending')
                                        <div class="pricing-review-actions">
                                            <form method="POST" action="{{ route('admin.pricing.changes.approve', $change->id) }}" onsubmit="return confirm('Approve this detected value and update the live pricing plan?')">
                                                @csrf
                                                <input type="hidden" name="review_note" value="Approved from pricing review queue">
                                                <button class="btn btn-primary btn-sm" type="submit"><i data-lucide="check"></i>Approve</button>
                                            </form>
                                            <form method="POST" action="{{ route('admin.pricing.changes.reject', $change->id) }}" onsubmit="return confirm('Reject this detected pricing change?')">
                                                @csrf
                                                <input type="hidden" name="review_note" value="Rejected from pricing review queue">
                                                <button class="btn btn-secondary btn-sm" type="submit"><i data-lucide="x"></i>Reject</button>
                                            </form>
                                        </div>
                                    @else
                                        <div class="pricing-reviewed">
                                            <span class="{{ $change->status === 'approved' ? 'is-approved' : 'is-rejected' }}">
                                                <i data-lucide="{{ $change->status === 'approved' ? 'badge-check' : 'circle-x' }}"></i>
                                                {{ ucfirst($change->status) }}
                                            </span>
                                            @if($change->reviewer)
                                                <small>by {{ $change->reviewer->name }}</small>
                                            @endif
                                            @if($change->reviewed_at)
                                                <small>{{ $change->reviewed_at->format('M j, Y H:i') }}</small>
                                            @endif
                                        </div>
                                    @endif
                                </td>
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
                <span><i data-lucide="shield-check"></i></span>
                <h3>No {{ $status === 'all' ? '' : $status }} detected changes</h3>
                <p>{{ $status === 'pending' ? 'The review queue is clear. Run a source scan to check for new official pricing differences.' : 'There are no records in this review state.' }}</p>
            </div>
        @endif
    </section>
</div>
@endsection
