<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Provider-agnostic geocoding metadata on re_properties.
 */
return new class () extends Migration {
    public function up(): void
    {
        if (! Schema::hasTable('re_properties')) {
            return;
        }

        Schema::table('re_properties', function (Blueprint $table): void {
            if (! Schema::hasColumn('re_properties', 'geocoding_provider')) {
                $after = Schema::hasColumn('re_properties', 'geocoded_at')
                    ? 'geocoded_at'
                    : (Schema::hasColumn('re_properties', 'google_location_type')
                        ? 'google_location_type'
                        : 'longitude');
                $table->string('geocoding_provider', 40)->nullable()->after($after);
            }

            if (! Schema::hasColumn('re_properties', 'geocoded_at')) {
                $table->timestamp('geocoded_at')->nullable()->after(
                    Schema::hasColumn('re_properties', 'geocoding_provider')
                        ? 'geocoding_provider'
                        : 'longitude'
                );
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('re_properties')) {
            return;
        }

        Schema::table('re_properties', function (Blueprint $table): void {
            if (Schema::hasColumn('re_properties', 'geocoding_provider')) {
                $table->dropColumn('geocoding_provider');
            }
        });
    }
};
