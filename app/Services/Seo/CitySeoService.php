<?php

namespace App\Services\Seo;

use App\Support\SeoLandingUrl;
use Botble\Location\Models\City;
use Botble\SeoHelper\Facades\SeoHelper;
use Illuminate\Http\Request;

/**
 * Per-city SEO metadata, canonical URLs, and schema markup.
 */
final class CitySeoService
{
    public function __construct(
        private readonly CityResolutionService $cityResolution,
    ) {
    }

    public function apply(Request $request): void
    {
        $city = $this->cityResolution->resolve($request);
        if (! $city) {
            return;
        }

        $isSold = $request->boolean('sold') || $request->input('status') === 'sold';
        $name = $city->name;
        $count = (int) ($city->property_count ?? 0);

        $title = $isSold
            ? "Sold Homes in {$name}, Ontario | Serik Realty"
            : "Homes For Sale in {$name}, Ontario | Serik Realty";

        $description = $isSold
            ? "Browse recently sold and leased properties in {$name}, Ontario. View sold home prices and market activity with Serik Realty."
            : "Search {$count}+ homes for sale in {$name}, Ontario. Filter by price, beds, baths and property type. Updated MLS listings from Serik Realty.";

        SeoHelper::setTitle($title);
        SeoHelper::meta()->setDescription($description);
        SeoHelper::meta()->addMeta('robots', 'index, follow');

        $canonical = $this->canonicalUrl($city, $request);
        SeoHelper::meta()->setUrl($canonical);
    }

    public function canonicalUrl(City $city, ?Request $request = null): string
    {
        $request ??= request();

        if ($request->routeIs('public.properties-by-city')) {
            return route('public.properties-by-city', $city->slug);
        }

        if ($request->routeIs('public.seo.ontario')) {
            return SeoLandingUrl::housesForSale($city);
        }

        return SeoLandingUrl::housesForSale($city);
    }
}
