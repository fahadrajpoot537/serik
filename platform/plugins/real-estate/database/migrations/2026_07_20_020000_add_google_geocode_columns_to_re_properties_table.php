<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Google Geocoding metadata on re_properties.
 */
return new class () extends Migration {
    public function up(): void
    {
        if (! Schema::hasTable('re_properties')) {
            return;
        }

        Schema::table('re_properties', function (Blueprint $table): void {
            if (! Schema::hasColumn('re_properties', 'google_formatted_address')) {
                $table->string('google_formatted_address', 500)->nullable()->after('longitude');
            }
            if (! Schema::hasColumn('re_properties', 'google_location_type')) {
                $table->string('google_location_type', 40)->nullable()->after('google_formatted_address');
            }
            if (! Schema::hasColumn('re_properties', 'geocoded_at')) {
                $table->timestamp('geocoded_at')->nullable()->after('google_location_type');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('re_properties')) {
            return;
        }

        Schema::table('re_properties', function (Blueprint $table): void {
            $cols = array_values(array_filter([
                'google_formatted_address',
                'google_location_type',
                'geocoded_at',
            ], fn ($c) => Schema::hasColumn('re_properties', $c)));

            if ($cols !== []) {
                $table->dropColumn($cols);
            }
        });
    }
};
