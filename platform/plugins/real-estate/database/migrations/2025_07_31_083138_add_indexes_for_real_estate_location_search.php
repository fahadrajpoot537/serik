<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::table('re_projects', function (Blueprint $table): void {
            $table->index('status', 'idx_re_projects_status');
            $table->index('location', 'idx_re_projects_location');
            $table->index(['city_id', 'status'], 'idx_re_projects_city_status');
            $table->index(['state_id', 'status'], 'idx_re_projects_state_status');
            $table->index(['is_featured', 'featured_priority', 'created_at'], 'idx_re_projects_featured_sort');
        });

        Schema::table('re_projects_translations', function (Blueprint $table): void {
            $table->index(['re_projects_id', 'lang_code'], 'idx_re_projects_trans_proj_lang');
            $table->index('location', 'idx_re_projects_trans_location');
        });

        Schema::table('re_properties', function (Blueprint $table): void {
            $table->index('status', 'idx_re_properties_status');
            $table->index('location', 'idx_re_properties_location');
            $table->index(['city_id', 'status'], 'idx_re_properties_city_status');
            $table->index(['state_id', 'status'], 'idx_re_properties_state_status');
            $table->index(['is_featured', 'featured_priority', 'created_at'], 'idx_re_properties_featured_sort');
            $table->index('moderation_status', 'idx_re_properties_moderation_status');
            $table->index('expire_date', 'idx_re_properties_expire_date');
        });

        Schema::table('re_properties_translations', function (Blueprint $table): void {
            $table->index(['re_properties_id', 'lang_code'], 'idx_re_properties_trans_prop_lang');
            $table->index('location', 'idx_re_properties_trans_location');
        });
    }

    public function down(): void
    {
        Schema::table('re_projects', function (Blueprint $table): void {
            $table->dropIndex('idx_re_projects_status');
            $table->dropIndex('idx_re_projects_location');
            $table->dropIndex('idx_re_projects_city_status');
            $table->dropIndex('idx_re_projects_state_status');
            $table->dropIndex('idx_re_projects_featured_sort');
        });

        Schema::table('re_projects_translations', function (Blueprint $table): void {
            $table->dropIndex('idx_re_projects_trans_proj_lang');
            $table->dropIndex('idx_re_projects_trans_location');
        });

        Schema::table('re_properties', function (Blueprint $table): void {
            $table->dropIndex('idx_re_properties_status');
            $table->dropIndex('idx_re_properties_location');
            $table->dropIndex('idx_re_properties_city_status');
            $table->dropIndex('idx_re_properties_state_status');
            $table->dropIndex('idx_re_properties_featured_sort');
            $table->dropIndex('idx_re_properties_moderation_status');
            $table->dropIndex('idx_re_properties_expire_date');
        });

        Schema::table('re_properties_translations', function (Blueprint $table): void {
            $table->dropIndex('idx_re_properties_trans_prop_lang');
            $table->dropIndex('idx_re_properties_trans_location');
        });
    }
};
