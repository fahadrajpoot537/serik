<?php

namespace App\Services;

use App\Services\Geocoding\GoogleGeocodingService as GoogleProvider;
use Botble\RealEstate\Models\Property;

/**
 * Backward-compatible alias for the Google provider.
 *
 * Prefer App\Services\Geocoding\GeocodingManager for new code.
 *
 * @deprecated
 */
class GoogleGeocodingService
{
    public function __construct(private GoogleProvider $provider) {}

    public function isConfigured(): bool
    {
        return $this->provider->isConfigured();
    }

    /**
     * @param  array<string, mixed>|Property  $propertyOrParts
     */
    public function buildAddress(array|Property $propertyOrParts): string
    {
        if ($propertyOrParts instanceof Property) {
            return $this->provider->buildAddress($propertyOrParts);
        }

        $unit = trim((string) ($propertyOrParts['unit'] ?? ''));
        $street = trim((string) ($propertyOrParts['street'] ?? $propertyOrParts['address'] ?? ''));
        $city = trim((string) ($propertyOrParts['city'] ?? ''));
        $province = trim((string) ($propertyOrParts['province'] ?? $propertyOrParts['state'] ?? 'Ontario'));
        $postal = trim((string) ($propertyOrParts['postal_code'] ?? $propertyOrParts['zip_code'] ?? ''));
        $country = trim((string) ($propertyOrParts['country'] ?? 'Canada'));

        $line = $street;
        if ($unit !== '' && $street !== '' && stripos($street, $unit) === false) {
            $line = 'Unit ' . $unit . ', ' . $street;
        } elseif ($unit !== '' && $street === '') {
            $line = 'Unit ' . $unit;
        }

        return implode(', ', array_values(array_filter([
            $line,
            $city,
            $province,
            $postal,
            $country,
        ], static fn ($p) => $p !== '')));
    }

    /**
     * @return array{
     *   success: bool,
     *   status: string,
     *   lat?: float,
     *   lng?: float,
     *   formatted_address?: string,
     *   location_type?: string,
     *   address_components?: array<int, mixed>,
     *   searched_address: string,
     *   error?: string,
     *   provider?: string,
     *   relevance?: float|null
     * }
     */
    public function geocode(string $address): array
    {
        $address = trim($address);
        if ($address === '') {
            return [
                'success' => false,
                'status' => 'INVALID_REQUEST',
                'searched_address' => $address,
                'error' => 'Empty address',
            ];
        }

        if (! $this->isConfigured()) {
            return [
                'success' => false,
                'status' => 'REQUEST_DENIED',
                'searched_address' => $address,
                'error' => 'GOOGLE_MAPS_GEOCODING_API_KEY is not configured',
            ];
        }

        $result = $this->provider->geocodeAddress($address);
        if ($result === null) {
            return [
                'success' => false,
                'status' => 'ZERO_RESULTS',
                'searched_address' => $address,
                'error' => 'No results',
            ];
        }

        return array_merge(['success' => true], $result);
    }

    /**
     * @return array<string, string>
     */
    public function partsFromProperty(Property $property): array
    {
        return $this->provider->partsFromProperty($property);
    }
}
