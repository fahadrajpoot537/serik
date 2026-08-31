<?php

namespace Tests\Unit;

use App\Support\AccountWishlist;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AccountWishlistTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropIfExists('re_account_wishlists');
        Schema::create('re_account_wishlists', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('account_id');
            $table->string('item_type', 16);
            $table->unsignedBigInteger('item_id');
            $table->timestamps();
            $table->unique(['account_id', 'item_type', 'item_id'], 're_account_wishlists_unique');
        });
    }

    public function test_add_is_idempotent_for_the_same_user_and_property(): void
    {
        AccountWishlist::add(11, 101);
        AccountWishlist::add(11, 101);
        AccountWishlist::add(11, 101);

        $this->assertSame(1, AccountWishlist::countFor(11));
        $this->assertSame([101], AccountWishlist::propertyIdsFor(11));
        $this->assertTrue(AccountWishlist::has(11, 101));
    }

    public function test_toggle_removes_a_saved_property(): void
    {
        AccountWishlist::add(12, 202);
        $removed = AccountWishlist::toggle(12, 202);

        $this->assertFalse($removed['saved']);
        $this->assertSame(0, $removed['count']);
        $this->assertFalse(AccountWishlist::has(12, 202));
    }

    public function test_users_cannot_see_another_users_ids(): void
    {
        AccountWishlist::add(21, 301);
        AccountWishlist::add(22, 302);

        $this->assertSame([301], AccountWishlist::propertyIdsFor(21));
        $this->assertSame([302], AccountWishlist::propertyIdsFor(22));
        $this->assertSame(1, AccountWishlist::countFor(21));
        $this->assertFalse(AccountWishlist::has(21, 302));
    }

    public function test_remove_is_idempotent(): void
    {
        AccountWishlist::remove(31, 401);
        AccountWishlist::remove(31, 401);

        $this->assertSame(0, AccountWishlist::countFor(31));
    }

    public function test_count_is_not_shared_across_accounts(): void
    {
        AccountWishlist::add(41, 501);
        AccountWishlist::add(41, 502);
        AccountWishlist::add(42, 503);

        $this->assertSame(2, AccountWishlist::countFor(41));
        $this->assertSame(1, AccountWishlist::countFor(42));
        $this->assertSame(0, AccountWishlist::countFor(99));
    }
}
