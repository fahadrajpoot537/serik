<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        if (! Schema::hasColumn('re_properties', 'external_id')) {
            return;
        }

        $duplicates = DB::table('re_properties')
            ->select('external_id', DB::raw('COUNT(*) as row_count'))
            ->whereNotNull('external_id')
            ->where('external_id', '!=', '')
            ->groupBy('external_id')
            ->having('row_count', '>', 1)
            ->limit(20)
            ->get();

        if ($duplicates->isNotEmpty()) {
            Log::warning('re_properties.external_id has duplicate values — using non-unique index only', [
                'duplicate_count_sample' => $duplicates->count(),
                'samples' => $duplicates->pluck('external_id')->all(),
            ]);
        }

        Schema::table('re_properties', function (Blueprint $table): void {
            if (Schema::hasIndex('re_properties', 'idx_re_properties_external_id')) {
                return;
            }

            // Legacy/manual index name from older deployments.
            if (Schema::hasIndex('re_properties', 're_properties_external_id_index')) {
                return;
            }

            $table->index('external_id', 'idx_re_properties_external_id');
        });
    }

    public function down(): void
    {
        Schema::table('re_properties', function (Blueprint $table): void {
            if (Schema::hasIndex('re_properties', 'idx_re_properties_external_id')) {
                $table->dropIndex('idx_re_properties_external_id');
            }
        });
    }
};
