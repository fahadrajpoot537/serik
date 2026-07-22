<?php

namespace App\Support;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Drivers\Gd\Driver as GdDriver;
use Intervention\Image\Drivers\Imagick\Driver as ImagickDriver;
use Intervention\Image\Encoders\WebpEncoder;
use Intervention\Image\ImageManager;

/**
 * Download TREB/AMP listing photos and persist them as WebP on the public disk.
 */
final class TrebImageStore
{
    private const DISK = 'public';

    private const BASE_DIR = 'properties/treb';

    public static function relativePath(string $listingKey, string $filename = 'cover.webp'): string
    {
        $listingKey = strtoupper(trim($listingKey));
        $filename = basename(str_replace('\\', '/', $filename));

        return self::BASE_DIR . '/' . $listingKey . '/' . $filename;
    }

    public function isRemoteUrl(?string $value): bool
    {
        $value = trim((string) $value);

        return $value !== ''
            && (str_starts_with($value, 'http://') || str_starts_with($value, 'https://'));
    }

    public function isStoredWebp(?string $value): bool
    {
        $value = ltrim(str_replace('\\', '/', (string) $value), '/');

        return $value !== ''
            && ! $this->isRemoteUrl($value)
            && str_ends_with(strtolower($value), '.webp');
    }

    public function persistFromRemoteUrl(string $listingKey, string $remoteUrl, string $filename = 'cover.webp'): ?string
    {
        $listingKey = strtoupper(trim($listingKey));
        $remoteUrl = trim($remoteUrl);

        if ($listingKey === '' || ! $this->isRemoteUrl($remoteUrl)) {
            return null;
        }

        $filename = $this->sanitizeFilename($filename);
        $relativePath = self::relativePath($listingKey, $filename);

        if (Storage::disk(self::DISK)->exists($relativePath)) {
            return $relativePath;
        }

        try {
            $response = Http::timeout(30)
                ->connectTimeout(10)
                ->withHeaders(['User-Agent' => 'SerikRealty/1.0'])
                ->get($remoteUrl);

            if (! $response->successful()) {
                return null;
            }

            $binary = $response->body();
            if ($binary === '') {
                return null;
            }

            $encoded = (string) $this->imageManager()
                ->read($binary)
                ->encode(new WebpEncoder(quality: 82));

            Storage::disk(self::DISK)->makeDirectory(dirname($relativePath));
            Storage::disk(self::DISK)->put($relativePath, $encoded, 'public');

            return $relativePath;
        } catch (\Throwable $e) {
            Log::warning('TrebImageStore: failed to persist image', [
                'listing_key' => $listingKey,
                'url' => $remoteUrl,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * @param  array<int, string>  $remoteUrls
     * @return array<int, string>
     */
    public function persistGallery(string $listingKey, array $remoteUrls, int $max = 25): array
    {
        $stored = [];
        $index = 0;

        foreach (array_values(array_filter($remoteUrls)) as $url) {
            if ($index >= $max) {
                break;
            }

            if ($this->isStoredWebp($url)) {
                $stored[] = ltrim(str_replace('\\', '/', $url), '/');
                $index++;

                continue;
            }

            if (! $this->isRemoteUrl($url)) {
                continue;
            }

            $path = $this->persistFromRemoteUrl(
                $listingKey,
                $url,
                sprintf('%02d.webp', $index + 1)
            );

            if ($path) {
                $stored[] = $path;
                $index++;
            }
        }

        return $stored;
    }

    private function sanitizeFilename(string $filename): string
    {
        $filename = basename(str_replace('\\', '/', $filename));
        $filename = preg_replace('/[^a-zA-Z0-9._-]/', '', $filename) ?: 'cover.webp';

        if (! str_ends_with(strtolower($filename), '.webp')) {
            $filename .= '.webp';
        }

        return $filename;
    }

    private function imageManager(): ImageManager
    {
        $driver = extension_loaded('imagick') ? ImagickDriver::class : GdDriver::class;

        return new ImageManager(new $driver());
    }
}
