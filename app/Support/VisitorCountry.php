<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * Resolve visitor country for geo-blocking (CA / US / PK).
 */
final class VisitorCountry
{
    /**
     * @return string|null ISO 3166-1 alpha-2 country code, or null if unknown
     */
    public static function resolve(Request $request): ?string
    {
        $headerCountry = $request->header('CF-IPCountry')
            ?: ($request->server('HTTP_CF_IPCOUNTRY') ?: null);

        if (is_string($headerCountry) && $headerCountry !== '') {
            $code = strtoupper(trim($headerCountry));
            if (! in_array($code, ['XX', 'T1', ''], true)) {
                return $code;
            }
        }

        $ip = self::clientIp($request);
        if ($ip === null || self::isLocalOrPrivateIp($ip)) {
            return null;
        }

        $cacheKey = 'geoip_country_v2_' . md5($ip);
        $cached = Cache::get($cacheKey);
        if (is_string($cached) && $cached !== '') {
            return $cached;
        }
        if ($cached === '') {
            return null;
        }

        $lock = Cache::lock('geoip_country_lock_' . md5($ip), 12);
        try {
            $lock->block(3);
            $cached = Cache::get($cacheKey);
            if (is_string($cached) && $cached !== '') {
                return $cached;
            }
            if ($cached === '') {
                return null;
            }

            $value = self::lookupIp($ip);
            Cache::put($cacheKey, $value ?? '', 86400);

            return $value;
        } catch (\Throwable) {
            $cached = Cache::get($cacheKey);

            return is_string($cached) && $cached !== '' ? $cached : null;
        } finally {
            optional($lock)->release();
        }
    }

    public static function clientIp(Request $request): ?string
    {
        foreach (['CF-Connecting-IP', 'True-Client-IP', 'X-Real-IP'] as $header) {
            $candidate = trim((string) $request->header($header, ''));
            if (filter_var($candidate, FILTER_VALIDATE_IP)) {
                return $candidate;
            }
        }

        $forwarded = trim((string) $request->header('X-Forwarded-For', ''));
        if ($forwarded !== '') {
            $first = trim(explode(',', $forwarded)[0]);
            if (filter_var($first, FILTER_VALIDATE_IP)) {
                return $first;
            }
        }

        $ip = $request->ip();

        return is_string($ip) && $ip !== '' ? $ip : null;
    }

    public static function isLocalOrPrivateIp(?string $ip): bool
    {
        if (empty($ip) || $ip === '127.0.0.1' || $ip === '::1') {
            return true;
        }

        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
            return true;
        }

        return false;
    }

    /**
     * @return string|null
     */
    private static function lookupIp(string $ip): ?string
    {
        try {
            $response = Http::timeout(2.5)->get('http://ip-api.com/json/' . $ip, [
                'fields' => 'status,countryCode',
            ]);
            if ($response->successful() && $response->json('status') === 'success') {
                $code = strtoupper((string) $response->json('countryCode'));
                if ($code !== '') {
                    return $code;
                }
            }
        } catch (\Throwable) {
            // continue
        }

        try {
            $response = Http::timeout(2.5)->get('https://ipapi.co/' . $ip . '/json/');
            if ($response->successful()) {
                $code = strtoupper((string) $response->json('country_code'));
                if ($code !== '' && $code !== 'UNDEFINED') {
                    return $code;
                }
            }
        } catch (\Throwable) {
            // continue
        }

        try {
            $response = Http::timeout(2.5)->get('https://ipinfo.io/' . $ip . '/json');
            if ($response->successful()) {
                $code = strtoupper((string) $response->json('country'));
                if ($code !== '') {
                    return $code;
                }
            }
        } catch (\Throwable) {
            // continue
        }

        // Unknown — do NOT invent "CA" (that previously allowed the whole world).
        return null;
    }
}
