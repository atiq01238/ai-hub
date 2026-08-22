@extends('frontend.layouts.app')

@section('title', $seo['title'])
@section('meta_description', $seo['description'])

@push('head')
<link rel="canonical" href="{{ $seo['canonical'] }}">
<meta name="robots" content="index,follow,max-image-preview:large,max-snippet:-1,max-video-preview:-1">
<meta property="og:type" content="profile">
<meta property="og:site_name" content="AI Hub">
<meta property="og:title" content="{{ $seo['title'] }}">
<meta property="og:description" content="{{ $seo['description'] }}">
<meta property="og:url" content="{{ $seo['canonical'] }}">
<meta property="og:image" content="{{ $seo['logo'] }}">
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="{{ $seo['title'] }}">
<meta name="twitter:description" content="{{ $seo['description'] }}">
<meta name="twitter:image" content="{{ $seo['logo'] }}">
<script type="application/ld+json">{!! json_encode($seo['organization'], JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE) !!}</script>
<script type="application/ld+json">{!! json_encode($seo['webPage'], JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE) !!}</script>
<script type="application/ld+json">{!! json_encode($seo['breadcrumb'], JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE) !!}</script>
@if($contentSeo['faq']->count())
<script type="application/ld+json">{!! json_encode([
    '@context' => 'https://schema.org',
    '@type' => 'FAQPage',
    'mainEntity' => $contentSeo['faq']->map(fn ($item) => [
        '@type' => 'Question',
        'name' => $item['question'],
        'acceptedAnswer' => [
            '@type' => 'Answer',
            'text' => $item['answer'],
        ],
    ])->values()->all(),
], JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE) !!}</script>
@endif
@endpush

@push('styles')
<link rel="stylesheet" href="{{ asset('css/frontend/companies.css') }}">
@endpush

@section('content')
@php
    $logo = $company->logo_url;
@endphp

<section class="company-profile-hero">
    <div class="company-wrap">
        <div class="company-breadcrumb">
            <a href="{{ route('home') }}">Home</a>
            <i data-lucide="chevron-right"></i>
            <a href="{{ route('companies.index') }}">Companies</a>
            <i data-lucide="chevron-right"></i>
            <span>{{ $company->name }}</span>
        </div>

        <div class="company-profile-main">
            <div class="company-profile-id">
                <img src="{{ $logo }}" alt="{{ $company->name }} official company logo">
                <div>
                    <div class="company-profile-badges">
                        <span class="company-status status-{{ $company->status }}">{{ ucfirst($company->status) }}</span>
                        @if($company->founded_year)
                            <span>Founded {{ $company->founded_year }}</span>
                        @endif
                        @if($lastUpdated)
                            <span class="company-updated-badge"><i data-lucide="shield-check"></i> Updated {{ $lastUpdated->format('M j, Y') }}</span>
                        @endif
                    </div>
                    <h1>{{ $company->name }}</h1>
                    <p>{{ \Illuminate\Support\Str::limit($company->description ?: 'AI company developing products, models and developer services.', 180) }}</p>
                </div>
            </div>

            <div class="company-profile-actions">
                <button type="button" class="save-item-btn detail-save" data-save-item data-save-type="company" data-save-id="{{ $company->id }}" aria-pressed="false">
                    <i data-lucide="bookmark"></i>
                    <span data-save-label data-default-label="Save">Save</span>
                </button>
                @if($company->website)
                    <a class="primary" href="{{ $company->website }}" target="_blank" rel="noopener noreferrer">Visit website <i data-lucide="external-link"></i></a>
                @endif
                <a href="{{ route('comparisons.builder') }}">Compare products <i data-lucide="scale"></i></a>
            </div>
        </div>

        <div class="company-profile-metrics">
            <div><span>AI tools</span><strong>{{ number_format($company->published_tools_count) }}</strong><small>Published products</small></div>
            <div><span>AI models</span><strong>{{ number_format($company->active_models_count) }}</strong><small>Active & preview</small></div>
            <div><span>News signals</span><strong>{{ number_format($company->published_news_count) }}</strong><small>Published intelligence</small></div>
            <div><span>Founded</span><strong>{{ $company->founded_year ?: '—' }}</strong><small>{{ ucfirst($company->status) }} company</small></div>
        </div>
    </div>
</section>

<nav class="company-profile-nav" aria-label="Company page sections">
    <div class="company-wrap">
        <a href="#overview">Overview</a>
        <a href="#tools">AI Tools</a>
        <a href="#models">AI Models</a>
        <a href="#news">Latest News</a>
        @if($articles->count())<a href="#articles">Articles</a>@endif
        @if($contentSeo['faq']->count())<a href="#faq">FAQ</a>@endif
    </div>
</nav>

