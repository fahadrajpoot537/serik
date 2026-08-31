<?php

namespace App\Support;

final class OfficePhone
{
    public const FALLBACK_DISPLAY = '+1 (647) 578-9400';

    public const FALLBACK_E164 = '+16475789400';

    public static function display(): string
    {
        $hotline = '';

        try {
            $hotline = trim((string) theme_option('hotline', ''));
        } catch (\Throwable) {
            $hotline = '';
        }

        if ($hotline !== '') {
            return $hotline;
        }

        $configured = trim((string) config('serik.phone.office_display', self::FALLBACK_DISPLAY));

        return $configured !== '' ? $configured : self::FALLBACK_DISPLAY;
    }

    public static function e164(): string
    {
        $parsed = PhoneNumberNormalizer::parse(self::display());
        if ($parsed['ok'] ?? false) {
            return $parsed['e164'];
        }

        $configured = trim((string) config('serik.phone.office_e164', self::FALLBACK_E164));

        return $configured !== '' ? $configured : self::FALLBACK_E164;
    }
}
