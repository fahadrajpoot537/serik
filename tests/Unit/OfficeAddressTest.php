<?php

namespace Tests\Unit;

use App\Support\OfficeAddress;
use Tests\TestCase;

class OfficeAddressTest extends TestCase
{
    public function test_footer_uses_official_configured_or_widget_address(): void
    {
        config(['serik.office.address' => '5800 Keaton Cres Mississauga, ON L5R 3K2']);
        config(['serik.office.maps_place_url' => '']);

        $this->assertSame('5800 Keaton Cres Mississauga, ON L5R 3K2', OfficeAddress::display());
        $this->assertSame(
            'https://www.google.com/maps/search/?api=1&query=' . rawurlencode('5800 Keaton Cres Mississauga, ON L5R 3K2'),
            OfficeAddress::mapsUrl()
        );
    }

    public function test_configured_place_url_is_preferred(): void
    {
        config([
            'serik.office.address' => '5800 Keaton Cres Mississauga, ON L5R 3K2',
            'serik.office.maps_place_url' => 'https://maps.google.com/?cid=12345',
        ]);

        $this->assertSame('https://maps.google.com/?cid=12345', OfficeAddress::mapsUrl());
    }

    public function test_footer_markup_is_keyboard_accessible_and_external_safe(): void
    {
        $blade = file_get_contents(base_path('platform/themes/homzen/widgets/site-information/templates/frontend.blade.php'));

        $this->assertStringContainsString('OfficeAddress::mapsUrl()', $blade);
        $this->assertStringContainsString('target="_blank"', $blade);
        $this->assertStringContainsString('rel="noopener noreferrer"', $blade);
        $this->assertStringContainsString('OfficeAddress::MAPS_LABEL', $blade);
        $this->assertSame('Open Serik Realty office in Google Maps', OfficeAddress::MAPS_LABEL);
        $this->assertStringContainsString('serik-footer-address-link', $blade);
    }

    public function test_map_pin_items_are_detected_as_address(): void
    {
        $this->assertTrue(OfficeAddress::isAddressItem('ti ti-map-pin', '5800 Keaton Cres Mississauga, ON L5R 3K2'));
        $this->assertFalse(OfficeAddress::isAddressItem('ti ti-phone-call', '+1 (647) 578-9400'));
    }
}
