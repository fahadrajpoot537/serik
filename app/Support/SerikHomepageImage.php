<?php

namespace App\Support;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Homepage-only on-demand image variants.
 *
 * Writes compressed WebP copies under storage/serik-hp-cache/.
 * Never modifies, replaces, or deletes original media files.
 */
final class SerikHomepageImage
{
    private const DISK = 'public';

    private const CACHE_DIR = 'serik-hp-cache';

    /**
     * Return a cached resized WebP URL for homepage delivery, or the original URL on failure.
     */
    public static function optimizedUrl(?string $url, int $maxWidth, int $quality = 78): ?string
    {
        $url = trim((string) $url);
        if ($url === '' || $maxWidth < 32) {
            return $url;
        }

        if (! SerikHomepage::isHomepageRequest()) {
            return $url;
        }

        if (str_starts_with($url, 'data:') || str_contains($url, 'serik-hp-cache/')) {
            return $url;
        }

        if (CmsWebp::isTrebPath($url)) {
            return $url;
        }

        $relative = self::relativeFromUrl($url);
        if ($relative === null) {
            return $url;
        }

        $disk = Storage::disk(self::DISK);
        if (! $disk->exists($relative)) {
            return $url;
        }

        try {
            $absolute = $disk->path($relative);
            if (! is_file($absolute)) {
                return $url;
            }

            $mtime = (int) @filemtime($absolute);
            $cacheName = sha1($relative . '|w' . $maxWidth . '|q' . $quality . '|m' . $mtime) . '.webp';
            $cacheRelative = self::CACHE_DIR . '/' . $cacheName;

            if (! $disk->exists($cacheRelative)) {
                if (! self::writeVariant($absolute, $cacheRelative, $maxWidth, $quality)) {
                    return $url;
                }
            }

            return CanonicalUrl::normalize(asset('storage/' . ltrim($cacheRelative, '/')));
        } catch (Throwable $e) {
            Log::debug('SerikHomepageImage: optimize failed', [
                'url' => $url,
                'error' => $e->getMessage(),
            ]);

            return $url;
        }
    }

    /**
     * Build a srcset string for homepage images.
     *
     * @param  list<int>  $widths
     */
    public static function srcset(?string $url, array $widths, int $quality = 78): string
    {
        $parts = [];
        $seen = [];

        foreach ($widths as $width) {
            $width = (int) $width;
            if ($width < 32 || isset($seen[$width])) {
                continue;
            }
            $seen[$width] = true;
            $variant = self::optimizedUrl($url, $width, $quality);
            if (! is_string($variant) || $variant === '') {
                continue;
            }
            $parts[] = $variant . ' ' . $width . 'w';
        }

        return implode(', ', $parts);
    }

    private static function writeVariant(string $absoluteSource, string $cacheRelative, int $maxWidth, int $quality): bool
    {
        if (! class_exists(\Botble\Media\Facades\RvMedia::class)) {
            return false;
        }

        $disk = Storage::disk(self::DISK);
        $disk->makeDirectory(self::CACHE_DIR);

        $image = \Botble\Media\Facades\RvMedia::imageManager()->read($absoluteSource);
        $image->scaleDown(width: $maxWidth);

        $encoded = (string) $image->encode(new \Intervention\Image\Encoders\WebpEncoder(quality: $quality));
        if ($encoded === '') {
            return false;
        }

        $tmp = self::CACHE_DIR . '/.tmp_' . bin2hex(random_bytes(6)) . '.webp';
        $disk->put($tmp, $encoded);

        if ($disk->exists($cacheRelative)) {
            $disk->delete($tmp);

            return true;
        }

        $tmpPath = $disk->path($tmp);
        $destPath = $disk->path($cacheRelative);
        if (! @rename($tmpPath, $destPath)) {
            $disk->put($cacheRelative, $encoded);
            $disk->delete($tmp);
        }

        $publicPath = public_path('storage/' . $cacheRelative);
        if (! is_file($publicPath) && is_file($disk->path($cacheRelative))) {
            File::ensureDirectoryExists(dirname($publicPath));
            @copy($disk->path($cacheRelative), $publicPath);
        }

        return $disk->exists($cacheRelative);
    }

    private static function relativeFromUrl(string $url): ?string
    {
        if (preg_match('#/storage/(.+)$#i', $url, $matches)) {
            return ltrim(str_replace('\\', '/', $matches[1]), '/');
        }

        if (! preg_match('#^https?://#i', $url) && ! str_starts_with($url, '//')) {
            $path = ltrim(str_replace('\\', '/', $url), '/');
            if (str_starts_with($path, 'storage/')) {
                $path = substr($path, 8);
            }

            return $path !== '' ? $path : null;
        }

        return null;
    }
}
