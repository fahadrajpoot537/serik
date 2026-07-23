<?php

namespace App\Support;

/**
 * Filter TREB/AMP media to photo URLs only (skip PDF, video, documents).
 */
final class TrebMediaFilter
{
    /** @var list<string> */
    private const BLOCKED_CATEGORIES = [
        'document',
        'documents',
        'video',
        'videos',
        'virtual tour',
        'brochure',
        'floor plan',
        'floorplan',
    ];

    /** @var list<string> */
    private const BLOCKED_URL_EXTENSIONS = [
        '.pdf',
        '.mp4',
        '.mov',
        '.avi',
        '.webm',
        '.m4v',
        '.wmv',
        '.mpeg',
        '.mpg',
        '.mkv',
    ];

    /**
     * @param  array<string, mixed>  $media
     */
    public static function isPhotoAmpMedia(array $media): bool
    {
        $url = trim((string) ($media['MediaURL'] ?? ''));
        if ($url === '') {
            return false;
        }

        $category = strtolower(trim((string) ($media['MediaCategory'] ?? '')));
        if ($category !== '' && in_array($category, self::BLOCKED_CATEGORIES, true)) {
            return false;
        }

        $type = strtolower(trim((string) ($media['MediaType'] ?? '')));
        if ($type !== '' && (str_contains($type, 'pdf') || str_contains($type, 'video'))) {
            return false;
        }

        $resourceName = strtolower(trim((string) ($media['ResourceName'] ?? '')));
        if ($resourceName !== '' && self::urlLooksNonPhoto($resourceName)) {
            return false;
        }

        $size = trim((string) ($media['ImageSizeDescription'] ?? ''));
        if ($size === '' && ! self::isPhotoMediaUrl($url)) {
            return false;
        }

        return self::isPhotoMediaUrl($url);
    }

    public static function isPhotoMediaUrl(?string $url): bool
    {
        $url = strtolower(trim((string) $url));
        if ($url === '') {
            return false;
        }

        if (self::urlLooksNonPhoto($url)) {
            return false;
        }

        if (str_contains($url, 'trreb-image.ampre.ca')) {
            return true;
        }

        if (str_contains($url, '/rs:') || str_contains($url, 'rs:fit')) {
            return true;
        }

        if (preg_match('/\.(jpe?g|png|webp|gif|bmp|avif)(\?|#|$)/i', $url)) {
            return true;
        }

        if (preg_match('/^l3rycmvi/i', $url)) {
            return true;
        }

        return false;
    }

    /**
     * @param  array<int, string>  $urls
     * @return array<int, string>
     */
    public static function filterPhotoUrls(array $urls): array
    {
        return array_values(array_filter($urls, static function ($url): bool {
            return is_string($url) && self::isPhotoMediaUrl($url);
        }));
    }

    private static function urlLooksNonPhoto(string $value): bool
    {
        $value = strtolower(trim($value));

        foreach (self::BLOCKED_URL_EXTENSIONS as $ext) {
            if (str_contains($value, $ext)) {
                return true;
            }
        }

        foreach (['/document/', '/documents/', '/video/', '/videos/', 'application/pdf'] as $needle) {
            if (str_contains($value, $needle)) {
                return true;
            }
        }

        return false;
    }
}
