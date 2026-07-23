<?php

namespace App\Support;

use Botble\RealEstate\Enums\ModerationStatusEnum;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

final class RealEstateCountCache
{
    private const CATEGORY_KEY = 're_category_property_counts_v1';

    private const AGENT_KEY = 're_agent_property_counts_v1';

    /**
     * @return Collection<int|string, int>
     */
    public static function categoryPropertyCounts(): Collection
    {
        return Cache::remember(self::CATEGORY_KEY, 3600, function (): Collection {
            return DB::table('re_property_categories')
                ->select('category_id', DB::raw('COUNT(*) as aggregate'))
                ->groupBy('category_id')
                ->pluck('aggregate', 'category_id')
                ->map(static fn ($count) => (int) $count);
        });
    }

    /**
     * @return Collection<int|string, int>
     */
    public static function agentPropertyCounts(): Collection
    {
        return Cache::remember(self::AGENT_KEY, 3600, function (): Collection {
            return DB::table('re_properties')
                ->select('author_id', DB::raw('COUNT(*) as aggregate'))
                ->where('moderation_status', ModerationStatusEnum::APPROVED)
                ->whereNotNull('author_id')
                ->groupBy('author_id')
                ->pluck('aggregate', 'author_id')
                ->map(static fn ($count) => (int) $count);
        });
    }

    public static function bump(): void
    {
        Cache::forget(self::CATEGORY_KEY);
        Cache::forget(self::AGENT_KEY);
    }
}
