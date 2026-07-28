<?php

namespace App\Support;

use Illuminate\Support\Arr;

final class EmailRecipients
{
    public const CONTACT_FALLBACK = 'info@serik.ca';

    /**
     * Contact-form admin notice recipients from Admin → Contact settings.
     * Always includes info@serik.ca so office never misses a lead.
     */
    public static function contactNoticeRecipients(): string|array
    {
        $receiverEmails = [];

        if ($receiverEmailsSetting = setting('receiver_emails', '')) {
            $receiverEmailsSetting = trim((string) $receiverEmailsSetting);
            $decoded = json_decode($receiverEmailsSetting, true);

            if (is_array($decoded)) {
                $receiverEmails = collect($decoded)->pluck('value')->all();
            }
        }

        if ($receiverEmails === []) {
            $admin = get_admin_email();
            $receiverEmails = $admin instanceof \Illuminate\Support\Collection
                ? $admin->filter()->values()->all()
                : array_filter((array) $admin);
        }

        $receiverEmails = array_values(array_unique(array_filter(array_map(
            static fn ($email) => strtolower(trim((string) $email)),
            $receiverEmails
        ))));

        if (! in_array(self::CONTACT_FALLBACK, $receiverEmails, true)) {
            $receiverEmails[] = self::CONTACT_FALLBACK;
        }

        return count($receiverEmails) === 1 ? Arr::first($receiverEmails) : $receiverEmails;
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
