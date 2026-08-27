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

    $canonicalBase = rtrim((string) config('seo.canonical_url'), '/');
    $canonicalPath = request()->path() === '/' ? '' : '/'.ltrim(request()->path(), '/');
    $seoCanonical = trim($__env->yieldContent('canonical')) ?: $canonicalBase.$canonicalPath;
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
    $seoRobots = trim($__env->yieldContent('robots'));
    if ($seoRobots === '') {
        $seoRobots = ($privateSeoRoute || request()->query())
            ? 'noindex,follow'
            : config('seo.default_robots');
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
