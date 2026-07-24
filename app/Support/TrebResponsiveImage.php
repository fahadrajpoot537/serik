<?php

namespace App\Support;

/**
 * Responsive srcset/sizes for same-origin TREB proxy listing images.
 */
final class TrebResponsiveImage
{
    /** @var list<int> */
    private const CARD_WIDTHS = [320, 640, 960];

    public static function isProxyUrl(?string $url): bool
    {
        return is_string($url)
            && $url !== ''
            && str_contains($url, '/storage/properties/treb/')
            && str_contains($url, '.webp');
    }

    /**
     * @return array<string, string>
     */
    public static function cardAttributes(?string $url, bool $lazy = true): array
    {
        if (! self::isProxyUrl($url)) {
            return [];
        }

        $srcset = self::buildSrcset($url, self::CARD_WIDTHS);

        if ($srcset === '') {
            return [];
        }

        $attrs = [
            'srcset' => $srcset,
            'sizes' => '(max-width: 576px) 50vw, (max-width: 992px) 33vw, 300px',
            'decoding' => 'async',
            'loading' => $lazy ? 'lazy' : 'eager',
        ];

        if (! $lazy) {
            $attrs['fetchpriority'] = 'high';
        }

        return $attrs;
    }

    public static function urlWithWidth(string $url, int $width): string
    {
        $parsed = parse_url($url);
        $path = $parsed['path'] ?? $url;
        $query = [];

        if (! empty($parsed['query'])) {
            parse_str($parsed['query'], $query);
        }

        $query['w'] = $width;

        $scheme = $parsed['scheme'] ?? null;
        $host = $parsed['host'] ?? null;
        $base = ($scheme && $host)
            ? $scheme . '://' . $host . $path
            : $path;

        return $base . '?' . http_build_query($query);
    }

    /**
     * @param  list<int>  $widths
     */
    private static function buildSrcset(string $url, array $widths): string
    {
        $parts = [];

        foreach ($widths as $width) {
            $parts[] = self::urlWithWidth($url, $width) . ' ' . $width . 'w';
        }

        return implode(', ', $parts);
    }
}
