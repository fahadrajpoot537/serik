<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::table('re_properties', function (Blueprint $table): void {
            if (! Schema::hasColumn('re_properties', 'CoveredSpaces')) {
                $table->unsignedTinyInteger('CoveredSpaces')->nullable()->after('ParkingSpaces');
            }

            $table->index(['latitude', 'longitude', 'moderation_status'], 'idx_re_properties_map_bounds');
            $table->index('MlsStatus', 'idx_re_properties_mls_status');
            $table->index('PropertySubType', 'idx_re_properties_property_subtype');
            $table->index('TransactionType', 'idx_re_properties_transaction_type');
        });
    }

    public function down(): void
    {
        Schema::table('re_properties', function (Blueprint $table): void {
            if (Schema::hasColumn('re_properties', 'CoveredSpaces')) {
                $table->dropColumn('CoveredSpaces');
            }

            $table->dropIndex('idx_re_properties_map_bounds');
            $table->dropIndex('idx_re_properties_mls_status');
            $table->dropIndex('idx_re_properties_property_subtype');
            $table->dropIndex('idx_re_properties_transaction_type');
        });
    }
};
