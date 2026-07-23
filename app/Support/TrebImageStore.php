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

    private const HTTP_TIMEOUT_SECONDS = 12;

    private const HTTP_CONNECT_TIMEOUT_SECONDS = 5;

    private const HTTP_RETRY_TIMES = 2;

    private const HTTP_RETRY_SLEEP_MS = 500;

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

    public function storedWebpExists(?string $value): bool
    {
        if (! $this->isStoredWebp($value)) {
            return false;
        }

        $relative = $this->normalizeRelativePath((string) $value);

        return $relative !== null && Storage::disk(self::DISK)->exists($relative);
    }

    public function coverExistsOnDisk(string $listingKey): bool
    {
        $listingKey = strtoupper(trim($listingKey));
        if ($listingKey === '') {
            return false;
        }

        return Storage::disk(self::DISK)->exists(self::relativePath($listingKey, 'cover.webp'));
    }

    /**
     * Persist from a URL or disk-relative path — never HTTP-fetches same-origin /storage URLs.
     */
    public function persistFromUrl(string $listingKey, string $source, string $filename = 'cover.webp'): ?string
    {
        $listingKey = strtoupper(trim($listingKey));
        $source = trim($source);

        if ($listingKey === '' || $source === '') {
            return null;
        }

        $filename = $this->sanitizeFilename($filename);
        $targetPath = self::relativePath($listingKey, $filename);

        if (Storage::disk(self::DISK)->exists($targetPath)) {
            return $targetPath;
        }

        $localRelative = $this->resolveLocalRelativePath($source);
        if ($localRelative !== null) {
            if ($localRelative === $targetPath) {
                return $targetPath;
            }

            return $this->persistFromLocalRelativePath($listingKey, $localRelative, $filename);
        }

        if ($this->isRemoteUrl($source) && ! $this->isExternalTrebUrl($source)) {
            Log::warning('TrebImageStore: skipped non-TREB remote URL (use local disk instead)', [
                'listing_key' => $listingKey,
                'url' => $source,
            ]);

            return null;
        }

        if ($this->isRemoteUrl($source)) {
            return $this->persistFromRemoteUrl($listingKey, $source, $filename);
        }

        return null;
    }

    public function persistFromLocalRelativePath(string $listingKey, string $sourcePath, string $filename = 'cover.webp'): ?string
    {
        $listingKey = strtoupper(trim($listingKey));
        $relative = $this->normalizeRelativePath($sourcePath);

        if ($listingKey === '' || $relative === null) {
            return null;
        }

        $disk = Storage::disk(self::DISK);
        if (! $disk->exists($relative)) {
            return null;
        }

        $filename = $this->sanitizeFilename($filename);
        $targetPath = self::relativePath($listingKey, $filename);

        if ($disk->exists($targetPath)) {
            return $targetPath;
        }

        if ($relative === $targetPath) {
            return $targetPath;
        }

        try {
            $binary = $disk->get($relative);
            if ($binary === '') {
                return null;
            }

            if (str_ends_with(strtolower($relative), '.webp') && $filename === basename($relative)) {
                $disk->makeDirectory(dirname($targetPath));
                $disk->put($targetPath, $binary, 'public');

                return $targetPath;
            }

            $encoded = (string) $this->imageManager()
                ->read($binary)
                ->encode(new WebpEncoder(quality: 82));

            $disk->makeDirectory(dirname($targetPath));
            $disk->put($targetPath, $encoded, 'public');

            return $targetPath;
        } catch (\Throwable $e) {
            Log::warning('TrebImageStore: failed to convert local image', [
                'listing_key' => $listingKey,
                'source' => $relative,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    public function persistFromRemoteUrl(string $listingKey, string $remoteUrl, string $filename = 'cover.webp'): ?string
    {
        $listingKey = strtoupper(trim($listingKey));
        $remoteUrl = trim($remoteUrl);

        if ($listingKey === '' || ! $this->isRemoteUrl($remoteUrl)) {
            return null;
        }

        $localRelative = $this->resolveLocalRelativePath($remoteUrl);
        if ($localRelative !== null) {
            return $this->persistFromLocalRelativePath($listingKey, $localRelative, $filename);
        }

        if (! $this->isExternalTrebUrl($remoteUrl)) {
            Log::warning('TrebImageStore: refused HTTP fetch for same-origin storage URL', [
                'listing_key' => $listingKey,
                'url' => $remoteUrl,
            ]);

            return null;
        }

        $filename = $this->sanitizeFilename($filename);
        $relativePath = self::relativePath($listingKey, $filename);

        if (Storage::disk(self::DISK)->exists($relativePath)) {
            return $relativePath;
        }

        try {
            $response = Http::timeout(self::HTTP_TIMEOUT_SECONDS)
                ->connectTimeout(self::HTTP_CONNECT_TIMEOUT_SECONDS)
                ->retry(self::HTTP_RETRY_TIMES, self::HTTP_RETRY_SLEEP_MS, throw: false)
                ->withHeaders(['User-Agent' => 'SerikRealty/1.0'])
                ->get($remoteUrl);

            if (! $response->successful()) {
                Log::warning('TrebImageStore: remote image HTTP failed', [
                    'listing_key' => $listingKey,
                    'url' => $remoteUrl,
                    'status' => $response->status(),
                ]);

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
     * @param  array<int, string>  $sources  URLs or disk-relative paths
     * @return array<int, string>
     */
    public function persistGallery(string $listingKey, array $sources, int $max = 25): array
    {
        $stored = [];
        $index = 0;

        foreach (array_values(array_filter($sources)) as $source) {
            if ($index >= $max) {
                break;
            }

            $source = trim((string) $source);
            if ($source === '') {
                continue;
            }

            if ($this->isStoredWebp($source) && $this->storedWebpExists($source)) {
                $stored[] = ltrim(str_replace('\\', '/', $this->normalizeRelativePath($source) ?? $source), '/');
                $index++;

                continue;
            }

            $path = $this->persistFromUrl(
                $listingKey,
                $source,
                sprintf('%02d.webp', $index + 1)
            );

            if ($path) {
                $stored[] = $path;
                $index++;
            }
        }

        return array_values(array_unique($stored));
    }

    /**
     * Resolve a public /storage URL or relative path to an on-disk relative path.
     */
    public function resolveLocalRelativePath(string $value): ?string
    {
        $value = trim($value);
        if ($value === '') {
            return null;
        }

        if ($this->isRemoteUrl($value)) {
            if (! $this->isInternalStorageUrl($value)) {
                return null;
            }

            $path = (string) (parse_url($value, PHP_URL_PATH) ?? '');
            $value = $path !== '' ? $path : $value;
        }

        $relative = $this->normalizeRelativePath($value);
        if ($relative === null) {
            return null;
        }

        if (Storage::disk(self::DISK)->exists($relative)) {
            return $relative;
        }

        $publicPath = public_path('storage/' . $relative);
        if (is_file($publicPath) && is_readable($publicPath)) {
            return $relative;
        }

        return null;
    }

    private function isInternalStorageUrl(string $url): bool
    {
        if (! $this->isRemoteUrl($url)) {
            return false;
        }

        $path = (string) (parse_url($url, PHP_URL_PATH) ?? '');
        if (! str_contains($path, '/storage/')) {
            return false;
        }

        $host = strtolower((string) (parse_url($url, PHP_URL_HOST) ?? ''));
        if ($host === '') {
            return false;
        }

        $appHost = strtolower((string) (parse_url((string) config('app.url'), PHP_URL_HOST) ?? ''));

        return in_array($host, array_filter([
            $appHost,
            'serik.ca',
            'www.serik.ca',
            'localhost',
            '127.0.0.1',
        ]), true);
    }

    private function isExternalTrebUrl(string $url): bool
    {
        if (! $this->isRemoteUrl($url)) {
            return false;
        }

        if ($this->isInternalStorageUrl($url)) {
            return false;
        }

        $host = strtolower((string) (parse_url($url, PHP_URL_HOST) ?? ''));

        return str_contains($host, 'ampre.ca')
            || str_contains($host, 'trreb')
            || str_contains($url, 'trreb-image');
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

    private function normalizeRelativePath(string $path): ?string
    {
        $path = trim(str_replace('\\', '/', $path));
        if ($path === '') {
            return null;
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            if (preg_match('#/storage/(.+)$#i', $path, $matches)) {
                $path = $matches[1];
            } else {
                return null;
            }
        }

        $relative = ltrim($path, '/');
        if (str_starts_with($relative, 'storage/')) {
            $relative = substr($relative, strlen('storage/'));
        }

        return $relative !== '' ? $relative : null;
    }

    private function imageManager(): ImageManager
    {
        $driver = extension_loaded('imagick') ? ImagickDriver::class : GdDriver::class;

        return new ImageManager(new $driver());
    }
}
