<?php

namespace App\Http\Middleware;

use App\Support\CanonicalUrl;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\Response;

class UseRequestRootUrlInLocal
{
    public function handle(Request $request, Closure $next): Response
    {
        if (CanonicalUrl::shouldNormalize($request)) {
            return $next($request);
        }

        if (app()->environment('local')) {
            // Use the full request root (host + subdirectory like /SERIK-01-06-2026/public),
            // not host-only. Host-only made CSS hrefs http://127.0.0.1/themes/... (404)
            // or a different port, which CSP style-src 'self' then blocks.
            $root = rtrim((string) $request->root(), '/');
            if ($root !== '') {
                URL::forceRootUrl($root);
            }
        }

        return $next($request);
    }
}
