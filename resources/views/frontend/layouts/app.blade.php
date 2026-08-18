<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'AI Hub — Discover, Compare, Master AI')</title>
    <meta name="description" content="@yield('meta_description', 'Discover AI tools, models, news, comparisons, pricing and independent test results in one place.')">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/frontend/app.css') }}">
    @stack('styles')
</head>
<body>
<div class="site-shell">
    <header class="topbar">
        <a class="brand" href="{{ route('home') }}" aria-label="AI Hub home">
            <span class="brand-mark"><i data-lucide="brain-circuit"></i></span>
            <span><strong>AI Hub</strong><small>Discover • Compare • Master AI</small></span>
        </a>

        <nav class="main-nav" aria-label="Main navigation">
            <a class="{{ request()->routeIs('home') ? 'active' : '' }}" href="{{ route('home') }}"><i data-lucide="house"></i>Home</a>
            <a class="{{ request()->routeIs('tools.*') ? 'active' : '' }}" href="{{ route('tools.index') }}"><i data-lucide="bot"></i>AI Tools</a>
            <a class="{{ request()->routeIs('models.*') ? 'active' : '' }}" href="{{ route('models.index') }}"><i data-lucide="code-xml"></i>AI Models</a>
            <a href="#news"><i data-lucide="radio"></i>AI News</a>
            <a href="#comparisons"><i data-lucide="scale"></i>Compare</a>
            <a href="#test-lab"><i data-lucide="flask-conical"></i>Test Lab</a>
            <a href="#pricing"><i data-lucide="badge-dollar-sign"></i>Pricing</a>
            <a href="#reviews"><i data-lucide="star"></i>Reviews</a>
            <a href="#articles"><i data-lucide="newspaper"></i>Articles</a>
            <a href="#companies"><i data-lucide="building-2"></i>Companies</a>
        </nav>

        <div class="nav-actions">
            <button class="icon-btn" type="button" aria-label="Search" data-focus-search><i data-lucide="search"></i></button>
            <button class="icon-btn" type="button" aria-label="Toggle theme"><i data-lucide="moon"></i></button>
            <button class="icon-btn" type="button" aria-label="Language"><i data-lucide="globe-2"></i></button>
            @auth
                <a class="signin-btn" href="{{ route('admin.dashboard') }}"><span class="avatar">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</span>Dashboard<i data-lucide="chevron-down"></i></a>
            @else
                <a class="signin-btn" href="{{ route('login') }}"><span class="avatar"><i data-lucide="user"></i></span>Sign In<i data-lucide="chevron-down"></i></a>
            @endauth
            <button class="menu-btn" type="button" aria-label="Open navigation" data-menu-toggle><i data-lucide="menu"></i></button>
        </div>
    </header>

    <div class="mobile-nav" data-mobile-nav>
        <a href="{{ route('home') }}">Home</a><a href="{{ route('tools.index') }}">AI Tools</a><a href="{{ route('models.index') }}">AI Models</a><a href="#news">AI News</a><a href="#comparisons">Compare</a><a href="#test-lab">Test Lab</a><a href="#pricing">Pricing</a><a href="#reviews">Reviews</a><a href="#articles">Articles</a><a href="#companies">Companies</a>
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
                <form class="footer-subscribe" action="#" method="get">
                    <div class="subscribe-field"><i data-lucide="mail"></i><input type="email" aria-label="Email address" placeholder="Enter your email address"></div>
                    <button type="submit">Get AI Updates <i data-lucide="arrow-right"></i></button>
                    <small>No spam. Just useful AI updates and product intelligence.</small>
                </form>
            </div>

            <div class="footer-main">
                <div class="footer-about">
                    <a class="footer-logo" href="{{ route('home') }}">
                        <span class="brand-mark"><i data-lucide="brain-circuit"></i></span>
                        <span><strong>AI Hub</strong><small>Discover • Compare • Master AI</small></span>
                    </a>
                    <p>A research-driven hub for finding the right AI tools and models, understanding the latest AI news and comparing products with useful data.</p>
                    <div class="footer-socials">
                        <a href="#" aria-label="X"><i data-lucide="twitter"></i></a>
                        <a href="#" aria-label="YouTube"><i data-lucide="youtube"></i></a>
                        <a href="#" aria-label="LinkedIn"><i data-lucide="linkedin"></i></a>
                        <a href="#" aria-label="Github"><i data-lucide="github"></i></a>
                    </div>
                </div>

                <div class="footer-links">
                    <div><h3>Explore</h3><a href="{{ route('tools.index') }}">AI Tools</a><a href="{{ route('models.index') }}">AI Models</a><a href="#news">AI News</a><a href="#comparisons">Comparisons</a><a href="#test-lab">Test Lab</a></div>
                    <div><h3>Intelligence</h3><a href="#">Pricing</a><a href="#">Benchmarks</a><a href="#">Reviews</a><a href="#">Articles</a><a href="#">Companies</a></div>
                    <div><h3>Company</h3><a href="#">About AI Hub</a><a href="#">Methodology</a><a href="#">Editorial Policy</a><a href="#">Contact</a><a href="#">Suggest a Tool</a></div>
                    <div><h3>Resources</h3><a href="#">AI Glossary</a><a href="#">For Developers</a><a href="#">API & Data</a><a href="#">Advertise</a><a href="#">Help Center</a></div>
                </div>
            </div>

            <div class="footer-bottom">
                <p>© {{ date('Y') }} AI Hub. All rights reserved.</p>
                <div class="footer-legal"><a href="#">Privacy</a><a href="#">Terms</a><a href="#">Cookies</a><a href="#">Disclosures</a></div>
                <div class="footer-status"><span></span> Systems operational <b>•</b> <i data-lucide="globe-2"></i> English</div>
            </div>
        </div>
    </footer>
</div>
<script src="https://unpkg.com/lucide@0.468.0/dist/umd/lucide.min.js"></script>
<script src="{{ asset('js/frontend/app.js') }}"></script>
@stack('scripts')
</body>
</html>
