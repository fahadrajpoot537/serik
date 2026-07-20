<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::table('re_properties', function (Blueprint $table) {
            if (! Schema::hasColumn('re_properties', 'purchase_contract_date')) {
                $table->dateTime('purchase_contract_date')->nullable()->after('close_date');
            }
        });

        $maxYear = (int) date('Y') + 1;

        DB::table('re_properties')
            ->whereNull('purchase_contract_date')
            ->whereNotNull('close_date')
            ->whereYear('close_date', '>=', 2000)
            ->whereYear('close_date', '<=', $maxYear)
            ->update(['purchase_contract_date' => DB::raw('close_date')]);
    }

    public function down(): void
    {
        Schema::table('re_properties', function (Blueprint $table) {
            if (Schema::hasColumn('re_properties', 'purchase_contract_date')) {
                $table->dropColumn('purchase_contract_date');
            }
        });
    }
};
