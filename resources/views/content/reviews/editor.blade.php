@extends('layouts.admin')
@section('title', 'Review Editor')

@section('content')

<x-page-header title="AI Review Editor" :breadcrumb="['Content', 'Reviews', 'Editor']">
    <x-slot:actions><button class="btn btn-primary btn-sm"><i data-lucide="check"></i> Publish Review</button></x-slot:actions>
</x-page-header>

<div class="grid-12">
<div class="col-8">
    <div class="card card-pad form-section" style="margin-bottom:16px;">
        <div class="form-section__title">Review Details</div>
        <div class="form-grid">
            <div class="form-field"><label>Tool</label><select class="select"><option>ChatGPT</option><option>Claude</option><option>Midjourney</option></select></div>
            <div class="form-field"><label>Reviewer</label><input class="input" placeholder="Editorial Team"></div>
            <div class="form-field col-span-2"><label>Verdict</label><input class="input" placeholder="One-line verdict summary"></div>
            <div class="form-field col-span-2"><label>Pros</label><textarea class="input" rows="3" placeholder="One per line"></textarea></div>
            <div class="form-field col-span-2"><label>Cons</label><textarea class="input" rows="3" placeholder="One per line"></textarea></div>
            <div class="form-field col-span-2"><label>Screenshots</label><div class="input" style="color:var(--text-lo);">Upload screenshots</div></div>
        </div>
    </div>
</div>
<div class="col-4 card card-pad">
    <div class="form-section__title" style="margin-bottom:12px;">Ratings</div>
    @foreach(['Quality','Speed','Features','Ease of Use','Value','Overall'] as $r)
    <div class="form-field" style="margin-bottom:12px;"><label>{{ $r }}</label><input class="input" type="number" min="1" max="5" step="0.1" value="4.5"></div>
    @endforeach
    <div class="form-field"><label>Status</label><select class="select"><option>Draft</option><option>Pending Moderation</option><option>Published</option></select></div>
</div>
</div>
@endsection
