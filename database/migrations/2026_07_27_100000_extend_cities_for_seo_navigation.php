<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cities', function (Blueprint $table): void {
            if (! Schema::hasColumn('cities', 'latitude')) {
                $table->decimal('latitude', 10, 7)->nullable()->after('zip_code');
            }
            if (! Schema::hasColumn('cities', 'longitude')) {
                $table->decimal('longitude', 10, 7)->nullable()->after('latitude');
            }
            if (! Schema::hasColumn('cities', 'property_count')) {
                $table->unsignedInteger('property_count')->default(0)->after('longitude');
            }
            if (! Schema::hasColumn('cities', 'is_major')) {
                $table->boolean('is_major')->default(false)->after('property_count');
            }
            if (! Schema::hasColumn('cities', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('is_major');
            }
        });

        Schema::table('cities', function (Blueprint $table): void {
            if (! $this->indexExists('cities', 'cities_is_active_is_major_index')) {
                $table->index(['is_active', 'is_major'], 'cities_is_active_is_major_index');
            }
            if (! $this->indexExists('cities', 'cities_state_id_is_active_index')) {
                $table->index(['state_id', 'is_active'], 'cities_state_id_is_active_index');
            }
            if (! $this->indexExists('cities', 'cities_slug_index')) {
                $table->index('slug', 'cities_slug_index');
            }
        });
    }

    public function down(): void
    {
        Schema::table('cities', function (Blueprint $table): void {
            foreach ([
                'cities_is_active_is_major_index',
                'cities_state_id_is_active_index',
                'cities_slug_index',
            ] as $index) {
                if ($this->indexExists('cities', $index)) {
                    $table->dropIndex($index);
                }
            }

            foreach (['latitude', 'longitude', 'property_count', 'is_major', 'is_active'] as $col) {
                if (Schema::hasColumn('cities', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }

    private function indexExists(string $table, string $index): bool
    {
        $indexes = Schema::getIndexes($table);

        foreach ($indexes as $meta) {
            if (($meta['name'] ?? '') === $index) {
                return true;
            }
        }

        return false;
    }
};
