@extends('layouts.admin')
@section('title', 'Automatic Pricing Sources')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/pages/pricing.css') }}">
@endpush

@section('content')
<div class="pricing-page pricing-sources">
    <x-page-header
        title="Automatic Pricing Sources"
        :subtitle="'Official monitoring for '.($plan->tool->name ?? 'Tool').' — '.$plan->plan_name"
        :breadcrumb="['Pricing', 'Pricing Plans', 'Automatic Sources']"
    >
        <x-slot:actions>
            <a href="{{ route('admin.pricing.index') }}" class="btn btn-secondary">
                <i data-lucide="arrow-left"></i>
                Pricing Plans
            </a>
            <a href="{{ route('admin.pricing.changes') }}" class="btn btn-secondary">
                <i data-lucide="scan-search"></i>
                Review Changes
            </a>
        </x-slot:actions>
    </x-page-header>

    @if(session('status'))
        <div class="alert alert-success pricing-flash"><i data-lucide="check-circle-2"></i><span>{{ session('status') }}</span></div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger pricing-flash"><i data-lucide="circle-alert"></i><span>{{ session('error') }}</span></div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger pricing-errors">
            <i data-lucide="circle-alert"></i>
            <div><strong>Source could not be saved.</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
        </div>
    @endif

    <section class="pricing-live-grid">
        <article class="card pricing-live">
            <span class="pricing-eyebrow">Monthly</span>
            <strong>{{ $plan->monthly_price !== null ? '$'.number_format((float)$plan->monthly_price,2) : '—' }}</strong>
            <small>Approved live value</small>
        </article>
        <article class="card pricing-live">
            <span class="pricing-eyebrow">Yearly</span>
            <strong>{{ $plan->yearly_price !== null ? '$'.number_format((float)$plan->yearly_price,2) : '—' }}</strong>
            <small>Approved live value</small>
        </article>
        <article class="card pricing-live pricing-live--wide">
            <span class="pricing-eyebrow">API</span>
            <strong>{{ $plan->api_price_label ?: '—' }}</strong>
            <small>Approved API pricing label</small>
        </article>
        <article class="card pricing-live">
            <span class="pricing-eyebrow">Monitoring</span>
            <strong>{{ $plan->sources->where('enabled', true)->count() }}</strong>
            <small>Enabled official sources</small>
        </article>
    </section>

    <div class="pricing-sources__layout">
        <main class="pricing-sources__main">
            <section class="card pricing-panel">
                <div class="pricing-section-head">
                    <div>
                        <span class="pricing-eyebrow">Monitoring configuration</span>
                        <h2>Add official source</h2>
                        <p>Use an official vendor pricing page or JSON/API endpoint.</p>
                    </div>
                    <span class="pricing-panel__icon"><i data-lucide="radar"></i></span>
                </div>

                <form method="POST" action="{{ route('admin.pricing.sources.store', $plan->id) }}" class="pricing-form-grid">
                    @csrf

                    <label class="pricing-field">
                        <span>Metric <b>*</b></span>
                        <select class="select" name="metric" required>
                            <option value="monthly_price" @selected(old('metric') === 'monthly_price')>Monthly Price</option>
                            <option value="yearly_price" @selected(old('metric') === 'yearly_price')>Yearly Price</option>
                            <option value="api_price_label" @selected(old('metric') === 'api_price_label')>API Price Label</option>
                        </select>
                    </label>

                    <label class="pricing-field">
                        <span>Source name</span>
                        <input class="input" name="source_name" value="{{ old('source_name') }}" placeholder="Official pricing page">
                    </label>

                    <label class="pricing-field pricing-field--full">
                        <span>Official source URL <b>*</b></span>
                        <div class="pricing-input-icon">
                            <i data-lucide="link-2"></i>
                            <input class="input" type="url" name="source_url" value="{{ old('source_url') }}" placeholder="https://vendor.com/pricing" required>
                        </div>
                    </label>

                    <label class="pricing-field">
                        <span>Extraction type <b>*</b></span>
                        <select class="select" name="source_type" id="sourceType" required>
                            <option value="auto" @selected(old('source_type','auto') === 'auto')>Auto</option>
                            <option value="regex" @selected(old('source_type') === 'regex')>Regex</option>
                            <option value="json_path" @selected(old('source_type') === 'json_path')>JSON Path</option>
                        </select>
                    </label>

                    <label class="pricing-field">
                        <span>Currency</span>
                        <input class="input" name="currency" value="{{ old('currency','USD') }}" placeholder="USD">
                    </label>

                    <label class="pricing-field pricing-field--full">
                        <span>Extraction rule</span>
                        <textarea class="textarea" name="extraction_rule" rows="5" placeholder="Regex: /Plus.{0,200}?\$(?<price>\d+(?:\.\d+)?)/is&#10;JSON Path: data.plans.plus.monthly">{{ old('extraction_rule') }}</textarea>
                        <small id="extractionHelp">Leave blank when using Auto. Regex should preferably expose a named <code>price</code> capture; JSON Path uses Laravel dot notation.</small>
                    </label>

                    <label class="pricing-field">
                        <span>Unit / note</span>
                        <input class="input" name="unit" value="{{ old('unit') }}" placeholder="per month / per 1M tokens">
                    </label>

                    <label class="pricing-toggle">
                        <input type="checkbox" name="enabled" value="1" @checked(old('enabled', true))>
                        <span>
                            <strong>Enable scheduled monitoring</strong>
                            <small>Include this source in automatic scans.</small>
                        </span>
                    </label>

                    <div class="pricing-form-actions pricing-field--full">
                        <button class="btn btn-primary" type="submit">
                            <i data-lucide="plus"></i>
                            Add Monitoring Source
                        </button>
                    </div>
                </form>
            </section>

            <section class="card pricing-table-card">
                <div class="pricing-section-head">
                    <div>
                        <span class="pricing-eyebrow">Monitoring network</span>
                        <h2>Configured sources</h2>
                        <p>Health, last detected values and manual source checks.</p>
                    </div>
                    <span class="pricing-count-pill">{{ $plan->sources->count() }} sources</span>
                </div>

                @if($plan->sources->count())
                    <div class="table-wrap">
                        <table class="data-table pricing-source-table">
                            <thead>
                                <tr>
                                    <th>Source</th>
                                    <th>Metric</th>
                                    <th>Extraction</th>
                                    <th>Last Value</th>
                                    <th>Last Check</th>
                                    <th>Health</th>
                                    <th class="pricing-table__actions">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($plan->sources as $source)
                                    <tr>
                                        <td>
                                            <div class="pricing-source">
                                                <span class="pricing-source__icon"><i data-lucide="globe-2"></i></span>
                                                <div>
                                                    <strong>{{ $source->source_name ?: 'Official source' }}</strong>
                                                    <a href="{{ $source->source_url }}" target="_blank" rel="noopener noreferrer">
                                                        {{ \Illuminate\Support\Str::limit($source->source_url, 48) }}
                                                        <i data-lucide="external-link"></i>
                                                    </a>
                                                </div>
                                            </div>
                                        </td>
                                        <td><span class="pricing-metric-pill">{{ ucwords(str_replace('_',' ',$source->metric)) }}</span></td>
                                        <td>
                                            <span class="pricing-extraction">{{ strtoupper(str_replace('_',' ',$source->source_type)) }}</span>
                                            @if(!$source->enabled)<small class="pricing-disabled">Disabled</small>@endif
                                        </td>
                                        <td><span class="pricing-money">{{ $source->last_detected_value ?? '—' }}</span></td>
                                        <td><span class="pricing-muted">{{ $source->last_checked_at?->format('M j, Y H:i') ?? 'Never' }}</span></td>
                                        <td>
                                            @if($source->last_check_status === 'ok')
                                                <span class="pricing-health is-healthy"><i data-lucide="circle-check"></i>Healthy</span>
                                            @elseif($source->last_check_status === 'failed')
                                                <span class="pricing-health is-failed" title="{{ $source->last_check_message }}"><i data-lucide="circle-x"></i>Failed</span>
                                            @else
                                                <span class="pricing-health"><i data-lucide="circle-dashed"></i>Not checked</span>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="pricing-actions">
                                                <form method="POST" action="{{ route('admin.pricing.sources.check', [$plan->id, $source->id]) }}">
                                                    @csrf
                                                    <button class="btn btn-secondary btn-sm" type="submit"><i data-lucide="refresh-cw"></i>Check</button>
                                                </form>
                                                <form method="POST" action="{{ route('admin.pricing.sources.destroy', [$plan->id, $source->id]) }}" onsubmit="return confirm('Remove this monitoring source?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button class="icon-btn icon-btn--danger" type="submit" title="Remove source"><i data-lucide="trash-2"></i></button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                    @if($source->last_check_status === 'failed' && $source->last_check_message)
                                        <tr class="pricing-source-error">
                                            <td colspan="7"><i data-lucide="triangle-alert"></i><span>{{ $source->last_check_message }}</span></td>
                                        </tr>
                                    @endif
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="pricing-empty">
                        <span><i data-lucide="radio-tower"></i></span>
                        <h3>Manual-only pricing plan</h3>
                        <p>Add an official source above to enable change detection for this plan.</p>
                    </div>
                @endif
            </section>
        </main>

        <aside class="pricing-sources__aside">
            <section class="card pricing-workflow">
                <span class="pricing-eyebrow">Detection policy</span>
                <div class="pricing-workflow__icon"><i data-lucide="shield-check"></i></div>
                <h3>Human approval required</h3>
                <p>A detected difference is sent to the review queue. Live pricing changes only after an administrator approves it.</p>
                <div class="pricing-workflow__facts">
                    <div><span>Auto publish</span><strong>Disabled</strong></div>
                    <div><span>Official sources</span><strong>{{ $plan->sources->count() }}</strong></div>
                    <div><span>Enabled</span><strong>{{ $plan->sources->where('enabled', true)->count() }}</strong></div>
                </div>
            </section>
        </aside>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const type = document.getElementById('sourceType');
    const help = document.getElementById('extractionHelp');
    if (!type || !help) return;

    const copy = {
        auto: 'Auto inspects simple server-rendered pricing pages. Leave the extraction rule blank.',
        regex: 'Regex requires an extraction rule. Prefer a named capture group called price.',
        json_path: 'JSON Path requires an extraction rule using Laravel dot notation, e.g. data.plans.plus.monthly.'
    };

    const sync = () => { help.textContent = copy[type.value] || copy.auto; };
    type.addEventListener('change', sync);
    sync();
});
</script>
@endpush
