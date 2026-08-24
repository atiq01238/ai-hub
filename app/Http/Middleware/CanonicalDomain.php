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
            $canonicalHost = parse_url((string) config('app.url'), PHP_URL_HOST);

            if ($canonicalHost && strcasecmp($request->getHost(), $canonicalHost) !== 0) {
                $target = rtrim((string) config('app.url'), '/').'/'.ltrim($request->getRequestUri(), '/');
                return redirect()->away($target, 301);
            }
        }

        return $next($request);
    }
}
