<?php

namespace App\Observers;

use App\Support\HomepageFeaturedCache;
use App\Support\RealEstateCountCache;
use App\Support\ShortcodeRenderCache;
use Botble\RealEstate\Models\Property;

class PropertyHomepageCacheObserver
{
    public function saved(Property $property): void
    {
        $this->invalidate();
    }

    public function deleted(Property $property): void
    {
        $this->invalidate();
    }

    private function invalidate(): void
    {
        HomepageFeaturedCache::bump();
        RealEstateCountCache::bump();
        ShortcodeRenderCache::bumpPropertyDependents();
    }
}
