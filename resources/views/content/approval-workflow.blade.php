@extends('layouts.admin')
@section('title', 'Approval Workflow')

@section('content')

<x-page-header title="Content Approval Workflow" subtitle="Draft → In Review → Needs Changes → Approved → Scheduled → Published" :breadcrumb="['Content', 'Approval Workflow']" />

<div style="display:grid; grid-template-columns:repeat(6,1fr); gap:14px; overflow-x:auto;">
    @php
    $stages = [
        'Draft' => [['GPT-5.2 Turbo Explained','Sarah A.']],
        'In Review' => [['Anthropic Enterprise API Push','Imran K.']],
        'Needs Changes' => [['Every AI Pricing Change','Sarah A.']],
        'Approved' => [['Runway Series E Recap','Ayesha R.']],
        'Scheduled' => [['Is Gemini 3 Pro the Leader?','Ayesha R.']],
        'Published' => [['Midjourney Price Cut Explained','Imran K.'],['ChatGPT vs Claude 2026','Sarah A.']],
    ];
    @endphp
    @foreach($stages as $stage => $cards)
    <div>
        <div class="flex items-center justify-between" style="margin-bottom:10px;">
            <span style="font-size:12px; font-weight:700; text-transform:uppercase; letter-spacing:.04em; color:var(--text-lo);">{{ $stage }}</span>
            <span class="badge badge-neutral">{{ count($cards) }}</span>
        </div>
        <div style="display:flex; flex-direction:column; gap:10px;">
            @foreach($cards as $c)
            <div class="card card-pad" style="cursor:pointer;">
                <div style="font-size:12.5px; font-weight:650; line-height:1.4; margin-bottom:10px;">{{ $c[0] }}</div>
                <div class="flex items-center justify-between">
                    <div class="row-media"><div class="thumb" style="width:22px;height:22px;font-size:9px;">{{ substr($c[1],0,2) }}</div><span class="cell-sub">{{ $c[1] }}</span></div>
                    <i data-lucide="message-square" style="width:13px;height:13px; color:var(--text-lo);"></i>
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @endforeach
</div>

<div class="card card-pad" style="margin-top:24px;">
    <div class="section-title">Approval History — "GPT-5.2 Turbo Explained"</div>
    @foreach([
        ['Sarah Ahmed created draft','Aug 3, 10:12 AM'],
        ['Submitted for review','Aug 4, 2:40 PM'],
        ['Imran Khan requested changes: "Add pricing section"','Aug 4, 5:15 PM'],
        ['Sarah Ahmed resubmitted','Aug 5, 9:00 AM'],
    ] as $h)
    <div class="flex items-center gap-12" style="padding:9px 0; border-bottom:1px solid var(--border-soft);">
        <span class="dot-indicator" style="background:var(--brand-1);"></span>
        <div style="flex:1; font-size:13px;">{{ $h[0] }}</div>
        <span class="cell-sub">{{ $h[1] }}</span>
    </div>
    @endforeach
</div>
@endsection
