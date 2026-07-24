<?php

namespace App\Http\Middleware;

use App\Support\HomepageResponseCache;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Serve cached homepage HTML when available (measured ~10s render → ~5ms cache hit).
 */
class CacheHomepageResponseMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $cached = HomepageResponseCache::get($request);

        if ($cached !== null) {
            return response($cached, 200, [
                'Content-Type' => 'text/html; charset=UTF-8',
                'X-Serik-Homepage-Cache' => 'HIT',
            ]);
        }

        $response = $next($request);

        if (
            $response->getStatusCode() === 200
            && str_contains((string) $response->headers->get('Content-Type'), 'text/html')
        ) {
            HomepageResponseCache::put($request, (string) $response->getContent());
            $response->headers->set('X-Serik-Homepage-Cache', 'MISS');
        }

        return $response;
    }
}
