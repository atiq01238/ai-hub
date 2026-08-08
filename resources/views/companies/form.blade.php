@extends('layouts.admin')
@section('title', isset($company) ? 'Edit Company' : 'Add Company')

@php
    use Illuminate\Support\Facades\Storage;
@endphp

@section('content')

@php
    $company ??= null;
    $old = fn ($key, $default = null) => old($key, $company->{$key} ?? $default);
@endphp

<form action="{{ $company ? route('admin.companies.update', $company->id) : route('admin.companies.store') }}" method="POST" enctype="multipart/form-data">
    @csrf
    @if ($company) @method('PUT') @endif

<x-page-header title="{{ $company ? 'Edit Company' : 'Add Company' }}" :breadcrumb="['AI Management', 'AI Companies', $company ? 'Edit' : 'Add']">
    <x-slot:actions><button type="submit" class="btn btn-primary btn-sm"><i data-lucide="check"></i> Save Company</button></x-slot:actions>
</x-page-header>

@if ($errors->any())
    <div class="alert alert-danger" style="margin-bottom:16px;">
        <ul style="margin:0; padding-left:18px;">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="card card-pad form-section" style="max-width:760px;">
    <div class="form-section__title">Company Information</div>
    <div class="form-grid">
        <div class="form-field"><label>Company Name</label><input class="input" name="name" value="{{ $old('name') }}" placeholder="e.g. Anthropic" required></div>
        <div class="form-field"><label>Website</label><input class="input" name="website" value="{{ $old('website') }}" placeholder="https://"></div>
        <div class="form-field"><label>Founded Year</label><input class="input" type="number" name="founded_year" value="{{ $old('founded_year') }}" placeholder="e.g. 2021"></div>
        <div class="form-field">
            <label>Logo</label>
            @if ($company && $company->logo_path)
                <img src="{{ Storage::url($company->logo_path) }}" alt="Current logo" style="width:40px;height:40px;border-radius:8px;object-fit:cover;margin-bottom:6px;display:block;">
            @endif
            <input class="input" type="file" name="logo" accept="image/*">
        </div>
        <div class="form-field">
            <label>Status</label>
            <select class="select" name="status">
                @foreach (['active' => 'Active', 'acquired' => 'Acquired', 'inactive' => 'Inactive'] as $value => $label)
                    <option value="{{ $value }}" @selected($old('status', 'active') === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="form-field col-span-2"><label>Overview</label><textarea class="input" name="description" rows="4" placeholder="Company description...">{{ $old('description') }}</textarea></div>
    </div>
</div>

</form>
@endsection
