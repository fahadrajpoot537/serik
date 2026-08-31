<?php

namespace Tests\Unit;

use App\Support\OfficePhone;
use App\Support\PhoneNumberNormalizer;
use Tests\TestCase;

class NavbarPhoneCtaTest extends TestCase
{
    public function test_desktop_copy_button_is_not_a_tel_link(): void
    {
        $header = file_get_contents(base_path('platform/themes/homzen/partials/header.blade.php'));
        $script = file_get_contents(base_path('platform/themes/homzen/assets/js/script.js'));
        $chrome = file_get_contents(base_path('platform/themes/homzen/public/css/site-chrome.css'));

        $this->assertStringContainsString('data-serik-copy-phone', $header);
        $this->assertStringContainsString('serik-portal-nav__map--copy', $header);
        $this->assertStringContainsString('serik-portal-nav__map--tel', $header);
        $this->assertStringContainsString('href="tel:{{ $officePhoneE164 }}"', $header);
        $this->assertStringContainsString('tabindex="-1"', $header);
        $this->assertStringContainsString('data-serik-phone-live', $header);
        $this->assertStringContainsString('aria-live="polite"', $header);
        $this->assertStringContainsString('Phone number copied.', $script);
        $this->assertStringContainsString('Select and copy the number manually.', $script);
        $this->assertStringContainsString('navigator.clipboard.writeText', $script);
        $this->assertStringContainsString('.serik-portal-nav__map--copy', $chrome);
        $this->assertStringContainsString('@media (min-width: 992px)', $chrome);
        $this->assertStringContainsString('.serik-portal-nav__map--tel', $chrome);
        $this->assertStringNotContainsString('href="tel:+16475789400"', $header);
    }

    public function test_office_phone_normalizes_to_e164(): void
    {
        $this->assertSame('+16475789400', PhoneNumberNormalizer::normalize(OfficePhone::FALLBACK_DISPLAY));
        $this->assertSame(OfficePhone::FALLBACK_E164, PhoneNumberNormalizer::normalize(OfficePhone::FALLBACK_DISPLAY));
    }

    public function test_mobile_drawer_uses_tel_href(): void
    {
        $header = file_get_contents(base_path('platform/themes/homzen/partials/header.blade.php'));

        $this->assertStringContainsString('href="tel:{{ $mobileOfficeE164 }}"', $header);
    }
}