<section class="company-profile-body">
    <div class="company-wrap">
        <div class="company-profile-layout">
            <main>
                <section class="company-block" id="overview">
                    <span class="company-kicker">COMPANY OVERVIEW</span>
                    <h2>About {{ $company->name }}</h2>
                    <p class="company-lead">{{ $company->description ?: $company->name.' develops AI products, models and developer services.' }}</p>
                    <div class="company-facts">
                        <div><span>Status</span><strong>{{ ucfirst($company->status) }}</strong></div>
                        <div><span>Founded</span><strong>{{ $company->founded_year ?: 'Not listed' }}</strong></div>
                        <div><span>Website</span><strong>{{ $company->website ? parse_url($company->website, PHP_URL_HOST) : 'Not listed' }}</strong></div>
                        <div><span>AI Hub coverage</span><strong>{{ $company->published_tools_count + $company->active_models_count }} products & models</strong></div>
                    </div>
                    <div class="company-knowledge-summary">
                        <div class="company-knowledge-copy">
                            <h3>What does {{ $company->name }} do?</h3>
                            <p>{{ $contentSeo['intro'] }}</p>
                            <p>{{ $contentSeo['portfolio_summary'] }}</p>
                            @if($contentSeo['focus_summary'])
                                <p>{{ $contentSeo['focus_summary'] }}</p>
                            @endif
                        </div>

                        @if($contentSeo['facts']->count())
                            <div class="company-knowledge-facts" aria-label="{{ $company->name }} key facts">
                                @foreach($contentSeo['facts'] as $fact)
                                    <div><span>{{ $fact['label'] }}</span><strong>{{ $fact['value'] }}</strong></div>
                                @endforeach
                            </div>
                        @endif

                        @if($contentSeo['model_names']->count() || $contentSeo['tool_names']->count())
                            <div class="company-entity-links">
                                @if($contentSeo['model_names']->count())
                                    <div>
                                        <span>Key models</span>
                                        <div>
                                            @foreach($models->whereIn('name', $contentSeo['model_names']) as $modelLink)
                                                <a href="{{ route('models.show', $modelLink) }}">{{ $modelLink->name }}</a>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif
                                @if($contentSeo['tool_names']->count())
                                    <div>
                                        <span>Key products</span>
                                        <div>
                                            @foreach($tools->whereIn('name', $contentSeo['tool_names']) as $toolLink)
                                                <a href="{{ route('tools.show', $toolLink) }}">{{ $toolLink->name }}</a>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif
                            </div>
                        @endif

                        @if($contentSeo['categories']->count())
                            <div class="company-focus-tags" aria-label="{{ $company->name }} AI focus areas">
                                @foreach($contentSeo['categories'] as $category)
                                    <a href="{{ route('tools.index', ['category' => \Illuminate\Support\Str::slug($category)]) }}">{{ $category }}</a>
                                @endforeach
                            </div>
                        @endif
                    </div>
                    @if($lastUpdated)
                        <div class="company-verification-note">
                            <i data-lucide="database"></i>
                            <span><strong>AI Hub profile data</strong> · Last updated {{ $lastUpdated->format('F j, Y') }} from linked company, model, tool and intelligence records.</span>
                        </div>
                    @endif
                </section>

                <section class="company-block" id="tools">
                    <div class="company-block-head">
                        <div><span class="company-kicker">PRODUCT PORTFOLIO</span><h2>{{ $company->name }} AI Tools</h2></div>
                        <a href="{{ route('tools.index', ['q' => $company->name]) }}">Browse tools <i data-lucide="arrow-right"></i></a>
                    </div>
                    @if($tools->count())
                        <div class="company-tool-grid">
                            @foreach($tools as $tool)
                                <a href="{{ route('tools.show', $tool) }}">
                                    <img src="{{ $tool->logo_url }}" alt="{{ $tool->name }} logo">
                                    <div><small>{{ $tool->category?->name ?: 'AI Tool' }}</small><strong>{{ $tool->name }}</strong><span>{{ \Illuminate\Support\Str::limit($tool->short_description ?: $tool->description, 72) }}</span></div>
                                    <b>{{ number_format((float) $tool->rating, 1) }}★</b>
                                </a>
                            @endforeach
                        </div>
                    @else
                        <div class="company-inline-empty">No published AI tools are linked to this company yet.</div>
                    @endif
                </section>

                <section class="company-block" id="models">
                    <div class="company-block-head">
                        <div><span class="company-kicker">MODEL INTELLIGENCE</span><h2>{{ $company->name }} AI Models</h2></div>
                        <a href="{{ route('models.index', ['company' => $company->slug]) }}">Browse models <i data-lucide="arrow-right"></i></a>
                    </div>
                    @if($models->count())
                        <div class="company-model-grid">
                            @foreach($models as $model)
                                <a href="{{ route('models.show', $model) }}">
                                    <img src="{{ $model->logo_url }}" alt="{{ $model->name }} model logo">
                                    <div><small>{{ strtoupper($model->status) }}</small><strong>{{ $model->name }}</strong><span>{{ $model->context_window ?: '—' }} context · {{ $model->version ?: 'Current version' }}</span></div>
                                    <b>{{ $model->benchmark_score !== null ? number_format((float) $model->benchmark_score, 1) : '—' }}</b>
                                </a>
                            @endforeach
                        </div>
                    @else
                        <div class="company-inline-empty">No active AI models are linked to this company yet.</div>
                    @endif
                </section>

                <section class="company-block" id="news">
                    <div class="company-block-head">
                        <div><span class="company-kicker">LATEST INTELLIGENCE</span><h2>Latest {{ $company->name }} News</h2></div>
                        <a href="{{ route('news.index', ['company' => $company->slug]) }}">All news <i data-lucide="arrow-right"></i></a>
                    </div>
                    @if($news->count())
                        <div class="company-news-grid">
                            @foreach($news as $item)
                                @php
                                    $nimg = $item->image_url ?: $logo;
                                @endphp
                                <a href="{{ route('news.show', $item) }}">
                                    <img src="{{ $nimg }}" alt="{{ $item->headline }}">
                                    <div><small>{{ $item->category ?: 'AI News' }} · {{ optional($item->published_at)->diffForHumans() }}</small><strong>{{ $item->headline }}</strong><span>{{ \Illuminate\Support\Str::limit($item->ai_summary ?: $item->summary, 92) }}</span></div>
                                </a>
                            @endforeach
                        </div>
                    @else
                        <div class="company-inline-empty">No published news is linked to this company yet.</div>
                    @endif
                </section>

                @if($articles->count())
                    <section class="company-block" id="articles">
                        <div class="company-block-head">
                            <div><span class="company-kicker">RESEARCH & GUIDES</span><h2>{{ $company->name }} Articles & Guides</h2></div>
                            <a href="{{ route('articles.index', ['company' => $company->id]) }}">Browse articles <i data-lucide="arrow-right"></i></a>
                        </div>
                        <div class="company-article-grid">
                            @foreach($articles as $article)
                                <a href="{{ route('articles.show', $article) }}">
                                    <div>
                                        <small>{{ $article->category ?: 'AI Research' }} @if($article->published_at)· {{ $article->published_at->format('M j, Y') }}@endif</small>
                                        <strong>{{ $article->title }}</strong>
                                        <span>{{ \Illuminate\Support\Str::limit($article->summary ?: strip_tags($article->content), 115) }}</span>
                                    </div>
                                    <i data-lucide="arrow-up-right"></i>
                                </a>
                            @endforeach
                        </div>
                    </section>
                @endif

                @if($contentSeo['faq']->count())
                    <section class="company-block company-faq-block" id="faq">
                        <div class="company-block-head">
                            <div>
                                <span class="company-kicker">QUICK ANSWERS</span>
                                <h2>{{ $company->name }} FAQ</h2>
                            </div>
                        </div>
                        <div class="company-faq-list">
                            @foreach($contentSeo['faq'] as $faqItem)
                                <details>
                                    <summary>{{ $faqItem['question'] }} <i data-lucide="chevron-down"></i></summary>
                                    <p>{{ $faqItem['answer'] }}</p>
                                </details>
                            @endforeach
                        </div>
                    </section>
                @endif
            </main>

            <aside class="company-profile-side">
                <div class="company-side-card">
                    <span class="company-side-label">COMPANY PROFILE</span>
                    <img class="company-side-logo" src="{{ $logo }}" alt="{{ $company->name }} logo">
                    <h3>{{ $company->name }}</h3>
                    <p>{{ \Illuminate\Support\Str::limit($company->description ?: 'AI company.', 150) }}</p>
                    @if($company->website)
                        <a href="{{ $company->website }}" target="_blank" rel="noopener noreferrer">Official website <i data-lucide="external-link"></i></a>
                    @endif
                    @if($lastUpdated)
                        <div class="company-side-updated"><i data-lucide="clock-3"></i> Profile updated {{ $lastUpdated->diffForHumans() }}</div>
                    @endif
                </div>

                <div class="company-side-card">
                    <span class="company-side-label">RELATED COMPANIES</span>
                    @foreach($relatedCompanies as $related)
                        <a class="related-company" href="{{ route('companies.show', $related) }}">
                            <img src="{{ $related->logo_url }}" alt="{{ $related->name }} logo">
                            <div><strong>{{ $related->name }}</strong><small>{{ $related->active_models_count }} models · {{ $related->published_tools_count }} tools</small></div>
                            <i data-lucide="chevron-right"></i>
                        </a>
                    @endforeach
                </div>
            </aside>
        </div>
    </div>
</section>
@endsection
