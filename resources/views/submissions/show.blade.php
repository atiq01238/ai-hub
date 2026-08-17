@extends('layouts.admin')
@section('title','Submission #' . $submission->id)

@push('styles')
<link rel="stylesheet" href="{{ asset('css/pages/users-community.css') }}">
@endpush

@section('content')
<div class="uc-page uc-case">
<x-page-header :title="$submission->tool_name" :subtitle="'Submission #'.$submission->id.' · '.ucfirst($submission->submission_type)" :breadcrumb="['Users & Community','Submissions','#'.$submission->id]">
<x-slot:actions><a href="{{ route('admin.submissions.index') }}" class="btn btn-secondary"><i data-lucide="arrow-left"></i>Submissions</a>@if($submission->convertedTool)<a href="{{ route('admin.tools.show',$submission->convertedTool->id) }}" class="btn btn-primary"><i data-lucide="wrench"></i>Open Tool Draft</a>@endif</x-slot:actions>
</x-page-header>

@if(session('status'))<div class="alert alert-success uc-flash"><i data-lucide="check-circle-2"></i><span>{{ session('status') }}</span></div>@endif
@if($errors->any())<div class="alert alert-danger uc-flash"><i data-lucide="circle-alert"></i><span>{{ $errors->first() }}</span></div>@endif

<section class="card uc-case__hero">
<div><div class="uc-case__badges"><span class="uc-type-pill">{{ ucfirst($submission->submission_type) }}</span><x-status-badge status="{{ ucwords(str_replace('_',' ',$submission->status)) }}" type="{{ $submission->status==='approved'?'pos':($submission->status==='rejected'?'neg':($submission->status==='needs_info'?'info':'warn')) }}" /></div><h1>{{ $submission->tool_name }}</h1><p>{{ $submission->description ?: 'No description supplied.' }}</p></div>
<div class="uc-case__signal"><span class="uc-eyebrow">Submitted</span><strong>{{ $submission->created_at->format('M j') }}</strong><small>{{ $submission->created_at->format('Y · g:i A') }}</small></div>
</section>

<div class="uc-case__layout">
<main class="uc-case__main">
<section class="card uc-panel"><div class="uc-section-head"><div><span class="uc-eyebrow">Submission Data</span><h2>Submitted information</h2></div><i data-lucide="clipboard-list"></i></div>
<dl class="uc-data-grid">
<div><dt>Type</dt><dd>{{ ucfirst($submission->submission_type) }}</dd></div>
<div><dt>Category</dt><dd>{{ $submission->category ?: '—' }}</dd></div>
<div><dt>Website</dt><dd>@if($submission->website)<a href="{{ $submission->website }}" target="_blank" rel="noopener noreferrer">{{ $submission->website }} <i data-lucide="external-link"></i></a>@else—@endif</dd></div>
<div><dt>Email</dt><dd>{{ $submission->submitted_by_email }}</dd></div>
</dl>
<div class="uc-case__description">{{ $submission->description ?: 'No description supplied.' }}</div>
</section>

@if($submission->admin_notes)
<section class="card uc-panel"><div class="uc-section-head"><div><span class="uc-eyebrow">Moderation Record</span><h2>Administrator notes</h2></div><i data-lucide="notebook-pen"></i></div><div class="uc-note">{{ $submission->admin_notes }}</div></section>
@endif
</main>

<aside class="uc-case__aside">
<section class="card uc-contributor-card"><span class="uc-eyebrow">Contributor</span><div class="uc-contributor-card__profile"><span><i data-lucide="user-round"></i></span><div><strong>{{ $submission->user?->name ?? 'Guest contributor' }}</strong><small>{{ $submission->submitted_by_email }}</small></div></div>@if($submission->user)<a href="{{ route('admin.users.show',$submission->user->id) }}" class="btn btn-secondary"><i data-lucide="user-round-search"></i>View User</a>@endif</section>

@if(in_array($submission->status,['pending','needs_info'],true) && auth()->user()->canAccessModule('Submissions','Edit'))
<section class="card uc-moderation">
<span class="uc-eyebrow">Moderation Decision</span>
<h3>Review contribution</h3>
<form method="POST" action="{{ route('admin.submissions.approve',$submission->id) }}">@csrf<label><span>Approval note</span><textarea class="textarea" name="admin_notes" rows="3" placeholder="Optional internal context..."></textarea></label><button class="btn btn-primary" type="submit"><i data-lucide="badge-check"></i>{{ $submission->submission_type==='tool'?'Approve & Create Tool Draft':'Approve Submission' }}</button></form>
<div class="uc-divider"></div>
<form method="POST" action="{{ route('admin.submissions.request-info',$submission->id) }}">@csrf<label><span>Information needed <b>*</b></span><textarea class="textarea" name="admin_notes" rows="3" required placeholder="Explain what information is missing..."></textarea></label><button class="btn btn-secondary" type="submit"><i data-lucide="message-circle-question"></i>Request Information</button></form>
<div class="uc-divider"></div>
<form method="POST" action="{{ route('admin.submissions.reject',$submission->id) }}" onsubmit="return confirm('Reject this submission?')">@csrf<label><span>Rejection reason <b>*</b></span><textarea class="textarea" name="admin_notes" rows="3" required placeholder="Record the policy or quality reason..."></textarea></label><button class="btn btn-danger" type="submit"><i data-lucide="x"></i>Reject Submission</button></form>
</section>
@else
<section class="card uc-facts"><span class="uc-eyebrow">Workflow</span><dl><div><dt>Status</dt><dd>{{ ucwords(str_replace('_',' ',$submission->status)) }}</dd></div><div><dt>Reviewer</dt><dd>{{ $submission->reviewer?->name ?? '—' }}</dd></div><div><dt>Reviewed</dt><dd>{{ $submission->reviewed_at?->format('M j, Y g:i A') ?? '—' }}</dd></div></dl></section>
@endif
</aside>
</div>
</div>
@endsection
