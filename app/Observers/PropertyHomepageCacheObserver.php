<?php

namespace App\Observers;

use App\Support\HomepageFeaturedCache;
use App\Support\HomepageFragmentCache;
use App\Support\RealEstateCountCache;
use App\Support\ShortcodeRenderCache;
use Botble\RealEstate\Models\Property;
use Illuminate\Support\Facades\Cache;

class PropertyHomepageCacheObserver
{
    /** Debounce data-cache invalidation during MLS bulk ingest. */
    private const BUMP_LOCK_SECONDS = 120;

    public function saved(Property $property): void
    {
        if (! $this->shouldInvalidate($property)) {
            return;
        }

        $this->invalidate();
    }

    public function deleted(Property $property): void
    {
        $this->invalidate();
    }

    private function shouldInvalidate(Property $property): bool
    {
        // Bulk MLS updates often touch listing_modified_at only — skip those.
        if (! $property->wasRecentlyCreated && $property->wasChanged()) {
            $relevant = [
                'status',
                'moderation_status',
                'is_featured',
                'featured_priority',
                'MlsStatus',
                'expire_date',
                'price',
                'images',
                'image_val',
                'location',
                'city_id',
                'PropertySubType',
            ];

            if (! $property->wasChanged($relevant)) {
                return false;
            }
        }

        return true;
    }

    private function invalidate(): void
    {
        if (! Cache::add('serik_homepage_cache_bump_lock', 1, self::BUMP_LOCK_SECONDS)) {
            return;
        }

        // Do NOT bump HomepageResponseCache here — MLS writes were causing
        // constant full-page cold misses (~13s). HTML cache expires on TTL /
        // CMS edits / explicit warm instead.
        HomepageFeaturedCache::bump();
        RealEstateCountCache::bump();
        ShortcodeRenderCache::bumpPropertyDependents();
        HomepageFragmentCache::bumpPropertyDependents();
    }
}
