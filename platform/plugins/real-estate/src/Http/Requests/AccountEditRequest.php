<?php

namespace Botble\RealEstate\Http\Requests;

use Botble\Support\Http\Requests\Request;

class AccountEditRequest extends Request
{
    public function rules(): array
    {
        $rules = [
            'first_name' => 'required|string|max:120|min:2',
            'last_name' => 'required|string|max:120|min:2',
            'username' => 'required|string|max:60|min:2|unique:re_accounts,username,' . $this->route('account.id'),
            'email' => 'required|max:60|min:6|email|unique:re_accounts,email,' . $this->route('account.id'),
        ];

        if ($this->boolean('is_change_password')) {
            $rules['password'] = 'required|min:6|confirmed';
        }

        $rules['is_blocked'] = 'nullable|boolean';
        $rules['blocked_reason'] = 'nullable|string|max:500|required_if:is_blocked,1';

        return $rules;
    }

    public function messages(): array
    {
        return [
            'blocked_reason.required_if' => trans('plugins/real-estate::account.blocked_reason_required'),
        ];
    }
}
