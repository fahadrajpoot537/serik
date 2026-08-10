<?php

namespace App\Support;

use Illuminate\Contracts\Http\Kernel as HttpKernel;
use Illuminate\Http\Request;

/**
 * Background-only warm of public guest caches (map / Ontario SEO / place search).
 * Uses the same HTTP paths and services as live traffic — identical payloads.
 * Never warms authenticated or private data.
 */
final class ProductionCacheWarmer
{
    /**
     * Popular Ontario place prefixes (navbar / map supplement). Results cached 24h.
     *
     * @var list<string>
     */
    public const POPULAR_PLACE_QUERIES = [
        'Toronto',
        'Mississauga',
        'Brampton',
        'Vaughan',
        'Markham',
        'Oakville',
        'Burlington',
        'Hamilton',
        'Kitchener',
        'London',
        'Ottawa',
        'Richmond Hill',
        'Ajax',
        'Pickering',
        'Whitby',
        'Oshawa',
        'Milton',
        'Newmarket',
        'Barrie',
        'Guelph',
    ];

    /**
     * Guest map viewports — API expects south/north/west/east (not ne_lat style params).
     *
     * @var list<array{south: float, north: float, west: float, east: float, zoom: int}>
     */
    public const MAP_VIEWPORTS = [
        // Greater Toronto Area default
        ['south' => 43.45, 'north' => 43.95, 'west' => -79.85, 'east' => -79.05, 'zoom' => 10],
        // Mississauga / Peel
        ['south' => 43.45, 'north' => 43.75, 'west' => -79.85, 'east' => -79.45, 'zoom' => 11],
        // York Region
        ['south' => 43.75, 'north' => 44.05, 'west' => -79.60, 'east' => -79.20, 'zoom' => 11],
        // Ontario-wide overview
        ['south' => 41.70, 'north' => 46.50, 'west' => -95.00, 'east' => -74.50, 'zoom' => 6],
    ];

    /**
     * Public HTML paths that already have page-level caches (guest only).
     *
     * @var list<string>
     */
    public const PUBLIC_PATHS = [
        '/',
        '/map',
        '/ontario',
        '/properties',
    ];

    /**
     * @return list<array{step: string, ms: float, detail: string}>
     */
    public static function warm(bool $includeHomepage = true): array
    {
        $timings = [];

        if ($includeHomepage) {
            $timings = array_merge($timings, HomepageCacheWarmer::warm());
        }

        $timings = array_merge($timings, self::warmPublicPaths());
        $timings = array_merge($timings, self::warmMapApis());
        $timings = array_merge($timings, self::warmPlaceSearch());
        $timings = array_merge($timings, self::warmSeoNav());

        return $timings;
    }

    /**
     * Lighter pass for the existing every-10-minute homepage warm schedule.
     *
     * @return list<array{step: string, ms: float, detail: string}>
     */
    public static function warmLight(): array
    {
        $timings = [];
        $timings = array_merge($timings, self::warmMapApis(2));
        // Rotate through popular places so all cities warm over successive ticks.
        $offset = ((int) floor(time() / 600)) % max(1, count(self::POPULAR_PLACE_QUERIES));
        $slice = array_slice(self::POPULAR_PLACE_QUERIES, $offset, 8);
        if (count($slice) < 8) {
            $slice = array_merge($slice, array_slice(self::POPULAR_PLACE_QUERIES, 0, 8 - count($slice)));
        }
        $timings = array_merge($timings, self::warmPlaceSearchList($slice));
        $timings[] = self::runHttpStep('path:/map', '/map');

        return $timings;
    }

    /**
     * @return list<array{step: string, ms: float, detail: string}>
     */
    private static function warmPublicPaths(): array
    {
        $out = [];
        foreach (self::PUBLIC_PATHS as $path) {
            $out[] = self::runHttpStep('path:' . $path, $path);
        }

        return $out;
    }

    /**
     * @return list<array{step: string, ms: float, detail: string}>
     */
    private static function warmMapApis(int $limit = 0): array
    {
        $out = [];
        $viewports = $limit > 0
            ? array_slice(self::MAP_VIEWPORTS, 0, $limit)
            : self::MAP_VIEWPORTS;

        foreach ($viewports as $i => $vp) {
            $query = http_build_query($vp);
            $out[] = self::runHttpStep(
                'map_api:' . $i,
                '/api/v1/map-properties?' . $query,
                'application/json'
            );
        }

        return $out;
    }

