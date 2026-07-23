<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpFoundation\Response;
use Theme\homzen\Supports\TrebPropertyHelper;

/**
 * Browser-safe TREB image URLs. trreb-image.ampre.ca returns 403 in browsers,
 * so the frontend uses same-origin proxy paths that stream from CDN on demand.
 * No WebP conversion, disk storage, or queue processing.
 */
final class TrebImageProxy
{
    private const HTTP_TIMEOUT = 20;

    public static function coverPublicUrl(string $listingKey): string
    {
        return self::publicUrl($listingKey, 0);
    }

    public static function publicUrl(string $listingKey, int $index = 0): string
    {
        $listingKey = strtoupper(preg_replace('/[^A-Z0-9]/', '', $listingKey));
        if ($listingKey === '') {
            return SerikMediaUrl::placeholder();
        }

        $filename = $index === 0 ? 'cover.webp' : sprintf('%02d.webp', $index);

        return CanonicalUrl::normalize(asset('storage/properties/treb/' . $listingKey . '/' . $filename));
    }

    public static function filenameToIndex(string $filename): ?int
    {
        $filename = strtolower(trim($filename));
        if ($filename === 'cover.webp') {
            return 0;
        }

        if (preg_match('/^(\d{2})\.webp$/', $filename, $matches)) {
            return max(0, (int) $matches[1]);
        }

        return null;
    }

    /**
     * @return list<string>
     */
    public static function remoteUrlsForListing(string $listingKey, ?string $imageVal = null): array
    {
        $listingKey = strtoupper(trim($listingKey));
        if ($listingKey === '') {
            return [];
        }

        $urls = TrebPropertyHelper::getPropertyImagesForPersistence($listingKey, $imageVal);

        return TrebMediaFilter::filterPhotoUrls(array_values(array_filter($urls, static fn ($url): bool => is_string($url) && trim($url) !== '')));
    }

    public static function remoteUrlAtIndex(string $listingKey, int $index, ?string $imageVal = null): ?string
    {
        $urls = self::remoteUrlsForListing($listingKey, $imageVal);

        return $urls[$index] ?? null;
    }

    public static function listingHasImage(string $listingKey, ?string $imageVal = null): bool
    {
        if (SerikMediaUrl::resolveCdnUrl($imageVal) !== null) {
            return true;
        }

        $imageVal = trim((string) $imageVal);
        if ($imageVal !== '' && str_contains($imageVal, 'properties/treb/')) {
            return true;
        }

        if ($imageVal !== '' && str_starts_with($imageVal, 'http')) {
            return TrebMediaFilter::isPhotoMediaUrl($imageVal);
        }

        $listingKey = strtoupper(trim($listingKey));
        if ($listingKey === '') {
            return false;
        }

        // Cache-only check — never hit AMP from a hot path (map API, list cards).
        foreach ([
            'treb_images_v5_' . $listingKey,
            'treb_property_images_' . $listingKey,
        ] as $cacheKey) {
            $cached = Cache::get($cacheKey);
            if (is_array($cached) && $cached !== []) {
                return true;
            }
        }

        return false;
    }

    public static function stream(string $listingKey, string $filename, ?string $imageVal = null): Response
    {
        $index = self::filenameToIndex($filename);
        if ($index === null) {
            abort(404);
        }

        $remoteUrl = self::remoteUrlAtIndex($listingKey, $index, $imageVal);
        if ($remoteUrl === null || $remoteUrl === '') {
            abort(404);
        }

        $response = Http::timeout(self::HTTP_TIMEOUT)
            ->connectTimeout(8)
            ->retry(2, 200, throw: false)
            ->withHeaders(['User-Agent' => 'SerikRealty/1.0'])
            ->get($remoteUrl);

        if (! $response->successful()) {
            abort(404);
        }

        $body = $response->body();
        if ($body === '') {
            abort(404);
        }

        $contentType = trim((string) ($response->header('Content-Type') ?? ''));
        if ($contentType === '' || $contentType === 'application/octet-stream') {
            $contentType = self::guessContentType($remoteUrl, $body);
        }

        return response($body, 200, [
            'Content-Type' => $contentType,
            'Cache-Control' => 'public, max-age=86400, stale-while-revalidate=604800',
            'X-Serik-Image-Source' => 'treb-cdn-proxy',
        ]);
    }

    private static function guessContentType(string $url, string $body): string
    {
        $url = strtolower($url);
        if (str_contains($url, '.png')) {
            return 'image/png';
        }
        if (str_contains($url, '.webp')) {
            return 'image/webp';
        }
        if (str_contains($url, '.gif')) {
            return 'image/gif';
        }

        if (str_starts_with($body, "\x89PNG")) {
            return 'image/png';
        }
        if (str_starts_with($body, 'GIF')) {
            return 'image/gif';
        }
        if (str_starts_with($body, 'RIFF') && str_contains(substr($body, 0, 16), 'WEBP')) {
            return 'image/webp';
        }

        return 'image/jpeg';
    }
}
