<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Indexes that make the "Last N days" date filters (map + listing) fast.
     *
     * Root cause before this migration: the date-filtered map query filtered on
     * listing_contract_date / close_date / purchase_contract_date but no index
     * covered those columns, so MySQL fell back to the low-selectivity
     * moderation_status index and scanned ~68k rows (~4s). Under load the
     * frontend AbortController cancelled the request => "date filter returns
     * nothing". These composite indexes let MySQL seek approved rows and
     * range-scan the recent date window instead.
     */
    private array $indexes = [
        // Active "Listed On" date windows (New / Price Change / Extension / ...).
        'idx_re_props_ms_lcd' => 'moderation_status, listing_contract_date',
        // Sold-close date windows.
        'idx_re_props_ms_closed' => 'moderation_status, close_date',
        // Sold-purchase (firm) date windows.
        'idx_re_props_ms_purchase' => 'moderation_status, purchase_contract_date',
    ];

    public function up(): void
    {
        if (! Schema::hasTable('re_properties')) {
            return;
        }

        $existing = collect(DB::select('SHOW INDEX FROM re_properties'))
            ->pluck('Key_name')
            ->unique();

        foreach ($this->indexes as $name => $columns) {
            if (! $existing->contains($name)) {
                DB::statement("CREATE INDEX {$name} ON re_properties ({$columns})");
            }
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('re_properties')) {
            return;
        }

        $existing = collect(DB::select('SHOW INDEX FROM re_properties'))
            ->pluck('Key_name')
            ->unique();

        foreach (array_keys($this->indexes) as $name) {
            if ($existing->contains($name)) {
                DB::statement("DROP INDEX {$name} ON re_properties");
            }
        }
    }
};
