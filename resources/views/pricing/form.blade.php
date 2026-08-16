@extends('layouts.admin')
@section('title', isset($plan) ? 'Edit Pricing Plan' : 'Add Pricing Plan')

@section('content')
@php
    $plan ??= null;
    $old = fn ($key, $default = null) => old($key, $plan->{$key} ?? $default);
@endphp

<form action="{{ $plan ? route('admin.pricing.update', $plan->id) : route('admin.pricing.store') }}" method="POST">
    @csrf
    @if ($plan) @method('PUT') @endif

<x-page-header title="{{ $plan ? 'Edit Pricing Plan' : 'Add Pricing Plan' }}" subtitle="Live values stay admin-approved; automatic monitoring is configured separately" :breadcrumb="['Pricing', 'Pricing Plans', $plan ? 'Edit' : 'Add']">
    <x-slot:actions>
        @if($plan)<a href="{{ route('admin.pricing.sources', $plan->id) }}" class="btn btn-secondary btn-sm"><i data-lucide="radar"></i> Automatic Sources</a>@endif
        <button type="submit" class="btn btn-primary btn-sm"><i data-lucide="check"></i> Save</button>
    </x-slot:actions>
</x-page-header>

@if ($errors->any())
    <div class="alert alert-danger" style="margin-bottom:16px;"><ul style="margin:0; padding-left:18px;">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
@endif

<div class="card card-pad form-section" style="max-width:760px;">
    <div class="form-grid">
        <div class="form-field">
            <label>Tool</label>
            <select class="select" name="tool_id" required>
                <option value="">Select tool...</option>
                @foreach ($tools as $tool)<option value="{{ $tool->id }}" @selected($old('tool_id') == $tool->id)>{{ $tool->name }}</option>@endforeach
            </select>
        </div>
        <div class="form-field"><label>Plan Name</label><input class="input" name="plan_name" value="{{ $old('plan_name') }}" placeholder="e.g. Plus, Pro, Team" required></div>
        <div class="form-field"><label>Monthly Price ($)</label><input class="input" type="number" step="0.01" name="monthly_price" value="{{ $old('monthly_price') }}" placeholder="20.00"></div>
        <div class="form-field"><label>Yearly Price ($)</label><input class="input" type="number" step="0.01" name="yearly_price" value="{{ $old('yearly_price') }}" placeholder="204.00"></div>
        <div class="form-field"><label>API Price</label><input class="input" name="api_price_label" value="{{ $old('api_price_label') }}" placeholder="$3 / 1M input tokens"></div>
        <div class="form-field"><label>Credits</label><input class="input" name="credits" value="{{ $old('credits') }}" placeholder="30 hr/mo GPU"></div>
        <div class="form-field col-span-2"><label>Limits</label><input class="input" name="limits" value="{{ $old('limits') }}" placeholder="40 msgs/3hr"></div>
    </div>

    <div style="margin-top:18px; padding:14px; border:1px solid var(--border, #273047); border-radius:10px;">
        <div style="font-weight:600; margin-bottom:5px;">How automatic detection works</div>
        <div class="text-sub" style="font-size:12px; line-height:1.6;">Save this approved pricing plan first, then open <b>Automatic Sources</b> and add its official pricing URL/API. The scanner compares the official value with this plan. A mismatch is sent to Detected Changes for your approval; it is never published automatically.</div>
    </div>
</div>
</form>
@endsection
