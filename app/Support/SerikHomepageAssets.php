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
        'animate.min.css',
    ];

    /**
     * Stylesheets loaded asynchronously (non-render-blocking).
     *
     * @var list<string>
     */
    private const ASYNC_PATTERNS = [
        'social-login',
        'front-auth',
        'auth-css',
        'language-public',
        'language-css',
        'announcement',
        'newsletter',
        'intlTelInput',
    ];

    /**
     * Footer scripts that receive defer on homepage (order preserved).
     *
     * @var list<string>
     */
    private const DEFER_SCRIPT_PATTERNS = [
        'newsletter.js',
        'announcement.js',
        'language-public.js',
        'js-validation',
        'toast.js',
        'wow.min.js',
    ];

    /**
     * Footer scripts delayed until idle or first interaction.
     *
     * @var list<string>
     */
    private const IDLE_SCRIPT_PATTERNS = [
        'recaptcha/api.js',
        'intl-tel-input',
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

        foreach (self::DEFER_SCRIPT_PATTERNS as $pattern) {
            $html = self::deferScriptTag($html, $pattern);
        }

        foreach (self::IDLE_SCRIPT_PATTERNS as $pattern) {
            $html = self::stripScriptForIdleLoad($html, $pattern);
        }

        if (str_contains($html, '__serikHomepageIdleScripts')) {
            return $html;
        }

        return $html . self::idleLoaderSnippet();
    }

    private static function deferScriptTag(string $html, string $pattern): string
    {
        return preg_replace_callback(
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

    private static function stripScriptForIdleLoad(string $html, string $pattern): string
    {
        return preg_replace(
            '/<script[^>]*src="[^"]*' . preg_quote($pattern, '/') . '[^"]*"[^>]*><\/script>\s*/i',
            '',
            $html
        ) ?? $html;
    }

    private static function idleLoaderSnippet(): string
    {
        return <<<'HTML'
<script>
(function () {
    if (window.__serikHomepageIdleScripts) {
        return;
    }
    window.__serikHomepageIdleScripts = true;

    var queue = [
        'https://www.google.com/recaptcha/api.js?onload=initSerikRecaptcha&render=explicit',
        'https://cdn.jsdelivr.net/npm/intl-tel-input@19.5.6/build/js/intlTelInput.min.js'
    ];

    function inject(src) {
        if (document.querySelector('script[src="' + src + '"]')) {
            return;
        }
        var s = document.createElement('script');
        s.src = src;
        s.async = true;
        document.body.appendChild(s);
    }

    function loadAll() {
        if (window.__serikHomepageIdleLoaded) {
            return;
        }
        window.__serikHomepageIdleLoaded = true;
        queue.forEach(inject);
    }

    function onModalOpen() {
        loadAll();
    }

    document.addEventListener('show.bs.modal', function (e) {
        if (e.target && e.target.id === 'modalLogin') {
            onModalOpen();
        }
    }, true);

    ['scroll', 'pointerdown', 'keydown', 'touchstart'].forEach(function (eventName) {
        window.addEventListener(eventName, loadAll, { once: true, passive: true });
    });

    if ('requestIdleCallback' in window) {
        requestIdleCallback(loadAll, { timeout: 8000 });
    } else {
        setTimeout(loadAll, 8000);
    }
})();
</script>
HTML;
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
}
