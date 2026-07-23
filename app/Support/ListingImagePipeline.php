<?php

namespace App\Support;

use App\Jobs\PersistTrebImagesJob;
use Botble\RealEstate\Models\Property;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Theme\homzen\Supports\TrebPropertyHelper;

/**
 * Single source of truth for TREB listing image state, persistence, and queue dispatch.
 */
final class ListingImagePipeline
{
    private const MANIFEST_PREFIX = 'serik:image_manifest:';

    private const LAZY_DISPATCH_PREFIX = 'serik_gallery_job_';

    /** @var array<string, list<string>>|null */
    private ?array $remoteUrlCache = null;

    public function __construct(
        private readonly TrebImageStore $store,
        private readonly PropertySearchSync $searchSync,
    ) {
    }

    public function needsProcessing(Property $property, bool $withGallery = true): bool
    {
        $listingKey = $this->listingKey($property);
        if ($listingKey === '') {
            return false;
        }

        $manifest = $this->manifest($listingKey);
        if ($manifest !== null && ($manifest['no_images'] ?? false) === true && ! $this->hasRemoteImages($property)) {
            return false;
        }

        if ($this->hasRemoteImages($property)) {
            return true;
        }

        if (! $this->hasCompleteWebp($property, $withGallery)) {
            return true;
        }

        if ($this->manifest($listingKey) === null) {
            return true;
        }

        if ($withGallery && $this->remoteImagesChanged($property)) {
            return true;
        }

        return false;
    }

    public function hasCompleteWebp(Property $property, bool $withGallery = true): bool
    {
        $listingKey = $this->listingKey($property);
        if ($listingKey === '') {
            return false;
        }

        if ($this->hasRemoteImages($property)) {
            return false;
        }

        if (! $this->store->storedWebpExists($property->image_val) && ! $this->store->coverExistsOnDisk($listingKey)) {
            return false;
        }

        if (! $withGallery) {
            return true;
        }

        $diskGallery = $this->store->discoverGalleryPathsOnDisk($listingKey);
        $dbImages = is_array($property->images) ? $property->images : [];

        if ($diskGallery === [] && $dbImages === []) {
            return false;
        }

        foreach ($dbImages as $path) {
            if (! $this->store->storedWebpExists(is_string($path) ? $path : null)) {
                return false;
            }
        }

        $manifest = $this->manifest($listingKey);
        if ($manifest !== null && ($manifest['no_images'] ?? false) === true) {
            return ! $this->hasRemoteImages($property);
        }

        if ($manifest === null) {
            return false;
        }

        return count($diskGallery) >= max(1, count($dbImages));
    }

