@extends('layouts.admin')
@section('title','Data Import')
@push('styles')<link rel="stylesheet" href="{{ asset('css/pages/data-import.css') }}">@endpush
@section('content')
<div class="import-page">
<x-page-header title="Data Import" subtitle="Bulk-load catalog, pricing and benchmark data with validation before anything is saved." :breadcrumb="['AI Management','Data Import']" />

<section class="import-hero card"><div class="import-hero__copy"><span class="import-eyebrow">Unified Import Center</span><h2>Companies, models, tools, pricing, logos and benchmarks</h2><p>Every importer previews rows first, checks relationships and duplicates, then commits in a transaction.</p><div class="import-chips"><span><i data-lucide="shield-check"></i> Preview first</span><span><i data-lucide="copy-check"></i> Duplicate safe</span><span><i data-lucide="database"></i> Transactional</span></div></div><div class="import-hero__metric"><strong>{{ number_format($companyCount+$modelCount+$toolCount) }}</strong><span>catalog records</span></div></section>

<section class="import-grid">
@php
$modules=[
 ['Companies','building-2',$companyCount,'admin.data-import.companies.preview','admin.data-import.companies.template','Download 235-company CSV','AI Companies'],
 ['AI Models','brain-circuit',$modelCount,'admin.data-import.models.preview','admin.data-import.models.template','Download 150-model CSV','AI Models'],
 ['AI Tools','wrench',$toolCount,'admin.data-import.tools.preview','admin.data-import.tools.template','Download curated tools CSV','AI Tools'],
 ['Pricing','credit-card',$pricingCount,'admin.data-import.pricing.preview','admin.data-import.pricing.template','Download pricing CSV','Pricing'],
 ['Benchmarks','bar-chart-3',$benchmarkResultCount,'admin.data-import.benchmarks.preview','admin.data-import.benchmarks.template','Download benchmark template','Benchmarks'],
];
@endphp
@foreach($modules as [$title,$icon,$count,$previewRoute,$templateRoute,$templateLabel,$permission])
<article class="import-module card import-module--active">
<div class="import-module__icon"><i data-lucide="{{ $icon }}"></i></div>
<div class="import-module__head"><div><span class="import-eyebrow">Available now</span><h3>{{ $title }}</h3></div><span class="badge badge-pos">Ready</span></div>
<p><strong>{{ number_format($count) }}</strong> currently in database. Upload CSV/XLSX, validate mappings and review errors before import.</p>
<form method="POST" action="{{ route($previewRoute) }}" enctype="multipart/form-data" class="import-upload">@csrf
<label class="import-drop"><i data-lucide="upload-cloud"></i><strong>Select {{ strtolower($title) }} spreadsheet</strong><span>CSV recommended · XLSX supported with PHP Zip</span><input type="file" name="file" accept=".csv,.xlsx" required></label>
<div class="import-actions"><button class="btn btn-primary" type="submit"><i data-lucide="scan-line"></i> Validate & Preview</button><a class="btn btn-secondary" href="{{ route($templateRoute) }}"><i data-lucide="download"></i> {{ $templateLabel }}</a></div>
</form></article>
@endforeach

<article class="import-module card import-module--active"><div class="import-module__icon"><i data-lucide="image"></i></div><div class="import-module__head"><div><span class="import-eyebrow">Media workflow</span><h3>Logos & Media</h3></div><span class="badge badge-pos">Ready</span></div><p>Review missing company/tool logo candidates, save approved candidates locally, and use company branding as the model fallback.</p><div class="import-actions"><a class="btn btn-primary" href="{{ route('admin.data-import.logos.index') }}"><i data-lucide="images"></i> Review Logos</a></div></article>
</section>

<section class="import-notes card"><div><i data-lucide="shield-alert"></i></div><div><h3>Data integrity rules</h3><p>Models and tools require existing companies. Pricing requires an existing tool. Benchmark scores require an existing tool/model and are never invented by this import center. Comparison data is intentionally excluded.</p></div></section>
</div>
@endsection
