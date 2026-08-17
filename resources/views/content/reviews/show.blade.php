@extends('layouts.admin')
@section('title','Review Detail')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/pages/content.css') }}">
@endpush

@section('content')
@php
$communityMode = $context === 'community';
$backRoute = $communityMode ? 'admin.community.reviews.index' : 'admin.content.reviews.index';
@endphp
<div class="content-page content-review">
<x-page-header :title="$review->verdict ?: 'Review Detail'" :subtitle="($review->tool->name ?? 'Deleted tool').' · '.number_format((float)$review->rating,1).'/5'" :breadcrumb="$communityMode ? ['Users & Community','Review Moderation','Detail'] : ['Content','Reviews','Detail']">
<x-slot:actions><a href="{{ route($backRoute) }}" class="btn btn-secondary"><i data-lucide="arrow-left"></i>Back</a>
@if($review->status==='pending' && auth()->user()->canAccessModule('Reviews','Publish'))<form method="POST" action="{{ route('admin.content.reviews.approve',$review->id) }}">@csrf<button class="btn btn-primary"><i data-lucide="badge-check"></i>Approve</button></form>@endif
</x-slot:actions>
</x-page-header>

@if(session('status'))<div class="alert alert-success content-flash"><i data-lucide="check-circle-2"></i><span>{{ session('status') }}</span></div>@endif
@if($errors->any())<div class="alert alert-danger content-flash"><i data-lucide="circle-alert"></i><span>{{ $errors->first() }}</span></div>@endif

<section class="card content-review__hero">
<div class="content-review__identity"><span class="content-review__tool"><i data-lucide="wrench"></i></span><div><span class="content-eyebrow">{{ ucfirst($review->review_type) }} Review</span><h1>{{ $review->tool->name ?? 'Deleted tool' }}</h1><p>Reviewed by {{ $review->user?->name ?? ($review->review_type==='editorial'?'Editorial Team':'Deleted user') }}</p></div></div>
<div class="content-review__rating"><strong>{{ number_format((float)$review->rating,1) }}</strong><span>out of 5</span></div>
</section>

<div class="content-review__layout">
<main class="content-review__main">
<section class="card content-review__body">@if($review->verdict)<h2>{{ $review->verdict }}</h2>@endif<p>{{ $review->body ?: 'No written review — star rating only.' }}</p></section>

@if(!empty($review->pros) || !empty($review->cons))
<div class="content-procon-grid">
<section class="card content-procon is-pro"><span class="content-eyebrow">Pros</span><ul>@forelse($review->pros ?? [] as $pro)<li>{{ $pro }}</li>@empty<li>None listed</li>@endforelse</ul></section>
<section class="card content-procon is-con"><span class="content-eyebrow">Cons</span><ul>@forelse($review->cons ?? [] as $con)<li>{{ $con }}</li>@empty<li>None listed</li>@endforelse</ul></section>
</div>
@endif

@if(!empty($review->rating_breakdown))
<section class="card content-panel">
<div class="content-section-head"><div><span class="content-eyebrow">Scorecard</span><h2>Rating breakdown</h2></div><i data-lucide="chart-no-axes-column"></i></div>
<div class="content-rating-grid">@foreach($review->rating_breakdown as $label=>$value)<div><span>{{ ucfirst(str_replace('_',' ',$label)) }}</span><strong>{{ number_format((float)$value,1) }}</strong><div><span style="width:{{ min(100,max(0,((float)$value/5)*100)) }}%"></span></div></div>@endforeach</div>
</section>
@endif
</main>

<aside class="content-review__aside">
<section class="card content-facts"><span class="content-eyebrow">Moderation Facts</span><dl><div><dt>Status</dt><dd>{{ ucfirst($review->status) }}</dd></div><div><dt>Type</dt><dd>{{ ucfirst($review->review_type) }}</dd></div><div><dt>Reports</dt><dd>{{ $review->reports_count }}</dd></div><div><dt>Moderator</dt><dd>{{ $review->moderator?->name ?? '—' }}</dd></div></dl>@if($review->moderation_note)<div class="content-note"><strong>Moderation note</strong><p>{{ $review->moderation_note }}</p></div>@endif</section>

@if($review->status!=='flagged' && auth()->user()->canAccessModule('Reviews','Edit'))
<section class="card content-action-card"><span class="content-eyebrow">Moderation Action</span><h3>Flag review</h3><form method="POST" action="{{ route('admin.content.reviews.flag',$review->id) }}">@csrf<textarea class="textarea" name="moderation_note" rows="4" required placeholder="Reason for flagging..."></textarea><button class="btn btn-secondary" type="submit"><i data-lucide="flag"></i>Flag & Unpublish</button></form></section>
@endif

@if(auth()->user()->canAccessModule('Reviews','Delete'))
<section class="card content-action-card content-action-card--danger"><span class="content-eyebrow">Recovery-safe removal</span><p>The review is soft-deleted and can remain recoverable in storage.</p><form method="POST" action="{{ route('admin.content.reviews.destroy',$review->id) }}" onsubmit="return confirm('Move this review to the recovery bin?')">@csrf @method('DELETE')<input type="hidden" name="context" value="{{ $communityMode ? 'community' : 'content' }}"><button class="btn btn-danger"><i data-lucide="trash-2"></i>Remove Review</button></form></section>
@endif
</aside>
</div>
</div>
@endsection
