<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('re_account_wishlists')) {
            return;
        }

        Schema::create('re_account_wishlists', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('account_id');
            $table->string('item_type', 16);
            $table->unsignedBigInteger('item_id');
            $table->timestamps();

            $table->unique(['account_id', 'item_type', 'item_id'], 're_account_wishlists_unique');
            $table->index(['account_id', 'item_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('re_account_wishlists');
    }
};
