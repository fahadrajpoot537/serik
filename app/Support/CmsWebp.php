<?php

namespace App\Support;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * On-demand WebP for CMS / uploaded media (NOT TREB listing images).
 */
final class CmsWebp
{
    private const DISK = 'public';

    /**
     * Rewrite a public media URL to a WebP sibling when possible.
     * Skips TREB proxy paths entirely.
     */
    public static function preferWebpUrl(?string $url): ?string
    {
        $url = trim((string) $url);
        if ($url === '') {
            return $url;
        }

        if (self::isTrebPath($url)) {
            return $url;
        }

        if (! preg_match('/\.(jpe?g|png)(\?.*)?$/i', $url)) {
            return $url;
        }

        $relative = self::relativeFromUrl($url);
        if ($relative === null) {
            return $url;
        }

        $webpRelative = self::ensureWebpSibling($relative);
        if ($webpRelative === null) {
            return $url;
        }

        return CanonicalUrl::normalize(asset('storage/' . ltrim($webpRelative, '/')));
    }

    /**
     * Ensure a .webp sibling exists for a public-disk relative path.
     *
     * @return string|null Relative webp path on success
     */
    public static function ensureWebpSibling(string $relative): ?string
    {
        $relative = ltrim(str_replace('\\', '/', $relative), '/');
        if ($relative === '' || self::isTrebPath($relative)) {
            return null;
        }

        if (! preg_match('/\.(jpe?g|png)$/i', $relative)) {
            return str_ends_with(strtolower($relative), '.webp') ? $relative : null;
        }

        $webpRelative = preg_replace('/\.(jpe?g|png)$/i', '.webp', $relative);
        if (! is_string($webpRelative) || $webpRelative === '') {
            return null;
        }

        $disk = Storage::disk(self::DISK);

        if ($disk->exists($webpRelative)) {
            return $webpRelative;
        }

        if (! $disk->exists($relative)) {
            return null;
        }

        try {
            if (! class_exists(\Botble\Media\Facades\RvMedia::class)) {
                return null;
            }

            $absolute = $disk->path($relative);
            if (! is_file($absolute)) {
                return null;
            }

            $image = \Botble\Media\Facades\RvMedia::imageManager()->read($absolute);
            $encoded = (string) $image->encode(new \Intervention\Image\Encoders\WebpEncoder(quality: 82));

            $dir = dirname($webpRelative);
            if ($dir !== '.' && $dir !== '') {
                $disk->makeDirectory($dir);
            }

            $tmp = $dir . '/.tmp_' . bin2hex(random_bytes(6)) . '.webp';
            $disk->put($tmp, $encoded);
            if ($disk->exists($webpRelative)) {
                $disk->delete($tmp);
            } else {
                // Atomic-ish replace
                if (! @rename($disk->path($tmp), $disk->path($webpRelative))) {
                    $disk->put($webpRelative, $encoded);
                    $disk->delete($tmp);
                }
            }

            // Also write beside public/storage symlink target when needed.
            $publicPath = public_path('storage/' . $webpRelative);
            if (! is_file($publicPath) && is_file($disk->path($webpRelative))) {
                File::ensureDirectoryExists(dirname($publicPath));
                @copy($disk->path($webpRelative), $publicPath);
            }

            return $webpRelative;
        } catch (Throwable $e) {
            Log::debug('CmsWebp: convert failed', [
                'path' => $relative,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    public static function isTrebPath(string $value): bool
    {
        $value = str_replace('\\', '/', $value);

        return str_contains($value, '/properties/treb/')
            || str_contains($value, 'properties/treb/')
            || str_contains($value, 'trreb-image.ampre.ca')
            || str_contains($value, 'ampre.ca');
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
