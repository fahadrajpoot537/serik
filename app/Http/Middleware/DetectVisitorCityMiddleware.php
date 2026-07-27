<?php

namespace App\Http\Middleware;

use App\Support\SerikHomepage;
use App\Support\VisitorIpLocation;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class DetectVisitorCityMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        // Never block TTFB on external IP geo. Cookie (set by JS or prior visit)
        // is enough for homepage personalization / cache key.
        $response = $next($request);

        if ($request->cookie('serik_visitor_city')) {
            return $response;
        }

        // Homepage must stay cache-fast; city cookie is set client-side.
        // SEO landings like `/ontario/*` must also stay fast; the server-side IP lookup
        // can be slow and is not required for correct results (nav loads async).
        if (SerikHomepage::isHomepageRequest() || $request->is('ontario/*') || $request->routeIs('public.seo.ontario')) {
            return $response;
        }

        $location = VisitorIpLocation::resolveFromIp((string) $request->ip());
        $city = trim((string) ($location['city'] ?? ''));

        if ($city === '') {
            return $response;
        }

        return $response->withCookie(cookie(
            'serik_visitor_city',
            $city,
            60 * 24 * 30,
            '/',
            null,
            $request->isSecure(),
            false,
            false,
            'Lax'
        ));
    }
}
