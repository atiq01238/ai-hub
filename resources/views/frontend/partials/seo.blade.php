@php
    $normalizeSeoText = static function ($value, $fallback = '') {
        $text = trim((string) $value);

        if ($text === '') {
            $text = trim((string) $fallback);
        }

        for ($i = 0; $i < 5; $i++) {
            $decoded = html_entity_decode(
                $text,
                ENT_QUOTES | ENT_HTML5,
                'UTF-8'
            );

            if ($decoded === $text) {
                break;
            }

            $text = $decoded;
        }

        $text = trim(strip_tags($text));
        $text = preg_replace('/\s+/u', ' ', $text) ?: $text;

        return $text;
    };

    $seoTitle = $normalizeSeoText(
        $__env->yieldContent('title'),
        config('seo.default_title')
    );

    $seoDescription = $normalizeSeoText(
        $__env->yieldContent('meta_description'),
        config('seo.default_description')
    );

    // Phase 2: apply the persisted Phase 1 keyword owner to live head metadata.
    // Routes without a persisted SEO target keep their existing title/description.
    try {
        $intentMetadata = app(\App\Services\Seo\SeoMetadataService::class)
            ->forRequest(request(), $seoTitle, $seoDescription);

        if (!empty($intentMetadata['target_key'])) {
            $seoTitle = $normalizeSeoText($intentMetadata['title'], $seoTitle);
            $seoDescription = $normalizeSeoText($intentMetadata['description'], $seoDescription);
        }
    } catch (\Throwable $e) {
        report($e);
    }

    $routeName = request()->route()?->getName();
    $paginationRoutes = (array) config('seo.indexable_pagination_routes', []);
    $isIndexablePaginationRoute = $routeName && in_array($routeName, $paginationRoutes, true);
    $allowedCanonicalQueryKeys = $isIndexablePaginationRoute ? ['page'] : [];

    $canonicalBase = rtrim((string) config('seo.canonical_url'), '/');
    $requestCanonicalPath = request()->path() === '/' ? '' : '/'.ltrim(request()->path(), '/');
    $rawCanonical = trim($__env->yieldContent('canonical')) ?: $canonicalBase.$requestCanonicalPath;
    $canonicalParts = parse_url($rawCanonical) ?: [];
    $canonicalPath = (string) ($canonicalParts['path'] ?? $requestCanonicalPath);
    $canonicalPath = $canonicalPath === '/' ? '' : '/'.ltrim($canonicalPath, '/');
    $canonicalQuery = [];

    if (!empty($canonicalParts['query'])) {
        parse_str((string) $canonicalParts['query'], $canonicalQuery);
        $canonicalQuery = array_intersect_key($canonicalQuery, array_flip($allowedCanonicalQueryKeys));
    }

    if (array_key_exists('page', $canonicalQuery)) {
        $canonicalPage = (int) $canonicalQuery['page'];
        if ($canonicalPage <= 1) {
            unset($canonicalQuery['page']);
        } else {
            $canonicalQuery['page'] = $canonicalPage;
        }
    }

    $seoCanonical = $canonicalBase.$canonicalPath;
    if ($canonicalQuery !== []) {
        $seoCanonical .= '?'.http_build_query($canonicalQuery);
    }

    $seoOgType = trim($__env->yieldContent('og_type')) ?: 'website';
    $seoImage = trim($__env->yieldContent('og_image')) ?: asset(config('seo.default_image'));
    if ($seoImage !== '' && !\Illuminate\Support\Str::startsWith($seoImage, ['http://', 'https://'])) {
        $seoImage = asset(ltrim($seoImage, '/'));
    }

    $privateSeoRoute = request()->routeIs(
        'search.*',
        'account.*',
        'saved.*',
        'user.*',
        'login',
        'login.2fa',
        'signup',
        'logout',
        'password.*',
        'verification.*',
        'social.*',
        'email.*',
        'submissions.*',
        'reviews.create',
        'reviews.models.create',
        'comparisons.builder',
        'comparisons.preview',
        'comparisons.my',
        'testlab.*'
    );

    // Query-string variants are crawlable only for canonical pagination. Filters,
    // tracking parameters and accidental query keys consolidate to the clean URL
    // and remain noindex,follow so they cannot create an indexable crawl trap.
    $queryKeys = array_keys(request()->query());
    $nonCanonicalQueryKeys = array_values(array_diff($queryKeys, $allowedCanonicalQueryKeys));
    $hasNonCanonicalQuery = $nonCanonicalQueryKeys !== [];

    $seoRobots = trim($__env->yieldContent('robots'));
    if ($seoRobots === '') {
        $seoRobots = ($privateSeoRoute || $hasNonCanonicalQuery)
            ? 'noindex,follow'
            : config('seo.default_robots');
    } elseif ($hasNonCanonicalQuery && !str_contains(strtolower($seoRobots), 'noindex')) {
        $seoRobots = 'noindex,follow';
    }

    $brandUrl = config('brand.url');
    $brandLogo = asset(config('brand.assets.logo'));
    $sameAs = collect(config('brand.social'))->filter()->values()->all();
    $siteSchemas = [
        [
            '@context' => 'https://schema.org',
            '@type' => 'Organization',
            '@id' => $brandUrl.'/#organization',
            'name' => config('brand.name'),
            'url' => $brandUrl,
            'logo' => [
                '@type' => 'ImageObject',
                'url' => $brandLogo,
            ],
            'description' => config('brand.description'),
            'email' => config('brand.emails.contact'),
            'sameAs' => $sameAs ?: null,
        ],
        [
            '@context' => 'https://schema.org',
            '@type' => 'WebSite',
            '@id' => $brandUrl.'/#website',
            'url' => $brandUrl,
            'name' => config('brand.name'),
            'description' => config('seo.default_description'),
            'publisher' => ['@id' => $brandUrl.'/#organization'],
            'potentialAction' => [
                '@type' => 'SearchAction',
                'target' => $brandUrl.'/search?q={search_term_string}',
                'query-input' => 'required name=search_term_string',
            ],
        ],
    ];
@endphp
<title>{{ $seoTitle }}</title>
<meta name="description" content="{{ $seoDescription }}">
<meta name="robots" content="{{ $seoRobots }}">
<link rel="canonical" href="{{ $seoCanonical }}">
<meta property="og:locale" content="en_US">
<meta property="og:type" content="{{ $seoOgType }}">
<meta property="og:site_name" content="{{ config('brand.name') }}">
<meta property="og:title" content="{{ $seoTitle }}">
<meta property="og:description" content="{{ $seoDescription }}">
<meta property="og:url" content="{{ $seoCanonical }}">
<meta property="og:image" content="{{ $seoImage }}">
<meta property="og:image:alt" content="{{ config('brand.name') }} — {{ config('brand.tagline') }}">
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="{{ $seoTitle }}">
<meta name="twitter:description" content="{{ $seoDescription }}">
<meta name="twitter:image" content="{{ $seoImage }}">
@if(config('seo.google_site_verification'))<meta name="google-site-verification" content="{{ config('seo.google_site_verification') }}">@endif
@if(config('seo.bing_site_verification'))<meta name="msvalidate.01" content="{{ config('seo.bing_site_verification') }}">@endif
@foreach($siteSchemas as $schema)
<script type="application/ld+json">{!! json_encode(array_filter($schema, fn($value) => $value !== null && $value !== []), JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE) !!}</script>
@endforeach
