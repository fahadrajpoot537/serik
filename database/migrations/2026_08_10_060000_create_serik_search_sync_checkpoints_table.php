<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Durable Meilisearch pending / in-flight checkpoints (cache crash recovery).
 * Does not change SearchBatchJob public behaviour — PropertySearchSync dual-writes.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('serik_search_sync_pending', function (Blueprint $table) {
            $table->unsignedBigInteger('property_id')->primary();
            $table->timestamp('queued_at')->useCurrent();
            $table->timestamp('updated_at')->nullable();
        });

        Schema::create('serik_search_sync_inflight', function (Blueprint $table) {
            $table->unsignedBigInteger('property_id')->primary();
            $table->timestamp('claimed_at')->useCurrent();
            $table->string('worker_token', 64)->nullable()->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('serik_search_sync_inflight');
        Schema::dropIfExists('serik_search_sync_pending');
    }
};
