@extends('layouts.admin')
@section('title', 'Automatic Pricing Sources')
@section('content')
<x-page-header title="Automatic Pricing Sources" subtitle="Monitor official pages or JSON endpoints for {{ $plan->tool->name ?? 'Tool' }} — {{ $plan->plan_name }}" :breadcrumb="['Pricing', 'Pricing Plans', 'Sources']">
    <x-slot:actions><a href="{{ route('admin.pricing.index') }}" class="btn btn-secondary btn-sm"><i data-lucide="arrow-left"></i> Pricing Plans</a></x-slot:actions>
</x-page-header>

@if(session('status'))<div class="alert alert-success" style="margin-bottom:16px;">{{ session('status') }}</div>@endif
@if(session('error'))<div class="alert alert-danger" style="margin-bottom:16px;">{{ session('error') }}</div>@endif
@if($errors->any())<div class="alert alert-danger" style="margin-bottom:16px;"><ul style="margin:0;padding-left:18px;">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif

<div class="card card-pad" style="margin-bottom:20px;">
    <div class="section-title">Approved Pricing</div>
    <div style="display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:14px;margin-top:12px;">
        <div><div class="text-sub">Monthly</div><div class="mono" style="font-size:18px;">{{ $plan->monthly_price !== null ? '$'.number_format((float)$plan->monthly_price,2) : '—' }}</div></div>
        <div><div class="text-sub">Yearly</div><div class="mono" style="font-size:18px;">{{ $plan->yearly_price !== null ? '$'.number_format((float)$plan->yearly_price,2) : '—' }}</div></div>
        <div><div class="text-sub">API</div><div class="mono" style="font-size:18px;">{{ $plan->api_price_label ?? '—' }}</div></div>
    </div>
</div>

<div class="card card-pad" style="margin-bottom:20px;">
    <div class="section-title">Add Official Source</div>
    <p class="text-sub" style="margin-top:4px;">Use an official vendor pricing page or official JSON/API endpoint. Auto works for simple server-rendered price pages; Regex or JSON Path gives precise extraction.</p>
    <form method="POST" action="{{ route('admin.pricing.sources.store', $plan->id) }}" style="margin-top:16px;">@csrf
        <div class="form-grid">
            <div class="form-field"><label>Metric</label><select class="select" name="metric" required><option value="monthly_price">Monthly Price</option><option value="yearly_price">Yearly Price</option><option value="api_price_label">API Price Label</option></select></div>
            <div class="form-field"><label>Source Name</label><input class="input" name="source_name" value="{{ old('source_name') }}" placeholder="Official pricing page"></div>
            <div class="form-field col-span-2"><label>Official Source URL</label><input class="input" type="url" name="source_url" value="{{ old('source_url') }}" placeholder="https://vendor.com/pricing" required></div>
            <div class="form-field"><label>Extraction Type</label><select class="select" name="source_type" id="sourceType"><option value="auto">Auto</option><option value="regex">Regex</option><option value="json_path">JSON Path</option></select></div>
            <div class="form-field"><label>Currency</label><input class="input" name="currency" value="{{ old('currency','USD') }}" placeholder="USD"></div>
            <div class="form-field col-span-2"><label>Extraction Rule</label><textarea class="input" name="extraction_rule" rows="3" placeholder="Regex example: /Plus.{0,200}?\$(?<price>\d+(?:\.\d+)?)/is  OR JSON path: data.plans.plus.monthly">{{ old('extraction_rule') }}</textarea><span class="text-sub" style="font-size:11px;">Leave blank for Auto. Regex should preferably use a named capture group called <code>price</code>. JSON Path uses Laravel dot notation.</span></div>
            <div class="form-field"><label>Unit / Note</label><input class="input" name="unit" value="{{ old('unit') }}" placeholder="per month / per 1M tokens"></div>
            <div class="form-field" style="display:flex;align-items:flex-end;"><label style="display:flex;gap:8px;align-items:center;"><input type="checkbox" name="enabled" value="1" checked> Enable scheduled monitoring</label></div>
        </div>
        <div style="margin-top:14px;"><button class="btn btn-primary btn-sm"><i data-lucide="plus"></i> Add Source</button></div>
    </form>
</div>

<div class="card">
    <div class="card-head"><h3>Configured Sources</h3></div>
    <div class="table-wrap"><table class="data-table">
        <thead><tr><th>Metric</th><th>Source</th><th>Type</th><th>Last Detected</th><th>Last Check</th><th>Status</th><th></th></tr></thead>
        <tbody>
        @forelse($plan->sources as $source)
            <tr>
                <td><span class="badge badge-info">{{ ucwords(str_replace('_',' ',$source->metric)) }}</span></td>
                <td><b>{{ $source->source_name ?: 'Official source' }}</b><div><a href="{{ $source->source_url }}" target="_blank" rel="noopener noreferrer" class="text-sub" style="font-size:11px;">{{ \Illuminate\Support\Str::limit($source->source_url,55) }}</a></div></td>
                <td class="text-sub">{{ strtoupper(str_replace('_',' ',$source->source_type)) }}</td>
                <td class="mono">{{ $source->last_detected_value ?? '—' }}</td>
                <td class="cell-sub">{{ $source->last_checked_at?->format('M j, H:i') ?? 'Never' }}</td>
                <td>
                    @if($source->last_check_status === 'ok')<span class="badge badge-pos">Healthy</span>
                    @elseif($source->last_check_status === 'failed')<span class="badge badge-neg" title="{{ $source->last_check_message }}">Failed</span>
                    @else<span class="badge badge-neutral">Not checked</span>@endif
                </td>
                <td><div class="flex gap-8">
                    <form method="POST" action="{{ route('admin.pricing.sources.check', [$plan->id,$source->id]) }}">@csrf<button class="btn btn-secondary btn-sm" type="submit"><i data-lucide="refresh-cw"></i> Check</button></form>
                    <form method="POST" action="{{ route('admin.pricing.sources.destroy', [$plan->id,$source->id]) }}" onsubmit="return confirm('Remove this source?')">@csrf @method('DELETE')<button class="icon-btn"><i data-lucide="trash-2"></i></button></form>
                </div></td>
            </tr>
            @if($source->last_check_status === 'failed' && $source->last_check_message)<tr><td colspan="7" class="text-sub" style="font-size:11px;padding-top:0;">Error: {{ $source->last_check_message }}</td></tr>@endif
        @empty
            <tr><td colspan="7" class="text-sub" style="text-align:center;padding:30px;">No automatic source configured. This plan is currently manual-only.</td></tr>
        @endforelse
        </tbody>
    </table></div>
</div>
@endsection
