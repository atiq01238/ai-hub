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
            <form method="POST" action="{{ route('admin.pricing.scan') }}" id="pricingDirectScanForm">
                @csrf
                <button class="btn btn-primary" type="submit" id="pricingDirectScanButton">
                    <i data-lucide="radar"></i>
                    <span>Scan All Sources Now</span>
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
                                    @php
                                        $numericMetric = in_array($change->metric, ['monthly_price', 'yearly_price'], true);
                                        $currency = strtoupper($change->currency ?: 'USD');
                                        $currentDisplay = $numericMetric && $change->current_value !== null
                                            ? $currency.' '.rtrim(rtrim(number_format((float) $change->current_value, 2), '0'), '.')
                                            : ($change->current_value ?? '—');
                                        $detectedDisplay = $numericMetric && $change->detected_value !== null && is_numeric($change->detected_value)
                                            ? $currency.' '.rtrim(rtrim(number_format((float) $change->detected_value, 2), '0'), '.')
                                            : ($change->detected_value ?? '—');
                                        $paidToZero = $numericMetric
                                            && $change->current_value !== null
                                            && (float) $change->current_value > 0
                                            && is_numeric($change->detected_value)
                                            && (float) $change->detected_value === 0.0
                                            && !str_contains(mb_strtolower((string) ($change->plan->plan_name ?? '')), 'free');
                                    @endphp
                                    <div class="pricing-change-values">
                                        <div><small>Current</small><strong>{{ $currentDisplay }}</strong></div>
                                        <i data-lucide="arrow-right"></i>
                                        <div class="is-new"><small>Detected</small><strong>{{ $detectedDisplay }}</strong></div>
                                    </div>
                                    @if($change->source?->source_type === 'auto')
                                        <small class="pricing-muted">Automatic extraction · verify against the official plan block before approval.</small>
                                    @endif
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
                                                @if($paidToZero)
                                                    <label style="display:flex;align-items:flex-start;gap:6px;margin:0 0 8px;color:#f6c76a;font-size:11px;line-height:1.35;max-width:220px">
                                                        <input type="checkbox" name="confirm_high_risk" value="1" required style="margin-top:2px">
                                                        <span>I verified on the official source that this paid plan is now free.</span>
                                                    </label>
                                                @endif
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

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('pricingDirectScanForm');
    const button = document.getElementById('pricingDirectScanButton');
    const label = button?.querySelector('span');

    if (!form || !button || !label) return;

    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        if (button.disabled) return;

        const originalLabel = label.textContent;
        const csrf = form.querySelector('input[name="_token"]')?.value || '';
        let afterId = 0;
        let processed = 0;
        let total = 0;
        const totals = { checked: 0, changes: 0, unchanged: 0, failed: 0 };

        button.disabled = true;
        button.setAttribute('aria-busy', 'true');
        label.textContent = 'Starting direct scan…';

        try {
            while (true) {
                const body = new URLSearchParams();
                body.set('_token', csrf);
                body.set('after_id', String(afterId));

                const response = await fetch(form.action, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8',
                    },
                    body: body.toString(),
                    credentials: 'same-origin',
                });

                let payload = {};
                try {
                    payload = await response.json();
                } catch (e) {
                    throw new Error('The server returned an invalid response during the pricing scan.');
                }

                if (!response.ok || !payload.ok) {
                    throw new Error(payload.message || 'Pricing source scan failed.');
                }

                total = Number(payload.total_sources || total || 0);
                processed += Number(payload.batch_count || 0);
                afterId = Number(payload.next_after_id || afterId || 0);

                Object.keys(totals).forEach((key) => {
                    totals[key] += Number(payload.stats?.[key] || 0);
                });

                label.textContent = total
                    ? `Scanning ${Math.min(processed, total)}/${total}…`
                    : `Scanning ${processed}…`;

                if (!payload.has_more) break;
            }

            const summary = `Direct scan complete: ${totals.checked} checked, ${totals.changes} change(s), ${totals.unchanged} unchanged, ${totals.failed} failed.`;
            sessionStorage.setItem('pricing-direct-scan-result', summary);
            window.location.reload();
        } catch (error) {
            button.disabled = false;
            button.removeAttribute('aria-busy');
            label.textContent = originalLabel;
            window.alert(error?.message || 'Pricing source scan failed. Please try again.');
        }
    });

    const completed = sessionStorage.getItem('pricing-direct-scan-result');
    if (completed) {
        sessionStorage.removeItem('pricing-direct-scan-result');
        const page = document.querySelector('.pricing-page');
        const header = page?.querySelector('.page-header') || page?.firstElementChild;
        if (page) {
            const alert = document.createElement('div');
            alert.className = 'alert alert-success pricing-flash';
            alert.innerHTML = '<i data-lucide="check-circle-2"></i><span></span>';
            alert.querySelector('span').textContent = completed;
            if (header?.nextSibling) {
                page.insertBefore(alert, header.nextSibling);
            } else {
                page.prepend(alert);
            }
            if (window.lucide) window.lucide.createIcons();
        }
    }
});
</script>
@endpush
@endsection
