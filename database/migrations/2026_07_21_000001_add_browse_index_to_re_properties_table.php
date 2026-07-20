<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('re_properties', function (Blueprint $table): void {
            $table->index(
                ['moderation_status', 'MlsStatus', 'PropertySubType', 'listing_modified_at'],
                'idx_re_properties_browse_active'
            );
        });
    }

    public function down(): void
    {
        Schema::table('re_properties', function (Blueprint $table): void {
            $table->dropIndex('idx_re_properties_browse_active');
        });
    }
};
