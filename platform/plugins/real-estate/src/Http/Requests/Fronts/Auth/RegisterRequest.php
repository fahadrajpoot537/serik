<?php

namespace Botble\RealEstate\Http\Requests\Fronts\Auth;

use Botble\Base\Facades\BaseHelper;
use Botble\Base\Rules\EmailRule;
use Botble\RealEstate\Models\Account;
use Botble\RealEstate\Supports\AccountRegistrationExpiry;
use Botble\Support\Http\Requests\Request;
use Illuminate\Validation\Rule;
use Theme\homzen\Supports\RecaptchaHelper;

class RegisterRequest extends Request
{
    protected function prepareForValidation(): void
    {
        foreach (['email', 'phone', 'username'] as $field) {
            $value = $this->input($field);

            if (! $value) {
                continue;
            }

            $account = Account::query()->where($field, $value)->first();

            if ($account) {
                AccountRegistrationExpiry::deleteIfExpired($account);
            }
        }
    }

    public function rules(): array
    {
        $table = (new Account())->getTable();

        return [
            'first_name' => ['required', 'string', 'max:120', 'min:2'],

            'last_name' => ['nullable', 'string', 'max:120', 'min:2'],

            'username' => [
                Rule::requiredIf(fn () => ! setting('real_estate_hide_username_in_registration_page', false)),
                'string',
                'max:120',
                'min:2',
                Rule::unique($table, 'username'),
            ],

            'email' => [
                'required',
                'max:60',
                'min:6',
                new EmailRule(),
                Rule::unique($table, 'email'), // ✅ FIXED (consistent)
            ],

            'phone' => [
                'required',
                'string',
                ...explode('|', BaseHelper::getPhoneValidationRule()),
                // Same phone may be used on multiple accounts (email remains unique).
            ],

            'password' => [
                'nullable', // keep if you're using OTP or auto password
                'min:6',
                'confirmed',
            ],

            'agree_terms_and_policy' => ['required', 'accepted'],
            'g-recaptcha-response' => ['required', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'email.unique' => 'This email is already registered.',
            'username.unique' => 'Username is already taken.',
            'phone.required' => 'Phone number is required.',
            'agree_terms_and_policy.accepted' => 'You must agree to the Terms of Use to register.',
            'g-recaptcha-response.required' => 'Please complete the reCAPTCHA verification.',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if ($validator->errors()->has('g-recaptcha-response')) {
                return;
            }

            if (! RecaptchaHelper::verify($this->input('g-recaptcha-response'))) {
                $validator->errors()->add('g-recaptcha-response', 'reCAPTCHA verification failed. Please try again.');
            }
        });
    }
}