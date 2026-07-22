<?php

namespace Botble\Newsletter\Listeners;

use App\Support\SerikQueue;
use Botble\Newsletter\Events\UnsubscribeNewsletterEvent;
use Botble\Newsletter\Facades\Newsletter;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;

class RemoveSubscriberToMailchimpContactListListener implements ShouldQueue
{
    use Queueable;

    public function __construct()
    {
        $this->onQueue(SerikQueue::high());
    }

    public function handle(UnsubscribeNewsletterEvent $event): void
    {
        if (! setting('enable_newsletter_contacts_list_api')) {
            return;
        }

        $mailchimpApiKey = setting('newsletter_mailchimp_api_key');
        $mailchimpListId = setting('newsletter_mailchimp_list_id');

        if (! $mailchimpApiKey || ! $mailchimpListId) {
            return;
        }

        Newsletter::driver('mailchimp')->unsubscribe($event->newsletter->email);
    }
}
