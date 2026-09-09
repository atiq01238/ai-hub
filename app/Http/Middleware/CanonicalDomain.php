<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CanonicalDomain
{
    public function handle(Request $request, Closure $next): Response
    {
        if (app()->environment('production') && in_array($request->method(), ['GET', 'HEAD'], true)) {
            $canonicalBase = rtrim((string) config('seo.canonical_url', config('app.url')), '/');
            $canonicalHost = parse_url($canonicalBase, PHP_URL_HOST);
            $canonicalScheme = parse_url($canonicalBase, PHP_URL_SCHEME) ?: 'https';
            $forwardedProto = strtolower(trim(explode(',', (string) $request->header('X-Forwarded-Proto'))[0] ?? ''));
            $requestScheme = in_array($forwardedProto, ['http', 'https'], true)
                ? $forwardedProto
                : $request->getScheme();

            $originMismatch = $canonicalHost && (
                strcasecmp($request->getHost(), $canonicalHost) !== 0
                || strcasecmp($requestScheme, $canonicalScheme) !== 0
            );

            // /index.php is a direct front-controller duplicate of the homepage
            // on Apache hosting. Consolidate it before crawlers can discover it.
            $indexPhpDuplicate = trim($request->getPathInfo(), '/') === 'index.php';

            if ($originMismatch || $indexPhpDuplicate) {
                $path = $indexPhpDuplicate ? '/' : $request->getPathInfo();
                $target = $canonicalBase.($path === '/' ? '' : '/'.ltrim($path, '/'));

                if ($request->getQueryString()) {
                    $target .= '?'.$request->getQueryString();
                }

                return redirect()->away($target ?: $canonicalBase.'/', 301);
            }
        }

        return $next($request);
    }
}
