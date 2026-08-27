<?php

return [
    'name' => env('BRAND_NAME', 'AI Orbit'),
    'legal_name' => env('BRAND_LEGAL_NAME', 'AI Orbit'),
    'domain' => env('BRAND_DOMAIN', 'ai-orbit.online'),
    'url' => rtrim(env('APP_URL', 'https://ai-orbit.online'), '/'),
    'tagline' => env('BRAND_TAGLINE', 'Explore • Compare • Stay Ahead'),
    'short_tagline' => env('BRAND_SHORT_TAGLINE', 'Discover • Compare • Decide'),
    'description' => 'Independent AI discovery, comparisons, pricing intelligence, benchmarks and news in one research-driven platform.',
    'features' => [
        // Keep the unfinished Test Lab available to admins while hiding it from all public surfaces.
        'public_test_lab' => filter_var(env('PUBLIC_TEST_LAB', false), FILTER_VALIDATE_BOOL),
    ],
    'assets' => [
        'logo' => 'images/brand/ai-orbit-logo.png',
        'wordmark' => 'images/brand/ai-orbit-wordmark.png',
        'icon' => 'images/brand/ai-orbit-icon-96.png',
        'favicon_32' => 'images/brand/favicon-32.png',
        'favicon_16' => 'images/brand/favicon-16.png',
        'apple_touch_icon' => 'images/brand/apple-touch-icon.png',
        'og_default' => 'images/brand/ai-orbit-og-default.jpg',
    ],
    'emails' => [
        'support' => env('BRAND_SUPPORT_EMAIL'),
        'contact' => env('BRAND_CONTACT_EMAIL'),
        'noreply' => env('MAIL_FROM_ADDRESS', 'noreply@ai-orbit.online'),
    ],
    'social' => [
        'x' => env('BRAND_X_URL'),
        'linkedin' => env('BRAND_LINKEDIN_URL'),
        'youtube' => env('BRAND_YOUTUBE_URL'),
    ],
];
