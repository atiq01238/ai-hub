@extends('layouts.admin')
@section('title','Community Submissions')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/pages/users-community.css') }}">
@endpush

@section('content')
<div class="uc-page">
<x-page-header title="Community Submissions" subtitle="Review product suggestions, corrections and community-contributed AI data." :breadcrumb="['Users & Community','Submissions']" />

@if(session('status'))<div class="alert alert-success uc-flash"><i data-lucide="check-circle-2"></i><span>{{ session('status') }}</span></div>@endif

<section class="uc-kpi-grid uc-kpi-grid--five">
@foreach([
['All',$counts['all'],'inbox',''],['Pending',$counts['pending'],'clock-3','amber'],['Needs Info',$counts['needs_info'],'message-circle-question','cyan'],['Approved',$counts['approved'],'badge-check','green'],['Rejected',$counts['rejected'],'circle-x','red']
] as [$label,$value,$icon,$tone])
<article class="uc-kpi uc-kpi--{{ $tone }}"><span><i data-lucide="{{ $icon }}"></i></span><div><small>{{ $label }}</small><strong>{{ number_format($value) }}</strong></div></article>
@endforeach
</section>

<nav class="uc-tabs">
<a href="{{ route('admin.submissions.index') }}" class="{{ !request('status')?'is-active':'' }}">All</a>
@foreach(['pending'=>'Pending','needs_info'=>'Needs Info','approved'=>'Approved','rejected'=>'Rejected'] as $key=>$label)
<a href="{{ route('admin.submissions.index',['status'=>$key]) }}" class="{{ request('status')===$key?'is-active':'' }}">{{ $label }}</a>
@endforeach
</nav>

<form method="GET" class="card uc-filterbar uc-filterbar--submissions">
<input type="hidden" name="status" value="{{ request('status') }}">
<div class="uc-search"><i data-lucide="search"></i><input class="input" name="search" value="{{ request('search') }}" placeholder="Search tool, email or website..."></div>
<select class="select" name="type"><option value="">All submission types</option>@foreach(['tool'=>'Tool','model'=>'Model','company'=>'Company','correction'=>'Correction'] as $key=>$label)<option value="{{ $key }}" @selected(request('type')===$key)>{{ $label }}</option>@endforeach</select>
<button class="btn btn-secondary"><i data-lucide="filter"></i>Apply</button>
</form>

<section class="card uc-table-card">
<div class="uc-section-head"><div><span class="uc-eyebrow">Contribution Queue</span><h2>Submission review</h2><p>Community-provided records awaiting verification and moderation.</p></div><span class="uc-count">{{ number_format($submissions->total()) }} records</span></div>
@if($submissions->count())
<div class="table-wrap"><table class="data-table uc-table"><thead><tr><th>Submission</th><th>Type</th><th>Contributor</th><th>Status</th><th>Reviewer</th><th>Submitted</th><th></th></tr></thead><tbody>
@foreach($submissions as $submission)
<tr>
<td><div class="uc-record"><span><i data-lucide="send"></i></span><div><a href="{{ route('admin.submissions.show',$submission->id) }}"><strong>{{ $submission->tool_name }}</strong></a><small>{{ $submission->website ?: \Illuminate\Support\Str::limit($submission->description,60) }}</small></div></div></td>
<td><span class="uc-type-pill">{{ ucfirst($submission->submission_type) }}</span></td>
<td><div class="uc-contributor"><strong>{{ $submission->user?->name ?? 'Guest contributor' }}</strong><small>{{ $submission->submitted_by_email }}</small></div></td>
<td><x-status-badge status="{{ ucwords(str_replace('_',' ',$submission->status)) }}" type="{{ $submission->status==='approved'?'pos':($submission->status==='rejected'?'neg':($submission->status==='needs_info'?'info':'warn')) }}" /></td>
<td><span class="uc-muted">{{ $submission->reviewer?->name ?? 'Unassigned' }}</span></td>
<td><span class="uc-muted">{{ $submission->created_at->format('M j, Y') }}<small>{{ $submission->created_at->diffForHumans() }}</small></span></td>
<td><a href="{{ route('admin.submissions.show',$submission->id) }}" class="btn btn-secondary btn-sm"><i data-lucide="scan-search"></i>Review</a></td>
</tr>
@endforeach
</tbody></table></div>
<div class="uc-pagination"><span>Showing {{ $submissions->firstItem() ?? 0 }}–{{ $submissions->lastItem() ?? 0 }} of {{ $submissions->total() }}</span><div>{{ $submissions->onEachSide(1)->links() }}</div></div>
@else<div class="uc-empty"><span><i data-lucide="inbox"></i></span><h3>No submissions found</h3><p>The selected moderation queue is clear.</p></div>@endif
</section>
</div>
@endsection
