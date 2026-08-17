@extends('layouts.admin')
@section('title','News Source Management')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/pages/data-reliability.css') }}">
@endpush

@section('content')
@php
$active=$sources->where('status','active')->count();
$errors=$sources->filter(fn($s)=>filled($s->last_error))->count();
$totalCollected=$sources->sum('articles_collected');
@endphp
<div class="dr-page">
<x-page-header title="News Source Management" subtitle="Configure official RSS/API endpoints feeding the AI News Intelligence pipeline." :breadcrumb="['AI Intelligence','News Sources']">
<x-slot:actions><a href="{{ route('admin.system.source-reliability') }}" class="btn btn-secondary"><i data-lucide="gauge"></i>Source Reliability</a></x-slot:actions>
</x-page-header>

@if(session('status'))<div class="alert alert-success dr-flash"><i data-lucide="check-circle-2"></i><span>{{ session('status') }}</span></div>@endif
@if($errors->any())<div class="alert alert-danger dr-flash"><i data-lucide="circle-alert"></i><span>{{ $errors->first() }}</span></div>@endif

<section class="dr-kpis">
@foreach([
['Configured Sources',$sources->count(),'rss',''],
['Active Sources',$active,'radio-tower','green'],
['Failure Signals',$errors,'triangle-alert','red'],
['Articles Collected',$totalCollected,'newspaper','violet'],
] as [$label,$value,$icon,$tone])
<article class="dr-kpi dr-kpi--{{ $tone }}"><span><i data-lucide="{{ $icon }}"></i></span><div><small>{{ $label }}</small><strong>{{ number_format($value) }}</strong></div></article>
@endforeach
</section>

<section class="card dr-panel dr-source-create">
<header class="dr-card-head"><div><span class="dr-eyebrow">Collection Network</span><h2>Add news source</h2><p>RSS is fully integrated. API sources can be stored, but collection is not wired yet.</p></div><i data-lucide="plus-circle"></i></header>
<form action="{{ route('admin.system.news-sources.store') }}" method="POST" class="dr-source-form">@csrf
<label class="dr-field"><span>Source Name <b>*</b></span><input class="input" name="name" placeholder="e.g. TechCrunch AI" required></label>
<label class="dr-field"><span>Type</span><select class="select" name="type"><option value="rss">RSS — active collector</option><option value="api">API — configuration only</option></select></label>
<label class="dr-field dr-field--wide"><span>Feed / Endpoint URL <b>*</b></span><input class="input" type="url" name="url" placeholder="https://example.com/feed.xml" required></label>
<label class="dr-field"><span>Default Category</span><select class="select" name="default_category"><option value="">None</option><option>Breaking News</option><option>Product Launch</option><option>Product Update</option><option>Research</option><option>Pricing Change</option></select></label>
<label class="dr-field"><span>Company</span><select class="select" name="company_id"><option value="">Auto-detect</option>@foreach($companies as $company)<option value="{{ $company->id }}">{{ $company->name }}</option>@endforeach</select></label>
<button class="btn btn-primary" type="submit"><i data-lucide="plus"></i>Add Source</button>
</form>
</section>

<section class="card dr-table-card">
<header class="dr-card-head"><div><span class="dr-eyebrow">Configured Sources</span><h2>Collection endpoints</h2><p>Toggle collection state or remove obsolete endpoints.</p></div><span class="dr-count">{{ $sources->count() }} sources</span></header>
@if($sources->count())
<div class="table-wrap"><table class="data-table dr-table"><thead><tr><th>Source</th><th>Type</th><th>Endpoint</th><th>Last Fetch</th><th>Collected</th><th>Status</th><th></th></tr></thead><tbody>
@foreach($sources as $source)
<tr>
<td><div class="dr-record"><span><i data-lucide="{{ $source->type==='rss'?'rss':'braces' }}"></i></span><div><strong>{{ $source->name }}</strong><small>{{ $source->default_category ?: 'No default category' }}</small></div></div></td>
<td><span class="dr-pill">{{ strtoupper($source->type) }}</span></td>
<td><a href="{{ $source->url }}" target="_blank" rel="noopener noreferrer" class="dr-endpoint">{{ \Illuminate\Support\Str::limit($source->url,60) }} <i data-lucide="external-link"></i></a></td>
<td><span class="dr-muted">{{ $source->last_fetched_at?->diffForHumans() ?? 'Never' }}@if($source->last_error)<small class="is-error">{{ \Illuminate\Support\Str::limit($source->last_error,55) }}</small>@endif</span></td>
<td><code>{{ number_format($source->articles_collected) }}</code></td>
<td><form action="{{ route('admin.system.news-sources.toggle',$source->id) }}" method="POST">@csrf<button type="submit" class="dr-toggle-status"><span class="dr-status {{ $source->status==='active'?'is-good':'' }}"><i data-lucide="{{ $source->status==='active'?'circle-check':'pause-circle' }}"></i>{{ ucfirst($source->status) }}</span></button></form></td>
<td><form action="{{ route('admin.system.news-sources.destroy',$source->id) }}" method="POST" onsubmit="return confirm('Remove this news source?')">@csrf @method('DELETE')<button class="icon-btn icon-btn--danger" type="submit"><i data-lucide="trash-2"></i></button></form></td>
</tr>
@endforeach
</tbody></table></div>
@else<div class="dr-empty"><span><i data-lucide="rss"></i></span><h3>No sources configured</h3><p>Add an RSS source above to begin automated news collection.</p></div>@endif
</section>

<div class="dr-architecture-note"><i data-lucide="info"></i><p><strong>Collection note:</strong> “Last Fetch” and “Articles Collected” are operational telemetry. API source records are accepted by the database, but the current automated collector is RSS-focused.</p></div>
</div>
@endsection
