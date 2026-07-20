<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Adds a natural-language FULLTEXT index over the address columns so keyword
 * searches (street, community, city fragments) can use MATCH ... AGAINST
 * instead of leading-wildcard LIKE '%kw%' scans, which cannot use a B-tree
 * index and force a full table scan on 100k+ rows.
 *
 * InnoDB FULLTEXT is supported on MariaDB 10.0.5+ (this server: 10.4).
 */
return new class () extends Migration {
    public function up(): void
    {
        if ($this->fulltextExists('ft_re_properties_address')) {
            return;
        }

        DB::statement(
            'ALTER TABLE `re_properties` ADD FULLTEXT INDEX `ft_re_properties_address` (`name`, `location`)'
        );
    }

    public function down(): void
    {
        if ($this->fulltextExists('ft_re_properties_address')) {
            DB::statement('ALTER TABLE `re_properties` DROP INDEX `ft_re_properties_address`');
        }
    }

    private function fulltextExists(string $name): bool
    {
        if (! Schema::hasTable('re_properties')) {
            return false;
        }

        return collect(DB::select("SHOW INDEX FROM `re_properties` WHERE Key_name = '{$name}'"))->isNotEmpty();
    }
};
