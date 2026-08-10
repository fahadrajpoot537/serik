<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Pending MLS → GoHighLevel Showings (contact custom fields) sync tasks.
 * Webhooks only enqueue rows; early-morning workers process them on the ghl queue.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ghl_mls_sync_tasks', function (Blueprint $table) {
            $table->id();
            $table->string('contact_id', 64)->index();
            $table->string('mls_number', 64)->index();
            $table->string('location_id', 64)->nullable();
            $table->string('status', 32)->default('pending')->index();
            $table->string('external_key', 128)->nullable()->unique();
            $table->unsignedInteger('attempts')->default(0);
            $table->text('last_error')->nullable();
            $table->string('sync_hash', 64)->nullable();
            $table->json('source_payload')->nullable();
            $table->json('mapped_fields')->nullable();
            $table->timestamp('queued_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->unique(['contact_id', 'mls_number'], 'ghl_mls_contact_mls_unique');
            $table->index(['status', 'queued_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ghl_mls_sync_tasks');
    }
};
