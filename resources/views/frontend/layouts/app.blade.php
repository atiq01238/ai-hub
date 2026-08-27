@php
    $frontNotifications = collect();
    $frontUnread = 0;
    $frontHasAdminAccess = auth()->check() && auth()->user()->hasAdminPanelAccess();
    if (auth()->check() && ! $frontHasAdminAccess) {
        try {
            $frontNotifications = \App\Models\AppNotification::where('user_id', auth()->id())->latest()->limit(5)->get();
            $frontUnread = \App\Models\AppNotification::where('user_id', auth()->id())->unread()->count();
        } catch (\Throwable $e) {}
    }
@endphp
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="saved-toggle-url" content="{{ route('saved.toggle') }}">
    <meta name="saved-intent-url" content="{{ route('saved.intent') }}">
    <meta name="saved-status-url" content="{{ route('saved.status') }}">
    <meta name="login-url" content="{{ route('login') }}">
    <meta name="search-suggest-url" content="{{ route('search.suggest') }}">
    <meta name="search-click-url" content="{{ route('search.click') }}">
    <meta name="auth-status" content="{{ auth()->check() ? '1' : '0' }}">
    @include('frontend.partials.seo')
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset(config('brand.assets.favicon_32')) }}">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset(config('brand.assets.favicon_16')) }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset(config('brand.assets.apple_touch_icon')) }}">
    <meta name="theme-color" content="#070a17">
    <link rel="manifest" href="{{ asset('site.webmanifest') }}">
    @stack('head')
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet" media="print" onload="this.media='all'">
    <noscript><link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet"></noscript>
    @if(request()->routeIs('home'))
        <link rel="stylesheet" href="{{ asset('css/frontend/home-performance.css') }}?v=20260827-search2">
    @else
        <link rel="stylesheet" href="{{ asset('css/frontend/app.css') }}">
        <link rel="stylesheet" href="{{ asset('css/frontend/community.css') }}">
        <link rel="stylesheet" href="{{ asset('css/frontend/saved.css') }}">
        <link rel="stylesheet" href="{{ asset('css/frontend/search-intelligence.css') }}">
        @stack('styles')
        <link rel="stylesheet" href="{{ asset('css/frontend/ui-polish.css') }}">
    @endif
