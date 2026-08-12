@extends('layouts.admin')
@section('title', $company->name . ' · Company Detail')

@php
    use Illuminate\Support\Facades\Storage;
@endphp

@section('content')

<style>
    .company-detail {
        --cd-border: var(--border-soft, rgba(148,163,184,.14));
        --cd-text: var(--text, #eef2ff);
        --cd-muted: var(--muted, #8d98ad);
        --cd-blue: #6d8cff;
        --cd-cyan: #22d3ee;
        --cd-green: #32d583;
    }

    .company-detail__hero {
        position: relative;
        overflow: hidden;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 20px;
        margin-bottom: 15px;
        padding: 20px;
        border: 1px solid var(--cd-border);
        border-radius: 17px;
        background:
            radial-gradient(circle at 88% 5%, rgba(109,140,255,.17), transparent 28%),
            radial-gradient(circle at 58% 120%, rgba(34,211,238,.06), transparent 28%),
            linear-gradient(145deg, rgba(255,255,255,.045), rgba(255,255,255,.012));
        box-shadow: 0 16px 42px rgba(0,0,0,.07);
    }

    .company-detail__hero::after {
        content: "";
        position: absolute;
        width: 250px;
        height: 250px;
        right: -110px;
        top: -155px;
        border: 1px solid rgba(109,140,255,.10);
        border-radius: 50%;
        box-shadow:
            0 0 0 28px rgba(109,140,255,.025),
            0 0 0 56px rgba(109,140,255,.012);
        pointer-events: none;
    }

    .company-detail__identity {
        position: relative;
        z-index: 1;
        display: flex;
        align-items: center;
        gap: 14px;
        min-width: 0;
    }

    .company-detail__logo {
        width: 60px;
        height: 60px;
        flex: 0 0 60px;
        display: flex;
        align-items: center;
        justify-content: center;
        overflow: hidden;
        border: 1px solid rgba(109,140,255,.20);
        border-radius: 15px;
        color: #aebaff;
        background:
            radial-gradient(circle at 25% 20%, rgba(109,140,255,.23), transparent 60%),
            rgba(109,140,255,.055);
        box-shadow: 0 8px 25px rgba(0,0,0,.10);
        font-size: 14px;
        font-weight: 800;
    }

    .company-detail__logo img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .company-detail__eyebrow {
        display: flex;
        align-items: center;
        gap: 7px;
        margin-bottom: 6px;
        color: var(--cd-cyan);
        font-size: 8px;
        font-weight: 800;
        letter-spacing: .13em;
        text-transform: uppercase;
    }

    .company-detail__live {
        width: 6px;
        height: 6px;
        border-radius: 50%;
        background: var(--cd-green);
        box-shadow: 0 0 0 4px rgba(50,213,131,.10);
    }

    .company-detail__name {
        margin: 0;
        color: var(--cd-text);
        font-size: clamp(22px, 3vw, 29px);
        line-height: 1.1;
        letter-spacing: -.035em;
        font-weight: 800;
    }

    .company-detail__meta {
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: 7px;
        margin-top: 8px;
        color: var(--cd-muted);
        font-size: 8.5px;
    }

    .company-detail__meta-link {
        color: #aab7ff;
        text-decoration: none;
    }

    .company-detail__meta-link:hover {
        color: #c2cbff;
    }

    .company-detail__dot {
        opacity: .45;
    }

    .company-detail__hero-actions {
        position: relative;
        z-index: 1;
        flex-shrink: 0;
    }

    .company-detail__hero-actions .btn {
        min-height: 37px;
        border-radius: 9px;
    }

    .company-detail__notice {
        display: flex;
        align-items: center;
        gap: 8px;
        margin-bottom: 13px;
        padding: 10px 12px;
        border: 1px solid rgba(50,213,131,.18);
        border-radius: 9px;
        color: #79dfa5;
        background: rgba(50,213,131,.055);
        font-size: 9px;
    }

    .company-detail__notice svg {
        width: 14px;
        height: 14px;
    }

    .company-detail__tabs {
        display: flex;
        align-items: center;
        gap: 3px;
        overflow-x: auto;
        margin-bottom: 15px;
        padding: 5px;
        border: 1px solid var(--cd-border);
        border-radius: 12px;
        background: rgba(255,255,255,.022);
        scrollbar-width: thin;
    }

    .company-detail__tab {
        position: relative;
        flex: 0 0 auto;
        padding: 8px 12px;
        border-radius: 8px;
        color: var(--cd-muted);
        font-size: 8.5px;
        font-weight: 650;
        white-space: nowrap;
    }

    .company-detail__tab.is-active {
        color: #c4ceff;
        background: rgba(109,140,255,.10);
        box-shadow: inset 0 0 0 1px rgba(109,140,255,.12);
    }

    .company-detail__tab.is-active::after {
        content: "";
        position: absolute;
        left: 50%;
        bottom: 2px;
        width: 18px;
        height: 2px;
        border-radius: 3px;
        background: var(--cd-blue);
        transform: translateX(-50%);
    }

    .company-detail__card {
        border: 1px solid var(--cd-border);
        border-radius: 14px;
        background: linear-gradient(145deg, rgba(255,255,255,.035), rgba(255,255,255,.012));
        box-shadow: 0 12px 34px rgba(0,0,0,.055);
    }

    .company-detail__overview {
        min-height: 250px;
    }

    .company-detail__card-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
        margin-bottom: 15px;
    }

    .company-detail__section-title {
        display: flex;
        align-items: center;
        gap: 8px;
        color: var(--cd-text);
        font-size: 11px;
        font-weight: 800;
    }

    .company-detail__section-icon {
        width: 27px;
        height: 27px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border: 1px solid rgba(109,140,255,.16);
        border-radius: 8px;
        color: #9eafff;
        background: rgba(109,140,255,.07);
    }

    .company-detail__section-icon svg {
        width: 13px;
        height: 13px;
    }

    .company-detail__overview-text {
        color: var(--cd-muted);
        font-size: 12px;
        line-height: 1.8;
    }

    .company-detail__empty {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 13px;
        border: 1px dashed var(--cd-border);
        border-radius: 10px;
        color: var(--cd-muted);
        background: rgba(255,255,255,.015);
        font-size: 9px;
    }

    .company-detail__empty svg {
        width: 15px;
        height: 15px;
        color: #7787a8;
    }

    .company-detail__snapshot {
        padding: 17px;
    }

    .company-detail__metrics {
        display: flex;
        flex-direction: column;
        gap: 8px;
    }

    .company-detail__metric {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        padding: 11px 0;
        border-bottom: 1px solid var(--cd-border);
    }

    .company-detail__metric:last-child {
        border-bottom: 0;
        padding-bottom: 0;
    }

    .company-detail__metric:first-child {
        padding-top: 0;
    }

    .company-detail__metric-label {
        display: flex;
        align-items: center;
        gap: 7px;
        color: var(--cd-muted);
        font-size: 8.5px;
    }

    .company-detail__metric-label svg {
        width: 13px;
        height: 13px;
        color: #7f91b7;
    }

    .company-detail__metric-value {
        color: var(--cd-text);
        font-size: 10px;
        font-weight: 750;
        text-align: right;
    }

    .company-detail__metric-value--accent {
        color: #aab7ff;
    }

    .company-detail__status {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: 5px 7px;
        border: 1px solid rgba(50,213,131,.15);
        border-radius: 6px;
        color: #7de0a7;
        background: rgba(50,213,131,.055);
        font-size: 7.5px;
        font-weight: 750;
        text-transform: uppercase;
    }

    .company-detail__status-dot {
        width: 5px;
        height: 5px;
        border-radius: 50%;
        background: var(--cd-green);
    }

    @media (max-width: 800px) {
        .company-detail__hero {
            align-items: flex-start;
            flex-direction: column;
        }

        .company-detail__hero-actions,
        .company-detail__hero-actions .btn {
            width: 100%;
        }
    }

    @media (max-width: 650px) {
        .company-detail__identity {
            align-items: flex-start;
        }

        .company-detail__logo {
            width: 50px;
            height: 50px;
            flex-basis: 50px;
        }

        .company-detail__name {
            font-size: 22px;
        }
    }
</style>

<div class="company-detail">

    {{-- HERO --}}
    <section class="company-detail__hero">

        <div class="company-detail__identity">

            @if ($company->logo_path)

                <div class="company-detail__logo">
                    <img
                        src="{{ Storage::url($company->logo_path) }}"
                        alt="{{ $company->name }} logo"
                    >
                </div>

            @else

                <div class="company-detail__logo">
                    {{ substr($company->name, 0, 2) }}
                </div>

            @endif

            <div>

                <div class="company-detail__eyebrow">
                    <span class="company-detail__live"></span>
                    AI Company Intelligence
                </div>

                <h1 class="company-detail__name">
                    {{ $company->name }}
                </h1>

                <div class="company-detail__meta">

                    @if ($company->website)
                        <a
                            href="{{ $company->website }}"
                            target="_blank"
                            class="company-detail__meta-link"
                        >
                            {{ $company->website }}
                        </a>
                    @else
                        <span>Website not added</span>
                    @endif

                    <span class="company-detail__dot">•</span>

                    <span>
                        {{ ucfirst($company->status) }}
                    </span>

                </div>

            </div>

        </div>

        <div class="company-detail__hero-actions">
            <a
                href="{{ route('admin.companies.edit', $company->id) }}"
                class="btn btn-primary btn-sm"
            >
                <i data-lucide="pencil"></i>
                Edit Company
            </a>
        </div>

    </section>

    @if (session('status'))

        <div class="company-detail__notice">
            <i data-lucide="circle-check"></i>
            <span>{{ session('status') }}</span>
        </div>

    @endif

    {{-- VISUAL TABS --}}
    <div class="company-detail__tabs">

        <div class="company-detail__tab is-active">
            Overview
        </div>

        <div class="company-detail__tab">
            AI Tools
        </div>

        <div class="company-detail__tab">
            Models
        </div>

        <div class="company-detail__tab">
            Latest News
        </div>

        <div class="company-detail__tab">
            Pricing
        </div>

        <div class="company-detail__tab">
            Comparisons
        </div>

        <div class="company-detail__tab">
            Reviews
        </div>

        <div class="company-detail__tab">
            Timeline
        </div>

    </div>

    {{-- CONTENT --}}
    <div class="grid-12">

        {{-- OVERVIEW --}}
        <div class="col-8">

            <div class="card card-pad company-detail__card company-detail__overview">

                <div class="company-detail__card-head">

                    <div class="company-detail__section-title">
                        <span class="company-detail__section-icon">
                            <i data-lucide="building-2"></i>
                        </span>
                        Company Overview
                    </div>

                    <span class="company-detail__status">
                        <span class="company-detail__status-dot"></span>
                        {{ ucfirst($company->status) }}
                    </span>

                </div>

                @if ($company->description)

                    <div class="company-detail__overview-text">
                        {{ $company->description }}
                    </div>

                @else

                    <div class="company-detail__empty">
                        <i data-lucide="file-text"></i>
                        <span>No overview added yet.</span>
                    </div>

                @endif

            </div>

        </div>

        {{-- SNAPSHOT --}}
        <div class="col-4">

            <div class="card company-detail__card company-detail__snapshot">

                <div class="company-detail__card-head">

                    <div class="company-detail__section-title">
                        <span class="company-detail__section-icon">
                            <i data-lucide="activity"></i>
                        </span>
                        Company Snapshot
                    </div>

                </div>

                <div class="company-detail__metrics">

                    <div class="company-detail__metric">

                        <div class="company-detail__metric-label">
                            <i data-lucide="calendar"></i>
                            Founded
                        </div>

                        <div class="company-detail__metric-value">
                            {{ $company->founded_year ?? '—' }}
                        </div>

                    </div>

                    <div class="company-detail__metric">

                        <div class="company-detail__metric-label">
                            <i data-lucide="sparkles"></i>
                            AI Tools
                        </div>

                        <div class="company-detail__metric-value company-detail__metric-value--accent">
                            {{ $company->tools_count }}
                        </div>

                    </div>

                    <div class="company-detail__metric">

                        <div class="company-detail__metric-label">
                            <i data-lucide="clock-3"></i>
                            Latest Update
                        </div>

                        <div class="company-detail__metric-value">
                            {{ $company->updated_at->diffForHumans() }}
                        </div>

                    </div>

                </div>

            </div>

        </div>

    </div>

</div>

@endsection