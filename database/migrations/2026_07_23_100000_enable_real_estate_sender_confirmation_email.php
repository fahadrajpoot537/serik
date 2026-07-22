<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        if (! function_exists('setting')) {
            return;
        }

        setting()->set([
            'plugins_real-estate_sender-confirmation_status' => '1',
        ])->save();
    }

    public function down(): void
    {
        if (! function_exists('setting')) {
            return;
        }

        setting()->forget('plugins_real-estate_sender-confirmation_status')->save();
    }
};
