<?php

return [
    'site_name' => env('SEO_SITE_NAME', 'AI Orbit'),
    'canonical_url' => rtrim(env('SEO_CANONICAL_URL', env('APP_URL', 'https://ai-orbit.online')), '/'),
    'default_title' => 'AI Orbit — Discover, Compare & Understand AI',
    'default_description' => 'Discover AI tools and models, compare pricing and capabilities, explore benchmarks and follow AI news on AI Orbit.',
    'default_robots' => 'index,follow,max-image-preview:large,max-snippet:-1,max-video-preview:-1',
    'default_image' => 'images/brand/ai-orbit-og-default.jpg',
    'google_site_verification' => env('GOOGLE_SITE_VERIFICATION'),
    'bing_site_verification' => env('BING_SITE_VERIFICATION'),

    // Only these public directory/detail hubs may expose crawlable ?page=N URLs.
    // Any other query-string variant is noindexed globally by the SEO head partial.
    // Temporary GSC-driven crawl focus. Keep this list small and only add
    // evidence-complete model profiles that are already eligible for indexing.
    // Remove slugs once indexing stabilizes so this never becomes a permanent
    // ranking shortcut or a substitute for normal internal linking.
    'crawl_focus_model_slugs' => [
        'deepseek-r1',
        'deepseek-v31',
        'gemma-3-27b',
    ],

    // Temporary GSC-driven impression focus. These are not ranking overrides.
    // They are used only to surface already-relevant entities from canonical hubs
    // and to break ties inside existing semantic-link candidate sets.
    'impression_focus_tool_slugs' => [
        'akool',
        'codex',
        'ltx-studio',
    ],

    'impression_focus_model_slugs' => [
        'gpt-41-multimodal',
    ],

    'impression_focus_company_slugs' => [
        'omnisale',
    ],

    'indexable_pagination_routes' => [
        'tools.index',
        'models.index',
        'news.index',
        'comparisons.index',
        'companies.index',
        'articles.index',
        'reviews.index',
        'categories.show',
        'categories.subcategories.show',
        'features.show',
        'use-cases.show',
        'topics.show',
        'pricing.index',
    ],
];
