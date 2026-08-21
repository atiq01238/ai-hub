@extends('layouts.admin')

@section('title', 'Model API Pricing')

@section('content')
<x-page-header
    title="Model API Pricing"
    subtitle="Verify structured token pricing against official provider sources."
    :breadcrumb="['Pricing', 'Model API Pricing']"
>
    <x-slot:actions>
        <a class="btn btn-secondary" href="{{ route('admin.pricing.index') }}">Tool Pricing</a>
    </x-slot:actions>
</x-page-header>

<form class="card" style="padding:14px;margin-bottom:16px" method="GET">
    <input class="input" name="q" value="{{ request('q') }}" placeholder="Search model...">
</form>

<div class="card" style="overflow:auto">
    <table class="table">
        <thead>
            <tr>
                <th>Model</th>
                <th>Provider</th>
                <th>Input / 1M</th>
                <th>Output / 1M</th>
                <th>Sources</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @foreach($models as $model)
                <tr>
                    <td><strong>{{ $model->name }}</strong></td>
                    <td>{{ $model->company?->name ?? '—' }}</td>
                    <td>{{ $model->input_price_per_million !== null ? '$' . number_format((float) $model->input_price_per_million, 6) : '—' }}</td>
                    <td>{{ $model->output_price_per_million !== null ? '$' . number_format((float) $model->output_price_per_million, 6) : '—' }}</td>
                    <td>{{ $model->pricingSources->count() }}</td>
                    <td>
                        <a class="btn btn-secondary btn-sm" href="{{ route('admin.pricing.models.sources', $model) }}">
                            Verify pricing
                        </a>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>

<div style="margin-top:16px">
    {{ $models->links() }}
</div>
@endsection
