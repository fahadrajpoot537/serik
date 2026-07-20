<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Adds a generated POINT column for optional spatial queries.
 * A SPATIAL (R-tree) index is attempted; if MariaDB rejects a nullable
 * generated column, Meilisearch remains the primary map engine.
 */
return new class extends Migration
{
    public function up(): void
    {
        @set_time_limit(0);

        if (! Schema::hasColumn('re_properties', 'geo_point')) {
            DB::statement("
                ALTER TABLE re_properties
                ADD COLUMN geo_point POINT
                    AS (
                        IF(
                            latitude IS NULL OR longitude IS NULL
                            OR latitude = 0 OR longitude = 0,
                            NULL,
                            POINT(longitude, latitude)
                        )
                    ) STORED
            ");
        }

        $indexes = collect(DB::select('SHOW INDEX FROM re_properties'))
            ->pluck('Key_name')
            ->unique()
            ->all();

        if (! in_array('idx_re_properties_geo_point', $indexes, true)) {
            try {
                DB::statement('ALTER TABLE re_properties ADD SPATIAL INDEX idx_re_properties_geo_point (geo_point)');
            } catch (\Throwable $e) {
                \Log::warning('SPATIAL index not created (Meili remains primary map path): ' . $e->getMessage());
            }
        }
    }

    public function down(): void
    {
        $indexes = collect(DB::select('SHOW INDEX FROM re_properties'))
            ->pluck('Key_name')
            ->unique()
            ->all();

        if (in_array('idx_re_properties_geo_point', $indexes, true)) {
            DB::statement('ALTER TABLE re_properties DROP INDEX idx_re_properties_geo_point');
        }

        if (Schema::hasColumn('re_properties', 'geo_point')) {
            DB::statement('ALTER TABLE re_properties DROP COLUMN geo_point');
        }
    }
};
