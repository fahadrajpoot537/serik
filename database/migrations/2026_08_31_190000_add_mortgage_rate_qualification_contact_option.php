<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    private const FIELD_NAME = 'Are you a Landlord or Tenant?';

    private const OPTION = 'Mortgage Rate Qualification';

    public function up(): void
    {
        if (! Schema::hasTable('contact_custom_fields') || ! Schema::hasTable('contact_custom_field_options')) {
            return;
        }

        $field = DB::table('contact_custom_fields')
            ->where('name', self::FIELD_NAME)
            ->first();

        if ($field === null) {
            return;
        }

        $exists = DB::table('contact_custom_field_options')
            ->where('custom_field_id', $field->id)
            ->where(function ($query): void {
                $query->where('value', self::OPTION)
                    ->orWhere('label', self::OPTION);
            })
            ->exists();

        if ($exists) {
            return;
        }

        $maxOrder = (int) DB::table('contact_custom_field_options')
            ->where('custom_field_id', $field->id)
            ->max('order');

        DB::table('contact_custom_field_options')->insert([
            'custom_field_id' => $field->id,
            'label' => self::OPTION,
            'value' => self::OPTION,
            'order' => $maxOrder + 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        if (! Schema::hasTable('contact_custom_fields') || ! Schema::hasTable('contact_custom_field_options')) {
            return;
        }

        $field = DB::table('contact_custom_fields')
            ->where('name', self::FIELD_NAME)
            ->first();

        if ($field === null) {
            return;
        }

        DB::table('contact_custom_field_options')
            ->where('custom_field_id', $field->id)
            ->where(function ($query): void {
                $query->where('value', self::OPTION)
                    ->orWhere('label', self::OPTION);
            })
            ->delete();
    }
};
