<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

final class VisitorIpLocation
{
    /**
     * @return array{lat: float, lng: float, city: string, source: string, accuracy: string}|null
     */
    public static function resolveFromIp(string $ip): ?array
    {
        $ip = trim($ip);

        if ($ip === '' || in_array($ip, ['127.0.0.1', '::1'], true)) {
            return null;
        }

        return Cache::remember(self::cacheKey($ip), 1800, function () use ($ip) {
            $inOntario = static function (float $lat, float $lng, ?string $region, string $country): bool {
                if (strtoupper($country) !== 'CA') {
                    return false;
                }

                if ($region !== null && stripos($region, 'ontario') !== false) {
                    return true;
                }

                return $lat >= 41.6 && $lat <= 56.9 && $lng >= -95.2 && $lng <= -74.0;
            };

            try {
                $response = Http::timeout(2)->get('http://ip-api.com/json/' . $ip, [
                    'fields' => 'status,lat,lon,city,regionName,countryCode',
                ]);

                if ($response->successful() && $response->json('status') === 'success') {
                    $lat = (float) $response->json('lat');
                    $lng = (float) $response->json('lon');
                    $region = (string) $response->json('regionName');
                    $country = (string) $response->json('countryCode');

                    if ($inOntario($lat, $lng, $region, $country)) {
                        return [
                            'lat' => round($lat, 6),
                            'lng' => round($lng, 6),
                            'city' => trim((string) $response->json('city')),
                            'country' => strtoupper($country ?: 'CA'),
                            'source' => 'ip',
                            'accuracy' => 'ip',
                        ];
                    }
                }
            } catch (\Throwable) {
                // try next provider
            }

            try {
                $response = Http::timeout(2)->get('https://ipapi.co/' . $ip . '/json/');

                if ($response->successful()) {
                    $lat = (float) $response->json('latitude');
                    $lng = (float) $response->json('longitude');
                    $region = (string) $response->json('region');
                    $country = (string) $response->json('country_code');

                    if ($inOntario($lat, $lng, $region, $country)) {
                        return [
                            'lat' => round($lat, 6),
                            'lng' => round($lng, 6),
                            'city' => trim((string) $response->json('city')),
                            'country' => strtoupper($country ?: 'CA'),
                            'source' => 'ip',
                            'accuracy' => 'ip',
                        ];
                    }
                }
            } catch (\Throwable) {
                // fall through
            }

            return null;
        });
    }

    private static function cacheKey(string $ip): string
    {
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            $parts = explode('.', $ip);
            $network = ($parts[0] ?? '0') . '.' . ($parts[1] ?? '0') . '.' . ($parts[2] ?? '0') . '.0';

            return 'serik_visitor_ip_loc_v2_' . md5($network);
        }

        return 'serik_visitor_ip_loc_v2_' . md5(substr($ip, 0, 19));
    }

    public static function defaultPayload(): array
    {
        return [
            'lat' => 43.6532,
            'lng' => -79.3832,
            'city' => 'Toronto',
            'source' => 'default',
            'accuracy' => 'city',
        ];
    }
}
