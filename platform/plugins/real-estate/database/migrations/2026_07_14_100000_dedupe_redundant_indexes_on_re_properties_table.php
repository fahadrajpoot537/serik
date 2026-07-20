<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Removes redundant / duplicate secondary indexes from re_properties.
 *
 * The table had 39 secondary indexes, several covering the exact same column
 * (or a column already served by the left-most prefix of a composite index).
 * Every duplicate index must be maintained on INSERT/UPDATE, so removing them
 * speeds up the TREB import/upsert path and reduces storage without hurting
 * any read path (an equivalent index is kept for every column).
 */
return new class () extends Migration {
    /**
     * Index name => the equivalent index that is kept (documentation only).
     */
    private array $redundant = [
        'idx_prop_moderation',            // dup of idx_re_properties_moderation_status
        'latitude',                       // prefix of idx_re_properties_map_bounds (lat,long,mod)
        'idx_properties_subtype',         // dup of idx_re_properties_property_subtype
        'idx_prop_subtype',               // dup of idx_re_properties_property_subtype
        'idx_prop_mlsstatus',             // prefix of composite `MlsStatus` (MlsStatus,TransactionType)
        'idx_prop_status',                // dup of idx_re_properties_status
        'idx_re_properties_mls_status',   // prefix of composite `MlsStatus`
        'idx_re_properties_external_id',  // dup of re_properties_external_id_index
    ];

    public function up(): void
    {
        foreach ($this->redundant as $index) {
            if (Schema::hasIndex('re_properties', $index)) {
                DB::statement("ALTER TABLE `re_properties` DROP INDEX `{$index}`");
            }
        }
    }

    public function down(): void
    {
        $recreate = [
            'idx_prop_moderation' => '(`moderation_status`)',
            'latitude' => '(`latitude`, `longitude`)',
            'idx_properties_subtype' => '(`PropertySubType`)',
            'idx_prop_subtype' => '(`PropertySubType`)',
            'idx_prop_mlsstatus' => '(`MlsStatus`)',
            'idx_prop_status' => '(`status`)',
            'idx_re_properties_mls_status' => '(`MlsStatus`)',
            'idx_re_properties_external_id' => '(`external_id`)',
        ];

        foreach ($recreate as $index => $columns) {
            if (! Schema::hasIndex('re_properties', $index)) {
                DB::statement("ALTER TABLE `re_properties` ADD INDEX `{$index}` {$columns}");
            }
        }
    }
};
