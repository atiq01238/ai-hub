<!DOCTYPE html>
<html lang="en">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Review {{ $tool->name }} · AI Hub</title><link rel="stylesheet" href="{{ asset('css/pages/final-polish.css') }}"></head>
<body class="fp-public-body">
<main class="fp-public-shell fp-public-shell--review">
<a href="{{ route('home') }}" class="fp-public-brand"><span>AI</span><div><strong>AI Hub</strong><small>Community Reviews</small></div></a>
<section class="fp-public-card">
<header class="fp-review-head"><span class="fp-review-avatar">{{ mb_strtoupper(mb_substr($tool->name,0,2)) }}</span><div><span class="fp-public-eyebrow">Community Experience</span><h1>{{ $existingReview?'Update your review':'Review '.$tool->name }}</h1><p>Share a specific, respectful experience to help other people evaluate this tool.</p>@if($existingReview)<span class="fp-public-status">Current moderation state: {{ ucfirst($existingReview->status) }}</span>@endif</div></header>
@if(session('status'))<div class="fp-public-alert is-success">{{ session('status') }}</div>@endif
@if($errors->any())<div class="fp-public-alert is-error">{{ $errors->first() }}</div>@endif
<form action="{{ route('reviews.store',$tool->id) }}" method="POST" class="fp-public-form">@csrf
<div class="fp-public-field"><label>Your rating <b>*</b></label><div class="fp-rating" aria-label="Rating from 1 to 5 stars">@for($star=5;$star>=1;$star--)<input type="radio" id="star{{ $star }}" name="rating" value="{{ $star }}" required @checked((float)old('rating',$existingReview?->rating)===(float)$star)><label for="star{{ $star }}" title="{{ $star }} stars">★</label>@endfor</div></div>
<div class="fp-public-field"><label for="body">Your experience <span>(optional)</span></label><textarea id="body" name="body" maxlength="2000" rows="7" placeholder="What worked well? What could be better?">{{ old('body',$existingReview?->body) }}</textarea><small>Reviews enter moderation before publication. Editing an approved review returns it to the pending queue.</small></div>
<div class="fp-public-actions"><a href="{{ route('home') }}">← Return to AI Hub</a><button type="submit">{{ $existingReview?'Update Review':'Submit Review' }}</button></div>
</form></section>
<div class="fp-public-trust"><span>Moderated</span><span>1–5 rating scale</span><span>2,000 character limit</span></div>
</main></body></html>
