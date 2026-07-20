<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::table('re_properties', function (Blueprint $table): void {
            $table->index('type', 'idx_re_properties_type');
            $table->index('never_expired', 'idx_re_properties_never_expired');
            $table->index(['moderation_status', 'status'], 'idx_re_properties_mod_status');
            $table->index('price', 'idx_re_properties_price');
            $table->index('square', 'idx_re_properties_square');
            $table->index('number_bedroom', 'idx_re_properties_bedroom');
            $table->index('number_bathroom', 'idx_re_properties_bathroom');
            $table->index('number_floor', 'idx_re_properties_floor');
            $table->index('project_id', 'idx_re_properties_project_id');
            $table->index(['author_id', 'author_type'], 'idx_re_properties_author');
            $table->index('country_id', 'idx_re_properties_country_id');
            $table->index('currency_id', 'idx_re_properties_currency_id');
        });

        if (Schema::hasTable('re_property_features')) {
            Schema::table('re_property_features', function (Blueprint $table): void {
                $table->index('property_id', 'idx_property_features_property_id');
                $table->index('feature_id', 'idx_property_features_feature_id');
            });
        }

        if (Schema::hasTable('re_property_categories')) {
            Schema::table('re_property_categories', function (Blueprint $table): void {
                $table->index('property_id', 'idx_property_categories_property_id');
                $table->index('category_id', 'idx_property_categories_category_id');
            });
        }
    }

    public function down(): void
    {
        Schema::table('re_properties', function (Blueprint $table): void {
            $table->dropIndex('idx_re_properties_type');
            $table->dropIndex('idx_re_properties_never_expired');
            $table->dropIndex('idx_re_properties_mod_status');
            $table->dropIndex('idx_re_properties_price');
            $table->dropIndex('idx_re_properties_square');
            $table->dropIndex('idx_re_properties_bedroom');
            $table->dropIndex('idx_re_properties_bathroom');
            $table->dropIndex('idx_re_properties_floor');
            $table->dropIndex('idx_re_properties_project_id');
            $table->dropIndex('idx_re_properties_author');
            $table->dropIndex('idx_re_properties_country_id');
            $table->dropIndex('idx_re_properties_currency_id');
        });

        if (Schema::hasTable('re_property_features')) {
            Schema::table('re_property_features', function (Blueprint $table): void {
                $table->dropIndex('idx_property_features_property_id');
                $table->dropIndex('idx_property_features_feature_id');
            });
        }

        if (Schema::hasTable('re_property_categories')) {
            Schema::table('re_property_categories', function (Blueprint $table): void {
                $table->dropIndex('idx_property_categories_property_id');
                $table->dropIndex('idx_property_categories_category_id');
            });
        }
    }
};
