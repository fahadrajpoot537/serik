<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        if (! Schema::hasColumn('re_accounts', 'blocked_reason')) {
            Schema::table('re_accounts', function (Blueprint $table): void {
                $table->text('blocked_reason')->nullable()->after('blocked_at');
            });
        }
    }

    public function down(): void
    {
        Schema::table('re_accounts', function (Blueprint $table): void {
            $table->dropColumn('blocked_reason');
        });
    }
};
