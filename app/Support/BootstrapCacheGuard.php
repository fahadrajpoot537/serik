<?php

namespace App\Support;

/**
 * Prevent IIS/PHP 502s from stale Laravel bootstrap caches.
 *
 * `php artisan config:cache` on Botble omits plugin config keys and crashes
 * every request during provider boot. A routes cache built before plugin
 * routes were registered breaks RSS/sitemap routes the same way.
 */
final class BootstrapCacheGuard
{
    public static function healStaleCaches(): void
    {
        self::healConfigCache();
        self::healRoutesCache();
    }

    private static function healConfigCache(): void
    {
        $path = self::bootstrapCachePath('config.php');

        if (! is_file($path)) {
            return;
        }

        $contents = @file_get_contents($path);

        if ($contents === false) {
            return;
        }

        if (
            ! str_contains($contents, 'plugins.real-estate')
            && ! str_contains($contents, "'plugins'")
        ) {
            @unlink($path);
        }
    }

    private static function healRoutesCache(): void
    {
        $path = self::bootstrapCachePath('routes-v7.php');

        if (! is_file($path)) {
            return;
        }

        $contents = @file_get_contents($path);

        if ($contents === false) {
            return;
        }

        $required = [
            'public.sitemap',
            'feeds.show',
        ];

        foreach ($required as $needle) {
            if (! str_contains($contents, $needle)) {
                @unlink($path);

                return;
            }
        }
    }

    private static function bootstrapCachePath(string $file): string
    {
        return dirname(__DIR__, 2) . '/bootstrap/cache/' . $file;
    }
}
