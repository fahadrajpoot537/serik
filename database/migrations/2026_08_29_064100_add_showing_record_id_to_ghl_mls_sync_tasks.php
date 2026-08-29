<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Persist the GHL Showings Custom Object record id from object-based webhooks
 * so sync updates that exact row instead of searching/creating another.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ghl_mls_sync_tasks', function (Blueprint $table): void {
            if (! Schema::hasColumn('ghl_mls_sync_tasks', 'showing_record_id')) {
                $table->string('showing_record_id', 64)->nullable()->after('mls_number');
                $table->index('showing_record_id', 'ghl_mls_showing_record_id_index');
            }
        });
    }

    public function down(): void
    {
        Schema::table('ghl_mls_sync_tasks', function (Blueprint $table): void {
            if (Schema::hasColumn('ghl_mls_sync_tasks', 'showing_record_id')) {
                $table->dropIndex('ghl_mls_showing_record_id_index');
                $table->dropColumn('showing_record_id');
            }
        });
    }
};
