<?php

namespace App\Listeners;

use App\Support\MortgageCalculatorFormContext;
use Botble\Contact\Events\SentContactEvent;
use Botble\Contact\Models\Contact;

class ApplyMortgageCalculatorContactContext
{
    public function handle(SentContactEvent $event): void
    {
        $contact = $event->data;

        if (! $contact instanceof Contact) {
            return;
        }

        MortgageCalculatorFormContext::applyToContact($contact, request());
    }
}
