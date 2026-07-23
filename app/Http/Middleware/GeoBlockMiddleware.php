<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpFoundation\Response;

class GeoBlockMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        // Use config() (not env()) so config:cache on production works correctly.
        if (! (bool) config('serik.geo_block.enabled', false)) {
            return $next($request);
        }

        if ($this->shouldBypass($request)) {
            return $next($request);
        }

        $ip = $request->ip();

        if ($this->isLocalOrPrivateIp($ip) || $this->isWhitelistedIp($ip)) {
            return $next($request);
        }

        $country = $request->header('CF-IPCountry') ?: ($request->server('HTTP_CF_IPCOUNTRY') ?: null);

        if (! $country) {
            $country = Cache::remember('geoip_country_' . $ip, 86400, function () use ($ip) {
                try {
                    $response = Http::timeout(1)->get('http://ip-api.com/json/' . $ip);
                    if ($response->successful() && $response->json('status') === 'success') {
                        return strtoupper((string) $response->json('countryCode'));
                    }
                } catch (\Throwable) {
                    // continue
                }

                try {
                    $response = Http::timeout(1)->get('https://ipapi.co/' . $ip . '/json/');
                    if ($response->successful()) {
                        return strtoupper((string) $response->json('country_code'));
                    }
                } catch (\Throwable) {
                    // continue
                }

                // Fail-open so API outages do not lock the whole site.
                return 'CA';
            });
        }

        $country = strtoupper((string) $country);

        if (in_array($country, ['XX', 'T1', ''], true)) {
            return $next($request);
        }

        $allowed = config('serik.geo_block.allowed_countries', ['US', 'CA']);
        $allowed = array_map('strtoupper', (array) $allowed);

        if (! in_array($country, $allowed, true)) {
            abort(403, 'Access denied: Website is only accessible from allowed countries (' . implode(', ', $allowed) . '). Your country: ' . $country);
        }

        return $next($request);
    }

    protected function shouldBypass(Request $request): bool
    {
        $path = ltrim($request->path(), '/');
        $adminDir = trim((string) config('core.base.general.admin_dir', 'admin'), '/');

        if ($this->isSitemapOrSeoPath($path)) {
            return true;
        }

        if ($this->isSearchEngineCrawler($request)) {
            return true;
        }

        $bypassPrefixes = array_filter([
            $adminDir,
            'iftheynopaysmywages',
            'paidmywagesthanks',
            'up',
            'storage/properties/treb',
            'ajax/render-ui-blocks',
            'ajax/render-ui-blocks-batch',
            'api/v1/map-properties',
            'api/v1/map-thumbnails',
            'api/v1/map-property-bundle',
            'api/v1/smart-search',
            'api/v1/property-image',
            'api/v1/getPropertyImages',
            'api/v1/getPropertyDetails',
            'api/v1/getPropertyBasicDetails',
            'api/v1/listing-history',
            'api/v1/price-changes',
            'api/v1/property-rooms',
            'api/v1/auth/session-status',
            'api/v1/listings-count',
        ]);

        foreach ($bypassPrefixes as $prefix) {
            if ($path === $prefix || str_starts_with($path, $prefix . '/')) {
                return true;
            }
        }

        return false;
    }

    protected function isSitemapOrSeoPath(string $path): bool
    {
        return (bool) preg_match(
            '#^(?:sitemap\.xml|agents\.xml|pages\.xml|robots\.txt|properties-\d{4}-\d{2}\.xml|blog-posts-(?:\d{4}-\d{2}|.*)\.xml)$#i',
            $path
        );
    }

    protected function isSearchEngineCrawler(Request $request): bool
    {
        $userAgent = strtolower((string) $request->userAgent());

        if ($userAgent === '') {
            return false;
        }

        $crawlers = [
            'googlebot',
            'bingbot',
            'slurp',
            'duckduckbot',
            'baiduspider',
            'yandexbot',
            'facebot',
            'sitechecker',
            'semrushbot',
            'ahrefsbot',
            'petalbot',
            'applebot',
            'dotbot',
        ];

        foreach ($crawlers as $crawler) {
            if (str_contains($userAgent, $crawler)) {
                return true;
            }
        }

        return false;
    }

    protected function isWhitelistedIp(?string $ip): bool
    {
        if (empty($ip)) {
            return false;
        }

        $whitelist = config('serik.geo_block.bypass_ips', []);

        return in_array($ip, (array) $whitelist, true);
    }

    protected function isLocalOrPrivateIp(?string $ip): bool
    {
        if (empty($ip) || $ip === '127.0.0.1' || $ip === '::1') {
            return true;
        }

        $ipParts = explode('.', $ip);
        if (count($ipParts) === 4) {
            $first = (int) $ipParts[0];
            $second = (int) $ipParts[1];

            if ($first === 10) {
                return true;
            }
            if ($first === 172 && $second >= 16 && $second <= 31) {
                return true;
            }
            if ($first === 192 && $second === 168) {
                return true;
            }
        }

        return false;
    }
}
