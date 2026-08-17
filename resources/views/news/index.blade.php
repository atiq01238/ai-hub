@extends('layouts.admin')
@section('title', 'AI News Intelligence')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/pages/news.css') }}">
@endpush

@section('content')
@php
    $currentRoute = request()->route()?->getName();
    $tabs = [
        ['route' => 'admin.news.index', 'label' => 'All News', 'icon' => 'newspaper'],
        ['route' => 'admin.news.breaking', 'label' => 'Breaking', 'icon' => 'radio'],
        ['route' => 'admin.news.trending', 'label' => 'Trending', 'icon' => 'trending-up'],
        ['route' => 'admin.news.updates', 'label' => 'Updates', 'icon' => 'refresh-cw'],
        ['route' => 'admin.news.saved', 'label' => 'Saved', 'icon' => 'bookmark'],
    ];
@endphp

<div class="news-shell">
    <x-page-header
        title="AI News Intelligence"
        subtitle="Monitor, classify and verify AI industry developments from one command center."
        :breadcrumb="['AI Intelligence', 'News']"
    >
        <x-slot:actions>
            <form action="{{ route('admin.news.fetch-now') }}" method="POST" class="news-inline-form">
                @csrf
                <button type="submit" class="btn btn-secondary btn-sm">
                    <i data-lucide="refresh-cw"></i>
                    Fetch Now
                </button>
            </form>
            <a href="{{ route('admin.news.duplicates') }}" class="btn btn-secondary btn-sm">
                <i data-lucide="copy-check"></i>
                Duplicates
            </a>
            <a href="{{ route('admin.news.create') }}" class="btn btn-primary btn-sm">
                <i data-lucide="plus"></i>
                Add News
            </a>
        </x-slot:actions>
    </x-page-header>

    @if (session('error'))
        <div class="alert alert-danger news-alert"><i data-lucide="triangle-alert"></i><span>{{ session('error') }}</span></div>
    @endif

    @if (session('status'))
        <div class="alert alert-success news-alert"><i data-lucide="circle-check"></i><span>{{ session('status') }}</span></div>
    @endif

    @isset($notice)
        <div class="alert alert-warning news-alert"><i data-lucide="info"></i><span>{{ $notice }}</span></div>
    @endisset

    <section class="news-command-card">
        <div class="news-tabs" aria-label="News views">
            @foreach ($tabs as $tab)
                <a href="{{ route($tab['route']) }}" class="news-tab {{ $currentRoute === $tab['route'] ? 'is-active' : '' }}">
                    <i data-lucide="{{ $tab['icon'] }}"></i>
                    <span>{{ $tab['label'] }}</span>
                </a>
            @endforeach
        </div>

        <form method="GET" class="news-filterbar">
            <div class="news-search">
                <i data-lucide="search"></i>
                <input type="search" name="search" value="{{ request('search') }}" placeholder="Search headline, summary or source…">
            </div>

            <select class="select" name="category" aria-label="Filter by category">
                <option value="">All categories</option>
                @foreach (['Breaking News','New Model','Product Launch','Product Update','New Feature','Pricing Change','AI Review','Benchmark','Research','Funding','Acquisition','Security','Policy','Regulation'] as $cat)
                    <option value="{{ $cat }}" @selected(request('category') === $cat)>{{ $cat }}</option>
                @endforeach
            </select>

            <select class="select" name="company_id" aria-label="Filter by company">
                <option value="">All companies</option>
                @foreach ($companies as $company)
                    <option value="{{ $company->id }}" @selected((string) request('company_id') === (string) $company->id)>{{ $company->name }}</option>
                @endforeach
            </select>

            <button type="submit" class="btn btn-secondary btn-sm"><i data-lucide="sliders-horizontal"></i> Apply</button>

            @if (request()->anyFilled(['search','category','company_id']))
                <a href="{{ url()->current() }}" class="btn btn-ghost btn-sm"><i data-lucide="x"></i> Clear</a>
            @endif
        </form>

        <div class="news-results-meta">
            <div>
                <strong>{{ number_format($items->total()) }}</strong>
                <span>intelligence record{{ $items->total() === 1 ? '' : 's' }}</span>
            </div>
            <span>Showing {{ $items->firstItem() ?? 0 }}–{{ $items->lastItem() ?? 0 }}</span>
        </div>
    </section>

    <div class="news-feed">
        @forelse ($items as $item)
            @php
                $sentimentClass = $item->sentiment === 'positive' ? 'badge-pos' : ($item->sentiment === 'negative' ? 'badge-neg' : 'badge-neutral');
                $verificationClass = $item->verification_status === 'verified' ? 'badge-pos' : ($item->verification_status === 'unverified' ? 'badge-neg' : 'badge-warn');
            @endphp

            <article class="news-card">
                <div class="news-card__rail" aria-hidden="true"></div>

                <div class="news-card__body">
                    <div class="news-card__topline">
                        <div class="news-card__badges">
                            @if ($item->category)
                                <span class="badge badge-neutral">{{ $item->category }}</span>
                            @endif
                            <span class="badge {{ $sentimentClass }}">{{ ucfirst($item->sentiment) }}</span>
                            <span class="badge {{ $verificationClass }}">{{ str_replace('_', ' ', ucfirst($item->verification_status)) }}</span>
                        </div>
                        <div class="news-card__time">
                            <i data-lucide="clock-3"></i>
                            {{ $item->published_at?->diffForHumans() ?? ucfirst($item->status) }}
                        </div>
                    </div>

                    <div class="news-card__content-grid">
                        <div class="news-card__identity">
                            <div class="news-source-avatar">{{ strtoupper(substr($item->source ?? $item->headline, 0, 2)) }}</div>
                            <div>
                                <div class="news-card__source">{{ $item->source ?? 'Unknown source' }}</div>
                                @if ($item->company)
                                    <div class="news-card__company">{{ $item->company->name }}</div>
                                @endif
                            </div>
                        </div>

                        <div class="news-card__story">
                            <a href="{{ route('admin.news.show', $item->id) }}" class="news-card__headline">{{ $item->headline }}</a>

                            @if ($item->summary)
                                <p class="news-card__summary">{{ \Illuminate\Support\Str::limit($item->summary, 190) }}</p>
                            @endif

                            @if ($item->why_it_matters)
                                <div class="news-card__why">
                                    <i data-lucide="lightbulb"></i>
                                    <span><strong>Why it matters:</strong> {{ \Illuminate\Support\Str::limit($item->why_it_matters, 180) }}</span>
                                </div>
                            @endif

                            @if (!empty($item->related_tools))
                                <div class="news-card__tags">
                                    @foreach (array_slice($item->related_tools, 0, 5) as $tool)
                                        <span>{{ $tool }}</span>
                                    @endforeach
                                </div>
                            @endif
                        </div>

                        <div class="news-card__score">
                            <div class="news-card__score-number">{{ (int) $item->importance }}</div>
                            <div class="news-card__score-label">Importance</div>
                            <x-score-meter :value="$item->importance" :segments="6" />
                        </div>
                    </div>

                    <div class="news-card__footer">
                        <div class="news-card__actions">
                            <form action="{{ route('admin.news.save', $item->id) }}" method="POST" class="news-inline-form">
                                @csrf
                                <button type="submit" class="btn btn-ghost btn-sm"><i data-lucide="bookmark"></i> Save</button>
                            </form>
                            <a href="{{ route('admin.news.show', $item->id) }}" class="btn btn-ghost btn-sm"><i data-lucide="eye"></i> View</a>
                            <a href="{{ route('admin.news.edit', $item->id) }}" class="btn btn-ghost btn-sm"><i data-lucide="pencil"></i> Edit</a>
                            @if ($item->source_url)
                                <a href="{{ $item->source_url }}" target="_blank" rel="noopener noreferrer" class="btn btn-ghost btn-sm"><i data-lucide="external-link"></i> Source</a>
                            @endif
                        </div>

                        <form action="{{ route('admin.news.destroy', $item->id) }}" method="POST" onsubmit="return confirm('Delete this news item?')" class="news-inline-form">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-ghost btn-sm news-delete"><i data-lucide="trash-2"></i> Delete</button>
                        </form>
                    </div>
                </div>
            </article>
        @empty
            <div class="news-empty">
                <div class="news-empty__icon"><i data-lucide="newspaper"></i></div>
                <h3>No intelligence records found</h3>
                <p>Try clearing your filters or create the first news item for this view.</p>
                <a href="{{ route('admin.news.create') }}" class="btn btn-primary btn-sm"><i data-lucide="plus"></i> Add News</a>
            </div>
        @endforelse
    </div>

    @if ($items->total() > 0)
        <nav class="news-pagination" aria-label="News pagination">
            <div class="news-pagination__info">
                Page <strong>{{ $items->currentPage() }}</strong> of <strong>{{ max(1, $items->lastPage()) }}</strong>
            </div>

            <div class="news-pagination__controls">
                @if ($items->onFirstPage())
                    <span class="news-page-btn is-disabled"><i data-lucide="chevron-left"></i><span>Previous</span></span>
                @else
                    <a href="{{ $items->previousPageUrl() }}" class="news-page-btn"><i data-lucide="chevron-left"></i><span>Previous</span></a>
                @endif

                @php
                    $current = $items->currentPage();
                    $last = $items->lastPage();
                    $start = max(1, $current - 2);
                    $end = min($last, $current + 2);
                @endphp

                <div class="news-page-numbers">
                    @if ($start > 1)
                        <a href="{{ $items->url(1) }}" class="news-page-number">1</a>
                        @if ($start > 2)<span class="news-page-dots">…</span>@endif
                    @endif

                    @for ($page = $start; $page <= $end; $page++)
                        @if ($page === $current)
                            <span class="news-page-number is-active">{{ $page }}</span>
                        @else
                            <a href="{{ $items->url($page) }}" class="news-page-number">{{ $page }}</a>
                        @endif
                    @endfor

                    @if ($end < $last)
                        @if ($end < $last - 1)<span class="news-page-dots">…</span>@endif
                        <a href="{{ $items->url($last) }}" class="news-page-number">{{ $last }}</a>
                    @endif
                </div>

                @if ($items->hasMorePages())
                    <a href="{{ $items->nextPageUrl() }}" class="news-page-btn"><span>Next</span><i data-lucide="chevron-right"></i></a>
                @else
                    <span class="news-page-btn is-disabled"><span>Next</span><i data-lucide="chevron-right"></i></span>
                @endif
            </div>
        </nav>
    @endif
</div>
@endsection
