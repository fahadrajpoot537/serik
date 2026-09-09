<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

/**
 * Full-page guest HTML cache for mostly-static CMS / blog routes.
 * Mirrors OntarioSeoPageCache — early HIT for anonymous visitors.
 */
final class SerikGuestPageCache
{
    public const TTL = 900;

    public const VERSION = 'v1';

    /**
     * Exact path prefixes (no trailing slash) eligible for guest HTML cache.
     *
     * @var list<string>
     */
    public const PATHS = [
        '/blogs',
        '/mortgage-calculator',
        '/cash-back-calculator',
        '/free-home-evaluation',
        '/tips-for-home-selling',
        '/evaluation',
    ];

    public static function matches(Request $request): bool
    {
        if (! $request->isMethod('GET') || $request->ajax() || $request->wantsJson()) {
            return false;
        }

        if ($request->query->count() > 0) {
            // Skip personalized / tracked query variants (utm etc. can be allowed later).
            foreach ($request->query->keys() as $key) {
                $k = strtolower((string) $key);
                if (str_starts_with($k, 'utm_') || $k === 'fbclid' || $k === 'gclid' || $k === 'msclkid') {
                    continue;
                }

                return false;
            }
        }

        $path = rtrim($request->getPathInfo(), '/') ?: '/';

        foreach (self::PATHS as $allowed) {
            if ($path === $allowed) {
                return true;
            }
        }

        // Guest property detail pages (anon HTML; CSRF refresh keeps forms valid).
        if (preg_match('#^/properties/[a-z0-9\-]+$#i', $path)) {
            return true;
        }

        return false;
    }

    public static function key(Request $request): ?string
    {
        if (! self::matches($request)) {
            return null;
        }

        $path = rtrim($request->getPathInfo(), '/') ?: '/';
        $origin = sha1(strtolower(rtrim($request->getSchemeAndHttpHost() . $request->getBaseUrl(), '/')));

        return 'serik_guest_html_' . self::VERSION . ':' . $origin . ':' . md5($path);
    }

    public static function get(?string $key): ?string
    {
        if ($key === null || $key === '') {
            return null;
        }

        $cached = Cache::get($key);
        if (! is_string($cached) || $cached === '') {
            return null;
        }

        $request = request();
        if ($request instanceof Request) {
            $cached = HomepageResponseCache::alignLoopbackOrigins($cached, $request);
        }

        return $cached;
    }

    public static function put(string $key, string $html): void
    {
        if ($html === '' || strlen($html) < 2000) {
            return;
        }

        // Never cache error / login-wall pages.
        if (
            str_contains($html, 'Whoops')
            || str_contains($html, 'Server Error')
            || str_contains($html, 'csrf-token-mismatch')
        ) {
            return;
        }

        Cache::put($key, $html, self::TTL);
    }
}
