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
            return $this->maybeCachedPublicHit($request, $next);
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
            ]);
            \App\Support\SerikHtmlCacheHeaders::apply($notModified, $request);

            return \App\Support\SerikSecurityHeaders::apply($notModified, $request);
        }

        $response = response($cached, 200, [
            'Content-Type' => 'text/html; charset=UTF-8',
            'ETag' => $etag,
            'X-Serik-Homepage-Cache' => 'HIT-EARLY',
        ]);
        \App\Support\SerikHtmlCacheHeaders::apply($response, $request);

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

    private function maybeCachedPublicHit(Request $request, Closure $next): Response
    {
        $ontario = $this->maybeOntarioSeoHit($request);
        if ($ontario !== null) {
            return $ontario;
        }

        $guest = $this->maybeGuestPageHit($request);
        if ($guest !== null) {
            return $guest;
        }

        return $next($request);
    }

    private function maybeGuestPageHit(Request $request): ?Response
    {
        if ($this->shouldBypassEarlyCache($request)) {
            return null;
        }

        $key = \App\Support\SerikGuestPageCache::key($request);
        $cached = \App\Support\SerikGuestPageCache::get($key);
        if ($cached === null) {
            return null;
        }

        $response = response($cached, 200, [
            'Content-Type' => 'text/html; charset=UTF-8',
            'Content-Length' => (string) strlen($cached),
            'X-Serik-Guest-Cache' => 'HIT-EARLY',
            'Connection' => 'close',
        ]);
        \App\Support\SerikHtmlCacheHeaders::apply($response, $request);

        return \App\Support\SerikSecurityHeaders::apply($response, $request);
    }

    private function maybeOntarioSeoHit(Request $request): ?Response
    {
        if (! preg_match('#^/ontario/([a-z0-9\-]+)$#i', $request->getPathInfo(), $matches)) {
            return null;
        }

        if ($request->ajax() || $request->wantsJson()) {
            return null;
        }

        if ($this->shouldBypassEarlyCache($request)) {
            return null;
        }

        $key = \App\Support\OntarioSeoPageCache::key($request, $matches[1], ':anon');
        $cached = \App\Support\OntarioSeoPageCache::get($key);
        if ($cached === null) {
            return null;
        }

        $response = response($cached, 200, [
            'Content-Type' => 'text/html; charset=UTF-8',
            'Content-Length' => (string) strlen($cached),
            'X-Serik-Ontario-Cache' => 'HIT-EARLY',
            'Connection' => 'close',
        ]);
        \App\Support\SerikHtmlCacheHeaders::apply($response, $request);

        return \App\Support\SerikSecurityHeaders::apply($response, $request);
    }
}
