<?php

namespace App\Support;

/**
 * Homepage-only responsive delivery: smaller cached WebP variants + srcset/sizes.
 * Never modifies original media files.
 */
final class SerikResponsiveImage
{
    /**
     * @var array<string, array{0:int,1:int}>
     */
    private const DISPLAY_SIZES = [
        'thumb' => [150, 150],
        'small' => [300, 200],
        'medium' => [400, 300],
        'medium-rectangle' => [400, 260],
        'medium-square' => [400, 400],
        'medium-rectangle-column' => [400, 560],
        'large' => [960, 720],
    ];

    /**
     * @var array<string, string>
     */
    private const SRCSET_SIZES = [
        'thumb' => '150px',
        'small' => '(max-width: 576px) 45vw, 300px',
        'medium' => '(max-width: 768px) 50vw, 400px',
        'medium-rectangle' => '(max-width: 768px) 45vw, 400px',
        'medium-square' => '(max-width: 768px) 40vw, 400px',
        'medium-rectangle-column' => '(max-width: 768px) 45vw, 400px',
        'large' => '(max-width: 768px) 90vw, (max-width: 1200px) 55vw, 720px',
        'hero' => '(max-width: 768px) 92vw, 574px',
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

        if (CmsWebp::isTrebPath($url) || TrebResponsiveImage::isProxyUrl($url)) {
            return $markup;
        }

        if ($size === null && isset($attributes['size']) && is_string($attributes['size'])) {
            $size = $attributes['size'];
        }

        $isHero = self::isHeroMarkup($markup, $attributes, $size);
        $sizeKey = $isHero ? 'hero' : (($size && isset(self::DISPLAY_SIZES[$size])) ? $size : 'medium-rectangle');
        [$width, $height] = $isHero
            ? [960, 720]
            : (self::DISPLAY_SIZES[$sizeKey] ?? self::DISPLAY_SIZES['medium-rectangle']);

        $widths = $isHero ? [480, 574, 720] : [240, 400, 560];
        $defaultWidth = $isHero ? 574 : 400;
        $quality = $isHero ? 72 : 64;

        $defaultSrc = SerikHomepageImage::optimizedUrl($url, $defaultWidth, $quality);
        if (! is_string($defaultSrc) || $defaultSrc === '') {
            $defaultSrc = CmsWebp::preferWebpUrl($url) ?: $url;
        }

        $srcset = SerikHomepageImage::srcset($url, $widths, $quality);
        $sizesAttr = self::SRCSET_SIZES[$sizeKey] ?? self::SRCSET_SIZES['medium-rectangle'];

        // Rewrite src to the optimized variant (not the full original).
        $markup = self::replaceSrc($markup, $defaultSrc);

        if ($srcset !== '' && ! str_contains($markup, 'srcset=')) {
            $markup = preg_replace(
                '/<img\b/i',
                '<img srcset="' . e($srcset) . '" sizes="' . e($sizesAttr) . '"',
                $markup,
                1
            ) ?? $markup;
        }

        if (! preg_match('/\bwidth=/i', $markup)) {
            $markup = preg_replace('/<img\b/i', '<img width="' . $width . '" height="' . $height . '"', $markup, 1) ?? $markup;
        }

        if (! preg_match('/\bdecoding=/i', $markup)) {
            $markup = preg_replace('/<img\b/i', '<img decoding="async"', $markup, 1) ?? $markup;
        }

        if (! $isHero && ! preg_match('/\bloading=/i', $markup)) {
            $markup = preg_replace('/<img\b/i', '<img loading="lazy"', $markup, 1) ?? $markup;
        }

        return $markup;
    }

