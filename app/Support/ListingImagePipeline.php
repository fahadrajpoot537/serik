<?php

namespace App\Support;

use Botble\RealEstate\Models\Property;

/**
 * Image processing disabled — listings serve TREB CDN URLs directly from the database.
 * This class remains as a no-op stub so legacy callers and queued jobs exit safely.
 */
final class ListingImagePipeline
{
    public function needsProcessing(Property $property, bool $withGallery = true): bool
    {
        return false;
    }

    public function hasCompleteWebp(Property $property, bool $withGallery = true): bool
    {
        return true;
    }

    public function hasRemoteImages(Property $property): bool
    {
        return false;
    }

    public function remoteImagesChanged(Property $property): bool
    {
        return false;
    }

    public function persist(Property $property, bool $withGallery = true): ListingImagePersistResult
    {
        return ListingImagePersistResult::unchanged();
    }

    /**
     * @param  list<string>  $keepRelativePaths
     */
    public function cleanup(string $listingKey, array $keepRelativePaths): void
    {
    }

    public function queueAfterCommit(int $propertyId): void
    {
    }

    public function queueForRecovery(int $propertyId): void
    {
    }

    public function queueForLazyRequest(int $propertyId): void
    {
    }

    public function enqueue(int $propertyId): void
    {
    }
}
