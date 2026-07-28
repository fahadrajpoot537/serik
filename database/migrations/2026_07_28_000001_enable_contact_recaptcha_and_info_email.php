<?php

use Botble\Setting\Facades\Setting;
use Illuminate\Database\Migrations\Migration;

return new class () extends Migration {
    public function up(): void
    {
        $receiverEmails = setting('receiver_emails', '');
        $hasReceivers = false;

        if (is_string($receiverEmails) && $receiverEmails !== '') {
            $decoded = json_decode($receiverEmails, true);
            $hasReceivers = is_array($decoded) && collect($decoded)->pluck('value')->filter()->isNotEmpty();
        }

        // Do NOT enable Botble global captcha here — it breaks login by loading a
        // second Google api.js onload. Contact captcha is wired via RecaptchaHelper.
        $payload = [];

        if (! $hasReceivers) {
            $payload['receiver_emails'] = json_encode([
                ['value' => 'info@serik.ca'],
            ]);
        }

        Setting::set($payload)->save();
    }

    public function down(): void
    {
        // Keep captcha/receivers enabled — do not disable on rollback.
    }
};
