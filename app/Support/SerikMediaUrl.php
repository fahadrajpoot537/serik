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
            $relative = self::resolveExistingRelativePath($candidate);

            if ($relative !== null) {
                return self::toPublic($relative);
            }
        }

        return $fallback ?? self::placeholder();
    }

    /**
     * Newsletter popup image candidates (JPG first when theme option points at missing/broken webp).
     *
     * @return array<int, string>
     */
    public static function newsletterPopupCandidates(?string $themeOptionValue = null): array
    {
        $normalized = self::normalizeStorageRelativePath($themeOptionValue);
        $fallbacks = [
            'general/newsletter-image.jpg',
            'newsletter-1.webp',
            'newsletter.webp',
        ];

        if ($normalized === null) {
            return $fallbacks;
        }

        if (in_array($normalized, ['newsletter-1.webp', 'newsletter.webp'], true)) {
            return array_values(array_unique(array_merge(['general/newsletter-image.jpg'], [$normalized], $fallbacks)));
        }

        return array_values(array_unique(array_merge([$normalized], $fallbacks)));
    }

    public static function newsletterPopupImage(?string $themeOptionValue = null): string
    {
        return self::resolvePublic(self::newsletterPopupCandidates($themeOptionValue));
    }

    private static function normalizeStorageRelativePath(?string $path): ?string
    {
        $path = trim((string) $path);

        if ($path === '') {
            return null;
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://') || str_starts_with($path, '//')) {
            if (! preg_match('#/storage/(.+)$#i', $path, $matches)) {
                return null;
            }

            $path = $matches[1];
        }

        $relative = ltrim(str_replace('\\', '/', $path), '/');

        if (str_starts_with($relative, 'storage/')) {
            $relative = substr($relative, strlen('storage/'));
        }

        return $relative !== '' ? $relative : null;
    }

    private static function resolveExistingRelativePath(string $candidate): ?string
    {
        $relative = self::normalizeStorageRelativePath($candidate);

        if ($relative === null) {
            return null;
        }

        $diskPath = storage_path('app/public/' . $relative);
        $publicPath = public_path('storage/' . $relative);

        if (self::isUsableMediaFile($diskPath)) {
            return $relative;
        }

        if (self::isUsableMediaFile($publicPath)) {
            return $relative;
        }

        return null;
    }

    private static function isUsableMediaFile(string $path): bool
    {
        if (! is_file($path) || ! is_readable($path)) {
            return false;
        }

        $size = @filesize($path);

        return $size !== false && $size > 0 && realpath($path) !== false;
    }
}
