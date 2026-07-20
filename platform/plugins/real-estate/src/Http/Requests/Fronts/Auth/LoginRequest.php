<?php

namespace Botble\RealEstate\Http\Requests\Fronts\Auth;

use Botble\Base\Rules\EmailRule;
use Botble\Support\Http\Requests\Request;
use Theme\homzen\Supports\RecaptchaHelper;

class LoginRequest extends Request
{
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', new EmailRule()],
            'password' => ['required', 'string'],
            'g-recaptcha-response' => ['required', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
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
