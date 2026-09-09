<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Google AdSense connection
    |--------------------------------------------------------------------------
    |
    | Keep the publisher/client identifiers in one place so the public loader,
    | ads.txt audit and final readiness checks cannot silently drift apart.
    |
    */
    'enabled' => env('ADSENSE_ENABLED', true),
    'publisher_id' => env('ADSENSE_PUBLISHER_ID', 'pub-9025296892842875'),
    'client_id' => env('ADSENSE_CLIENT_ID', 'ca-pub-9025296892842875'),

    /*
    | Auto ads should only be eligible on real publisher-content surfaces.
    | Transactional/private/search/filter/legal pages intentionally do not load
    | the AdSense bootstrap script. Canonical ?page=N pagination is allowed.
    */
    'content_route_patterns' => [
        'home',
        'tools.index', 'tools.show',
        'models.index', 'models.show',
        'news.index', 'news.show',
        'comparisons.index', 'comparisons.show',
        'companies.index', 'companies.show',
        'articles.index', 'articles.show',
        'pricing.index', 'pricing.show',
        'benchmarks.index', 'benchmarks.show',
        'categories.index', 'categories.show', 'categories.subcategories.show',
        'features.index', 'features.show',
        'use-cases.index', 'use-cases.show',
        'topics.index', 'topics.show',
        'trending.index',
    ],

    'readiness' => [
        'min_published_tools' => 20,
        'min_approved_articles' => 15,
        'min_active_models' => 20,
        'min_indexable_model_percent' => 70,
        'min_indexable_article_percent' => 80,
        'min_quality_news' => 1,
        'min_valid_comparisons' => 2,
        'min_priced_tools' => 10,
        'min_verified_benchmarks' => 3,
    ],
];
