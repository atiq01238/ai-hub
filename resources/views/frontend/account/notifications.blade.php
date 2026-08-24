@extends('frontend.layouts.app')
@section('title','Notifications — My AI Orbit')
@push('styles')<link rel="stylesheet" href="{{ asset('css/frontend/account.css') }}">@endpush
@section('content')
<section class="account-page"><div class="account-shell">@include('frontend.account._sidebar')<div class="account-main">
<header class="account-subhead"><div><span class="account-kicker"><i data-lucide="bell"></i> NOTIFICATIONS</span><h1>Your notification center</h1><p>Review moderation, replies, helpful votes and updates from AI products you follow.</p></div>@if($unreadCount)<form method="POST" action="{{ route('account.notifications.read-all') }}">@csrf<button class="btn btn-secondary"><i data-lucide="check-check"></i> Mark all read</button></form>@endif</header>
@if(session('status'))<div class="account-notice">{{ session('status') }}</div>@endif
<div class="account-notification-list">
@forelse($notifications as $notice)
<article class="account-notification {{ $notice->read_at ? '' : 'is-unread' }}"><span class="account-notification-icon"><i data-lucide="{{ $notice->icon ?: 'bell' }}"></i></span><div><div class="review-top"><h2>{{ $notice->title }}</h2>@unless($notice->read_at)<b class="status pending">New</b>@endunless</div>@if($notice->description)<p>{{ $notice->description }}</p>@endif<small>{{ $notice->created_at->diffForHumans() }}</small><div class="account-notification-actions"><a href="{{ route('account.notifications.open',$notice) }}">Open <i data-lucide="arrow-up-right"></i></a><form method="POST" action="{{ route('account.notifications.destroy',$notice) }}">@csrf @method('DELETE')<button>Delete</button></form></div></div></article>
@empty<div class="account-empty big"><i data-lucide="bell-off"></i><strong>No notifications yet.</strong><span>Approvals, replies and followed AI updates will appear here.</span></div>@endforelse
</div><div class="account-pagination">{{ $notifications->links() }}</div></div></div></section>
@endsection
