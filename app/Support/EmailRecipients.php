<?php

namespace App\Support;

use Illuminate\Support\Arr;

final class EmailRecipients
{
    /**
     * Contact-form admin notice recipients from Admin → Contact settings.
     * Returns null when unset so EmailHandler falls back to get_admin_email().
     */
    public static function contactNoticeRecipients(): string|array|null
    {
        $receiverEmails = null;

        if ($receiverEmailsSetting = setting('receiver_emails', '')) {
            $receiverEmails = trim((string) $receiverEmailsSetting);
        }

        if ($receiverEmails) {
            $receiverEmails = collect(json_decode($receiverEmails, true))
                ->pluck('value')
                ->all();
        }

        if (is_array($receiverEmails)) {
            $receiverEmails = array_filter($receiverEmails);

            if (count($receiverEmails) === 1) {
                $receiverEmails = Arr::first($receiverEmails);
            }
        }

        return $receiverEmails ?: null;
    }

    /**
     * Real-estate consult notice: property/project author when available, otherwise admin.
     */
    public static function consultNoticeRecipient(?string $authorEmail = null): string|array|null
    {
        $authorEmail = trim((string) $authorEmail);

        return $authorEmail !== '' ? $authorEmail : null;
    }
}
