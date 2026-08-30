<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Canonical first-time buyer page slug: /fthb → /first-time-house-buyer
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('slugs')) {
            return;
        }

        $taken = DB::table('slugs')->where('key', 'first-time-house-buyer')->exists();
        if ($taken) {
            return;
        }

        DB::table('slugs')
            ->where('key', 'fthb')
            ->update(['key' => 'first-time-house-buyer']);
    }

    public function down(): void
    {
        if (! Schema::hasTable('slugs')) {
            return;
        }

        $taken = DB::table('slugs')->where('key', 'fthb')->exists();
        if ($taken) {
            return;
        }

        DB::table('slugs')
            ->where('key', 'first-time-house-buyer')
            ->update(['key' => 'fthb']);
    }
};
