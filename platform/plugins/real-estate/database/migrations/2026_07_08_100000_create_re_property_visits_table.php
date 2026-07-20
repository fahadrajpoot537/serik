<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('re_property_visits')) {
            return;
        }

        Schema::create('re_property_visits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_id')->constrained('re_accounts')->cascadeOnDelete();
            $table->foreignId('property_id')->nullable()->constrained('re_properties')->nullOnDelete();
            $table->string('listing_key', 64)->index();
            $table->string('property_name')->nullable();
            $table->string('property_location')->nullable();
            $table->decimal('property_price', 15, 2)->nullable();
            $table->decimal('close_price', 15, 2)->nullable();
            $table->string('mls_status', 80)->nullable();
            $table->string('transaction_type', 40)->nullable();
            $table->string('property_sub_type', 80)->nullable();
            $table->string('source', 30)->default('map');
            $table->unsignedInteger('view_count')->default(1);
            $table->timestamp('last_viewed_at')->nullable();
            $table->timestamp('delete_requested_at')->nullable();
            $table->unsignedBigInteger('delete_approved_by')->nullable();
            $table->timestamp('delete_approved_at')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->unique(['account_id', 'listing_key']);
            $table->index(['account_id', 'deleted_at', 'last_viewed_at']);
            $table->index('delete_requested_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('re_property_visits');
    }
};
