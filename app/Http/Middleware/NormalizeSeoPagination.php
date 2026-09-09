<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class NormalizeSeoPagination
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! in_array($request->method(), ['GET', 'HEAD'], true) || ! $request->has('page')) {
            return $next($request);
        }

        $routeName = $request->route()?->getName();
        $paginatedRoutes = (array) config('seo.indexable_pagination_routes', []);

        if (! $routeName || ! in_array($routeName, $paginatedRoutes, true)) {
            return $next($request);
        }

        $value = $request->query('page');
        if (! is_scalar($value)) {
            abort(404);
        }

        $raw = (string) $value;

        if ($raw === '' || ! ctype_digit($raw) || (int) $raw < 1) {
            abort(404);
        }

        $page = (int) $raw;
        $normalized = (string) $page;

        // /directory?page=1 is a duplicate of /directory. Normalize leading
        // zero variants too so one crawlable URL exists for every result page.
        if ($page === 1 || $raw !== $normalized) {
            $query = $request->query();

            if ($page === 1) {
                unset($query['page']);
            } else {
                $query['page'] = $normalized;
            }

            $target = $request->url();
            if ($query !== []) {
                $target .= '?'.http_build_query($query);
            }

            return redirect()->away($target, 301);
        }

        return $next($request);
    }
}
