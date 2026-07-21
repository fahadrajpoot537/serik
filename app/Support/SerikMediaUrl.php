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
    public static function placeholder(): string
    {
        return CanonicalUrl::normalize(asset('storage/general/placeholder.png'));
    }

    public static function toPublic(?string $path, ?string $fallback = null): string
    {
        $path = trim((string) $path);

        if ($path === '') {
            return $fallback ?? self::placeholder();
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://') || str_starts_with($path, '//')) {
            return self::normalizeExternalUrl($path, $fallback);
        }

        $path = ltrim(str_replace('\\', '/', $path), '/');

        // Already a public storage URL path
        if (str_starts_with($path, 'storage/')) {
            return CanonicalUrl::normalize(asset($path));
        }

        // Laravel public disk relative path (Botble media)
        return CanonicalUrl::normalize(asset('storage/' . $path));
    }

    public static function normalizeExternalUrl(string $url, ?string $fallback = null): string
    {
        $url = trim($url);

        if ($url === '') {
            return $fallback ?? self::placeholder();
        }

        // Rewrite legacy staging/dev hosts to the canonical CDN path.
        if (preg_match('#^https?://[^/]+/(storage/.+)$#i', $url, $matches)) {
            return CanonicalUrl::normalize(asset($matches[1]));
        }

        if (str_contains($url, 'mytemp.website') || str_contains($url, 'localhost')) {
            if (preg_match('#/storage/(.+)$#i', $url, $matches)) {
                return self::toPublic($matches[1], $fallback);
            }

            return $fallback ?? self::placeholder();
        }

        return CanonicalUrl::normalize($url);
    }

    /**
     * Resolve a theme/media filename to a public URL when the primary file is missing on disk.
     *
     * @param  array<int, string>  $candidates
     */
    public static function resolvePublic(array $candidates, ?string $fallback = null): string
    {
        foreach ($candidates as $candidate) {
            $candidate = trim((string) $candidate);
            if ($candidate === '') {
                continue;
            }

            $relative = ltrim(str_replace('\\', '/', $candidate), '/');
            if (str_starts_with($relative, 'storage/')) {
                $relative = substr($relative, strlen('storage/'));
            }

            $publicPath = public_path('storage/' . $relative);
            $diskPath = storage_path('app/public/' . $relative);

            if (is_file($publicPath) || is_file($diskPath)) {
                return self::toPublic($relative);
            }
        }

        return $fallback ?? self::placeholder();
    }
}
