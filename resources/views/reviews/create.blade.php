<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Review {{ $reviewable->name }} · AI Hub</title>
<link rel="stylesheet" href="{{ asset('css/pages/final-polish.css') }}">
<style>
.fp-review-context{display:inline-flex;gap:6px;align-items:center;margin-top:8px;padding:5px 8px;border:1px solid #313a61;border-radius:999px;color:#a9b2d0;font-size:11px}
.fp-rating-half{display:flex;gap:8px;flex-wrap:wrap}.fp-rating-half label{display:flex;align-items:center;gap:5px;padding:8px 10px;border:1px solid #313a61;border-radius:8px;cursor:pointer}.fp-rating-half input{accent-color:#7b5cff}
</style>
</head>
<body class="fp-public-body">
<main class="fp-public-shell fp-public-shell--review">
<a href="{{ route('home') }}" class="fp-public-brand"><span>AI</span><div><strong>AI Hub</strong><small>Community Reviews</small></div></a>

<section class="fp-public-card">
<header class="fp-review-head">
    <span class="fp-review-avatar">{{ mb_strtoupper(mb_substr($reviewable->name,0,2)) }}</span>
    <div>
        <span class="fp-public-eyebrow">Community Experience</span>
        <h1>{{ $existingReview ? 'Update your review' : 'Review '.$reviewable->name }}</h1>
        <p>Share a specific, respectful first-hand experience to help other people make a better AI decision.</p>
        <span class="fp-review-context">{{ $type === 'tool' ? 'AI Tool' : 'AI Model' }} · {{ $reviewable->name }}</span>
        @if($existingReview)
            <span class="fp-public-status">Current moderation state: {{ ucfirst($existingReview->status) }}</span>
        @endif
    </div>
</header>

@if(session('status'))<div class="fp-public-alert is-success">{{ session('status') }}</div>@endif
@if($errors->any())<div class="fp-public-alert is-error">{{ $errors->first() }}</div>@endif

<form
    action="{{ $type === 'tool'
        ? url('/tools/'.$reviewable->getRouteKey().'/review')
        : url('/models/'.$reviewable->getRouteKey().'/review') }}"
    method="POST"
    class="fp-public-form"
>
@csrf

<div class="fp-public-field">
    <label>Your rating <b>*</b></label>
    <div class="fp-rating-half" aria-label="Rating from 1 to 5">
        @foreach([5,4.5,4,3.5,3,2.5,2,1.5,1] as $score)
        <label>
            <input
                type="radio"
                name="rating"
                value="{{ $score }}"
                required
                @checked((float)old('rating',$existingReview?->rating)===(float)$score)
            >
            <span>★ {{ number_format($score,1) }}</span>
        </label>
        @endforeach
    </div>
</div>

<div class="fp-public-field">
    <label for="body">Your experience <span>(optional)</span></label>
    <textarea id="body" name="body" maxlength="2000" rows="7" placeholder="What worked well? What could be better? Give useful context.">{{ old('body',$existingReview?->body) }}</textarea>
    <small>Community reviews are moderated before publication. Editing an approved review sends it back to the moderation queue.</small>
</div>

<div class="fp-public-actions">
    <a href="{{ $type === 'tool'
        ? url('/ai-tools/'.$reviewable->slug)
        : url('/ai-models/'.$reviewable->slug) }}">← Back to {{ $reviewable->name }}</a>
    <button type="submit">{{ $existingReview ? 'Update Review' : 'Submit Review' }}</button>
</div>
</form>
</section>

<div class="fp-public-trust"><span>Moderated</span><span>1–5 rating scale</span><span>2,000 character limit</span></div>
</main>
</body>
</html>
