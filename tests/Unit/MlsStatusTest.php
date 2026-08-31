<?php

namespace Tests\Unit;

use App\Support\MlsStatus;
use Tests\TestCase;

class MlsStatusTest extends TestCase
{
    /**
     * @return array<string, list<string>>
     */
    private function verifiedRawMap(): array
    {
        return [
            MlsStatus::EXPIRED => ['Expired', 'expired', ' EXPIRED ', "Expired\t"],
            MlsStatus::SUSPENDED => ['Suspended', 'suspended', ' SUSPENDED '],
            MlsStatus::CANCELLED => ['Cancelled', 'Canceled', 'cancelled', 'canceled', ' CANCELLED '],
            MlsStatus::TERMINATED => ['Terminated', 'terminated', ' TERMINATED '],
            MlsStatus::WITHDRAWN => ['Withdrawn', 'withdrawn', ' WITHDRAWN '],
        ];
    }

    public function test_verified_raw_codes_map_to_distinct_display_labels(): void
    {
        $seen = [];

        foreach ($this->verifiedRawMap() as $expected => $raws) {
            foreach ($raws as $raw) {
                $resolved = MlsStatus::resolve($raw);
                $this->assertSame($expected, $resolved['normalized_status'], $raw);
                $this->assertSame($expected, $resolved['display_label'], $raw);
                $this->assertTrue($resolved['is_delisted'], $raw);
                $this->assertFalse($resolved['is_active'], $raw);
                $this->assertFalse($resolved['is_sold'], $raw);
                $this->assertTrue($resolved['strike_price'], $raw);
            }
            $seen[] = $expected;
        }

        $this->assertSame(
            [MlsStatus::EXPIRED, MlsStatus::SUSPENDED, MlsStatus::CANCELLED, MlsStatus::TERMINATED, MlsStatus::WITHDRAWN],
            $seen
        );
        $this->assertCount(5, array_unique($seen));
    }

    public function test_the_five_delisted_labels_remain_distinct(): void
    {
        $labels = [
            MlsStatus::resolve('Expired')['display_label'],
            MlsStatus::resolve('Suspended')['display_label'],
            MlsStatus::resolve('Cancelled')['display_label'],
            MlsStatus::resolve('Terminated')['display_label'],
            MlsStatus::resolve('Withdrawn')['display_label'],
        ];

        $this->assertCount(5, array_unique($labels));
        $this->assertNotContains('Sold', $labels);
        $this->assertSame('Cancelled', MlsStatus::resolve('Canceled')['display_label']);
        $this->assertNotSame('Terminated', MlsStatus::resolve('Suspended')['display_label']);
        $this->assertNotSame('Withdrawn', MlsStatus::resolve('Cancelled')['display_label']);
        $this->assertNotSame('Cancelled', MlsStatus::resolve('Expired')['display_label']);
        $this->assertNotSame('Terminated', MlsStatus::resolve('Withdrawn')['display_label']);
    }

    public function test_unknown_unavailable_status_is_safe_and_never_active(): void
    {
        $resolved = MlsStatus::resolve('Off Market XYZ');

        $this->assertSame('Off Market XYZ', $resolved['raw_status']);
        $this->assertSame(MlsStatus::UNAVAILABLE, $resolved['normalized_status']);
        $this->assertSame(MlsStatus::UNAVAILABLE, $resolved['display_label']);
        $this->assertTrue($resolved['is_delisted']);
        $this->assertFalse($resolved['is_active']);
        $this->assertFalse($resolved['is_sold']);
        $this->assertNull($resolved['status_date']);
        $this->assertNotContains('Off Market XYZ', MlsStatus::activeQueryValues());
        $this->assertNotContains(MlsStatus::UNAVAILABLE, MlsStatus::activeQueryValues());
    }

    public function test_expired_status_date_uses_expire_date_only(): void
    {
        $expired = MlsStatus::resolve('Expired', ['expire_date' => '2026-08-15']);

        $this->assertSame('2026-08-15', $expired['status_date']);
        $this->assertSame('Aug 15, 2026', $expired['status_date_label']);
        $this->assertSame('expire_date', $expired['status_date_field']);
        $this->assertSame('Expired · Aug 15, 2026', $expired['compact_label']);
        $this->assertSame('Expired on August 15, 2026', MlsStatus::detailLine($expired));
        $this->assertSame('2026-08-15', MlsStatus::publicDateString('Expired', '2026-08-15'));
    }

