<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('re_categories')) {
            Schema::table('re_categories', function (Blueprint $table): void {
                $table->index('status', 'idx_re_categories_status');

                $table->index('parent_id', 'idx_re_categories_parent_id');

                $table->index(['status', 'parent_id', 'order'], 'idx_re_categories_status_parent_order');

                $table->index('is_default', 'idx_re_categories_is_default');

                $table->index('name', 'idx_re_categories_name');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('re_categories')) {
            Schema::table('re_categories', function (Blueprint $table): void {
                $table->dropIndex('idx_re_categories_status');
                $table->dropIndex('idx_re_categories_parent_id');
                $table->dropIndex('idx_re_categories_status_parent_order');
                $table->dropIndex('idx_re_categories_is_default');
                $table->dropIndex('idx_re_categories_name');
            });
        }
    }
};
