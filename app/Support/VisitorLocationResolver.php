<?php

namespace App\Support;

use App\Services\Seo\CityResolutionService;
use Botble\Location\Models\City;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Throwable;

final class VisitorLocationResolver
{
    public const REQUEST_ATTR = 'serik_resolved_location';

    /**
     * Resolve once per request. Precedence:
     * explicit request city → URL/path city → saved cookie/session city → IP → Ontario fallback.
     */
    public static function resolve(?Request $request = null, bool $allowIp = true): ResolvedLocation
    {
        $request ??= request();

        $attr = self::REQUEST_ATTR . ($allowIp ? '' : ':noip');
        if ($request->attributes->has($attr)) {
            return $request->attributes->get($attr);
        }

        $resolved = self::compute($request, $allowIp);
        $request->attributes->set($attr, $resolved);

        return $resolved;
    }

    /**
     * Homepage full-page HTML is shared. Never bake a per-visitor IP location into it.
     */
    public static function forSharedHomepage(?Request $request = null): ResolvedLocation
    {
        return self::resolve($request, false);
    }

    private static function compute(Request $request, bool $allowIp): ResolvedLocation
    {
        if ($coords = self::fromRequestCoordinates($request)) {
            return $coords;
        }

        if ($explicit = self::fromExplicitRequest($request)) {
            return $explicit;
        }

        if ($url = self::fromUrl($request)) {
            return $url;
        }

        if ($saved = self::fromSavedPreference($request)) {
            return $saved;
        }

        if ($allowIp) {
            if ($mock = self::fromMock()) {
                return $mock;
            }

            $ipLocation = self::fromIp($request);
            if ($ipLocation) {
                return $ipLocation;
            }
        }

        return ResolvedLocation::ontarioFallback();
    }

    /**
     * AJAX hydrate may pass the visitor's actual lat/lng (IP or browser).
     * Prefer those coordinates over a city-centroid lookup.
     */
    private static function fromRequestCoordinates(Request $request): ?ResolvedLocation
    {
        $latRaw = $request->input('lat', $request->input('latitude'));
        $lngRaw = $request->input('lng', $request->input('longitude'));
        if (! is_numeric($latRaw) || ! is_numeric($lngRaw)) {
            return null;
        }

        $lat = (float) $latRaw;
        $lng = (float) $lngRaw;
        if ($lat < 41.6 || $lat > 56.9 || $lng < -95.2 || $lng > -74.0) {
            return null;
        }

        $cityName = trim((string) $request->input('city', ''));
        if (in_array(strtolower($cityName), ['auto', 'ontario', 'on'], true)) {
            $cityName = '';
        }

        $city = $cityName !== '' ? self::findCity($cityName) : null;

        return new ResolvedLocation(
            $city?->name ?: ($cityName !== '' ? $cityName : 'Ontario'),
            'Ontario',
            'CA',
            $lat,
            $lng,
            'explicit',
            time(),
            'ip',
        );
    }

    private static function fromExplicitRequest(Request $request): ?ResolvedLocation
    {
        $slug = trim((string) $request->input('city', ''));
        if ($slug === '' || in_array(strtolower($slug), ['auto', 'ontario', 'on'], true)) {
            return null;
        }

        return self::fromCityModel(self::findCity($slug), 'explicit');
    }

    private static function fromUrl(Request $request): ?ResolvedLocation
    {
        try {
            $city = app(CityResolutionService::class)->resolve($request);
        } catch (Throwable) {
            $city = null;
        }

        return self::fromCityModel($city, 'url');
    }

    private static function fromSavedPreference(Request $request): ?ResolvedLocation
    {
        $name = '';
        try {
            if (class_exists(\Theme\homzen\Supports\VisitorCityHelper::class)) {
                $name = (string) (\Theme\homzen\Supports\VisitorCityHelper::get() ?? '');
            }
        } catch (Throwable) {
            $name = '';
        }

        if ($name === '') {
            $name = trim((string) $request->cookie('serik_visitor_city', ''));
        }

        if ($name === '') {
            return null;
        }

        return self::fromCityModel(self::findCity($name), 'session');
    }

