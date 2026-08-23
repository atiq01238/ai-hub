@extends('layouts.admin')
@section('title','Contact Messages')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/pages/users-community.css') }}">
@endpush

@section('content')
<div class="uc-page">
<x-page-header title="Contact Messages" subtitle="Review and respond to messages submitted through the public Contact Us form." :breadcrumb="['Users & Community','Contact Messages']">
    <x-slot:actions>
        @if(auth()->user()->canAccessModule('Notifications','View'))
            <a href="{{ route('admin.system.notifications') }}" class="btn btn-secondary"><i data-lucide="bell"></i>Notification Center</a>
        @endif
    </x-slot:actions>
</x-page-header>

@if(session('status'))<div class="alert alert-success uc-flash"><i data-lucide="check-circle-2"></i><span>{{ session('status') }}</span></div>@endif

<section class="uc-kpi-grid uc-kpi-grid--five">
@foreach([
['All Messages',$counts['all'],'mail',''],
['New',$counts['new'],'mail-plus','amber'],
['Read',$counts['read'],'mail-open','cyan'],
['Replied',$counts['replied'],'send','green'],
['Closed',$counts['closed'],'circle-check-big','violet']
] as [$label,$value,$icon,$tone])
<article class="uc-kpi uc-kpi--{{ $tone }}"><span><i data-lucide="{{ $icon }}"></i></span><div><small>{{ $label }}</small><strong>{{ number_format($value) }}</strong></div></article>
@endforeach
</section>

@php $base=array_filter(['search'=>request('search'),'topic'=>request('topic')]); @endphp
<nav class="uc-tabs">
<a href="{{ route('admin.contact-messages.index',$base) }}" class="{{ !request('status')?'is-active':'' }}">All {{ $counts['all'] }}</a>
<a href="{{ route('admin.contact-messages.index',$base+['status'=>'new']) }}" class="{{ request('status')==='new'?'is-active':'' }}">New {{ $counts['new'] }}</a>
<a href="{{ route('admin.contact-messages.index',$base+['status'=>'read']) }}" class="{{ request('status')==='read'?'is-active':'' }}">Read {{ $counts['read'] }}</a>
<a href="{{ route('admin.contact-messages.index',$base+['status'=>'replied']) }}" class="{{ request('status')==='replied'?'is-active':'' }}">Replied {{ $counts['replied'] }}</a>
<a href="{{ route('admin.contact-messages.index',$base+['status'=>'closed']) }}" class="{{ request('status')==='closed'?'is-active':'' }}">Closed {{ $counts['closed'] }}</a>
<a href="{{ route('admin.contact-messages.index',$base+['status'=>'spam']) }}" class="{{ request('status')==='spam'?'is-active':'' }}">Spam {{ $counts['spam'] }}</a>
</nav>

<form method="GET" class="card uc-filterbar uc-filterbar--contacts">
<input type="hidden" name="status" value="{{ request('status') }}">
<div class="uc-search"><i data-lucide="search"></i><input class="input" name="search" value="{{ request('search') }}" placeholder="Search sender, email, subject or message..."></div>
<select class="select" name="topic">
<option value="">All topics</option>
@foreach(['general'=>'General','feedback'=>'Feedback','data_correction'=>'Data Correction','partnership'=>'Partnership','press'=>'Press','technical'=>'Technical'] as $value=>$label)
<option value="{{ $value }}" @selected(request('topic')===$value)>{{ $label }}</option>
@endforeach
</select>
<button class="btn btn-secondary"><i data-lucide="filter"></i>Apply</button>
@if(request()->hasAny(['search','topic','status']))<a class="btn btn-ghost" href="{{ route('admin.contact-messages.index') }}">Clear</a>@endif
</form>

<section class="card uc-table-card">
<div class="uc-section-head"><div><span class="uc-eyebrow">Communication Inbox</span><h2>User contact requests</h2><p>New messages stay at the top until an administrator opens them.</p></div><span class="uc-count">{{ number_format($messages->total()) }} records</span></div>
@if($messages->count())
<div class="table-wrap"><table class="data-table uc-table"><thead><tr><th>Sender</th><th>Topic & Subject</th><th>Message</th><th>Received</th><th>Handler</th><th>Status</th><th></th></tr></thead><tbody>
@foreach($messages as $message)
@php
$statusType=match($message->status){'new'=>'warn','replied'=>'pos','spam'=>'neg','closed'=>'neutral',default=>'info'};
@endphp
<tr class="{{ $message->status==='new'?'uc-row--new':'' }}">
<td><div class="uc-user"><span class="uc-avatar">{{ strtoupper(mb_substr($message->name,0,2)) }}</span><div><strong>{{ $message->name }}</strong><small>{{ $message->email }}</small>@if($message->user)<small>Registered user #{{ $message->user->id }}</small>@endif</div></div></td>
<td><div class="uc-contributor"><span class="uc-type-pill">{{ $message->topic_label }}</span><a href="{{ route('admin.contact-messages.show',$message) }}"><strong>{{ $message->subject }}</strong></a></div></td>
<td><span class="uc-contact-preview">{{ \Illuminate\Support\Str::limit($message->message,100) }}</span></td>
<td><span class="uc-muted">{{ $message->created_at->diffForHumans() }}<small>{{ $message->created_at->format('M j, Y · g:i A') }}</small></span></td>
<td><div class="uc-contributor"><strong>{{ $message->handler?->name ?? 'Unassigned' }}</strong><small>{{ $message->handler ? 'Handling conversation' : 'Opens when reviewed' }}</small></div></td>
<td><x-status-badge status="{{ ucfirst($message->status) }}" type="{{ $statusType }}" /></td>
<td><a href="{{ route('admin.contact-messages.show',$message) }}" class="btn btn-secondary btn-sm"><i data-lucide="mail-open"></i>Open</a></td>
</tr>
@endforeach
</tbody></table></div>
<div class="uc-pagination"><span>Showing {{ $messages->firstItem() ?? 0 }}–{{ $messages->lastItem() ?? 0 }} of {{ $messages->total() }}</span><div>{{ $messages->onEachSide(1)->links() }}</div></div>
@else
<div class="uc-empty"><span><i data-lucide="inbox"></i></span><h3>No contact messages found</h3><p>The current inbox has no messages matching these filters.</p></div>
@endif
</section>
</div>
@endsection
