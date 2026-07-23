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
    /**
     * Markers that appear in a healthy Botble config cache file.
     *
     * @var list<string>
     */
    private const VALID_CONFIG_MARKERS = [
        'plugins.real-estate',
        'plugins/real-estate::',
        "'real-estate' =>",
        'core.base.general',
        "'core' =>",
    ];

    public static function healStaleCaches(): void
    {
        self::healConfigCache();
        self::healRoutesCache();
    }

    private static function healConfigCache(): void
    {
        $path = self::bootstrapCachePath('config.php');

        if (! is_file($path) || ! is_readable($path)) {
            return;
        }

        $handle = @fopen($path, 'rb');
        if ($handle === false) {
            return;
        }

        $sample = (string) @fread($handle, 65536);
        @fclose($handle);

        if ($sample === '' || self::isValidBotbleConfigCache($sample)) {
            return;
        }

        @unlink($path);
    }

    private static function isValidBotbleConfigCache(string $contents): bool
    {
        foreach (self::VALID_CONFIG_MARKERS as $marker) {
            if (str_contains($contents, $marker)) {
                return true;
            }
        }

        return false;
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
