<?php

namespace App\Support;

/**
 * Homepage-only asset optimizations that must not alter layout/spacing.
 */
final class SerikHomepageAssets
{
    /**
     * Patterns removed entirely from homepage head (unused on front page).
     *
     * @var list<string>
     */
    private const REMOVE_PATTERNS = [
        'content-styles',
        'ckeditor',
    ];

    /**
     * Stylesheets loaded asynchronously (non-render-blocking).
     *
     * @var list<string>
     */
    private const ASYNC_PATTERNS = [
        'animate.min.css',
        'bootstrap',
        'social-login',
        'front-auth',
        'auth-css',
        'language-public',
        'language-css',
        'announcement',
        'newsletter',
    ];

    public static function optimizeHeaderHtml(?string $html): ?string
    {
        if (! SerikHomepage::isHomepageRequest() || ! is_string($html) || $html === '') {
            return $html;
        }

        foreach (self::REMOVE_PATTERNS as $pattern) {
            $html = preg_replace(
                '/<link[^>]*href="[^"]*' . preg_quote($pattern, '/') . '[^"]*"[^>]*>\s*/i',
                '',
                $html
            ) ?? $html;
        }

        foreach (self::ASYNC_PATTERNS as $pattern) {
            $html = self::makeStylesheetAsync($html, $pattern);
        }

        return $html;
    }

    public static function optimizeFooterHtml(?string $html): ?string
    {
        if (! SerikHomepage::isHomepageRequest() || ! is_string($html) || $html === '') {
            return $html;
        }

        $deferPatterns = [
            'newsletter.js',
        ];

        foreach ($deferPatterns as $pattern) {
            $html = preg_replace_callback(
                '/<script([^>]*src="[^"]*' . preg_quote($pattern, '/') . '[^"]*"[^>]*)>/i',
                static function (array $matches): string {
                    if (str_contains($matches[0], ' defer')) {
                        return $matches[0];
                    }

                    return '<script' . $matches[1] . ' defer>';
                },
                $html
            ) ?? $html;
        }

        return $html;
    }

    private static function makeStylesheetAsync(string $html, string $pattern): string
    {
        return preg_replace_callback(
            '/<link([^>]*href="[^"]*' . preg_quote($pattern, '/') . '[^"]*"[^>]*)>/i',
            static function (array $matches): string {
                $attrs = $matches[1];

                if (str_contains($attrs, 'onload=')) {
                    return $matches[0];
                }

                $attrs = preg_replace('/\smedia=(["\']).*?\1/i', '', $attrs) ?? $attrs;

                return '<link' . $attrs . ' media="print" onload="this.media=\'all\'">';
            },
            $html
        ) ?? $html;
    }

    /**
     * @return list<array{href: string, as: string, crossorigin?: string}>
     */
    public static function fontPreloads(): array
    {
        if (! SerikHomepage::isHomepageRequest()) {
            return [];
        }

        $preloads = [];

        try {
            $css = (string) theme()->typography()->renderCssVariables();
        } catch (\Throwable) {
            return [];
        }

        if (preg_match_all('/url\(([^)]+\.woff2[^)]*)\)/i', $css, $matches)) {
            foreach (array_unique($matches[1]) as $rawUrl) {
                $href = trim($rawUrl, " '\"");
                if ($href !== '') {
                    $preloads[] = ['href' => $href, 'as' => 'font', 'crossorigin' => 'anonymous'];
                }
            }
        }

        return array_slice($preloads, 0, 2);
    }
}
