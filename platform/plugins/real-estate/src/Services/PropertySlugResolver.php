<?php

namespace Botble\RealEstate\Services;

use Botble\Slug\Models\Slug;

class PropertySlugResolver
{
    public static function resolve(?string $key): ?Slug
    {
        if ($key === null || $key === '') {
            return null;
        }

        return app(LiveTrebPropertyFallbackService::class)->resolveSlugForRequest($key);
    }

    public static function extractListingKey(string $key): ?string
    {
        $key = trim($key);

        // TREB/AMP ListingKey: board letter + digits (E13561586, W1234567, N…, C…).
        // Older code only matched C/W — that caused mass detail-page 404s for E/N/S/….
        if (preg_match('/^[A-Za-z]\d+$/', $key)) {
            return strtoupper($key);
        }

        if (preg_match('/-([A-Za-z]\d+)$/', $key, $matches)) {
            return strtoupper($matches[1]);
        }

        return null;
    }
}
