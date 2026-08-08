@extends('layouts.admin')
@section('title', $company->name . ' · Company Detail')

@php
    use Illuminate\Support\Facades\Storage;
@endphp

@section('content')

<x-page-header
    title="{{ $company->name }}"
    subtitle="{{ $company->website ?? '—' }} · {{ ucfirst($company->status) }}"
    :breadcrumb="['AI Management', 'AI Companies', $company->name]">
    <x-slot:actions>
        <a href="{{ route('admin.companies.edit', $company->id) }}" class="btn btn-primary btn-sm"><i data-lucide="pencil"></i> Edit Company</a>
    </x-slot:actions>
</x-page-header>

@if (session('status'))
    <div class="alert alert-success" style="margin-bottom:16px;">{{ session('status') }}</div>
@endif

<div class="tabs">
    <div class="tab is-active">Overview</div>
    <div class="tab">AI Tools</div>
    <div class="tab">Models</div>
    <div class="tab">Latest News</div>
    <div class="tab">Pricing</div>
    <div class="tab">Comparisons</div>
    <div class="tab">Reviews</div>
    <div class="tab">Timeline</div>
</div>
{{-- Note: same as the tool detail page, these tabs are visual only for now — no JS wires them up yet. --}}

<div class="grid-12">
    <div class="col-8 card card-pad">
        @if ($company->logo_path)
            <img src="{{ Storage::url($company->logo_path) }}" alt="{{ $company->name }} logo" style="width:48px;height:48px;border-radius:10px;object-fit:cover;margin-bottom:16px;">
        @endif
        <div class="section-title">Overview</div>
        <p class="text-sub" style="font-size:13.5px; line-height:1.7;">
            {{ $company->description ?: 'No overview added yet.' }}
        </p>
    </div>
    <div class="col-4 card card-pad">
        <div class="section-title">Snapshot</div>
        <div class="flex items-center justify-between" style="margin-bottom:10px;"><span class="text-sub">Founded</span><b>{{ $company->founded_year ?? '—' }}</b></div>
        <div class="flex items-center justify-between" style="margin-bottom:10px;"><span class="text-sub">AI Tools</span><b>{{ $company->tools_count }}</b></div>
        <div class="flex items-center justify-between"><span class="text-sub">Latest Update</span><b>{{ $company->updated_at->diffForHumans() }}</b></div>
    </div>
</div>

@endsection
