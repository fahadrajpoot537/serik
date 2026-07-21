<?php

namespace App\Support;

/**
 * Handle bot/crawler requests that incorrectly prefix URLs with /public/.
 */
final class PublicPrefixPath
{
    /**
     * Path segments under /public/ that must never redirect (security probes).
     *
     * @return array<int, string>
     */
    public static function blockedPrefixes(): array
    {
        return [
            '.env',
            'vendor',
            'storage',
            'bootstrap',
            'config',
            'composer.json',
            'composer.lock',
            'phpinfo.php',
            'wp-admin',
            'wp-login.php',
            'xmlrpc.php',
            'adminer.php',
            'phpmyadmin',
            'server-status',
            '.git',
            '.svn',
            '.ds_store',
            'artisan',
            'phpunit.xml',
            'package.json',
            'package-lock.json',
            'yarn.lock',
            'dockerfile',
            'docker-compose.yml',
            'docker-compose.yaml',
            '.htaccess',
            '.htpasswd',
            'web.config',
        ];
    }

    /**
     * @return string|null Remainder after "public/" or "" for bare "/public", null if not a public-prefix path.
     */
    public static function stripPrefix(string $path): ?string
    {
        $path = ltrim($path, '/');
        $lower = strtolower($path);

        if ($lower === 'public') {
            return '';
        }

        if (! str_starts_with($lower, 'public/')) {
            return null;
        }

        return ltrim(substr($path, 7), '/');
    }

    public static function isBlockedRemainder(string $remainder): bool
    {
        $lower = strtolower(ltrim($remainder, '/'));

        if ($lower === '') {
            return false;
        }

        if (preg_match('#(^|/)\.#', $lower)) {
            return true;
        }

        foreach (self::blockedPrefixes() as $blocked) {
            if ($lower === $blocked || str_starts_with($lower, $blocked . '/')) {
                return true;
            }
        }

        if (preg_match(
            '#\.(bak|backup|old|orig|save|swp|swo|sql|sqlite|db|dump|zip|tar|tgz|gz|bz2|7z|rar|log|ini|conf|sh|bash|pem|key)$#i',
            $lower
        )) {
            return true;
        }

        return false;
    }

    public static function redirectTarget(string $remainder): string
    {
        $remainder = ltrim($remainder, '/');

        return $remainder === '' ? '/' : '/' . $remainder;
    }

    public static function stripFromUrlPath(string $path): string
    {
        if (preg_match('#^/public(/.*)?$#i', $path, $matches)) {
            $rest = $matches[1] ?? '';

            return $rest === '' ? '/' : $rest;
        }

        return $path;
    }
}
