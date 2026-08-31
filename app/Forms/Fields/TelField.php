<?php

namespace App\Forms\Fields;

use Botble\Base\Forms\Fields\TextField;

class TelField extends TextField
{
    protected function getTemplate(): string
    {
        return 'forms.fields.tel';
    }
}