    public function hasRemoteImages(Property $property): bool
    {
        $imageVal = trim((string) ($property->image_val ?? ''));
        if ($this->store->isRemoteUrl($imageVal)) {
            return true;
        }

        $images = is_array($property->images) ? $property->images : [];
        foreach ($images as $path) {
            if ($this->store->isRemoteUrl(is_string($path) ? $path : null)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Compare stored manifest fingerprint with a single AMP fetch.
     */
    public function remoteImagesChanged(Property $property): bool
    {
        $listingKey = $this->listingKey($property);
        if ($listingKey === '') {
            return false;
        }

        $manifest = $this->manifest($listingKey);
        if ($manifest === null) {
            return true;
        }

        if (($manifest['no_images'] ?? false) === true) {
            return false;
        }

        $remoteUrls = $this->fetchRemoteUrls($listingKey, $property);

        return ($manifest['fingerprint'] ?? '') !== $this->fingerprint($remoteUrls);
    }

    /**
     * Download, convert, store, DB update, cleanup, cache invalidation, deferred search sync.
     */
    public function persist(Property $property, bool $withGallery = true): ListingImagePersistResult
    {
        $listingKey = $this->listingKey($property);
        if ($listingKey === '') {
            return ListingImagePersistResult::unchanged();
        }

        $remoteUrls = $this->fetchRemoteUrls($listingKey, $property);

        if ($remoteUrls === []) {
            $this->storeManifest($listingKey, [], noImages: true);
            $this->invalidateCaches($listingKey, (int) $property->id);

            return ListingImagePersistResult::noRemoteImages();
        }

        $fingerprint = $this->fingerprint($remoteUrls);
        $manifest = $this->manifest($listingKey);

        if (
            $manifest !== null
            && ($manifest['fingerprint'] ?? '') === $fingerprint
            && $this->hasCompleteWebp($property, $withGallery)
            && ! $this->hasRemoteImages($property)
        ) {
            return ListingImagePersistResult::unchanged();
        }

        $diskBefore = $this->store->discoverGalleryPathsOnDisk($listingKey);
        $newImageVal = trim((string) ($property->image_val ?? ''));
        $newImages = is_array($property->images) ? $property->images : [];
        $imagesChanged = false;

        $coverPath = $this->persistCover($listingKey, $property, $remoteUrls);
        if ($coverPath !== null) {
            $newImageVal = $coverPath;
            $imagesChanged = true;
        } elseif ($this->store->coverExistsOnDisk($listingKey)) {
            $newImageVal = TrebImageStore::relativePath($listingKey, 'cover.webp');
        }

        if ($withGallery) {
            $galleryPaths = $this->store->persistGallery($listingKey, $remoteUrls);
            if ($galleryPaths !== []) {
                $newImages = array_values(array_unique($galleryPaths));
                if ($newImageVal === '' || $this->store->isRemoteUrl($newImageVal)) {
                    $newImageVal = $newImages[0];
                }
                $imagesChanged = true;
            }
        }

        $keepPaths = $withGallery ? $newImages : array_filter([$newImageVal]);
        if ($keepPaths === [] && $coverPath !== null) {
            $keepPaths = [$coverPath];
        }

        $this->cleanup($listingKey, array_values($keepPaths));

        $dbChanged = $this->persistImageFields($property, $newImageVal, $newImages);

        if (! $dbChanged && ! $imagesChanged) {
            return ListingImagePersistResult::unchanged();
        }

        $this->storeManifest($listingKey, $remoteUrls);
        $this->invalidateCaches($listingKey, (int) $property->id);

        if ($dbChanged) {
            $this->syncSearchIndexIfNeeded($property);
        }

        return ListingImagePersistResult::persisted(
            array_values($keepPaths),
            $dbChanged || $imagesChanged
        );
    }

    /**
     * @param  list<string>  $keepRelativePaths
     */
    public function cleanup(string $listingKey, array $keepRelativePaths): void
    {
        $this->store->deleteOrphans($listingKey, $keepRelativePaths);
    }

    /**
     * Queue image job only after the surrounding DB transaction commits.
     */
    public function queueAfterCommit(int $propertyId): void
    {
        $dispatch = function () use ($propertyId): void {
            $property = Property::query()
                ->select(['id', 'external_id', 'image_val', 'images'])
                ->find($propertyId);

            if ($property === null || ! $this->needsProcessing($property)) {
                return;
            }

            $this->enqueue($propertyId);
        };

        if (DB::transactionLevel() > 0) {
            DB::afterCommit($dispatch);
        } else {
            $dispatch();
        }
    }

    /**
     * Recovery/backfill cron — no SSR throttle.
     */
    public function queueForRecovery(int $propertyId): void
    {
        $property = Property::query()
            ->select(['id', 'external_id', 'image_val', 'images'])
            ->find($propertyId);

        if ($property === null || ! $this->needsProcessing($property)) {
            return;
        }

        $this->enqueue($propertyId);
    }

    /**
     * User-facing lazy path (SSR/API) with cooldown.
     */
    public function queueForLazyRequest(int $propertyId): void
    {
        $cacheKey = self::LAZY_DISPATCH_PREFIX . $propertyId;

        if (Cache::has($cacheKey)) {
            return;
        }

        $property = Property::query()
            ->select(['id', 'external_id', 'image_val', 'images'])
            ->find($propertyId);

        if ($property === null || ! $this->needsProcessing($property)) {
            return;
        }

        Cache::put($cacheKey, 1, (int) config('serik.images.dispatch_cooldown_seconds', 3600));
        $this->enqueue($propertyId);
    }

    /**
     * @internal Only entry that dispatches PersistTrebImagesJob.
     */
    public function enqueue(int $propertyId): void
    {
        PersistTrebImagesJob::enqueue($propertyId);
    }

    /**
     * @return list<string>
     */
    private function fetchRemoteUrls(string $listingKey, Property $property): array
    {
        if (isset($this->remoteUrlCache[$listingKey])) {
            return $this->remoteUrlCache[$listingKey];
        }

        $urls = TrebPropertyHelper::getPropertyImagesForPersistence(
            $listingKey,
            $property->image_val,
            fresh: true
        );

        $urls = TrebMediaFilter::filterPhotoUrls(array_values(array_filter($urls)));
        $this->remoteUrlCache[$listingKey] = $urls;

        return $urls;
    }

    /**
     * @param  list<string>  $remoteUrls
     */
    private function persistCover(string $listingKey, Property $property, array $remoteUrls): ?string
    {
        $coverRelative = TrebImageStore::relativePath($listingKey, 'cover.webp');

        if ($this->store->storedWebpExists($property->image_val) && $this->store->coverExistsOnDisk($listingKey)) {
            return null;
        }

        if ($this->store->coverExistsOnDisk($listingKey)) {
            return $coverRelative;
        }

        $remote = (string) ($remoteUrls[0] ?? '');
        if ($remote === '') {
            return null;
        }

        $resolved = SerikMediaUrl::resolveTrebRemoteUrl($remote) ?? $remote;

        return $this->store->persistFromUrl($listingKey, $resolved, 'cover.webp');
    }

    /**
     * @param  list<string>  $images
     */
    private function persistImageFields(Property $property, string $imageVal, array $images): bool
    {
        $currentVal = trim((string) ($property->image_val ?? ''));
        $currentImages = is_array($property->images) ? $property->images : [];

        if ($currentVal === $imageVal && $currentImages === $images) {
            return false;
        }

        DB::transaction(function () use ($property, $imageVal, $images): void {
            $property->image_val = $imageVal !== '' ? $imageVal : $property->image_val;
            $property->images = $images !== [] ? $images : $property->images;
            $property->saveQuietly();
        });

        $property->refresh();

        return true;
    }

    private function syncSearchIndexIfNeeded(Property $property): void
    {
        $this->searchSync->schedule((int) $property->id);
    }

    private function invalidateCaches(string $listingKey, int $propertyId): void
    {
        $normalizedKey = strtoupper($listingKey);

        foreach ([
            'treb_images_v5_' . $normalizedKey,
            'treb_images_v4_' . $normalizedKey,
            'treb_images_v3_' . $normalizedKey,
            'treb_property_images_' . $normalizedKey,
            'serik:hydrated:' . $normalizedKey,
        ] as $key) {
            Cache::forget($key);
        }
    }

    /**
     * @param  list<string>  $remoteUrls
     */
    private function fingerprint(array $remoteUrls): string
    {
        return hash('sha256', json_encode(array_values($remoteUrls), JSON_UNESCAPED_SLASHES));
    }

    /**
     * @return array{fingerprint?: string, paths?: list<string>, no_images?: bool}|null
     */
    private function manifest(string $listingKey): ?array
    {
        $data = Cache::get(self::MANIFEST_PREFIX . strtoupper($listingKey));

        return is_array($data) ? $data : null;
    }

    /**
     * @param  list<string>  $remoteUrls
     */
    private function storeManifest(string $listingKey, array $remoteUrls, bool $noImages = false): void
    {
        Cache::put(self::MANIFEST_PREFIX . strtoupper($listingKey), [
            'fingerprint' => $noImages ? 'none' : $this->fingerprint($remoteUrls),
            'no_images' => $noImages,
            'count' => count($remoteUrls),
            'updated_at' => now()->toIso8601String(),
        ], 86400 * 30);
    }

    private function listingKey(Property $property): string
    {
        return strtoupper(trim((string) $property->external_id));
    }
}
