<?php

return [
    'enabled' => env('ANALYTICS_TRACKING_ENABLED', true),
    'exclude_admins' => env('ANALYTICS_EXCLUDE_ADMINS', true),
    'respect_dnt' => env('ANALYTICS_RESPECT_DNT', true),
    'cookie_name' => env('ANALYTICS_COOKIE_NAME', 'ao_visitor'),
    'cookie_days' => (int) env('ANALYTICS_COOKIE_DAYS', 365),

    // Public page tracking intentionally excludes admin/auth/account/system endpoints.
    'excluded_route_prefixes' => [
        'admin.', 'sitemap.', 'media.', 'account.', 'notifications.', 'verification.', 'password.', 'login.', 'auth.',
    ],
    'excluded_route_names' => [
        'login', 'logout', 'signup', 'register', 'search.suggest', 'search.click', 'login.2fa', 'login.2fa.verify',
    ],
    'excluded_path_prefixes' => [
        'admin', 'dashboard', 'api', 'media', 'storage', 'email', 'auth', 'login', 'logout', 'signup', 'register',
        'password', 'forgot-password', 'reset-password', 'account', 'notifications', '_debugbar', 'up', 'sitemap',
    ],
];
