<?php

namespace App\Support;

use Botble\Base\Facades\BaseHelper;
use Botble\Theme\Facades\Theme;
use Illuminate\Http\Request;

final class PageH1
{
    /**
     * CMS utility page slugs => SEO H1 text.
     *
     * @var array<string, string>
     */
    private const UTILITY_H1 = [
        'appointment-scheduler' => 'Schedule an Appointment with Serik Realty',
        'blogs' => 'Serik Realty Real Estate Blog',
        'blog' => 'Serik Realty Real Estate Blog',
        'cash-back-calculator' => 'Real Estate Cash Back Calculator',
        'contact-us' => 'Contact Serik Realty',
        'cookie-policy' => 'Cookie Policy',
        'map' => 'Search Homes for Sale in Ontario',
        'mortgage-calculator' => 'Ontario Mortgage Calculator',
        'our-services' => 'Our Real Estate Services',
        'term-and-conditions' => 'Terms & Conditions',
        'terms-conditions' => 'Terms & Conditions',
        'tips-for-home-selling' => 'Tips for Selling Your Home in Ontario',
        'categories' => 'Categories',
        'faqs' => 'Frequently Asked Questions',
        'properties' => 'Properties for Sale in Ontario',
        'agents' => 'Our Real Estate Agents',
    ];

    /**
     * Pages whose primary H1 is rendered inside page content shortcodes.
     *
     * @var array<int, string>
     */
    private const CONTENT_H1_SLUGS = [
        'faqs',
    ];

    /**
     * @var array<string, string>
     */
    private const PROPERTY_TYPE_LABELS = [
        'semi-detached-houses' => 'Semi-Detached Houses',
        'detached-houses' => 'Detached Houses',
        'townhouses' => 'Townhouses',
        'condos' => 'Condos',
        'houses' => 'Houses',
    ];

    /**
     * @var array<string, string>
     */
    private const CITY_LABELS = [
        'kwc' => 'Kitchener-Waterloo-Cambridge',
        'kitchener' => 'Kitchener',
    ];

    public static function configureForPage(object $page): void
    {
        if (BaseHelper::isHomepage($page->id)) {
            Theme::set('pageH1ProvidedByContent', true);

            return;
        }

        $slug = trim((string) $page->slug, '/');

        if (in_array($slug, self::CONTENT_H1_SLUGS, true)) {
            Theme::set('pageH1ProvidedByContent', true);
            Theme::set('breadcrumbStyle', 'without-title');

            return;
        }

        if ($h1 = self::utilityH1ForSlug($slug)) {
            Theme::set('pageH1', $h1);
        }
    }

    public static function resolve(?Request $request = null): ?string
    {
        $request ??= request();

        if (Theme::get('pageH1ProvidedByContent')) {
            return null;
        }

        if ($explicit = Theme::get('pageH1')) {
            return trim((string) $explicit) ?: null;
        }

        if ($mapH1 = self::resolveMap($request)) {
            return $mapH1;
        }

        $path = trim($request->path(), '/');
        $slug = strtok($path, '/') ?: '';

        if ($utility = self::utilityH1ForSlug($slug)) {
            return $utility;
        }

        if ($title = Theme::get('pageTitle')) {
            return trim((string) $title) ?: null;
        }

        return null;
    }

    public static function resolveMap(?Request $request = null): ?string
    {
        $request ??= request();
        $path = trim(strtolower($request->path()), '/');

        if (preg_match('#^on/.+/map/.+#', $path)) {
            return null;
        }

        if ($request->is('map') || $request->is('on/map')) {
            $seo = trim((string) $request->input('seo', ''));

            return $seo !== ''
                ? (self::fromMapSeoSlug($seo) ?? 'Search Homes for Sale in Ontario')
                : 'Search Homes for Sale in Ontario';
        }

        if (preg_match('#^on/(.+)/map$#', $path, $matches)) {
            return self::fromMapSeoSlug($matches[1]);
        }

        if (preg_match('#^ontario/(.+)$#', $path, $matches)) {
            return self::fromMapSeoSlug($matches[1]);
        }

        if (preg_match('#^(.+)-for-(sale|lease)(?:-in-.+)?$#', $path)) {
            return self::fromMapSeoSlug($path);
        }

        return null;
    }

