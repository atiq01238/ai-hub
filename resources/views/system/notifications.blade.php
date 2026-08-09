@extends('layouts.admin')
@section('title', 'Notifications')

@section('content')

<x-page-header title="Notification Center" subtitle="{{ $unreadCount }} unread" :breadcrumb="['System', 'Notifications']">
    <x-slot:actions>
        <form action="{{ route('admin.system.notifications.mark-all-read') }}" method="POST">
            @csrf
            <button type="submit" class="btn btn-secondary btn-sm"><i data-lucide="check-check"></i> Mark All Read</button>
        </form>
    </x-slot:actions>
</x-page-header>

@if (session('status'))
    <div class="alert alert-success" style="margin-bottom:16px;">{{ session('status') }}</div>
@endif

<div class="card">
    @forelse ($notifications as $n)
    <div class="flex items-center gap-12" style="padding:14px 20px; border-bottom:1px solid var(--border-soft); background:{{ $n->read_at ? 'transparent' : 'rgba(91,127,255,.04)' }};">
        <div class="kpi-icon" style="background:var(--{{ $n->tone }}-bg); color:var(--{{ $n->tone }});"><i data-lucide="{{ $n->icon }}"></i></div>
        <div style="flex:1;">
            <div style="font-weight:650; font-size:13.5px;">{{ $n->title }} @if(!$n->read_at)<span class="dot-indicator" style="background:var(--brand-3); margin-left:6px;"></span>@endif</div>
            @if ($n->description)<div class="text-sub" style="font-size:12.5px;">{{ $n->description }}</div>@endif
        </div>
        <div class="cell-sub">{{ $n->created_at->diffForHumans() }}</div>
        <div class="flex gap-8">
            @if (!$n->read_at)
            <form action="{{ route('admin.system.notifications.mark-read', $n->id) }}" method="POST">
                @csrf
                <button type="submit" class="icon-btn" style="width:28px;height:28px;" title="Mark read"><i data-lucide="check" style="width:14px;height:14px;"></i></button>
            </form>
            @endif
            <form action="{{ route('admin.system.notifications.destroy', $n->id) }}" method="POST">
                @csrf
                @method('DELETE')
                <button type="submit" class="icon-btn" style="width:28px;height:28px;"><i data-lucide="trash-2" style="width:14px;height:14px;"></i></button>
            </form>
        </div>
    </div>
    @empty
    <div class="text-sub" style="text-align:center; padding:40px;">No notifications yet. These appear automatically when a tool is suggested, a review needs approval, or a price changes.</div>
    @endforelse
</div>
<div class="pager" style="margin-top:12px;">{{ $notifications->links() }}</div>
@endsection
