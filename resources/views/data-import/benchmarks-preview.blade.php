@extends('layouts.admin')

@section('title', 'Preview Benchmark Import')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/pages/data-import.css') }}">
@endpush

@section('content')
<div class="import-page">
    <x-page-header
        title="Preview Benchmark Import"
        subtitle="Only source-backed scores should be imported. Blank template rows remain invalid until you add an entity and score."
        :breadcrumb="['AI Management', 'Data Import', 'Benchmarks Preview']"
    >
        <x-slot:actions>
            <a href="{{ route('admin.data-import.index') }}" class="btn btn-secondary">Back</a>
        </x-slot:actions>
    </x-page-header>

    @include('data-import.partials.stats', ['stats' => $stats])

    <form method="POST" action="{{ route('admin.data-import.benchmarks.commit') }}" class="import-commit card">
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">

        <div>
            <h3>Import verified benchmark history</h3>
            <p>Rows marked verified should have a trustworthy source URL.</p>
        </div>

        <button class="btn btn-primary" type="submit" onclick="return confirm('Import ready benchmark rows?')">
            Import Benchmarks
        </button>
    </form>

    <section class="card import-preview-card">
        <div class="table-wrap">
            <table class="data-table import-table">
                <thead>
                    <tr>
                        <th>Row</th>
                        <th>Entity</th>
                        <th>Benchmark</th>
                        <th>Score</th>
                        <th>Source</th>
                        <th>Verified</th>
                        <th>Result</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($preview as $row)
                        <tr>
                            <td>#{{ $row['row_number'] }}</td>
                            <td>{{ ucfirst($row['entity_type']) }} · {{ $row['entity_name'] ?: '—' }}</td>
                            <td><strong>{{ $row['benchmark_name'] }}</strong></td>
                            <td>{{ $row['score'] ?? '—' }}</td>
                            <td>{{ $row['source_name'] ?: '—' }}</td>
                            <td>{{ $row['verified'] ? 'Yes' : 'No' }}</td>
                            <td>@include('data-import.partials.state', ['row' => $row])</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>
</div>
@endsection
