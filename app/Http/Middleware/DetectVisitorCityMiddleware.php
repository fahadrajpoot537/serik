<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class DetectVisitorCityMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        // City cookie is set by visitor-location.js (and reused on later visits).
        // A server-side ip-api lookup AFTER the controller still delays TTFB
        // because PHP does not flush until this middleware returns (measured
        // +2–4s on listing/map/detail). The lookup does not affect the current
        // response body — it only set a cookie for the *next* request.
        return $next($request);
    }
}
