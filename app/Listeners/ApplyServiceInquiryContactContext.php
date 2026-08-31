<?php

namespace App\Listeners;

use App\Support\AgentInquiryFormContext;
use App\Support\ServiceInquiryFormContext;
use Botble\Contact\Events\SentContactEvent;
use Botble\Contact\Models\Contact;

class ApplyServiceInquiryContactContext
{
    public function handle(SentContactEvent $event): void
    {
        $contact = $event->data;

        if (! $contact instanceof Contact) {
            return;
        }

        $request = request();

        if (ServiceInquiryFormContext::isActive($request)) {
            ServiceInquiryFormContext::applyToContact($contact, $request);

            return;
        }

        if (AgentInquiryFormContext::isActive($request)) {
            AgentInquiryFormContext::applyToContact($contact, $request);
        }
    }
}
