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
            $host = (string) ($request->server('HTTP_HOST') ?: $request->header('Host'));

            if ($host !== '') {
                $scheme = $request->isSecure() ? 'https' : 'http';
                URL::forceRootUrl($scheme . '://' . $host);
            }
        }

        return $next($request);
    }
}
