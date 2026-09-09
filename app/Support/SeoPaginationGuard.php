<?php

namespace App\Support;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;

final class SeoPaginationGuard
{
    /**
     * Prevent crawlable soft-404 pagination URLs such as ?page=9999.
     * Page-number syntax is normalized by NormalizeSeoPagination middleware;
     * this guard only needs the paginator's real final page.
     */
    public static function enforce(LengthAwarePaginator $paginator, Request $request): void
    {
        if (! $request->has('page')) {
            return;
        }

        $page = (int) $request->query('page', 1);

        if ($page > max(1, $paginator->lastPage())) {
            abort(404);
        }
    }
}
