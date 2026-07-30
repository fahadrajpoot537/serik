<?php

namespace App\Listeners;

use App\Services\GoHighLevel\GoHighLevelLeadService;
use Botble\Contact\Events\SentContactEvent;
use Botble\Contact\Models\Contact;

class PushContactLeadToGoHighLevel
{
    public function __construct(protected GoHighLevelLeadService $ghl)
    {
    }

    public function handle(SentContactEvent $event): void
    {
        $contact = $event->data;

        if (! $contact instanceof Contact) {
            return;
        }

        $this->ghl->pushAfterResponse([
            'name' => (string) ($contact->name ?? ''),
            'email' => (string) ($contact->email ?? ''),
            'phone' => (string) ($contact->phone ?? ''),
            'subject' => (string) ($contact->subject ?? ''),
            'message' => (string) ($contact->content ?? ''),
            'source' => 'Contact Form — serik.ca',
            'tags' => ['Website Lead', 'Contact Form', 'Serik Realty'],
        ]);
    }
}
