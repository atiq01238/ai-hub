@extends('frontend.layouts.app')

@section('title', html_entity_decode(
    $article->seo_title ?: $article->title . ' | AI Orbit',
    ENT_QUOTES | ENT_HTML5,
    'UTF-8'
))

@section('meta_description', html_entity_decode(
    $article->meta_description ?: $article->summary ?: 'AI Orbit article',
    ENT_QUOTES | ENT_HTML5,
    'UTF-8'
))

@section('canonical', route('articles.show', $article))
@section('robots', 'index,follow,max-image-preview:large,max-snippet:-1,max-video-preview:-1')
@section('og_type', 'article')
@section('og_image', $article->featured_image_url ?: asset(config('brand.assets.og_default')))

@push('head')
@php
    $articleFaq = collect(\App\Support\ArticleContent::faq($article->content));
    $articleSchemaHeadline = (string) $article->title;
    $articleSchemaDescription = (string) ($article->meta_description
        ?: $article->summary
        ?: 'AI Orbit article');

    for ($i = 0; $i < 5; $i++) {
        $decodedHeadline = html_entity_decode($articleSchemaHeadline, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $decodedDescription = html_entity_decode($articleSchemaDescription, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        if ($decodedHeadline === $articleSchemaHeadline && $decodedDescription === $articleSchemaDescription) {
            break;
        }
        $articleSchemaHeadline = $decodedHeadline;
        $articleSchemaDescription = $decodedDescription;
    }
    $articleSchemaHeadline = trim(strip_tags($articleSchemaHeadline));
    $articleSchemaDescription = trim(strip_tags($articleSchemaDescription));

    $seoImage = $article->featured_image_url
        ?: url('/images/frontend/content-placeholder.svg');

    if (!\Illuminate\Support\Str::startsWith($seoImage, ['http://', 'https://'])) {
        $seoImage = url('/' . ltrim($seoImage, '/'));
    }

    $articleSchema = [
        '@' . 'context' => 'https://schema.org',
        '@' . 'type' => 'Article',
        'headline' => $articleSchemaHeadline,
        'description' => $articleSchemaDescription,
        'image' => [$seoImage],
        'datePublished' => optional($article->published_at)->toIso8601String(),
        'dateModified' => optional($article->updated_at)->toIso8601String(),
        'author' => [
            '@' . 'type' => 'Person',
            'name' => $article->author?->name ?? 'AI Orbit Editorial',
        ],
        'publisher' => [
            '@' . 'type' => 'Organization',
            'name' => 'AI Orbit',
            'url' => route('home'),
        ],
        'mainEntityOfPage' => [
            '@' . 'type' => 'WebPage',
            '@' . 'id' => route('articles.show', $article),
        ],
    ];

    $breadcrumbSchema = [
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
                'name' => 'Articles',
                'item' => route('articles.index'),
            ],
            [
                '@' . 'type' => 'ListItem',
                'position' => 3,
                'name' => $articleSchemaHeadline,
                'item' => route('articles.show', $article),
            ],
        ],
    ];

    $faqSchema = null;

    if ($articleFaq->isNotEmpty()) {
        $faqSchema = [
            '@' . 'context' => 'https://schema.org',
            '@' . 'type' => 'FAQPage',
            'mainEntity' => $articleFaq->map(fn ($item) => [
                '@' . 'type' => 'Question',
                'name' => $item['question'],
                'acceptedAnswer' => [
                    '@' . 'type' => 'Answer',
                    'text' => $item['answer'],
                ],
            ])->values()->all(),
        ];
    }
@endphp

<script type="application/ld+json">{!! json_encode(
    $articleSchema,
    JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
) !!}</script>

<script type="application/ld+json">{!! json_encode(
    $breadcrumbSchema,
    JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
) !!}</script>

@if($faqSchema)
<script type="application/ld+json">{!! json_encode(
    $faqSchema,
    JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
) !!}</script>
@endif
@endpush

