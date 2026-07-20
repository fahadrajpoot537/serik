<?php

namespace Botble\RealEstate\Services;

use Botble\RealEstate\Models\Property;
use Botble\Slug\Facades\SlugHelper;
use Botble\Slug\Models\Slug;
use Illuminate\Support\Str;

class PropertySlugResolver
{
    public static function resolve(?string $key): ?Slug
    {
        if ($key === null || $key === '') {
            return null;
        }

        $listingKey = self::extractListingKey($key);

        if ($listingKey === null) {
            return null;
        }

        $property = Property::query()
            ->where('external_id', $listingKey)
            ->orWhere('external_id', strtolower($listingKey))
            ->first();

        if (! $property) {
            return null;
        }

        $existing = Slug::query()
            ->where('reference_type', Property::class)
            ->where('reference_id', $property->getKey())
            ->first();

        if ($existing) {
            return $existing;
        }

        $slugKey = Str::slug($property->name ?? 'property') . '-' . strtolower((string) $property->external_id);

        return SlugHelper::createSlug($property, $slugKey);
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
