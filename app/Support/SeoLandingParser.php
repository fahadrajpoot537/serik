<?php

namespace App\Support;

use Botble\Location\Models\City;
use Illuminate\Support\Str;

/**
 * Parse /ontario/{seo-slug} into /properties filter parameters.
 *
 * Preferred:  toronto-houses-for-sale
 * Legacy:     houses-for-sale-in-toronto
 */
final class SeoLandingParser
{
    private const TYPE_PATTERN = 'houses|house|townhouses|townhouse|condos|condo|apartments|apartment'
        . '|detached-houses|semi-detached-houses|condo-townhouses|freehold-townhouses'
        . '|condo-apartments|condos-apartments|detached|semi-detached';

    /**
     * @return array<string, mixed>
     */
    public static function toFilterParams(string $seoSlug): array
    {
        $seoSlug = trim(strtolower($seoSlug), '/');
        $filters = [];

        $typePart = null;
        $transaction = null;
        $citySlug = '';

        // Preferred: {city}-{type}-for-{sale|lease}
        if (preg_match('#^(.+)-(' . self::TYPE_PATTERN . ')-for-(sale|lease)$#', $seoSlug, $matches)) {
            $citySlug = trim($matches[1]);
            $typePart = $matches[2];
            $transaction = $matches[3];
        // Legacy: {type}-for-{sale|lease}-in-{city}
        } elseif (preg_match('#^(' . self::TYPE_PATTERN . ')-for-(sale|lease)(?:-in-(.+))?$#', $seoSlug, $matches)) {
            $typePart = $matches[1];
            $transaction = $matches[2];
            $citySlug = trim((string) ($matches[3] ?? ''));
        // Legacy catch-all (compound types before -for-sale-in-)
        } elseif (preg_match('#^(.*)-for-(sale|lease)(?:-in-(.+))?$#', $seoSlug, $matches)) {
            $typePart = $matches[1];
            $transaction = $matches[2];
            $citySlug = trim((string) ($matches[3] ?? ''));
        } else {
            return $filters;
        }

        $filters['type'] = $transaction === 'lease' ? 'rent' : 'sale';

        if ($citySlug !== '' && $citySlug !== 'ontario') {
            // Only pass `location` (city name). Do NOT set city_id/city —
            // MLS properties keep city_id=0, so those filters always return empty.
            $city = City::query()
                ->where('slug', $citySlug)
                ->where('is_active', true)
                ->first(['id', 'name', 'slug']);

            $filters['location'] = $city
                ? (string) $city->name
                : self::formatCityLabel($citySlug);
        }

        $homeType = self::resolveHomeType((string) $typePart);
        if ($homeType !== null) {
            $filters['home_types'] = [$homeType];
        }

        return $filters;
    }

    private static function resolveHomeType(string $typePart): ?string
    {
        $map = [
            'houses' => 'house',
            'house' => 'house',
            'detached-houses' => 'house',
            'detached' => 'house',
            'semi-detached-houses' => 'house',
            'semi-detached' => 'house',
            'townhouses' => 'townhouse',
            'townhouse' => 'townhouse',
            'freehold-townhouses' => 'townhouse',
            'condo-townhouses' => 'townhouse',
            'condos' => 'condo',
            'condo' => 'condo',
            'condo-apartments' => 'condo',
            'condos-apartments' => 'condo',
            'apartments' => 'condo',
            'apartment' => 'condo',
        ];

        if (isset($map[$typePart])) {
            return $map[$typePart];
        }

        foreach ($map as $key => $value) {
            if (str_ends_with($typePart, '-' . $key) || str_starts_with($typePart, $key . '-')) {
                return $value;
            }
        }

        return null;
    }

    private static function formatCityLabel(string $slug): string
    {
        return ucwords(str_replace('-', ' ', $slug));
    }

    public static function parseCitySlugFromSeo(string $seoSlug): ?string
    {
        return SeoLandingUrl::parseCitySlugFromSeo($seoSlug);
    }
}
