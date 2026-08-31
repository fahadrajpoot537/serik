<?php

namespace App\Rules;

use App\Support\PhoneNumberNormalizer;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ConsultPhoneNumber implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $parsed = PhoneNumberNormalizer::parse($value);

        if ($parsed['ok']) {
            return;
        }

        $fail(PhoneNumberNormalizer::messageForError($parsed['error']));
    }
}
