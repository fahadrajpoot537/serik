<?php

namespace Botble\RealEstate\Http\Requests;

use Botble\Support\Http\Requests\Request;

class AccountCreateRequest extends Request
{
    public function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:120', 'min:2'],
            'last_name' => ['required', 'string', 'max:120', 'min:2'],
            'username' => ['required', 'string', 'max:60', 'min:2', 'unique:re_accounts,username'],
            'email' => ['required', 'max:60', 'min:6', 'email', 'unique:re_accounts'],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
            'description' => ['nullable', 'string', 'max:400'],
            'is_blocked' => ['nullable', 'boolean'],
            'blocked_reason' => ['nullable', 'string', 'max:500', 'required_if:is_blocked,1'],
        ];
    }

    public function messages(): array
    {
        return [
            'blocked_reason.required_if' => trans('plugins/real-estate::account.blocked_reason_required'),
        ];
    }
}