    /**
     * @return list<array{step: string, ms: float, detail: string}>
     */
    private static function warmPlaceSearch(int $limit = 0): array
    {
        $queries = $limit > 0
            ? array_slice(self::POPULAR_PLACE_QUERIES, 0, $limit)
            : self::POPULAR_PLACE_QUERIES;

        return self::warmPlaceSearchList($queries);
    }

    /**
     * @param  list<string>  $queries
     * @return list<array{step: string, ms: float, detail: string}>
     */
    private static function warmPlaceSearchList(array $queries): array
    {
        $out = [];
        $search = app(OntarioPlaceSearch::class);
        foreach ($queries as $q) {
            $out[] = self::runStep('place:' . $q, static function () use ($search, $q): string {
                $key = 'serik_place_search_v1:' . md5(mb_strtolower($q) . '|5');
                if (\Illuminate\Support\Facades\Cache::has($key)) {
                    return 'already-cached';
                }

                // Nominatim polite use: ≤1 req/sec when filling cold keys only.
                usleep(1_100_000);
                $rows = $search->search($q, 5);

                return 'hits=' . count($rows);
            });
        }

        return $out;
    }

    /**
     * @return list<array{step: string, ms: float, detail: string}>
     */
    private static function warmSeoNav(): array
    {
        return [
            self::runStep('seo_nav:ontario_properties', static function (): string {
                $navigation = app(\App\Services\Seo\CityNavigationService::class);
                $data = $navigation->build('properties');
                $html = \Botble\Theme\Facades\Theme::partial('seo.city-navigation', $data) ?: '';
                $slug = $data['current_city']->slug ?? 'ontario';
                \Illuminate\Support\Facades\Cache::put(
                    "seo_nav_html:v7:properties:{$slug}:none",
                    $html,
                    (int) config('seo_navigation.cache_ttl', 3600)
                );

                return 'bytes=' . strlen($html);
            }),
        ];
    }

    /**
     * @return array{step: string, ms: float, detail: string}
     */
    private static function runHttpStep(string $step, string $uri, string $accept = 'text/html'): array
    {
        return self::runStep($step, static function () use ($uri, $accept): string {
            $kernel = app(HttpKernel::class);
            $request = self::guestRequest($uri, $accept);
            $response = $kernel->handle($request);
            $kernel->terminate($request, $response);

            return 'status=' . $response->getStatusCode()
                . ', bytes=' . strlen((string) $response->getContent())
                . ', cache=' . ($response->headers->get('X-Serik-Homepage-Cache') ?: 'n/a');
        });
    }

    /**
     * @return array{step: string, ms: float, detail: string}
     */
    private static function runStep(string $step, callable $callback): array
    {
        $t0 = microtime(true);

        try {
            $detail = (string) $callback();
        } catch (\Throwable $e) {
            $detail = 'error: ' . $e->getMessage();
        }

        return [
            'step' => $step,
            'ms' => round((microtime(true) - $t0) * 1000, 2),
            'detail' => $detail,
        ];
    }

    private static function guestRequest(string $uri, string $accept): Request
    {
        $appUrl = (string) config('app.url', 'http://localhost');
        $host = parse_url($appUrl, PHP_URL_HOST) ?: 'localhost';
        $scheme = parse_url($appUrl, PHP_URL_SCHEME) ?: 'http';
        $port = parse_url($appUrl, PHP_URL_PORT);
        $parts = parse_url($uri);
        $path = $parts['path'] ?? '/';
        $query = [];
        if (! empty($parts['query'])) {
            parse_str($parts['query'], $query);
        }

        $server = [
            'HTTP_HOST' => $host . ($port ? ':' . $port : ''),
            'SERVER_NAME' => $host,
            'REQUEST_URI' => $uri,
            'HTTPS' => $scheme === 'https' ? 'on' : 'off',
            'HTTP_ACCEPT' => $accept,
        ];

        return Request::create($path, 'GET', $query, [], [], $server);
    }
}
