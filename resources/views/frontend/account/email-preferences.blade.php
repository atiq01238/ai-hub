@extends('frontend.layouts.app')
@section('title','Email Preferences — My AI Hub')
@push('styles')<link rel="stylesheet" href="{{ asset('css/frontend/account.css') }}">@endpush
@section('content')
<section class="account-page"><div class="account-shell">
@include('frontend.account._sidebar')
<div class="account-main">
<header class="account-subhead"><div><span class="account-kicker"><i data-lucide="mail"></i> EMAIL INTELLIGENCE</span><h1>Email preferences</h1><p>Choose which AI Hub updates should reach your inbox. Transactional security emails are not affected.</p></div></header>
@if(session('status'))<div class="account-success"><i data-lucide="circle-check"></i>{{ session('status') }}</div>@endif
<form class="email-pref-form" method="POST" action="{{ route('account.email-preferences.update') }}">@csrf @method('PATCH')
<section class="account-panel email-pref-master"><div><h2>AI Hub intelligence emails</h2><p>Master switch for news, discovery, pricing, benchmark and digest emails.</p></div><label class="email-switch"><input type="checkbox" name="email_enabled" value="1" @checked($preference->email_enabled)><span></span></label></section>
<div class="email-pref-grid">
@php $items=[
['breaking_news','zap','Breaking AI News','High-importance and breaking AI developments.'],
['new_models','brain-circuit','New AI Models','Major models when they become active in the catalog.'],
['new_tools','sparkles','New AI Tools','Newly published AI tools and products.'],
['followed_entities','bell-ring','Followed Updates','Updates from companies, tools and models you follow.'],
['benchmark_updates','bar-chart-3','Benchmark Updates','New verified benchmark results.'],
['price_changes','tag','Price Changes','Verified pricing plan changes and updates.'],
['weekly_digest','newspaper','Weekly AI Digest','A compact weekly summary of important AI Hub intelligence.'],
]; @endphp
@foreach($items as [$field,$icon,$title,$text])<label class="account-panel email-pref-card"><span class="email-pref-icon"><i data-lucide="{{ $icon }}"></i></span><span class="email-pref-copy"><b>{{ $title }}</b><small>{{ $text }}</small></span><span class="email-switch"><input type="checkbox" name="{{ $field }}" value="1" @checked($preference->{$field})><span></span></span></label>@endforeach
</div>
<button class="email-pref-save" type="submit"><i data-lucide="save"></i>Save email preferences</button>
</form>
</div></div></section>
@endsection
