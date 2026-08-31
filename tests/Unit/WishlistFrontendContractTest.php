<?php

namespace Tests\Unit;

use Tests\TestCase;

class WishlistFrontendContractTest extends TestCase
{
    public function test_guest_heart_opens_login_and_does_not_write_property_cookies(): void
    {
        $script = file_get_contents(base_path('platform/themes/homzen/assets/js/script.js'));

        $this->assertStringContainsString("sessionStorage.setItem('serik_pending_wishlist'", $script);
        $this->assertStringContainsString('promptWishlistLogin', $script);
        $this->assertStringContainsString("window.openAuthModal('login')", $script);
        $this->assertStringContainsString("type: 'property'", $script);
        $this->assertStringContainsString('X-XSRF-TOKEN', $script);
        $this->assertStringContainsString('session expired', $script);
        $this->assertStringContainsString('csrfUrl', file_get_contents(base_path('platform/themes/homzen/partials/header.blade.php')));
        $this->assertStringContainsString('if (!cfg.stateUrl)', $script);
        $this->assertStringNotContainsString('if (!cfg.authenticated || !cfg.stateUrl)', $script);
        $this->assertStringContainsString('public.ajax.wishlist.toggle', file_get_contents(base_path('platform/themes/homzen/routes/web.php')));
        $this->assertStringNotContainsString("cookieName = \$currentTarget.data('type') === 'property' ? 'wishlist'", $script);
    }

    public function test_topbar_wishlist_icon_and_accessible_count_exist(): void
    {
        $header = file_get_contents(base_path('platform/themes/homzen/partials/header.blade.php'));

        $this->assertStringContainsString('data-serik-wishlist-trigger', $header);
        $this->assertStringContainsString('serik-nav-wishlist', $header);
        $this->assertStringContainsString('Wishlist, :count saved properties', $header);
        $this->assertStringContainsString('window.SERIK_WISHLIST', $header);
        $this->assertStringContainsString('Cache-Control', file_get_contents(base_path('app/Http/Controllers/AccountWishlistController.php')));
        $this->assertStringContainsString('private, no-store', file_get_contents(base_path('app/Http/Controllers/AccountWishlistController.php')));
    }

    public function test_wishlist_controller_never_trusts_a_client_user_id(): void
    {
        $controller = file_get_contents(base_path('app/Http/Controllers/AccountWishlistController.php'));

        $this->assertStringContainsString("Auth::guard('account')->id()", $controller);
        $this->assertStringNotContainsString("input('user_id'", $controller);
        $this->assertStringNotContainsString("input('account_id'", $controller);
        $this->assertStringContainsString("'action' => ['nullable', 'in:add,remove,toggle']", $controller);
    }

    public function test_migration_adds_a_unique_account_property_constraint(): void
    {
        $migration = file_get_contents(base_path('database/migrations/2026_08_31_130000_create_re_account_wishlists_table.php'));

        $this->assertStringContainsString("unique(['account_id', 'item_type', 'item_id']", $migration);
        $this->assertStringContainsString('dropIfExists', $migration);
    }
}
