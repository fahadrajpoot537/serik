<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('settings')) {
            return;
        }

        $defaults = [
            'shortcode_cache_enabled' => '1',
            'shortcode_cache_ttl_cacheable' => '1800',
            'shortcode_cache_ttl_default' => '300',
            'shortcode_cache_ttl' => '1800',
        ];

        $now = now();

        foreach ($defaults as $key => $value) {
            if (DB::table('settings')->where('key', $key)->exists()) {
                continue;
            }

            DB::table('settings')->insert([
                'key' => $key,
                'value' => $value,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        // Intentionally leave settings in place — disabling cache in down() could surprise production.
    }
};
