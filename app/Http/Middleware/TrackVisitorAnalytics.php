<?php

namespace App\Http\Middleware;

use App\Services\Analytics\VisitorTrackingService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class TrackVisitorAnalytics
{
    public function __construct(private readonly VisitorTrackingService $tracking)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! $this->shouldTrack($request, $response)) {
            return $response;
        }

        $cookieName = (string) config('analytics.cookie_name', 'ao_visitor');
        $visitorToken = (string) $request->cookie($cookieName, '');
        $isNewCookie = ! preg_match('/^[A-Za-z0-9_-]{20,100}$/', $visitorToken);

        if ($isNewCookie) {
            $visitorToken = Str::random(48);
            $minutes = max(1, (int) config('analytics.cookie_days', 365)) * 1440;
            $response->headers->setCookie(cookie(
                $cookieName,
                $visitorToken,
                $minutes,
                '/',
                null,
                $request->isSecure(),
                true,
                false,
                'Lax'
            ));
        }

        try {
            $this->tracking->record($request, $visitorToken);
        } catch (\Throwable $e) {
            // Analytics must never break a public request (including before migration is run).
            Log::debug('Visitor analytics record skipped.', ['message' => $e->getMessage()]);
        }

        return $response;
    }

    private function shouldTrack(Request $request, Response $response): bool
    {
        if (! config('analytics.enabled', true) || ! $request->isMethod('GET')) {
            return false;
        }

        if (! $response->isSuccessful()) {
            return false;
        }

        $contentType = strtolower((string) $response->headers->get('Content-Type', ''));
        if ($contentType !== '' && ! str_contains($contentType, 'text/html')) {
            return false;
        }

        if ($request->ajax() || $request->expectsJson()) {
            return false;
        }

        if (config('analytics.respect_dnt', true) && $request->headers->get('DNT') === '1') {
            return false;
        }

        $purpose = strtolower((string) ($request->headers->get('Sec-Purpose') ?: $request->headers->get('Purpose', '')));
        if (str_contains($purpose, 'prefetch') || str_contains($purpose, 'prerender')) {
            return false;
        }

        if ($this->tracking->isBot($request->userAgent())) {
            return false;
        }

        if (config('analytics.exclude_admins', true) && $request->user()) {
            $user = $request->user();
            if (method_exists($user, 'hasAdminPanelAccess') && $user->hasAdminPanelAccess()) {
                return false;
            }
        }

        $routeName = (string) ($request->route()?->getName() ?? '');
        if (in_array($routeName, (array) config('analytics.excluded_route_names', []), true)) {
            return false;
        }

        foreach ((array) config('analytics.excluded_route_prefixes', []) as $prefix) {
            if ($prefix !== '' && str_starts_with($routeName, $prefix)) {
                return false;
            }
        }

        $path = trim($request->path(), '/');
        foreach ((array) config('analytics.excluded_path_prefixes', []) as $prefix) {
            $prefix = trim((string) $prefix, '/');
            if ($prefix !== '' && ($path === $prefix || str_starts_with($path, $prefix . '/'))) {
                return false;
            }
        }

        return true;
    }
}
