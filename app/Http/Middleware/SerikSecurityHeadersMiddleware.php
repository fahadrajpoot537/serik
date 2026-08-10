<?php

namespace App\Http\Middleware;

use App\Support\SerikSecurityHeaders;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Global security headers (runs on every response, including early homepage HIT).
 */
class SerikSecurityHeadersMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        /** @var Response $response */
        $response = $next($request);

        return SerikSecurityHeaders::apply($response, $request);
    }
}
