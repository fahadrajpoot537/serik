<?php

namespace App\Http\Controllers;

use App\Services\Seo\CityNavigationService;
use App\Services\Seo\CityResolutionService;
use Botble\Location\Models\City;
use Botble\Theme\Facades\Theme;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class SeoCityNavigationController extends Controller
{
    public function __invoke(
        Request $request,
        CityNavigationService $navigation,
        CityResolutionService $cityResolution
    ) {
        $context = $request->input('context') === 'home' ? 'home' : 'properties';
        $slug = trim((string) $request->input('city', ''));
        $community = trim((string) $request->input('community', ''));

        $city = null;
        if ($slug !== '' && ! in_array(strtolower($slug), ['ontario', 'on', 'auto'], true)) {
            $city = City::query()
                ->where('slug', Str::slug($slug))
                ->where('is_active', true)
                ->first(['id', 'name', 'slug', 'latitude', 'longitude', 'property_count', 'is_major']);
        }

        if (! $city && $context === 'home') {
            $city = $cityResolution->resolveForHome($request);
        }

        $cacheSlug = $city?->slug ?? ($slug !== '' ? Str::slug($slug) : 'ontario');
        $communityKey = $community !== '' ? md5(mb_strtolower($community)) : 'none';
        $cacheKey = "seo_nav_html:v11:{$context}:{$cacheSlug}:{$communityKey}";

        $html = Cache::remember($cacheKey, (int) config('seo_navigation.cache_ttl', 3600), function () use ($navigation, $context, $city, $community) {
            $data = $navigation->build($context, $city, $community !== '' ? $community : null);

            return Theme::partial('seo.city-navigation', $data) ?: '';
        });

        return response($html, 200, [
            'Content-Type' => 'text/html; charset=UTF-8',
            'Cache-Control' => 'public, max-age=600, stale-while-revalidate=86400',
        ]);
    }
}
