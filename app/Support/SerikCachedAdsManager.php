<?php

namespace App\Support;

use Botble\Ads\Models\Ads;
use Botble\Ads\Supports\AdsManager;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * Caches the ads table read (replaces full-table SELECT on every page).
 */
final class SerikCachedAdsManager extends AdsManager
{
    private const CACHE_KEY = 'serik_ads_collection_v1';

    private const TTL_SECONDS = 300;

    protected function read(array $with): Collection
    {
        $withKey = md5(serialize($with));

        return Cache::remember(self::CACHE_KEY . ':' . $withKey, self::TTL_SECONDS, function () use ($with): Collection {
            return parent::read($with);
        });
    }

    public static function bust(): void
    {
        Cache::forget(self::CACHE_KEY . ':' . md5(serialize(['metadata'])));
    }
}
