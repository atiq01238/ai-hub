@extends('layouts.admin')
@section('title','Integrations')
@push('styles')<link rel="stylesheet" href="{{ asset('css/pages/administration.css') }}">@endpush
@section('content')
@php $connected=collect($integrations)->where('connected',true)->count(); @endphp
<div class="ad-page">
<x-page-header title="Integrations" subtitle="Read-only configuration visibility based on the application's actual environment and runtime config." :breadcrumb="['System','Integrations']" />
<section class="ad-integration-hero"><span><i data-lucide="plug-zap"></i></span><div><span class="ad-eyebrow">Configuration Visibility</span><strong>{{ $connected }} of {{ count($integrations) }} integrations appear configured</strong><p>“Connected” means required configuration is present—not that a live external authentication or API test has succeeded.</p></div></section>
<section class="ad-integration-grid">
@foreach($integrations as $i)
<article class="card ad-integration-card {{ $i['connected']?'is-connected':'' }}"><div class="ad-integration-card__top"><span><i data-lucide="{{ $i['icon'] }}"></i></span><span class="ad-status {{ $i['connected']?'is-good':'' }}"><i data-lucide="{{ $i['connected']?'circle-check':'circle-dashed' }}"></i>{{ $i['connected']?'Configured':'Not Configured' }}</span></div><h2>{{ $i['name'] }}</h2><p>{{ $i['detail'] }}</p><div class="ad-integration-card__foot"><i data-lucide="file-key-2"></i><span>Read from real app configuration</span></div></article>
@endforeach
</section>
<section class="card ad-boundary"><span><i data-lucide="info"></i></span><div><span class="ad-eyebrow">Capability Boundary</span><strong>No fake Configure or Test Connection actions</strong><p>OAuth flows, credential storage and provider-specific live tests require separate backend implementations. This page intentionally reports configuration state only.</p></div></section>
</div>
@endsection
