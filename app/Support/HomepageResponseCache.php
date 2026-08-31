<?php

namespace App\Support;

use Illuminate\Http\Request;

/**
 * Versioned full-page HTML cache for GET / (anonymous).
 *
 * Entry format (v6): ['html' => string, 'etag' => string, 'gz' => ?string]
 * Legacy v4/v5 string entries are still readable until TTL expiry.
 */
final class HomepageResponseCache
{
    private const VERSION_KEY = 'homepage_response_cache_version_v4';

    private const KEY_PREFIX = 'homepage_html_v6:';

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
        return (int) SerikCache::get(self::VERSION_KEY, 1);
    }

    public static function ttl(): int
    {
        return max(60, (int) config('serik.cache.homepage_ttl', 7200));
    }

    public static function bump(): void
    {
        $locale = app()->getLocale();
        $oldVersion = self::version();
        $oldKey = self::KEY_PREFIX . $oldVersion . ':' . $locale . ':shared';
        // Also forget legacy v4 keys if present.
        $legacyKey = 'homepage_html_v4:' . $oldVersion . ':' . $locale . ':shared';

        SerikCache::forever(self::VERSION_KEY, $oldVersion + 1);

        SerikCache::forget($oldKey);
        SerikCache::forget($legacyKey);
        SerikCache::forget('homepage_html_v5:' . $oldVersion . ':' . $locale . ':shared');
        SerikCache::forget(self::KEY_PREFIX . self::version() . ':' . $locale . ':shared');
        SerikCache::forget('homepage_html_v4:' . self::version() . ':' . $locale . ':shared');
        SerikCache::forget('homepage_html_v5:' . self::version() . ':' . $locale . ':shared');
    }

    public static function forget(): void
    {
        $locale = app()->getLocale();
        $version = self::version();
        SerikCache::forget(self::KEY_PREFIX . $version . ':' . $locale . ':shared');
        SerikCache::forget('homepage_html_v4:' . $version . ':' . $locale . ':shared');
        SerikCache::forget('homepage_html_v5:' . $version . ':' . $locale . ':shared');
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
        // Marker cookies mean "might be an account session" — never serve shared guest HTML.
        foreach ($request->cookies->keys() as $key) {
            $normalized = strtolower((string) $key);
            if ($normalized === 'serik_acct' || str_starts_with($normalized, 'remember_')) {
                return true;
            }
        }

        if (! $request->hasSession()) {
            return false;
        }

        if ($request->user()) {
            return true;
        }

        return is_plugin_active('real-estate') && auth('account')->check();
    }

    public static function hasOnlyTrackingQuery(Request $request): bool
    {
        return self::hasOnlyTrackingQueryParams($request);
    }

    /**
     * Shared anonymous homepage HTML — no session/auth required.
     */
    public static function getSharedHtml(?Request $request = null): ?string
    {
        $html = self::extractHtml(SerikCache::get(self::sharedKey($request)));

        if ($html === null || $html === '') {
            return null;
        }

        $request ??= request();
        if ($request instanceof Request) {
            $html = self::alignLoopbackOrigins($html, $request);
            if (! self::htmlMatchesRequestOrigin($html, $request)) {
                return null;
            }
        }

        return $html;
    }

    /**
     * Precomputed ETag for shared HTML (avoids sha1 on every HIT).
     */
    public static function getSharedEtag(): ?string
    {
        $entry = SerikCache::get(self::sharedKey());
        if (is_array($entry) && ! empty($entry['etag']) && is_string($entry['etag'])) {
            return $entry['etag'];
        }

        $html = self::extractHtml($entry);
        if ($html === null || $html === '') {
            return null;
        }

        return '"' . sha1($html) . '"';
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
        return self::sharedKey($request);
    }

    private static function sharedKey(?Request $request = null): string
    {
        $locale = app()->getLocale();
        $request ??= request();
        $origin = '';
        if ($request instanceof Request) {
            $origin = strtolower(rtrim($request->getSchemeAndHttpHost() . $request->getBaseUrl(), '/'));
        }

        return self::KEY_PREFIX . self::version() . ':' . $locale . ':' . sha1($origin) . ':shared';
    }

    /**
     * Cached HTML baked on :8000 must not be served on Apache :80 (or localhost vs 127.0.0.1).
     * Absolute CSS hrefs would be cross-origin and CSP style-src 'self' would block them.
     */
    public static function htmlMatchesRequestOrigin(string $html, Request $request): bool
    {
        if (! preg_match('#https?://(?:127\.0\.0\.1|localhost)(?::\d+)?#i', $html, $match)) {
            return true;
        }

        return strtolower($match[0]) === strtolower($request->getSchemeAndHttpHost());
    }

    /**
     * Fragment HTML can be baked on another local port (php -S :8000 vs :8010).
     * Those absolute http://127.0.0.1:PORT URLs are a different origin, so CSP
     * img-src 'self' blocks them. Rewrite loopback origins to this request.
     * Production HTML (https://serik.ca) is unchanged.
     */
    public static function alignLoopbackOrigins(string $html, Request $request): string
    {
        if ($html === '') {
            return $html;
        }

        $target = rtrim($request->getSchemeAndHttpHost(), '/');
        if ($target === '') {
            return $html;
        }

        return preg_replace(
            '#https?://(?:127\.0\.0\.1|localhost|\[::1\])(?::\d+)?#i',
            $target,
            $html
        ) ?? $html;
    }

    public static function get(Request $request): ?string
    {
        if (! self::isCacheableRequest($request)) {
            return null;
        }

        $html = self::extractHtml(SerikCache::get(self::cacheKey($request)));
        if ($html === null || $html === '') {
            return null;
        }

        $html = self::alignLoopbackOrigins($html, $request);
        if (! self::htmlMatchesRequestOrigin($html, $request)) {
            return null;
        }

        return $html;
    }

    public static function getEtag(Request $request): ?string
    {
        if (! self::isCacheableRequest($request)) {
            return null;
        }

        $entry = SerikCache::get(self::cacheKey($request));
        if (is_array($entry) && ! empty($entry['etag']) && is_string($entry['etag'])) {
            return $entry['etag'];
        }

        $html = self::extractHtml($entry);

        return ($html !== null && $html !== '') ? '"' . sha1($html) . '"' : null;
    }

    public static function put(Request $request, string $html): void
    {
        if (! self::isCacheableRequest($request) || $html === '') {
            return;
        }

        $html = self::alignLoopbackOrigins($html, $request);

        SerikCache::put(self::cacheKey($request), self::packEntry($html), self::ttl());
    }

    /**
     * @return array{html: string, etag: string, gz?: string}
     */
    private static function packEntry(string $html): array
    {
        $entry = [
            'html' => $html,
            'etag' => '"' . sha1($html) . '"',
        ];

        // Compress for Memurai/Redis storage (response still serves plaintext; IIS may gzip).
        if (function_exists('gzencode') && strlen($html) > 2048) {
            $gz = @gzencode($html, 6);
            if (is_string($gz) && $gz !== '') {
                $entry['gz'] = $gz;
                // Prefer gz blob in redis to cut memory; keep html for file-store simplicity.
                if (config('cache.default') === 'redis') {
                    unset($entry['html']);
                }
            }
        }

        return $entry;
    }

    private static function extractHtml(mixed $cached): ?string
    {
        if (is_string($cached) && $cached !== '') {
            return $cached;
        }

        if (! is_array($cached)) {
            return null;
        }

        if (! empty($cached['html']) && is_string($cached['html'])) {
            return $cached['html'];
        }

        if (! empty($cached['gz']) && is_string($cached['gz']) && function_exists('gzdecode')) {
            $html = @gzdecode($cached['gz']);

            return is_string($html) && $html !== '' ? $html : null;
        }

        return null;
    }
}
