@extends('layouts.admin')
@section('title','Report Case #' . $report->id)

@push('styles')
<link rel="stylesheet" href="{{ asset('css/pages/users-community.css') }}">
@endpush

@section('content')
@php
$subject=$report->reportable;
$subjectUrl=match(true){
    $subject instanceof \App\Models\User => route('admin.users.show',$subject->id),
    $subject instanceof \App\Models\Review => route($subject->review_type==='user'?'admin.community.reviews.show':'admin.content.reviews.show',$subject->id),
    $subject instanceof \App\Models\Submission => route('admin.submissions.show',$subject->id),
    default => null,
};
@endphp

<div class="uc-page uc-case">
<x-page-header :title="'Report Case #'.$report->id" :subtitle="ucfirst($report->reason).' · '.class_basename($report->reportable_type).' report'" :breadcrumb="['Users & Community','Reports & Abuse','#'.$report->id]">
<x-slot:actions><a href="{{ route('admin.community.reports.index') }}" class="btn btn-secondary"><i data-lucide="arrow-left"></i>Reports</a>@if($subjectUrl)<a href="{{ $subjectUrl }}" class="btn btn-secondary"><i data-lucide="external-link"></i>Open Reported Item</a>@endif</x-slot:actions>
</x-page-header>

@if(session('status'))<div class="alert alert-success uc-flash"><i data-lucide="check-circle-2"></i><span>{{ session('status') }}</span></div>@endif
@if($errors->any())<div class="alert alert-danger uc-flash"><i data-lucide="circle-alert"></i><span>{{ $errors->first() }}</span></div>@endif

<section class="card uc-case__hero uc-case__hero--report">
<div><div class="uc-case__badges"><span class="uc-priority uc-priority--{{ $report->priority }}">{{ ucfirst($report->priority) }} Priority</span><x-status-badge status="{{ ucfirst($report->status) }}" type="{{ in_array($report->status,['resolved','dismissed'],true)?'pos':'warn' }}" /></div><h1>{{ $report->subject_label }}</h1><p>{{ $report->description ?: 'The reporter did not provide additional details.' }}</p></div>
<div class="uc-case__signal"><span class="uc-eyebrow">Case age</span><strong>{{ $report->created_at->diffForHumans(null,true) }}</strong><small>Opened {{ $report->created_at->format('M j, Y') }}</small></div>
</section>

<div class="uc-case__layout">
<main class="uc-case__main">
<section class="card uc-panel"><div class="uc-section-head"><div><span class="uc-eyebrow">Investigation</span><h2>Case details</h2></div><i data-lucide="shield-alert"></i></div>
<dl class="uc-data-grid"><div><dt>Reason</dt><dd>{{ ucfirst($report->reason) }}</dd></div><div><dt>Reported type</dt><dd>{{ class_basename($report->reportable_type) }}</dd></div><div><dt>Assigned to</dt><dd>{{ $report->assignee?->name ?? 'Unassigned' }}</dd></div><div><dt>Reported at</dt><dd>{{ $report->created_at->format('M j, Y g:i A') }}</dd></div></dl>
<div class="uc-case__description">{{ $report->description ?: 'No additional detail provided.' }}</div>
</section>

@if($report->resolution_note)
<section class="card uc-panel"><div class="uc-section-head"><div><span class="uc-eyebrow">Resolution Record</span><h2>Case decision</h2></div><i data-lucide="file-check-2"></i></div><div class="uc-note">{{ $report->resolution_note }}</div><div class="uc-resolution-meta">{{ $report->resolver?->name ?? 'Administrator' }}@if($report->resolved_at) · {{ $report->resolved_at->format('M j, Y g:i A') }}@endif</div></section>
@endif
</main>

<aside class="uc-case__aside">
<section class="card uc-contributor-card"><span class="uc-eyebrow">Reporter</span>@if($report->reporter)<div class="uc-contributor-card__profile"><span><i data-lucide="user-round"></i></span><div><strong>{{ $report->reporter->name }}</strong><small>{{ $report->reporter->email }}</small></div></div><a href="{{ route('admin.users.show',$report->reporter->id) }}" class="btn btn-secondary"><i data-lucide="user-round-search"></i>View Reporter</a>@else<p class="uc-muted">The reporting account has been deleted.</p>@endif</section>

@if(auth()->user()->canAccessModule('Reports','Edit'))
<section class="card uc-moderation">
<span class="uc-eyebrow">Case Workflow</span><h3>Record investigation decision</h3>
<form method="POST" action="{{ route('admin.community.reports.status',$report->id) }}" id="reportWorkflowForm">@csrf @method('PATCH')
<label><span>Decision <b>*</b></span><select class="select" name="status" id="reportStatus" required><option value="reviewing" @selected($report->status==='reviewing')>Mark as reviewing</option><option value="resolved" @selected($report->status==='resolved')>Resolve with action</option><option value="dismissed" @selected($report->status==='dismissed')>Dismiss report</option></select></label>
<label><span>Priority <b>*</b></span><select class="select" name="priority" required>@foreach(['low','medium','high','critical'] as $p)<option value="{{ $p }}" @selected($report->priority===$p)>{{ ucfirst($p) }}</option>@endforeach</select></label>
<label><span>Investigation / resolution note</span><textarea class="textarea" name="resolution_note" id="resolutionNote" rows="5" placeholder="Evidence checked, decision and action taken...">{{ old('resolution_note',$report->resolution_note) }}</textarea><small>Required when resolving or dismissing a case.</small></label>
<button class="btn btn-primary" type="submit"><i data-lucide="save"></i>Save Case Decision</button>
</form>
</section>
@else
<section class="card uc-facts"><span class="uc-eyebrow">Read-only Access</span><p class="uc-muted">You can inspect this case but cannot change its workflow state.</p></section>
@endif
</aside>
</div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded',()=>{
    const form=document.getElementById('reportWorkflowForm');
    if(!form)return;
    form.addEventListener('submit',event=>{
        const status=document.getElementById('reportStatus')?.value;
        const note=document.getElementById('resolutionNote')?.value.trim();
        if((status==='resolved'||status==='dismissed')&&!note){
            event.preventDefault();
            document.getElementById('resolutionNote')?.focus();
            alert('A resolution note is required for this decision.');
        }
    });
});
</script>
@endpush
