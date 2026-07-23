<?php

namespace Botble\Newsletter\Listeners;

use App\Support\SerikQueue;
use Botble\Base\Facades\EmailHandler;
use Botble\Base\Facades\Html;
use Botble\Newsletter\Events\SubscribeNewsletterEvent;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;

class SendEmailNotificationAboutNewSubscriberListener implements ShouldQueue
{
    use Queueable;

    public function viaQueue(): string
    {
        return SerikQueue::high();
    }

    public function handle(SubscribeNewsletterEvent $event): void
    {
        $unsubscribeUrl = URL::signedRoute('public.newsletter.unsubscribe', ['user' => $event->newsletter->id]);

        $mailer = EmailHandler::setModule(NEWSLETTER_MODULE_SCREEN_NAME)->setVariableValues([
            'newsletter_name' => $event->newsletter->name ?? 'N/A',
            'newsletter_email' => $event->newsletter->email,
            'newsletter_unsubscribe_link' => Html::link($unsubscribeUrl, trans('plugins/newsletter::newsletter.here'))->toHtml(),
            'newsletter_unsubscribe_url' => $unsubscribeUrl,
        ]);

        if (! $mailer->sendUsingTemplate('subscriber_email', $event->newsletter->email)) {
            Log::warning('[mail] Newsletter subscriber confirmation not sent', [
                'newsletter_id' => $event->newsletter->getKey(),
                'to' => $event->newsletter->email,
            ]);
        }

        if (! $mailer->sendUsingTemplate('admin_email')) {
            Log::warning('[mail] Newsletter admin notification not sent', [
                'newsletter_id' => $event->newsletter->getKey(),
            ]);
        }
    }
}
