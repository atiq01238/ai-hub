@extends('layouts.admin')
@section('title', 'Notifications')

@section('content')

<x-page-header title="Notification Center" subtitle="12 unread" :breadcrumb="['System', 'Notifications']">
    <x-slot:actions>
        <button class="btn btn-secondary btn-sm"><i data-lucide="check-check"></i> Mark All Read</button>
        <button class="btn btn-secondary btn-sm"><i data-lucide="settings"></i> Notification Rules</button>
    </x-slot:actions>
</x-page-header>

<div class="filter-bar">
    <span class="chip is-active">All</span>
    <span class="chip">News</span>
    <span class="chip">Pricing</span>
    <span class="chip">Security</span>
    <span class="chip">System</span>
    <span class="chip">Submissions</span>
</div>

@php
$notifs = [
    ['icon'=>'zap','tone'=>'neg','title'=>'New breaking AI news detected','desc'=>'OpenAI announces GPT-5.2 Turbo','time'=>'2 min ago','read'=>false],
    ['icon'=>'tag','tone'=>'warn','title'=>'Price update detected','desc'=>'ChatGPT Plus increased from $20 to $22','time'=>'18 min ago','read'=>false],
    ['icon'=>'lightbulb','tone'=>'info','title'=>'New tool submission','desc'=>'"NarrateAI" awaiting review','time'=>'1 hr ago','read'=>false],
    ['icon'=>'star','tone'=>'info','title'=>'Review awaiting approval','desc'=>'Midjourney review by j.reviewer_92','time'=>'1 hr ago','read'=>true],
    ['icon'=>'link-2-off','tone'=>'neg','title'=>'Broken tool link detected','desc'=>'PromptForge website returned a 404','time'=>'2 hr ago','read'=>true],
    ['icon'=>'bar-chart-3','tone'=>'warn','title'=>'Benchmark requires update','desc'=>'MMLU Pro scores stale for 3 models','time'=>'3 hr ago','read'=>true],
    ['icon'=>'server-crash','tone'=>'neg','title'=>'API source failure','desc'=>'"TechCrunch AI" feed failed to fetch 3 times','time'=>'4 hr ago','read'=>true],
    ['icon'=>'shield-alert','tone'=>'neg','title'=>'Security alert','desc'=>'New device login from Karachi, PK','time'=>'6 hr ago','read'=>true],
];
@endphp

<div class="card">
    @foreach($notifs as $n)
    <div class="flex items-center gap-12" style="padding:14px 20px; border-bottom:1px solid var(--border-soft); background:{{ $n['read'] ? 'transparent' : 'rgba(91,127,255,.04)' }};">
        <div class="kpi-icon" style="background:var(--{{ $n['tone'] }}-bg); color:var(--{{ $n['tone'] }});"><i data-lucide="{{ $n['icon'] }}"></i></div>
        <div style="flex:1;">
            <div style="font-weight:650; font-size:13.5px;">{{ $n['title'] }} @if(!$n['read'])<span class="dot-indicator" style="background:var(--brand-3); margin-left:6px;"></span>@endif</div>
            <div class="text-sub" style="font-size:12.5px;">{{ $n['desc'] }}</div>
        </div>
        <div class="cell-sub">{{ $n['time'] }}</div>
        <div class="flex gap-8">
            <button class="icon-btn" style="width:28px;height:28px;"><i data-lucide="external-link" style="width:14px;height:14px;"></i></button>
            <button class="icon-btn" style="width:28px;height:28px;"><i data-lucide="trash-2" style="width:14px;height:14px;"></i></button>
        </div>
    </div>
    @endforeach
</div>
@endsection
