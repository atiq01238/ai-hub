@extends('layouts.admin')

@section('title', 'Preview Model Import')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/pages/data-import.css') }}">
@endpush

@section('content')
<div class="import-page">
    <x-page-header
        title="Preview AI Model Import"
        subtitle="Review provider matching, Taxonomy v2 capabilities, pricing and duplicate handling before saving."
        :breadcrumb="['AI Management', 'Data Import', 'Models Preview']"
    >
        <x-slot:actions>
            <a href="{{ route('admin.data-import.index') }}" class="btn btn-secondary"><i data-lucide="arrow-left"></i> Back</a>
        </x-slot:actions>
    </x-page-header>

    @include('data-import.partials.stats', ['stats' => $stats])

    <form method="POST" action="{{ route('admin.data-import.models.commit') }}" class="import-commit card">
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">
        <div>
            <span class="import-eyebrow">Duplicate handling</span>
            <h3>Existing models</h3>
            <p>Skip existing is safest. Update synchronizes normalized capabilities and inferred use cases.</p>
        </div>
        <div class="import-choice-wrap">
            <label class="import-choice"><input type="radio" name="existing_action" value="skip" checked><span><b>Skip existing</b><small>Recommended</small></span></label>
            <label class="import-choice"><input type="radio" name="existing_action" value="update"><span><b>Update existing</b><small>Use imported fields</small></span></label>
        </div>
        <button class="btn btn-primary" type="submit" onclick="return confirm('Import validated AI model rows now?')"><i data-lucide="database-zap"></i> Import AI Models</button>
    </form>

    <section class="card import-preview-card">
        <div class="import-preview-head"><div><span class="import-eyebrow">Validation results</span><h3>{{ number_format($stats['total']) }} model rows</h3></div></div>
        <div class="table-wrap">
            <table class="data-table import-table">
                <thead><tr><th>Row</th><th>Provider</th><th>Model</th><th>Capabilities</th><th>Pricing / 1M</th><th>Benchmark</th><th>Result</th></tr></thead>
                <tbody>
                @foreach($preview as $row)
                    <tr>
                        <td>#{{ $row['row_number'] }}</td>
                        <td><strong>{{ $row['company_match'] ?: ($row['company'] ?: '—') }}</strong></td>
                        <td><strong>{{ $row['name'] }}</strong><small>{{ $row['version'] ?: 'No version' }} · {{ ucfirst($row['status']) }}</small></td>
                        <td>{{ implode(', ', array_slice($row['capabilities'] ?? [], 0, 5)) ?: '—' }}</td>
                        <td><small>In: {{ $row['input_price_per_million'] !== null ? '$'.number_format((float)$row['input_price_per_million'], 2) : '—' }}</small><small>Out: {{ $row['output_price_per_million'] !== null ? '$'.number_format((float)$row['output_price_per_million'], 2) : '—' }}</small></td>
                        <td>{{ $row['benchmark_score'] !== null ? number_format((float)$row['benchmark_score'], 1) : '—' }}</td>
                        <td>@include('data-import.partials.state', ['row' => $row])</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </section>
</div>
@endsection
