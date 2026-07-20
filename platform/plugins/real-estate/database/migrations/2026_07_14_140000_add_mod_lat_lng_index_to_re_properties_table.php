<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('re_properties')) {
            return;
        }

        $exists = collect(DB::select('SHOW INDEX FROM re_properties'))
            ->contains(fn ($i) => $i->Key_name === 'idx_re_properties_mod_lat_lng');

        if (! $exists) {
            // Equality on moderation_status first, then latitude range: lets the
            // MySQL map fallback seek approved rows and range-scan the bbox
            // instead of scanning the low-selectivity moderation_status index.
            DB::statement('CREATE INDEX idx_re_properties_mod_lat_lng ON re_properties (moderation_status, latitude, longitude)');
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('re_properties')) {
            return;
        }

        $exists = collect(DB::select('SHOW INDEX FROM re_properties'))
            ->contains(fn ($i) => $i->Key_name === 'idx_re_properties_mod_lat_lng');

        if ($exists) {
            DB::statement('DROP INDEX idx_re_properties_mod_lat_lng ON re_properties');
        }
    }
};
