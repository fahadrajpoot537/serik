<?php

namespace App\Support;

use Illuminate\Http\Request;

class CanonicalUrl
{
    public const DEFAULT_ORIGIN = 'https://serik.ca';

    public static function origin(): string
    {
        $origin = rtrim((string) (env('CANONICAL_URL') ?: env('FORCE_ROOT_URL') ?: self::DEFAULT_ORIGIN), '/');

        if ($origin === '') {
            return self::DEFAULT_ORIGIN;
        }

        if (! str_starts_with($origin, 'http://') && ! str_starts_with($origin, 'https://')) {
            $origin = 'https://' . ltrim($origin, '/');
        }

        return $origin;
    }

    public static function shouldNormalize(?Request $request = null): bool
    {
        $request ??= request();
        $host = strtolower((string) $request->getHost());

        if ($host === '' || in_array($host, ['localhost', '127.0.0.1', '[::1]'], true)) {
            return false;
        }

        if (str_ends_with($host, '.test') || str_ends_with($host, '.local')) {
            return false;
        }

        if (str_contains($host, 'serik.ca')) {
            return true;
        }

        return ! app()->environment('local');
    }

    public static function forceApplicationUrl(?Request $request = null): void
    {
        if (! self::shouldForceRootUrl($request)) {
            return;
        }

        \Illuminate\Support\Facades\URL::forceRootUrl(self::origin());
        \Illuminate\Support\Facades\URL::forceScheme('https');
    }

    public static function shouldForceRootUrl(?Request $request = null): bool
    {
        if (self::shouldNormalize($request)) {
            return true;
        }

        if (app()->environment('local')) {
            return (bool) (env('CANONICAL_URL') || env('FORCE_ROOT_URL'));
        }

        return true;
    }

    public static function normalize(string $url): string
    {
        if ($url === '' || ! self::shouldForceRootUrl()) {
            return $url;
        }

        $canonical = parse_url(self::origin());
        $parsed = parse_url($url);

        if (! is_array($parsed)) {
            return $url;
        }

        if (! isset($parsed['host'])) {
            if (str_starts_with($url, '/')) {
                return self::origin() . $url;
            }

            return $url;
        }

        $scheme = $canonical['scheme'] ?? 'https';
        $host = $canonical['host'] ?? 'serik.ca';
        $port = isset($canonical['port']) ? ':' . $canonical['port'] : '';

        $path = $parsed['path'] ?? '';
        $query = isset($parsed['query']) ? '?' . $parsed['query'] : '';
        $fragment = isset($parsed['fragment']) ? '#' . $parsed['fragment'] : '';

        return $scheme . '://' . $host . $port . $path . $query . $fragment;
    }

    public static function forRequest(Request $request): string
    {
        $path = $request->getPathInfo() ?: '/';

        return rtrim(self::origin(), '/') . ($path === '/' ? '/' : $path);
    }
}
