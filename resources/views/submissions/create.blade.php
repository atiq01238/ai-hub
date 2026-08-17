<!DOCTYPE html>
<html lang="en">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Contribute to AI Hub</title><link rel="stylesheet" href="{{ asset('css/pages/final-polish.css') }}"></head>
<body class="fp-public-body">
<main class="fp-public-shell">
<a href="{{ route('home') }}" class="fp-public-brand"><span>AI</span><div><strong>AI Hub</strong><small>Research & Intelligence Platform</small></div></a>
<section class="fp-public-card fp-contribute-card">
<header><span class="fp-public-eyebrow">Community Contribution</span><h1>Help improve the AI directory.</h1><p>Suggest a tool, model or company—or submit a correction. Every contribution enters a human moderation queue before publication.</p></header>
@if(session('status'))<div class="fp-public-alert is-success">{{ session('status') }}</div>@endif
@if($errors->any())<div class="fp-public-alert is-error"><strong>Please fix the following:</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
<form action="{{ route('submissions.store') }}" method="POST" class="fp-public-form">@csrf
<div class="fp-hp" aria-hidden="true"><label>Company name<input name="company_name" tabindex="-1" autocomplete="off"></label></div>
<div class="fp-public-grid">
<div class="fp-public-field"><label for="submission_type">Contribution type <b>*</b></label><select id="submission_type" name="submission_type" required><option value="tool" @selected(old('submission_type','tool')==='tool')>AI Tool</option><option value="model" @selected(old('submission_type')==='model')>AI Model</option><option value="company" @selected(old('submission_type')==='company')>AI Company</option><option value="correction" @selected(old('submission_type')==='correction')>Data Correction</option></select></div>
<div class="fp-public-field"><label for="tool_name">Name or correction subject <b>*</b></label><input id="tool_name" name="tool_name" required maxlength="255" value="{{ old('tool_name') }}" placeholder="e.g. Example AI"></div>
<div class="fp-public-field"><label for="submitted_by_email">Contact email <b>*</b></label><input type="email" id="submitted_by_email" name="submitted_by_email" required maxlength="255" value="{{ auth()->user()?->email??old('submitted_by_email') }}" @readonly(auth()->check())><small>Used only if moderators need clarification.</small></div>
<div class="fp-public-field"><label for="website">Official website <span>(optional)</span></label><input type="url" id="website" name="website" maxlength="255" placeholder="https://" value="{{ old('website') }}"></div>
<div class="fp-public-field fp-public-field--full"><label for="category">Suggested category <span>(optional)</span></label><input id="category" name="category" maxlength="100" value="{{ old('category') }}" placeholder="e.g. Coding Assistant"></div>
<div class="fp-public-field fp-public-field--full"><label for="description">Contribution details <span>(optional)</span></label><textarea id="description" name="description" maxlength="2000" rows="7" placeholder="Tell moderators what should be added or corrected...">{{ old('description') }}</textarea><small>Maximum 2,000 characters. Duplicate pending submissions from the same email are blocked for 24 hours.</small></div>
</div>
<div class="fp-public-actions"><a href="{{ route('home') }}">← Return to AI Hub</a><button type="submit">Send for Review</button></div>
</form></section>
<div class="fp-public-trust"><span>Human moderated</span><span>Duplicate protection</span><span>Contact used for clarification only</span></div>
</main></body></html>
