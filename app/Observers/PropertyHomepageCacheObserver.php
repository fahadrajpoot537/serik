<?php

namespace App\Observers;

use App\Support\HomepageFeaturedCache;
use App\Support\RealEstateCountCache;
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

        \Illuminate\Support\Facades\Cache::forget('re_properties_min_square_v1');
        \Illuminate\Support\Facades\Cache::forget('re_properties_max_square_v1');
    }
}
