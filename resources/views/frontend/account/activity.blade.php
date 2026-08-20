@extends('frontend.layouts.app')
@section('title','Recent Activity — My AI Hub')
@push('styles')<link rel="stylesheet" href="{{ asset('css/frontend/account.css') }}">@endpush
@section('content')
<section class="account-page"><div class="account-shell">
@include('frontend.account._sidebar')
<div class="account-main">
    <header class="account-subhead"><div><span class="account-kicker"><i data-lucide="activity"></i> ACTIVITY</span><h1>Your recent AI Hub activity</h1><p>A private timeline of saved research, ratings, follows, comparisons and Test Lab exploration.</p></div></header>
    <section class="account-panel timeline-panel">
        <div class="activity-timeline">
            @forelse($activity as $item)
                <a href="{{ $item['url'] }}" class="timeline-row">
                    <span class="timeline-dot {{ $item['kind'] }}"><i data-lucide="{{ $item['icon'] }}"></i></span>
                    <div><strong>{{ $item['title'] }}</strong><span>{{ $item['subtitle'] }}</span></div>
                    <time title="{{ $item['at']->format('M j, Y g:i A') }}">{{ $item['at']->diffForHumans() }}</time>
                </a>
            @empty
                <div class="account-empty big"><i data-lucide="activity"></i><strong>No activity yet.</strong><span>Start exploring AI Hub and your private timeline will build automatically.</span></div>
            @endforelse
        </div>
    </section>
</div></div></section>
@endsection
