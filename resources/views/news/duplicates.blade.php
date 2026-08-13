@extends('layouts.admin')
@section('title', 'Duplicate News Detection')

@section('content')

<x-page-header title="Duplicate News Detection" subtitle="AI-matched near-identical stories across sources" :breadcrumb="['AI Intelligence', 'Duplicate Detection']">
    <x-slot:actions>
        <a href="{{ url('/admin/news') }}" class="btn btn-secondary btn-sm"><i data-lucide="arrow-left"></i> Back to News Feed</a>
    </x-slot:actions>
</x-page-header>

<div class="kpi-grid" style="grid-template-columns:repeat(4,1fr);">
    <x-kpi-card icon="copy" label="Total Duplicates Found" value="214" />
    <x-kpi-card icon="clock" label="Pending Review" value="6" />
    <x-kpi-card icon="git-merge" label="Merged" value="182" />
    <x-kpi-card icon="eye-off" label="Ignored" value="26" />
</div>

@php
$groups = [
    [
        'main' => 'OpenAI announces GPT-5.2 Turbo with native agent orchestration',
        'similar' => [
            ['h'=>'OpenAI unveils GPT-5.2 Turbo, a leap for AI agents','sim'=>94,'src'=>'TechCrunch','time'=>'10 min ago'],
            ['h'=>'GPT-5.2 Turbo brings agent orchestration to ChatGPT','sim'=>91,'src'=>'The Verge','time'=>'14 min ago'],
            ['h'=>'OpenAI\'s newest model focuses on multi-agent workflows','sim'=>88,'src'=>'VentureBeat','time'=>'22 min ago'],
        ],
        'companies'=>'OpenAI', 'tools'=>'ChatGPT',
    ],
    [
        'main' => 'Midjourney cuts Pro plan pricing by 20% amid new competition',
        'similar' => [
            ['h'=>'Midjourney slashes subscription prices across all tiers','sim'=>90,'src'=>'The Verge','time'=>'1 hr ago'],
            ['h'=>'Midjourney Pro now $48/month, down from $60','sim'=>86,'src'=>'PetaPixel','time'=>'2 hr ago'],
        ],
        'companies'=>'Midjourney', 'tools'=>'Midjourney v7',
    ],
];
@endphp

<div style="display:flex; flex-direction:column; gap:16px;">
@foreach($groups as $g)
<div class="card card-pad">
    <div class="flex items-center gap-8" style="margin-bottom:10px;">
        <span class="badge badge-warn">Possible Duplicate Story</span>
        <span class="cell-sub">{{ count($g['similar']) }} similar articles found</span>
    </div>
    <div style="font-size:15px; font-weight:650; margin-bottom:12px;">{{ $g['main'] }}</div>

    <div style="display:flex; flex-direction:column; gap:8px;">
        @foreach($g['similar'] as $s)
        <div class="flex items-center justify-between" style="background:var(--surface-2); border-radius:var(--radius-sm); padding:10px 14px;">
            <div class="flex items-center gap-12">
                <input type="radio" name="primary-{{ $loop->parent->index }}" {{ $loop->first ? 'checked' : '' }}>
                <div>
                    <div style="font-size:13px; font-weight:600;">{{ $s['h'] }}</div>
                    <div class="cell-sub">{{ $s['src'] }} · {{ $s['time'] }}</div>
                </div>
            </div>
            <span class="badge {{ $s['sim'] >= 90 ? 'badge-neg' : 'badge-warn' }}">{{ $s['sim'] }}% Similar</span>
        </div>
        @endforeach
    </div>

    <div class="flex items-center gap-8" style="margin-top:12px;">
        <span class="badge-neutral badge" style="border:none;">{{ $g['companies'] }}</span>
        <span class="badge-neutral badge" style="border:none;">{{ $g['tools'] }}</span>
    </div>

    <div class="divider"></div>
    <div class="flex gap-8" style="flex-wrap:wrap;">
        <button class="btn btn-primary btn-sm"><i data-lucide="git-merge"></i> Merge</button>
        <button class="btn btn-secondary btn-sm"><i data-lucide="split"></i> Keep Separate</button>
        <button class="btn btn-secondary btn-sm"><i data-lucide="check-circle"></i> Choose Primary Source</button>
        <button class="btn btn-ghost btn-sm"><i data-lucide="eye-off"></i> Ignore</button>
        <button class="btn btn-ghost btn-sm"><i data-lucide="archive"></i> Archive</button>
    </div>
</div>
@endforeach
</div>

@endsection
