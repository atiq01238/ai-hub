@extends('frontend.layouts.app')

@php
    $pricingHasFilters = trim((string) request('q')) !== ''
        || !in_array((string) request('type', 'all'), ['', 'all'], true)
        || !in_array((string) request('category', 'all'), ['', 'all'], true)
        || !in_array((string) request('price', 'all'), ['', 'all'], true)
        || !in_array((string) request('freshness', 'all'), ['', 'all'], true)
        || !in_array((string) request('sort', 'updated'), ['', 'updated'], true);

    $pricingPage = max(1, (int) $tools->currentPage());
    $pricingSeoTitle = 'AI Tool Pricing Compared (2026) — 50+ Tools, Live Price Tracker';
    $pricingSeoDescription = 'Compare pricing for 50+ AI tools side-by-side. Free vs paid plans, API rates, and every price change tracked in real time — updated daily.';
    $pricingCanonical = (!$pricingHasFilters && $pricingPage > 1)
        ? route('pricing.index', ['page' => $pricingPage])
        : route('pricing.index');

    $pricingCollectionSchema = [
        '@' . 'context' => 'https://schema.org',
        '@' . 'type' => 'CollectionPage',
        'name' => 'AI Pricing Intelligence',
        'description' => $pricingSeoDescription,
        'url' => $pricingCanonical,
    ];

    $pricingBreadcrumbSchema = [
        '@' . 'context' => 'https://schema.org',
        '@' . 'type' => 'BreadcrumbList',
        'itemListElement' => [
            [
                '@' . 'type' => 'ListItem',
                'position' => 1,
                'name' => 'Home',
                'item' => route('home'),
            ],
            [
                '@' . 'type' => 'ListItem',
                'position' => 2,
                'name' => 'Pricing Intelligence',
                'item' => route('pricing.index'),
            ],
        ],
    ];

    $pricingQuery = static function (array $changes = []) {
        $query = request()->except('page');

        foreach ($changes as $key => $value) {
            if ($value === null || $value === '' || $value === 'all') {
                unset($query[$key]);
            } else {
                $query[$key] = $value;
            }
        }

        return $query;
    };
@endphp

@section('title', $pricingSeoTitle)
@section('meta_description', $pricingSeoDescription)
@section('canonical', $pricingCanonical)

@section(
    'robots',
    $pricingHasFilters
        ? 'noindex,follow'
        : 'index,follow,max-image-preview:large,max-snippet:-1,max-video-preview:-1'
)

@push('head')
<script type="application/ld+json">{!! json_encode(
    $pricingCollectionSchema,
    JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
) !!}</script>
<script type="application/ld+json">{!! json_encode(
    $pricingBreadcrumbSchema,
    JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
) !!}</script>
@endpush

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/frontend/pricing-intelligence.css') }}?v={{ is_file(public_path('css/frontend/pricing-intelligence.css')) ? filemtime(public_path('css/frontend/pricing-intelligence.css')) : '20260919' }}">
@endpush

@push('scripts')
    <script src="{{ asset('js/frontend/pricing-intelligence.js') }}?v={{ is_file(public_path('js/frontend/pricing-intelligence.js')) ? filemtime(public_path('js/frontend/pricing-intelligence.js')) : '20260919' }}" defer></script>
@endpush

@section('content')
<section class="pi-hero">
    <div class="pi-wrap">
        <span class="pi-kicker"><i data-lucide="radar"></i> Pricing Intelligence</span>
        <h1>Know what AI really costs.</h1>
        <p>Compare plans, spot free tiers, review API pricing and follow verified pricing changes from one research dashboard.</p>

        <div class="pi-stats">
            <div><b>{{ number_format((int) $stats['tools']) }}</b><span>Tools tracked</span></div>
            <div><b>{{ number_format((int) $stats['plans']) }}</b><span>Plans indexed</span></div>
            <div><b>{{ number_format((int) $stats['free']) }}</b><span>Free plans</span></div>
            <div><b>{{ number_format((int) $stats['changes']) }}</b><span>30-day changes</span></div>
        </div>
    </div>
</section>

