<?php

namespace Botble\Shortcode\Http\Requests;

use Botble\Support\Http\Requests\Request;

class RenderBlockUiRequest extends Request
{
    public function rules(): array
    {
        // Keep validation minimal — nested/typed attributes are normalized in the controller.
        return [
            'name' => ['required'],
            'attributes' => ['nullable'],
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
