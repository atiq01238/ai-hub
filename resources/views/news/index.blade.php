@extends('layouts.admin')
@section('title', 'AI News Intelligence')


@section('content')


<style>
    /* =========================================================
       AI NEWS INTELLIGENCE - PAGINATION
       UI ONLY — NO BACKEND LOGIC CHANGED
    ========================================================= */

    .news-pagination {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 16px;
        margin-top: 18px;
        padding: 13px 15px;
        border: 1px solid var(--border-soft);
        border-radius: 12px;
        background: var(--surface);
    }

    .news-pagination__info {
        color: var(--text-sub, #8b95a7);
        font-size: 11px;
        white-space: nowrap;
    }

    .news-pagination__info strong {
        color: var(--text, #e8edf7);
        font-weight: 700;
    }

    .news-pagination__controls {
        display: flex;
        align-items: center;
        gap: 5px;
    }

    .news-page-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 5px;
        min-width: 70px;
        height: 31px;
        padding: 0 9px;
        border: 1px solid var(--border);
        border-radius: 7px;
        background: var(--surface);
        color: var(--text-sub, #9aa4b5);
        font-size: 10px;
        font-weight: 600;
        text-decoration: none;
        transition:
            color .18s ease,
            background .18s ease,
            border-color .18s ease,
            transform .18s ease;
    }

    .news-page-btn:hover {
        border-color: rgba(109, 140, 255, .45);
        color: var(--text, #fff);
        background: rgba(109, 140, 255, .08);
        text-decoration: none;
        transform: translateY(-1px);
    }

    /*
     * IMPORTANT:
     * Explicit SVG dimensions prevent Laravel/Tailwind
     * pagination arrows from becoming extremely large.
     */
    .news-page-btn svg {
        width: 12px !important;
        height: 12px !important;
        min-width: 12px !important;
        max-width: 12px !important;
        min-height: 12px !important;
        max-height: 12px !important;
        display: block;
        flex-shrink: 0;
    }

    .news-page-btn.is-disabled {
        opacity: .38;
        cursor: not-allowed;
        pointer-events: none;
        transform: none;
    }

    .news-page-numbers {
        display: flex;
        align-items: center;
        gap: 4px;
    }

    .news-page-number {
        width: 31px;
        height: 31px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border: 1px solid transparent;
        border-radius: 7px;
        color: var(--text-sub, #9aa4b5);
        background: transparent;
        font-size: 10px;
        font-weight: 650;
        text-decoration: none;
        transition:
            color .18s ease,
            background .18s ease,
            border-color .18s ease,
            transform .18s ease;
    }

    .news-page-number:hover {
        border-color: var(--border);
        background: var(--surface-2);
        color: var(--text, #fff);
        text-decoration: none;
        transform: translateY(-1px);
    }

    .news-page-number.is-active {
        border-color: rgba(109, 140, 255, .35);
        background:
            linear-gradient(
                135deg,
                rgba(109, 140, 255, .22),
                rgba(109, 140, 255, .08)
            );
        color: #aebcff;
        box-shadow: 0 4px 14px rgba(109, 140, 255, .08);
    }

    .news-page-dots {
        width: 24px;
        text-align: center;
        color: var(--text-sub, #7f899b);
        font-size: 10px;
        user-select: none;
    }

    @media (max-width: 700px) {

        .news-pagination {
            align-items: stretch;
            flex-direction: column;
        }

        .news-pagination__info {
            text-align: center;
        }

        .news-pagination__controls {
            justify-content: center;
            flex-wrap: wrap;
        }

    }

    @media (max-width: 480px) {

        .news-page-btn {
            min-width: 34px;
            width: 34px;
            padding: 0;
            font-size: 0;
        }

        .news-page-btn svg {
            width: 13px !important;
            height: 13px !important;
        }

    }
</style>


<x-page-header
    title="AI News Intelligence Center"
    subtitle="{{ $items->total() }} news items"
    :breadcrumb="['AI Intelligence', 'News Feed']"
>
    <x-slot:actions>

        <a
            href="{{ route('admin.news.duplicates') }}"
            class="btn btn-secondary btn-sm"
        >
            <i data-lucide="copy"></i>
            Duplicates
        </a>

        <a
            href="{{ route('admin.news.create') }}"
            class="btn btn-primary btn-sm"
        >
            <i data-lucide="plus"></i>
            Add News
        </a>

    </x-slot:actions>
</x-page-header>


@if (session('error'))

    <div class="alert alert-danger" style="margin-bottom:16px;">
        {{ session('error') }}
    </div>

@endif


@if (session('status'))

    <div
        class="alert alert-success"
        style="margin-bottom:16px;"
    >
        {{ session('status') }}
    </div>

@endif


@isset($notice)

    <div
        class="alert alert-warning"
        style="margin-bottom:16px;"
    >
        {{ $notice }}
    </div>

@endisset


{{-- =========================================================
     FILTER BAR
========================================================= --}}

<form method="GET" class="filter-bar">

    <div
        class="input-search"
        style="
            background:var(--surface);
            border:1px solid var(--border);
            border-radius:var(--radius-sm);
            padding:8px 12px;
        "
    >

        <i data-lucide="search"></i>

        <input
            type="text"
            name="search"
            value="{{ request('search') }}"
            placeholder="Search headlines..."
        >

    </div>


    <select
        class="select"
        name="category"
        onchange="this.form.submit()"
    >

        <option value="">
            All Categories
        </option>

        @foreach ([
            'Breaking News',
            'New Model',
            'Product Launch',
            'Product Update',
            'New Feature',
            'Pricing Change',
            'AI Review',
            'Benchmark',
            'Research',
            'Funding',
            'Acquisition',
            'Security',
            'Policy',
            'Regulation'
        ] as $cat)

            <option
                value="{{ $cat }}"
                @selected(request('category') === $cat)
            >
                {{ $cat }}
            </option>

        @endforeach

    </select>


    <select
        class="select"
        name="company_id"
        onchange="this.form.submit()"
    >

        <option value="">
            All Companies
        </option>

        @foreach ($companies as $company)

            <option
                value="{{ $company->id }}"
                @selected(
                    (string) request('company_id') ===
                    (string) $company->id
                )
            >
                {{ $company->name }}
            </option>

        @endforeach

    </select>


    <button
        type="submit"
        class="btn btn-secondary btn-sm"
    >
        Filter
    </button>


    @if (request()->anyFilled([
        'search',
        'category',
        'company_id'
    ]))

        <a
            href="{{ route('admin.news.index') }}"
            class="btn btn-ghost btn-sm"
        >
            Clear
        </a>

    @endif

</form>


{{-- =========================================================
     NEWS LIST
========================================================= --}}

<div
    style="
        display:flex;
        flex-direction:column;
        gap:14px;
    "
>

@forelse ($items as $item)

    <div class="card card-pad">

        <div
            class="flex gap-12"
            style="
                align-items:flex-start;
            "
        >

            {{-- NEWS THUMBNAIL --}}

            <div class="thumb lg">
                {{ substr($item->source ?? $item->headline, 0, 2) }}
            </div>


            {{-- NEWS CONTENT --}}

            <div
                style="
                    flex:1;
                    min-width:0;
                "
            >

                {{-- BADGES / META --}}

                <div
                    class="flex items-center gap-8"
                    style="
                        margin-bottom:6px;
                        flex-wrap:wrap;
                    "
                >

                    @if ($item->category)

                        <span class="badge badge-neutral">
                            {{ $item->category }}
                        </span>

                    @endif


                    <span
                        class="badge badge-{{
                            $item->sentiment === 'positive'
                                ? 'pos'
                                : (
                                    $item->sentiment === 'negative'
                                        ? 'neg'
                                        : 'neutral'
                                )
                        }}"
                    >
                        {{ ucfirst($item->sentiment) }}
                    </span>


                    <span
                        class="badge {{
                            $item->verification_status === 'verified'
                                ? 'badge-pos'
                                : (
                                    $item->verification_status === 'unverified'
                                        ? 'badge-neg'
                                        : 'badge-warn'
                                )
                        }}"
                    >
                        {{ str_replace(
                            '_',
                            ' ',
                            ucfirst($item->verification_status)
                        ) }}
                    </span>


                    <span class="cell-sub">

                        {{ $item->source ?? 'Unknown source' }}

                        ·

                        {{
                            $item->published_at?->diffForHumans()
                            ?? ucfirst($item->status)
                        }}

                    </span>

                </div>


                {{-- HEADLINE --}}

                <a
                    href="{{ route('admin.news.show', $item->id) }}"
                    style="
                        text-decoration:none;
                        color:inherit;
                    "
                >

                    <div
                        style="
                            font-size:15px;
                            font-weight:650;
                            margin-bottom:6px;
                        "
                    >
                        {{ $item->headline }}
                    </div>

                </a>


                {{-- SUMMARY --}}

                <div
                    class="text-sub"
                    style="
                        font-size:13px;
                        margin-bottom:6px;
                    "
                >
                    {{ \Illuminate\Support\Str::limit(
                        $item->summary,
                        160
                    ) }}
                </div>


                {{-- WHY IT MATTERS --}}

                @if ($item->why_it_matters)

                    <div
                        style="
                            font-size:12.5px;
                            color:var(--brand-3);
                        "
                    >

                        <i
                            data-lucide="lightbulb"
                            style="
                                width:12px;
                                height:12px;
                                vertical-align:-2px;
                            "
                        ></i>

                        Why it matters:

                        {{ $item->why_it_matters }}

                    </div>

                @endif


                {{-- COMPANY / RELATED TOOLS --}}

                <div
                    class="flex items-center gap-8"
                    style="
                        margin-top:10px;
                        flex-wrap:wrap;
                    "
                >

                    @if ($item->company)

                        <span
                            class="badge-neutral badge"
                            style="border:none;"
                        >
                            {{ $item->company->name }}
                        </span>

                    @endif


                    @foreach ($item->related_tools ?? [] as $tool)

                        <span
                            class="badge-neutral badge"
                            style="border:none;"
                        >
                            {{ $tool }}
                        </span>

                    @endforeach

                </div>

            </div>


            {{-- IMPORTANCE --}}

            <div
                style="
                    text-align:right;
                    flex-shrink:0;
                "
            >

                <x-score-meter
                    :value="$item->importance"
                    :segments="6"
                />

                <div
                    class="cell-sub"
                    style="margin-top:4px;"
                >
                    Importance
                </div>

            </div>

        </div>


        <div class="divider"></div>


        {{-- ACTIONS --}}

        <div
            class="flex gap-8"
            style="
                flex-wrap:wrap;
            "
        >

            <form action="{{ route('admin.news.save', $item->id) }}" method="POST" style="margin:0;">
                @csrf
                <button type="submit" class="btn btn-ghost btn-sm">
                    <i data-lucide="bookmark"></i>
                    Save
                </button>
            </form>

            <a
                href="{{ route('admin.news.show', $item->id) }}"
                class="btn btn-ghost btn-sm"
            >
                <i data-lucide="eye"></i>
                View
            </a>


            <a
                href="{{ route('admin.news.edit', $item->id) }}"
                class="btn btn-ghost btn-sm"
            >
                <i data-lucide="pencil"></i>
                Edit
            </a>


            @if ($item->source_url)

                <a
                    href="{{ $item->source_url }}"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="btn btn-ghost btn-sm"
                >
                    <i data-lucide="external-link"></i>
                    View Source
                </a>

            @endif


            <form
                action="{{ route('admin.news.destroy', $item->id) }}"
                method="POST"
                onsubmit="return confirm('Delete this news item?')"
            >

                @csrf
                @method('DELETE')

                <button
                    type="submit"
                    class="btn btn-ghost btn-sm"
                >
                    <i data-lucide="trash-2"></i>
                    Delete
                </button>

            </form>

        </div>

    </div>


@empty

    <div
        class="card card-pad text-sub"
        style="
            text-align:center;
            padding:32px;
        "
    >
        No news items yet — add your first one.
    </div>

@endforelse

</div>


{{-- =========================================================
     CUSTOM PAGINATION
     Replaces Laravel default pagination to prevent the
     giant SVG arrow issue shown in the screenshot.
========================================================= --}}

@if ($items->total() > 0)

    <div class="news-pagination">


        {{-- RESULTS INFO --}}

        <div class="news-pagination__info">

            Showing

            <strong>
                {{ $items->firstItem() ?? 0 }}
            </strong>

            –

            <strong>
                {{ $items->lastItem() ?? 0 }}
            </strong>

            of

            <strong>
                {{ $items->total() }}
            </strong>

            results

        </div>


        {{-- PAGINATION CONTROLS --}}

        <div class="news-pagination__controls">


            {{-- PREVIOUS --}}

            @if ($items->onFirstPage())

                <span class="news-page-btn is-disabled">

                    <i data-lucide="chevron-left"></i>

                    Previous

                </span>

            @else

                <a
                    href="{{ $items->previousPageUrl() }}"
                    class="news-page-btn"
                >

                    <i data-lucide="chevron-left"></i>

                    Previous

                </a>

            @endif


            {{-- PAGE NUMBERS --}}

            <div class="news-page-numbers">

                @php

                    $current = $items->currentPage();

                    $last = $items->lastPage();

                    $start = max(
                        1,
                        $current - 2
                    );

                    $end = min(
                        $last,
                        $current + 2
                    );

                @endphp


                {{-- FIRST PAGE --}}

                @if ($start > 1)

                    <a
                        href="{{ $items->url(1) }}"
                        class="news-page-number"
                    >
                        1
                    </a>


                    @if ($start > 2)

                        <span class="news-page-dots">
                            ...
                        </span>

                    @endif

                @endif


                {{-- CURRENT PAGE RANGE --}}

                @for (
                    $page = $start;
                    $page <= $end;
                    $page++
                )

                    @if ($page == $current)

                        <span
                            class="news-page-number is-active"
                        >
                            {{ $page }}
                        </span>

                    @else

                        <a
                            href="{{ $items->url($page) }}"
                            class="news-page-number"
                        >
                            {{ $page }}
                        </a>

                    @endif

                @endfor


                {{-- LAST PAGE --}}

                @if ($end < $last)

                    @if ($end < $last - 1)

                        <span class="news-page-dots">
                            ...
                        </span>

                    @endif


                    <a
                        href="{{ $items->url($last) }}"
                        class="news-page-number"
                    >
                        {{ $last }}
                    </a>

                @endif

            </div>


            {{-- NEXT --}}

            @if ($items->hasMorePages())

                <a
                    href="{{ $items->nextPageUrl() }}"
                    class="news-page-btn"
                >

                    Next

                    <i data-lucide="chevron-right"></i>

                </a>

            @else

                <span class="news-page-btn is-disabled">

                    Next

                    <i data-lucide="chevron-right"></i>

                </span>

            @endif

        </div>

    </div>

@endif


@endsection