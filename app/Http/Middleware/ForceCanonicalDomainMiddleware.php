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
        if (! CanonicalUrl::shouldNormalize($request)) {
            return $next($request);
        }

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

        return $next($request);
    }
}
