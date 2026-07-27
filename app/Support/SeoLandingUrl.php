<?php

namespace App\Support;

use Botble\Location\Models\City;
use Illuminate\Support\Str;

/**
 * SEO landing URLs: /ontario/{city}-{type}-for-{sale|lease}
 * Legacy /ontario/{type}-for-{sale|lease}-in-{city} still parses.
 */
final class SeoLandingUrl
{
    public static function prefix(): string
    {
        return 'ontario';
    }

    public static function url(string $seoSlug, array $query = []): string
    {
        $seoSlug = trim($seoSlug, '/');
        // Relative path so links always match the current host/port
        // (avoids APP_URL/localhost vs 127.0.0.1:8000 mismatches).
        $path = '/' . self::prefix() . '/' . $seoSlug;

        if ($query !== []) {
            $path .= '?' . http_build_query($query);
        }

        return $path;
    }

    public static function citySlug(?City $city): string
    {
        if (! $city) {
            return 'ontario';
        }

        return $city->slug ?: Str::slug($city->name);
    }

    public static function ontarioRealEstate(): string
    {
        return self::url('ontario-houses-for-sale');
    }

    public static function housesForSale(?City $city): string
    {
        return self::url(self::citySlug($city) . '-houses-for-sale');
    }

    public static function townhousesForSale(?City $city): string
    {
        return self::url(self::citySlug($city) . '-townhouses-for-sale');
    }

    public static function condosForSale(?City $city): string
    {
        return self::url(self::citySlug($city) . '-condos-for-sale');
    }

    public static function housesForLease(?City $city): string
    {
        return self::url(self::citySlug($city) . '-houses-for-lease');
    }

    public static function townhousesForLease(?City $city): string
    {
        return self::url(self::citySlug($city) . '-townhouses-for-lease');
    }

    public static function apartmentsForLease(?City $city, array $query = []): string
    {
        return self::url(self::citySlug($city) . '-condos-for-lease', $query);
    }

    public static function studioApartmentsForLease(?City $city): string
    {
        return self::apartmentsForLease($city, ['k' => 'studio']);
    }

    public static function bedroomApartmentsForLease(?City $city, int $bedrooms): string
    {
        return self::apartmentsForLease($city, ['bedroom' => $bedrooms]);
    }

    public static function openHouses(?City $city): string
    {
        return self::url(self::citySlug($city) . '-houses-for-sale', ['open_house' => 1]);
    }

    public static function soldHomes(?City $city): string
    {
        return self::url(self::citySlug($city) . '-houses-for-sale', ['status' => 'sold']);
    }

    public static function community(?City $city, string $communityName, array $query = []): string
    {
        return self::url(
            self::citySlug($city) . '-houses-for-sale',
            array_merge(['community' => $communityName], $query)
        );
    }

    public static function communityTownhouses(?City $city, string $communityName): string
    {
        return self::url(self::citySlug($city) . '-townhouses-for-sale', ['community' => $communityName]);
    }

    public static function communityCondos(?City $city, string $communityName): string
    {
        return self::url(self::citySlug($city) . '-condos-for-sale', ['community' => $communityName]);
    }

    public static function communityHousesForLease(?City $city, string $communityName): string
    {
        return self::url(self::citySlug($city) . '-houses-for-lease', ['community' => $communityName]);
    }

    public static function communityTownhousesForLease(?City $city, string $communityName): string
    {
        return self::url(self::citySlug($city) . '-townhouses-for-lease', ['community' => $communityName]);
    }

    public static function communityApartmentsForLease(?City $city, string $communityName, array $query = []): string
    {
        return self::url(
            self::citySlug($city) . '-condos-for-lease',
            array_merge(['community' => $communityName], $query)
        );
    }

    public static function city(?City $city): string
    {
        return self::housesForSale($city);
    }

    /**
     * Extract city slug from /ontario/{seo} path segment.
     * Supports toronto-houses-for-sale and legacy houses-for-sale-in-toronto.
     */
    public static function parseCitySlugFromSeo(string $seoSlug): ?string
    {
        $seoSlug = trim(strtolower($seoSlug), '/');

        $types = 'houses|house|townhouses|townhouse|condos|condo|apartments|apartment'
            . '|detached-houses|semi-detached-houses|condo-townhouses|freehold-townhouses'
            . '|condo-apartments|condos-apartments|detached|semi-detached';

        if (preg_match('#^(.+)-(' . $types . ')-for-(?:sale|lease)$#', $seoSlug, $matches)) {
            $city = trim($matches[1]);

            return $city !== '' ? $city : null;
        }

        if (preg_match('#-for-(?:sale|lease)-in-(.+)$#', $seoSlug, $matches)) {
            $city = trim($matches[1]);

            return $city !== '' ? $city : null;
        }

        return null;
    }
}
