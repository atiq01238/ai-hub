@extends('layouts.admin')
@section('title','Contact Message #' . $contactMessage->id)

@push('styles')
<link rel="stylesheet" href="{{ asset('css/pages/users-community.css') }}">
@endpush

@section('content')
@php
$statusType=match($contactMessage->status){'new'=>'warn','replied'=>'pos','spam'=>'neg','closed'=>'neutral',default=>'info'};
@endphp
<div class="uc-page uc-case">
<x-page-header :title="'Contact Message #'.$contactMessage->id" :subtitle="$contactMessage->topic_label.' · received '.$contactMessage->created_at->diffForHumans()" :breadcrumb="['Users & Community','Contact Messages','#'.$contactMessage->id]">
<x-slot:actions>
<a href="{{ route('admin.contact-messages.index') }}" class="btn btn-secondary"><i data-lucide="arrow-left"></i>Inbox</a>
<a href="mailto:{{ $contactMessage->email }}" class="btn btn-secondary"><i data-lucide="mail"></i>Open Mail App</a>
</x-slot:actions>
</x-page-header>

@if(session('status'))<div class="alert alert-success uc-flash"><i data-lucide="check-circle-2"></i><span>{{ session('status') }}</span></div>@endif
@if($errors->any())<div class="alert alert-danger uc-flash"><i data-lucide="circle-alert"></i><span>{{ $errors->first() }}</span></div>@endif

<section class="card uc-case__hero uc-case__hero--contact">
<div><div class="uc-case__badges"><span class="uc-type-pill">{{ $contactMessage->topic_label }}</span><x-status-badge status="{{ ucfirst($contactMessage->status) }}" type="{{ $statusType }}" /></div><h1>{{ $contactMessage->subject }}</h1><p>Message from {{ $contactMessage->name }} · {{ $contactMessage->email }}</p></div>
<div class="uc-case__signal"><span class="uc-eyebrow">Replies</span><strong>{{ number_format($contactMessage->replies->count()) }}</strong><small>Received {{ $contactMessage->created_at->format('M j, Y') }}</small></div>
</section>

<div class="uc-case__layout">
<main class="uc-case__main">
<section class="card uc-panel"><div class="uc-section-head"><div><span class="uc-eyebrow">Original Message</span><h2>{{ $contactMessage->subject }}</h2><p>Submitted through the public Contact Us form.</p></div><i data-lucide="message-square-text"></i></div>
<dl class="uc-data-grid"><div><dt>Sender</dt><dd>{{ $contactMessage->name }}</dd></div><div><dt>Email</dt><dd><a href="mailto:{{ $contactMessage->email }}">{{ $contactMessage->email }} <i data-lucide="external-link"></i></a></dd></div><div><dt>Topic</dt><dd>{{ $contactMessage->topic_label }}</dd></div><div><dt>Received</dt><dd>{{ $contactMessage->created_at->format('M j, Y · g:i A') }}</dd></div></dl>
<div class="uc-case__description">{{ $contactMessage->message }}</div>
</section>

<section class="card uc-panel"><div class="uc-section-head"><div><span class="uc-eyebrow">Conversation History</span><h2>Administrator replies</h2><p>Replies sent from AI Orbit are retained here for an audit trail.</p></div><i data-lucide="messages-square"></i></div>
@if($contactMessage->replies->count())
<div class="uc-contact-thread">
@foreach($contactMessage->replies as $reply)
<article><div class="uc-contact-thread__meta"><span><i data-lucide="send"></i>{{ $reply->admin?->name ?? 'Administrator' }}</span><time>{{ $reply->created_at->format('M j, Y · g:i A') }}</time></div><div>{{ $reply->body }}</div></article>
@endforeach
</div>
@else
<div class="uc-empty uc-empty--small"><span><i data-lucide="message-circle"></i></span><h3>No replies yet</h3><p>This conversation has not received an administrator reply.</p></div>
@endif
</section>

@if($contactMessage->admin_notes)
<section class="card uc-panel"><div class="uc-section-head"><div><span class="uc-eyebrow">Internal Notes</span><h2>Administrator context</h2></div><i data-lucide="notebook-pen"></i></div><div class="uc-note">{{ $contactMessage->admin_notes }}</div></section>
@endif
</main>

<aside class="uc-case__aside">
<section class="card uc-contributor-card"><span class="uc-eyebrow">Sender</span><div class="uc-contributor-card__profile"><span><i data-lucide="user-round"></i></span><div><strong>{{ $contactMessage->name }}</strong><small>{{ $contactMessage->email }}</small></div></div>
@if($contactMessage->user)<a href="{{ route('admin.users.show',$contactMessage->user->id) }}" class="btn btn-secondary"><i data-lucide="user-round-search"></i>View User Profile</a>@else<p class="uc-muted">Guest contact · no registered account linked.</p>@endif
<div class="uc-divider"></div><div class="uc-contact-meta"><span>Handled by</span><strong>{{ $contactMessage->handler?->name ?? 'Unassigned' }}</strong><span>Read</span><strong>{{ $contactMessage->read_at?->format('M j, g:i A') ?? 'Not recorded' }}</strong>@if($contactMessage->replied_at)<span>Last replied</span><strong>{{ $contactMessage->replied_at->format('M j, g:i A') }}</strong>@endif</div>
</section>

@if(auth()->user()->canAccessModule('Users','Edit'))
<section class="card uc-moderation">
<span class="uc-eyebrow">Reply by Email</span><h3>Respond to {{ $contactMessage->name }}</h3>
<p class="uc-contact-help">The reply is queued through your configured Laravel mailer and retained in this conversation.</p>
<form method="POST" action="{{ route('admin.contact-messages.reply',$contactMessage) }}">@csrf
<label><span>Reply <b>*</b></span><textarea class="textarea" name="reply_message" rows="7" required placeholder="Write a clear response to the user...">{{ old('reply_message') }}</textarea></label>
<button class="btn btn-primary" type="submit"><i data-lucide="send"></i>Queue Email Reply</button>
</form>
</section>

<section class="card uc-moderation">
<span class="uc-eyebrow">Inbox Workflow</span><h3>Status & internal notes</h3>
<form method="POST" action="{{ route('admin.contact-messages.status',$contactMessage) }}">@csrf @method('PATCH')
<label><span>Status</span><select class="select" name="status" required>@foreach(['new'=>'New','read'=>'Read','replied'=>'Replied','closed'=>'Closed','spam'=>'Spam'] as $value=>$label)<option value="{{ $value }}" @selected($contactMessage->status===$value)>{{ $label }}</option>@endforeach</select></label>
<label><span>Internal note</span><textarea class="textarea" name="admin_notes" rows="5" placeholder="Private notes for administrators only...">{{ old('admin_notes',$contactMessage->admin_notes) }}</textarea><small>Never included in the email sent to the user.</small></label>
<button class="btn btn-secondary" type="submit"><i data-lucide="save"></i>Save Workflow</button>
</form>
</section>
@else
<section class="card uc-facts"><span class="uc-eyebrow">Read-only Access</span><p class="uc-muted">You can inspect this message but cannot reply or change its workflow state.</p></section>
@endif
</aside>
</div>
</div>
@endsection
