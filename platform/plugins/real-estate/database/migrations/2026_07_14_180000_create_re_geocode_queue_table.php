<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Permanent failed-geocode queue (Issue 1).
 *
 * Nominatim is rate-limited (~1 req/sec). Without tracking, the geocoder kept
 * re-selecting the same newest active listings every round — so any address it
 * could not resolve was retried forever, starving the rate-limited budget and
 * preventing the 46k backlog from ever draining.
 *
 * This table records each failed attempt with an exponential-backoff
 * `next_attempt_at` so the selector can skip a row until it is due again, and
 * quarantines permanently-unresolvable rows (`permanent_fail`) for reporting.
 * A successful geocode deletes the row, so it never blocks a real coordinate.
 */
return new class () extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('re_geocode_queue')) {
            return;
        }

        Schema::create('re_geocode_queue', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('property_id')->unique();
            $table->string('external_id', 100)->nullable();
            $table->unsignedSmallInteger('attempts')->default(0);
            $table->string('last_error', 255)->nullable();
            $table->string('last_address', 255)->nullable();
            $table->timestamp('last_attempt_at')->nullable();
            $table->timestamp('next_attempt_at')->nullable();
            $table->boolean('permanent_fail')->default(false);
            $table->timestamps();

            // The selector filters "due or permanent" — index both.
            $table->index(['permanent_fail', 'next_attempt_at'], 'idx_geoq_due');
            $table->index('external_id', 'idx_geoq_external');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('re_geocode_queue');
    }
};
