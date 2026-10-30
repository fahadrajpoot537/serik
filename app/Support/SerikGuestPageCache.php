<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

/**
 * Full-page guest HTML cache for mostly-static CMS / blog routes.
 * Mirrors OntarioSeoPageCache — early HIT for anonymous visitors.
 */
final class SerikGuestPageCache
{
    public const TTL = 900;

    /** Shorter TTL when property HTML was cached before census warmed. */
    public const TTL_PROPERTY_COLD_CENSUS = 60;

    public const VERSION = 'v35';

    /**
     * Exact path prefixes (no trailing slash) eligible for guest HTML cache.
     *
     * @var list<string>
     */
    public const PATHS = [
        '/blogs',
        '/mortgage-calculator',
        '/cash-back-calculator',
        '/free-home-evaluation',
        '/tips-for-home-selling',
        '/evaluation',
    ];

    public static function matches(Request $request): bool
    {
        if (! $request->isMethod('GET') || $request->ajax() || $request->wantsJson()) {
            return false;
        }

        if ($request->query->count() > 0) {
            // Skip personalized / tracked query variants (utm etc. can be allowed later).
            foreach ($request->query->keys() as $key) {
                $k = strtolower((string) $key);
                if (str_starts_with($k, 'utm_') || $k === 'fbclid' || $k === 'gclid' || $k === 'msclkid') {
                    continue;
                }

                return false;
            }
        }

        $path = rtrim($request->getPathInfo(), '/') ?: '/';

        foreach (self::PATHS as $allowed) {
            if ($path === $allowed) {
                return true;
            }
        }

        // Guest property detail pages (anon HTML; CSRF refresh keeps forms valid).
        if (preg_match('#^/properties/[a-z0-9\-]+$#i', $path)) {
            return true;
        }

        return false;
    }

    public static function key(Request $request): ?string
    {
        if (! self::matches($request)) {
            return null;
        }

        $path = rtrim($request->getPathInfo(), '/') ?: '/';
        $origin = sha1(strtolower(rtrim($request->getSchemeAndHttpHost() . $request->getBaseUrl(), '/')));

        return 'serik_guest_html_' . self::VERSION . ':' . $origin . ':' . md5($path);
    }

    public static function get(?string $key): ?string
    {
        if ($key === null || $key === '') {
            return null;
        }

        $cached = Cache::get($key);
        if (! is_string($cached) || $cached === '') {
            return null;
        }

        $request = request();
        if ($request instanceof Request) {
            $cached = HomepageResponseCache::alignLoopbackOrigins($cached, $request);
        }

        // Property pages may have been cached while census was still cold.
        // If redis now has ok metrics, paint them into the HIT HTML immediately.
        $cached = self::hydrateCensusBootstrap($cached);

        return $cached;
    }

    public static function put(string $key, string $html): void
    {
        if ($html === '' || strlen($html) < 2000) {
            return;
        }

        // Never cache error / login-wall pages.
        if (
            str_contains($html, 'Whoops')
            || str_contains($html, 'Server Error')
            || str_contains($html, 'csrf-token-mismatch')
        ) {
            return;
        }

        $ttl = self::TTL;
        // Avoid locking guests into "Loading neighbourhood demographics…" for 15 minutes
        // after a cold first render — re-cache soon so SSR bootstrap can stick.
        if (
            str_contains($html, 'data-property-census-id=')
            && str_contains($html, 'data-census-bootstrapped="0"')
        ) {
            $ttl = self::TTL_PROPERTY_COLD_CENSUS;
        }

        Cache::put($key, $html, $ttl);
    }

    /**
     * Inject census metric cards + bootstrap JSON into guest HTML when cache is warm.
     */
    public static function hydrateCensusBootstrap(string $html): string
    {
        if (! str_contains($html, 'id="neighbourhoodDemographics"')) {
            return $html;
        }

        if (! str_contains($html, 'data-census-bootstrapped="0"')) {
            return $html;
        }

        if (! preg_match('/data-property-census-id="(\d+)"/', $html, $m)) {
            return $html;
        }

        $propertyId = (int) $m[1];
        if ($propertyId < 1) {
            return $html;
        }

        try {
            $payload = Cache::get('census:property:' . $propertyId . ':v12');
        } catch (\Throwable) {
            return $html;
        }

        if (! is_array($payload) || ($payload['status'] ?? null) !== 'ok') {
            return $html;
        }

        $metrics = $payload['metrics'] ?? null;
        if (! is_array($metrics) || $metrics === []) {
            return $html;
        }

        $cards = '';
        foreach ($metrics as $metric) {
            if (! is_array($metric)) {
                continue;
            }
            $label = e((string) ($metric['label'] ?? ''));
            $display = e((string) ($metric['display'] ?? ''));
            $cards .= '<div class="hs-census-card">'
                . '<div class="hs-census-label">' . $label . '</div>'
                . '<div class="hs-census-value">' . $display . '</div>'
                . '</div>';
        }

        if ($cards === '') {
            return $html;
        }

        $source = e((string) ($payload['source'] ?? 'Statistics Canada — 2021 Census'));
        $geoHtml = '';
        $level = (string) ($payload['geography_level'] ?? 'da');
        if ($level !== '' && $level !== 'da') {
            $levelLabel = match ($level) {
                'ada' => 'Aggregate dissemination area',
                'csd' => 'Census subdivision',
                default => $level,
            };
            $geoHtml = '<br>Geography: ' . e($levelLabel);
        }

        $bodyInner = '<div class="hs-census-grid">' . $cards . '</div>'
            . '<div class="hs-census-source">Source: ' . $source . $geoHtml . '</div>';

        $html = preg_replace(
            '/(<div class="hs-census-body">)(.*?)(<\/div>\s*<\/section>)/s',
            '$1' . $bodyInner . '$3',
            $html,
            1
        ) ?? $html;

        $html = str_replace(
            [
                'data-census-bootstrapped="0"',
                'aria-busy="true"',
                ' hs-census-section is-loading',
                'hs-census-section is-loading',
            ],
            [
                'data-census-bootstrapped="1"',
                'aria-busy="false"',
                ' hs-census-section',
                'hs-census-section',
            ],
            $html
        );

        $json = json_encode($payload, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_UNESCAPED_UNICODE);
        if (is_string($json) && $json !== '') {
            $bootTag = '<script type="application/json" id="hsCensusBootstrap">' . $json . '</script>';
            if (str_contains($html, 'id="hsCensusBootstrap"')) {
                $html = preg_replace(
                    '/<script type="application\/json" id="hsCensusBootstrap">.*?<\/script>/s',
                    $bootTag,
                    $html,
                    1
                ) ?? $html;
            } else {
                $html = str_replace(
                    '</section>' . "\n" . '<script src=',
                    '</section>' . "\n" . $bootTag . "\n" . '<script src=',
                    $html
                );
                // Fallback if whitespace differs: insert before Chart.js script.
                if (! str_contains($html, 'id="hsCensusBootstrap"')) {
                    $html = preg_replace(
                        '/(<script[^>]+chart\.umd[^>]*>)/',
                        $bootTag . "\n$1",
                        $html,
                        1
                    ) ?? $html;
                }
            }
        }

        return $html;
    }
}
