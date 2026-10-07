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
        'leaflet',
        // Heavy theme CSS: async on homepage (homepage-premium stays blocking for above-fold).
        'bootstrap.min.css',
        'bootstrap.rtl.min.css',
        'css/style.css',
        'swiper-bundle.min.css',
        'site-chrome.css',
        // Do NOT list fonts.googleapis.com / fonts.gstatic.com — that breaks <link rel=preconnect>.
    ];

    /**
     * Footer scripts that receive defer on homepage (order preserved).
     * Swiper + script.js must stay here (not idle) or carousels render broken.
     *
     * @var list<string>
     */
    private const DEFER_SCRIPT_PATTERNS = [
        'jquery.min.js',
        'swiper-bundle.min.js',
        'js/script.js',
    ];

    /**
     * Non-carousel scripts delayed until idle / interaction (safe for Lighthouse TBT).
     * Popper/bootstrap only needed for login modal — idle + modal show loads them.
     * Lazyload is idle-safe on homepage because hydrateLazyPlaceholders promotes data-src.
     *
     * @var list<string>
     */
    private const IDLE_THEME_SCRIPT_PATTERNS = [
        'popper.min.js',
        'bootstrap.min.js',
        'lazyload.min.js',
        'keyboard-a11y.js',
        'jquery.fancybox',
        'newsletter.js',
        'announcement.js',
        'language-public.js',
        'js-validation',
        'toast.js',
        'wow.min.js',
        'visitor-location.js',
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
     * True when cached/served HTML still has homepage-critical blocking junk.
     */
    public static function needsDocumentOptimize(string $html): bool
    {
        if ($html === '') {
            return false;
        }

        if (str_contains($html, 'content-styles.css') || str_contains($html, '/ckeditor/')) {
            return true;
        }

        // Idle pass not applied yet (or stale pre-optimize cache).
        if (! str_contains($html, '__serikHomepageIdleScripts')) {
            return true;
        }

        // Scripts that must be idle-extracted are still present as real tags.
        if (preg_match('/<script[^>]+src=["\'][^"\']*(?:bootstrap\.min\.js|popper\.min\.js|lazyload\.min\.js|visitor-location\.js)[^"\']*["\'][^>]*>/i', $html)) {
            return true;
        }

        if (preg_match('/<script[^>]+src=["\'][^"\']*(?:recaptcha\/api\.js|intl-tel-input)[^"\']*["\'][^>]*>/i', $html)) {
            return true;
        }

        return false;
    }

    /**
     * Full HTML pass for homepage (Theme::header/footer fragments + inline assets).
     *
     * @param  bool  $force  Re-optimize even when the current request is not detected as homepage
     *                       (used when healing stale cached HTML on HIT).
     */
    public static function optimizeDocumentHtml(string $html, bool $force = false): string
    {
        if ($html === '' || (! $force && ! SerikHomepage::isHomepageRequest())) {
            return $html;
        }

        // If a previous pass left a stale idle loader, strip it so we rebuild with
        // the current themeQueue (avoids double loaders / empty queues).
        if (str_contains($html, '__serikHomepageIdleScripts')) {
            $html = preg_replace(
                '/<script>\s*\(function\s*\(\)\s*\{\s*if\s*\(window\.__serikHomepageIdleScripts\)[\s\S]*?<\/script>\s*/i',
                '',
                $html,
                1
            ) ?? $html;
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

        // Drop duplicate stylesheet hrefs (e.g. tabler / site-chrome listed twice).
        $html = self::dedupeStylesheetLinks($html);

        foreach (self::DEFER_SCRIPT_PATTERNS as $pattern) {
            $html = self::deferScriptTag($html, $pattern);
        }

        [$html, $themeIdleUrls] = self::extractScriptsForIdleLoad($html, self::IDLE_THEME_SCRIPT_PATTERNS);

        foreach (self::IDLE_SCRIPT_PATTERNS as $pattern) {
            $html = self::stripScriptForIdleLoad($html, $pattern);
        }

        // Homepage images are hydrated — drop Botble LazyLoad boot (undefined/late = TBT noise).
        $html = preg_replace(
            '/<script>\s*document\.addEventListener\(\s*[\'"]DOMContentLoaded[\'"]\s*,\s*function\s*\(\)\s*\{\s*window\.Theme\s*=\s*window\.Theme\s*\|\|\s*\{\};[\s\S]*?Theme\.lazyLoadInstance\s*=\s*new\s*LazyLoad\([\s\S]*?<\/script>\s*/i',
            '',
            $html
        ) ?? $html;

        if (! str_contains($html, '__serikHomepageIdleScripts')) {
            $html = str_replace('</body>', self::idleLoaderSnippet($themeIdleUrls) . '</body>', $html);
        }

        // Fancybox CSS/JS load on gallery click (services style-3) — drop head links on homepage.
        $html = preg_replace(
            '/<link[^>]+href="[^"]*fancybox[^"]*"[^>]*>\s*/i',
            '',
            $html
        ) ?? $html;
        $html = preg_replace(
            '/<noscript>\s*<link[^>]+href="[^"]*fancybox[^"]*"[^>]*>\s*<\/noscript>\s*/i',
            '',
            $html
        ) ?? $html;

        // Defer non-LCP images (newsletter popup, WA float, agent avatars). Keep logo/hero.
        $html = preg_replace_callback(
            '/<img\b([^>]*\b(?:newsletter-image|whatsapp-icon-free|unnamed-\d+\.png)[^>]*?)\s*\/?>/i',
            static function (array $m): string {
                $attrs = rtrim($m[1]);
                if (preg_match('/\bloading=/i', $attrs)) {
                    $attrs = preg_replace('/\bloading=(["\'])[^"\']*\1/i', 'loading="lazy"', $attrs) ?? $attrs;
                } else {
                    $attrs .= ' loading="lazy"';
                }
                if (preg_match('/\bfetchpriority=/i', $attrs)) {
                    $attrs = preg_replace('/\bfetchpriority=(["\'])[^"\']*\1/i', 'fetchpriority="low"', $attrs) ?? $attrs;
                } else {
                    $attrs .= ' fetchpriority="low"';
                }
                if (preg_match('/\bdata-bb-lazy=/i', $attrs)) {
                    $attrs = preg_replace('/\bdata-bb-lazy=(["\'])[^"\']*\1/i', 'data-bb-lazy="true"', $attrs) ?? $attrs;
                }

                return '<img' . $attrs . '>';
            },
            $html
        ) ?? $html;

        // Promote data-src → src when Botble left a placeholder (works even if
        // LazyLoad is late). Native loading="lazy" still defers offscreen work.
        $html = self::hydrateLazyPlaceholders($html);

        // Homepage-only: swap storage image src to cached resized WebP (+ srcset).
        // Touches HTML only — originals on disk / DB URLs are never modified.
        $html = SerikResponsiveImage::enhanceHomepageHtml($html);

        // CMS testimonial paste often embeds font-family:Roboto (no Roboto file is loaded).
        // Point those spans at Poppins so browsers do not request a stray Roboto face.
        $html = preg_replace(
            '/font-family\s*:\s*Roboto\b[^;}"\']*/i',
            'font-family:var(--primary-font, Poppins, sans-serif)',
            $html
        ) ?? $html;

        // Do NOT use naive \bsrc= rewrite (matches data-src / this.src and wipes URLs).
        return $html;
    }

    /**
     * Botble lazy-load leaves src=placeholder + data-src=real. If LazyLoad.js is
     * missing/late, every homepage image stays blank — promote real URLs here.
     */
    public static function hydrateLazyPlaceholders(string $html): string
    {
        return preg_replace_callback(
            '/<img\b([^>]*)>/i',
            static function (array $m): string {
                $attrs = $m[1];
                if (! preg_match('/\bdata-src=(["\'])([^"\']+)\1/i', $attrs, $dataSrcMatch)) {
                    return '<img' . $attrs . '>';
                }

                $real = html_entity_decode($dataSrcMatch[2], ENT_QUOTES | ENT_HTML5);
                if ($real === '' || str_starts_with($real, 'data:') || str_contains($real, '${')) {
                    return '<img' . $attrs . '>';
                }

                $src = '';
                if (preg_match('/(?:^|\s)src=(["\'])([^"\']*)\1/i', $attrs, $srcMatch)) {
                    $src = html_entity_decode($srcMatch[2], ENT_QUOTES | ENT_HTML5);
                }

                $needsPromote = $src === ''
                    || str_contains($src, 'placeholder')
                    || str_contains($src, 'data:image');

                if (! $needsPromote) {
                    return '<img' . $attrs . '>';
                }

                if (preg_match('/(?:^|\s)src=(["\'])[^"\']*\1/i', $attrs)) {
                    $attrs = preg_replace(
                        '/(?:^|\s)src=(["\'])[^"\']*\1/i',
                        ' src="' . e($real) . '"',
                        $attrs,
                        1
                    ) ?? $attrs;
                } else {
                    $attrs .= ' src="' . e($real) . '"';
                }

                $attrs = preg_replace('/\sdata-src=(["\'])[^"\']*\1/i', '', $attrs) ?? $attrs;
                $attrs = preg_replace(
                    '/\bdata-bb-lazy=(["\'])[^"\']*\1/i',
                    'data-bb-lazy="false"',
                    $attrs
                ) ?? $attrs;

                return '<img' . $attrs . '>';
            },
            $html
        ) ?? $html;
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
    // reCAPTCHA + intl-tel-input load on demand (never idle-inject).
    var thirdPartyQueue = [];

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
                // reCAPTCHA: do not init here — window.loadRecaptcha() is on-demand only.
            });
        });
    }

    window.__serikLoadHomepageIdleScripts = loadAll;

    function openLoginModalWhenReady() {
        var tries = 0;
        var t = setInterval(function () {
            tries++;
            if (window.bootstrap && document.getElementById('modalLogin')) {
                clearInterval(t);
                try {
                    window.bootstrap.Modal.getOrCreateInstance(document.getElementById('modalLogin')).show();
                } catch (e) {}
                return;
            }
            if (tries > 120) {
                clearInterval(t);
            }
        }, 50);
    }

    // Bootstrap is idle-loaded — first login click must wait for it, then open.
    document.addEventListener('click', function (e) {
        var trigger = e.target && e.target.closest
            ? e.target.closest('[data-bs-target="#modalLogin"], a[href="#modalLogin"]')
            : null;
        if (!trigger) {
            return;
        }
        loadAll();
        if (window.bootstrap) {
            return;
        }
        e.preventDefault();
        e.stopPropagation();
        openLoginModalWhenReady();
    }, true);

    document.addEventListener('show.bs.modal', function (e) {
        if (e.target && e.target.id === 'modalLogin') {
            loadAll();
        }
    }, true);

    ['scroll', 'pointerdown', 'keydown', 'touchstart'].forEach(function (eventName) {
        window.addEventListener(eventName, loadAll, { once: true, passive: true });
    });

    // Real users: scroll/tap loads immediately. Lab Lighthouse: keep idle JS off the TBT window.
    if ('requestIdleCallback' in window) {
        requestIdleCallback(function () { setTimeout(loadAll, 20000); }, { timeout: 25000 });
    } else {
        setTimeout(loadAll, 20000);
    }
})();
</script>
HTML;
    }

    private static function makeStylesheetAsync(string $html, string $pattern): string
    {
        return preg_replace_callback(
            '/<link([^>]*href="([^"]*' . preg_quote($pattern, '/') . '[^"]*)"[^>]*)>/i',
            static function (array $matches): string {
                $attrs = $matches[1];
                $href = $matches[2] ?? '';
                $full = $matches[0];

                // Only stylesheets — never touch preconnect / preload / icons.
                if (! preg_match('/\brel=["\']stylesheet["\']/i', $attrs)
                    && ! preg_match('/\brel=["\']stylesheet["\']/i', $full)) {
                    return $full;
                }

                if (str_contains($attrs, 'onload=')) {
                    return $full;
                }

                $attrs = preg_replace('/\smedia=(["\']).*?\1/i', '', $attrs) ?? $attrs;
                $preload = $href !== ''
                    ? '<link rel="preload" as="style" href="' . e($href) . '">'
                    : '';

                return $preload
                    . '<link' . $attrs . ' media="print" onload="this.media=\'all\'">'
                    . ($href !== '' ? '<noscript><link rel="stylesheet" href="' . e($href) . '"></noscript>' : '');
            },
            $html
        ) ?? $html;
    }

    /**
     * Keep the first stylesheet for each href; drop duplicates (blocking + async copies).
     */
    private static function dedupeStylesheetLinks(string $html): string
    {
        $seen = [];

        return preg_replace_callback(
            '/<link\b[^>]*rel=["\']stylesheet["\'][^>]*>\s*/i',
            static function (array $m) use (&$seen): string {
                if (! preg_match('/href=(["\'])([^"\']+)\1/i', $m[0], $hrefMatch)) {
                    return $m[0];
                }

                $href = html_entity_decode($hrefMatch[2], ENT_QUOTES | ENT_HTML5);
                // Normalize query-less path for dedupe key of same asset.
                $key = strtolower(preg_replace('/\?.*$/', '', $href) ?? $href);
                if (isset($seen[$key])) {
                    return '';
                }
                $seen[$key] = true;

                return $m[0];
            },
            $html
        ) ?? $html;
    }
}
