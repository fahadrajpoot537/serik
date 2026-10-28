<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * BTREE prefix on name so evaluation typeahead can use
 * `name LIKE '47 Bro%'` in ~1–100ms instead of FULLTEXT+LIKE scans (1–20s).
 */
return new class () extends Migration {
    public function up(): void
    {
        if (! Schema::hasTable('re_properties') || $this->indexExists('idx_re_properties_name_prefix')) {
            return;
        }

        DB::statement(
            'ALTER TABLE `re_properties` ADD INDEX `idx_re_properties_name_prefix` (`name`(96))'
        );
    }

    public function down(): void
    {
        if ($this->indexExists('idx_re_properties_name_prefix')) {
            DB::statement('ALTER TABLE `re_properties` DROP INDEX `idx_re_properties_name_prefix`');
        }
    }

    private function indexExists(string $name): bool
    {
        if (! Schema::hasTable('re_properties')) {
            return false;
        }

        return collect(DB::select(
            'SHOW INDEX FROM `re_properties` WHERE Key_name = ?',
            [$name]
        ))->isNotEmpty();
    }
};
