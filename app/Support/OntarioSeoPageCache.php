<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

final class OntarioSeoPageCache
{
    public const TTL = 1800;

    public const VERSION = 'v18';

    /**
     * @return array<int, string>
     */
    public static function allowedQueryKeys(): array
    {
        return [
            'open_house', 'status', 'community', 'home_types', 'subtypes', 'page', 'bedroom', 'k',
            'type', 'per_page', 'min_price', 'max_price', 'bathroom', 'min_square', 'sort_by',
            'location',
        ];
    }

    public static function key(Request $request, string $seo, string $authPart = ':anon'): ?string
    {
        if (! $request->isMethod('GET') || $request->ajax() || $request->wantsJson()) {
            return null;
        }

        foreach ($request->query() as $key => $value) {
            if (! in_array($key, self::allowedQueryKeys(), true)) {
                return null;
            }
        }

        return 'ontario_seo_html_' . self::VERSION . $authPart . ':' . md5(strtolower($seo) . '|' . $request->getQueryString());
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

        return $cached;
    }

    public static function put(string $key, string $html): void
    {
        if ($html === '' || str_contains($html, 'No properties found.')) {
            return;
        }

        Cache::put($key, $html, self::TTL);
    }
}
