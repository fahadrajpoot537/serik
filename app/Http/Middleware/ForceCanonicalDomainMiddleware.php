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
