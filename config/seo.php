<?php

return [
    'site_name' => env('SEO_SITE_NAME', 'AI Orbit'),
    'canonical_url' => rtrim(env('SEO_CANONICAL_URL', env('APP_URL', 'https://ai-orbit.online')), '/'),
    'default_title' => 'AI Orbit — Discover, Compare & Understand AI',
    'default_description' => 'Discover AI tools and models, compare pricing and capabilities, explore benchmarks, follow AI news and review controlled Test Lab evaluations on AI Orbit.',
    'default_robots' => 'index,follow,max-image-preview:large,max-snippet:-1,max-video-preview:-1',
    'default_image' => 'images/brand/ai-orbit-og-default.jpg',
    'google_site_verification' => env('GOOGLE_SITE_VERIFICATION'),
    'bing_site_verification' => env('BING_SITE_VERIFICATION'),
];
