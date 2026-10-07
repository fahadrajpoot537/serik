<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Speeds community-index aggregation:
 * meta_boxes filtered by reference_type + meta_key=amp_snapshot joined on reference_id.
 */
return new class extends Migration
{
    private string $indexName = 'mb_ref_type_key_id_idx';

    public function up(): void
    {
        if (! Schema::hasTable('meta_boxes') || $this->indexExists()) {
            return;
        }

        Schema::table('meta_boxes', function (Blueprint $table) {
            $table->index(['reference_type', 'meta_key', 'reference_id'], $this->indexName);
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('meta_boxes') || ! $this->indexExists()) {
            return;
        }

        Schema::table('meta_boxes', function (Blueprint $table) {
            $table->dropIndex($this->indexName);
        });
    }

    private function indexExists(): bool
    {
        try {
            $database = Schema::getConnection()->getDatabaseName();
            $row = DB::selectOne(
                'SELECT 1 AS ok FROM information_schema.statistics
                 WHERE table_schema = ? AND table_name = ? AND index_name = ?
                 LIMIT 1',
                [$database, 'meta_boxes', $this->indexName]
            );

            return $row !== null;
        } catch (\Throwable) {
            return false;
        }
    }
};
