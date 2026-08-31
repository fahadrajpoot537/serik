<?php

namespace App\Support;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * HTML cache headers for public pages.
 *
 * Authenticated / remember-me responses keep no-store so a guest snapshot
 * cannot be restored after login. Anonymous HTML omits no-store so the
 * browser back/forward cache can restore the page. Shared caches stay
 * blocked via private + Vary: Cookie. Existing pageshow auth-nav sync
 * still reloads if a restored guest page disagrees with the session.
 */
final class SerikHtmlCacheHeaders
{
    public static function isLikelyAuthenticated(Request $request): bool
    {
        foreach ($request->cookies->keys() as $key) {
            $normalized = strtolower((string) $key);
            if ($normalized === 'serik_acct' || str_starts_with($normalized, 'remember_')) {
                return true;
            }
        }

        return false;
    }

    public static function value(Request $request): string
    {
        if (self::isLikelyAuthenticated($request)) {
            return 'private, no-cache, no-store, must-revalidate';
        }

        return 'private, no-cache, must-revalidate';
    }

    public static function apply(Response $response, Request $request): void
    {
        $response->headers->set('Cache-Control', self::value($request));
        $response->headers->set('Pragma', 'no-cache');
        $response->headers->set('Vary', 'Cookie');
    }
}
