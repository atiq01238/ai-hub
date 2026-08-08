@extends('layouts.admin')
@section('title', $item->headline . ' · News Detail')

@section('content')

<x-page-header title="News Detail" :breadcrumb="['AI Intelligence', 'News Feed', 'Detail']">
    <x-slot:actions>
        <a href="{{ route('admin.news.edit', $item->id) }}" class="btn btn-secondary btn-sm"><i data-lucide="pencil"></i> Edit</a>
        <form action="{{ route('admin.news.destroy', $item->id) }}" method="POST" onsubmit="return confirm('Delete this news item?')">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-danger btn-sm"><i data-lucide="x"></i> Delete</button>
        </form>
    </x-slot:actions>
</x-page-header>

@if (session('status'))
    <div class="alert alert-success" style="margin-bottom:16px;">{{ session('status') }}</div>
@endif

<div class="grid-12">
    <div class="col-8">
        <div class="card card-pad" style="margin-bottom:16px;">
            <div class="flex items-center gap-8" style="margin-bottom:12px; flex-wrap:wrap;">
                @if ($item->category)<span class="badge badge-neutral">{{ $item->category }}</span>@endif
                <span class="badge badge-{{ $item->sentiment === 'positive' ? 'pos' : ($item->sentiment === 'negative' ? 'neg' : 'neutral') }}">{{ ucfirst($item->sentiment) }}</span>
                <span class="cell-sub">{{ $item->source ?? 'Unknown source' }} · {{ $item->published_at?->diffForHumans() ?? ucfirst($item->status) }}</span>
            </div>
            <h2 style="font-size:19px; margin:0 0 14px;">{{ $item->headline }}</h2>

            <div class="form-section">
                <div class="form-section__title">Summary</div>
                <p class="text-sub" style="font-size:13px; line-height:1.6;">{{ $item->summary ?: 'No summary added.' }}</p>
                @if ($item->why_it_matters)
                <div style="background:var(--surface-2); border-radius:var(--radius-sm); padding:12px 14px; font-size:12.5px; color:var(--brand-3); margin-top:10px;">
                    <i data-lucide="lightbulb" style="width:13px;height:13px; vertical-align:-2px;"></i>
                    <b>Why it matters:</b> {{ $item->why_it_matters }}
                </div>
                @endif
            </div>
        </div>

        <div class="card card-pad">
            <div class="form-section__title">Classification</div>
            <div class="grid-2">
                <div><div class="cell-sub">Sentiment</div><div style="font-weight:600;">{{ ucfirst($item->sentiment) }}</div></div>
                <div><div class="cell-sub">Importance Score</div><x-score-meter :value="$item->importance" /></div>
                <div><div class="cell-sub">Related Company</div><div style="font-weight:600;">{{ $item->company->name ?? '—' }}</div></div>
                <div><div class="cell-sub">Related Tools</div><div style="font-weight:600;">{{ implode(', ', $item->related_tools ?? []) ?: '—' }}</div></div>
            </div>
            @if (!empty($item->tags))
            <div style="margin-top:12px;">
                <div class="cell-sub" style="margin-bottom:6px;">Tags</div>
                <div class="flex gap-8" style="flex-wrap:wrap;">
                    @foreach ($item->tags as $tag)<span class="badge badge-neutral">{{ $tag }}</span>@endforeach
                </div>
            </div>
            @endif
        </div>
    </div>

    <div class="col-4 card card-pad">
        <div class="form-section__title">Source &amp; Status</div>
        <div class="flex items-center justify-between" style="padding:9px 0; border-bottom:1px solid var(--border-soft);"><span class="cell-sub">Source</span><b style="font-size:13px;">{{ $item->source ?? '—' }}</b></div>
        @if ($item->source_url)
        <div style="padding:9px 0; border-bottom:1px solid var(--border-soft);">
            <a href="{{ $item->source_url }}" target="_blank" style="font-size:12.5px;">{{ $item->source_url }} <i data-lucide="external-link" style="width:12px;height:12px;"></i></a>
        </div>
        @endif
        <div class="flex items-center justify-between" style="padding:9px 0; border-bottom:1px solid var(--border-soft);">
            <span class="cell-sub">Verification</span>
            <span class="badge {{ $item->verification_status === 'verified' ? 'badge-pos' : ($item->verification_status === 'unverified' ? 'badge-neg' : 'badge-warn') }}">{{ str_replace('_', ' ', ucfirst($item->verification_status)) }}</span>
        </div>
        <div class="flex items-center justify-between" style="padding:9px 0;">
            <span class="cell-sub">Status</span>
            <x-status-badge status="{{ ucfirst($item->status) }}" type="{{ $item->status === 'published' ? 'pos' : 'neutral' }}" />
        </div>
    </div>
</div>

@endsection
