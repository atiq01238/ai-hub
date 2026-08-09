@extends('layouts.admin')
@section('title', 'Error Detail')

@section('content')

<form action="{{ route('admin.system.errors.update-status', $error->id) }}" method="POST">
@csrf
@method('PUT')

<x-page-header title="{{ class_basename($error->exception_class) }}" subtitle="{{ basename($error->file ?? '') }}:{{ $error->line }} · {{ ucfirst($error->severity) }}" :breadcrumb="['System', 'Error Monitoring', 'Detail']">
    <x-slot:actions>
        <button type="submit" name="status" value="investigating" class="btn btn-secondary btn-sm"><i data-lucide="search"></i> Investigating</button>
        <button type="submit" name="status" value="resolved" class="btn btn-primary btn-sm"><i data-lucide="check"></i> Mark Resolved</button>
    </x-slot:actions>
</x-page-header>

@if (session('status'))
    <div class="alert alert-success" style="margin-bottom:16px;">{{ session('status') }}</div>
@endif

<div class="grid-12">
    <div class="col-8">
        <div class="card card-pad" style="margin-bottom:16px;">
            <div class="section-title">Error Message</div>
            <div class="mono" style="font-size:12.5px; background:var(--surface-2); padding:12px 14px; border-radius:8px; color:var(--neg);">
                {{ class_basename($error->exception_class) }}: {{ $error->message ?: 'No message' }}
            </div>
        </div>
        <div class="card card-pad">
            <div class="section-title">Stack Trace</div>
            <div class="mono" style="font-size:11.5px; background:var(--surface-2); padding:14px; border-radius:8px; line-height:1.8; color:var(--text-md); white-space:pre-wrap; max-height:400px; overflow-y:auto;">{{ $error->trace ?: 'No trace recorded.' }}</div>
        </div>
    </div>
    <div class="col-4 card card-pad">
        <div class="section-title">Details</div>
        <div style="margin-bottom:12px;"><div class="cell-sub">File</div><div style="font-weight:600; font-size:12px; word-break:break-all;">{{ $error->file }}:{{ $error->line }}</div></div>
        <div style="margin-bottom:12px;"><div class="cell-sub">URL</div><div style="font-weight:600; font-size:12px; word-break:break-all;">{{ $error->http_method }} {{ $error->url ?? '—' }}</div></div>
        <div style="margin-bottom:12px;"><div class="cell-sub">Occurrences</div><div style="font-weight:600;">{{ $error->occurrence_count }}x — first {{ $error->first_seen_at->diffForHumans() }}, last {{ $error->last_seen_at->diffForHumans() }}</div></div>
        <div style="margin-bottom:16px;"><div class="cell-sub">Triggered By</div><div style="font-weight:600;">{{ $error->user->name ?? 'Guest / system' }}</div></div>
        <div class="form-field"><label>Resolution Notes</label><textarea class="input" name="resolution_notes" rows="4" placeholder="Add a note about this error...">{{ $error->resolution_notes }}</textarea></div>
    </div>
</div>
</form>
@endsection
