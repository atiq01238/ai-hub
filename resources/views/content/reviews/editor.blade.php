@extends('layouts.admin')
@section('title','Add Editorial Review')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/pages/content.css') }}">
@endpush

@section('content')
<div class="content-page content-review-editor">
<form action="{{ route('admin.content.reviews.store') }}" method="POST">
@csrf
<x-page-header title="Add Editorial Review" subtitle="Create a structured expert review with score breakdown, pros, cons and moderation state." :breadcrumb="['Content','Reviews','Add Editorial Review']">
<x-slot:actions><a href="{{ route('admin.content.reviews.index') }}" class="btn btn-secondary"><i data-lucide="arrow-left"></i>Cancel</a><button class="btn btn-primary"><i data-lucide="save"></i>Save Review</button></x-slot:actions>
</x-page-header>

@if($errors->any())<div class="alert alert-danger content-errors"><i data-lucide="circle-alert"></i><div><strong>Please review the fields.</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div></div>@endif

<div class="content-review-editor__layout">
<main class="content-review-editor__main">
<section class="card content-panel">
<div class="content-section-head"><div><span class="content-eyebrow">Review</span><h2>Verdict & narrative</h2><p>Capture the editorial conclusion and supporting detail.</p></div><span class="content-panel__icon"><i data-lucide="message-square-text"></i></span></div>
<div class="content-form-grid">
<label class="content-field"><span>AI Tool <b>*</b></span><select class="select" name="tool_id" required><option value="">Select tool...</option>@foreach($tools as $tool)<option value="{{ $tool->id }}" @selected((string)old('tool_id')===(string)$tool->id)>{{ $tool->name }}</option>@endforeach</select></label>
<label class="content-field"><span>Attributed User</span><select class="select" name="user_id"><option value="">Editorial Team</option>@foreach($reviewers as $user)<option value="{{ $user->id }}" @selected((string)old('user_id')===(string)$user->id)>{{ $user->name }}</option>@endforeach</select></label>
<label class="content-field content-field--full"><span>Verdict</span><input class="input" name="verdict" value="{{ old('verdict') }}" placeholder="A concise editorial verdict"></label>
<label class="content-field content-field--full"><span>Review Body</span><textarea class="textarea" rows="10" name="body">{{ old('body') }}</textarea></label>
<label class="content-field"><span>Pros — one per line</span><textarea class="textarea" rows="7" name="pros_input">{{ old('pros_input') }}</textarea></label>
<label class="content-field"><span>Cons — one per line</span><textarea class="textarea" rows="7" name="cons_input">{{ old('cons_input') }}</textarea></label>
</div>
</section>

<section class="card content-panel">
<div class="content-section-head"><div><span class="content-eyebrow">Scorecard</span><h2>Rating breakdown</h2><p>All scores use the 1–5 scale.</p></div><span class="content-panel__icon"><i data-lucide="gauge"></i></span></div>
<div class="content-form-grid">
@foreach(['quality'=>'Quality','speed'=>'Speed','features'=>'Features','ease_of_use'=>'Ease of Use','value'=>'Value'] as $key=>$label)
<label class="content-field"><span>{{ $label }}</span><input class="input" type="number" min="1" max="5" step="0.1" name="{{ $key }}" value="{{ old($key) }}" placeholder="1–5"></label>
@endforeach
<label class="content-field"><span>Overall Rating <b>*</b></span><input class="input" type="number" min="1" max="5" step="0.1" name="rating" value="{{ old('rating') }}" required></label>
</div>
</section>
</main>

<aside class="content-review-editor__aside">
<section class="card content-editor__publish">
<span class="content-eyebrow">Moderation</span><div class="content-editor__publish-icon"><i data-lucide="shield-check"></i></div>
<label class="content-field"><span>Status <b>*</b></span><select class="select" name="status" required><option value="pending" @selected(old('status','pending')==='pending')>Pending</option><option value="published" @selected(old('status')==='published')>Published</option></select><small>Published editorial reviews are immediately marked as moderated by the current admin.</small></label>
</section>
</aside>
</div>
</form>
</div>
@endsection
