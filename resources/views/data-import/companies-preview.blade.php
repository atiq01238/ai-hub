@extends('layouts.admin')

@section('title', 'Preview Company Import')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/pages/data-import.css') }}">
@endpush

@section('content')
<div class="import-page">
    <x-page-header
        title="Preview AI Company Import"
        subtitle="Review normalized company records and duplicate matches before saving."
        :breadcrumb="['AI Management', 'Data Import', 'Companies Preview']"
    >
        <x-slot:actions><a href="{{ route('admin.data-import.index') }}" class="btn btn-secondary"><i data-lucide="arrow-left"></i> Back</a></x-slot:actions>
    </x-page-header>

    @include('data-import.partials.stats', ['stats' => $stats])

    <form method="POST" action="{{ route('admin.data-import.companies.commit') }}" class="import-commit card">
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">
        <div><span class="import-eyebrow">Duplicate handling</span><h3>Existing companies</h3><p>Skip existing is safest. Update overwrites only fields included in this company import.</p></div>
        <div class="import-choice-wrap">
            <label class="import-choice"><input type="radio" name="existing_action" value="skip" checked><span><b>Skip existing</b><small>Recommended</small></span></label>
            <label class="import-choice"><input type="radio" name="existing_action" value="update"><span><b>Update existing</b><small>Use imported fields</small></span></label>
        </div>
        <button class="btn btn-primary" type="submit" onclick="return confirm('Import validated company rows now?')"><i data-lucide="database-zap"></i> Import Companies</button>
    </form>

    <section class="card import-preview-card">
        <div class="import-preview-head"><div><span class="import-eyebrow">Validation results</span><h3>{{ number_format($stats['total']) }} company rows</h3></div></div>
        <div class="table-wrap"><table class="data-table import-table">
            <thead><tr><th>Row</th><th>Company</th><th>Website</th><th>Founded</th><th>Status</th><th>Result</th></tr></thead>
            <tbody>@foreach($preview as $row)<tr>
                <td>#{{ $row['row_number'] }}</td>
                <td><strong>{{ $row['name'] }}</strong>@if($row['existing_name'])<small>Matches {{ $row['existing_name'] }}</small>@endif</td>
                <td>{{ $row['website'] ?: '—' }}</td>
                <td>{{ $row['founded_year'] ?: '—' }}</td>
                <td>{{ ucfirst($row['status']) }}</td>
                <td>@include('data-import.partials.state', ['row' => $row])</td>
            </tr>@endforeach</tbody>
        </table></div>
    </section>
</div>
@endsection
