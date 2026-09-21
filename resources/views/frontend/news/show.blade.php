@extends('frontend.layouts.app')

@php
    $newsUrl = route('news.show', $news);

    $newsSeoTitle = trim($news->headline ?: 'AI News') . ' | AI Orbit';

    $newsSeoDescription = $news->meta_description
        ?: $news->ai_summary
        ?: $news->summary
        ?: 'Read the latest AI news, developments and industry intelligence on AI Orbit.';

    $newsSeoDescription = strip_tags($newsSeoDescription);

    for ($i = 0; $i < 5; $i++) {
        $decoded = html_entity_decode(
            $newsSeoDescription,
            ENT_QUOTES | ENT_HTML5,
            'UTF-8'
        );

        if ($decoded === $newsSeoDescription) {
            break;
        }

        $newsSeoDescription = $decoded;
    }

    $newsSchemaImage = $news->image_url
        ?: asset(config('brand.assets.og_default'));

    if (!\Illuminate\Support\Str::startsWith($newsSchemaImage, ['http://', 'https://'])) {
        $newsSchemaImage = url('/' . ltrim($newsSchemaImage, '/'));
    }

    $newsArticleSchema = [
        '@' . 'context' => 'https://schema.org',
        '@' . 'type' => 'NewsArticle',
        'headline' => $news->headline,
        'description' => $newsSeoDescription,
        'url' => $newsUrl,
        'mainEntityOfPage' => [
            '@' . 'type' => 'WebPage',
            '@' . 'id' => $newsUrl,
        ],
        'image' => [
            $newsSchemaImage
        ],
        'datePublished' => $news->published_at?->toAtomString(),
        'dateModified' => $news->updated_at?->toAtomString(),
        'author' => [
            '@' . 'type' => 'Organization',
            'name' => $news->source
                ?: ($news->company?->name ?? 'AI Orbit News Desk'),
        ],
        'publisher' => [
            '@' . 'type' => 'Organization',
            'name' => 'AI Orbit',
            'url' => route('home'),
            'logo' => [
                '@' . 'type' => 'ImageObject',
                'url' => asset('images/brand/ai-orbit-logo.png'),
            ],
        ],
    ];

    $newsBreadcrumbSchema = [
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
                'name' => 'AI News',
                'item' => route('news.index'),
            ],
            [
                '@' . 'type' => 'ListItem',
                'position' => 3,
                'name' => $news->headline,
                'item' => $newsUrl,
            ],
        ],
    ];
@endphp

@section('title', $newsSeoTitle)

@section(
    'meta_description',
    \Illuminate\Support\Str::limit($newsSeoDescription, 160, '')
)

@section('canonical', $newsUrl)

@section(
    'robots',
    $seoQuality['robots'] ?? 'noindex,follow'
)

@section('og_type', 'article')

@section(
    'og_image',
    $news->image_url ?: asset(config('brand.assets.og_default'))
)

@push('head')
<script type="application/ld+json">{!! json_encode(
    $newsArticleSchema,
    JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
) !!}</script>

<script type="application/ld+json">{!! json_encode(
    $newsBreadcrumbSchema,
    JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
) !!}</script>
@endpush
@push('styles')
<link rel="stylesheet" href="{{ asset('css/frontend/news.css') }}">
@endpush

@php
    $image = $news->image_url;
    $summary = $news->ai_summary ?: $news->summary;
    $why = $news->ai_why_it_matters ?: $news->why_it_matters;
    $sourceName = $news->newsSource?->name ?: ($news->source ?: ($news->company?->name ?? 'Primary source'));
    $isVerifiedStory = $news->verification_status === 'verified';
    $connectedCount = $relatedTools->count() + $relatedModels->count();
@endphp

