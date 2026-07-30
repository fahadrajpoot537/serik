<?php

namespace App\Events;

use Botble\RealEstate\Models\Consult;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ConsultSubmitted
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public Consult $consult,
        public ?string $propertyName = null,
        public ?string $propertyUrl = null,
        public string $sourceLabel = 'Property Inquiry — serik.ca',
        public string $sourceTag = 'Property Detail Inquiry',
    ) {
    }
}
