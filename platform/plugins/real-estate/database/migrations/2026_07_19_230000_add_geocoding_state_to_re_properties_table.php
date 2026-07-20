<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Production geocoding state machine on re_properties.
 *
 * Status: pending → queued → processing → done | failed
 *
 * Uses INPLACE/LOCK=NONE alters where possible so production stays online.
 * Status backfill is a separate command (serik:geocode:backfill-status).
 */
return new class () extends Migration {
    public function up(): void
    {
        if (! Schema::hasTable('re_properties')) {
            return;
        }

        $this->addColumnIfMissing(
            'geocoding_status',
            "ADD COLUMN geocoding_status VARCHAR(20) NOT NULL DEFAULT 'pending' AFTER longitude"
        );
        $this->addColumnIfMissing(
            'geocode_queued_at',
            'ADD COLUMN geocode_queued_at TIMESTAMP NULL AFTER geocoding_status'
        );
        $this->addColumnIfMissing(
            'geocode_started_at',
            'ADD COLUMN geocode_started_at TIMESTAMP NULL AFTER geocode_queued_at'
        );
        $this->addColumnIfMissing(
            'geocode_completed_at',
            'ADD COLUMN geocode_completed_at TIMESTAMP NULL AFTER geocode_started_at'
        );
        $this->addColumnIfMissing(
            'geocode_failed_at',
            'ADD COLUMN geocode_failed_at TIMESTAMP NULL AFTER geocode_completed_at'
        );
        $this->addColumnIfMissing(
            'geocode_attempts',
            'ADD COLUMN geocode_attempts SMALLINT UNSIGNED NOT NULL DEFAULT 0 AFTER geocode_failed_at'
        );

        $this->addIndexIfMissing(
            'idx_re_prop_geocode_status',
            'ADD INDEX idx_re_prop_geocode_status (geocoding_status, latitude)'
        );
        $this->addIndexIfMissing(
            'idx_re_prop_geocode_processing',
            'ADD INDEX idx_re_prop_geocode_processing (geocoding_status, geocode_started_at)'
        );
        $this->addIndexIfMissing(
            'idx_re_prop_geocode_failed',
            'ADD INDEX idx_re_prop_geocode_failed (geocoding_status, geocode_failed_at)'
        );
    }

    public function down(): void
    {
        if (! Schema::hasTable('re_properties')) {
            return;
        }

        Schema::table('re_properties', function (Blueprint $table): void {
            foreach ([
                'idx_re_prop_geocode_status',
                'idx_re_prop_geocode_processing',
                'idx_re_prop_geocode_failed',
            ] as $index) {
                try {
                    $table->dropIndex($index);
                } catch (\Throwable) {
                    //
                }
            }

            $cols = [
                'geocoding_status',
                'geocode_queued_at',
                'geocode_started_at',
                'geocode_completed_at',
                'geocode_failed_at',
                'geocode_attempts',
            ];
            $drop = array_values(array_filter($cols, fn ($c) => Schema::hasColumn('re_properties', $c)));
            if ($drop !== []) {
                $table->dropColumn($drop);
            }
        });
    }

    private function addColumnIfMissing(string $column, string $addSql): void
    {
        if ($this->columnExists('re_properties', $column)) {
            return;
        }

        try {
            DB::statement("ALTER TABLE re_properties {$addSql}, ALGORITHM=INPLACE, LOCK=NONE");
        } catch (\Throwable $e) {
            // Race / stale cache / older MariaDB without INPLACE hints.
            if ($this->columnExists('re_properties', $column)) {
                return;
            }
            if (str_contains($e->getMessage(), 'Duplicate column')) {
                return;
            }
            DB::statement("ALTER TABLE re_properties {$addSql}");
        }
    }

    private function columnExists(string $table, string $column): bool
    {
        try {
            $db = Schema::getConnection()->getDatabaseName();
            $row = DB::selectOne(
                'SELECT 1 AS ok FROM information_schema.columns
                 WHERE table_schema = ? AND table_name = ? AND column_name = ? LIMIT 1',
                [$db, $table, $column]
            );

            return $row !== null;
        } catch (\Throwable) {
            return Schema::hasColumn($table, $column);
        }
    }

    private function addIndexIfMissing(string $name, string $addSql): void
    {
        if ($this->indexExists('re_properties', $name)) {
            return;
        }

        try {
            DB::statement("ALTER TABLE re_properties {$addSql}, ALGORITHM=INPLACE, LOCK=NONE");
        } catch (\Throwable $e) {
            // Fallback without algorithm hints (older MariaDB).
            if (! $this->indexExists('re_properties', $name)) {
                DB::statement("ALTER TABLE re_properties {$addSql}");
            }
        }
    }

    private function indexExists(string $table, string $index): bool
    {
        try {
            $db = Schema::getConnection()->getDatabaseName();
            $row = DB::selectOne(
                'SELECT 1 AS ok FROM information_schema.statistics
                 WHERE table_schema = ? AND table_name = ? AND index_name = ? LIMIT 1',
                [$db, $table, $index]
            );

            return $row !== null;
        } catch (\Throwable) {
            return false;
        }
    }
};
