@extends('layouts.admin')
@section('title', isset($plan) ? 'Edit Pricing Plan' : 'Add Pricing Plan')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/pages/pricing.css') }}">
@endpush

@section('content')
@php
    $plan ??= null;
    $value = fn($key, $default = null) => old($key, $plan?->{$key} ?? $default);
@endphp

<div class="pricing-page pricing-editor">
    <form action="{{ $plan ? route('admin.pricing.update', $plan->id) : route('admin.pricing.store') }}" method="POST">
        @csrf
        @if($plan)
            @method('PUT')
        @endif

        <x-page-header
            :title="$plan ? 'Edit Pricing Plan' : 'Add Pricing Plan'"
            subtitle="Maintain the admin-approved values that remain live until a monitored change is reviewed."
            :breadcrumb="['Pricing', 'Pricing Plans', $plan ? 'Edit' : 'Add']"
        >
            <x-slot:actions>
                <a href="{{ route('admin.pricing.index') }}" class="btn btn-secondary">
                    <i data-lucide="arrow-left"></i>
                    Cancel
                </a>
                @if($plan)
                    <a href="{{ route('admin.pricing.sources', $plan->id) }}" class="btn btn-secondary">
                        <i data-lucide="radar"></i>
                        Sources
                    </a>
                @endif
                <button type="submit" class="btn btn-primary">
                    <i data-lucide="save"></i>
                    Save Plan
                </button>
            </x-slot:actions>
        </x-page-header>

        @if($errors->any())
            <div class="alert alert-danger pricing-errors">
                <i data-lucide="circle-alert"></i>
                <div>
                    <strong>Please review the pricing fields.</strong>
                    <ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
                </div>
            </div>
        @endif

        <div class="pricing-editor__layout">
            <main class="pricing-editor__main">
                <section class="card pricing-panel">
                    <div class="pricing-section-head">
                        <div>
                            <span class="pricing-eyebrow">Plan identity</span>
                            <h2>Product & tier</h2>
                            <p>Link this pricing tier to an existing AI Tool record.</p>
                        </div>
                        <span class="pricing-panel__icon"><i data-lucide="badge-dollar-sign"></i></span>
                    </div>

                    <div class="pricing-form-grid">
                        <label class="pricing-field">
                            <span>AI Tool <b>*</b></span>
                            <select class="select" name="tool_id" required>
                                <option value="">Select tool...</option>
                                @foreach($tools as $tool)
                                    <option value="{{ $tool->id }}" @selected((string)$value('tool_id') === (string)$tool->id)>{{ $tool->name }}</option>
                                @endforeach
                            </select>
                        </label>

                        <label class="pricing-field">
                            <span>Plan name <b>*</b></span>
                            <input class="input" name="plan_name" value="{{ $value('plan_name') }}" placeholder="Plus, Pro, Team..." required>
                        </label>
                        <label class="pricing-field">
                            <span>Currency <b>*</b></span>
                            <input class="input" name="currency" value="{{ $value('currency','USD') }}" maxlength="10" placeholder="USD" required>
                        </label>
                        <label class="pricing-field">
                            <span>Billing type <b>*</b></span>
                            <select class="select" name="billing_type" required>
                                @foreach(['subscription'=>'Subscription','per_seat'=>'Per seat','usage'=>'Usage based','one_time'=>'One-time','custom'=>'Custom / Enterprise'] as $key=>$label)
                                    <option value="{{ $key }}" @selected($value('billing_type','subscription')===$key)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </label>
                        <label class="pricing-field">
                            <span>Billing unit</span>
                            <input class="input" name="billing_unit" value="{{ $value('billing_unit') }}" placeholder="per user / month">
                        </label>
                        <label class="pricing-field">
                            <span>Last verified</span>
                            <input class="input" type="datetime-local" name="last_verified_at" value="{{ old('last_verified_at', $plan?->last_verified_at?->format('Y-m-d\TH:i')) }}">
                        </label>
                    </div>
                </section>

                <section class="card pricing-panel">
                    <div class="pricing-section-head">
                        <div>
                            <span class="pricing-eyebrow">Approved values</span>
                            <h2>Commercial pricing</h2>
                            <p>These values are considered live and administrator-approved.</p>
                        </div>
                        <span class="pricing-panel__icon"><i data-lucide="wallet-cards"></i></span>
                    </div>

                    <div class="pricing-form-grid">
                        <label class="pricing-field">
                            <span>Monthly price</span>
                            <div class="pricing-input-prefix">
                                <span>{{ $value('currency','USD') }}</span>
                                <input class="input" type="number" min="0" step="0.01" name="monthly_price" value="{{ $value('monthly_price') }}" placeholder="20.00">
                            </div>
                        </label>

                        <label class="pricing-field">
                            <span>Yearly price</span>
                            <div class="pricing-input-prefix">
                                <span>{{ $value('currency','USD') }}</span>
                                <input class="input" type="number" min="0" step="0.01" name="yearly_price" value="{{ $value('yearly_price') }}" placeholder="204.00">
                            </div>
                        </label>

                        <label class="pricing-field pricing-field--full">
                            <span>API pricing label</span>
                            <input class="input" name="api_price_label" value="{{ $value('api_price_label') }}" placeholder="$3 / 1M input tokens">
                            <small>Free-form API pricing text for token, request or usage-based pricing.</small>
                        </label>
                    </div>
                </section>

                <section class="card pricing-panel">
                    <div class="pricing-section-head">
                        <div>
                            <span class="pricing-eyebrow">Entitlements</span>
                            <h2>Credits & limits</h2>
                            <p>Optional commercial notes attached to this plan.</p>
                        </div>
                        <span class="pricing-panel__icon"><i data-lucide="gauge"></i></span>
                    </div>

                    <div class="pricing-form-grid">
                        <label class="pricing-field">
                            <span>Credits</span>
                            <input class="input" name="credits" value="{{ $value('credits') }}" placeholder="30 hr/mo GPU">
                        </label>

                        <label class="pricing-field">
                            <span>Limits</span>
                            <input class="input" name="limits" value="{{ $value('limits') }}" placeholder="40 messages / 3 hr">
                        </label>
                    </div>
                </section>
            </main>

            <aside class="pricing-editor__aside">
                <section class="card pricing-workflow">
                    <span class="pricing-eyebrow">Approval workflow</span>
                    <div class="pricing-workflow__icon"><i data-lucide="shield-check"></i></div>
                    <h3>Admin-controlled publishing</h3>
                    <p>Automatic monitoring never changes live pricing by itself.</p>
                    <ol>
                        <li><span>1</span><div><strong>Save approved plan</strong><small>Create the current trusted pricing record.</small></div></li>
                        <li><span>2</span><div><strong>Add official source</strong><small>Monitor a vendor page or JSON endpoint.</small></div></li>
                        <li><span>3</span><div><strong>Review detected changes</strong><small>Approve or reject before publishing.</small></div></li>
                    </ol>
                    @if($plan)
                        <a href="{{ route('admin.pricing.sources', $plan->id) }}" class="btn btn-secondary pricing-workflow__button">
                            <i data-lucide="radio-tower"></i>
                            Configure Sources
                        </a>
                    @endif
                </section>
            </aside>
        </div>
    </form>
</div>
@endsection
