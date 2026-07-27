<?php

namespace App\Services\Seo;

use Botble\Location\Models\City;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Central city resolution from URL, filters, and route context.
 */
final class CityResolutionService
{
    public function resolve(?Request $request = null): ?City
    {
        $request ??= request();

        if ($city = $this->fromRoute($request)) {
            return $city;
        }

        if ($city = $this->fromRequestParams($request)) {
            return $city;
        }

        if ($city = $this->fromOntarioSeoPath($request)) {
            return $city;
        }

        return null;
    }

    public function resolveForHome(?Request $request = null): ?City
    {
        $request ??= request();

        if ($city = $this->fromVisitorCookie()) {
            return $city;
        }

        $slug = (string) config('seo_navigation.default_home_city_slug', 'toronto');

        return $this->findActiveCity($slug);
    }

    private function fromVisitorCookie(): ?City
    {
        if (! class_exists(\Theme\homzen\Supports\VisitorCityHelper::class)) {
            return null;
        }

        $name = \Theme\homzen\Supports\VisitorCityHelper::get();
        if ($name === null || trim($name) === '') {
            return null;
        }

        return $this->findActiveCityByName(trim($name));
    }

    public function resolveName(?Request $request = null): ?string
    {
        $city = $this->resolve($request);

        return $city?->name;
    }

    public function resolveSlug(?Request $request = null): ?string
    {
        $city = $this->resolve($request);

        return $city?->slug;
    }

    private function fromOntarioSeoPath(Request $request): ?City
    {
        $path = trim(strtolower($request->path()), '/');

        if (preg_match('#^ontario/(.+)$#', $path, $matches)) {
            $citySlug = \App\Support\SeoLandingUrl::parseCitySlugFromSeo($matches[1]);
            if ($citySlug && $citySlug !== 'ontario') {
                return $this->findActiveCity($citySlug);
            }
        }

        $seo = trim((string) $request->input('seo', ''));
        if ($seo !== '') {
            $citySlug = \App\Support\SeoLandingUrl::parseCitySlugFromSeo($seo);
            if ($citySlug && $citySlug !== 'ontario') {
                return $this->findActiveCity($citySlug);
            }
        }

        return null;
    }

    private function fromRoute(Request $request): ?City
    {
        $route = $request->route();
        if (! $route) {
            return null;
        }

        $slug = $route->parameter('slug') ?? $route->parameter('city');
        if (! is_string($slug) || trim($slug) === '') {
            return null;
        }

        $routeName = (string) $route->getName();
        if (! str_contains($routeName, 'properties-by-city') && ! str_contains($routeName, 'projects-by-city')) {
            return null;
        }

        return $this->findActiveCity($slug);
    }

    private function fromRequestParams(Request $request): ?City
    {
        $cityId = (int) $request->input('city_id');
        if ($cityId > 0) {
            return City::query()
                ->where('id', $cityId)
                ->where('is_active', true)
                ->first(['id', 'name', 'slug', 'latitude', 'longitude', 'property_count', 'is_major']);
        }

        $slug = trim((string) $request->input('city', ''));
        if ($slug !== '' && ! in_array(strtolower($slug), ['ontario', 'on'], true)) {
            if ($city = $this->findActiveCity($slug)) {
                return $city;
            }
        }

        $location = trim((string) $request->input('location', ''));
        if ($location !== '') {
            $name = $this->extractCityName($location);
            if ($name !== '') {
                return $this->findActiveCityByName($name);
            }
        }

        return null;
    }

    private function findActiveCity(string $slug): ?City
    {
        return City::query()
            ->where('slug', Str::slug($slug))
            ->where('is_active', true)
            ->first(['id', 'name', 'slug', 'latitude', 'longitude', 'property_count', 'is_major']);
    }

    private function findActiveCityByName(string $name): ?City
    {
        $normalized = Str::title(trim($name));

        return City::query()
            ->where('is_active', true)
            ->where(function ($q) use ($normalized, $name): void {
                $q->where('name', $normalized)
                    ->orWhere('name', 'like', $normalized . '%');
            })
            ->orderByDesc('property_count')
            ->first(['id', 'name', 'slug', 'latitude', 'longitude', 'property_count', 'is_major']);
    }

    private function extractCityName(string $location): string
    {
        $location = trim($location);
        if ($location === '') {
            return '';
        }

        $parts = array_map('trim', explode(',', $location));
        if (count($parts) >= 2) {
            return $parts[count($parts) - 2] ?? $parts[0];
        }

        return $parts[0];
    }
}
