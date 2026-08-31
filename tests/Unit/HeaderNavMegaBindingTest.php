<?php

namespace Tests\Unit;

use Tests\TestCase;

class HeaderNavMegaBindingTest extends TestCase
{
    public function test_parent_dropdown_binding_and_desktop_link_navigation(): void
    {
        $menu = file_get_contents(base_path('platform/themes/homzen/partials/main-menu.blade.php'));
        $chrome = file_get_contents(base_path('platform/themes/homzen/public/css/site-chrome.css'));
        $cache = file_get_contents(base_path('app/Support/HomepageFragmentCache.php'));

        $this->assertStringContainsString('data-menu-key=', $menu);
        $this->assertStringContainsString('data-menu-parent=', $menu);
        $this->assertStringContainsString('aria-controls="{{ $panelId }}"', $menu);
        $this->assertStringContainsString('item._megaPanel = panel', $menu);
        $this->assertStringContainsString('let activeItem = null', $menu);
        $this->assertStringContainsString('headerNavItemFromPoint', $menu);
        $this->assertStringContainsString('pointerenter', $menu);
        $this->assertStringContainsString('__serikMegaMenuInit', $menu);
        $this->assertStringContainsString("e.key !== 'ArrowDown'", $menu);
        $this->assertStringContainsString('$isBlogsItem && $showMega', $menu);
        $this->assertStringContainsString('isHeaderSearchActive()', $menu);
        $this->assertStringContainsString('window.closeMegaMenu = closeMegaMenu', $menu);
        $this->assertStringContainsString('mega-v8', $cache);
        $this->assertStringContainsString('li.menu-item.is-active > a.menu-link', $chrome);
        $this->assertStringContainsString('a.menu-link:focus-visible', $chrome);

        $clickStart = strpos($menu, "link.addEventListener('click'");
        $this->assertNotFalse($clickStart);
        $clickChunk = substr($menu, $clickStart, 520);
        $this->assertStringContainsString('if (isDesktop())', $clickChunk);
        $this->assertTrue((bool) preg_match('/if \(isDesktop\(\)\) \{\s*if \(isHeaderSearchActive\(\)\) \{\s*closeMegaMenu\(\);\s*\}\s*return;\s*\}/s', $clickChunk));
    }
}
