<?php

namespace App\Listeners;

use App\Events\ConsultSubmitted;
use App\Services\GoHighLevel\GoHighLevelLeadService;
use App\Support\PhoneNumberNormalizer;

class PushConsultLeadToGoHighLevel
{
    public function __construct(protected GoHighLevelLeadService $ghl)
    {
    }

    public function handle(ConsultSubmitted $event): void
    {
        $consult = $event->consult;
        $phone = PhoneNumberNormalizer::normalize((string) ($consult->phone ?? '')) ?? '';

        $this->ghl->pushAfterResponse([
            'name' => (string) ($consult->name ?? ''),
            'email' => (string) ($consult->email ?? ''),
            'phone' => $phone,
            'message' => (string) ($consult->content ?? ''),
            'property_name' => $event->propertyName,
            'property_url' => $event->propertyUrl,
            'source' => $event->sourceLabel,
            'tags' => ['Website Lead', 'Property Inquiry', 'Schedule Viewing', $event->sourceTag, 'Serik Realty'],
        ]);
    }
}
