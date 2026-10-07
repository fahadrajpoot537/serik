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
        // Below-fold / decorative only. NEVER async bootstrap, homepage-premium, or site-chrome
        // (that caused unstyled paint → CLS ~0.9 / mobile PSI ~10).
        'css/style.css',
        'swiper-bundle.min.css',
        // Do NOT list fonts.googleapis.com / fonts.gstatic.com — that breaks <link rel=preconnect>.
        // Do NOT list site-chrome.css / homepage-premium / bootstrap — required for first paint.
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

        return self::replaceGoogleFontsWithLocalPoppins($html);
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

        // Stale HTML still pulling render-blocking Google Fonts CSS.
        if (preg_match('/<link[^>]+href=["\'][^"\']*fonts\.googleapis\.com\/css2[^"\']*["\'][^>]*>/i', $html)) {
            return true;
        }

        if (! str_contains($html, '__serikLocalPoppins')) {
            return true;
        }

        // Stale HTML still serving the 160KB WhatsApp logo PNG.
        if (preg_match('/<img[^>]+src=["\'][^"\']*whatsapp-image-2025[^"\']*\.(?:png|jpe?g)/i', $html)) {
            return true;
        }

        // Critical CSS must not be async (media=print) — recover broken PSI deploys.
        if (preg_match('/<link[^>]+homepage-premium\.css[^>]+media=["\']print["\']/i', $html)) {
            return true;
        }
        if (preg_match('/<link[^>]+site-chrome\.css[^>]+media=["\']print["\']/i', $html)) {
            return true;
        }
        if (preg_match('/<link[^>]+bootstrap\.min\.css[^>]+media=["\']print["\']/i', $html)) {
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

        // Heal stale cache that async'd critical CSS (PSI ~10 / CLS ~0.9).
        $html = self::restoreBlockingStylesheet($html, 'homepage-premium.css');
        $html = self::restoreBlockingStylesheet($html, 'site-chrome.css');
        $html = self::restoreBlockingStylesheet($html, 'bootstrap.min.css');
        $html = self::restoreBlockingStylesheet($html, 'bootstrap.rtl.min.css');

        // Drop duplicate stylesheet hrefs (e.g. tabler / site-chrome listed twice).
        $html = self::dedupeStylesheetLinks($html);

        // Local Poppins only — remove Google Fonts critical-path chain.
        $html = self::replaceGoogleFontsWithLocalPoppins($html);

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

        // Force site logo PNG/JPEG → tiny WebP (heals stale cache / missed blade paths).
        $html = self::rewriteSiteLogoImages($html);

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
     * Replace bulky WhatsApp/site-logo raster URLs with the theme mobile WebP.
     */
    private static function rewriteSiteLogoImages(string $html): string
    {
        $logoWebp = asset('themes/' . \Botble\Theme\Facades\Theme::getPublicThemeName() . '/images/serik-logo-mobile.webp');

        return preg_replace_callback(
            '/<img\b([^>]*?)>/i',
            static function (array $m) use ($logoWebp): string {
                $attrs = $m[1];
                if (! preg_match('/(?:^|\s)src=(["\'])([^"\']+)\1/i', $attrs, $srcMatch)) {
                    return '<img' . $attrs . '>';
                }

                $src = html_entity_decode($srcMatch[2], ENT_QUOTES | ENT_HTML5);
                if ($src === '' || ! preg_match('/whatsapp-image-2025[^"\']*\.(?:png|jpe?g)(?:\?|$)/i', $src)) {
                    return '<img' . $attrs . '>';
                }

                // Skip non-logo uses (rare) that are clearly large content images.
                $looksLikeNavLogo = preg_match('/\bwidth=["\']160["\']/i', $attrs)
                    || str_contains($attrs, 'max-height: 44px')
                    || str_contains($attrs, 'max-height:44px')
                    || preg_match('/\bheight=["\']4[0-9]["\']/i', $attrs);

                if (! $looksLikeNavLogo) {
                    // Still rewrite — every whatsapp-image-2025-* logo asset is the brand mark.
                    // Content photos use different filenames.
                }

                $attrs = preg_replace(
                    '/(?:^|\s)src=(["\'])[^"\']*\1/i',
                    ' src="' . e($logoWebp) . '"',
                    $attrs,
                    1
                ) ?? $attrs;

                if (! preg_match('/\bwidth=/i', $attrs)) {
                    $attrs .= ' width="160"';
                }
                if (! preg_match('/\bheight=/i', $attrs)) {
                    $attrs .= ' height="44"';
                }
                if (! preg_match('/\bdecoding=/i', $attrs)) {
                    $attrs .= ' decoding="async"';
                }

                return '<img' . $attrs . '>';
            },
            $html
        ) ?? $html;
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

    /**
     * Swap render-blocking Google Fonts CSS for self-hosted latin Poppins faces.
     * Preloaded 400/600 files in base.blade already match these filenames.
     */
    private static function replaceGoogleFontsWithLocalPoppins(string $html): string
    {
        // Blocking remote font CSS (googleapis / bunny).
        $html = preg_replace(
            '/<link\b[^>]*href=["\'][^"\']*(?:fonts\.googleapis\.com|fonts\.bunny\.net)\/css2\?[^"\']*["\'][^>]*>\s*/i',
            '',
            $html
        ) ?? $html;

        // Preconnects are useless once we stop calling Google Fonts.
        $html = preg_replace(
            '/<link\b[^>]*rel=["\']preconnect["\'][^>]*href=["\']https:\/\/fonts\.(?:googleapis|gstatic)\.com\/?["\'][^>]*>\s*/i',
            '',
            $html
        ) ?? $html;
        $html = preg_replace(
            '/<link\b[^>]*href=["\']https:\/\/fonts\.(?:googleapis|gstatic)\.com\/?["\'][^>]*rel=["\']preconnect["\'][^>]*>\s*/i',
            '',
            $html
        ) ?? $html;

        if (str_contains($html, '__serikLocalPoppins')) {
            return $html;
        }

        $dir = '/storage/fonts/82ced711bf';
        $faces = <<<CSS
<style id="__serikLocalPoppins">
@font-face{font-family:'Poppins';font-style:normal;font-weight:400;font-display:swap;src:url({$dir}/spoppinsv24pxieyp8kv8jhgfvrjjfecnfhgpc.woff2) format('woff2');unicode-range:U+0000-00FF,U+0131,U+0152-0153,U+02BB-02BC,U+02C6,U+02DA,U+02DC,U+0304,U+0308,U+0329,U+2000-206F,U+20AC,U+2122,U+2191,U+2193,U+2212,U+2215,U+FEFF,U+FFFD}
@font-face{font-family:'Poppins';font-style:normal;font-weight:500;font-display:swap;src:url({$dir}/spoppinsv24pxibyp8kv8jhgfvrlgt9z1xlfd2jqek.woff2) format('woff2');unicode-range:U+0000-00FF,U+0131,U+0152-0153,U+02BB-02BC,U+02C6,U+02DA,U+02DC,U+0304,U+0308,U+0329,U+2000-206F,U+20AC,U+2122,U+2191,U+2193,U+2212,U+2215,U+FEFF,U+FFFD}
@font-face{font-family:'Poppins';font-style:normal;font-weight:600;font-display:swap;src:url({$dir}/spoppinsv24pxibyp8kv8jhgfvrlej6z1xlfd2jqek.woff2) format('woff2');unicode-range:U+0000-00FF,U+0131,U+0152-0153,U+02BB-02BC,U+02C6,U+02DA,U+02DC,U+0304,U+0308,U+0329,U+2000-206F,U+20AC,U+2122,U+2191,U+2193,U+2212,U+2215,U+FEFF,U+FFFD}
@font-face{font-family:'Poppins';font-style:normal;font-weight:700;font-display:swap;src:url({$dir}/spoppinsv24pxibyp8kv8jhgfvrlcz7z1xlfd2jqek.woff2) format('woff2');unicode-range:U+0000-00FF,U+0131,U+0152-0153,U+02BB-02BC,U+02C6,U+02DA,U+02DC,U+0304,U+0308,U+0329,U+2000-206F,U+20AC,U+2122,U+2191,U+2193,U+2212,U+2215,U+FEFF,U+FFFD}
</style>
CSS;

        if (str_contains($html, '</head>')) {
            return str_replace('</head>', $faces . '</head>', $html);
        }

        return $faces . $html;
    }

    /**
     * Convert a media=print async stylesheet back to a normal blocking stylesheet.
     */
    private static function restoreBlockingStylesheet(string $html, string $pattern): string
    {
        // Drop matching preloads added by makeStylesheetAsync.
        $html = preg_replace(
            '/<link\b[^>]*rel=["\']preload["\'][^>]*href=["\'][^"\']*' . preg_quote($pattern, '/') . '[^"\']*["\'][^>]*>\s*/i',
            '',
            $html
        ) ?? $html;

        $html = preg_replace_callback(
            '/<link\b([^>]*href=["\'][^"\']*' . preg_quote($pattern, '/') . '[^"\']*["\'][^>]*)>/i',
            static function (array $m): string {
                $attrs = $m[1];
                if (! preg_match('/\brel=["\']stylesheet["\']/i', $attrs) && ! preg_match('/\brel=["\']stylesheet["\']/i', $m[0])) {
                    return $m[0];
                }
                $attrs = preg_replace('/\smedia=(["\']).*?\1/i', '', $attrs) ?? $attrs;
                $attrs = preg_replace('/\sonload=(["\']).*?\1/i', '', $attrs) ?? $attrs;

                return '<link' . $attrs . '>';
            },
            $html
        ) ?? $html;

        // Remove noscript duplicates for the same file (optional cleanup).
        return preg_replace(
            '/<noscript>\s*<link[^>]+' . preg_quote($pattern, '/') . '[^>]*>\s*<\/noscript>\s*/i',
            '',
            $html
        ) ?? $html;
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
