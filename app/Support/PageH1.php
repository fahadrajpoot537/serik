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
        'terms-conditions' => 'Terms & Conditions',
        'tips-for-home-selling' => 'Tips for Selling Your Home in Ontario',
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
        'tips-for-home-selling',
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

        if (strtolower($citySlug) === 'ontario') {
            return "{$type} for {$listingType} in Ontario";
        }

        return "{$type} for {$listingType} in {$city}";
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
