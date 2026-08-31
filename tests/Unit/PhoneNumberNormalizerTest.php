<?php

namespace Tests\Unit;

use App\Rules\ConsultPhoneNumber;
use App\Support\PhoneNumberNormalizer;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class PhoneNumberNormalizerTest extends TestCase
{
    public function test_blank_values_are_required_errors(): void
    {
        foreach ([null, '', '   ', "\t", '---', '()', '.', '+', '   -  '] as $value) {
            $parsed = PhoneNumberNormalizer::parse($value);
            $this->assertFalse($parsed['ok'], var_export($value, true));
            $this->assertSame(PhoneNumberNormalizer::ERROR_REQUIRED, $parsed['error'], var_export($value, true));
        }
    }

    public function test_invalid_values_are_rejected(): void
    {
        foreach ([
            '123',
            '416555',
            '416-555',
            '1234567890123456',
            str_repeat('1', 16),
            'abc4165550123',
            '++14165550123',
            '+1',
            '+44',
            '+01234567890',
            '0014165550123',
            '141655',
            '0114165550123',
        ] as $value) {
            $parsed = PhoneNumberNormalizer::parse($value);
            $this->assertFalse($parsed['ok'], $value);
            $this->assertSame(PhoneNumberNormalizer::ERROR_INVALID, $parsed['error'], $value);
            $this->assertNull(PhoneNumberNormalizer::normalize($value), $value);
        }
    }

    public function test_formatted_north_american_numbers_normalize_to_e164(): void
    {
        $this->assertSame('+14165550123', PhoneNumberNormalizer::normalize('(416) 555-0123'));
        $this->assertSame('+14165550123', PhoneNumberNormalizer::normalize('416-555-0123'));
        $this->assertSame('+14165550123', PhoneNumberNormalizer::normalize('416 555 0123'));
        $this->assertSame('+14165550123', PhoneNumberNormalizer::normalize('4165550123'));
        $this->assertSame('+14165550123', PhoneNumberNormalizer::normalize('1 416 555 0123'));
        $this->assertSame('+14165550123', PhoneNumberNormalizer::normalize('14165550123'));
    }

    public function test_plus_one_is_not_double_prefixed(): void
    {
        $this->assertSame('+14165550123', PhoneNumberNormalizer::normalize('+1 416 555 0123'));
        $this->assertSame('+14165550123', PhoneNumberNormalizer::normalize('+14165550123'));
        $this->assertSame('+14165550123', PhoneNumberNormalizer::normalize('+1 (416) 555-0123'));
    }

    public function test_supported_international_number_keeps_explicit_prefix(): void
    {
        $this->assertSame('+442079460958', PhoneNumberNormalizer::normalize('+44 20 7946 0958'));
        $this->assertSame('+33123456789', PhoneNumberNormalizer::normalize('+33 1 23 45 67 89'));
    }

    public function test_local_numbers_use_configured_default_country(): void
    {
        $this->assertSame('CA', PhoneNumberNormalizer::defaultRegion());
        $this->assertSame('1', PhoneNumberNormalizer::defaultCountryCallingCode());
        $this->assertSame('+14165550123', PhoneNumberNormalizer::normalize('4165550123'));
    }

    public function test_ambiguous_international_numbers_without_plus_are_not_guessed(): void
    {
        $this->assertNull(PhoneNumberNormalizer::normalize('20794609581'));
        $this->assertNull(PhoneNumberNormalizer::normalize('02079460958'));
        $this->assertNull(PhoneNumberNormalizer::normalize('612345678'));
    }

    public function test_validation_rule_messages(): void
    {
        $this->assertSame('Phone number is required.', $this->firstError(null));
        $this->assertSame('Phone number is required.', $this->firstError(''));
        $this->assertSame('Phone number is required.', $this->firstError('   '));
        $this->assertSame('Phone number is required.', $this->firstError('---'));
        $this->assertSame('Please enter a valid phone number.', $this->firstError('123'));
        $this->assertSame('Please enter a valid phone number.', $this->firstError(str_repeat('1', 16)));
        $this->assertSame('Please enter a valid phone number.', $this->firstError('+44'));
        $this->assertNull($this->firstError('(416) 555-0123'));
        $this->assertNull($this->firstError('+1 416 555 0123'));
        $this->assertNull($this->firstError('+44 20 7946 0958'));
    }

    public function test_missing_phone_field_is_rejected_by_required_rule(): void
    {
        $validator = Validator::make(
            ['name' => 'Jane'],
            ['phone' => ['bail', 'required', 'string', new ConsultPhoneNumber()]],
            ['phone.required' => PhoneNumberNormalizer::REQUIRED_MESSAGE]
        );

        $this->assertTrue($validator->fails());
        $this->assertSame('Phone number is required.', $validator->errors()->first('phone'));
    }

    private function firstError(mixed $value): ?string
    {
        $validator = Validator::make(
            ['phone' => $value],
            ['phone' => ['bail', 'required', 'string', new ConsultPhoneNumber()]],
            ['phone.required' => PhoneNumberNormalizer::REQUIRED_MESSAGE]
        );

        return $validator->fails() ? $validator->errors()->first('phone') : null;
    }
}