@push('styles')<link rel="stylesheet" href="{{ asset('css/frontend/content.css') }}">@endpush
@section('content')
@php
$image = $article->featured_image_url ?: '/images/frontend/content-placeholder.svg';
$readTime=max(1,(int)ceil(str_word_count(strip_tags($article->content ?? $article->summary ?? ''))/220));
$tags=collect($article->tags ?? [])->merge($article->tagTerms->pluck('name'))->filter()->unique();
@endphp
<section class="article-detail-hero"><div class="content-wrap"><div class="content-breadcrumb"><a href="{{ route('home') }}">Home</a><i data-lucide="chevron-right"></i><a href="{{ route('articles.index') }}">Articles</a><i data-lucide="chevron-right"></i><span>{{ \Illuminate\Support\Str::limit($article->title,45) }}</span></div><div class="article-detail-grid"><div><span class="article-category">{{ $article->category ?: $article->categoryTerm?->name ?: 'Article' }}</span><h1>{{ $article->title }}</h1><p class="article-deck">{{ $article->summary }}</p><div class="article-byline"><span><i data-lucide="user-round"></i>{{ $article->author?->name ?? 'AI Orbit Editorial' }}</span><span><i data-lucide="calendar-days"></i>{{ optional($article->published_at)->format('F j, Y') }}</span><span><i data-lucide="clock-3"></i>{{ $readTime }} min read</span>@if($article->reviewer)<span><i data-lucide="badge-check"></i>Reviewed by {{ $article->reviewer->name }}</span>@endif</div></div><div class="article-detail-image"><img src="{{ $image }}" alt="{{ $article->title }}"><span><i data-lucide="shield-check"></i> Editorially approved</span></div></div></div></section>
<section class="article-reading"><div class="content-wrap article-reading-grid"><article class="article-body"><div class="article-summary-box"><span>THE BRIEF</span><p>{{ $article->summary ?: 'A practical AI Orbit analysis focused on the information that matters for product and model decisions.' }}</p></div><div class="article-prose">{!! \App\Support\ArticleContent::render($article->content ?: $article->summary) !!}</div>
@include('frontend.partials.quick-vote', [
    'type' => 'article',
    'id' => $article->id,
    'summary' => $articleFeedback,
    'label' => 'Was this article helpful?',
])
@if($tags->isNotEmpty())<div class="article-tags"><span>Topics</span>@foreach($tags as $tag)<a href="{{ route('articles.index',['q'=>$tag]) }}">#{{ $tag }}</a>@endforeach</div>@endif
@if($relatedTools->isNotEmpty() || $relatedModels->isNotEmpty())<section class="article-related-block"><div class="block-heading"><span>CONNECTED TO THIS GUIDE</span><h2>Related AI products</h2></div><div class="article-product-grid">@foreach($relatedTools as $tool)@php $logo=$tool->logo_url; @endphp<a href="{{ route('tools.show',$tool) }}"><img src="{{ $logo }}" alt=""><div><small>AI TOOL</small><strong>{{ $tool->name }}</strong><span>{{ $tool->company?->name }}</span></div><i data-lucide="arrow-up-right"></i></a>@endforeach @foreach($relatedModels as $model)@php $logo=$model->logo_url; @endphp<a href="{{ route('models.show',$model) }}"><img src="{{ $logo }}" alt=""><div><small>AI MODEL</small><strong>{{ $model->name }}</strong><span>{{ $model->company?->name }}</span></div><i data-lucide="arrow-up-right"></i></a>@endforeach</div></section>@endif
<div class="article-prev-next"><div>@if($previous)<small>Previous</small><a href="{{ route('articles.show',$previous) }}"><i data-lucide="arrow-left"></i>{{ \Illuminate\Support\Str::limit($previous->title,55) }}</a>@endif</div><div>@if($next)<small>Next</small><a href="{{ route('articles.show',$next) }}">{{ \Illuminate\Support\Str::limit($next->title,55) }}<i data-lucide="arrow-right"></i></a>@endif</div></div></article>
<aside class="article-aside"><div class="side-card article-author"><span class="side-label">PUBLISHED BY</span><div class="author-avatar">{{ strtoupper(substr($article->author?->name ?? 'AI',0,2)) }}</div><h3>{{ $article->author?->name ?? 'AI Orbit Editorial' }}</h3><p>AI Orbit editorial content is structured around product intelligence, benchmarks and practical decision-making.</p></div>@if($article->company)<div class="side-card"><span class="side-label">COMPANY</span><h3>{{ $article->company->name }}</h3><p>{{ \Illuminate\Support\Str::limit($article->company->description,110) }}</p><a class="side-action" href="{{ route('companies.show',$article->company) }}">Company profile <i data-lucide="arrow-right"></i></a></div>@endif<div class="side-card"><span class="side-label">SHARE</span><div class="share-row"><button type="button" data-save-item data-save-type="article" data-save-id="{{ $article->id }}" aria-label="Save article" aria-pressed="false"><i data-lucide="bookmark"></i></button><button type="button" onclick="navigator.clipboard?.writeText(location.href)"><i data-lucide="link"></i></button><a href="mailto:?subject={{ rawurlencode($article->title) }}&body={{ rawurlencode(request()->fullUrl()) }}"><i data-lucide="mail"></i></a></div></div></aside></div>
@if($relatedArticles->isNotEmpty())<div class="content-wrap related-reading"><div class="block-heading"><span>KEEP READING</span><h2>Related guides & analysis</h2></div><div class="related-reading-grid">@foreach($relatedArticles as $related)<a href="{{ route('articles.show',$related) }}"><small>{{ $related->category ?: 'Article' }}</small><h3>{{ $related->title }}</h3><p>{{ \Illuminate\Support\Str::limit($related->summary,100) }}</p><span>{{ optional($related->published_at)->format('M j, Y') }} <i data-lucide="arrow-right"></i></span></a>@endforeach</div></div>@endif
</section>
@endsection
