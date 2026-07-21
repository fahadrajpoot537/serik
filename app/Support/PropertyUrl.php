<?php

namespace App\Support;

use Botble\RealEstate\Models\Property;

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
        return url(self::path($slug));
    }

    public static function forProperty(Property $property): string
    {
        $slug = trim((string) ($property->slug ?? ''));

        if ($slug === '' && $property->relationLoaded('slugable') && $property->slugable) {
            $slug = trim((string) $property->slugable->key);
        }

        if ($slug !== '') {
            return self::forSlug($slug);
        }

        return (string) $property->url;
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
