<?php

namespace Theme\homzen\Supports;

use Botble\RealEstate\Services\PropertySearchService;
use Illuminate\Database\Eloquent\Builder;

class VisitorCityHelper
{
    public static function get(): ?string
    {
        $city = request()->cookie('serik_visitor_city');

        if (! $city && ! empty($_COOKIE['serik_visitor_city'])) {
            $city = $_COOKIE['serik_visitor_city'];
        }

        if (! $city) {
            return null;
        }

        $city = trim(urldecode((string) $city));

        return $city !== '' ? $city : null;
    }

    public static function applyCityScope(Builder $query): void
    {
        $city = self::get();

        if (! $city) {
            return;
        }

        // Meili IDs → whereIn. Empty/unavailable = skip city (never blank homepage).
        app(PropertySearchService::class)->constrainQueryToCity($query, $city, 3000, false);
    }
}
