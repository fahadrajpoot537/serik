<?php

namespace App\Support;

use Botble\RealEstate\Models\Property;
use Theme\homzen\Supports\TrebPropertyHelper;

/**
 * Persist TREB cover/gallery images as local WebP.
 * Lives in app/Support so queue jobs do not depend on API PropertyController methods.
 */
final class TrebImagePersistence
{
    public function __construct(
        private readonly TrebImageStore $store,
    ) {
    }

    public function persistForProperty(Property $property, bool $withGallery = false, ?callable $resolveMediaUrl = null): bool
    {
        $listingKey = strtoupper(trim((string) $property->external_id));
        if ($listingKey === '') {
            return false;
        }

        $changed = $this->assignCover($property, $listingKey, $resolveMediaUrl);
        if ($withGallery) {
            $changed = $this->assignGallery($property, $listingKey) || $changed;
        }

        if ($changed) {
            $property->saveQuietly();
        }

        return $changed;
    }

    private function assignCover(Property $property, string $listingKey, ?callable $resolveMediaUrl): bool
    {
        $imageVal = trim((string) ($property->image_val ?? ''));

        if ($this->store->storedWebpExists($imageVal)) {
            return false;
        }

        $coverRelative = TrebImageStore::relativePath($listingKey, 'cover.webp');
        if ($this->store->coverExistsOnDisk($listingKey)) {
            $property->image_val = $coverRelative;

            return true;
        }

        $remote = '';

        if ($imageVal !== '' && $this->store->isRemoteUrl($imageVal)) {
            $localFromUrl = $this->store->resolveLocalRelativePath($imageVal);
            if ($localFromUrl !== null) {
                $local = $this->store->persistFromLocalRelativePath($listingKey, $localFromUrl, 'cover.webp');
                if ($local) {
                    $property->image_val = $local;

                    return true;
                }
            }

            if ($this->store->isRemoteUrl($imageVal)) {
                $remote = $imageVal;
            }
        }

        if ($remote === '' && $imageVal !== '' && ! $this->store->isRemoteUrl($imageVal)) {
            if (preg_match('/^L3RycmVi/i', $imageVal) || str_contains($imageVal, '/rs:') || str_contains($imageVal, 'rs:fit')) {
                $remote = SerikMediaUrl::resolveTrebRemoteUrl($imageVal) ?? '';
            } elseif (! str_ends_with(strtolower($imageVal), '.webp')) {
                $local = $this->store->persistFromLocalRelativePath($listingKey, $imageVal, 'cover.webp');
                if ($local) {
                    $property->image_val = $local;

                    return true;
                }
            }
        }

        if ($remote !== '' && (str_contains($remote, '/rs:') || str_contains($remote, 'rs:fit') || preg_match('/^L3RycmVi/i', $remote))) {
            $remote = SerikMediaUrl::resolveTrebRemoteUrl($remote) ?? '';
        }

        if ($remote === '' || (str_contains($remote, 'serik.ca') && str_contains($remote, 'rs:'))) {
            $remote = $this->resolveCoverMediaUrl($listingKey, $resolveMediaUrl);
        }

        if ($remote === '') {
            return false;
        }

        $localFromUrl = $this->store->resolveLocalRelativePath($remote);
        if ($localFromUrl !== null) {
            $local = $this->store->persistFromLocalRelativePath($listingKey, $localFromUrl, 'cover.webp');
        } else {
            $local = $this->store->persistFromUrl($listingKey, $remote, 'cover.webp');
        }

        if ($local) {
            $property->image_val = $local;

            return true;
        }

        return false;
    }

    private function assignGallery(Property $property, string $listingKey): bool
    {
        $existing = is_array($property->images) ? $property->images : [];
        $diskGallery = $this->store->discoverGalleryPathsOnDisk($listingKey);

        $remoteGallery = TrebPropertyHelper::getPropertyImagesForPersistence(
            $listingKey,
            $property->image_val
        );

        $existingValid = array_values(array_filter(
            $existing,
            fn ($path) => is_string($path) && $this->store->storedWebpExists($path)
        ));

        if ($remoteGallery !== [] && count($existingValid) >= count($remoteGallery)) {
            return false;
        }

        if ($remoteGallery === [] && $diskGallery !== [] && count($existingValid) >= count($diskGallery)) {
            return false;
        }

        $persisted = $remoteGallery !== []
            ? $this->store->persistGallery($listingKey, $remoteGallery)
            : [];

        $localGallery = array_values(array_unique(array_merge($diskGallery, $persisted)));

        if ($localGallery === []) {
            return false;
        }

        $property->images = $localGallery;

        if (empty($property->image_val) || $this->store->isRemoteUrl($property->image_val)) {
            $property->image_val = $localGallery[0];
        }

        return true;
    }

    private function resolveCoverMediaUrl(string $listingKey, ?callable $resolveMediaUrl): string
    {
        if ($resolveMediaUrl) {
            $url = trim((string) ($resolveMediaUrl($listingKey) ?: ''));
            if ($url !== '' && $this->store->isInternalStorageUrl($url)) {
                return '';
            }

            return $url;
        }

        $images = TrebPropertyHelper::getPropertyImagesForPersistence($listingKey, null);
        foreach ($images as $image) {
            if (! is_string($image) || $image === '') {
                continue;
            }

            return SerikMediaUrl::resolveTrebRemoteUrl($image) ?? $image;
        }

        return '';
    }
}
