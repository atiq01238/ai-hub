@extends('layouts.admin')
@section('title','Error Detail')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/pages/system-operations.css') }}">
@endpush

@section('content')
<div class="so-page so-error-detail">
<form action="{{ route('admin.system.errors.update-status',$error->id) }}" method="POST">
@csrf @method('PUT')
<x-page-header :title="class_basename($error->exception_class)" :subtitle="basename($error->file ?? '').':'.$error->line.' · '.ucfirst($error->severity)" :breadcrumb="['System','Error Monitoring','Detail']">
<x-slot:actions>
<a href="{{ route('admin.system.errors.index') }}" class="btn btn-secondary"><i data-lucide="arrow-left"></i>Error Queue</a>
@if(auth()->user()->canAccessModule('Error Monitoring','Edit'))
<button type="submit" name="status" value="investigating" class="btn btn-secondary"><i data-lucide="search"></i>Investigating</button>
<button type="submit" name="status" value="resolved" class="btn btn-primary"><i data-lucide="check"></i>Mark Resolved</button>
@endif
</x-slot:actions>
</x-page-header>

@if(session('status'))<div class="alert alert-success so-flash"><i data-lucide="check-circle-2"></i><span>{{ session('status') }}</span></div>@endif

<section class="card so-error-hero">
<div><div class="so-error-hero__badges"><span class="so-severity so-severity--{{ $error->severity }}">{{ ucfirst($error->severity) }}</span><span class="so-status {{ $error->status==='resolved'?'is-good':($error->status==='investigating'?'is-warning':'is-bad') }}">{{ ucfirst($error->status) }}</span></div><h1>{{ class_basename($error->exception_class) }}</h1><p>{{ $error->message ?: 'No error message was recorded.' }}</p></div>
<div class="so-error-hero__count"><span class="so-eyebrow">Occurrences</span><strong>{{ number_format($error->occurrence_count) }}×</strong><small>Last seen {{ $error->last_seen_at->diffForHumans() }}</small></div>
</section>

<div class="so-error-detail__layout">
<main class="so-error-detail__main">
<section class="card so-panel">
<header class="so-card-head"><div><span class="so-eyebrow">Exception Message</span><h2>Failure summary</h2></div><i data-lucide="circle-alert"></i></header>
<pre class="so-code so-code--message">{{ class_basename($error->exception_class) }}: {{ $error->message ?: 'No message' }}</pre>
</section>
<section class="card so-panel">
<header class="so-card-head"><div><span class="so-eyebrow">Stack Trace</span><h2>Captured execution trace</h2></div><i data-lucide="braces"></i></header>
<pre class="so-code">{{ $error->trace ?: 'No trace recorded.' }}</pre>
</section>
</main>
<aside class="so-error-detail__aside">
<section class="card so-facts">
<span class="so-eyebrow">Error Context</span>
<dl>
<div><dt>File</dt><dd><code>{{ $error->file ?: '—' }}:{{ $error->line }}</code></dd></div>
<div><dt>Request</dt><dd>{{ $error->http_method ?: '—' }} {{ $error->url ?: '—' }}</dd></div>
<div><dt>Triggered by</dt><dd>{{ $error->user->name ?? 'Guest / system' }}</dd></div>
<div><dt>First seen</dt><dd>{{ $error->first_seen_at?->format('M j, Y g:i A') ?? '—' }}</dd></div>
<div><dt>Last seen</dt><dd>{{ $error->last_seen_at?->format('M j, Y g:i A') ?? '—' }}</dd></div>
</dl>
</section>
@if(auth()->user()->canAccessModule('Error Monitoring','Edit'))
<section class="card so-resolution-form">
<span class="so-eyebrow">Investigation Record</span><h3>Resolution notes</h3><textarea class="textarea" name="resolution_notes" rows="7" placeholder="Root cause, mitigation and verification steps...">{{ old('resolution_notes',$error->resolution_notes) }}</textarea><p>Notes are stored with the grouped exception for future incident review.</p>
</section>
@endif
</aside>
</div>
</form>
</div>
@endsection
