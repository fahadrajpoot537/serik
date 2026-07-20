<?php

namespace App\Support;

/**
 * Normalize property / CMS media paths to a public URL.
 *
 * Botble often stores paths like "properties/foo.jpg" (no "storage/" prefix).
 * Using asset($path) alone then hits https://site/properties/foo.jpg → 404.
 * Storage::disk('public')->url() / RvMedia expect the relative disk path.
 */
final class SerikMediaUrl
{
    public static function toPublic(?string $path, ?string $fallback = null): string
    {
        $path = trim((string) $path);

        if ($path === '') {
            return $fallback ?? asset('storage/general/placeholder.png');
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://') || str_starts_with($path, '//')) {
            return $path;
        }

        $path = ltrim(str_replace('\\', '/', $path), '/');

        // Already a public storage URL path
        if (str_starts_with($path, 'storage/')) {
            return asset($path);
        }

        // Laravel public disk relative path (Botble media)
        return asset('storage/' . $path);
    }
}
