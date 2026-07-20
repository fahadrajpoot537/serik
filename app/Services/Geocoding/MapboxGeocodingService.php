<?php

namespace App\Services\Geocoding;

use App\Contracts\GeocodingProviderInterface;
use Botble\RealEstate\Models\Property;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use RuntimeException;

class MapboxGeocodingService implements GeocodingProviderInterface
{
    use BuildsCanadianAddress;

    public function name(): string
    {
        return 'mapbox';
    }

    public function isConfigured(): bool
    {
        return trim((string) config('geocoding.providers.mapbox.token', '')) !== '';
    }

    public function geocode(Property $property): ?array
    {
        $address = $this->buildAddress($property);
        if ($address === '') {
            return null;
        }

        if (! $this->isConfigured()) {
            Log::channel('geocoding')->error('Mapbox geocode denied — missing token', [
                'provider' => $this->name(),
                'address' => $address,
            ]);

            return null;
        }

        $this->throttle();

        $base = rtrim((string) config(
            'geocoding.providers.mapbox.url',
            'https://api.mapbox.com/geocoding/v5/mapbox.places'
        ), '/');

        $url = $base . '/' . rawurlencode($address) . '.json';

        try {
            $response = Http::timeout(20)
                ->acceptJson()
                ->get($url, [
                    'access_token' => config('geocoding.providers.mapbox.token'),
                    'country' => 'ca',
                    'limit' => 5,
                    'types' => 'address,place,locality,neighborhood,postcode',
                ]);
        } catch (\Throwable $e) {
            Log::channel('geocoding')->warning('Mapbox HTTP failure', [
                'provider' => $this->name(),
                'address' => $address,
                'error' => $e->getMessage(),
            ]);

            throw new RuntimeException('Mapbox Geocoding HTTP error: ' . $e->getMessage(), 0, $e);
        }

        if ($response->status() === 429) {
            throw new RuntimeException('OVER_QUERY_LIMIT');
        }

        if (! $response->successful()) {
            Log::channel('geocoding')->error('Mapbox geocode HTTP error', [
                'provider' => $this->name(),
                'address' => $address,
                'status' => $response->status(),
                'body' => mb_substr((string) $response->body(), 0, 500),
            ]);

            return null;
        }

        $body = $response->json() ?: [];
        $features = $body['features'] ?? [];

        if (! is_array($features) || $features === []) {
            Log::channel('geocoding')->info('Mapbox ZERO_RESULTS', [
                'provider' => $this->name(),
                'address' => $address,
                'status' => 'ZERO_RESULTS',
            ]);

            return null;
        }

        $best = $this->pickBestFeature($features);
        $center = $best['center'] ?? null;
        if (! is_array($center) || count($center) < 2) {
            return null;
        }

        // Mapbox: center[0] = longitude, center[1] = latitude
        $lng = (float) $center[0];
        $lat = (float) $center[1];
        if ($lat === 0.0 && $lng === 0.0) {
            return null;
        }

        $placeType = is_array($best['place_type'] ?? null)
            ? implode(',', $best['place_type'])
            : (string) ($best['place_type'] ?? '');

        $relevance = isset($best['relevance']) ? (float) $best['relevance'] : null;

        if ($relevance !== null && $relevance < 0.7) {
            Log::channel('geocoding')->info('Mapbox low-relevance result', [
                'provider' => $this->name(),
                'address' => $address,
                'relevance' => $relevance,
                'place_name' => $best['place_name'] ?? null,
            ]);
        }

        return [
            'provider' => $this->name(),
            'status' => 'OK',
            'lat' => $lat,
            'lng' => $lng,
            'formatted_address' => (string) ($best['place_name'] ?? ''),
            'location_type' => $placeType,
            'relevance' => $relevance,
            'searched_address' => $address,
            'address_components' => $best['context'] ?? [],
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $features
     * @return array<string, mixed>
     */
    private function pickBestFeature(array $features): array
    {
        usort($features, static function ($a, $b) {
            $ra = (float) ($a['relevance'] ?? 0);
            $rb = (float) ($b['relevance'] ?? 0);
            if ($ra !== $rb) {
                return $rb <=> $ra;
            }

            $ta = is_array($a['place_type'] ?? null) ? ($a['place_type'][0] ?? '') : '';
            $tb = is_array($b['place_type'] ?? null) ? ($b['place_type'][0] ?? '') : '';
            $priority = ['address' => 0, 'neighborhood' => 1, 'locality' => 2, 'place' => 3, 'postcode' => 4];

            return ($priority[$ta] ?? 9) <=> ($priority[$tb] ?? 9);
        });

        return $features[0];
    }

    private function throttle(): void
    {
        $maxPerMinute = max(1, (int) config('geocoding.providers.mapbox.rate_per_minute', 60));
        $key = 'mapbox-geocoding-api';

        $attempts = 0;
        while (RateLimiter::tooManyAttempts($key, $maxPerMinute)) {
            $attempts++;
            if ($attempts > 30) {
                throw new RuntimeException('OVER_QUERY_LIMIT');
            }
            $seconds = RateLimiter::availableIn($key);
            usleep(max(200_000, min(2_000_000, ($seconds > 0 ? $seconds : 1) * 200_000)));
        }

        RateLimiter::hit($key, 60);

        $delayMs = max(0, (int) config('geocoding.providers.mapbox.delay_ms', 20));
        if ($delayMs > 0) {
            usleep($delayMs * 1000);
        }
    }
}
