@extends('layouts.admin')
@section('title', 'AI Companies')

@section('content')

<style>
    /* AI HUB — ADVANCED COMPANY MANAGEMENT
       UI-only enhancement. Existing routes, variables,
       pagination, actions and backend behaviour preserved. */

    .companies-page {
        --cm-border: var(--border-soft, rgba(148,163,184,.14));
        --cm-text: var(--text, #eef2ff);
        --cm-muted: var(--muted, #8d98ad);
        --cm-blue: #6d8cff;
        --cm-cyan: #22d3ee;
        --cm-green: #32d583;
        --cm-orange: #f5a524;
    }

    .companies-page__hero {
        position: relative;
        overflow: hidden;
        display: flex;
        align-items: flex-end;
        justify-content: space-between;
        gap: 20px;
        margin-bottom: 15px;
        padding: 20px 21px;
        border: 1px solid var(--cm-border);
        border-radius: 17px;
        background:
            radial-gradient(circle at 91% 8%, rgba(109,140,255,.17), transparent 28%),
            radial-gradient(circle at 63% 120%, rgba(34,211,238,.06), transparent 28%),
            linear-gradient(145deg, rgba(255,255,255,.045), rgba(255,255,255,.012));
        box-shadow: 0 16px 42px rgba(0,0,0,.07);
    }

    .companies-page__hero:after {
        content: "";
        position: absolute;
        width: 245px;
        height: 245px;
        right: -105px;
        top: -150px;
        border: 1px solid rgba(109,140,255,.11);
        border-radius: 50%;
        box-shadow: 0 0 0 28px rgba(109,140,255,.025), 0 0 0 56px rgba(109,140,255,.012);
        pointer-events: none;
    }

    .companies-page__hero-copy,
    .companies-page__hero-actions {
        position: relative;
        z-index: 1;
    }

    .companies-page__eyebrow {
        display: flex;
        align-items: center;
        gap: 7px;
        margin-bottom: 7px;
        color: var(--cm-cyan);
        font-size: 8.5px;
        font-weight: 800;
        letter-spacing: .13em;
        text-transform: uppercase;
    }

    .companies-page__live {
        width: 6px;
        height: 6px;
        border-radius: 50%;
        background: var(--cm-green);
        box-shadow: 0 0 0 4px rgba(50,213,131,.10);
    }

    .companies-page__title {
        margin: 0;
        color: var(--cm-text);
        font-size: clamp(22px, 3vw, 29px);
        line-height: 1.15;
        letter-spacing: -.035em;
        font-weight: 800;
    }

    .companies-page__subtitle {
        margin: 7px 0 0;
        color: var(--cm-muted);
        font-size: 9px;
    }

    .companies-page__hero-actions .btn {
        min-height: 37px;
        border-radius: 9px;
    }

    .companies-page__notice {
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

    .companies-page__notice svg {
        width: 14px;
        height: 14px;
    }

    .companies-page__toolbar {
        display: grid;
        grid-template-columns: minmax(220px, 1fr) 165px auto;
        align-items: center;
        gap: 8px;
        margin-bottom: 13px;
        padding: 8px;
        border: 1px solid var(--cm-border);
        border-radius: 12px;
        background: rgba(255,255,255,.022);
    }

    .companies-page__search {
        position: relative;
        display: flex;
        align-items: center;
        min-height: 38px;
        border: 1px solid var(--cm-border);
        border-radius: 9px;
        background: rgba(255,255,255,.025);
    }

    .companies-page__search svg {
        width: 14px;
        height: 14px;
        margin-left: 11px;
        color: var(--cm-muted);
        flex: 0 0 14px;
    }

    .companies-page__search input {
        width: 100%;
        height: 36px;
        border: 0;
        outline: 0;
        padding: 0 10px 0 8px;
        color: var(--cm-text);
        background: transparent;
        font-size: 9.5px;
    }

    .companies-page__search input::placeholder {
        color: #68748a;
    }

    .companies-page__toolbar .select {
        min-height: 38px;
        border-radius: 9px;
        color: var(--cm-text);
        color-scheme: dark;
    }

    .companies-page__toolbar .select option {
        color: #182033;
        background: #fff;
    }

    .companies-page__toolbar .select option:checked,
    .companies-page__toolbar .select option:hover {
        color: #fff;
        background: #536ff0;
    }

    .companies-page__filter-state {
        display: flex;
        align-items: center;
        gap: 6px;
        padding: 0 5px;
        color: var(--cm-muted);
        font-size: 8px;
        white-space: nowrap;
    }

    .companies-page__filter-state span {
        width: 5px;
        height: 5px;
        border-radius: 50%;
        background: var(--cm-blue);
        box-shadow: 0 0 0 3px rgba(109,140,255,.09);
    }

    .companies-page__table-card {
        overflow: hidden;
        border: 1px solid var(--cm-border);
        border-radius: 14px;
        background: linear-gradient(145deg, rgba(255,255,255,.035), rgba(255,255,255,.012));
        box-shadow: 0 12px 34px rgba(0,0,0,.055);
    }

    .companies-page__table-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
        padding: 13px 15px;
        border-bottom: 1px solid var(--cm-border);
    }

    .companies-page__table-title {
        display: flex;
        align-items: center;
        gap: 8px;
        color: var(--cm-text);
        font-size: 10px;
        font-weight: 800;
    }

    .companies-page__table-title-icon {
        width: 26px;
        height: 26px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border: 1px solid rgba(109,140,255,.16);
        border-radius: 8px;
        color: #9eafff;
        background: rgba(109,140,255,.07);
    }

    .companies-page__table-title-icon svg {
        width: 13px;
        height: 13px;
    }

    .companies-page__count {
        color: var(--cm-muted);
        font-size: 8px;
    }

    .companies-page__table-wrap {
        overflow-x: auto;
    }

    .companies-page .data-table {
        min-width: 790px;
    }

    .companies-page .data-table thead th {
        padding-top: 11px;
        padding-bottom: 11px;
        color: #7e8aa1;
        font-size: 7.5px;
        letter-spacing: .08em;
        text-transform: uppercase;
        white-space: nowrap;
    }

    .companies-page .data-table tbody tr {
        transition: background .16s ease;
    }

    .companies-page .data-table tbody tr:hover {
        background: rgba(109,140,255,.025);
    }

    .companies-page .data-table tbody td {
        padding-top: 12px;
        padding-bottom: 12px;
    }

    .companies-page__company {
        display: flex;
        align-items: center;
        gap: 10px;
        min-width: 175px;
    }

    .companies-page__logo {
        position: relative;
        width: 39px;
        height: 39px;
        display: flex;
        align-items: center;
        justify-content: center;
        flex: 0 0 39px;
        overflow: hidden;
        border: 1px solid rgba(109,140,255,.16);
        border-radius: 11px;
        color: #aab8ff;
        background:
            radial-gradient(circle at 25% 20%, rgba(109,140,255,.20), transparent 60%),
            rgba(109,140,255,.055);
        font-size: 9px;
        font-weight: 800;
        text-transform: uppercase;
    }

    .companies-page__logo img {
        width: 100%;
        height: 100%;
        display: block;
        object-fit: cover;
    }

    .companies-page__company-name {
        min-width: 0;
    }

    .companies-page__company-name a {
        display: block;
        overflow: hidden;
        color: var(--cm-text);
        font-size: 10px;
        font-weight: 700;
        text-decoration: none;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .companies-page__company-name a:hover {
        color: #aebaff;
    }

    .companies-page__company-meta {
        margin-top: 3px;
        color: var(--cm-muted);
        font-size: 7.5px;
    }

    .companies-page__website {
        display: inline-block;
        max-width: 190px;
        overflow: hidden;
        color: #96a3ba;
        font-size: 8.5px;
        text-overflow: ellipsis;
        white-space: nowrap;
        vertical-align: middle;
    }

    .companies-page__tools {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 31px;
        height: 24px;
        padding: 0 7px;
        border: 1px solid var(--cm-border);
        border-radius: 7px;
        color: #b5c0d4;
        background: rgba(255,255,255,.022);
        font-size: 8.5px;
        font-weight: 750;
    }

    .companies-page__updated {
        color: var(--cm-muted);
        font-size: 8px;
        white-space: nowrap;
    }

    .companies-page__actions {
        display: flex;
        align-items: center;
        justify-content: flex-end;
        gap: 5px;
    }

    .companies-page__action {
        width: 29px !important;
        height: 29px !important;
        border: 1px solid var(--cm-border) !important;
        border-radius: 8px !important;
        color: #9da9bf;
        background: rgba(255,255,255,.018);
        transition: .16s ease;
    }

    .companies-page__action:hover {
        color: #b9c4ff;
        border-color: rgba(109,140,255,.30) !important;
        background: rgba(109,140,255,.07);
    }

    .companies-page__action svg {
        width: 13px !important;
        height: 13px !important;
    }

    .companies-page__empty {
        padding: 45px 20px !important;
        text-align: center;
    }

    .companies-page__empty-icon {
        width: 45px;
        height: 45px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 10px;
        border: 1px solid var(--cm-border);
        border-radius: 13px;
        color: #8fa0c4;
        background: rgba(255,255,255,.025);
    }

    .companies-page__empty-icon svg {
        width: 19px;
        height: 19px;
    }

    .companies-page__empty-title {
        color: var(--cm-text);
        font-size: 11px;
        font-weight: 750;
    }

    .companies-page__empty-sub {
        margin-top: 4px;
        color: var(--cm-muted);
        font-size: 8.5px;
    }

    .companies-page__pager {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
        padding: 12px 15px;
        border-top: 1px solid var(--cm-border);
        color: var(--cm-muted);
        font-size: 8px;
    }

    .companies-page__pager .pager-btns {
        margin-left: auto;
    }

    @media (max-width: 800px) {
        .companies-page__hero {
            align-items: flex-start;
            flex-direction: column;
        }

        .companies-page__hero-actions,
        .companies-page__hero-actions .btn {
            width: 100%;
        }

        .companies-page__toolbar {
            grid-template-columns: 1fr;
        }

        .companies-page__filter-state {
            padding: 3px 4px;
        }
    }

    @media (max-width: 550px) {
        .companies-page__hero {
            padding: 17px;
        }

        .companies-page__title {
            font-size: 22px;
        }

        .companies-page__pager {
            align-items: flex-start;
            flex-direction: column;
        }

        .companies-page__pager .pager-btns {
            margin-left: 0;
        }
    }
</style>

<div class="companies-page">

    {{-- HERO --}}
    <section class="companies-page__hero">

        <div class="companies-page__hero-copy">
            <div class="companies-page__eyebrow">
                <span class="companies-page__live"></span>
                AI Management · Company Intelligence
            </div>

            <h1 class="companies-page__title">
                AI Company Management
            </h1>

            <p class="companies-page__subtitle">
                {{ $companies->total() }} companies tracked across your AI intelligence registry
            </p>
        </div>

        <div class="companies-page__hero-actions">
            <a href="{{ route('admin.companies.create') }}" class="btn btn-primary btn-sm">
                <i data-lucide="plus"></i>
                Add Company
            </a>
        </div>

    </section>

    @if (session('status'))
        <div class="companies-page__notice">
            <i data-lucide="circle-check"></i>
            <span>{{ session('status') }}</span>
        </div>
    @endif

    {{-- FILTER TOOLBAR --}}
    <div class="companies-page__toolbar">

        <div class="companies-page__search">
            <i data-lucide="search"></i>
            <input type="text" placeholder="Search companies...">
        </div>

        <select class="select">
            <option>All Status</option>
            <option>Active</option>
            <option>Acquired</option>
            <option>Inactive</option>
        </select>

        <div class="companies-page__filter-state">
            <span></span>
            Registry synchronized
        </div>

    </div>

    {{-- COMPANY TABLE --}}
    <section class="companies-page__table-card">

        <div class="companies-page__table-head">
            <div class="companies-page__table-title">
                <span class="companies-page__table-title-icon">
                    <i data-lucide="building-2"></i>
                </span>
                Company Registry
            </div>

            <div class="companies-page__count">
                {{ $companies->total() }} records
            </div>
        </div>

        <div class="companies-page__table-wrap">

            <table class="data-table">

                <thead>
                    <tr>
                        <th>Company</th>
                        <th>Website</th>
                        <th>Tools</th>
                        <th>Latest Update</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>

                <tbody>

                @forelse ($companies as $company)

                    <tr>

                        <td>
                            <div class="companies-page__company">

                                @if ($company->logo_path)

                                    <div class="companies-page__logo">
                                        <img
                                            src="{{ \Illuminate\Support\Facades\Storage::url($company->logo_path) }}"
                                            alt=""
                                        >
                                    </div>

                                @else

                                    <div class="companies-page__logo">
                                        {{ substr($company->name, 0, 2) }}
                                    </div>

                                @endif

                                <div class="companies-page__company-name">

                                    <a href="{{ route('admin.companies.show', $company->id) }}">
                                        {{ $company->name }}
                                    </a>

                                    <div class="companies-page__company-meta">
                                        AI company profile
                                    </div>

                                </div>

                            </div>
                        </td>

                        <td>
                            @if ($company->website)
                                <span class="companies-page__website">
                                    {{ $company->website }}
                                </span>
                            @else
                                <span class="text-sub">—</span>
                            @endif
                        </td>

                        <td>
                            <span class="companies-page__tools">
                                {{ $company->tools_count }}
                            </span>
                        </td>

                        <td>
                            <span class="companies-page__updated">
                                {{ $company->updated_at->diffForHumans() }}
                            </span>
                        </td>

                        <td>
                            <x-status-badge
                                status="{{ ucfirst($company->status) }}"
                                type="{{ $company->status === 'active' ? 'pos' : ($company->status === 'inactive' ? 'neutral' : 'warn') }}"
                            />
                        </td>

                        <td>

                            <div class="companies-page__actions">

                                <a
                                    href="{{ route('admin.companies.edit', $company->id) }}"
                                    class="icon-btn companies-page__action"
                                    title="Edit company"
                                >
                                    <i data-lucide="pencil"></i>
                                </a>

                                <form
                                    action="{{ route('admin.companies.destroy', $company->id) }}"
                                    method="POST"
                                    onsubmit="return confirm('Delete this company?')"
                                    style="display:inline;"
                                >
                                    @csrf
                                    @method('DELETE')

                                    <button
                                        type="submit"
                                        class="icon-btn companies-page__action"
                                        title="Delete company"
                                    >
                                        <i data-lucide="trash-2"></i>
                                    </button>

                                </form>

                            </div>

                        </td>

                    </tr>

                @empty

                    <tr>
                        <td colspan="6" class="text-sub companies-page__empty">

                            <div class="companies-page__empty-icon">
                                <i data-lucide="building-2"></i>
                            </div>

                            <div class="companies-page__empty-title">
                                No companies yet
                            </div>

                            <div class="companies-page__empty-sub">
                                Add your first AI company to start building the intelligence registry.
                            </div>

                        </td>
                    </tr>

                @endforelse

                </tbody>

            </table>

        </div>

        {{-- PAGINATION --}}
        <div class="companies-page__pager">

            <span>
                Showing
                {{ $companies->firstItem() ?? 0 }}–{{ $companies->lastItem() ?? 0 }}
                of
                {{ $companies->total() }}
                companies
            </span>

            <div class="pager-btns">
                {{ $companies->links() }}
            </div>

        </div>

    </section>

</div>

@endsection