    public function test_missing_invalid_and_future_dates_are_omitted(): void
    {
        $this->assertNull(MlsStatus::resolve('Expired')['status_date']);
        $this->assertNull(MlsStatus::resolve('Expired', ['expire_date' => null])['status_date']);
        $this->assertNull(MlsStatus::resolve('Expired', ['expire_date' => ''])['status_date']);
        $this->assertNull(MlsStatus::resolve('Expired', ['expire_date' => 'not-a-date'])['status_date']);
        $this->assertNull(MlsStatus::resolve('Expired', ['expire_date' => '1980-01-01'])['status_date']);
        $this->assertNull(MlsStatus::resolve('Expired', ['expire_date' => '2026-12-31'])['status_date']);
        $this->assertSame('Expired', MlsStatus::detailLine(MlsStatus::resolve('Expired')));
        $this->assertStringNotContainsString('on —', MlsStatus::detailLine(MlsStatus::resolve('Terminated')));
        $this->assertStringNotContainsString('on —', MlsStatus::detailLine(MlsStatus::resolve('Expired')));
    }

    public function test_non_expired_delisted_statuses_do_not_use_expire_or_close_date(): void
    {
        foreach (['Terminated', 'Suspended', 'Cancelled', 'Withdrawn'] as $raw) {
            $resolved = MlsStatus::resolve($raw, [
                'expire_date' => '2026-08-15',
                'close_date' => '2026-08-20',
            ]);
            $this->assertNull($resolved['status_date'], $raw);
            $this->assertNull($resolved['status_date_field'], $raw);
            $this->assertSame($raw === 'Cancelled' ? 'Cancelled' : $raw, $resolved['display_label']);
            $this->assertSame($resolved['display_label'], $resolved['compact_label']);
        }
    }

    public function test_suspended_can_return_to_active_after_a_later_source_status(): void
    {
        $suspended = MlsStatus::resolve('Suspended');
        $active = MlsStatus::resolve('New', ['transaction_type' => 'For Sale']);

        $this->assertTrue($suspended['is_delisted']);
        $this->assertFalse($suspended['is_active']);
        $this->assertTrue($active['is_active']);
        $this->assertFalse($active['is_delisted']);
        $this->assertSame('For Sale', $active['display_label']);
        $this->assertSame('For Lease', MlsStatus::resolve('Active', ['transaction_type' => 'For Lease'])['display_label']);
    }

    public function test_active_and_sold_filters_exclude_delisted_query_values(): void
    {
        $delisted = MlsStatus::delistedQueryValues();
        $sold = MlsStatus::soldQueryValues();
        $active = MlsStatus::activeQueryValues();
        $inactive = MlsStatus::inactiveQueryValues();

        foreach (['Expired', 'Suspended', 'Cancelled', 'Canceled', 'Terminated', 'Withdrawn'] as $value) {
            $this->assertContains($value, $delisted);
            $this->assertContains($value, $inactive);
            $this->assertNotContains($value, $sold);
            $this->assertNotContains($value, $active);
        }

        $this->assertContains('Sold', $sold);
        $this->assertNotContains('Expired', $sold);
        $this->assertContains('New', $active);
    }

    public function test_cards_and_detail_show_status_badge_not_strikethrough_only(): void
    {
        $grid = file_get_contents(base_path('platform/themes/homzen/views/real-estate/properties/item-grid.blade.php'));
        $home = file_get_contents(base_path('platform/themes/homzen/views/real-estate/properties/item-grid-home.blade.php'));
        $header = file_get_contents(base_path('platform/themes/homzen/views/real-estate/single-layouts/partials/header.blade.php'));
        $gallery = file_get_contents(base_path('platform/themes/homzen/views/real-estate/single-layouts/partials/gallery-slider.blade.php'));
        $list = file_get_contents(base_path('platform/themes/homzen/views/real-estate/properties/item-list.blade.php'));
        $wishlist = file_get_contents(base_path('platform/themes/homzen/views/real-estate/wishlist.blade.php'));
        $model = file_get_contents(base_path('platform/plugins/real-estate/src/Models/Property.php'));

        $this->assertStringContainsString('status_compact', $grid);
        $this->assertStringContainsString('serik-prop-card__badge', $grid);
        $this->assertStringContainsString("aria-label=\"{{ __('MLS status') }}", $grid);
        $this->assertStringContainsString("'delisted' => ! empty(\$card['is_delisted'])", $grid);
        $this->assertStringContainsString('status_compact', $home);
        $this->assertStringContainsString("aria-label=\"{{ __('MLS status') }}", $home);
        $this->assertStringContainsString("MlsStatus::forProperty(\$model)", $header);
        $this->assertStringContainsString('detailLine', $header);
        $this->assertStringContainsString("aria-label=\"{{ __('MLS status') }}", $header);
        $this->assertStringContainsString("MlsStatus::forProperty(\$property)", $gallery);
        $this->assertStringContainsString('status_html', $list);
        $this->assertStringContainsString("MlsStatus::forProperty(\$this)", $model);
        $this->assertStringContainsString('views.real-estate.properties.index', $wishlist);
    }

