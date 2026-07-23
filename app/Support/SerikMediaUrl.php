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

    /**
     * Browser-safe cover image for map cluster/list cards.
     * Never returns trreb-image.ampre.ca (403 in browser) — uses local WebP route.
     */
    public static function mapListingCover(?string $listingKey, ?string $imageVal = null): string
    {
        $listingKey = strtoupper(trim((string) $listingKey));
        $imageVal = trim((string) $imageVal);

        if ($listingKey !== '') {
            $trebRelative = 'properties/treb/' . $listingKey . '/cover.webp';
            if (self::resolveExistingRelativePath($trebRelative) !== null) {
                return self::toPublic($trebRelative);
            }

            $firstGallery = 'properties/treb/' . $listingKey . '/01.webp';
            if (self::resolveExistingRelativePath($firstGallery) !== null) {
                return self::toPublic($firstGallery);
            }
        }

        if ($imageVal !== '' && ! self::isRemoteUrl($imageVal)) {
            $relative = ltrim(str_replace('\\', '/', $imageVal), '/');
            if (self::resolveExistingRelativePath($relative) !== null) {
                return self::toPublic($relative);
            }
            if (preg_match('/^L3RycmVi/i', $relative) && $listingKey !== '') {
                return self::trebCoverPublicUrl($listingKey);
            }
        }

        if ($imageVal !== '' && self::isRemoteUrl($imageVal)) {
            if (
                $listingKey !== ''
                && (
                    str_contains($imageVal, 'trreb-image.ampre.ca')
                    || str_contains($imageVal, '/rs:')
                    || str_contains($imageVal, 'rs:fit')
                )
            ) {
                return self::trebCoverPublicUrl($listingKey);
            }

            if (! str_contains($imageVal, 'trreb-image.ampre.ca') && ! str_contains($imageVal, '/rs:')) {
                return self::normalizeExternalUrl($imageVal);
            }
        }

        if ($listingKey !== '') {
            return self::trebCoverPublicUrl($listingKey);
        }

        return self::placeholder();
    }

    /**
     * Browser-safe gallery URLs for property detail pages.
     * Never exposes TREB CDN URLs — uses local WebP paths when available.
     *
     * @param  array<int, string>  $dbImages
     * @return array<int, string>
     */
    public static function mapListingGalleryUrls(?string $listingKey, ?string $imageVal = null, array $dbImages = []): array
    {
        $listingKey = strtoupper(trim((string) $listingKey));
        $urls = [];

        foreach ($dbImages as $path) {
            if (! is_string($path) || trim($path) === '') {
                continue;
            }

            if (self::isRemoteUrl($path)) {
                continue;
            }

            if (self::resolveExistingRelativePath($path) === null) {
                continue;
            }

            $public = self::toPublic($path);
            if (! str_contains($public, 'placeholder.png')) {
                $urls[] = $public;
            }
        }

        $diskUrls = $listingKey !== '' ? self::discoverLocalTrebGalleryUrls($listingKey) : [];

        if ($diskUrls !== [] && count($diskUrls) >= count($urls)) {
            return $diskUrls;
        }

        if ($urls !== []) {
            return array_values(array_unique($urls));
        }

        if ($diskUrls !== []) {
            return $diskUrls;
        }

        $cover = self::mapListingCover($listingKey !== '' ? $listingKey : null, $imageVal);
        if ($cover !== '' && ! str_contains($cover, 'placeholder.png')) {
            return [$cover];
        }

        return [];
    }

    public static function trebCoverPublicUrl(string $listingKey): string
    {
        $listingKey = strtoupper(preg_replace('/[^A-Z0-9]/', '', $listingKey));

        return CanonicalUrl::normalize(asset('storage/properties/treb/' . $listingKey . '/cover.webp'));
    }

    /**
     * @return array<int, string>
     */
    private static function discoverLocalTrebGalleryUrls(string $listingKey): array
    {
        $listingKey = strtoupper(preg_replace('/[^A-Z0-9]/', '', $listingKey));
        if ($listingKey === '') {
            return [];
        }

        $urls = [];
        $coverRelative = 'properties/treb/' . $listingKey . '/cover.webp';
        if (self::resolveExistingRelativePath($coverRelative) !== null) {
            $urls[] = self::toPublic($coverRelative);
        }

        for ($i = 1; $i <= 25; $i++) {
            $relative = 'properties/treb/' . $listingKey . '/' . sprintf('%02d.webp', $i);
            if (self::resolveExistingRelativePath($relative) === null) {
                if ($i === 1 && $urls === []) {
                    continue;
                }

                break;
            }

            $urls[] = self::toPublic($relative);
        }

        return array_values(array_unique($urls));
    }

    /**
     * Resolve a remote fetch URL for server-side image download (not for <img> src).
     */
    public static function resolveTrebRemoteUrl(?string $path): ?string
    {
        $path = trim((string) $path);
        if ($path === '') {
            return null;
        }

        if (str_contains($path, 'trreb-image.ampre.ca')) {
            return CanonicalUrl::normalize($path);
        }

        return self::resolveTrebCdnFromImgproxy($path);
    }

    /**
     * @deprecated Use mapListingCover() for map UI.
     */
    public static function mapPropertyImage(?string $path, ?string $fallback = null): string
    {
        $path = trim((string) $path);

        if ($path !== '' && ! self::isRemoteUrl($path)) {
            return self::toPublic($path, $fallback);
        }

        return $fallback ?? self::placeholder();
    }

    private static function isRemoteUrl(string $value): bool
    {
        return str_starts_with($value, 'http://')
            || str_starts_with($value, 'https://')
            || str_starts_with($value, '//');
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

    private static function resolveTrebCdnFromImgproxy(string $value): ?string
    {
        $value = trim($value);
        if ($value === '') {
            return null;
        }

        // Bare base64-encoded TREB path stored in image_val (no imgproxy host).
        if (preg_match('/^L3RycmVi/i', $value)) {
            return self::decodeImgproxyEncodedTrebPath($value);
        }

        if (! str_contains($value, '/rs:') && ! str_contains($value, 'rs:fit')) {
            return null;
        }

        $path = $value;
        if (preg_match('#^https?://#i', $value)) {
            $path = (string) (parse_url($value, PHP_URL_PATH) ?? '');
        }

        $path = '/' . ltrim(str_replace('\\', '/', $path), '/');
        $segments = array_values(array_filter(explode('/', $path)));
        $encoded = $segments !== [] ? (string) end($segments) : '';
        if ($encoded === '' || strlen($encoded) < 16) {
            return null;
        }

        return self::decodeImgproxyEncodedTrebPath($encoded);
    }

    private static function decodeImgproxyEncodedTrebPath(string $encoded): ?string
    {
        $encoded = trim($encoded);
        if ($encoded === '') {
            return null;
        }

        // imgproxy appends a file extension after the base64 payload.
        $encoded = preg_replace('/\.(jpe?g|png|webp|gif|bmp|avif)$/i', '', $encoded) ?? $encoded;

        $decoded = base64_decode(strtr($encoded, '-_', '+/'), true);
        if ($decoded === false || $decoded === '') {
            return null;
        }

        $decoded = '/' . ltrim(str_replace('\\', '/', $decoded), '/');
        if (! str_starts_with($decoded, '/trreb/')) {
            return null;
        }

        return 'https://trreb-image.ampre.ca' . $decoded;
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
