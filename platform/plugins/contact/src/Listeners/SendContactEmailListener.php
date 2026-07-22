<?php

namespace Botble\Contact\Listeners;

use App\Support\EmailRecipients;
use App\Support\SerikQueue;
use Botble\Base\Facades\EmailHandler;
use Botble\Contact\Events\SentContactEvent;
use Botble\Contact\Models\Contact;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;

class SendContactEmailListener implements ShouldQueue
{
    use Queueable;

    public function __construct()
    {
        $this->onQueue(SerikQueue::high());
    }

    public function handle(SentContactEvent $event): void
    {
        $contact = $event->data;

        if (! $contact instanceof Contact) {
            return;
        }

        $receiverEmails = EmailRecipients::contactNoticeRecipients();
        $customFields = $contact->custom_fields ?? [];

        $args = [];

        if ($contact->name && $contact->email) {
            $args = ['replyTo' => [$contact->name => $contact->email]];
        }

        $emailHandler = EmailHandler::setModule(CONTACT_MODULE_SCREEN_NAME)
            ->setVariableValues([
                'contact_name' => $contact->name,
                'contact_subject' => $contact->subject,
                'contact_email' => $contact->email,
                'contact_phone' => $contact->phone,
                'contact_address' => $contact->address,
                'contact_content' => $contact->content,
                'contact_custom_fields' => $customFields,
            ]);

        if (! $emailHandler->sendUsingTemplate('notice', $receiverEmails, $args)) {
            Log::warning('[mail] Contact notice not sent', [
                'contact_id' => $contact->getKey(),
                'to' => $receiverEmails,
            ]);
        }

        $args = ['replyTo' => is_array($receiverEmails) ? Arr::first($receiverEmails) : $receiverEmails];

        if (! $emailHandler->sendUsingTemplate('sender-confirmation', $contact->email, $args)) {
            Log::warning('[mail] Contact sender-confirmation not sent', [
                'contact_id' => $contact->getKey(),
                'to' => $contact->email,
            ]);
        }
    }
}
