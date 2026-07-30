<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

/**
 * Versioned full-page HTML cache for GET / (anonymous, no query string).
 */
final class HomepageResponseCache
{
    private const VERSION_KEY = 'homepage_response_cache_version_v4';

    private const TTL_SECONDS = 7200;

    public static function version(): int
    {
        return (int) Cache::get(self::VERSION_KEY, 1);
    }

    public static function bump(): void
    {
        $locale = app()->getLocale();
        $oldVersion = self::version();
        $oldKey = 'homepage_html_v4:' . $oldVersion . ':' . $locale . ':shared';
        $oldHtml = Cache::get($oldKey);

        Cache::forever(self::VERSION_KEY, $oldVersion + 1);

        // Keep serving the previous HTML under the new version key so a bump
        // never forces a multi-second cold MISS (target ≤0.8s TTFB).
        if (is_string($oldHtml) && $oldHtml !== '') {
            Cache::put(
                'homepage_html_v4:' . self::version() . ':' . $locale . ':shared',
                $oldHtml,
                self::TTL_SECONDS
            );
        }
    }

    /**
     * Soft bump used by MLS property writes — does NOT invalidate full-page HTML.
     * Featured/fragment caches are bumped separately by the observer.
     */
    public static function bumpDataOnly(): void
    {
        HomepageFeaturedCache::bump();
    }

    public static function isCacheableRequest(Request $request): bool
    {
        if ($request->method() !== 'GET') {
            return false;
        }

        if (! SerikHomepage::isHomepageRequest()) {
            return false;
        }

        if ($request->query->count() > 0) {
            return false;
        }

        if ($request->ajax() || $request->wantsJson()) {
            return false;
        }

        if (self::isAuthenticatedRequest($request)) {
            return false;
        }

        return true;
    }

    private static function isAuthenticatedRequest(Request $request): bool
    {
        if (! $request->hasSession()) {
            return false;
        }

        if ($request->user()) {
            return true;
        }

        return is_plugin_active('real-estate') && auth('account')->check();
    }

    public static function cacheKey(Request $request): string
    {
        // Shared key for all anonymous visitors — visitor-city personalization
        // loads via async SEO nav / featured fragments, not per-city full HTML.
        $locale = app()->getLocale();

        return 'homepage_html_v4:' . self::version() . ':' . $locale . ':shared';
    }

    public static function get(Request $request): ?string
    {
        if (! self::isCacheableRequest($request)) {
            return null;
        }

        $cached = Cache::get(self::cacheKey($request));

        return is_string($cached) && $cached !== '' ? $cached : null;
    }

    public static function put(Request $request, string $html): void
    {
        if (! self::isCacheableRequest($request) || $html === '') {
            return;
        }

        Cache::put(self::cacheKey($request), $html, self::TTL_SECONDS);
    }
}
