<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Explicit PHP time budgets for hot /api/v1 routes (prevents silent 60s fatals).
 */
class SerikApiTimeBudgetMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $path = ltrim($request->path(), '/');
        $seconds = (int) config('serik.http.api_default_max_seconds', 55);

        foreach ((array) config('serik.http.api_route_max_seconds', []) as $pattern => $budget) {
            if (fnmatch($pattern, $path)) {
                $seconds = max(10, (int) $budget);
                break;
            }
        }

        @set_time_limit($seconds);
        @ini_set('max_execution_time', (string) $seconds);

        return $next($request);
    }
}
