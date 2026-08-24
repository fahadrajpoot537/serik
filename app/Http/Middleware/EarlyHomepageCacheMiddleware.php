<?php

namespace App\Http\Middleware;

use App\Support\HomepageResponseCache;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Serve anonymous homepage HTML before the heavy web middleware stack.
 *
 * Must NOT serve shared guest HTML to anyone who might be logged in.
 * Session-based account login does not set remember_* cookies, so any
 * present session cookie bypasses this layer; CacheHomepageResponseMiddleware
 * still returns a fast HIT for true guests after StartSession.
 */
class EarlyHomepageCacheMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->method() !== 'GET') {
            return $next($request);
        }

        if ($request->getPathInfo() !== '/') {
            return $this->maybeOntarioSeoHit($request, $next);
        }

        if ($request->ajax() || $request->wantsJson()) {
            return $next($request);
        }

        if ($this->shouldBypassEarlyCache($request)) {
            return $next($request);
        }

        if (! HomepageResponseCache::hasOnlyTrackingQuery($request)) {
            return $next($request);
        }

        $cached = HomepageResponseCache::getSharedHtml($request);
        if ($cached === null || $cached === '') {
            return $next($request);
        }

        // Hash the HTML we actually serve (loopback origins may have been aligned).
        $etag = '"' . sha1($cached) . '"';
        if (trim((string) $request->headers->get('If-None-Match')) === $etag) {
            $notModified = response('', 304, [
                'ETag' => $etag,
                'X-Serik-Homepage-Cache' => 'HIT-EARLY-304',
                // no-store: block bfcache restoring guest nav after login.
                'Cache-Control' => 'private, no-cache, no-store, must-revalidate',
                'Vary' => 'Cookie',
            ]);

            return \App\Support\SerikSecurityHeaders::apply($notModified, $request);
        }

        $response = response($cached, 200, [
            'Content-Type' => 'text/html; charset=UTF-8',
            'ETag' => $etag,
            'X-Serik-Homepage-Cache' => 'HIT-EARLY',
            // private + no-store + Vary: Cookie — never reuse guest HTML after login.
            'Cache-Control' => 'private, no-cache, no-store, must-revalidate',
            'Vary' => 'Cookie',
        ]);

        return \App\Support\SerikSecurityHeaders::apply($response, $request);
    }

    private function shouldBypassEarlyCache(Request $request): bool
    {
        foreach ($request->cookies->keys() as $key) {
            $normalized = strtolower((string) $key);
            if (str_starts_with($normalized, 'remember_')) {
                return true;
            }
            // Explicit account marker set on login (see Login/Logout listeners).
            if ($normalized === 'serik_acct') {
                return true;
            }
        }

        $sessionCookie = (string) config('session.cookie');
        if ($sessionCookie !== '' && $request->cookies->has($sessionCookie)) {
            return true;
        }

        return false;
    }

    private function maybeOntarioSeoHit(Request $request, Closure $next): Response
    {
        if (! preg_match('#^/ontario/([a-z0-9\-]+)$#i', $request->getPathInfo(), $matches)) {
            return $next($request);
        }

        if ($request->ajax() || $request->wantsJson()) {
            return $next($request);
        }

        if ($this->shouldBypassEarlyCache($request)) {
            return $next($request);
        }

        $key = \App\Support\OntarioSeoPageCache::key($request, $matches[1], ':anon');
        $cached = \App\Support\OntarioSeoPageCache::get($key);
        if ($cached === null) {
            return $next($request);
        }

        $response = response($cached, 200, [
            'Content-Type' => 'text/html; charset=UTF-8',
            'X-Serik-Ontario-Cache' => 'HIT-EARLY',
            'Cache-Control' => 'private, no-cache, no-store, must-revalidate',
            'Vary' => 'Cookie',
        ]);

        return \App\Support\SerikSecurityHeaders::apply($response, $request);
    }
}
