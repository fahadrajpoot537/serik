<?php

use Botble\RealEstate\Enums\PropertyStatusEnum;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('re_properties') || ! Schema::hasColumn('re_properties', 'status')) {
            return;
        }

        DB::table('re_properties')
            ->where(function ($query) {
                $query->whereNull('status')
                    ->orWhere('status', '');
            })
            ->update(['status' => PropertyStatusEnum::DRAFT]);
    }

    public function down(): void
    {
        // Data cleanup only — no rollback.
    }
};
