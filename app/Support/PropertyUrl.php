<?php

namespace App\Support;

use Botble\RealEstate\Models\Property;
use Botble\Slug\Facades\SlugHelper;
use Illuminate\Support\Str;

final class PropertyUrl
{
    /**
     * Canonical front-end path for a property detail page.
     */
    public static function path(string $slug): string
    {
        $slug = trim($slug, '/');

        return $slug === '' ? 'properties' : 'properties/' . $slug;
    }

    public static function forSlug(string $slug): string
    {
        // Relative path so homepage HTML cache works on 127.0.0.1 / localhost / production.
        return '/' . ltrim(self::path($slug), '/');
    }

    public static function forProperty(Property $property): string
    {
        $slug = trim((string) ($property->slug ?? ''));

        if ($slug === '') {
            if (! $property->relationLoaded('slugable')) {
                $property->loadMissing('slugable');
            }

            if ($property->slugable) {
                $slug = trim((string) $property->slugable->key);
            }
        }

        // Never fall back to homepage URL when slug rows are missing.
        if ($slug === '') {
            $listingKey = strtolower(trim((string) ($property->external_id ?: $property->getKey())));
            $slug = Str::slug((string) ($property->name ?: 'property')) . '-' . $listingKey;

            try {
                if ($slug !== '' && ! $property->slugable) {
                    SlugHelper::createSlug($property, $slug);
                    $property->unsetRelation('slugable');
                    $property->loadMissing('slugable');
                    if ($property->slugable?->key) {
                        $slug = (string) $property->slugable->key;
                    }
                }
            } catch (\Throwable) {
                // Keep synthesized slug even if create fails.
            }
        }

        if ($slug !== '') {
            return self::forSlug($slug);
        }

        // Absolute last resort — still never return "/" / homepage.
        return self::forSlug('property-' . (string) $property->getKey());
    }

    /**
     * Rewrite legacy /on/{filters}/map/{slug} paths to /properties/{slug}.
     */
    public static function normalizePath(string $path): string
    {
        $path = '/' . trim($path, '/');

        if (preg_match('#^/on/.+/map/([^/]+)$#', $path, $matches)) {
            return '/' . self::path($matches[1]);
        }

        return $path === '//' ? '/' : $path;
    }

    public static function isLegacyDetailPath(string $path): bool
    {
        return (bool) preg_match('#^/on/.+/map/[^/]+$#', '/' . trim($path, '/'));
    }
}
