@extends('layouts.admin')

@section('title', 'Preview Pricing Import')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/pages/data-import.css') }}">
@endpush

@section('content')
<div class="import-page">
    <x-page-header
        title="Preview Pricing Import"
        subtitle="Pricing rows are linked to existing tools and can attach official monitoring sources."
        :breadcrumb="['AI Management', 'Data Import', 'Pricing Preview']"
    >
        <x-slot:actions>
            <a href="{{ route('admin.data-import.index') }}" class="btn btn-secondary">Back</a>
        </x-slot:actions>
    </x-page-header>

    @include('data-import.partials.stats', ['stats' => $stats])

    <form method="POST" action="{{ route('admin.data-import.pricing.commit') }}" class="import-commit card">
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">

        <div>
            <h3>Existing pricing plans</h3>
            <p>Match key is Tool + Plan name.</p>
        </div>

        <div class="import-choice-wrap">
            <label class="import-choice">
                <input type="radio" name="existing_action" value="skip" checked>
                <span><b>Skip existing</b></span>
            </label>
            <label class="import-choice">
                <input type="radio" name="existing_action" value="update">
                <span><b>Update existing</b></span>
            </label>
        </div>

        <button class="btn btn-primary" type="submit">Import Pricing</button>
    </form>

    <section class="card import-preview-card">
        <div class="table-wrap">
            <table class="data-table import-table">
                <thead>
                    <tr>
                        <th>Row</th>
                        <th>Tool</th>
                        <th>Plan</th>
                        <th>Monthly</th>
                        <th>Yearly</th>
                        <th>Source</th>
                        <th>Result</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($preview as $row)
                        <tr>
                            <td>#{{ $row['row_number'] }}</td>
                            <td>{{ $row['tool'] }}</td>
                            <td><strong>{{ $row['plan_name'] }}</strong></td>
                            <td>{{ $row['monthly_price'] === null ? '—' : '$'.number_format($row['monthly_price'], 2) }}</td>
                            <td>{{ $row['yearly_price'] === null ? '—' : '$'.number_format($row['yearly_price'], 2) }}</td>
                            <td>{{ $row['source_name'] ?: '—' }}</td>
                            <td>@include('data-import.partials.state', ['row' => $row])</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>
</div>
@endsection
