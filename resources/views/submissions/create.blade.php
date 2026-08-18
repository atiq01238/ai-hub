@extends('frontend.layouts.app')
@section('title', 'Suggest an AI Tool, Model or Company — AI Hub')
@section('meta_description', 'Suggest a new AI tool, model or company, or submit a structured data correction to the AI Hub moderation queue.')
@push('styles')<link rel="stylesheet" href="{{ asset('css/frontend/institutional.css') }}">@endpush
@section('content')
<section class="inst-hero inst-hero-compact"><div class="inst-wrap"><span class="inst-eyebrow"><i data-lucide="lightbulb"></i> Community contribution</span><h1>Help improve the <span>AI directory.</span></h1><p>Suggest a tool, model or company—or submit a correction. Every contribution enters a moderation queue before publication.</p></div></section>
<div class="inst-wrap inst-contact-layout">
<section class="inst-contact-form panel">
<div class="inst-contact-head"><span class="inst-mini-title">Structured submission</span><h2>What should we add or correct?</h2><p>Provide enough information for moderators to verify the record against an official or reliable source.</p></div>
@if(session('status'))<div class="inst-alert success"><i data-lucide="circle-check"></i>{{ session('status') }}</div>@endif
@if($errors->any())<div class="inst-alert error"><i data-lucide="circle-alert"></i><div><strong>Please check the form.</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div></div>@endif
<form action="{{ route('submissions.store') }}" method="POST" class="inst-form">@csrf
<div class="inst-hp" aria-hidden="true"><label>Company name<input name="company_name" tabindex="-1" autocomplete="off"></label></div>
<div class="inst-form-grid">
<label><span>Contribution type <b>*</b></span><select name="submission_type" required><option value="tool" @selected(old('submission_type','tool')==='tool')>AI Tool</option><option value="model" @selected(old('submission_type')==='model')>AI Model</option><option value="company" @selected(old('submission_type')==='company')>AI Company</option><option value="correction" @selected(old('submission_type')==='correction')>Data Correction</option></select></label>
<label><span>Name or correction subject <b>*</b></span><input name="tool_name" required maxlength="255" value="{{ old('tool_name') }}" placeholder="e.g. Example AI"></label>
<label><span>Contact email <b>*</b></span><input type="email" name="submitted_by_email" required maxlength="255" value="{{ auth()->user()?->email ?? old('submitted_by_email') }}" @readonly(auth()->check()) placeholder="you@example.com"></label>
<label><span>Official website</span><input type="url" name="website" maxlength="255" value="{{ old('website') }}" placeholder="https://"></label>
<label class="full"><span>Suggested category</span><input name="category" maxlength="100" value="{{ old('category') }}" placeholder="e.g. Coding Assistant"></label>
<label class="full"><span>Contribution details</span><textarea name="description" maxlength="2000" rows="7" placeholder="Tell moderators what should be added or corrected...">{{ old('description') }}</textarea><small>Maximum 2,000 characters. Duplicate pending submissions from the same email are blocked for 24 hours.</small></label>
</div>
<div class="inst-form-footer"><span><i data-lucide="shield-check"></i> Human moderation • duplicate protection • contact used only for clarification</span><button class="inst-btn primary" type="submit">Send for review <i data-lucide="send"></i></button></div>
</form></section>
<aside class="inst-contact-side"><article class="inst-side-card"><i data-lucide="scan-search"></i><h3>Verification helps</h3><p>Official product pages, provider documentation and clear correction details make moderation faster.</p></article><article class="inst-side-card"><i data-lucide="message-square"></i><h3>General feedback?</h3><p>Use the contact form for product feedback, partnerships, press or technical questions.</p><a href="{{ route('contact') }}">Contact AI Hub <i data-lucide="arrow-right"></i></a></article><article class="inst-side-card"><i data-lucide="microscope"></i><h3>How data is handled</h3><p>Read the methodology before submitting a concern about rankings, benchmarks or pricing.</p><a href="{{ route('methodology') }}">Read methodology <i data-lucide="arrow-right"></i></a></article></aside>
</div>
@endsection
