<?php

namespace App\Support;

use Botble\Media\Facades\RvMedia;
use Illuminate\Support\Str;

/**
 * Adds intrinsic dimensions and srcset without CSS layout overrides.
 */
final class SerikResponsiveImage
{
    /**
     * @var array<string, array{0:int,1:int}>
     */
    private const DISPLAY_SIZES = [
        'thumb' => [120, 120],
        'small' => [300, 200],
        'medium' => [400, 300],
        'medium-rectangle' => [400, 300],
        'large' => [800, 600],
    ];

    /**
     * @var array<string, string>
     */
    private const SRCSET_SIZES = [
        'thumb' => '120px',
        'small' => '(max-width: 576px) 100vw, 300px',
        'medium' => '(max-width: 768px) 50vw, 400px',
        'medium-rectangle' => '(max-width: 768px) 50vw, 400px',
        'large' => '(max-width: 992px) 75vw, 800px',
    ];

    public static function enhance(
        string $markup,
        ?string $url,
        ?string $size = null,
        array $attributes = []
    ): string {
        if (! SerikHomepage::isHomepageRequest() || ! is_string($url) || $url === '') {
            return $markup;
        }

        if (str_contains($markup, 'srcset=')) {
            return $markup;
        }

        $sizeKey = $size && isset(self::DISPLAY_SIZES[$size]) ? $size : 'medium-rectangle';
        [$width, $height] = self::DISPLAY_SIZES[$sizeKey];

        if (! preg_match('/\bwidth=/i', $markup)) {
            $markup = preg_replace('/<img\b/i', '<img width="' . $width . '" height="' . $height . '"', $markup, 1) ?? $markup;
        }

        if (! preg_match('/\bdecoding=/i', $markup)) {
            $markup = preg_replace('/<img\b/i', '<img decoding="async"', $markup, 1) ?? $markup;
        }

        $srcset = self::buildSrcset($url, $sizeKey);

        if ($srcset === '') {
            return $markup;
        }

        $sizesAttr = self::SRCSET_SIZES[$sizeKey] ?? self::SRCSET_SIZES['medium-rectangle'];

        return preg_replace(
            '/<img\b/i',
            '<img srcset="' . e($srcset) . '" sizes="' . e($sizesAttr) . '"',
            $markup,
            1
        ) ?? $markup;
    }

    private static function buildSrcset(string $url, string $preferredSize): string
    {
        if (Str::startsWith($url, ['data:', 'http://', 'https://']) && ! str_contains($url, '/storage/')) {
            return '';
        }

        $candidates = [];

        foreach (['small', 'medium', 'medium-rectangle', 'large'] as $sizeName) {
            $variant = RvMedia::getImageUrl($url, $sizeName, false, null);

            if (! is_string($variant) || $variant === '' || $variant === $url) {
                continue;
            }

            $dims = self::DISPLAY_SIZES[$sizeName] ?? null;

            if ($dims) {
                $candidates[$dims[0] . 'w'] = $variant;
            }
        }

        if ($candidates === []) {
            return '';
        }

        ksort($candidates, SORT_NUMERIC);

        $parts = [];

        foreach ($candidates as $descriptor => $variantUrl) {
            $parts[] = $variantUrl . ' ' . $descriptor;
        }

        return implode(', ', $parts);
    }
}
