@extends('layouts.admin')
@section('title', $item->headline . ' · News Detail')

@section('content')

<style>
    /* AI HUB — ADVANCED NEWS DETAIL
       UI-only enhancement. Existing backend data, routes,
       components and actions are preserved. */

    .news-detail {
        --nd-border: var(--border-soft, rgba(148,163,184,.14));
        --nd-text: var(--text, #eef2ff);
        --nd-muted: var(--muted, #8d98ad);
        --nd-blue: #6d8cff;
        --nd-cyan: #22d3ee;
        --nd-green: #32d583;
        --nd-red: #f97068;
        --nd-orange: #f5a524;
    }

    .news-detail__hero {
        position: relative;
        overflow: hidden;
        margin-bottom: 16px;
        padding: 21px 22px;
        border: 1px solid var(--nd-border);
        border-radius: 17px;
        background:
            radial-gradient(circle at 92% 8%, rgba(109,140,255,.18), transparent 27%),
            radial-gradient(circle at 65% 115%, rgba(34,211,238,.07), transparent 28%),
            linear-gradient(145deg, rgba(255,255,255,.045), rgba(255,255,255,.012));
        box-shadow: 0 16px 42px rgba(0,0,0,.08);
    }

    .news-detail__hero:after {
        content: "";
        position: absolute;
        width: 240px;
        height: 240px;
        right: -115px;
        top: -150px;
        border: 1px solid rgba(109,140,255,.12);
        border-radius: 50%;
        box-shadow: 0 0 0 28px rgba(109,140,255,.025), 0 0 0 58px rgba(109,140,255,.012);
        pointer-events: none;
    }

    .news-detail__hero-top,
    .news-detail__headline {
        position: relative;
        z-index: 1;
    }

    .news-detail__eyebrow {
        display: flex;
        align-items: center;
        gap: 7px;
        margin-bottom: 10px;
        color: var(--nd-cyan);
        font-size: 9px;
        font-weight: 800;
        letter-spacing: .13em;
        text-transform: uppercase;
    }

    .news-detail__live {
        width: 6px;
        height: 6px;
        border-radius: 50%;
        background: var(--nd-green);
        box-shadow: 0 0 0 4px rgba(50,213,131,.10);
    }

    .news-detail__headline {
        max-width: 980px;
        margin: 0;
        color: var(--nd-text);
        font-size: clamp(22px, 3vw, 31px);
        line-height: 1.18;
        letter-spacing: -.035em;
        font-weight: 800;
    }

    .news-detail__meta {
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: 7px;
        margin-top: 12px;
    }

    .news-detail__source {
        color: var(--nd-muted);
        font-size: 9px;
    }

    .news-detail__layout {
        display: grid;
        grid-template-columns: minmax(0, 1fr) 315px;
        gap: 16px;
    }

    .news-detail__main {
        min-width: 0;
    }

    .news-detail__card {
        border: 1px solid var(--nd-border);
        border-radius: 14px;
        background: linear-gradient(145deg, rgba(255,255,255,.035), rgba(255,255,255,.012));
        box-shadow: 0 10px 30px rgba(0,0,0,.055);
    }

    .news-detail__card + .news-detail__card {
        margin-top: 13px;
    }

    .news-detail__card-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
        padding: 14px 16px;
        border-bottom: 1px solid var(--nd-border);
    }

    .news-detail__section-title {
        display: flex;
        align-items: center;
        gap: 8px;
        color: var(--nd-text);
        font-size: 10.5px;
        font-weight: 800;
        letter-spacing: .01em;
    }

    .news-detail__section-icon {
        width: 27px;
        height: 27px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border: 1px solid rgba(109,140,255,.16);
        border-radius: 8px;
        color: #9daeff;
        background: rgba(109,140,255,.07);
    }

    .news-detail__section-icon svg {
        width: 13px;
        height: 13px;
    }

    .news-detail__card-body {
        padding: 16px;
    }

    .news-detail__summary {
        margin: 0;
        color: var(--nd-muted);
        font-size: 12px;
        line-height: 1.75;
    }

    .news-detail__why {
        display: flex;
        gap: 9px;
        align-items: flex-start;
        margin-top: 13px;
        padding: 12px 13px;
        border: 1px solid rgba(109,140,255,.15);
        border-radius: 10px;
        color: #aab9ff;
        background: linear-gradient(135deg, rgba(109,140,255,.07), rgba(109,140,255,.025));
        font-size: 10.5px;
        line-height: 1.55;
    }

    .news-detail__why i {
        width: 14px;
        height: 14px;
        flex: 0 0 14px;
        margin-top: 1px;
    }

    .news-detail__why strong {
        color: #c4ceff;
    }

    .news-detail__classification {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 9px;
    }

    .news-detail__stat {
        min-height: 74px;
        padding: 12px;
        border: 1px solid var(--nd-border);
        border-radius: 10px;
        background: rgba(255,255,255,.018);
    }

    .news-detail__stat-label {
        margin-bottom: 7px;
        color: var(--nd-muted);
        font-size: 8px;
        font-weight: 750;
        letter-spacing: .08em;
        text-transform: uppercase;
    }

    .news-detail__stat-value {
        color: var(--nd-text);
        font-size: 11px;
        line-height: 1.45;
        font-weight: 650;
        word-break: break-word;
    }

    .news-detail__score {
        display: flex;
        align-items: center;
        gap: 9px;
        min-height: 24px;
    }

    .news-detail__tags {
        display: flex;
        flex-wrap: wrap;
        gap: 5px;
        margin-top: 13px;
        padding-top: 13px;
        border-top: 1px solid var(--nd-border);
    }

    .news-detail__tags-title {
        width: 100%;
        margin-bottom: 1px;
        color: var(--nd-muted);
        font-size: 8px;
        font-weight: 750;
        letter-spacing: .08em;
        text-transform: uppercase;
    }

    .news-detail__tags .badge {
        font-size: 8px;
    }

    .news-detail__sidebar {
        min-width: 0;
    }

    .news-detail__side-card {
        position: sticky;
        top: 16px;
        overflow: hidden;
        border: 1px solid var(--nd-border);
        border-radius: 14px;
        background: linear-gradient(145deg, rgba(255,255,255,.035), rgba(255,255,255,.012));
        box-shadow: 0 12px 34px rgba(0,0,0,.065);
    }

    .news-detail__side-head {
        padding: 15px 16px;
        border-bottom: 1px solid var(--nd-border);
        background: rgba(255,255,255,.018);
    }

    .news-detail__side-head-title {
        color: var(--nd-text);
        font-size: 11px;
        font-weight: 800;
    }

    .news-detail__side-head-sub {
        margin-top: 3px;
        color: var(--nd-muted);
        font-size: 8.5px;
    }

    .news-detail__side-body {
        padding: 7px 16px 14px;
    }

    .news-detail__info {
        padding: 11px 0;
        border-bottom: 1px solid var(--nd-border);
    }

    .news-detail__info:last-child {
        border-bottom: 0;
    }

    .news-detail__info-label {
        margin-bottom: 5px;
        color: var(--nd-muted);
        font-size: 8px;
        font-weight: 750;
        letter-spacing: .07em;
        text-transform: uppercase;
    }

    .news-detail__info-value {
        color: var(--nd-text);
        font-size: 10.5px;
        line-height: 1.45;
        font-weight: 600;
    }

    .news-detail__source-link {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        max-width: 100%;
        color: #9eafff;
        font-size: 9.5px;
        line-height: 1.4;
        text-decoration: none;
        word-break: break-all;
    }

    .news-detail__source-link:hover {
        color: #c4ceff;
    }

    .news-detail__source-link svg {
        width: 11px;
        height: 11px;
        flex: 0 0 11px;
    }

    .news-detail__status-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
    }

    .news-detail__status-label {
        color: var(--nd-muted);
        font-size: 9px;
    }

    .news-detail__footer-note {
        display: flex;
        align-items: center;
        gap: 7px;
        margin-top: 12px;
        padding: 10px 11px;
        border: 1px solid var(--nd-border);
        border-radius: 9px;
        color: var(--nd-muted);
        background: rgba(255,255,255,.018);
        font-size: 8.5px;
        line-height: 1.45;
    }

    .news-detail__footer-note svg {
        width: 13px;
        height: 13px;
        flex: 0 0 13px;
        color: var(--nd-cyan);
    }

    .news-detail__notice {
        display: flex;
        align-items: center;
        gap: 8px;
        margin-bottom: 14px;
        padding: 10px 12px;
        border: 1px solid rgba(50,213,131,.18);
        border-radius: 9px;
        color: #79dfa5;
        background: rgba(50,213,131,.055);
        font-size: 9.5px;
    }

    .news-detail__notice svg {
        width: 14px;
        height: 14px;
        flex: 0 0 14px;
    }

    @media (max-width: 900px) {
        .news-detail__layout {
            grid-template-columns: 1fr;
        }

        .news-detail__side-card {
            position: static;
        }
    }

    @media (max-width: 600px) {
        .news-detail__hero {
            padding: 17px;
        }

        .news-detail__headline {
            font-size: 22px;
        }

        .news-detail__classification {
            grid-template-columns: 1fr;
        }

        .news-detail__card-head,
        .news-detail__card-body {
            padding-left: 13px;
            padding-right: 13px;
        }
    }
