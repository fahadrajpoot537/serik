<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('re_reviews')) {
            Schema::table('re_reviews', function (Blueprint $table): void {
                // Add composite index for reviewable polymorphic relation with status
                // This optimizes the withCount query that filters by status
                $table->index(['reviewable_type', 'reviewable_id', 'status'], 'idx_reviews_reviewable_status');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('re_reviews')) {
            Schema::table('re_reviews', function (Blueprint $table): void {
                $table->dropIndex('idx_reviews_reviewable_status');
            });
        }
    }
};
