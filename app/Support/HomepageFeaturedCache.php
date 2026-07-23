<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;

final class HomepageFeaturedCache
{
    private const VERSION_KEY = 'homepage_featured_props_cache_version';

    public static function version(): int
    {
        return (int) Cache::get(self::VERSION_KEY, 1);
    }

    public static function bump(): void
    {
        Cache::forever(self::VERSION_KEY, self::version() + 1);
    }
}
