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
            ->contains(fn ($i) => $i->Key_name === 'idx_re_props_mls_lcd');

        if (! $exists) {
            // Speeds Active-first geocode selection + status+date browsing
            // without a CASE/ORDER BY filesort over the zero-coord backlog.
            DB::statement('CREATE INDEX idx_re_props_mls_lcd ON re_properties (MlsStatus, listing_contract_date)');
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('re_properties')) {
            return;
        }

        $exists = collect(DB::select('SHOW INDEX FROM re_properties'))
            ->contains(fn ($i) => $i->Key_name === 'idx_re_props_mls_lcd');

        if ($exists) {
            DB::statement('DROP INDEX idx_re_props_mls_lcd ON re_properties');
        }
    }
};
