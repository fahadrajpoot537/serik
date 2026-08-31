<?php

namespace Tests\Unit;

use Tests\TestCase;

class HeaderSearchNavGuardTest extends TestCase
{
    public function test_header_search_and_mega_menu_share_search_active_guard(): void
    {
        $header = file_get_contents(base_path('platform/themes/homzen/partials/header.blade.php'));
        $menu = file_get_contents(base_path('platform/themes/homzen/partials/main-menu.blade.php'));
        $cache = file_get_contents(base_path('app/Support/HomepageFragmentCache.php'));
        $chrome = file_get_contents(base_path('platform/themes/homzen/public/css/site-chrome.css'));
        $a11y = file_get_contents(base_path('platform/themes/homzen/public/js/keyboard-a11y.js'));

        $this->assertStringContainsString('serik-header-search-active', $header);
        $this->assertStringContainsString('serikHeaderSearchSync', $header);
        $this->assertStringContainsString('__serikHeaderSearchNavGuard', $header);
        $this->assertStringContainsString('pointerInSearch', $header);
        $this->assertStringContainsString('isHeaderSearchPanelOpen', $header);
        $this->assertStringContainsString("getElementById('smartInput')", $menu);
        $this->assertStringContainsString('isHeaderSearchActive', $menu);
        $this->assertStringContainsString("classList.contains('serik-header-search-active')", $menu);
        $this->assertStringContainsString('isHeaderSearchActive()', $menu);
        $this->assertStringContainsString('isHeaderSearchPanelOpen', $menu);
        $this->assertStringContainsString('closeMegaMenu();', $menu);
        $this->assertStringContainsString("classList.add('serik-mega-portal', 'is-mega-open')", $menu);
        $this->assertStringContainsString('mega-v8', $cache);
        $this->assertStringContainsString('html.serik-header-search-active .mega-dropdown.serik-mega-portal', $chrome);
        $this->assertStringContainsString('html.serik-header-search-active #page-home .main-header .main-menu .navigation > li:hover > ul', $chrome);
        $this->assertStringContainsString('html.serik-header-search-active .has-dropdown.is-active > .mega-dropdown', $chrome);
        $this->assertStringContainsString('serikHeaderSearchSync', $a11y);
        $this->assertStringContainsString('html.serik-header-search-active .mega-dropdown', $menu);
        $this->assertStringContainsString('__serikMegaMenuInit', $menu);
    }
}