@section('content')
<div class="news-container news-detail-page">
    <div class="news-breadcrumb"><a href="{{ route('home') }}">Home</a><i data-lucide="chevron-right"></i><a href="{{ route('news.index') }}">AI News</a><i data-lucide="chevron-right"></i><span>{{ $news->category ?? 'Update' }}</span></div>

    <header class="news-detail-header">
        <div class="news-detail-meta"><span class="news-category">{{ $news->category ?? 'AI Update' }}</span><?php if ($news->verification_status==='verified'): ?><span class="verified"><i data-lucide="badge-check"></i>Verified</span><?php endif; ?><span class="importance-inline"><i data-lucide="activity"></i>Importance {{ (int)$news->importance }}/100</span></div>
        <h1>{{ $news->headline }}</h1>
        <?php if ($summary): ?><p class="news-deck">{{ $summary }}</p><?php endif; ?>
        <div class="news-byline">
            <div class="source-identity"><?php if ($news->company): ?><img src="{{ $news->company->logo_url }}" alt="{{ $news->company->name }}"><?php else: ?><span><i data-lucide="radio"></i></span><?php endif; ?><div><strong>{{ $news->source ?: ($news->company?->name ?? 'AI Orbit News Desk') }}</strong><small><?php if ($news->published_at): ?>Published {{ $news->published_at->format('M j, Y \a\t g:i A') }}<?php endif; ?></small></div></div>
            <div class="news-detail-actions"><button type="button" data-save-item data-save-type="news" data-save-id="{{ $news->id }}" aria-pressed="false"><i data-lucide="bookmark"></i><span data-save-label data-default-label="Save">Save</span></button><button type="button" data-copy-url><i data-lucide="link-2"></i>Copy link</button><?php if ($news->source_url): ?><a href="{{ $news->source_url }}" target="_blank" rel="noopener noreferrer">Original source<i data-lucide="external-link"></i></a><?php endif; ?></div>
        </div>
    </header>

    <div class="news-detail-layout">
        <main class="news-article">
            <figure class="news-hero-image"><?php if ($image): ?><img src="{{ $image }}" alt="{{ $news->headline }}"><?php else: ?><div class="news-media-placeholder news-media-placeholder--detail"><i data-lucide="image-off"></i><strong>No story image provided</strong><span>This article was published without a selected image.</span></div><?php endif; ?><figcaption>AI Orbit intelligence brief • Source context preserved below.</figcaption></figure>

            <section class="intelligence-summary"><div class="intelligence-icon"><i data-lucide="sparkles"></i></div><div><span>AI ORBIT SUMMARY</span><h2>What happened</h2><p>{{ $summary ?: 'A concise summary is not available for this item yet. Open the original source for the complete report.' }}</p></div><?php if ($news->ai_confidence): ?><div class="confidence"><strong>{{ (int)$news->ai_confidence }}%</strong><small>processing confidence</small></div><?php endif; ?></section>

            <?php if ($why): ?><section class="article-section"><span class="article-eyebrow">WHY IT MATTERS</span><h2>Why this deserves attention</h2><p>{{ $why }}</p></section><?php endif; ?>

            <section class="news-evidence-brief" aria-label="Source-grounded context">
                <div class="news-evidence-brief__head"><span class="article-eyebrow">SOURCE-GROUNDED CONTEXT</span><h2>What is confirmed, and what is not</h2></div>
                <div class="news-evidence-brief__grid">
                    <article>
                        <span><i data-lucide="badge-check"></i></span>
                        <div><h3>Confirmed record</h3><p>AI Orbit links this brief to <strong>{{ $sourceName }}</strong><?php if ($news->published_at): ?>, published {{ $news->published_at->format('M j, Y') }}<?php endif; ?>. The stored source record is {{ $isVerifiedStory ? 'marked verified' : 'not yet marked fully verified' }}.</p><?php if ($news->company): ?><a href="{{ route('companies.show',$news->company) }}">Open {{ $news->company->name }} company context <i data-lucide="arrow-right"></i></a><?php endif; ?></div>
                    </article>
                    <article>
                        <span><i data-lucide="triangle-alert"></i></span>
                        <div><h3>Scope of this brief</h3><p>AI Orbit does not independently reproduce or certify every claim in the publisher's report. The original source remains the primary reference for quotations, full reporting and details not stored in this intelligence record.</p><?php if ($news->verification_notes): ?><small>{{ $news->verification_notes }}</small><?php endif; ?></div>
                    </article>
                    <?php if ($connectedCount > 0 || $deeperAnalysis): ?>
                    <article>
                        <span><i data-lucide="waypoints"></i></span>
                        <div><h3>Connected AI Orbit context</h3><p>This update is linked to {{ $connectedCount }} relevant product/model profile{{ $connectedCount === 1 ? '' : 's' }}<?php if ($deeperAnalysis): ?> and a deeper editorial analysis<?php endif; ?>, so readers can move from the headline to structured product and company context.</p></div>
                    </article>
                    <?php endif; ?>
                </div>
            </section>

            <section class="article-section"><span class="article-eyebrow">WHAT AI ORBIT ADDS</span><h2>How to use this update</h2><p>This page is an intelligence brief rather than a replacement for the publisher's article. AI Orbit preserves the source link, verification state, timing and related entities so the update can be checked against the broader product, model and company records on the site.</p></section>

            <?php if ($tags->isNotEmpty()): ?><div class="article-tags"><span>Topics</span><?php foreach ($tags as $tag): ?><a href="{{ route('news.index',['q'=>$tag]) }}">{{ $tag }}</a><?php endforeach; ?></div><?php endif; ?>

            <section class="source-audit">
                <div class="source-audit-head"><div><span>SOURCE TRANSPARENCY</span><h2>Intelligence record</h2></div><i data-lucide="scan-search"></i></div>
                <div class="audit-grid">
                    <div><span>Verification</span><strong class="status-{{ $news->verification_status }}">{{ str_replace('_',' ',ucfirst($news->verification_status)) }}</strong></div>
                    <div><span>Sentiment</span><strong>{{ ucfirst($news->sentiment ?? 'neutral') }}</strong></div>
                    <div><span>Importance</span><strong>{{ (int)$news->importance }}/100</strong></div>
                    <div><span>AI topic</span><strong>{{ $news->ai_topic ?: ($news->category ?: 'General AI') }}</strong></div>
                    <?php if ($news->fetched_at): ?><div><span>Source fetched</span><strong>{{ $news->fetched_at->diffForHumans() }}</strong></div><?php endif; ?>
                </div>
                <?php if ($news->verification_notes): ?><p class="audit-note"><i data-lucide="info"></i>{{ $news->verification_notes }}</p><?php endif; ?>
            </section>

            <?php if ($deeperAnalysis): ?>
            <section class="news-deeper-analysis">
                <span class="news-analysis-icon"><i data-lucide="book-open-check"></i></span>
                <div><small>DEEPER ANALYSIS</small><h2>{{ $deeperAnalysis->title }}</h2><p>{{ Str::limit($deeperAnalysis->summary,155) }}</p><a href="{{ route('articles.show',$deeperAnalysis) }}">Read the full AI Orbit analysis <i data-lucide="arrow-right"></i></a></div>
            </section>
            <?php endif; ?>

            <?php if ($relatedTools->isNotEmpty() || $relatedModels->isNotEmpty()): ?>
            <section class="related-intelligence"><div class="detail-section-title"><span>CONNECTED INTELLIGENCE</span><h2>Related AI products</h2></div>
                <div class="connected-grid">
                    <?php foreach ($relatedTools as $tool): ?><a class="connected-card" href="{{ route('tools.show',$tool) }}"><img src="{{ $tool->logo_url }}" alt="{{ $tool->name }}"><div><span>AI TOOL</span><strong>{{ $tool->name }}</strong><small>{{ $tool->company?->name }}</small></div><i data-lucide="arrow-up-right"></i></a><?php endforeach; ?>
                    <?php foreach ($relatedModels as $model): ?><a class="connected-card" href="{{ route('models.show',$model) }}"><img src="{{ $model->logo_url }}" alt="{{ $model->name }}"><div><span>AI MODEL</span><strong>{{ $model->name }}</strong><small>{{ $model->company?->name }}</small></div><i data-lucide="arrow-up-right"></i></a><?php endforeach; ?>
                </div>
            </section>
            <?php endif; ?>

            <nav class="article-next-prev">
                <?php if ($previous): ?><a href="{{ route('news.show',$previous) }}"><span><i data-lucide="arrow-left"></i>Previous</span><strong>{{ Str::limit($previous->headline,70) }}</strong></a><?php else: ?><div></div><?php endif; ?>
                <?php if ($next): ?><a class="next" href="{{ route('news.show',$next) }}"><span>Next<i data-lucide="arrow-right"></i></span><strong>{{ Str::limit($next->headline,70) }}</strong></a><?php endif; ?>
            </nav>
        </main>

        <aside class="news-detail-sidebar">
            <div class="detail-side-card sticky-side">
                <span class="detail-side-kicker">AT A GLANCE</span><h3>Story intelligence</h3>
                <div class="signal-meter"><div><span>Importance</span><strong>{{ (int)$news->importance }}</strong></div><div class="meter"><span style="width:{{ min(100,max(0,(int)$news->importance)) }}%"></span></div></div>
                <dl><div><dt>Category</dt><dd>{{ $news->category ?: 'AI Update' }}</dd></div><div><dt>Company</dt><dd>{{ $news->company?->name ?: '—' }}</dd></div><div><dt>Source</dt><dd>{{ $news->newsSource?->name ?: ($news->source ?: '—') }}</dd></div><div><dt>Verification</dt><dd>{{ str_replace('_',' ',ucfirst($news->verification_status)) }}</dd></div></dl>
                <?php if ($news->source_url): ?><a class="source-button" href="{{ $news->source_url }}" target="_blank" rel="noopener noreferrer">Read primary source<i data-lucide="external-link"></i></a><?php endif; ?>
            </div>
        </aside>
    </div>

    <?php if ($relatedNews->isNotEmpty()): ?><section class="related-news-section"><div class="news-section-head"><div><span>KEEP EXPLORING</span><h2>Related AI news</h2></div><a href="{{ route('news.index') }}">View all news <i data-lucide="arrow-right"></i></a></div><div class="related-news-grid"><?php foreach ($relatedNews as $item): ?><a class="related-news-card" href="{{ route('news.show',$item) }}"><?php if ($item->image_url): ?><img src="{{ $item->image_url }}" alt="{{ $item->headline }}"><?php else: ?><div class="news-media-placeholder"><i data-lucide="image-off"></i></div><?php endif; ?><div><span>{{ $item->category ?? 'AI Update' }}</span><h3>{{ $item->headline }}</h3><small>{{ $item->source ?: $item->company?->name }} • {{ optional($item->published_at)->diffForHumans() }}</small></div></a><?php endforeach; ?></div></section><?php endif; ?>
</div>
@endsection

@push('scripts')<script src="{{ asset('js/frontend/news.js') }}"></script>@endpush
