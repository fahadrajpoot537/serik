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

        if (preg_match('#^on/.+/map/[^/]+$#', $lower)) {
            return false;
        }

        return true;
    }
}
