@extends('layouts.admin')
@section('title', 'Review Editor')
@section('content')
<form method="POST" action="{{ route('admin.content.reviews.store') }}">@csrf
<x-page-header title="Editorial Review Editor" subtitle="Create a staff/editorial review tied to a real Tool" :breadcrumb="['Content','Reviews','Editor']"><x-slot:actions><button class="btn btn-primary btn-sm" type="submit"><i data-lucide="check"></i> Save Review</button></x-slot:actions></x-page-header>
@if($errors->any())<div class="alert alert-danger" style="margin-bottom:16px;"><ul style="margin:0;padding-left:18px;">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>@endif
<div class="grid-12"><div class="col-8">
<div class="card card-pad form-section" style="margin-bottom:16px;"><div class="form-section__title">Review Details</div><div class="form-grid">
<div class="form-field"><label>Tool</label><select class="select" name="tool_id" required><option value="">Select tool...</option>@foreach($tools as $tool)<option value="{{ $tool->id }}" @selected(old('tool_id')==$tool->id)>{{ $tool->name }}</option>@endforeach</select></div>
<div class="form-field"><label>Reviewer</label><select class="select" name="user_id"><option value="">Editorial Team</option>@foreach($reviewers as $reviewer)<option value="{{ $reviewer->id }}" @selected(old('user_id')==$reviewer->id)>{{ $reviewer->name }}</option>@endforeach</select></div>
<div class="form-field col-span-2"><label>Verdict</label><input class="input" name="verdict" value="{{ old('verdict') }}" placeholder="One-line verdict summary"></div>
<div class="form-field col-span-2"><label>Full Review</label><textarea class="input" name="body" rows="9" placeholder="Write the editorial review...">{{ old('body') }}</textarea></div>
<div class="form-field"><label>Pros</label><textarea class="input" name="pros_input" rows="5" placeholder="One per line">{{ old('pros_input') }}</textarea></div>
<div class="form-field"><label>Cons</label><textarea class="input" name="cons_input" rows="5" placeholder="One per line">{{ old('cons_input') }}</textarea></div>
</div></div></div>
<div class="col-4 card card-pad"><div class="form-section__title" style="margin-bottom:12px;">Ratings</div>
@foreach(['quality'=>'Quality','speed'=>'Speed','features'=>'Features','ease_of_use'=>'Ease of Use','value'=>'Value'] as $key=>$label)<div class="form-field" style="margin-bottom:12px;"><label>{{ $label }}</label><input class="input" name="{{ $key }}" type="number" min="1" max="5" step="0.1" value="{{ old($key,4.0) }}"></div>@endforeach
<div class="form-field" style="margin-bottom:12px;"><label>Overall Rating</label><input class="input" name="rating" type="number" min="1" max="5" step="0.1" value="{{ old('rating',4.0) }}" required></div>
<div class="form-field"><label>Status</label><select class="select" name="status"><option value="pending" @selected(old('status')==='pending')>Pending Moderation</option><option value="published" @selected(old('status')==='published')>Published</option></select></div>
</div></div></form>
@endsection
