<?php

namespace App\Listeners;

use App\Services\GoHighLevel\GoHighLevelLeadService;
use App\Support\AgentInquiryFormContext;
use App\Support\MortgageCalculatorFormContext;
use App\Support\ServiceInquiryFormContext;
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

        $inquiryType = MortgageCalculatorFormContext::inquiryTypeFromCustomFields(
            is_array($contact->custom_fields) ? $contact->custom_fields : null
        ) ?? MortgageCalculatorFormContext::validatedInquiryType(
            request()->input(MortgageCalculatorFormContext::REQUEST_INQUIRY_KEY)
        );

        if (MortgageCalculatorFormContext::isActive() && $inquiryType !== null) {
            $this->ghl->pushAfterResponse(MortgageCalculatorFormContext::buildGhlLead(
                (string) ($contact->name ?? ''),
                (string) ($contact->email ?? ''),
                (string) ($contact->phone ?? ''),
                (string) ($contact->content ?? ''),
                $inquiryType
            ));

            return;
        }

        $serviceKey = ServiceInquiryFormContext::activeKey();
        if ($serviceKey !== null) {
            $this->ghl->pushAfterResponse(ServiceInquiryFormContext::buildGhlLead(
                (string) ($contact->name ?? ''),
                (string) ($contact->email ?? ''),
                (string) ($contact->phone ?? ''),
                (string) ($contact->content ?? ''),
                $serviceKey
            ));

            return;
        }

        if (AgentInquiryFormContext::isActive()) {
            $agent = AgentInquiryFormContext::resolveAgent();
            if ($agent !== null) {
                $this->ghl->pushAfterResponse(AgentInquiryFormContext::buildGhlLead(
                    (string) ($contact->name ?? ''),
                    (string) ($contact->email ?? ''),
                    (string) ($contact->phone ?? ''),
                    (string) ($contact->content ?? ''),
                    $agent
                ));

                return;
            }
        }

        $this->ghl->pushAfterResponse([
            'name' => (string) ($contact->name ?? ''),
            'email' => (string) ($contact->email ?? ''),
            'phone' => (string) ($contact->phone ?? ''),
            'subject' => (string) ($contact->subject ?? ''),
            'message' => (string) ($contact->content ?? ''),
            'source' => 'Contact Us Form — serik.ca',
            'tags' => ['Website Lead', 'Contact Us Form', 'Serik Realty'],
        ]);
    }
}
