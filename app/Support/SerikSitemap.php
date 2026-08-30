<?php

namespace App\Support;

/**
 * Sitemap URL policy for Serik Realty.
 *
 * Redirect source paths must never appear in sitemaps — only live canonical URLs.
 */
final class SerikSitemap
{
    /**
     * Paths that 301 elsewhere (see theme routes/web.php). Never index these.
     *
     * @return array<int, string>
     */
    public static function redirectSourcePaths(): array
    {
        return [
            'evaluation',
            'frequently-asked-questions',
            'blog',
            'agents/sadaqat',
            'fthb',
        ];
    }

    public static function shouldInclude(string $url): bool
    {
        $path = trim((string) parse_url($url, PHP_URL_PATH), '/');
        $lower = strtolower($path);

        if ($lower === 'public' || str_starts_with($lower, 'public/')) {
            return false;
        }

        if ($path === '') {
            return true;
        }

        foreach (self::redirectSourcePaths() as $excluded) {
            if ($path === $excluded) {
                return false;
            }
        }

        // Map pin + map listing URLs are not the canonical featured listing pages.
        if (preg_match('~^on/.+/map(?:/.*)?$~', $lower)) {
            return false;
        }

        // Individual property URLs and /properties/city/{slug} archives.
        if (str_starts_with($lower, 'properties/')) {
            return false;
        }

        if (str_starts_with($lower, 'projects/')) {
            return false;
        }

        // CMS listing pages that rewrite to /on/.../map — use /ontario/{city}-houses-for-sale instead.
        if (str_contains($lower, '-for-sale-in-') || str_contains($lower, '-for-lease-in-')) {
            return false;
        }

        if (str_starts_with($lower, 'tag/')) {
            return false;
        }

        return true;
    }

    /**
     * Child sitemaps that must not appear in sitemap.xml (individual listings / tags).
     */
    public static function shouldIncludeIndex(string $url): bool
    {
        $path = strtolower(trim((string) parse_url($url, PHP_URL_PATH), '/'));

        if ($path === 'blog-tags.xml') {
            return false;
        }

        if ($path === 'properties-city.xml' || $path === 'projects-city.xml') {
            return false;
        }

        if (preg_match('#^properties-\d{4}-\d{2}(?:-page-\d+)?\.xml$#', $path)) {
            return false;
        }

        if (preg_match('#^projects-\d{4}-\d{2}(?:-page-\d+)?\.xml$#', $path)) {
            return false;
        }

        return true;
    }

    /**
     * Featured listing pages (all properties for a city/type), not individual listings.
     *
     * @return list<string> absolute URLs
     */
    public static function featuredListingUrls(): array
    {
        $cities = [
            'toronto',
            'brampton',
            'mississauga',
            'vaughan',
            'oakville',
            'milton',
            'hamilton',
            'kitchener',
            'ottawa',
        ];

        $types = [
            'houses',
            'detached-houses',
            'semi-detached-houses',
            'townhouses',
            'condos',
        ];

        $urls = [];
        foreach (['ontario', ...$cities] as $city) {
            foreach ($types as $type) {
                foreach (['sale', 'lease'] as $tx) {
                    $urls[] = url('ontario/' . $city . '-' . $type . '-for-' . $tx);
                }
            }
        }

        return array_values(array_unique($urls));
    }
}
