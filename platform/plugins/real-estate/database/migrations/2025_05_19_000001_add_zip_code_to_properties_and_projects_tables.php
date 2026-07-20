<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        if (! Schema::hasColumn('re_properties', 'zip_code')) {
            Schema::table('re_properties', function (Blueprint $table): void {
                $table->string('zip_code', 20)->nullable()->after('longitude');
            });
        }

        if (! Schema::hasColumn('re_projects', 'zip_code')) {
            Schema::table('re_projects', function (Blueprint $table): void {
                $table->string('zip_code', 20)->nullable()->after('longitude');
            });
        }
    }

    public function down(): void
    {
        Schema::table('re_properties', function (Blueprint $table): void {
            $table->dropColumn('zip_code');
        });

        Schema::table('re_projects', function (Blueprint $table): void {
            $table->dropColumn('zip_code');
        });
    }
};