    /**
     * Safely rewrite homepage <img src> values for leftover storage URLs
     * that did not pass through core_media_image (still never touches files).
     */
    public static function enhanceHomepageHtml(string $html): string
    {
        if (! SerikHomepage::isHomepageRequest() || $html === '') {
            return $html;
        }

        return preg_replace_callback(
            '/<img\b([^>]*?)>/i',
            static function (array $m): string {
                $attrs = $m[1];

                // Only the src attribute — never data-src / this.src .
                if (! preg_match('/(?:^|\s)src=(["\'])([^"\']+)\1/i', $attrs, $srcMatch)) {
                    return '<img' . $attrs . '>';
                }

                $src = html_entity_decode($srcMatch[2], ENT_QUOTES | ENT_HTML5);
                if ($src === ''
                    || str_starts_with($src, 'data:')
                    || str_contains($src, 'serik-hp-cache/')
                    || str_contains($src, 'placeholder')
                    || CmsWebp::isTrebPath($src)
                    || TrebResponsiveImage::isProxyUrl($src)
                    || ! str_contains($src, '/storage/')
                ) {
                    return '<img' . $attrs . '>';
                }

                // Skip tiny chrome / already-sized thumbs.
                if (preg_match('/-150x150\./i', $src) || preg_match('/whatsapp-image-2025/i', $src)) {
                    return '<img' . $attrs . '>';
                }

                $isHero = self::isHeroMarkup($attrs, [], null)
                    || str_contains($attrs, 'serik-split-hero__banner-img')
                    || str_contains($attrs, 'fetchpriority="high"')
                    || str_contains($attrs, "fetchpriority='high'");

                $defaultWidth = $isHero ? 574 : 400;
                $widths = $isHero ? [480, 574, 720] : [240, 400, 560];
                $quality = $isHero ? 72 : 64;

                $optimized = SerikHomepageImage::optimizedUrl($src, $defaultWidth, $quality);
                if (! is_string($optimized) || $optimized === '' || $optimized === $src) {
                    $webp = CmsWebp::preferWebpUrl($src);
                    if (is_string($webp) && $webp !== '' && $webp !== $src) {
                        $attrs = preg_replace(
                            '/(?:^|\s)src=(["\'])[^"\']*\1/i',
                            ' src="' . e($webp) . '"',
                            $attrs,
                            1
                        ) ?? $attrs;
                    }

                    return '<img' . $attrs . '>';
                }

                $attrs = preg_replace(
                    '/(?:^|\s)src=(["\'])[^"\']*\1/i',
                    ' src="' . e($optimized) . '"',
                    $attrs,
                    1
                ) ?? $attrs;

                if (! str_contains($attrs, 'srcset=')) {
                    $srcset = SerikHomepageImage::srcset($src, $widths, $quality);
                    if ($srcset !== '') {
                        $sizes = $isHero
                            ? self::SRCSET_SIZES['hero']
                            : self::SRCSET_SIZES['medium-rectangle'];
                        $attrs .= ' srcset="' . e($srcset) . '" sizes="' . e($sizes) . '"';
                    }
                }

                if (! preg_match('/\bdecoding=/i', $attrs)) {
                    $attrs .= ' decoding="async"';
                }

                if (! $isHero && ! preg_match('/\bloading=/i', $attrs)) {
                    $attrs .= ' loading="lazy"';
                }

                if (! preg_match('/\bwidth=/i', $attrs)) {
                    $attrs .= $isHero ? ' width="960" height="720"' : ' width="400" height="300"';
                }

                return '<img' . $attrs . '>';
            },
            $html
        ) ?? $html;
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private static function isHeroMarkup(string $markup, array $attributes, ?string $size): bool
    {
        if ($size === 'large') {
            return true;
        }
        if (! empty($attributes['fetchpriority']) && (string) $attributes['fetchpriority'] === 'high') {
            return true;
        }

        return str_contains($markup, 'serik-split-hero__banner-img')
            || str_contains($markup, 'fetchpriority="high"')
            || str_contains($markup, "fetchpriority='high'");
    }

    private static function replaceSrc(string $markup, string $newSrc): string
    {
        $replaced = preg_replace(
            '/(<img\b[^>]*?\s)src=(["\'])[^"\']*\2/i',
            '$1src="' . e($newSrc) . '"',
            $markup,
            1
        );

        return is_string($replaced) ? $replaced : $markup;
    }
}
