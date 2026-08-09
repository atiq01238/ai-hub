@extends('layouts.admin')
@section('title', 'Integrations')

@section('content')

<x-page-header title="Integrations" subtitle="Real status, read from your actual .env / config files" :breadcrumb="['System', 'Integrations']" />

<div class="grid-3">
    @foreach ($integrations as $i)
    <div class="card card-pad">
        <div class="flex items-center justify-between" style="margin-bottom:14px;">
            <div class="kpi-icon"><i data-lucide="{{ $i['icon'] }}"></i></div>
            <span class="badge {{ $i['connected'] ? 'badge-pos' : 'badge-neutral' }}">{{ $i['connected'] ? 'Connected' : 'Not Connected' }}</span>
        </div>
        <b style="font-size:14px;">{{ $i['name'] }}</b>
        <div class="cell-sub" style="margin-top:6px;">{{ $i['detail'] }}</div>
    </div>
    @endforeach
</div>

<p class="text-sub" style="font-size:12px; margin-top:16px;">
    "Configure" and "Test Connection" buttons aren't here — actually connecting a new service
    (OAuth flow, API key setup, etc.) is different work for each integration. This page tells you
    honestly what's set up right now, read from your real config, instead of pretending buttons
    that don't do anything yet.
</p>
@endsection
