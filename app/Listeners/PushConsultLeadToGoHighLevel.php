<?php

namespace App\Listeners;

use App\Events\ConsultSubmitted;
use App\Services\GoHighLevel\GoHighLevelLeadService;

class PushConsultLeadToGoHighLevel
{
    public function __construct(protected GoHighLevelLeadService $ghl)
    {
    }

    public function handle(ConsultSubmitted $event): void
    {
        $consult = $event->consult;

        $this->ghl->pushAfterResponse([
            'name' => (string) ($consult->name ?? ''),
            'email' => (string) ($consult->email ?? ''),
            'phone' => (string) ($consult->phone ?? ''),
            'message' => (string) ($consult->content ?? ''),
            'property_name' => $event->propertyName,
            'property_url' => $event->propertyUrl,
            'source' => $event->sourceLabel,
            'tags' => ['Website Lead', 'Property Inquiry', 'Schedule Viewing', 'Serik Realty'],
        ]);
    }
}
