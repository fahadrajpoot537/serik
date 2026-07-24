<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

/**
 * Versioned full-page HTML cache for GET / (anonymous, no query string).
 */
final class HomepageResponseCache
{
    private const VERSION_KEY = 'homepage_response_cache_version_v2';

    private const TTL_SECONDS = 300;

    public static function version(): int
    {
        return (int) Cache::get(self::VERSION_KEY, 1);
    }

    public static function bump(): void
    {
        Cache::forever(self::VERSION_KEY, self::version() + 1);
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

        if ($request->hasSession() && $request->user()) {
            return false;
        }

        return true;
    }

    public static function cacheKey(Request $request): string
    {
        $city = 'ontario';

        try {
            if (class_exists(\Theme\homzen\Supports\VisitorCityHelper::class)) {
                $detected = \Theme\homzen\Supports\VisitorCityHelper::get();
                if (is_string($detected) && $detected !== '') {
                    $city = strtolower($detected);
                }
            }
        } catch (\Throwable) {
            // ignore
        }

        $locale = app()->getLocale();

        return 'homepage_html_v2:' . self::version() . ':' . $locale . ':' . $city;
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