    private static function fromMock(): ?ResolvedLocation
    {
        $city = trim((string) config('serik.location.mock_city', ''));
        if ($city === '') {
            return null;
        }

        $fromCity = self::fromCityModel(self::findCity($city), 'ip');
        if ($fromCity) {
            $lat = (float) config('serik.location.mock_lat', $fromCity->latitude);
            $lng = (float) config('serik.location.mock_lng', $fromCity->longitude);

            return new ResolvedLocation(
                $fromCity->city,
                $fromCity->region,
                $fromCity->country,
                $lat ?: $fromCity->latitude,
                $lng ?: $fromCity->longitude,
                'ip',
                time(),
                'ip',
            );
        }

        $lat = (float) config('serik.location.mock_lat', 0);
        $lng = (float) config('serik.location.mock_lng', 0);
        if ($lat === 0.0 && $lng === 0.0) {
            return null;
        }

        return new ResolvedLocation($city, 'Ontario', 'CA', $lat, $lng, 'ip', time(), 'ip');
    }

    private static function fromIp(Request $request): ?ResolvedLocation
    {
        $ip = self::clientIp($request);
        if ($ip === null) {
            return null;
        }

        try {
            $payload = VisitorIpLocation::resolveFromIp($ip);
        } catch (Throwable) {
            $payload = null;
        }

        if (! is_array($payload) || empty($payload['lat']) || empty($payload['lng'])) {
            return null;
        }

        $cityName = trim((string) ($payload['city'] ?? ''));
        $city = $cityName !== '' ? self::findCity($cityName) : null;

        return new ResolvedLocation(
            $city?->name ?: ($cityName !== '' ? $cityName : 'Ontario'),
            'Ontario',
            strtoupper((string) ($payload['country'] ?? 'CA')) ?: 'CA',
            (float) $payload['lat'],
            (float) $payload['lng'],
            'ip',
            time(),
            'ip',
        );
    }

    public static function clientIp(Request $request): ?string
    {
        foreach (['CF-Connecting-IP', 'True-Client-IP', 'X-Real-IP'] as $header) {
            $candidate = self::usableClientIp(trim((string) $request->header($header, '')));
            if ($candidate !== null) {
                return $candidate;
            }
        }

        $forwarded = trim((string) $request->header('X-Forwarded-For', ''));
        if ($forwarded !== '') {
            foreach (explode(',', $forwarded) as $part) {
                $candidate = self::usableClientIp(trim($part));
                if ($candidate !== null) {
                    return $candidate;
                }
            }
        }

        return self::usableClientIp(trim((string) $request->ip()));
    }

    private static function usableClientIp(string $ip): ?string
    {
        if ($ip === '' || in_array($ip, ['127.0.0.1', '::1'], true)) {
            return null;
        }

        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
            return $ip;
        }

        return null;
    }

    private static function findCity(string $nameOrSlug): ?City
    {
        if (! class_exists(City::class)) {
            return null;
        }

        $slug = Str::slug($nameOrSlug);
        $cacheKey = 'serik_loc_city_v1:' . md5(strtolower($slug));

        try {
            return Cache::remember($cacheKey, 3600, function () use ($slug, $nameOrSlug) {
                $bySlug = City::query()
                    ->where('is_active', true)
                    ->where('slug', $slug)
                    ->first(['id', 'name', 'slug', 'latitude', 'longitude', 'property_count', 'is_major']);

                if ($bySlug) {
                    return $bySlug;
                }

                $normalized = Str::title(trim($nameOrSlug));

                return City::query()
                    ->where('is_active', true)
                    ->where(function ($q) use ($normalized): void {
                        $q->where('name', $normalized)
                            ->orWhere('name', 'like', $normalized . '%');
                    })
                    ->orderByDesc('property_count')
                    ->first(['id', 'name', 'slug', 'latitude', 'longitude', 'property_count', 'is_major']);
            });
        } catch (Throwable) {
            return null;
        }
    }

    private static function fromCityModel(?City $city, string $source): ?ResolvedLocation
    {
        if (! $city) {
            return null;
        }

        $lat = (float) ($city->latitude ?? 0);
        $lng = (float) ($city->longitude ?? 0);
        if ($lat === 0.0 && $lng === 0.0) {
            $fallback = ResolvedLocation::ontarioFallback();
            $lat = $fallback->latitude;
            $lng = $fallback->longitude;
        }

        return new ResolvedLocation(
            (string) $city->name,
            'Ontario',
            'CA',
            $lat,
            $lng,
            $source,
            time(),
            'city',
        );
    }
}
