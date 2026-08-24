@extends('frontend.layouts.app')
@section('title', 'About AI Orbit — Independent AI Discovery & Intelligence')
@section('meta_description', 'Learn how AI Orbit helps people discover AI tools and models, compare products, follow pricing and news, and understand independent benchmark data.')
@push('styles')<link rel="stylesheet" href="{{ asset('css/frontend/institutional.css') }}">@endpush
@section('content')
<section class="inst-hero">
    <div class="inst-orb inst-orb-a"></div><div class="inst-orb inst-orb-b"></div>
    <div class="inst-wrap inst-hero-grid">
        <div>
            <span class="inst-eyebrow"><i data-lucide="brain-circuit"></i> About AI Orbit</span>
            <h1>One place to understand the <span>AI landscape.</span></h1>
            <p>AI Orbit is designed as a research-driven discovery layer for AI tools, models, companies, pricing, news, benchmarks and practical comparisons.</p>
            <div class="inst-actions"><a class="inst-btn primary" href="{{ route('tools.index') }}">Explore AI Tools <i data-lucide="arrow-right"></i></a><a class="inst-btn" href="{{ route('methodology') }}">Read our methodology</a></div>
        </div>
        <div class="inst-hero-card">
            <div class="inst-live"><span></span> Research platform</div>
            <div class="inst-stat-grid"><div><strong>Tools</strong><span>Product discovery</span></div><div><strong>Models</strong><span>Capability intelligence</span></div><div><strong>Pricing</strong><span>Plan tracking</span></div><div><strong>Benchmarks</strong><span>Comparable evidence</span></div></div>
        </div>
    </div>
</section>

<div class="inst-wrap inst-stack">
    <section class="inst-section">
        <div class="inst-section-head"><span>Our purpose</span><h2>Make AI research easier to navigate and easier to verify.</h2><p>Instead of treating every product page, model announcement and benchmark as an isolated source, AI Orbit connects them into a structured research experience.</p></div>
        <div class="inst-value-grid">
            <article class="inst-value"><i data-lucide="search-check"></i><h3>Discover with context</h3><p>Find tools and models by category, company, capabilities, price signals and quality indicators—not only by name.</p></article>
            <article class="inst-value"><i data-lucide="scale"></i><h3>Compare consistently</h3><p>Use the same data structure across products so meaningful differences are easier to see.</p></article>
            <article class="inst-value"><i data-lucide="flask-conical"></i><h3>Test transparently</h3><p>Test Lab and benchmark pages expose prompts, scoring criteria and recorded results where the underlying data is available.</p></article>
            <article class="inst-value"><i data-lucide="badge-dollar-sign"></i><h3>Track commercial reality</h3><p>Pricing Intelligence connects current plans, pricing history and detected changes rather than showing a single stale number.</p></article>
        </div>
    </section>

    <section class="inst-split">
        <div class="inst-panel">
            <span class="inst-mini-title">What AI Orbit covers</span>
            <h2>A connected intelligence graph, not a list of links.</h2>
            <div class="inst-check-list"><div><i data-lucide="check"></i><span><b>AI Tools</b> — discovery, categories, features, pricing and reviews.</span></div><div><i data-lucide="check"></i><span><b>AI Models</b> — providers, capabilities, context, API pricing and benchmarks.</span></div><div><i data-lucide="check"></i><span><b>AI News</b> — structured summaries, sources, verification and importance signals.</span></div><div><i data-lucide="check"></i><span><b>Companies</b> — linked tools, models and news activity.</span></div><div><i data-lucide="check"></i><span><b>Comparisons & Test Lab</b> — side-by-side evidence for practical decisions.</span></div></div>
        </div>
        <div class="inst-panel inst-principles">
            <span class="inst-mini-title">Product principles</span>
            <div class="inst-principle"><strong>01</strong><div><h3>Evidence over hype</h3><p>Scores and labels should be traceable to stored fields, benchmark records, reviews or clearly described calculations.</p></div></div>
            <div class="inst-principle"><strong>02</strong><div><h3>Useful uncertainty</h3><p>If the database does not contain a fact, the interface should prefer an honest empty state over invented precision.</p></div></div>
            <div class="inst-principle"><strong>03</strong><div><h3>Separation of source and opinion</h3><p>Provider claims, community reviews, editorial summaries and measured results should remain distinguishable.</p></div></div>
        </div>
    </section>

    <section class="inst-callout">
        <div><span class="inst-eyebrow"><i data-lucide="users"></i> Community input</span><h2>See something missing or incorrect?</h2><p>AI changes quickly. Community submissions help surface new products and corrections, while moderation keeps public data controlled.</p></div>
        <div class="inst-actions"><a class="inst-btn primary" href="{{ route('submissions.create') }}">Suggest or correct data</a><a class="inst-btn" href="{{ route('contact') }}">Contact AI Orbit</a></div>
    </section>
</div>
@endsection
