<?php

namespace App\Http\Controllers;

use App\Support\OntarioSeoPageCache;
use App\Support\SeoLandingParser;
use Botble\RealEstate\Http\Controllers\Fronts\PublicController;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class OntarioSeoLandingController extends Controller
{
    public function show(Request $request, string $seo)
    {
        $cacheKey = $this->pageCacheKey($request, $seo);
        if ($cacheKey !== null) {
            $cached = OntarioSeoPageCache::get($cacheKey);
            if ($cached !== null) {
                return $this->cachedResponse($cached, 'HIT');
            }
        }

        // Capture client intent BEFORE SEO defaults touch the request.
        $explicitType = strtolower(trim((string) $request->input('type', '')));
        $explicitStatus = strtolower(trim((string) $request->input('status', '')));

        $parsed = SeoLandingParser::toFilterParams($seo);

        // Never let city_id / city slug blank MLS results (city_id is unset on listings).
        unset($parsed['city_id'], $parsed['city']);

        // Neighborhood pages (?community=) must list that community, not SEO house-only
        // subtypes (Waterfront C1 is almost all condos — house filter shows 0 cards).
        if ($request->filled('community')) {
            unset($parsed['home_types']);
        }

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

        $result = app(PublicController::class)->getProperties($request);

        if ($cacheKey === null) {
            return $result;
        }

        $html = null;
        if (is_string($result)) {
            $html = $result;
        } elseif ($result instanceof Response) {
            $html = $result->getContent();
        } elseif (is_object($result) && method_exists($result, '__toString')) {
            $html = (string) $result;
        }

        if (! is_string($html) || $html === '') {
            return $result;
        }

        OntarioSeoPageCache::put($cacheKey, $html);

        return $this->cachedResponse($html, 'MISS');
    }

    private function cachedResponse(string $html, string $cacheStatus): Response
    {
        return response($html, 200, [
            'Content-Type' => 'text/html; charset=UTF-8',
            'X-Serik-Ontario-Cache' => $cacheStatus,
            'Cache-Control' => 'private, no-cache, no-store, must-revalidate',
            'Vary' => 'Cookie',
        ]);
    }

    private function pageCacheKey(Request $request, string $seo): ?string
    {
        $accountAuthed = is_plugin_active('real-estate') ? auth('account')->check() : false;
        $isAuthed = (bool) ($request->user() || $accountAuthed || auth()->check());
        $authPart = $isAuthed ? ':auth' : ':anon';

        return OntarioSeoPageCache::key($request, $seo, $authPart);
    }
}