</head>
<body>
<div class="site-shell">
    <header class="topbar">
        <a class="brand" href="{{ route('home') }}" aria-label="AI Orbit home">
            <span class="brand-mark brand-orbit-mark"><img src="{{ asset(config('brand.assets.icon')) }}" alt="" aria-hidden="true" width="96" height="96" decoding="async"></span>
            <span><strong>AI Orbit</strong><small>Explore • Compare • Stay Ahead</small></span>
        </a>

        <nav class="main-nav" aria-label="Main navigation">
            <a class="{{ request()->routeIs('home') ? 'active' : '' }}" href="{{ route('home') }}"><i data-lucide="house"></i>Home</a>
            <a class="{{ request()->routeIs('tools.*') ? 'active' : '' }}" href="{{ route('tools.index') }}"><i data-lucide="bot"></i>AI Tools</a>
            <a class="{{ request()->routeIs('models.*') ? 'active' : '' }}" href="{{ route('models.index') }}"><i data-lucide="code-xml"></i>AI Models</a>
            <a class="{{ request()->routeIs('news.*') ? 'active' : '' }}" href="{{ route('news.index') }}"><i data-lucide="radio"></i>AI News</a>
            <a class="{{ request()->routeIs('comparisons.*') ? 'active' : '' }}" href="{{ route('comparisons.index') }}"><i data-lucide="scale"></i>Compare</a>
            <a class="{{ request()->routeIs('pricing.*') ? 'active' : '' }}" href="{{ route('pricing.index') }}"><i data-lucide="badge-dollar-sign"></i>Pricing</a>
            <a class="{{ request()->routeIs('reviews.*') ? 'active' : '' }}" href="{{ route('reviews.index') }}"><i data-lucide="star"></i>Reviews</a>
            <a class="{{ request()->routeIs('articles.*') ? 'active' : '' }}" href="{{ route('articles.index') }}"><i data-lucide="newspaper"></i>Articles</a>
            <a class="{{ request()->routeIs('companies.*') ? 'active' : '' }}" href="{{ route('companies.index') }}"><i data-lucide="building-2"></i>Companies</a>
        </nav>

        <div class="nav-actions">
            <a class="icon-btn {{ request()->routeIs('search.*') ? 'active' : '' }}" href="{{ route('search.index') }}" aria-label="Search AI Orbit" data-global-search-open><i data-lucide="search"></i></a>
            <a class="icon-btn {{ request()->routeIs('saved.*') ? 'active' : '' }}" href="{{ route('saved.index') }}" aria-label="Saved library"><i data-lucide="bookmark"></i></a>
            @auth
                @if(! $frontHasAdminAccess)
                    <div class="front-notif-wrap">
                        <button class="icon-btn front-notif-btn" type="button" data-front-notif-toggle aria-label="Notifications"><i data-lucide="bell"></i>@if($frontUnread)<span class="front-notif-count">{{ $frontUnread > 99 ? '99+' : $frontUnread }}</span>@endif</button>
                        <div class="front-notif-menu" data-front-notif-menu>
                            <div class="front-notif-head"><strong>Notifications</strong><span>{{ $frontUnread ? $frontUnread.' unread' : 'All caught up' }}</span></div>
                            @forelse($frontNotifications as $notice)
                                <a href="{{ route('account.notifications.open',$notice) }}" class="front-notif-row {{ $notice->read_at ? '' : 'is-unread' }}"><span><i data-lucide="{{ $notice->icon ?: 'bell' }}"></i></span><div><b>{{ $notice->title }}</b>@if($notice->description)<p>{{ \Illuminate\Support\Str::limit($notice->description,70) }}</p>@endif<small>{{ $notice->created_at->diffForHumans() }}</small></div></a>
                            @empty
                                <div class="front-notif-empty">No notifications yet.</div>
                            @endforelse
                            <a class="front-notif-footer" href="{{ route('account.notifications') }}">View all notifications <i data-lucide="arrow-right"></i></a>
                        </div>
                    </div>
                @endif
                @if($frontHasAdminAccess)
                    <a class="signin-btn" href="{{ route('admin.dashboard') }}" aria-label="Open admin panel"><span class="avatar">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</span>Admin Panel<i data-lucide="chevron-right"></i></a>
                @else
                    <a class="signin-btn {{ request()->routeIs('account.*') ? 'active' : '' }}" href="{{ route('account.dashboard') }}"><span class="avatar">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</span>My AI Orbit<i data-lucide="chevron-right"></i></a>
                @endif
            @else
                <a class="signin-btn" href="{{ route('login') }}"><span class="avatar"><i data-lucide="user"></i></span>Sign In<i data-lucide="chevron-right"></i></a>
            @endauth
            <button class="menu-btn" type="button" aria-label="Open navigation" aria-controls="mobile-navigation" aria-expanded="false" data-menu-toggle><i data-lucide="menu"></i></button>
        </div>
    </header>

    <div class="mobile-nav-backdrop" data-mobile-nav-backdrop aria-hidden="true"></div>
    <div class="mobile-nav" id="mobile-navigation" data-mobile-nav aria-hidden="true" inert>
        @auth
            @if($frontHasAdminAccess)
                <a href="{{ route('admin.dashboard') }}"><i data-lucide="shield-check"></i>Admin Panel</a>
            @else
                <a class="{{ request()->routeIs('account.dashboard') ? 'active' : '' }}" href="{{ route('account.dashboard') }}">My AI Orbit</a>
                <a class="{{ request()->routeIs('account.notifications*') ? 'active' : '' }}" href="{{ route('account.notifications') }}">Notifications @if($frontUnread)({{ $frontUnread }})@endif</a>
            @endif
        @endauth
        <a class="{{ request()->routeIs('home') ? 'active' : '' }}" href="{{ route('home') }}">Home</a>
        <a class="{{ request()->routeIs('tools.*') ? 'active' : '' }}" href="{{ route('tools.index') }}">AI Tools</a>
        <a class="{{ request()->routeIs('models.*') ? 'active' : '' }}" href="{{ route('models.index') }}">AI Models</a>
        <a class="{{ request()->routeIs('companies.*') ? 'active' : '' }}" href="{{ route('companies.index') }}">Companies</a>
        <a class="{{ request()->routeIs('news.*') ? 'active' : '' }}" href="{{ route('news.index') }}">AI News</a>
        <a class="{{ request()->routeIs('articles.*') ? 'active' : '' }}" href="{{ route('articles.index') }}">Articles</a>
        <a class="{{ request()->routeIs('comparisons.*') ? 'active' : '' }}" href="{{ route('comparisons.index') }}">Compare</a>
        <a class="{{ request()->routeIs('pricing.*') ? 'active' : '' }}" href="{{ route('pricing.index') }}">Pricing</a>
        <a class="{{ request()->routeIs('search.*') ? 'active' : '' }}" href="{{ route('search.index') }}">Search</a>
    </div>

    <div class="site-search-overlay" data-site-search-modal hidden aria-hidden="true" inert>
        <button class="site-search-backdrop" type="button" data-global-search-close aria-label="Close search"></button>
        <section class="site-search-panel" role="dialog" aria-modal="true" aria-label="Search AI Orbit">
            <div class="site-search-panel-head">
                <div><span><i data-lucide="sparkles"></i> Search Intelligence</span><strong>Search across AI Orbit</strong></div>
                <button type="button" data-global-search-close aria-label="Close search"><i data-lucide="x"></i></button>
            </div>
            <form class="site-search-form" action="{{ route('search.index') }}" method="get" data-search-shell>
                <i data-lucide="search"></i>
                <input type="search" name="q" autocomplete="off" placeholder="Tools, models, companies, news, use cases..." data-search-autocomplete data-search-overlay-input>
                <button type="submit">Search</button>
                <div class="search-live-results site-search-live-results" data-search-suggestions hidden></div>
            </form>
            <div class="site-search-hints">
                <span><kbd>↑</kbd><kbd>↓</kbd> navigate</span>
                <span><kbd>Enter</kbd> open</span>
                <span><kbd>Esc</kbd> close</span>
                <a href="{{ route('search.index') }}">Advanced search <i data-lucide="arrow-right"></i></a>
            </div>
        </section>
    </div>

    <main>@yield('content')</main>

    <footer class="footer">
        <div class="footer-glow footer-glow-one"></div>
        <div class="footer-glow footer-glow-two"></div>

        <div class="footer-inner">
            <div class="footer-cta">
                <div class="footer-cta-copy">
                    <span class="footer-kicker"><i data-lucide="sparkles"></i> AI intelligence, in one place</span>
                    <h2>Stay ahead of what is happening in AI.</h2>
                    <p>Discover new tools, model releases, pricing changes, benchmarks and practical comparisons without searching across dozens of sites.</p>
                </div>
                <div class="footer-subscribe footer-discovery-actions">
                    <a class="footer-discovery-primary" href="{{ route('trending.index') }}">Explore Trending AI <i data-lucide="arrow-right"></i></a>
                    <a class="footer-discovery-secondary" href="{{ route('news.index') }}">Read Latest News</a>
                    <small>Fresh tools, model releases, pricing changes and verified AI intelligence.</small>
                </div>
            </div>

            <div class="footer-main">
                <div class="footer-about">
                    <a class="footer-logo" href="{{ route('home') }}">
                        <span class="brand-mark brand-orbit-mark"><img src="{{ asset(config('brand.assets.icon')) }}" alt="" aria-hidden="true" width="96" height="96" decoding="async"></span>
                        <span><strong>AI Orbit</strong><small>Explore • Compare • Stay Ahead</small></span>
                    </a>
                    <p>A research-driven hub for finding the right AI tools and models, understanding the latest AI news and comparing products with useful data.</p>
                    <div class="footer-proof">
                        <span><i data-lucide="shield-check"></i> Source-aware</span>
                        <span><i data-lucide="database"></i> Data-driven</span>
                        <span><i data-lucide="scale"></i> Comparison-ready</span>
                    </div>
                </div>

                <div class="footer-links">
                    <div><h3>Explore</h3><a href="{{ route('search.index') }}">Global Search</a><a href="{{ route('categories.index') }}">AI Categories</a><a href="{{ route('features.index') }}">AI Features</a><a href="{{ route('use-cases.index') }}">Use Cases</a><a href="{{ route('tools.index') }}">AI Tools</a><a href="{{ route('models.index') }}">AI Models</a><a href="{{ route('news.index') }}">AI News</a><a href="{{ route('comparisons.index') }}">Comparisons</a></div>
                    <div><h3>Intelligence</h3><a href="{{ route('pricing.index') }}">Pricing</a><a href="{{ route('benchmarks.index') }}">Benchmarks</a><a href="{{ route('trending.index') }}">Trending</a><a href="{{ route('reviews.index') }}">Reviews</a><a href="{{ route('articles.index') }}">Articles</a><a href="{{ route('companies.index') }}">Companies</a></div>
                    <div><h3>Company</h3><a href="{{ route('about') }}">About AI Orbit</a><a href="{{ route('methodology') }}">Methodology</a><a href="{{ route('methodology') }}#editorial">Editorial Policy</a><a href="{{ route('contact') }}">Contact</a><a href="{{ route('submissions.create') }}">Suggest a Tool</a></div>
                    <div><h3>Resources</h3><a href="{{ route('saved.index') }}">Saved Library</a><a href="{{ route('topics.index') }}">Editorial Topics</a><a href="{{ route('categories.index') }}">AI Categories</a><a href="{{ route('benchmarks.index') }}">Benchmark Data</a><a href="{{ route('pricing.index') }}">Pricing Intelligence</a><a href="{{ route('disclosures') }}">Data Disclosures</a><a href="{{ route('contact') }}">Help & Feedback</a></div>
                </div>
            </div>

            <div class="footer-bottom">
                <p>© {{ date('Y') }} AI Orbit. All rights reserved.</p>
                <div class="footer-legal"><a href="{{ route('privacy') }}">Privacy</a><a href="{{ route('terms') }}">Terms</a><a href="{{ route('cookies') }}">Cookies</a><a href="{{ route('disclosures') }}">Disclosures</a></div>
                <div class="footer-status"><i data-lucide="database"></i> Public AI intelligence <b>•</b> English</div>
            </div>
        </div>
    </footer>
</div>
<script src="https://unpkg.com/lucide@0.468.0/dist/umd/lucide.min.js"></script>
<script src="{{ asset('js/frontend/app.js') }}"></script>
<script src="{{ asset('js/frontend/search-intelligence.js') }}?v=20260827-search2"></script>
<script src="{{ asset('js/frontend/saved.js') }}"></script>
<script src="{{ asset('js/frontend/community.js') }}"></script>
@stack('scripts')
</body>
</html>
