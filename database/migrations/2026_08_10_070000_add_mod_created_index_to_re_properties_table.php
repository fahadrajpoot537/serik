<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Speeds approved listing browse ORDER BY created_at (EXPLAIN showed filesort).
 * Additive only — does not change query results.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('re_properties', function (Blueprint $table): void {
            if (! $this->indexExists('idx_re_properties_mod_created')) {
                $table->index(
                    ['moderation_status', 'created_at'],
                    'idx_re_properties_mod_created'
                );
            }
        });
    }

    public function down(): void
    {
        Schema::table('re_properties', function (Blueprint $table): void {
            if ($this->indexExists('idx_re_properties_mod_created')) {
                $table->dropIndex('idx_re_properties_mod_created');
            }
        });
    }

    private function indexExists(string $name): bool
    {
        foreach (Schema::getIndexes('re_properties') as $index) {
            if (($index['name'] ?? '') === $name) {
                return true;
            }
        }

        return false;
    }
};
