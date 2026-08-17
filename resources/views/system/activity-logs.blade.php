@extends('layouts.admin')
@section('title','Activity Logs')
@push('styles')<link rel="stylesheet" href="{{ asset('css/pages/final-polish.css') }}">@endpush
@section('content')
<div class="fp-page">
<x-page-header title="Activity Logs" :subtitle="$logs->total().' recorded administrative actions'" :breadcrumb="['System','Activity Logs']" />
<form method="GET" class="card fp-filterbar">
<select class="select" name="user_id"><option value="">All administrators</option>@foreach($users as $user)<option value="{{ $user->id }}" @selected((string)request('user_id')===(string)$user->id)>{{ $user->name }}</option>@endforeach</select>
<input class="input" name="action" value="{{ request('action') }}" placeholder="Exact action, e.g. role_created">
<select class="select" name="subject_type"><option value="">All subject types</option>@foreach($subjectTypes as $type)@php($short=class_basename($type))<option value="{{ $short }}" @selected(request('subject_type')===$short)>{{ $short }}</option>@endforeach</select>
<button class="btn btn-secondary"><i data-lucide="filter"></i>Apply</button>@if(request()->query())<a href="{{ route('admin.system.activity-logs') }}" class="btn btn-ghost"><i data-lucide="rotate-ccw"></i>Reset</a>@endif
</form>
<section class="card fp-table-card">
<header class="fp-card-head"><div><span class="fp-eyebrow">Audit Trail</span><h2>Administrative activity</h2><p>Chronological record of protected system and content operations.</p></div><span class="fp-count">{{ number_format($logs->total()) }} events</span></header>
@if($logs->count())<div class="table-wrap"><table class="data-table fp-table"><thead><tr><th>Actor</th><th>Action</th><th>Subject</th><th>Description</th><th>Occurred</th></tr></thead><tbody>
@foreach($logs as $log)<tr><td><div class="fp-actor"><span><i data-lucide="user-round"></i></span><div><strong>{{ $log->user->name??'System' }}</strong><small>{{ $log->user->email??'Automated/system event' }}</small></div></div></td><td><span class="fp-action">{{ ucwords(str_replace('_',' ',$log->action)) }}</span></td><td><span class="fp-muted">{{ $log->subject_name }}</span></td><td>{{ $log->description }}</td><td><span class="fp-muted">{{ $log->created_at->format('M j, Y · H:i') }}<small>{{ $log->created_at->diffForHumans() }}</small></span></td></tr>@endforeach
</tbody></table></div><div class="fp-pagination"><span>Showing {{ $logs->firstItem()??0 }}–{{ $logs->lastItem()??0 }} of {{ $logs->total() }}</span><div>{{ $logs->links() }}</div></div>
@else<div class="fp-empty"><span><i data-lucide="scroll-text"></i></span><h3>No activity matched</h3><p>Protected actions will appear here as administrators operate the platform.</p></div>@endif
</section></div>
@endsection
