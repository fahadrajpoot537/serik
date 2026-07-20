<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpFoundation\Response;

class GeoBlockMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! filter_var(env('GEO_BLOCK_ENABLED', true), FILTER_VALIDATE_BOOLEAN)) {
            return $next($request);
        }

        if ($this->shouldBypass($request)) {
            return $next($request);
        }

        $ip = $request->ip();

        if ($this->isLocalOrPrivateIp($ip) || $this->isWhitelistedIp($ip)) {
            return $next($request);
        }

        // 1. Check Cloudflare Header (fastest & most reliable in production)
        $country = $request->header('CF-IPCountry') ?: ($request->server('HTTP_CF_IPCOUNTRY') ?: null);

        if (! $country) {
            // 2. Fetch from GeoIP APIs with caching to prevent overhead
            $country = Cache::remember("geoip_country_{$ip}", 86400, function () use ($ip) {
                try {
                    $response = Http::timeout(3)->get("http://ip-api.com/json/{$ip}");
                    if ($response->successful() && $response->json('status') === 'success') {
                        return strtoupper($response->json('countryCode'));
                    }
                } catch (\Throwable) {
                    // Fall back to next API or default
                }

                try {
                    $response = Http::timeout(3)->get("https://ipapi.co/{$ip}/json/");
                    if ($response->successful()) {
                        return strtoupper($response->json('country_code'));
                    }
                } catch (\Throwable) {
                    // Fall back to next API or default
                }

                // Default to CA so we don't accidentally block legitimate users on API failure
                return 'CA';
            });
        }

        $country = strtoupper((string) $country);

        // Cloudflare unknown / no country — allow rather than block
        if (in_array($country, ['XX', 'T1', ''], true)) {
            return $next($request);
        }

        $allowedCountries = array_map(
            'trim',
            explode(',', (string) env('GEO_BLOCK_ALLOWED_COUNTRIES', 'US,CA'))
        );

        if (! in_array($country, $allowedCountries, true)) {
            abort(403, 'Access denied: Website is only accessible from the United States and Canada.');
        }

        return $next($request);
    }

    protected function shouldBypass(Request $request): bool
    {
        $path = ltrim($request->path(), '/');
        $adminDir = trim((string) config('core.base.general.admin_dir', 'admin'), '/');

        $bypassPrefixes = array_filter([
            $adminDir,
            'iftheynopaysmywages',
            'paidmywagesthanks',
            'up',
            // Homepage lazy shortcodes — must not be geo/API blocked.
            'ajax/render-ui-blocks',
            // Public map/search APIs — must work for CA/US users even when
            // CF-IPCountry is missing on origin hits; HTML pages stay geo-gated.
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

    protected function isWhitelistedIp(?string $ip): bool
    {
        if (empty($ip)) {
            return false;
        }

        $whitelist = array_filter(array_map('trim', explode(',', (string) env('GEO_BLOCK_BYPASS_IPS', ''))));

        return in_array($ip, $whitelist, true);
    }

    /**
     * Check if the IP is local or private.
     */
    protected function isLocalOrPrivateIp(?string $ip): bool
    {
        if (empty($ip) || $ip === '127.0.0.1' || $ip === '::1') {
            return true;
        }

        // Check RFC1918 private ranges
        $ipParts = explode('.', $ip);
        if (count($ipParts) === 4) {
            $first = (int) $ipParts[0];
            $second = (int) $ipParts[1];

            // 10.0.0.0/8
            if ($first === 10) {
                return true;
            }
            // 172.16.0.0/12
            if ($first === 172 && $second >= 16 && $second <= 31) {
                return true;
            }
            // 192.168.0.0/16
            if ($first === 192 && $second === 168) {
                return true;
            }
        }

        return false;
    }
}
