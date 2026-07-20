<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::table('re_properties', function (Blueprint $table) {
            if (! Schema::hasColumn('re_properties', 'listing_contract_date')) {
                $table->dateTime('listing_contract_date')->nullable()->after('updated_at');
            }
            if (! Schema::hasColumn('re_properties', 'listing_modified_at')) {
                $table->dateTime('listing_modified_at')->nullable()->after('listing_contract_date');
            }
            if (! Schema::hasColumn('re_properties', 'close_date')) {
                $table->dateTime('close_date')->nullable()->after('listing_modified_at');
            }
        });

        $maxYear = (int) date('Y') + 1;

        DB::table('re_properties')
            ->whereNull('listing_contract_date')
            ->whereNotNull('created_at')
            ->whereYear('created_at', '>=', 2000)
            ->whereYear('created_at', '<=', $maxYear)
            ->update(['listing_contract_date' => DB::raw('created_at')]);

        DB::table('re_properties')
            ->whereNull('listing_modified_at')
            ->whereNotNull('updated_at')
            ->whereYear('updated_at', '>=', 2000)
            ->whereYear('updated_at', '<=', $maxYear)
            ->update(['listing_modified_at' => DB::raw('updated_at')]);

        DB::table('re_properties')
            ->whereNull('close_date')
            ->whereIn('MlsStatus', ['Sold', 'Sold Conditional', 'Sold Conditional Escape', 'Leased', 'Leased Conditional'])
            ->whereNotNull('updated_at')
            ->whereYear('updated_at', '>=', 2000)
            ->whereYear('updated_at', '<=', $maxYear)
            ->update(['close_date' => DB::raw('updated_at')]);
    }

    public function down(): void
    {
        Schema::table('re_properties', function (Blueprint $table) {
            if (Schema::hasColumn('re_properties', 'close_date')) {
                $table->dropColumn('close_date');
            }
            if (Schema::hasColumn('re_properties', 'listing_modified_at')) {
                $table->dropColumn('listing_modified_at');
            }
            if (Schema::hasColumn('re_properties', 'listing_contract_date')) {
                $table->dropColumn('listing_contract_date');
            }
        });
    }
};
