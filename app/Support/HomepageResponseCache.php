<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

/**
 * Versioned full-page HTML cache for GET / (anonymous).
 */
final class HomepageResponseCache
{
    private const VERSION_KEY = 'homepage_response_cache_version_v4';

    private const TTL_SECONDS = 7200;
    private const TRACKING_QUERY_KEYS = [
        'utm_source',
        'utm_medium',
        'utm_campaign',
        'utm_term',
        'utm_content',
        'gclid',
        'fbclid',
        'msclkid',
        'ttclid',
    ];

    public static function version(): int
    {
        return (int) Cache::get(self::VERSION_KEY, 1);
    }

    public static function bump(): void
    {
        $locale = app()->getLocale();
        $oldVersion = self::version();
        $oldKey = 'homepage_html_v4:' . $oldVersion . ':' . $locale . ':shared';

        Cache::forever(self::VERSION_KEY, $oldVersion + 1);

        // Drop both keys so the next request regenerates fresh HTML.
        // Copying stale HTML was serving broken property hrefs (homepage "/").
        Cache::forget($oldKey);
        Cache::forget('homepage_html_v4:' . self::version() . ':' . $locale . ':shared');
    }

    public static function forget(): void
    {
        $locale = app()->getLocale();
        $version = self::version();
        Cache::forget('homepage_html_v4:' . $version . ':' . $locale . ':shared');
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

        // Safety guard: never allow full-page homepage HTML cache on non-root URLs.
        // This prevents accidental homepage responses on property/detail routes.
        if ($request->getPathInfo() !== '/') {
            return false;
        }

        if (! SerikHomepage::isHomepageRequest()) {
            return false;
        }

        if (! self::hasOnlyTrackingQueryParams($request)) {
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

    private static function hasOnlyTrackingQueryParams(Request $request): bool
    {
        $query = $request->query();
        if ($query === []) {
            return true;
        }

        foreach (array_keys($query) as $key) {
            if (! in_array(strtolower((string) $key), self::TRACKING_QUERY_KEYS, true)) {
                return false;
            }
        }

        return true;
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
