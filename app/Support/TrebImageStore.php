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

    private const HTTP_CONNECT_TIMEOUT_SECONDS = 3;

    private const HTTP_RETRY_TIMES = 2;

    private const HTTP_RETRY_SLEEP_MS = 500;

    /** @var list<string> */
    private const ALLOWED_IMAGE_MIMES = [
        'image/jpeg',
        'image/png',
        'image/webp',
    ];

    /** @var list<string> */
    private const BLOCKED_ASSET_MIMES = [
        'application/pdf',
        'text/html',
    ];

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
     * @return array<int, string> Relative disk paths (cover.webp, 01.webp, …)
     */
    public function discoverGalleryPathsOnDisk(string $listingKey): array
    {
        $listingKey = strtoupper(trim($listingKey));
        if ($listingKey === '') {
            return [];
        }

        $paths = [];
        $cover = self::relativePath($listingKey, 'cover.webp');
        if (Storage::disk(self::DISK)->exists($cover)) {
            $paths[] = $cover;
        }

        for ($i = 1; $i <= 25; $i++) {
            $relative = self::relativePath($listingKey, sprintf('%02d.webp', $i));
            if (! Storage::disk(self::DISK)->exists($relative)) {
                if ($i === 1 && $paths === []) {
                    continue;
                }

                break;
            }

            $paths[] = $relative;
        }

        return array_values(array_unique($paths));
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
            Log::debug('TrebImageStore: skipped same-origin URL (file not on disk)', [
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
                $this->logSkippedNonImageAsset($listingKey, $relative, 'empty');

                return null;
            }

            if (str_ends_with(strtolower($relative), '.webp') && $filename === basename($relative)) {
                $disk->makeDirectory(dirname($targetPath));
                $disk->put($targetPath, $binary, 'public');

                return $targetPath;
            }

            if (! $this->isProcessableImageBinary($binary, $listingKey, $relative)) {
                return null;
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
            Log::debug('TrebImageStore: refused HTTP fetch for same-origin storage URL', [
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
            $contentType = $response->header('Content-Type');

            if (! $this->isProcessableImageBinary($binary, $listingKey, $remoteUrl, $contentType)) {
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

            if ($this->isRemoteUrl($source) && $this->isInternalStorageUrl($source)) {
                $local = $this->resolveLocalRelativePath($source);
                if ($local !== null) {
                    $stored[] = $local;
                    $index++;
                }

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

    public function isInternalStorageUrl(string $url): bool
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

    private function isProcessableImageBinary(
        string $binary,
        string $listingKey,
        string $url,
        ?string $contentTypeHeader = null,
    ): bool {
        if ($binary === '') {
            $this->logSkippedNonImageAsset($listingKey, $url, 'empty');

            return false;
        }

        $headerMime = $this->normalizeMimeType($this->parseContentTypeHeader($contentTypeHeader));
        if ($headerMime !== null && $this->isBlockedAssetMime($headerMime)) {
            $this->logSkippedNonImageAsset($listingKey, $url, $headerMime);

            return false;
        }

        $detectedMime = $this->detectBinaryMimeType($binary);
        if ($detectedMime !== null && $this->isBlockedAssetMime($detectedMime)) {
            $this->logSkippedNonImageAsset($listingKey, $url, $detectedMime);

            return false;
        }

        $imageMime = $this->resolveImageMimeFromBinary($binary);
        if ($imageMime === null || ! $this->isAllowedImageMime($imageMime)) {
            $this->logSkippedNonImageAsset(
                $listingKey,
                $url,
                $imageMime ?? $headerMime ?? $detectedMime ?? 'unknown'
            );

            return false;
        }

        return true;
    }

    private function resolveImageMimeFromBinary(string $binary): ?string
    {
        $imageInfo = @getimagesizefromstring($binary);
        if (is_array($imageInfo) && isset($imageInfo['mime']) && is_string($imageInfo['mime'])) {
            return $this->normalizeMimeType($imageInfo['mime']);
        }

        return $this->detectBinaryMimeType($binary);
    }

    private function detectBinaryMimeType(string $binary): ?string
    {
        if ($binary === '') {
            return null;
        }

        if (function_exists('finfo_buffer')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            if ($finfo !== false) {
                $mime = finfo_buffer($finfo, $binary);
                finfo_close($finfo);

                if (is_string($mime) && $mime !== '') {
                    return $this->normalizeMimeType($mime);
                }
            }
        }

        if (str_starts_with($binary, '%PDF')) {
            return 'application/pdf';
        }

        $trimmed = ltrim($binary);
        if (
            str_starts_with($trimmed, '<!DOCTYPE html')
            || str_starts_with($trimmed, '<html')
            || str_starts_with($trimmed, '<HTML')
        ) {
            return 'text/html';
        }

        return null;
    }

    private function parseContentTypeHeader(?string $contentType): ?string
    {
        $contentType = trim((string) $contentType);
        if ($contentType === '') {
            return null;
        }

        $parts = explode(';', $contentType, 2);

        return trim($parts[0]);
    }

    private function normalizeMimeType(?string $mime): ?string
    {
        $mime = strtolower(trim((string) $mime));

        if ($mime === 'image/jpg') {
            return 'image/jpeg';
        }

        return $mime !== '' ? $mime : null;
    }

    private function isAllowedImageMime(?string $mime): bool
    {
        $mime = $this->normalizeMimeType($mime);

        return $mime !== null && in_array($mime, self::ALLOWED_IMAGE_MIMES, true);
    }

    private function isBlockedAssetMime(?string $mime): bool
    {
        $mime = $this->normalizeMimeType($mime);

        return $mime !== null && in_array($mime, self::BLOCKED_ASSET_MIMES, true);
    }

    private function logSkippedNonImageAsset(string $listingKey, string $url, string $contentType): void
    {
        Log::warning('TrebImageStore: skipped non-image asset', [
            'listing_key' => $listingKey,
            'url' => $url,
            'content_type' => $contentType,
        ]);
    }
}
