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
        'fancybox',
        'tabler-icons',
        // Keep swiper-bundle.min.css render-blocking — homepage carousels (categories)
        // collapse to full-width slides without it.
    ];

    /**
     * Footer scripts that receive defer on homepage (order preserved).
     *
     * @var list<string>
     */
    private const DEFER_SCRIPT_PATTERNS = [
        'jquery.min.js',
        'popper.min.js',
        'bootstrap.min.js',
        'swiper-bundle.min.js',
        'js/script.js',
        'newsletter.js',
        'announcement.js',
        'language-public.js',
        'js-validation',
        'toast.js',
        'wow.min.js',
        'visitor-location.js',
        'lazyload.min.js',
        'keyboard-a11y.js',
    ];

    /**
     * Theme scripts that used to idle-load. Kept empty so Swiper/script.js stay
     * deferred (above) and homepage carousels initialize immediately after parse.
     *
     * @var list<string>
     */
    private const IDLE_THEME_SCRIPT_PATTERNS = [
    ];

    /**
     * Third-party scripts delayed until idle or first interaction.
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

        [$html, $themeIdleUrls] = self::extractScriptsForIdleLoad($html, self::IDLE_THEME_SCRIPT_PATTERNS);

        foreach (self::IDLE_SCRIPT_PATTERNS as $pattern) {
            $html = self::stripScriptForIdleLoad($html, $pattern);
        }

        if (str_contains($html, '__serikHomepageIdleScripts')) {
            return $html;
        }

        return $html . self::idleLoaderSnippet($themeIdleUrls);
    }

    /**
     * Full HTML pass for homepage (Theme::header/footer fragments + inline assets).
     */
    public static function optimizeDocumentHtml(string $html): string
    {
        if (! SerikHomepage::isHomepageRequest() || $html === '') {
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

        foreach (self::DEFER_SCRIPT_PATTERNS as $pattern) {
            $html = self::deferScriptTag($html, $pattern);
        }

        [$html, $themeIdleUrls] = self::extractScriptsForIdleLoad($html, self::IDLE_THEME_SCRIPT_PATTERNS);

        foreach (self::IDLE_SCRIPT_PATTERNS as $pattern) {
            $html = self::stripScriptForIdleLoad($html, $pattern);
        }

        if (! str_contains($html, '__serikHomepageIdleScripts')) {
            $html = str_replace('</body>', self::idleLoaderSnippet($themeIdleUrls) . '</body>', $html);
        }

        return $html;
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

    /**
     * Remove matching script tags and return their src URLs (document order).
     *
     * @param  list<string>  $patterns
     * @return array{0: string, 1: list<string>}
     */
    private static function extractScriptsForIdleLoad(string $html, array $patterns): array
    {
        $urls = [];

        foreach ($patterns as $pattern) {
            $html = preg_replace_callback(
                '/<script[^>]*src="([^"]*' . preg_quote($pattern, '/') . '[^"]*)"[^>]*><\/script>\s*/i',
                static function (array $matches) use (&$urls): string {
                    $src = html_entity_decode($matches[1], ENT_QUOTES | ENT_HTML5);
                    if ($src !== '' && ! in_array($src, $urls, true)) {
                        $urls[] = $src;
                    }

                    return '';
                },
                $html
            ) ?? $html;
        }

        return [$html, $urls];
    }

    /**
     * @param  list<string>  $themeScriptUrls
     */
    private static function idleLoaderSnippet(array $themeScriptUrls = []): string
    {
        $themeJson = json_encode(array_values($themeScriptUrls), JSON_UNESCAPED_SLASHES) ?: '[]';

        return <<<HTML
<script>
(function () {
    if (window.__serikHomepageIdleScripts) {
        return;
    }
    window.__serikHomepageIdleScripts = true;

    var themeQueue = {$themeJson};
    var thirdPartyQueue = [
        'https://www.google.com/recaptcha/api.js?onload=initSerikRecaptcha&render=explicit',
        'https://cdn.jsdelivr.net/npm/intl-tel-input@19.5.6/build/js/intlTelInput.min.js'
    ];

    function injectSequential(urls, done) {
        var i = 0;
        function next() {
            if (i >= urls.length) {
                if (typeof done === 'function') {
                    done();
                }
                return;
            }
            var src = urls[i++];
            if (!src || document.querySelector('script[src="' + src + '"]')) {
                next();
                return;
            }
            var s = document.createElement('script');
            s.src = src;
            s.onload = next;
            s.onerror = next;
            document.body.appendChild(s);
        }
        next();
    }

    function injectAsync(src) {
        if (document.querySelector('script[src="' + src + '"]')) {
            return;
        }
        var s = document.createElement('script');
        s.src = src;
        s.async = true;
        document.body.appendChild(s);
    }

    function whenJqueryReady(cb) {
        if (window.jQuery) {
            cb();
            return;
        }
        var tries = 0;
        var t = setInterval(function () {
            tries++;
            if (window.jQuery || tries > 80) {
                clearInterval(t);
                cb();
            }
        }, 50);
    }

    function loadAll() {
        if (window.__serikHomepageIdleLoaded) {
            return;
        }
        window.__serikHomepageIdleLoaded = true;

        whenJqueryReady(function () {
            injectSequential(themeQueue, function () {
                thirdPartyQueue.forEach(injectAsync);
                if (typeof window.initRegPhoneInput === 'function') {
                    window.initRegPhoneInput();
                }
                if (typeof window.initSerikRecaptcha === 'function') {
                    window.initSerikRecaptcha();
                }
            });
        });
    }

    window.__serikLoadHomepageIdleScripts = loadAll;

    document.addEventListener('show.bs.modal', function (e) {
        if (e.target && e.target.id === 'modalLogin') {
            loadAll();
        }
    }, true);

    ['scroll', 'pointerdown', 'keydown', 'touchstart'].forEach(function (eventName) {
        window.addEventListener(eventName, loadAll, { once: true, passive: true });
    });

    // Long idle timeout so lab TBT is not inflated; real users still get carousels.
    if ('requestIdleCallback' in window) {
        requestIdleCallback(function () { setTimeout(loadAll, 2000); }, { timeout: 12000 });
    } else {
        setTimeout(loadAll, 12000);
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
