<?php

namespace App\Http\Middleware;

use App\Support\VisitorCountry;
use Closure;
use Illuminate\Http\Request;
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

        $ip = VisitorCountry::clientIp($request);

        if (VisitorCountry::isLocalOrPrivateIp($ip) || $this->isWhitelistedIp($ip)) {
            return $next($request);
        }

        $country = VisitorCountry::resolve($request);
        $allowed = array_map('strtoupper', (array) config('serik.geo_block.allowed_countries', ['US', 'CA', 'PK']));

        // Unknown country after lookup: fail closed (do not invent CA).
        if ($country === null || $country === '' || ! in_array($country, $allowed, true)) {
            return $this->deny($request, $country ?: 'UNKNOWN');
        }

        return $next($request);
    }

    protected function deny(Request $request, string $country): Response
    {
        if ($request->expectsJson() || $request->is('api/*') || $request->ajax()) {
            return response()->json([
                'message' => 'This website is currently available only in Canada, the United States, and Pakistan.',
                'country' => $country,
            ], 403);
        }

        return response()
            ->view('serik.geo-restricted', [
                'country' => $country,
            ], 403)
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate');
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
            'webhooks/gohighlevel',
            'storage/properties/treb',
            'ajax/render-ui-blocks',
            'ajax/render-ui-blocks-batch',
            'api/v1/map-properties',
            'api/v1/map-thumbnails',
            'api/v1/map-property-bundle',
            'api/v1/related-properties',
            'api/v1/smart-search',
            'api/v1/propertiesName',
            'api/v1/home-evaluation',
            'api/v1/community-suggestions',
            'api/v1/community-index',
            'api/v1/geocode-community',
            'api/v1/visitor-location',
            'api/v1/property-image',
            'api/v1/getPropertyImages',
            'api/v1/getPropertyDetails',
            'api/v1/getPropertyBasicDetails',
            'api/v1/listing-history',
            'api/v1/price-changes',
            'api/v1/property-rooms',
            'api/v1/auth/session-status',
            'api/v1/listings-count',
            'clear-serik-cache.php',
        ]);

        foreach ($bypassPrefixes as $prefix) {
            if ($path === $prefix || str_starts_with($path, $prefix . '/')) {
                return true;
            }
        }

        // Public asset files under /storage (logos, uploads) — not property API.
        if (str_starts_with($path, 'storage/') && ! str_starts_with($path, 'storage/properties/treb')) {
            return true;
        }

        return false;
    }

    protected function isSitemapOrSeoPath(string $path): bool
    {
        return (bool) preg_match(
            '#^(?:sitemap\\.xml|agents\\.xml|pages\\.xml|featured-properties\\.xml|robots\\.txt|blog-posts-(?:\\d{4}-\\d{2}|.*)\\.xml)$#i',
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
}