    public function test_map_api_and_cards_use_the_central_normalizer(): void
    {
        $map = file_get_contents(base_path('platform/themes/homzen/partials/shortcodes/hero-banner/styles/style-4.blade.php'));
        $api = file_get_contents(base_path('platform/plugins/real-estate/src/Http/Controllers/API/PropertyController.php'));

        $this->assertStringContainsString('window.SERIK_MLS_STATUS', $map);
        $this->assertStringContainsString('serikMlsResolve', $map);
        $this->assertStringContainsString('hs-list-card-status', $map);
        $this->assertStringContainsString('serikMlsDelistedQuery', $map);
        $this->assertStringContainsString("aria-label=\"MLS status:", $map);
        $this->assertStringContainsString('MlsStatus::delistedQueryValues()', $api);
        $this->assertStringContainsString("'status_date' => \\App\\Support\\MlsStatus::publicDateString", $api);
        $this->assertStringContainsString("'expire_date',", $api);
        $this->assertStringNotContainsString("props.transaction === 'Expired' || props.transaction === 'Terminated'", $map);
    }

    public function test_search_serialization_includes_raw_status_and_expire_date(): void
    {
        $model = file_get_contents(base_path('platform/plugins/real-estate/src/Models/Property.php'));
        $search = file_get_contents(base_path('platform/plugins/real-estate/src/Services/PropertySearchService.php'));
        $resource = file_get_contents(base_path('platform/plugins/real-estate/src/Http/Resources/PropertyResource.php'));

        $this->assertStringContainsString("'mls_status' => (string) \$this->MlsStatus", $model);
        $this->assertStringContainsString("'expire_date' => optional(\$this->expire_date)->toDateString()", $model);
        $this->assertStringContainsString("'expire_date'", $search);
        $this->assertStringContainsString("'mls_status_label'", $resource);
        $this->assertStringContainsString("'mls_status_date'", $resource);
    }

    public function test_cache_invalidates_when_mls_status_changes(): void
    {
        $observer = file_get_contents(base_path('app/Observers/PropertyHomepageCacheObserver.php'));
        $fragment = file_get_contents(base_path('app/Support/HomepageFragmentCache.php'));
        $api = file_get_contents(base_path('platform/plugins/real-estate/src/Http/Controllers/API/PropertyController.php'));
        $featured = file_get_contents(base_path('app/Actions/HomepageFeaturedPropertiesAction.php'));

        $this->assertStringContainsString("'MlsStatus'", $observer);
        $this->assertStringContainsString("'expire_date'", $observer);
        $this->assertStringContainsString('HomepageFeaturedCache::bump()', $observer);
        $this->assertStringContainsString('homepage_fragment_html_v4:', $fragment);
        $this->assertStringContainsString('map_meili_v16_', $api);
        $this->assertStringContainsString('map_v31_', $api);
        $this->assertStringContainsString("'Cancelled', 'Canceled', 'Withdrawn'", $featured);
    }

    public function test_older_imports_cannot_overwrite_a_newer_status(): void
    {
        $api = file_get_contents(base_path('platform/plugins/real-estate/src/Http/Controllers/API/PropertyController.php'));

        $this->assertStringContainsString('function ampItemUnchanged', $api);
        $this->assertStringContainsString('listing_modified_at', $api);
        $this->assertStringContainsString('greaterThanOrEqualTo(Carbon::parse($ampModified))', $api);
        $this->assertStringContainsString('Never overwrite newer local data', $api);
        $this->assertStringContainsString('ampModified->lessThanOrEqualTo($localModified)', $api);
    }

    public function test_restricted_sold_data_remains_vow_gated_and_delisted_is_not(): void
    {
        $map = file_get_contents(base_path('platform/themes/homzen/partials/shortcodes/hero-banner/styles/style-4.blade.php'));

        $this->assertStringContainsString('HARD RULE: login is ONLY for Sold/Leased history', $map);
        $this->assertStringContainsString("raw === 'unavailable'", $map);
        $this->assertStringContainsString('isSoldHistoryMlsStatus', file_get_contents(
            base_path('platform/themes/homzen/src/Supports/TrebPropertyHelper.php')
        ));
    }

    public function test_card_view_model_does_not_query_status_history(): void
    {
        $helper = file_get_contents(base_path('platform/themes/homzen/src/Supports/TrebPropertyHelper.php'));
        $start = strpos($helper, 'function listingCardViewModel');
        $this->assertNotFalse($start);
        $chunk = substr($helper, $start, 1800);

        $this->assertStringContainsString('MlsStatus::forProperty($property)', $chunk);
        $this->assertStringNotContainsString('PropertyHistory', $chunk);
        $this->assertStringNotContainsString('::query()', $chunk);
        $this->assertStringNotContainsString('DB::', $chunk);
    }

    public function test_frontend_config_is_the_single_js_mapping_source(): void
    {
        $config = MlsStatus::frontendConfig();

        $this->assertSame('Expired', $config['map']['expired']);
        $this->assertSame('Cancelled', $config['map']['canceled']);
        $this->assertSame('Cancelled', $config['map']['cancelled']);
        $this->assertContains('Canceled', $config['delisted_query']);
        $this->assertContains('Withdrawn', $config['delisted_query']);
        $this->assertSame(MlsStatus::UNAVAILABLE, $config['unavailable']);
    }
}