</style>

<div class="news-detail">

    {{-- HERO --}}
    <section class="news-detail__hero">

        <div class="news-detail__hero-top">
            <div class="news-detail__eyebrow">
                <span class="news-detail__live"></span>
                AI Intelligence · News Detail
            </div>

            <h1 class="news-detail__headline">
                {{ $item->headline }}
            </h1>

            <div class="news-detail__meta">

                @if ($item->category)
                    <span class="badge badge-neutral">{{ $item->category }}</span>
                @endif

                <span class="badge badge-{{ $item->sentiment === 'positive' ? 'pos' : ($item->sentiment === 'negative' ? 'neg' : 'neutral') }}">
                    {{ ucfirst($item->sentiment) }}
                </span>

                <span class="badge {{ $item->verification_status === 'verified' ? 'badge-pos' : ($item->verification_status === 'unverified' ? 'badge-neg' : 'badge-warn') }}">
                    {{ str_replace('_', ' ', ucfirst($item->verification_status)) }}
                </span>

                <span class="news-detail__source">
                    {{ $item->source ?? 'Unknown source' }}
                    ·
                    {{ $item->published_at?->diffForHumans() ?? ucfirst($item->status) }}
                </span>

            </div>
        </div>

    </section>

    @if (session('status'))
        <div class="news-detail__notice">
            <i data-lucide="circle-check"></i>
            <span>{{ session('status') }}</span>
        </div>
    @endif

    <div class="news-detail__layout">

        {{-- MAIN CONTENT --}}
        <main class="news-detail__main">

            {{-- SUMMARY --}}
            <section class="news-detail__card">

                <div class="news-detail__card-head">
                    <div class="news-detail__section-title">
                        <span class="news-detail__section-icon">
                            <i data-lucide="file-text"></i>
                        </span>
                        Story Summary
                    </div>
                </div>

                <div class="news-detail__card-body">

                    <p class="news-detail__summary">
                        {{ $item->summary ?: 'No summary added.' }}
                    </p>

                    @if ($item->why_it_matters)
                        <div class="news-detail__why">
                            <i data-lucide="lightbulb"></i>
                            <div>
                                <strong>Why it matters</strong><br>
                                {{ $item->why_it_matters }}
                            </div>
                        </div>
                    @endif

                </div>

            </section>

            {{-- CLASSIFICATION --}}
            <section class="news-detail__card">

                <div class="news-detail__card-head">
                    <div class="news-detail__section-title">
                        <span class="news-detail__section-icon">
                            <i data-lucide="layers-3"></i>
                        </span>
                        Intelligence Classification
                    </div>
                </div>

                <div class="news-detail__card-body">

                    <div class="news-detail__classification">

                        <div class="news-detail__stat">
                            <div class="news-detail__stat-label">Sentiment</div>
                            <div class="news-detail__stat-value">
                                {{ ucfirst($item->sentiment) }}
                            </div>
                        </div>

                        <div class="news-detail__stat">
                            <div class="news-detail__stat-label">Importance Score</div>
                            <div class="news-detail__score">
                                <x-score-meter :value="$item->importance" />
                            </div>
                        </div>

                        <div class="news-detail__stat">
                            <div class="news-detail__stat-label">Related Company</div>
                            <div class="news-detail__stat-value">
                                {{ $item->company->name ?? '—' }}
                            </div>
                        </div>

                        <div class="news-detail__stat">
                            <div class="news-detail__stat-label">Related Tools</div>
                            <div class="news-detail__stat-value">
                                {{ implode(', ', $item->related_tools ?? []) ?: '—' }}
                            </div>
                        </div>

                    </div>

                    @if (!empty($item->tags))
                        <div class="news-detail__tags">
                            <div class="news-detail__tags-title">Tags</div>

                            @foreach ($item->tags as $tag)
                                <span class="badge badge-neutral">{{ $tag }}</span>
                            @endforeach
                        </div>
                    @endif

                </div>

            </section>

        </main>

        {{-- SIDEBAR --}}
        <aside class="news-detail__sidebar">

            <section class="news-detail__side-card">

                <div class="news-detail__side-head">
                    <div class="news-detail__side-head-title">
                        Source & Status
                    </div>
                    <div class="news-detail__side-head-sub">
                        Verification and publication intelligence
                    </div>
                </div>

                <div class="news-detail__side-body">

                    <div class="news-detail__info">
                        <div class="news-detail__info-label">Source</div>
                        <div class="news-detail__info-value">
                            {{ $item->source ?? '—' }}
                        </div>
                    </div>

                    @if ($item->source_url)
                        <div class="news-detail__info">
                            <div class="news-detail__info-label">Source URL</div>

                            <a
                                href="{{ $item->source_url }}"
                                target="_blank"
                                class="news-detail__source-link"
                            >
                                {{ $item->source_url }}
                                <i data-lucide="external-link"></i>
                            </a>
                        </div>
                    @endif

                    <div class="news-detail__info">
                        <div class="news-detail__status-row">
                            <span class="news-detail__status-label">Verification</span>

                            <span class="badge {{ $item->verification_status === 'verified' ? 'badge-pos' : ($item->verification_status === 'unverified' ? 'badge-neg' : 'badge-warn') }}">
                                {{ str_replace('_', ' ', ucfirst($item->verification_status)) }}
                            </span>
                        </div>
                    </div>

                    <div class="news-detail__info">
                        <div class="news-detail__status-row">
                            <span class="news-detail__status-label">Publication Status</span>

                            <x-status-badge
                                status="{{ ucfirst($item->status) }}"
                                type="{{ $item->status === 'published' ? 'pos' : 'neutral' }}"
                            />
                        </div>
                    </div>

                    <div class="news-detail__footer-note">
                        <i data-lucide="shield-check"></i>
                        <span>
                            Source and verification information is shown exactly from the current news record.
                        </span>
                    </div>

                </div>

            </section>

            {{-- ACTIONS --}}
            <div style="display:flex; gap:7px; margin-top:11px;">

                <a
                    href="{{ route('admin.news.edit', $item->id) }}"
                    class="btn btn-secondary btn-sm"
                    style="flex:1; justify-content:center;"
                >
                    <i data-lucide="pencil"></i>
                    Edit
                </a>

                <form
                    action="{{ route('admin.news.destroy', $item->id) }}"
                    method="POST"
                    onsubmit="return confirm('Delete this news item?')"
                    style="margin:0;"
                >
                    @csrf
                    @method('DELETE')

                    <button type="submit" class="btn btn-danger btn-sm">
                        <i data-lucide="trash-2"></i>
                        Delete
                    </button>
                </form>

            </div>

        </aside>

    </div>

</div>

@endsection