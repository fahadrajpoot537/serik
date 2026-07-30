<?php

namespace App\Http\Controllers;

use App\Support\SeoLandingParser;
use Botble\RealEstate\Http\Controllers\Fronts\PublicController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class OntarioSeoLandingController extends Controller
{
    private const PAGE_CACHE_TTL = 1800; // Reduce MISS frequency; nav personalization is async.

    public function show(Request $request, string $seo)
    {
        // Capture client intent BEFORE SEO defaults touch the request.
        $explicitType = strtolower(trim((string) $request->input('type', '')));
        $explicitStatus = strtolower(trim((string) $request->input('status', '')));

        $parsed = SeoLandingParser::toFilterParams($seo);

        // Never let city_id / city slug blank MLS results (city_id is unset on listings).
        unset($parsed['city_id'], $parsed['city']);

        // SEO slug supplies defaults only. Explicit query/body params must win
        // (AJAX For Lease on a …-for-sale landing sends type=rent).
        $seoDefaults = [];
        foreach ($parsed as $key => $value) {
            if (! $request->filled($key)) {
                $seoDefaults[$key] = $value;
            }
        }
        if ($seoDefaults !== []) {
            $request->merge($seoDefaults);
        }

        // Hard guarantee: never let SEO …-for-sale overwrite an explicit lease/sale choice.
        if (in_array($explicitType, ['rent', 'lease'], true)) {
            $request->merge(['type' => 'rent']);
        } elseif ($explicitType === 'sale') {
            $request->merge(['type' => 'sale']);
        }
        if ($explicitStatus === 'sold') {
            $request->merge(['status' => 'sold', 'type' => '']);
        }

        // Lean listing pages: 12 cards per page.
        if (! $request->filled('per_page')) {
            $request->merge(['per_page' => 12]);
        }

        $cacheKey = $this->pageCacheKey($request, $seo);
        if ($cacheKey !== null) {
            $cached = Cache::get($cacheKey);
            if (is_string($cached) && $cached !== '') {
                return response($cached, 200, [
                    'Content-Type' => 'text/html; charset=UTF-8',
                    'X-Serik-Ontario-Cache' => 'HIT',
                ]);
            }
        }

        $result = app(PublicController::class)->getProperties($request);

        if ($cacheKey === null) {
            return $result;
        }

        $html = null;
        if (is_string($result)) {
            $html = $result;
        } elseif ($result instanceof \Symfony\Component\HttpFoundation\Response) {
            $html = $result->getContent();
        } elseif (is_object($result) && method_exists($result, '__toString')) {
            $html = (string) $result;
        }

        if (! is_string($html) || $html === '') {
            return $result;
        }

        Cache::put($cacheKey, $html, self::PAGE_CACHE_TTL);

        return response($html, 200, [
            'Content-Type' => 'text/html; charset=UTF-8',
            'X-Serik-Ontario-Cache' => 'MISS',
        ]);
    }

    private function pageCacheKey(Request $request, string $seo): ?string
    {
        if (! $request->isMethod('GET') || $request->ajax() || $request->wantsJson()) {
            return null;
        }

        // Render output differs for authenticated users (e.g. sold card gating), so keep separate caches.
        $accountAuthed = is_plugin_active('real-estate') ? auth('account')->check() : false;
        $isAuthed = (bool) ($request->user() || $accountAuthed || auth()->check());
        $authPart = $isAuthed ? ':auth' : ':anon';

        // Cache SEO landings including common filter query params (fast repeat visits).
        $allowed = [
            'open_house', 'status', 'community', 'home_types', 'subtypes', 'page', 'bedroom', 'k',
            'type', 'per_page', 'min_price', 'max_price', 'bathroom', 'min_square', 'sort_by',
            'location', // toolbar / lease-sale switches always send location=
        ];
        foreach ($request->query() as $key => $value) {
            if (! in_array($key, $allowed, true)) {
                return null;
            }
        }

        // v12: lease AJAX + location-aware keys; never serve stale sale HTML for type=rent.
        return 'ontario_seo_html_v12' . $authPart . ':' . md5(strtolower($seo) . '|' . $request->getQueryString());
    }
}
