<?php

namespace App\Http\Middleware;

use App\Support\CanonicalUrl;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ForceCanonicalDomainMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (CanonicalUrl::shouldNormalize($request)) {
            $pathInfo = (string) $request->getPathInfo();

            // /index.php and /index.php/... are non-canonical homepage variants.
            if ($pathInfo === '/index.php' || str_starts_with($pathInfo, '/index.php/')) {
                $cleanPath = substr($pathInfo, strlen('/index.php')) ?: '/';
                $target = CanonicalUrl::normalize(rtrim(CanonicalUrl::origin(), '/') . $cleanPath);

                if ($request->getQueryString()) {
                    $target .= '?' . $request->getQueryString();
                }

                return redirect()->away($target, Response::HTTP_MOVED_PERMANENTLY);
            }

            $host = strtolower((string) $request->getHost());
            $needsHttps = ! $request->isSecure();
            $needsNonWww = str_starts_with($host, 'www.');

            if ($needsHttps || $needsNonWww) {
                $target = CanonicalUrl::normalize($request->fullUrl());

                return redirect()->away($target, Response::HTTP_MOVED_PERMANENTLY);
            }

            CanonicalUrl::forceApplicationUrl();
        }

        $response = $next($request);

        // Always disable HTML bfcache/shared reuse after login — previously only
        // applied on canonical hosts, so local/other hosts could restore guest nav.
        if (str_contains((string) $response->headers->get('Content-Type', ''), 'text/html')) {
            $response->headers->set('Cache-Control', 'private, no-cache, no-store, must-revalidate');
            $response->headers->set('Pragma', 'no-cache');
            $response->headers->set('Vary', 'Cookie');
        }

        return $response;
    }
}
