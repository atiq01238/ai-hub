@extends('layouts.admin')

@section('title', 'Preview Tool Import')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/pages/data-import.css') }}">
@endpush

@section('content')
<div class="import-page">
    <x-page-header
        title="Preview AI Tool Import"
        subtitle="Review company/category mapping, canonical platforms, source evidence and duplicates before saving."
        :breadcrumb="['AI Management', 'Data Import', 'Tools Preview']"
    >
        <x-slot:actions>
            <a href="{{ route('admin.data-import.index') }}" class="btn btn-secondary">
                <i data-lucide="arrow-left"></i> Back
            </a>
        </x-slot:actions>
    </x-page-header>

    @include('data-import.partials.stats', ['stats' => $stats])

    <form method="POST" action="{{ route('admin.data-import.tools.commit') }}" class="import-commit card">
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">

        <div>
            <span class="import-eyebrow">Duplicate handling</span>
            <h3>Existing tools</h3>
            <p>Skip existing is safest. Update existing is now blank-safe: only non-empty imported fields and explicitly supplied taxonomy columns are changed.</p>
        </div>

        <div class="import-choice-wrap">
            <label class="import-choice">
                <input type="radio" name="existing_action" value="skip" checked>
                <span><b>Skip existing</b><small>Recommended</small></span>
            </label>
            <label class="import-choice">
                <input type="radio" name="existing_action" value="update">
                <span><b>Update existing</b><small>Use imported fields</small></span>
            </label>
        </div>

        <button class="btn btn-primary" type="submit" onclick="return confirm('Import validated AI tool rows now?')">
            <i data-lucide="database-zap"></i> Import AI Tools
        </button>
    </form>

    <section class="card import-preview-card">
        <div class="import-preview-head">
            <div>
                <span class="import-eyebrow">Validation results</span>
                <h3>{{ number_format($stats['total']) }} tool rows</h3>
            </div>
        </div>

        <div class="table-wrap">
            <table class="data-table import-table">
                <thead>
                    <tr>
                        <th>Row</th>
                        <th>Company</th>
                        <th>Tool</th>
                        <th>Category</th>
                        <th>Capabilities</th>
                        <th>Use Cases</th>
                        <th>Tags</th>
                        <th>Platforms</th>
                        <th>Source</th>
                        <th>Status</th>
                        <th>Result</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($preview as $row)
                        <tr>
                            <td>#{{ $row['row_number'] }}</td>
                            <td>{{ $row['company'] ?: 'Independent' }}</td>
                            <td>
                                <strong>{{ $row['name'] }}</strong>
                                <small>{{ $row['website'] }}</small>
                            </td>
                            <td>
                                <strong>{{ $row['category_match'] ?: ($row['category'] ?: '—') }}</strong>
                                @if(!empty($row['subcategory_match']))
                                    <small>{{ $row['subcategory_match'] }}</small>
                                @elseif(!empty($row['subcategory']))
                                    <small>{{ $row['subcategory'] }}</small>
                                @endif
                            </td>
                            <td>{{ !empty($row['provided']['capabilities']) ? count($row['capabilities']) . ' mapped' : 'Preserve' }}</td>
                            <td>{{ !empty($row['provided']['use_cases']) ? count($row['use_cases']) . ' mapped' : 'Preserve' }}</td>
                            <td>{{ !empty($row['provided']['tags']) ? count($row['tags']) . ' mapped' : 'Preserve' }}</td>
                            <td>{{ !empty($row['provided']['platforms']) ? implode(', ', $row['platforms']) : 'Preserve' }}</td>
                            <td>@if(!empty($row['source_url']))<small>{{ Str::limit($row['source_url'], 42) }} · {{ ucfirst($row['source_verification_status'] ?: 'pending') }}</small>@else—@endif</td>
                            <td>{{ $row['status'] !== '' ? ucfirst($row['status']) : 'Preserve' }}</td>
                            <td>@include('data-import.partials.state', ['row' => $row])</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>
</div>
@endsection
