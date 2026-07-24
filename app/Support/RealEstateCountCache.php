<?php

namespace App\Support;

use Botble\RealEstate\Enums\ModerationStatusEnum;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

final class RealEstateCountCache
{
    private const VERSION_KEY = 're_count_cache_version_v1';

    private const CATEGORY_KEY = 're_category_property_counts_v1';

    private const AGENT_KEY = 're_agent_property_counts_v1';

    private const SUBTYPE_KEY = 're_property_subtype_counts_v1';

    private const MIN_SQUARE_KEY = 're_properties_min_square_v1';

    private const MAX_SQUARE_KEY = 're_properties_max_square_v1';

    public static function version(): int
    {
        return (int) Cache::get(self::VERSION_KEY, 1);
    }

    /**
     * @return Collection<int|string, int>
     */
    public static function categoryPropertyCounts(): Collection
    {
        $version = self::version();

        return Cache::remember(self::CATEGORY_KEY.':'.$version, 3600, function (): Collection {
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
        $version = self::version();

        return Cache::remember(self::AGENT_KEY.':'.$version, 3600, function (): Collection {
            return DB::table('re_properties')
                ->select('author_id', DB::raw('COUNT(*) as aggregate'))
                ->where('moderation_status', ModerationStatusEnum::APPROVED)
                ->whereNotNull('author_id')
                ->groupBy('author_id')
                ->pluck('aggregate', 'author_id')
                ->map(static fn ($count) => (int) $count);
        });
    }

    /**
     * Property counts grouped by PropertySubType for homepage category cards.
     *
     * @param  list<string>  $allowedTypes
     * @return list<object{PropertySubType: string, total: int}>
     */
    public static function propertySubTypeCounts(array $allowedTypes): array
    {
        if ($allowedTypes === []) {
            return [];
        }

        $version = self::version();
        $typesKey = md5(implode('|', $allowedTypes));

        return Cache::remember(self::SUBTYPE_KEY.':'.$version.':'.$typesKey, 3600, function () use ($allowedTypes): array {
            $order = implode(',', array_map(
                static fn (string $type): string => "'" . str_replace("'", "''", $type) . "'",
                $allowedTypes
            ));

            return DB::table('re_properties')
                ->select('PropertySubType', DB::raw('COUNT(*) as total'))
                ->whereIn('PropertySubType', $allowedTypes)
                ->groupBy('PropertySubType')
                ->orderByRaw("FIELD(PropertySubType, {$order})")
                ->get()
                ->all();
        });
    }

    public static function minSquare(): int
    {
        $version = self::version();

        return (int) Cache::remember(self::MIN_SQUARE_KEY.':'.$version, 3600, function (): int {
            $square = DB::table('re_properties')
                ->where('moderation_status', ModerationStatusEnum::APPROVED)
                ->whereNotNull('square')
                ->where('square', '>', 0)
                ->min('square');

            return $square ? (int) ceil((float) $square) : 0;
        });
    }

    public static function maxSquare(): int
    {
        $version = self::version();

        return (int) Cache::remember(self::MAX_SQUARE_KEY.':'.$version, 3600, function (): int {
            $square = DB::table('re_properties')
                ->where('moderation_status', ModerationStatusEnum::APPROVED)
                ->whereNotNull('square')
                ->where('square', '>', 0)
                ->max('square');

            return $square ? (int) ceil((float) $square) : 0;
        });
    }

    public static function bump(): void
    {
        Cache::forever(self::VERSION_KEY, self::version() + 1);
    }
}
