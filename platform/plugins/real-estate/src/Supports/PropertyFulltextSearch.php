<?php

namespace Botble\RealEstate\Supports;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Hybrid address search over re_properties:
 *   1) InnoDB FULLTEXT MATCH(name, location) AGAINST (... NATURAL LANGUAGE MODE)
 *   2) LIKE fallback only when FULLTEXT returns zero rows (or index missing)
 *
 * Keeps previous LIKE semantics for street / free-text cases while avoiding
 * full-table scans on the hot path (~180k rows).
 */
class PropertyFulltextSearch
{
    public const HISTORY_COLUMNS = [
        'id',
        'external_id',
        'name',
        'location',
        'MlsStatus',
        'TransactionType',
        'price',
        'ClosePrice',
        'close_date',
        'purchase_contract_date',
        'listing_modified_at',
        'expire_date',
        'listing_contract_date',
        'created_at',
    ];

    public const SEARCH_COLUMNS = [
        'id',
        'external_id',
        'name',
        'location',
        'zip_code',
        'price',
        'number_bedroom',
        'number_bathroom',
        'PropertySubType',
        'type',
        'MlsStatus',
        'status',
        'TransactionType',
        'ParkingSpaces',
        'CoveredSpaces',
        'latitude',
        'longitude',
        'image_val',
        'updated_at',
    ];

    public static function fulltextAvailable(): bool
    {
        return (bool) Cache::remember('serik_ft_re_properties_address', 3600, function () {
            if (! Schema::hasTable('re_properties')) {
                return false;
            }

            try {
                return collect(DB::select(
                    "SHOW INDEX FROM `re_properties` WHERE Key_name = 'ft_re_properties_address'"
                ))->isNotEmpty();
            } catch (\Throwable) {
                return false;
            }
        });
    }

    /**
     * Sanitize a user/address phrase for NATURAL LANGUAGE MODE (bound as a param).
     * Strips control chars; keeps digits/letters/spaces for street addresses.
     */
    public static function sanitizePhrase(string $phrase): string
    {
        $phrase = trim(preg_replace('/[^\p{L}\p{N}\s\-\.\'\/]/u', ' ', $phrase) ?? '');
        $phrase = preg_replace('/\s+/', ' ', $phrase) ?? '';

        return mb_substr($phrase, 0, 200);
    }

    /**
     * Base approved-properties query with selected columns (never SELECT *).
     *
     * @param  list<string>  $columns
     */
    public static function baseQuery(array $columns): Builder
    {
        return DB::table('re_properties')
            ->select($columns)
            ->where('moderation_status', 'approved');
    }

    /**
     * Apply FULLTEXT first; if zero candidate rows possible (caller checks count)
     * they should call applyStreetLikeFallback / applyKeywordLikeFallback.
     */
    public static function applyFulltext(Builder $query, string $phrase): Builder
    {
        $phrase = self::sanitizePhrase($phrase);
        if ($phrase === '' || ! self::fulltextAvailable()) {
            return $query->whereRaw('0 = 1');
        }

        return $query->whereRaw(
            'MATCH(`name`, `location`) AGAINST(? IN NATURAL LANGUAGE MODE)',
            [$phrase]
        );
    }

    /**
     * @deprecated Never call — leading-% LIKE banned. Prefer Meilisearch then FULLTEXT.
     */
    public static function applyStreetLikeFallback(
        Builder $query,
        string $streetNumber,
        string $streetName
    ): Builder {
        return self::applyFulltext($query, trim($streetNumber . ' ' . $streetName));
    }

    /**
     * @deprecated Prefer Meilisearch / applyParsedAddressFulltext. No leading-% LIKE.
     */
    public static function applyParsedAddressLike(
        Builder $query,
        string $streetNumber,
        string $streetPart
    ): Builder {
        return self::applyParsedAddressFulltext($query, $streetNumber, $streetPart);
    }

    /**
     * Street address candidates: FULLTEXT only (Meili should be preferred by callers).
     * Never falls back to leading-% LIKE.
     *
     * @param  list<string>  $columns
     * @return \Illuminate\Support\Collection<int, object>
     */
    public static function searchStreetAddress(
        string $streetNumber,
        string $streetName,
        int $limit = 40,
        string $orderBy = 'created_at',
        string $direction = 'desc',
        ?array $columns = null
    ) {
        $columns ??= self::HISTORY_COLUMNS;
        $phrase = self::sanitizePhrase($streetNumber . ' ' . $streetName);
        $limit = max(1, min(100, $limit));

        if ($phrase === '' || ! self::fulltextAvailable()) {
            return collect();
        }

        return self::applyFulltext(self::baseQuery($columns), $phrase)
            ->orderBy($orderBy, $direction)
            ->limit($limit)
            ->get();
    }

    /**
     * Apply FULLTEXT only (caller runs LIKE fallback as a second query if empty).
     */
    public static function applyParsedAddressFulltext(
        Builder $query,
        string $streetNumber,
        string $streetPart
    ): Builder {
        $phrase = self::sanitizePhrase($streetNumber . ' ' . $streetPart);

        if ($phrase === '' || ! self::fulltextAvailable()) {
            return $query->whereRaw('0 = 1');
        }

        return self::applyFulltext($query, $phrase);
    }

    /**
     * Free-text FULLTEXT only (caller may fall back to LIKE if empty).
     */
    public static function applyKeywordFulltext(Builder $query, string $keyword): Builder
    {
        $phrase = self::sanitizePhrase($keyword);

        if ($phrase === '' || ! self::fulltextAvailable()) {
            return $query->whereRaw('0 = 1');
        }

        return self::applyFulltext($query, $phrase);
    }

    /**
     * @deprecated Leading-% LIKE on name/location is banned on re_properties.
     * Prefer Meilisearch (PropertySearchService) or applyKeywordFulltext.
     * Prefix-only matches on indexed external_id / zip_code remain allowed.
     */
    public static function applyKeywordLike(Builder $query, string $keyword): Builder
    {
        $keyword = trim($keyword);
        if ($keyword === '') {
            return $query->whereRaw('0 = 1');
        }

        return $query->where(function ($q) use ($keyword) {
            $q->where('external_id', 'like', $keyword . '%')
                ->orWhere('zip_code', 'like', $keyword . '%');
        });
    }
}
