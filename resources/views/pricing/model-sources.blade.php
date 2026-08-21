@extends('layouts.admin')

@section('title', $model->name . ' API Pricing')

@section('content')
<x-page-header
    :title="$model->name . ' API Pricing'"
    subtitle="Official-source verification and token-price history."
    :breadcrumb="['Pricing', 'Model API Pricing', $model->name]"
>
    <x-slot:actions>
        <a class="btn btn-secondary" href="{{ route('admin.pricing.models.index') }}">Back</a>
    </x-slot:actions>
</x-page-header>

@if(session('status'))
    <div class="alert alert-success">{{ session('status') }}</div>
@endif

@if(session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
@endif

@if(session()->has('pricing_candidate'))
    @php
        $candidate = session('pricing_candidate');
    @endphp

    <div class="alert alert-warning">
        <strong>Change detected:</strong>
        {{ $candidate['metric'] }}
        {{ $candidate['current'] ?? '—' }} → {{ $candidate['detected'] }}

        <form
            method="POST"
            action="{{ route('admin.pricing.models.sources.approve', [$model, $candidate['source_id']]) }}"
            style="display:inline"
        >
            @csrf
            <input type="hidden" name="detected_value" value="{{ $candidate['detected'] }}">
            <button class="btn btn-primary btn-sm" type="submit">Approve verified price</button>
        </form>
    </div>
@endif

<div class="grid-2" style="display:grid;grid-template-columns:1fr 1fr;gap:16px">
    <section class="card" style="padding:18px">
        <h3>Current structured pricing</h3>
        <p>
            Input:
            <strong>
                {{ $model->input_price_per_million !== null ? '$' . $model->input_price_per_million : '—' }}
            </strong>
            / 1M tokens
        </p>
        <p>
            Output:
            <strong>
                {{ $model->output_price_per_million !== null ? '$' . $model->output_price_per_million : '—' }}
            </strong>
            / 1M tokens
        </p>
    </section>

    <section class="card" style="padding:18px">
        <h3>Add official source</h3>

        <form method="POST" action="{{ route('admin.pricing.models.sources.store', $model) }}">
            @csrf

            <select class="select" name="metric" required>
                <option value="input_price_per_million">Input / 1M tokens</option>
                <option value="output_price_per_million">Output / 1M tokens</option>
            </select>

            <input class="input" name="source_name" placeholder="Official pricing page" style="margin-top:8px">
            <input class="input" type="url" name="source_url" placeholder="https://..." required style="margin-top:8px">

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-top:8px">
                <select class="select" name="source_type">
                    <option value="auto">Automatic</option>
                    <option value="regex">Regex</option>
                    <option value="json_path">JSON Path</option>
                </select>
                <input class="input" name="currency" value="USD">
            </div>

            <input class="input" name="unit" value="per 1M tokens" style="margin-top:8px">
            <textarea
                class="input"
                name="extraction_rule"
                placeholder="Regex / JSON path (optional for auto)"
                style="margin-top:8px"
            ></textarea>

            <button class="btn btn-primary" type="submit" style="margin-top:8px">Add source</button>
        </form>
    </section>
</div>

<section class="card" style="padding:18px;margin-top:16px">
    <h3>Official sources</h3>

    @forelse($model->pricingSources as $source)
        <div style="padding:12px 0;border-bottom:1px solid var(--border)">
            <strong>{{ $source->metric }}</strong>
            · {{ $source->source_name ?: 'Official source' }}
            · {{ strtoupper($source->currency) }}

            <br>

            <small>
                {{ $source->last_checked_at ? 'Checked ' . $source->last_checked_at->diffForHumans() : 'Not checked yet' }}
                · {{ $source->last_check_status ?: 'pending' }}
            </small>

            <div style="margin-top:8px">
                <form
                    style="display:inline"
                    method="POST"
                    action="{{ route('admin.pricing.models.sources.verify', [$model, $source]) }}"
                >
                    @csrf
                    <button class="btn btn-secondary btn-sm" type="submit">Check now</button>
                </form>

                <form
                    style="display:inline"
                    method="POST"
                    action="{{ route('admin.pricing.models.sources.destroy', [$model, $source]) }}"
                >
                    @csrf
                    @method('DELETE')
                    <button class="btn btn-danger btn-sm" type="submit">Remove</button>
                </form>
            </div>
        </div>
    @empty
        <p>No official sources configured.</p>
    @endforelse
</section>

<section class="card" style="padding:18px;margin-top:16px">
    <h3>Verified price history</h3>

    @forelse($model->pricingHistory as $row)
        <p>
            <strong>{{ $row->metric }}</strong>:
            {{ $row->old_value ?? '—' }} → {{ $row->new_value }}
            {{ $row->currency }}
            · {{ $row->verified_at?->format('M j, Y H:i') }}
        </p>
    @empty
        <p>No verified changes yet.</p>
    @endforelse
</section>
@endsection
