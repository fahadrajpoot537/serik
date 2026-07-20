<?php

namespace App\Services\Geocoding;

use App\Contracts\GeocodingProviderInterface;
use Botble\RealEstate\Models\Property;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use RuntimeException;

class GoogleGeocodingService implements GeocodingProviderInterface
{
    use BuildsCanadianAddress;

    public function name(): string
    {
        return 'google';
    }

    public function isConfigured(): bool
    {
        return trim((string) config('geocoding.providers.google.key', '')) !== ''
            || trim((string) config('services.google_maps.geocoding_api_key', '')) !== '';
    }

    public function geocode(Property $property): ?array
    {
        return $this->geocodeAddress($this->buildAddress($property));
    }

    /**
     * Geocode a free-form address string (used by BC wrapper / tests).
     *
     * @return array<string, mixed>|null
     */
    public function geocodeAddress(string $address): ?array
    {
        $address = trim($address);
        if ($address === '') {
            return null;
        }

        if (! $this->isConfigured()) {
            Log::channel('geocoding')->error('Google geocode denied — missing API key', [
                'provider' => $this->name(),
                'address' => $address,
            ]);

            return null;
        }

        $this->throttle();

        $endpoint = rtrim((string) (
            config('geocoding.providers.google.url')
            ?: config('services.google_maps.geocoding_url')
            ?: 'https://maps.googleapis.com/maps/api/geocode/json'
        ), '/');

        $key = config('geocoding.providers.google.key')
            ?: config('services.google_maps.geocoding_api_key');

        try {
            $response = Http::timeout(20)
                ->acceptJson()
                ->get($endpoint, [
                    'address' => $address,
                    'key' => $key,
                    'region' => 'ca',
                    'components' => 'country:CA',
                ]);
        } catch (\Throwable $e) {
            Log::channel('geocoding')->warning('Google HTTP failure', [
                'provider' => $this->name(),
                'address' => $address,
                'error' => $e->getMessage(),
            ]);

            throw new RuntimeException('Google Geocoding HTTP error: ' . $e->getMessage(), 0, $e);
        }

        if (! $response->successful()) {
            throw new RuntimeException('Google Geocoding HTTP status ' . $response->status());
        }

        $body = $response->json() ?: [];
        $status = (string) ($body['status'] ?? 'UNKNOWN');

        if ($status === 'OVER_QUERY_LIMIT') {
            throw new RuntimeException('OVER_QUERY_LIMIT');
        }

        if (in_array($status, ['REQUEST_DENIED', 'INVALID_REQUEST', 'UNKNOWN_ERROR'], true)) {
            Log::channel('geocoding')->error('Google geocode denied/invalid', [
                'provider' => $this->name(),
                'address' => $address,
                'status' => $status,
                'error' => $body['error_message'] ?? null,
            ]);

            return null;
        }

        if ($status === 'ZERO_RESULTS' || empty($body['results'][0])) {
            Log::channel('geocoding')->info('Google ZERO_RESULTS', [
                'provider' => $this->name(),
                'address' => $address,
                'status' => $status,
            ]);

            return null;
        }

        $best = $this->pickBestResult($body['results']);
        $loc = $best['geometry']['location'] ?? [];
        $locationType = (string) ($best['geometry']['location_type'] ?? '');
        $lat = isset($loc['lat']) ? (float) $loc['lat'] : 0.0;
        $lng = isset($loc['lng']) ? (float) $loc['lng'] : 0.0;

        if ($lat === 0.0 && $lng === 0.0) {
            return null;
        }

        if ($locationType !== '' && $locationType !== 'ROOFTOP') {
            Log::channel('geocoding')->info('Google non-ROOFTOP result', [
                'provider' => $this->name(),
                'address' => $address,
                'location_type' => $locationType,
            ]);
        }

        return [
            'provider' => $this->name(),
            'status' => $status,
            'lat' => $lat,
            'lng' => $lng,
            'formatted_address' => (string) ($best['formatted_address'] ?? ''),
            'location_type' => $locationType,
            'relevance' => null,
            'searched_address' => $address,
            'address_components' => $best['address_components'] ?? [],
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $results
     * @return array<string, mixed>
     */
    private function pickBestResult(array $results): array
    {
        $priority = [
            'ROOFTOP' => 0,
            'RANGE_INTERPOLATED' => 1,
            'GEOMETRIC_CENTER' => 2,
            'APPROXIMATE' => 3,
        ];

        usort($results, static function ($a, $b) use ($priority) {
            $ta = (string) ($a['geometry']['location_type'] ?? 'APPROXIMATE');
            $tb = (string) ($b['geometry']['location_type'] ?? 'APPROXIMATE');

            return ($priority[$ta] ?? 9) <=> ($priority[$tb] ?? 9);
        });

        return $results[0];
    }

    private function throttle(): void
    {
        $maxPerMinute = max(1, (int) (
            config('geocoding.providers.google.rate_per_minute')
            ?: config('services.google_maps.rate_per_minute', 50)
        ));
        $key = 'google-geocoding-api';

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

        $delayMs = max(0, (int) (
            config('geocoding.providers.google.delay_ms')
            ?: config('services.google_maps.delay_ms', 50)
        ));
        if ($delayMs > 0) {
            usleep($delayMs * 1000);
        }
    }
}
