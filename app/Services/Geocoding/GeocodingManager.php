<?php

namespace App\Services\Geocoding;

use App\Contracts\GeocodingProviderInterface;
use Botble\RealEstate\Models\Property;
use InvalidArgumentException;

class GeocodingManager
{
    /** @var array<string, class-string<GeocodingProviderInterface>> */
    protected array $drivers = [
        'google' => GoogleGeocodingService::class,
        'mapbox' => MapboxGeocodingService::class,
    ];

    public function driver(?string $name = null): GeocodingProviderInterface
    {
        $name = strtolower(trim($name ?: (string) config('geocoding.default', 'mapbox')));

        if (! isset($this->drivers[$name])) {
            throw new InvalidArgumentException("Unsupported geocoding provider [{$name}].");
        }

        /** @var GeocodingProviderInterface $provider */
        $provider = app($this->drivers[$name]);

        return $provider;
    }

    public function providerName(): string
    {
        return $this->driver()->name();
    }

    public function isConfigured(): bool
    {
        return $this->driver()->isConfigured();
    }

    public function buildAddress(Property $property): string
    {
        return $this->driver()->buildAddress($property);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function geocode(Property $property): ?array
    {
        return $this->driver()->geocode($property);
    }
}
