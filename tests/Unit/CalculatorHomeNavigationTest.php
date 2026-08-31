<?php

namespace Tests\Unit;

use App\Support\SerikHtmlCacheHeaders;
use Illuminate\Http\Request;
use Tests\TestCase;
use Symfony\Component\HttpFoundation\Response;

class CalculatorHomeNavigationTest extends TestCase
{
    public function test_home_and_logo_point_at_canonical_homepage(): void
    {
        $header = file_get_contents(base_path('platform/themes/homzen/partials/header.blade.php'));

        $this->assertSame(2, substr_count($header, 'href="{{ BaseHelper::getHomepageUrl() }}"'));
        $this->assertStringContainsString('<a href="/" class="nav-item">', $header);
        $this->assertStringContainsString('<small>Home</small>', $header);
    }

    public function test_home_nav_loader_preserves_native_link_behavior(): void
    {
        $header = file_get_contents(base_path('platform/themes/homzen/partials/header.blade.php'));

        $this->assertStringContainsString('id="serik-home-nav-status"', $header);
        $this->assertStringContainsString('aria-live="polite"', $header);
        $this->assertStringContainsString('window.addEventListener(\'pageshow\', hideHomeNavStatus)', $header);
        $this->assertStringContainsString('SHOW_DELAY_MS = 80', $header);
        $this->assertStringContainsString('e.metaKey || e.ctrlKey', $header);
        $start = strpos($header, 'window.__serikHomeNavFeedback');
        $this->assertNotFalse($start);
        $chunk = substr($header, $start, 2200);
        $this->assertStringNotContainsString('preventDefault', $chunk);
    }

    public function test_calculator_pagehide_cleans_confetti_and_observer(): void
    {
        $calc = file_get_contents(base_path('platform/themes/homzen/partials/shortcodes/blog-posts/styles/style-2.blade.php'));

        $this->assertStringContainsString('function calculatePayment(M, r, years, freq)', $calc);
        $this->assertStringContainsString('addEventListener(\'pagehide\'', $calc);
        $this->assertStringContainsString('stopCelebration()', $calc);
        $this->assertStringContainsString('observer.disconnect()', $calc);
    }

    public function test_calculator_scripts_are_not_in_homepage_template(): void
    {
        $home = file_get_contents(base_path('platform/themes/homzen/views/index.blade.php'));

        $this->assertStringNotContainsString('canvas-confetti', $home);
        $this->assertStringNotContainsString('function calculatePayment', $home);
        $this->assertStringContainsString('[blog-posts style="1"', $home);
    }

    public function test_theme_script_uses_pagehide_not_beforeunload_for_hero(): void
    {
        $script = file_get_contents(base_path('platform/themes/homzen/public/js/script.js'));

        $this->assertStringContainsString("$(window).on('pagehide', function() {", $script);
        $this->assertStringNotContainsString("$(window).on('beforeunload'", $script);
        $this->assertStringContainsString("if (!$('#location').length) {", $script);
        $this->assertStringContainsString('clearInterval(checkInterval);', $script);
    }

    public function test_guest_html_cache_headers_omit_no_store(): void
    {
        $guest = Request::create('/', 'GET');
        $this->assertSame('private, no-cache, must-revalidate', SerikHtmlCacheHeaders::value($guest));
        $this->assertFalse(SerikHtmlCacheHeaders::isLikelyAuthenticated($guest));

        $auth = Request::create('/', 'GET');
        $auth->cookies->set('serik_acct', '1');
        $this->assertSame('private, no-cache, no-store, must-revalidate', SerikHtmlCacheHeaders::value($auth));
        $this->assertTrue(SerikHtmlCacheHeaders::isLikelyAuthenticated($auth));

        $remember = Request::create('/', 'GET');
        $remember->cookies->set('remember_account_xxx', 'token');
        $this->assertStringContainsString('no-store', SerikHtmlCacheHeaders::value($remember));

        $response = new Response('ok', 200, ['Content-Type' => 'text/html; charset=UTF-8']);
        SerikHtmlCacheHeaders::apply($response, $guest);
        $this->assertStringNotContainsString('no-store', (string) $response->headers->get('Cache-Control'));
        $this->assertSame('Cookie', $response->headers->get('Vary'));
    }

    public function test_site_chrome_includes_home_nav_status_and_version_bump(): void
    {
        $chrome = file_get_contents(base_path('platform/themes/homzen/public/css/site-chrome.css'));
        $config = file_get_contents(base_path('platform/themes/homzen/config.php'));
        $base = file_get_contents(base_path('platform/themes/homzen/layouts/base.blade.php'));

        $this->assertStringContainsString('#serik-home-nav-status', $chrome);
        $this->assertStringContainsString('prefers-reduced-motion', $chrome);
        $this->assertStringContainsString('#serik-home-nav-status *', $chrome);
        $this->assertStringContainsString('pointer-events: none', $chrome);
        $this->assertStringContainsString("version: \$version . '-sc24'", $config);
        $this->assertStringContainsString('-sc24', $base);
        $this->assertStringContainsString('mega-v8', file_get_contents(base_path('app/Support/HomepageFragmentCache.php')));
    }
}
