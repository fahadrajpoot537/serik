<?php

namespace App\Support;

final class PhoneNumberNormalizer
{
    public const ERROR_REQUIRED = 'required';

    public const ERROR_INVALID = 'invalid';

    public const REQUIRED_MESSAGE = 'Phone number is required.';

    public const INVALID_MESSAGE = 'Please enter a valid phone number.';

    public const DEFAULT_REGION = 'CA';

    public const DEFAULT_COUNTRY_CALLING_CODE = '1';

    public static function defaultRegion(): string
    {
        return (string) config('serik.phone.default_region', self::DEFAULT_REGION);
    }

    public static function defaultCountryCallingCode(): string
    {
        $code = preg_replace('/\D+/', '', (string) config(
            'serik.phone.default_country_calling_code',
            self::DEFAULT_COUNTRY_CALLING_CODE
        )) ?? '';

        return $code !== '' ? $code : self::DEFAULT_COUNTRY_CALLING_CODE;
    }

    public static function isBlank(mixed $value): bool
    {
        if ($value === null) {
            return true;
        }

        if (! is_string($value) && ! is_numeric($value)) {
            return true;
        }

        $trimmed = trim((string) $value);

        if ($trimmed === '') {
            return true;
        }

        $digits = preg_replace('/\D+/', '', $trimmed) ?? '';

        return $digits === '';
    }

    /**
     * @return array{ok: true, e164: string}|array{ok: false, error: self::ERROR_REQUIRED|self::ERROR_INVALID}
     */
    public static function parse(mixed $value): array
    {
        if ($value === null) {
            return ['ok' => false, 'error' => self::ERROR_REQUIRED];
        }

        if (is_numeric($value) && ! is_string($value)) {
            $value = (string) $value;
        }

        if (! is_string($value)) {
            return ['ok' => false, 'error' => self::ERROR_INVALID];
        }

        if (self::isBlank($value)) {
            return ['ok' => false, 'error' => self::ERROR_REQUIRED];
        }

        $e164 = self::normalize($value);

        if ($e164 === null) {
            return ['ok' => false, 'error' => self::ERROR_INVALID];
        }

        return ['ok' => true, 'e164' => $e164];
    }

    public static function normalize(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        if (is_numeric($value) && ! is_string($value)) {
            $value = (string) $value;
        }

        if (! is_string($value)) {
            return null;
        }

        $original = trim($value);

        if ($original === '') {
            return null;
        }

        if (preg_match('/[A-Za-z]/', $original)) {
            return null;
        }

        if (substr_count($original, '+') > 1) {
            return null;
        }

        $hasPlus = str_starts_with($original, '+');
        $digits = preg_replace('/\D+/', '', $original) ?? '';
        $count = strlen($digits);

        if ($count === 0 || $count > 15) {
            return null;
        }

        if ($hasPlus) {
            if ($digits[0] === '0' || $count < 8) {
                return null;
            }

            return '+' . $digits;
        }

        if (self::defaultCountryCallingCode() === '1') {
            if ($count === 10) {
                return self::isValidNanpNational($digits) ? '+1' . $digits : null;
            }

            if ($count === 11 && $digits[0] === '1') {
                $national = substr($digits, 1);

                return self::isValidNanpNational($national) ? '+' . $digits : null;
            }
        }

        return null;
    }

    public static function messageForError(string $error): string
    {
        return $error === self::ERROR_REQUIRED
            ? self::REQUIRED_MESSAGE
            : self::INVALID_MESSAGE;
    }

    private static function isValidNanpNational(string $digits): bool
    {
        if (strlen($digits) !== 10) {
            return false;
        }

        return $digits[0] !== '0' && $digits[0] !== '1'
            && $digits[3] !== '0' && $digits[3] !== '1';
    }
}
