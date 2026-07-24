<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;
use Intervention\Image\Drivers\Gd\Driver as GdDriver;
use Intervention\Image\Drivers\Imagick\Driver as ImagickDriver;
use Intervention\Image\Encoders\WebpEncoder;
use Intervention\Image\ImageManager;

/**
 * Cached width variants for TREB proxy images (generate once, serve forever).
 */
final class TrebImageDerivative
{
  /** @var list<int> */
    public const WIDTHS = [320, 640, 960, 1280];

    private const DISK = 'public';

    private const CACHE_DIR = 'properties/treb-cache';

    public static function normalizeWidth(?int $width): ?int
    {
        if ($width === null || $width <= 0) {
            return null;
        }

        $allowed = null;

        foreach (self::WIDTHS as $candidate) {
            if ($width <= $candidate) {
                $allowed = $candidate;
                break;
            }
        }

        return $allowed ?? self::WIDTHS[array_key_last(self::WIDTHS)];
    }

    public static function relativePath(string $listingKey, string $filename, int $width): string
    {
        $listingKey = strtoupper(preg_replace('/[^A-Z0-9]/', '', $listingKey));
        $filename = basename(str_replace('\\', '/', $filename));

        return self::CACHE_DIR . '/' . $listingKey . '/' . $width . '/' . $filename;
    }

    public static function readCached(string $listingKey, string $filename, int $width): ?string
    {
        $path = self::relativePath($listingKey, $filename, $width);

        if (! Storage::disk(self::DISK)->exists($path)) {
            return null;
        }

        $binary = Storage::disk(self::DISK)->get($path);

        return is_string($binary) && $binary !== '' ? $binary : null;
    }

    public static function encodeForWidth(string $body, int $width): ?string
    {
        if ($body === '' || ! extension_loaded('gd') && ! extension_loaded('imagick')) {
            return null;
        }

        try {
            $driver = extension_loaded('imagick') ? new ImagickDriver() : new GdDriver();
            $image = (new ImageManager($driver))->read($body);

            if ($image->width() > $width) {
                $image->scaleDown(width: $width);
            }

            return (string) $image->encode(new WebpEncoder(quality: 82));
        } catch (\Throwable) {
            return null;
        }
    }

    public static function writeCached(string $listingKey, string $filename, int $width, string $binary): bool
    {
        $path = self::relativePath($listingKey, $filename, $width);
        $directory = dirname($path);

        if (! Storage::disk(self::DISK)->exists($directory)) {
            Storage::disk(self::DISK)->makeDirectory($directory);
        }

        return Storage::disk(self::DISK)->put($path, $binary);
    }
}
