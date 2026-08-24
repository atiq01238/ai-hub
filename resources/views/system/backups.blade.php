@extends('layouts.admin')
@section('title','Backup Center')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/pages/data-reliability.css') }}">
@endpush

@section('content')
<div class="dr-page">
<x-page-header title="Backup Center" subtitle="Create, retain and download protected local snapshots of critical AI Orbit data." :breadcrumb="['System','Backups']">
<x-slot:actions>
<button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createBackup"><i data-lucide="database-backup"></i>Create Backup</button>
</x-slot:actions>
</x-page-header>

@if(session('status'))<div class="alert alert-success dr-flash"><i data-lucide="check-circle-2"></i><span>{{ session('status') }}</span></div>@endif
@if(session('error'))<div class="alert alert-danger dr-flash"><i data-lucide="circle-alert"></i><span>{{ session('error') }}</span></div>@endif

<section class="dr-kpis">
@foreach([
['Last Backup',$lastBackup ? $lastBackup['created_at']->diffForHumans() : 'Never','clock-3',''],
['Backup Count',count($items),'layers-3','violet'],
['Backup Storage',number_format($totalBytes/1048576,1).' MB','hard-drive','cyan'],
['Disk Free',number_format($freeBytes/1073741824,1).' GB','database','green'],
] as [$label,$value,$icon,$tone])
<article class="dr-kpi dr-kpi--{{ $tone }}"><span><i data-lucide="{{ $icon }}"></i></span><div><small>{{ $label }}</small><strong>{{ $value }}</strong></div></article>
@endforeach
</section>

<section class="dr-safety">
<span><i data-lucide="shield-check"></i></span>
<div><span class="dr-eyebrow">Recovery Safety Policy</span><strong>Backup creation is automated; restoration stays intentionally controlled.</strong><p>This interface creates, downloads and deletes archives. Destructive production restore is not exposed through a one-click admin action.</p></div>
</section>

<section class="card dr-table-card">
<header class="dr-card-head"><div><span class="dr-eyebrow">Protected Archives</span><h2>Backup history</h2><p>Local snapshots currently retained by the backup service.</p></div><span class="dr-count">{{ count($items) }} archives</span></header>
@if(count($items))
<div class="table-wrap"><table class="data-table dr-table"><thead><tr><th>Archive</th><th>Created</th><th>Size</th><th>Type</th><th>State</th><th></th></tr></thead><tbody>
@foreach($items as $backup)
<tr>
<td><div class="dr-record"><span><i data-lucide="file-archive"></i></span><div><strong>{{ $backup['name'] }}</strong><small>Protected snapshot archive</small></div></div></td>
<td><span class="dr-muted">{{ $backup['created_at']->format('M j, Y · g:i A') }}<small>{{ $backup['created_at']->diffForHumans() }}</small></span></td>
<td><code>{{ $backup['size'] }}</code></td>
<td><span class="dr-pill">{{ ucfirst($backup['type']) }}</span></td>
<td><span class="dr-status is-good"><i data-lucide="circle-check"></i>Completed</span></td>
<td><div class="dr-actions"><a href="{{ route('admin.system.backups.download',$backup['name']) }}" class="icon-btn" title="Download archive"><i data-lucide="download"></i></a><form method="POST" action="{{ route('admin.system.backups.destroy',$backup['name']) }}" onsubmit="return confirm('Delete this backup archive?')">@csrf @method('DELETE')<button class="icon-btn icon-btn--danger" type="submit" title="Delete archive"><i data-lucide="trash-2"></i></button></form></div></td>
</tr>
@endforeach
</tbody></table></div>
@else
<div class="dr-empty"><span><i data-lucide="database-backup"></i></span><h3>No backup archives yet</h3><p>Create a full, database-only or uploaded-files snapshot.</p></div>
@endif
</section>

<div class="modal fade" id="createBackup" tabindex="-1" aria-hidden="true">
<div class="modal-dialog modal-dialog-centered"><div class="modal-content dr-modal"><form method="POST" action="{{ route('admin.system.backups.store') }}">@csrf
<div class="modal-head"><div><span class="dr-eyebrow">Protected Snapshot</span><h3>Create Backup</h3></div><button type="button" class="icon-btn" data-bs-dismiss="modal"><i data-lucide="x"></i></button></div>
<div class="modal-body">
<label class="dr-field"><span>Backup Type</span><select class="select" name="type" required><option value="full">Full — database + uploaded files</option><option value="database">Database only</option><option value="files">Uploaded files only</option></select></label>
<div class="dr-callout"><i data-lucide="info"></i><p>MySQL backups use <code>mysqldump</code>. XAMPP Windows auto-detection is supported; otherwise configure <code>MYSQLDUMP_PATH</code> in <code>.env</code>.</p></div>
</div>
<div class="modal-foot"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button class="btn btn-primary"><i data-lucide="database-backup"></i>Create Backup</button></div>
</form></div></div>
</div>
</div>
@endsection
