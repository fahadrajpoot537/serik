<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('re_accounts')) {
            return;
        }

        Schema::table('re_accounts', function (Blueprint $table): void {
            if (! Schema::hasColumn('re_accounts', 'professional_title')) {
                $table->string('professional_title', 160)->nullable()->after('company');
            }
            if (! Schema::hasColumn('re_accounts', 'short_bio')) {
                $table->string('short_bio', 400)->nullable()->after('professional_title');
            }
            if (! Schema::hasColumn('re_accounts', 'specialties')) {
                $table->json('specialties')->nullable()->after('short_bio');
            }
            if (! Schema::hasColumn('re_accounts', 'service_areas')) {
                $table->json('service_areas')->nullable()->after('specialties');
            }
            if (! Schema::hasColumn('re_accounts', 'languages')) {
                $table->json('languages')->nullable()->after('service_areas');
            }
            if (! Schema::hasColumn('re_accounts', 'contact_enabled')) {
                $table->boolean('contact_enabled')->default(true)->after('is_public_profile');
            }
            if (! Schema::hasColumn('re_accounts', 'display_order')) {
                $table->unsignedInteger('display_order')->default(0)->after('is_featured');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('re_accounts')) {
            return;
        }

        Schema::table('re_accounts', function (Blueprint $table): void {
            $drop = [];
            foreach ([
                'professional_title',
                'short_bio',
                'specialties',
                'service_areas',
                'languages',
                'contact_enabled',
                'display_order',
            ] as $column) {
                if (Schema::hasColumn('re_accounts', $column)) {
                    $drop[] = $column;
                }
            }
            if ($drop !== []) {
                $table->dropColumn($drop);
            }
        });
    }
};
