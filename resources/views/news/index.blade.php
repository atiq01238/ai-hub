@extends('layouts.admin')
@section('title', 'AI News Intelligence')

@section('content')

<style>
    /* AI HUB — ADVANCED NEWS INTELLIGENCE LIST
       UI-only enhancement. Existing routes, variables,
       filters, actions and backend logic are preserved. */

    .news-center {
        --nc-border: var(--border-soft, rgba(148,163,184,.14));
        --nc-text: var(--text, #eef2ff);
        --nc-muted: var(--muted, #8d98ad);
        --nc-blue: #6d8cff;
        --nc-cyan: #22d3ee;
        --nc-green: #32d583;
        --nc-red: #f97068;
        --nc-orange: #f5a524;
    }

    .news-center__hero {
        position: relative;
        overflow: hidden;
        display: flex;
        align-items: flex-end;
        justify-content: space-between;
        gap: 20px;
        min-height: 122px;
        margin-bottom: 17px;
        padding: 21px 22px;
        border: 1px solid var(--nc-border);
        border-radius: 17px;
        background:
            radial-gradient(circle at 88% 10%, rgba(109,140,255,.18), transparent 28%),
            radial-gradient(circle at 63% 120%, rgba(34,211,238,.08), transparent 27%),
            linear-gradient(145deg, rgba(255,255,255,.045), rgba(255,255,255,.012));
        box-shadow: 0 16px 42px rgba(0,0,0,.09);
    }

    .news-center__hero:before {
        content: "";
        position: absolute;
        right: -45px;
        bottom: -95px;
        width: 260px;
        height: 260px;
        border: 1px solid rgba(109,140,255,.13);
        border-radius: 50%;
        box-shadow: 0 0 0 30px rgba(109,140,255,.025), 0 0 0 62px rgba(109,140,255,.012);
        pointer-events: none;
    }

    .news-center__hero-content,
    .news-center__hero-actions {
        position: relative;
        z-index: 1;
    }

    .news-center__eyebrow {
        display: flex;
        align-items: center;
        gap: 7px;
        margin-bottom: 7px;
        color: var(--nc-cyan);
        font-size: 9px;
        font-weight: 800;
        letter-spacing: .14em;
        text-transform: uppercase;
    }

    .news-center__live {
        width: 6px;
        height: 6px;
        border-radius: 50%;
        background: var(--nc-green);
        box-shadow: 0 0 0 4px rgba(50,213,131,.10);
    }

    .news-center__hero-title {
        margin: 0;
        font-size: clamp(22px, 2.5vw, 29px);
        line-height: 1.15;
        letter-spacing: -.035em;
        font-weight: 800;
    }

    .news-center__hero-sub {
        margin: 7px 0 0;
        color: var(--nc-muted);
        font-size: 10.5px;
    }

    .news-center__hero-actions {
        display: flex;
        gap: 8px;
        flex-shrink: 0;
    }

    .news-center__hero-actions .btn {
        min-height: 36px;
        border-radius: 9px;
    }

    .news-center__notice {
        display: flex;
        align-items: center;
        gap: 9px;
        margin-bottom: 14px;
        padding: 11px 13px;
        border-radius: 10px;
        font-size: 10.5px;
    }

    .news-center__notice i {
        width: 15px;
        height: 15px;
        flex: 0 0 15px;
    }

    .news-center__notice.success {
        border: 1px solid rgba(50,213,131,.18);
        color: #79dfa5;
        background: rgba(50,213,131,.055);
    }

    .news-center__notice.warning {
        border: 1px solid rgba(245,165,36,.20);
        color: #ffc86b;
        background: rgba(245,165,36,.055);
    }

    .news-center__filters {
        display: grid;
        grid-template-columns: minmax(220px, 1.5fr) minmax(160px, .7fr) minmax(160px, .7fr) auto auto;
        gap: 8px;
        align-items: center;
        margin-bottom: 17px;
        padding: 10px;
        border: 1px solid var(--nc-border);
        border-radius: 13px;
        background: rgba(255,255,255,.018);
        box-shadow: 0 10px 30px rgba(0,0,0,.055);
    }

    .news-center__search {
        min-height: 38px;
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 0 11px;
        border: 1px solid var(--nc-border);
        border-radius: 9px;
        background: rgba(255,255,255,.025);
    }

    .news-center__search:focus-within {
        border-color: rgba(109,140,255,.50);
        box-shadow: 0 0 0 3px rgba(109,140,255,.07);
    }

    .news-center__search i {
        width: 14px;
        height: 14px;
        color: var(--nc-muted);
        flex: 0 0 14px;
    }

    .news-center__search input {
        width: 100%;
        min-width: 0;
        border: 0;
        outline: 0;
        color: var(--nc-text);
        background: transparent;
        font-size: 10.5px;
    }

    .news-center__search input::placeholder {
        color: #68748a;
    }

    .news-center__filters .select {
        min-height: 38px;
        border-radius: 9px;
        border-color: var(--nc-border);
        background-color: rgba(255,255,255,.025);
    }

    /* Native dropdown readability */
    .news-center__filters select.select {
        color-scheme: dark;
        color: var(--nc-text);
    }

    .news-center__filters select.select option,
    .news-center__filters select.select optgroup {
        color: #182033;
        background: #fff;
    }

    .news-center__filters select.select option:checked,
    .news-center__filters select.select option:hover {
        color: #fff;
        background: #536ff0;
    }

    .news-center__filter-btn {
        min-height: 38px;
        border-radius: 9px;
    }

    .news-center__clear {
        white-space: nowrap;
    }

    .news-center__list {
        display: flex;
        flex-direction: column;
        gap: 11px;
    }

    .news-center__item {
        position: relative;
        overflow: hidden;
        border: 1px solid var(--nc-border);
        border-radius: 14px;
        background: linear-gradient(145deg, rgba(255,255,255,.035), rgba(255,255,255,.012));
        box-shadow: 0 10px 28px rgba(0,0,0,.055);
        transition: transform .2s ease, border-color .2s ease, box-shadow .2s ease;
    }

    .news-center__item:hover {
        transform: translateY(-1px);
        border-color: rgba(109,140,255,.23);
        box-shadow: 0 15px 35px rgba(0,0,0,.10);
    }

    .news-center__item-main {
        display: grid;
        grid-template-columns: 50px minmax(0, 1fr) 105px;
        gap: 13px;
        align-items: start;
        padding: 15px;
    }

    .news-center__thumb {
        position: relative;
        width: 50px;
        height: 50px;
        display: flex;
        align-items: center;
        justify-content: center;
        overflow: hidden;
        border: 1px solid rgba(109,140,255,.18);
        border-radius: 12px;
        color: #b8c4ff;
        background:
            radial-gradient(circle at 25% 20%, rgba(109,140,255,.24), transparent 55%),
            linear-gradient(145deg, rgba(109,140,255,.12), rgba(34,211,238,.045));
        font-size: 11px;
        font-weight: 850;
        letter-spacing: .04em;
    }

    .news-center__thumb:after {
        content: "";
        position: absolute;
        width: 6px;
        height: 6px;
        right: 6px;
        top: 6px;
        border-radius: 50%;
        background: var(--nc-green);
        box-shadow: 0 0 0 3px rgba(50,213,131,.09);
    }

    .news-center__meta {
        display: flex;
        align-items: center;
        gap: 6px;
        flex-wrap: wrap;
        margin-bottom: 7px;
    }

    .news-center__meta .badge {
        font-size: 8.5px;
        line-height: 1.2;
    }

    .news-center__source {
        color: var(--nc-muted);
        font-size: 8.5px;
        white-space: nowrap;
    }

    .news-center__headline {
        display: block;
        margin-bottom: 5px;
        color: var(--nc-text);
        text-decoration: none;
        font-size: 14px;
        line-height: 1.35;
        font-weight: 720;
        letter-spacing: -.012em;
    }

    .news-center__headline:hover {
        color: #aebcff;
    }

    .news-center__summary {
        margin-bottom: 6px;
        color: var(--nc-muted);
        font-size: 10px;
        line-height: 1.55;
    }

    .news-center__why {
        display: flex;
        gap: 6px;
        align-items: flex-start;
        color: #93a8ff;
        font-size: 9.5px;
        line-height: 1.45;
    }

    .news-center__why i {
        width: 12px;
        height: 12px;
        flex: 0 0 12px;
        margin-top: 1px;
    }

    .news-center__related {
        display: flex;
        gap: 5px;
        flex-wrap: wrap;
        margin-top: 9px;
    }

    .news-center__related .badge {
        border: 1px solid var(--nc-border) !important;
        font-size: 8px;
        background: rgba(255,255,255,.025);
    }

    .news-center__importance {
        display: flex;
        flex-direction: column;
        align-items: flex-end;
        gap: 4px;
        padding-top: 2px;
    }

    .news-center__importance-label {
        color: var(--nc-muted);
        font-size: 8px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .07em;
    }

    .news-center__divider {
        height: 1px;
        margin: 0 15px;
        background: var(--nc-border);
    }

    .news-center__actions {
        display: flex;
        align-items: center;
        gap: 6px;
        flex-wrap: wrap;
        padding: 10px 15px 12px;
    }

    .news-center__actions .btn {
        min-height: 31px;
        border-radius: 8px;
        font-size: 9px;
    }

    .news-center__actions .btn svg {
        width: 13px;
        height: 13px;
    }

    .news-center__delete {
        margin: 0;
    }

    .news-center__empty {
        padding: 42px 20px;
        text-align: center;
        border: 1px dashed rgba(148,163,184,.20);
        border-radius: 14px;
        background: rgba(255,255,255,.015);
    }

    .news-center__empty-icon {
        width: 44px;
        height: 44px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 10px;
        border: 1px solid var(--nc-border);
        border-radius: 12px;
        color: var(--nc-muted);
        background: rgba(255,255,255,.025);
    }

    .news-center__empty-icon svg {
        width: 19px;
        height: 19px;
    }

    .news-center__empty-title {
        margin-bottom: 4px;
        color: var(--nc-text);
        font-size: 12px;
        font-weight: 750;
    }

    .news-center__empty-text {
        color: var(--nc-muted);
        font-size: 9.5px;
    }

    .news-center__pager {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        margin-top: 14px;
        color: var(--nc-muted);
        font-size: 9.5px;
    }

    .news-center__pager .pager-btns {
        min-width: 0;
    }

    @media (max-width: 1000px) {
        .news-center__filters {
            grid-template-columns: 1fr 1fr;
        }

        .news-center__search {
            grid-column: 1 / -1;
        }

        .news-center__filter-btn {
            justify-self: start;
        }
    }

    @media (max-width: 720px) {
        .news-center__hero {
            align-items: flex-start;
            flex-direction: column;
        }

        .news-center__hero-actions {
            width: 100%;
        }

        .news-center__hero-actions .btn {
            flex: 1;
        }

        .news-center__filters {
            grid-template-columns: 1fr;
        }

        .news-center__search {
            grid-column: auto;
        }

        .news-center__item-main {
            grid-template-columns: 43px minmax(0, 1fr);
        }

        .news-center__thumb {
            width: 43px;
            height: 43px;
        }

        .news-center__importance {
            grid-column: 2;
            align-items: flex-start;
            padding-top: 0;
        }

        .news-center__pager {
            align-items: flex-start;
            flex-direction: column;
        }
    }

    @media (max-width: 500px) {
        .news-center__hero-actions {
            flex-direction: column;
        }

        .news-center__hero-actions .btn {
            width: 100%;
        }

        .news-center__item-main {
            gap: 9px;
            padding: 12px;
        }

        .news-center__divider {
            margin: 0 12px;
        }

        .news-center__actions {
            padding: 9px 12px 11px;
        }
    }
</style>

<div class="news-center">

    {{-- =================================================
         HERO
         ================================================= --}}
    <section class="news-center__hero">
        <div class="news-center__hero-content">
            <div class="news-center__eyebrow">
                <span class="news-center__live"></span>
                AI Intelligence · Live News Operations
            </div>

            <h1 class="news-center__hero-title">AI News Intelligence Center</h1>

            <p class="news-center__hero-sub">
                {{ $items->total() }} news items · Monitor, verify and manage your AI intelligence feed.
            </p>
        </div>

        <div class="news-center__hero-actions">
            <a href="{{ route('admin.news.duplicates') }}" class="btn btn-secondary btn-sm">
                <i data-lucide="copy"></i>
                Duplicates
            </a>

            <a href="{{ route('admin.news.create') }}" class="btn btn-primary btn-sm">
                <i data-lucide="plus"></i>
                Add News
            </a>
        </div>
    </section>

    {{-- =================================================
         NOTICES
         ================================================= --}}
    @if (session('status'))
        <div class="news-center__notice success">
            <i data-lucide="circle-check"></i>
            <span>{{ session('status') }}</span>
        </div>
    @endif

    @isset($notice)
        <div class="news-center__notice warning">
            <i data-lucide="triangle-alert"></i>
            <span>{{ $notice }}</span>
        </div>
    @endisset

    {{-- =================================================
         FILTER BAR
         ================================================= --}}
    <form method="GET" class="news-center__filters">

        <div class="news-center__search">
            <i data-lucide="search"></i>
            <input
                type="text"
                name="search"
                value="{{ request('search') }}"
                placeholder="Search headlines..."
            >
        </div>

        <select class="select" name="category" onchange="this.form.submit()">
            <option value="">All Categories</option>

            @foreach (['Breaking News','New Model','Product Launch','Product Update','New Feature','Pricing Change','AI Review','Benchmark','Research','Funding','Acquisition','Security','Policy','Regulation'] as $cat)
                <option value="{{ $cat }}" @selected(request('category') === $cat)>
                    {{ $cat }}
                </option>
            @endforeach
        </select>

        <select class="select" name="company_id" onchange="this.form.submit()">
            <option value="">All Companies</option>

            @foreach ($companies as $company)
                <option
                    value="{{ $company->id }}"
                    @selected((string) request('company_id') === (string) $company->id)
                >
                    {{ $company->name }}
                </option>
            @endforeach
        </select>

        <button type="submit" class="btn btn-secondary btn-sm news-center__filter-btn">
            <i data-lucide="sliders-horizontal"></i>
            Filter
        </button>

        @if (request()->anyFilled(['search', 'category', 'company_id']))
            <a href="{{ route('admin.news.index') }}" class="btn btn-ghost btn-sm news-center__clear">
                Clear
            </a>
        @endif

    </form>

    {{-- =================================================
         NEWS LIST
         ================================================= --}}
    <div class="news-center__list">

        @forelse ($items as $item)

            <article class="news-center__item">

                <div class="news-center__item-main">

                    <div class="news-center__thumb">
                        {{ substr($item->source ?? $item->headline, 0, 2) }}
                    </div>

                    <div style="min-width:0;">

                        <div class="news-center__meta">

                            @if ($item->category)
                                <span class="badge badge-neutral">{{ $item->category }}</span>
                            @endif

                            <span class="badge badge-{{ $item->sentiment === 'positive' ? 'pos' : ($item->sentiment === 'negative' ? 'neg' : 'neutral') }}">
                                {{ ucfirst($item->sentiment) }}
                            </span>

                            <span class="badge {{ $item->verification_status === 'verified' ? 'badge-pos' : ($item->verification_status === 'unverified' ? 'badge-neg' : 'badge-warn') }}">
                                {{ str_replace('_', ' ', ucfirst($item->verification_status)) }}
                            </span>

                            <span class="news-center__source">
                                {{ $item->source ?? 'Unknown source' }}
                                ·
                                {{ $item->published_at?->diffForHumans() ?? ucfirst($item->status) }}
                            </span>

                        </div>

                        <a href="{{ route('admin.news.show', $item->id) }}" class="news-center__headline">
                            {{ $item->headline }}
                        </a>

                        <div class="news-center__summary">
                            {{ \Illuminate\Support\Str::limit($item->summary, 160) }}
                        </div>

                        @if ($item->why_it_matters)
                            <div class="news-center__why">
                                <i data-lucide="lightbulb"></i>
                                <span>
                                    <strong>Why it matters:</strong>
                                    {{ $item->why_it_matters }}
                                </span>
                            </div>
                        @endif

                        <div class="news-center__related">

                            @if ($item->company)
                                <span class="badge-neutral badge">
                                    {{ $item->company->name }}
                                </span>
                            @endif

                            @foreach ($item->related_tools ?? [] as $tool)
                                <span class="badge-neutral badge">
                                    {{ $tool }}
                                </span>
                            @endforeach

                        </div>

                    </div>

                    <div class="news-center__importance">
                        <x-score-meter :value="$item->importance" :segments="6" />
                        <span class="news-center__importance-label">Importance</span>
                    </div>

                </div>

                <div class="news-center__divider"></div>

                <div class="news-center__actions">

                    <a href="{{ route('admin.news.show', $item->id) }}" class="btn btn-ghost btn-sm">
                        <i data-lucide="eye"></i>
                        View
                    </a>

                    <a href="{{ route('admin.news.edit', $item->id) }}" class="btn btn-ghost btn-sm">
                        <i data-lucide="pencil"></i>
                        Edit
                    </a>

                    @if ($item->source_url)
                        <a
                            href="{{ $item->source_url }}"
                            target="_blank"
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
                        class="news-center__delete"
                    >
                        @csrf
                        @method('DELETE')

                        <button type="submit" class="btn btn-ghost btn-sm">
                            <i data-lucide="trash-2"></i>
                            Delete
                        </button>
                    </form>

                </div>

            </article>

        @empty

            <div class="news-center__empty">
                <div class="news-center__empty-icon">
                    <i data-lucide="newspaper"></i>
                </div>

                <div class="news-center__empty-title">
                    No news items found
                </div>

                <div class="news-center__empty-text">
                    No news items match the current filters — add your first one or clear the filters.
                </div>
            </div>

        @endforelse

    </div>

    {{-- =================================================
         PAGINATION
         ================================================= --}}
    <div class="news-center__pager">
        <span>
            Showing {{ $items->firstItem() ?? 0 }}–{{ $items->lastItem() ?? 0 }}
            of {{ $items->total() }}
        </span>

        <div class="pager-btns">
            {{ $items->links() }}
        </div>
    </div>

</div>

@endsection