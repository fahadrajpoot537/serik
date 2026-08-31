<?php

namespace App\Support;

final class ResolvedLocation
{
    public function __construct(
        public readonly string $city,
        public readonly string $region,
        public readonly string $country,
        public readonly float $latitude,
        public readonly float $longitude,
        public readonly string $source,
        public readonly int $timestamp,
        public readonly string $accuracy = 'city',
    ) {
    }

    /**
     * @return array{
     *   city: string,
     *   region: string,
     *   country: string,
     *   latitude: float,
     *   longitude: float,
     *   source: string,
     *   timestamp: int,
     *   accuracy: string
     * }
     */
    public function toArray(): array
    {
        return [
            'city' => $this->city,
            'region' => $this->region,
            'country' => $this->country,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'source' => $this->source,
            'timestamp' => $this->timestamp,
            'accuracy' => $this->accuracy,
        ];
    }

    public function publicLabel(): string
    {
        return $this->city !== '' ? $this->city : 'Ontario';
    }

    public function isFallback(): bool
    {
        return in_array($this->source, ['fallback', 'default'], true);
    }

    public static function ontarioFallback(): self
    {
        return new self(
            'Toronto',
            'Ontario',
            'CA',
            43.6532,
            -79.3832,
            'fallback',
            time(),
            'city',
        );
    }
}
