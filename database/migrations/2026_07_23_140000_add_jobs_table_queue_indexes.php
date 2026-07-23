<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('jobs')) {
            return;
        }

        Schema::table('jobs', function (Blueprint $table) {
            if (! $this->indexExists('jobs', 'jobs_queue_available_reserved_index')) {
                $table->index(['queue', 'available_at', 'reserved_at'], 'jobs_queue_available_reserved_index');
            }

            if (! $this->indexExists('jobs', 'jobs_reserved_at_index')) {
                $table->index('reserved_at', 'jobs_reserved_at_index');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('jobs')) {
            return;
        }

        Schema::table('jobs', function (Blueprint $table) {
            if ($this->indexExists('jobs', 'jobs_queue_available_reserved_index')) {
                $table->dropIndex('jobs_queue_available_reserved_index');
            }

            if ($this->indexExists('jobs', 'jobs_reserved_at_index')) {
                $table->dropIndex('jobs_reserved_at_index');
            }
        });
    }

    private function indexExists(string $table, string $indexName): bool
    {
        $connection = Schema::getConnection();
        $driver = $connection->getDriverName();

        if ($driver === 'mysql') {
            $rows = $connection->select(
                'SHOW INDEX FROM `' . $table . '` WHERE Key_name = ?',
                [$indexName]
            );

            return $rows !== [];
        }

        if ($driver === 'sqlite') {
            $rows = $connection->select("PRAGMA index_list('{$table}')");

            foreach ($rows as $row) {
                if (($row->name ?? '') === $indexName) {
                    return true;
                }
            }
        }

        return false;
    }
};
