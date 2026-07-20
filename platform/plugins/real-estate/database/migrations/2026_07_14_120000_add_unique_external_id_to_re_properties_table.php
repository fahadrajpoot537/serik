<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Guarantees that each TREB ListingKey (external_id) can exist only once.
 *
 * The 2026-05-01 double-import created 372 exact-duplicate rows because there
 * was no unique constraint on external_id. Those duplicates were removed; this
 * migration installs the constraint so the situation can never recur, no matter
 * how many import/cron jobs run concurrently.
 */
return new class extends Migration
{
    private function indexExists(string $index): bool
    {
        $rows = DB::select(
            'SHOW INDEX FROM re_properties WHERE Key_name = ?',
            [$index]
        );

        return ! empty($rows);
    }

    public function up(): void
    {
        // Defensive: collapse any remaining duplicates so the unique index can be built.
        $groups = DB::select("
            SELECT external_id
            FROM re_properties
            WHERE external_id IS NOT NULL AND external_id <> ''
            GROUP BY external_id HAVING COUNT(*) > 1
        ");

        foreach ($groups as $g) {
            $ids = DB::table('re_properties')
                ->where('external_id', $g->external_id)
                ->orderByRaw('(latitude IS NOT NULL AND latitude <> 0) DESC')
                ->orderBy('updated_at', 'desc')
                ->orderBy('id')
                ->pluck('id')
                ->all();

            $keeper = array_shift($ids);
            if ($ids) {
                if (Schema::hasTable('re_property_history')) {
                    DB::table('re_property_history')->whereIn('property_id', $ids)->update(['property_id' => $keeper]);
                }
                DB::table('re_properties')->whereIn('id', $ids)->delete();
            }
        }

        Schema::table('re_properties', function ($table) {
            if (! $this->indexExists('re_properties_external_id_unique')) {
                $table->unique('external_id', 're_properties_external_id_unique');
            }
        });

        // Drop the now-redundant non-unique index (unique index covers lookups).
        if ($this->indexExists('re_properties_external_id_index')) {
            Schema::table('re_properties', function ($table) {
                $table->dropIndex('re_properties_external_id_index');
            });
        }
    }

    public function down(): void
    {
        Schema::table('re_properties', function ($table) {
            if ($this->indexExists('re_properties_external_id_unique')) {
                $table->dropUnique('re_properties_external_id_unique');
            }
            if (! $this->indexExists('re_properties_external_id_index')) {
                $table->index('external_id', 're_properties_external_id_index');
            }
        });
    }
};
