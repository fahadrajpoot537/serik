<?php

namespace App\Support;

use Botble\RealEstate\Models\Property;

/**
 * @deprecated Use ListingImagePipeline directly. Thin delegate for legacy callers.
 */
final class TrebImagePersistence
{
    public function __construct(
        private readonly ListingImagePipeline $pipeline,
    ) {
    }

    public function persistForProperty(Property $property, bool $withGallery = false, ?callable $resolveMediaUrl = null): bool
    {
        unset($resolveMediaUrl);

        return $this->pipeline->persist($property, $withGallery)->changed;
    }
}