<section class="pi-wrap pi-body">
    <div class="pi-explorer-head">
        <div>
            <span>PRICING EXPLORER</span>
            <h2>Find the right AI plan faster</h2>
            <p>Search and filter verified pricing without loading the entire directory at once.</p>
        </div>
        @if($pricingHasFilters)
            <a class="pi-clear" href="{{ route('pricing.index') }}">
                <i data-lucide="rotate-ccw"></i> Clear filters
            </a>
        @endif
    </div>

    <form class="pi-toolbar pi-toolbar-v2" method="get" action="{{ route('pricing.index') }}">
        @if($type !== 'all')
            <input type="hidden" name="type" value="{{ $type }}">
        @endif

        <label class="pi-search">
            <i data-lucide="search"></i>
            <input name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Search tool or company..." autocomplete="off">
        </label>

        <select name="category" aria-label="Filter by category">
            <option value="">All categories</option>
            @foreach($categories as $categoryOption)
                <option value="{{ $categoryOption->slug }}" @selected($category === $categoryOption->slug)>
                    {{ $categoryOption->name }} ({{ $categoryOption->pricing_tools_count }})
                </option>
            @endforeach
        </select>

        <select name="price" aria-label="Filter by starting price">
            <option value="">Any price</option>
            <option value="under_10" @selected($price === 'under_10')>Under $10/mo</option>
            <option value="10_20" @selected($price === '10_20')>$10–$20/mo</option>
            <option value="20_50" @selected($price === '20_50')>$20–$50/mo</option>
            <option value="50_plus" @selected($price === '50_plus')>$50+/mo</option>
            <option value="custom" @selected($price === 'custom')>Custom / contact</option>
        </select>

        <select name="freshness" aria-label="Filter by pricing freshness">
            <option value="">Any verification status</option>
            <option value="fresh" @selected($freshness === 'fresh')>Fresh · ≤14 days</option>
            <option value="review" @selected($freshness === 'review')>Review · 15–45 days</option>
            <option value="stale" @selected($freshness === 'stale')>Stale · 45+ days</option>
            <option value="unverified" @selected($freshness === 'unverified')>Unverified</option>
        </select>

        <select name="sort" aria-label="Sort pricing results">
            <option value="updated" @selected($sort === 'updated')>Recently verified</option>
            <option value="value" @selected($sort === 'value')>AI Orbit value score</option>
            <option value="price_low" @selected($sort === 'price_low')>Price: low to high</option>
            <option value="price_high" @selected($sort === 'price_high')>Price: high to low</option>
            <option value="name" @selected($sort === 'name')>Name A–Z</option>
        </select>

        <button class="pi-apply" type="submit">
            <i data-lucide="sliders-horizontal"></i> Apply
        </button>
    </form>

    <nav class="pi-tabs pi-type-tabs" aria-label="Pricing type">
        @foreach (['all' => 'All pricing', 'free' => 'Free tier', 'paid' => 'Paid plans', 'api' => 'API pricing'] as $key => $label)
            <a
                href="{{ route('pricing.index', $pricingQuery(['type' => $key])) }}"
                class="{{ $type === $key ? 'active' : '' }}"
                @if($type === $key) aria-current="page" @endif
            >
                {{ $label }}
            </a>
        @endforeach
    </nav>

    <div class="pi-layout">
        <main>
            <div class="pi-directory-head">
                <div class="pi-heading">
                    <div>
                        <span>LIVE DIRECTORY</span>
                        <h2>AI pricing comparison</h2>
                    </div>
                    <small>
                        @if($tools->total())
                            Showing {{ number_format((int) $tools->firstItem()) }}–{{ number_format((int) $tools->lastItem()) }} of {{ number_format((int) $tools->total()) }} products
                        @else
                            0 products
                        @endif
                    </small>
                </div>

                <div class="pi-view-actions">
                    <div class="pi-view-toggle" role="group" aria-label="Pricing directory view">
                        <button type="button" class="active" data-pricing-view-button="cards" aria-pressed="true">
                            <i data-lucide="layout-grid"></i> Cards
                        </button>
                        <button type="button" data-pricing-view-button="table" aria-pressed="false">
                            <i data-lucide="table-2"></i> Table
                        </button>
                    </div>
                    <span class="pi-select-hint"><i data-lucide="git-compare-arrows"></i> Select 2–4 tools to compare pricing</span>
                </div>
            </div>

            <div class="pi-grid" data-pricing-cards>
                @forelse ($tools as $tool)
                    @php
                        $freshnessLabel = match($tool->pricing_freshness) {
                            'fresh' => 'Fresh',
                            'review' => 'Needs review',
                            'stale' => 'Stale',
                            default => 'Unverified',
                        };
                    @endphp
                    <article class="pi-card pi-card-compact" data-tool-card="{{ $tool->id }}">
                        <div class="pi-card-select-row">
                            <label class="pi-select-tool">
                                <input
                                    type="checkbox"
                                    value="{{ $tool->id }}"
                                    data-pricing-select
                                    data-tool-id="{{ $tool->id }}"
                                    data-tool-name="{{ $tool->name }}"
                                    aria-label="Select {{ $tool->name }} for pricing comparison"
                                >
                                <span>Select to compare</span>
                            </label>
                            <span class="pi-freshness {{ $tool->pricing_freshness }}">{{ $freshnessLabel }}</span>
                        </div>

                        <div class="pi-card-head">
                            <div class="pi-tool">
                                <div class="pi-logo">
                                    <img src="{{ $tool->logo_url }}" alt="{{ $tool->name }} logo" loading="lazy">
                                </div>
                                <div>
                                    <h3>{{ $tool->name }}</h3>
                                    <p>{{ $tool->company?->name ?? 'Independent' }}</p>
                                </div>
                            </div>
                        </div>

                        <div class="pi-price-row">
                            <div>
                                <small>STARTING AT</small>
                                <strong>
                                    @if ($tool->has_free)
                                        Free
                                    @elseif ($tool->lowest_monthly !== null)
                                        ${{ number_format((float) $tool->lowest_monthly, 2) }}<em>/mo</em>
                                    @else
                                        Custom
                                    @endif
                                </strong>
                            </div>

                            <div class="pi-badges">
                                @if ($tool->has_free)<span>Free tier</span>@endif
                                @if ($tool->has_api)<span>API</span>@endif
                            </div>
                        </div>

                        <div class="pi-card-meta">
                            <span><i data-lucide="layers-3"></i> {{ $tool->pricing_plans_count }} {{ \Illuminate\Support\Str::plural('plan', $tool->pricing_plans_count) }}</span>
                            @if($tool->category)<span><i data-lucide="tag"></i> {{ $tool->category->name }}</span>@endif
                            <span>
                                <i data-lucide="badge-check"></i>
                                @if($tool->latest_pricing_evidence_at)
                                    Checked {{ $tool->latest_pricing_evidence_at->diffForHumans() }}
                                @else
                                    Verification pending
                                @endif
                            </span>
                        </div>

                        <div class="pi-card-foot pi-card-foot-actions">
                            <span>
                                <i data-lucide="star"></i>
                                {{ (float)($tool->rating ?? 0) > 0 ? number_format((float)$tool->rating, 1).' rating' : 'Not rated' }}
                            </span>
                            <div>
                                <a href="{{ route('tools.show', $tool) }}" class="pi-secondary-link">Profile</a>
                                <a href="{{ route('pricing.show', $tool) }}">View pricing <i data-lucide="arrow-up-right"></i></a>
                            </div>
                        </div>
                    </article>
                @empty
                    <div class="pi-empty">
                        <i data-lucide="search-x"></i>
                        <h3>No pricing matches</h3>
                        <p>Try changing the search, category, price or verification filters.</p>
                        <a href="{{ route('pricing.index') }}">Reset pricing explorer</a>
                    </div>
                @endforelse
            </div>

            @if($tools->count())
                <div class="pi-table-view" data-pricing-table hidden>
                    <div class="pi-directory-table-wrap">
                        <table class="pi-directory-table">
                            <thead>
                                <tr>
                                    <th class="pi-col-select">Compare</th>
                                    <th>Tool</th>
                                    <th>Starting price</th>
                                    <th>Free</th>
                                    <th>API</th>
                                    <th>Plans</th>
                                    <th>Category</th>
                                    <th>Verified</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($tools as $tool)
                                    @php
                                        $freshnessLabel = match($tool->pricing_freshness) {
                                            'fresh' => 'Fresh',
                                            'review' => 'Needs review',
                                            'stale' => 'Stale',
                                            default => 'Unverified',
                                        };
                                    @endphp
                                    <tr data-tool-row="{{ $tool->id }}">
                                        <td class="pi-col-select">
                                            <input
                                                type="checkbox"
                                                value="{{ $tool->id }}"
                                                data-pricing-select
                                                data-tool-id="{{ $tool->id }}"
                                                data-tool-name="{{ $tool->name }}"
                                                aria-label="Select {{ $tool->name }} for pricing comparison"
                                            >
                                        </td>
                                        <td>
                                            <a class="pi-table-tool" href="{{ route('pricing.show', $tool) }}">
                                                <span class="pi-logo"><img src="{{ $tool->logo_url }}" alt="" loading="lazy"></span>
                                                <span><b>{{ $tool->name }}</b><small>{{ $tool->company?->name ?? 'Independent' }}</small></span>
                                            </a>
                                        </td>
                                        <td>
                                            <strong class="pi-table-price">
                                                @if($tool->has_free)
                                                    Free
                                                @elseif($tool->lowest_monthly !== null)
                                                    ${{ number_format((float)$tool->lowest_monthly, 2) }}/mo
                                                @else
                                                    Custom
                                                @endif
                                            </strong>
                                        </td>
                                        <td>{{ $tool->has_free ? 'Yes' : '—' }}</td>
                                        <td>{{ $tool->has_api ? 'Yes' : '—' }}</td>
                                        <td>{{ $tool->pricing_plans_count }}</td>
                                        <td>{{ $tool->category?->name ?? '—' }}</td>
                                        <td><span class="pi-freshness {{ $tool->pricing_freshness }}">{{ $freshnessLabel }}</span></td>
                                        <td class="pi-table-actions"><a href="{{ route('pricing.show', $tool) }}">View <i data-lucide="arrow-up-right"></i></a></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif

            @if($tools->hasPages())
                @php
                    $startPage = max(1, $tools->currentPage() - 2);
                    $endPage = min($tools->lastPage(), $tools->currentPage() + 2);
                @endphp
                <nav class="pi-pagination" aria-label="Pricing directory pages">
                    @if($tools->onFirstPage())
                        <span class="disabled"><i data-lucide="chevron-left"></i> Previous</span>
                    @else
                        <a href="{{ $tools->previousPageUrl() }}" rel="prev"><i data-lucide="chevron-left"></i> Previous</a>
                    @endif

                    <div class="pi-page-numbers">
                        @if($startPage > 1)
                            <a href="{{ $tools->url(1) }}">1</a>
                            @if($startPage > 2)<span>…</span>@endif
                        @endif

                        @for($page = $startPage; $page <= $endPage; $page++)
                            @if($page === $tools->currentPage())
                                <span class="active" aria-current="page">{{ $page }}</span>
                            @else
                                <a href="{{ $tools->url($page) }}">{{ $page }}</a>
                            @endif
                        @endfor

                        @if($endPage < $tools->lastPage())
                            @if($endPage < $tools->lastPage() - 1)<span>…</span>@endif
                            <a href="{{ $tools->url($tools->lastPage()) }}">{{ $tools->lastPage() }}</a>
                        @endif
                    </div>

                    @if($tools->hasMorePages())
                        <a href="{{ $tools->nextPageUrl() }}" rel="next">Next <i data-lucide="chevron-right"></i></a>
                    @else
                        <span class="disabled">Next <i data-lucide="chevron-right"></i></span>
                    @endif
                </nav>
            @endif
        </main>

        <aside>
            <section class="pi-panel pi-action-guide">
                <div class="pi-panel-title">
                    <span><i data-lucide="mouse-pointer-click"></i> Pricing actions</span>
                    <small>Research</small>
                </div>
                <div class="pi-action-step">
                    <i data-lucide="check-square-2"></i>
                    <span><b>Select 2–4 tools</b><small>Choose products across pricing pages. Your selection stays for this browser tab.</small></span>
                </div>
                <div class="pi-action-step">
                    <i data-lucide="git-compare-arrows"></i>
                    <span><b>Compare side by side</b><small>Open the public comparison workspace with pricing, plans and verified product data.</small></span>
                </div>
                <div class="pi-action-step">
                    <i data-lucide="table-2"></i>
                    <span><b>Scan in table view</b><small>Switch between visual cards and a compact pricing table without changing the URL.</small></span>
                </div>
            </section>

            <section class="pi-panel pi-method">
                <span class="pi-panel-title">
                    <span><i data-lucide="shield-check"></i> How pricing works</span>
                </span>
                <p>AI Orbit separates live plan data from detected changes. Automatic detections can be reviewed before they become published pricing history.</p>

                <div>
                    <i data-lucide="scan-search"></i>
                    <span><b>Source monitoring</b><small>Official pricing sources can be tracked.</small></span>
                </div>
                <div>
                    <i data-lucide="badge-check"></i>
                    <span><b>Review workflow</b><small>Detected changes stay separate until approved.</small></span>
                </div>
                <div>
                    <i data-lucide="history"></i>
                    <span><b>Price history</b><small>Published changes build a transparent timeline.</small></span>
                </div>
            </section>
        </aside>
    </div>

    <section class="pi-changes-explorer" aria-labelledby="pricing-changes-title">
        <div class="pi-changes-head">
            <div>
                <span>LATEST MOVEMENTS</span>
                <h2 id="pricing-changes-title">Recent AI price changes</h2>
                <p>Review published pricing movements and jump straight to the affected product's pricing record.</p>
            </div>
            <div class="pi-change-filters" role="group" aria-label="Filter recent price changes">
                <button type="button" class="active" data-change-filter="all" aria-pressed="true">All</button>
                <button type="button" data-change-filter="increase" aria-pressed="false">Increases</button>
                <button type="button" data-change-filter="decrease" aria-pressed="false">Decreases</button>
                <button type="button" data-change-filter="new_plan" aria-pressed="false">New plans</button>
            </div>
        </div>

        <div class="pi-change-grid" data-change-grid>
            @forelse($recentChanges as $change)
                @php
                    $changeIcon = match($change->change_type) {
                        'decrease' => 'trending-down',
                        'new_plan' => 'plus',
                        'removed_plan' => 'minus',
                        default => 'trending-up',
                    };
                @endphp
                <article class="pi-change-card" data-change-card data-change-type="{{ $change->change_type }}">
                    <div class="pi-change-card-top">
                        <span class="pi-change-icon {{ $change->change_type }}"><i data-lucide="{{ $changeIcon }}"></i></span>
                        <div>
                            <span>{{ $change->metric_label }}</span>
                            <h3>{{ $change->tool?->name ?? 'AI Tool' }}</h3>
                        </div>
                        @if($change->change_percent !== null)
                            <b class="pi-change-percent {{ $change->change_percent < 0 ? 'down' : 'up' }}">
                                {{ $change->change_percent > 0 ? '+' : '' }}{{ number_format((float)$change->change_percent, 1) }}%
                            </b>
                        @endif
                    </div>
                    <p>{{ $change->plan_name ?: 'Pricing' }} · {{ str_replace('_', ' ', ucfirst($change->change_type)) }}</p>
                    @if($change->old_price !== null && $change->new_price !== null)
                        <strong>${{ number_format((float)$change->old_price, 2) }} <i data-lucide="arrow-right"></i> ${{ number_format((float)$change->new_price, 2) }}</strong>
                    @elseif($change->new_value)
                        <strong>{{ $change->new_value }}</strong>
                    @endif
                    <footer>
                        <small>{{ $change->created_at?->diffForHumans() }}</small>
                        @if($change->tool)
                            <a href="{{ route('pricing.show', $change->tool) }}">View pricing <i data-lucide="arrow-up-right"></i></a>
                        @endif
                    </footer>
                </article>
            @empty
                <div class="pi-empty pi-change-empty">No published price changes yet.</div>
            @endforelse
        </div>
    </section>

    <form class="pi-compare-bar" action="{{ route('comparisons.preview') }}" method="get" data-pricing-compare-form hidden>
        <input type="hidden" name="type" value="tool">
        <div data-pricing-compare-items></div>
        <div class="pi-compare-summary">
            <span class="pi-compare-count" data-pricing-compare-count>0 selected</span>
            <div class="pi-compare-names" data-pricing-compare-names></div>
            <small data-pricing-compare-status>Select at least two tools to compare.</small>
        </div>
        <div class="pi-compare-actions">
            <button type="button" class="pi-compare-clear" data-pricing-compare-clear>Clear</button>
            <button type="submit" class="pi-compare-submit" data-pricing-compare-submit disabled>
                <i data-lucide="git-compare-arrows"></i> Compare pricing
            </button>
        </div>
    </form>
</section>
@endsection
