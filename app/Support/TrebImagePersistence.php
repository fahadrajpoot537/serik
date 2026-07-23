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

        $remote = '';

        if ($imageVal !== '' && $this->store->isRemoteUrl($imageVal)) {
            $remote = $imageVal;
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

        $local = $this->store->persistFromRemoteUrl($listingKey, $remote, 'cover.webp');
        if ($local) {
            $property->image_val = $local;

            return true;
        }

        return false;
    }

    private function assignGallery(Property $property, string $listingKey): bool
    {
        $existing = is_array($property->images) ? $property->images : [];
        if (
            $existing !== []
            && collect($existing)->every(fn ($path) => $this->store->isStoredWebp(is_string($path) ? $path : null))
        ) {
            return false;
        }

        $remoteGallery = TrebPropertyHelper::getPropertyImages(
            $listingKey,
            $property->image_val,
            true
        );

        if ($remoteGallery === []) {
            return false;
        }

        $localGallery = $this->store->persistGallery($listingKey, $remoteGallery);
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
            return trim((string) ($resolveMediaUrl($listingKey) ?: ''));
        }

        $images = TrebPropertyHelper::getPropertyImages($listingKey, null, true);
        $first = $images[0] ?? '';

        if (! is_string($first) || $first === '') {
            return '';
        }

        if ($this->store->isRemoteUrl($first)) {
            return SerikMediaUrl::resolveTrebRemoteUrl($first) ?? $first;
        }

        return '';
    }
}
