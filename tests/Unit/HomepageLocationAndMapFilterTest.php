<?php

namespace Tests\Unit;

use Tests\TestCase;

class HomepageLocationAndMapFilterTest extends TestCase
{
    public function test_featured_action_uses_geo_radius_and_active_sale_transaction(): void
    {
        $action = file_get_contents(base_path('app/Actions/HomepageFeaturedPropertiesAction.php'));

        $this->assertStringContainsString('geo_radius_km', $action);
        $this->assertStringContainsString('_geoPoint', $action);
        $this->assertStringContainsString("\$base['transaction'] = 'For Sale'", $action);
        $this->assertStringContainsString("\$base['status'] = 'Sold'", $action);
        $this->assertStringContainsString("'sort' => \$geoSort", $action);
        $this->assertStringContainsString('INACTIVE_STATUSES', $action);
        $this->assertStringContainsString('ontarioFallback()', $action);
    }

    public function test_quick_links_are_cached_by_city_not_user(): void
    {
        $nav = file_get_contents(base_path('app/Services/Seo/CityNavigationService.php'));

        $this->assertStringContainsString('seo_nav:v6:ontario_active_cities:{$origin}', $nav);
        $this->assertStringContainsString('seo_nav:v6:ontario_sold_cities:{$origin}', $nav);
        $this->assertStringContainsString('citiesNearFirst', $nav);
        $this->assertStringContainsString('SeoLandingUrl::housesForSale', $nav);
        $this->assertStringContainsString('SeoLandingUrl::soldHomes', $nav);
        $this->assertStringNotContainsString('auth(', $nav);
    }

    public function test_map_reads_canonical_transaction_query_parameter(): void
    {
        $map = file_get_contents(base_path('platform/themes/homzen/partials/shortcodes/hero-banner/styles/style-4.blade.php'));

        $this->assertStringContainsString("urlParams.get('transaction')", $map);
        $this->assertStringContainsString("decodedTransaction === 'For Lease'", $map);
        $this->assertStringContainsString('shouldUseIpMapCenter', $map);
        $this->assertStringContainsString('userHasMovedMap', $map);
        $this->assertStringContainsString("mActive.textContent = selectedTransaction === 'For Lease' ? 'For Lease' : 'For Sale'", $map);
    }

    public function test_search_service_supports_indexed_geo_radius_filters(): void
    {
        $search = file_get_contents(base_path('platform/plugins/real-estate/src/Services/PropertySearchService.php'));

        $this->assertStringContainsString('_geoRadius(', $search);
        $this->assertStringContainsString("opts['geo_lat']", $search);
        $this->assertStringContainsString("opts['geo_radius_km']", $search);
    }

    public function test_homepage_hydrate_endpoint_is_registered(): void
    {
        $routes = file_get_contents(base_path('platform/themes/homzen/routes/web.php'));
        $style = file_get_contents(base_path('platform/themes/homzen/partials/shortcodes/properties/styles/style-5.blade.php'));

        $this->assertStringContainsString('homepage-featured-properties', $routes);
        $this->assertStringContainsString('Recently sold and leased homes near', $style);
        $this->assertStringContainsString('data-hydrate-url', $style);
        $this->assertStringContainsString("params.push('lat='", $style);
        $this->assertStringContainsString('detectLocation', $style);

        $detector = file_get_contents(base_path('platform/themes/homzen/assets/js/visitor-location.js'));
        $this->assertStringContainsString("serik:visitor-location", $detector);
        $this->assertStringContainsString('dispatchEvent', $detector);
    }
}