    public static function fromMapSeoSlug(string $slug): ?string
    {
        $slug = trim(strtolower($slug), '/');

        if ($slug === '') {
            return null;
        }

        $types = 'houses|house|townhouses|townhouse|condos|condo|apartments|apartment'
            . '|detached-houses|semi-detached-houses|condo-townhouses|freehold-townhouses'
            . '|condo-apartments|condos-apartments|detached|semi-detached';

        // Preferred: toronto-houses-for-sale
        if (preg_match('#^(.+)-(' . $types . ')-for-(sale|lease)$#', $slug, $matches)) {
            return self::formatMapH1($matches[2], $matches[1], $matches[3] === 'lease' ? 'Lease' : 'Sale');
        }

        if (preg_match('#^(.+)-for-sale-in-(.+)$#', $slug, $matches)) {
            return self::formatMapH1($matches[1], $matches[2]);
        }

        if (preg_match('#^(.+)-for-lease-in-(.+)$#', $slug, $matches)) {
            return self::formatMapH1($matches[1], $matches[2], 'Lease');
        }

        if (preg_match('#^(.+)-for-sale$#', $slug, $matches)) {
            return self::formatMapH1($matches[1], 'ontario');
        }

        if (preg_match('#^(.+)-for-lease$#', $slug, $matches)) {
            return self::formatMapH1($matches[1], 'ontario', 'Lease');
        }

        return null;
    }

    public static function utilityH1ForSlug(string $slug): ?string
    {
        $slug = trim(strtolower($slug), '/');

        return self::UTILITY_H1[$slug] ?? null;
    }

    private static function formatMapH1(string $typeSlug, string $citySlug, string $listingType = 'Sale'): string
    {
        $type = self::formatPropertyTypeSlug($typeSlug);
        $city = self::formatCitySlug($citySlug);

        // Consistent pattern: "{Place} {Type} for {Sale|Lease}"
        return "{$city} {$type} for {$listingType}";
    }

    /**
     * Ontario SEO landing / community listing H1 (and matching page title).
     */
    public static function ontarioListingH1(?Request $request = null): string
    {
        $request ??= request();
        $seo = trim((string) $request->route('seo', ''), '/');
        $base = $seo !== '' ? (self::fromMapSeoSlug($seo) ?? 'Ontario Houses for Sale') : 'Ontario Houses for Sale';
        $community = trim((string) $request->input('community', ''));
        $isSold = $request->input('status') === 'sold' || $request->boolean('sold');
        $isLease = in_array(strtolower((string) $request->input('type', '')), ['rent', 'lease'], true)
            || str_contains(strtolower($seo), '-for-lease');

        $typeLabel = 'Houses';
        if (preg_match('#-(townhouses|townhouse)-for-#', $seo)) {
            $typeLabel = 'Townhouses';
        } elseif (preg_match('#-(condos|condo|apartments|apartment)-for-#', $seo)) {
            $typeLabel = 'Condos';
        } elseif (preg_match('#-(detached-houses|detached)-for-#', $seo)) {
            $typeLabel = 'Detached Houses';
        } elseif (preg_match('#-(semi-detached-houses|semi-detached)-for-#', $seo)) {
            $typeLabel = 'Semi-Detached Houses';
        }

        if ($community !== '') {
            if ($isSold) {
                return "{$community} Sold {$typeLabel}";
            }

            return $isLease
                ? "{$community} {$typeLabel} for Lease"
                : "{$community} {$typeLabel} for Sale";
        }

        if ($isSold) {
            // Rewrite "Toronto Houses for Sale" → "Toronto Sold Houses"
            if (preg_match('/^(.+?)\s+(Houses|Townhouses|Condos|Detached Houses|Semi-Detached Houses)\s+for\s+(Sale|Lease)$/i', $base, $m)) {
                return "{$m[1]} Sold {$m[2]}";
            }

            return $base;
        }

        return $base;
    }

    private static function formatPropertyTypeSlug(string $slug): string
    {
        $slug = strtolower(trim($slug));

        foreach (self::PROPERTY_TYPE_LABELS as $key => $label) {
            if ($slug === $key || str_ends_with($slug, '-' . $key) || str_starts_with($slug, $key . '-')) {
                return $label;
            }
        }

        return ucwords(str_replace('-', ' ', $slug));
    }

    private static function formatCitySlug(string $slug): string
    {
        $slug = strtolower(trim($slug));

        if (isset(self::CITY_LABELS[$slug])) {
            return self::CITY_LABELS[$slug];
        }

        return ucwords(str_replace('-', ' ', $slug));
    }
}
