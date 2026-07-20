<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Immutable, append-only history of every meaningful change to a listing.
 *
 * re_properties keeps only the latest snapshot of a listing; this table keeps
 * every previous version so the UI can render price / status / sale / relisting
 * timelines without ever losing historical TREB data. Keyed by external_id
 * (MLS ListingKey) so history survives even if the parent row is rebuilt.
 */
return new class () extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('re_property_history')) {
            return;
        }

        Schema::create('re_property_history', function (Blueprint $table): void {
            $table->id();

            $table->unsignedBigInteger('property_id')->nullable();
            $table->string('external_id', 100)->nullable();

            // listed | price_change | status_change | sold | leased | relisted
            // | terminated | expired | suspended | updated
            $table->string('event', 40)->default('updated');

            $table->decimal('price', 15, 2)->nullable();
            $table->decimal('close_price', 15, 2)->nullable();
            $table->string('mls_status', 100)->nullable();
            $table->string('transaction_type', 50)->nullable();
            $table->string('status', 60)->nullable();

            $table->dateTime('listing_contract_date')->nullable();
            $table->dateTime('listing_modified_at')->nullable();
            $table->dateTime('close_date')->nullable();
            $table->dateTime('purchase_contract_date')->nullable();

            // Diff of tracked fields: { field: { old, new } }
            $table->json('changed')->nullable();
            // Full snapshot of tracked fields at this point in time.
            $table->json('snapshot')->nullable();

            $table->string('source', 40)->default('amp'); // amp_incremental | amp_historical | manual
            $table->dateTime('recorded_at')->useCurrent();
            $table->timestamps();

            $table->index('property_id', 'idx_reph_property_id');
            $table->index('external_id', 'idx_reph_external_id');
            $table->index(['external_id', 'recorded_at'], 'idx_reph_external_recorded');
            $table->index('event', 'idx_reph_event');
            $table->index('mls_status', 'idx_reph_mls_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('re_property_history');
    }
};
