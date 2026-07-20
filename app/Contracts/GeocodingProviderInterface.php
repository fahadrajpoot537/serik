<?php

namespace App\Contracts;

use Botble\RealEstate\Models\Property;

interface GeocodingProviderInterface
{
    public function name(): string;

    public function isConfigured(): bool;

    public function buildAddress(Property $property): string;

    /**
     * Geocode a property. Returns a normalized result array on success, null on soft failure.
     * Temporary rate-limit errors should throw RuntimeException('OVER_QUERY_LIMIT').
     *
     * @return array{
     *   provider: string,
     *   status: string,
     *   lat: float,
     *   lng: float,
     *   formatted_address: string,
     *   location_type: string,
     *   relevance?: float|null,
     *   searched_address: string,
     *   address_components?: array<int, mixed>
     * }|null
     */
    public function geocode(Property $property): ?array;
}
