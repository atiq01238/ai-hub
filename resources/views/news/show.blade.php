@extends('layouts.admin')
@section('title', $item->headline . ' · News Detail')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/pages/news.css') }}">
@endpush

@section('content')
@php
    $sentimentClass = $item->sentiment === 'positive' ? 'badge-pos' : ($item->sentiment === 'negative' ? 'badge-neg' : 'badge-neutral');
    $verificationClass = $item->verification_status === 'verified' ? 'badge-pos' : ($item->verification_status === 'unverified' ? 'badge-neg' : 'badge-warn');
@endphp

<div class="news-shell news-detail">
    <x-page-header
        title="News Intelligence Detail"
        subtitle="Review the story, classification, source and verification status."
        :breadcrumb="['AI Intelligence', 'News', 'Detail']"
    >
        <x-slot:actions>
            <a href="{{ route('admin.news.index') }}" class="btn btn-secondary btn-sm"><i data-lucide="arrow-left"></i> News Feed</a>
            <form action="{{ route('admin.news.save', $item->id) }}" method="POST" class="news-inline-form">
                @csrf
                <button type="submit" class="btn btn-secondary btn-sm"><i data-lucide="bookmark"></i> Save</button>
            </form>
            <a href="{{ route('admin.news.edit', $item->id) }}" class="btn btn-primary btn-sm"><i data-lucide="pencil"></i> Edit</a>
        </x-slot:actions>
    </x-page-header>

    @if (session('status'))
        <div class="alert alert-success news-alert"><i data-lucide="circle-check"></i><span>{{ session('status') }}</span></div>
    @endif

    <section class="news-detail__hero">
        <div class="news-detail__hero-main">
            <div class="news-detail__eyebrow"><span></span> AI Intelligence Record</div>
            <h1>{{ $item->headline }}</h1>
            <div class="news-detail__meta">
                @if ($item->category)<span class="badge badge-neutral">{{ $item->category }}</span>@endif
                <span class="badge {{ $sentimentClass }}">{{ ucfirst($item->sentiment) }}</span>
                <span class="badge {{ $verificationClass }}">{{ str_replace('_', ' ', ucfirst($item->verification_status)) }}</span>
                <span><i data-lucide="building-2"></i>{{ $item->company->name ?? 'No company linked' }}</span>
                <span><i data-lucide="clock-3"></i>{{ $item->published_at?->diffForHumans() ?? ucfirst($item->status) }}</span>
            </div>
        </div>
        <div class="news-detail__importance">
            <div class="news-detail__importance-value">{{ (int) $item->importance }}</div>
            <div class="news-detail__importance-label">Importance</div>
            <x-score-meter :value="$item->importance" :segments="6" />
        </div>
    </section>

    <div class="news-detail__layout">
        <main class="news-detail__main">
            <section class="news-panel">
                <div class="news-panel__header">
                    <div class="news-panel__icon"><i data-lucide="file-text"></i></div>
                    <div><h2>Story Summary</h2><p>The structured editorial summary stored for this intelligence record.</p></div>
                </div>
                <div class="news-panel__body">
                    <p class="news-detail__summary">{{ $item->summary ?: 'No summary has been added yet.' }}</p>
                    @if ($item->why_it_matters)
                        <div class="news-detail__why"><i data-lucide="lightbulb"></i><div><strong>Why it matters</strong><p>{{ $item->why_it_matters }}</p></div></div>
                    @endif
                </div>
            </section>

            <section class="news-panel">
                <div class="news-panel__header">
                    <div class="news-panel__icon"><i data-lucide="scan-search"></i></div>
                    <div><h2>Intelligence Classification</h2><p>Signals used for discovery, ranking and editorial trust.</p></div>
                </div>
                <div class="news-panel__body">
                    <div class="news-detail__facts">
                        <div class="news-fact"><span>Sentiment</span><strong>{{ ucfirst($item->sentiment) }}</strong></div>
                        <div class="news-fact"><span>Category</span><strong>{{ $item->category ?: '—' }}</strong></div>
                        <div class="news-fact"><span>Related company</span><strong>{{ $item->company->name ?? '—' }}</strong></div>
                        <div class="news-fact"><span>Publication status</span><strong>{{ ucfirst($item->status) }}</strong></div>
                    </div>

                    @if (!empty($item->related_tools))
                        <div class="news-detail__tag-block"><span>Related tools</span><div>@foreach ($item->related_tools as $tool)<span class="news-chip">{{ $tool }}</span>@endforeach</div></div>
                    @endif

                    @if (!empty($item->tags))
                        <div class="news-detail__tag-block"><span>Tags</span><div>@foreach ($item->tags as $tag)<span class="news-chip">{{ $tag }}</span>@endforeach</div></div>
                    @endif
                </div>
            </section>
        </main>

        <aside class="news-detail__sidebar">
            <section class="news-panel">
                <div class="news-panel__header">
                    <div class="news-panel__icon"><i data-lucide="shield-check"></i></div>
                    <div><h2>Source & Trust</h2><p>Verification and provenance details.</p></div>
                </div>
                <div class="news-panel__body news-stack">
                    <div class="news-detail__source-row"><span>Source</span><strong>{{ $item->source ?? '—' }}</strong></div>
                    <div class="news-detail__source-row"><span>Verification</span><span class="badge {{ $verificationClass }}">{{ str_replace('_', ' ', ucfirst($item->verification_status)) }}</span></div>
                    <div class="news-detail__source-row"><span>Status</span><x-status-badge status="{{ ucfirst($item->status) }}" type="{{ $item->status === 'published' ? 'pos' : 'neutral' }}" /></div>

                    @if ($item->source_url)
                        <a href="{{ $item->source_url }}" target="_blank" rel="noopener noreferrer" class="news-source-link"><i data-lucide="external-link"></i><span>Open original source</span></a>
                    @endif

                    @if ($item->duplicateOf)
                        <div class="news-detail__duplicate-note">
                            <i data-lucide="copy"></i>
                            <div><strong>Duplicate relationship</strong><p>This record is linked to “{{ $item->duplicateOf->headline }}”.</p></div>
                        </div>
                    @endif
                </div>
            </section>

            <section class="news-danger-zone">
                <div><strong>Danger zone</strong><span>Deleting removes this news record.</span></div>
                <form action="{{ route('admin.news.destroy', $item->id) }}" method="POST" onsubmit="return confirm('Delete this news item?')" class="news-inline-form">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger btn-sm"><i data-lucide="trash-2"></i> Delete</button>
                </form>
            </section>
        </aside>
    </div>
</div>
@endsection
