<?php

namespace Botble\RealEstate\Notifications;

use Botble\Base\Facades\EmailHandler;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\HtmlString;

class ConfirmEmailNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        $expirationMinutes = (int) setting('real_estate_verification_expire_minutes', 60);

        if (! $expirationMinutes) {
            $expirationMinutes = (int) config('plugins.real-estate.general.verification_expire_minutes', 60);
        }

        $emailHandler = EmailHandler::setModule(REAL_ESTATE_MODULE_SCREEN_NAME)
            ->setType('plugins')
            ->setTemplate('confirm-email')
            ->addTemplateSettings(REAL_ESTATE_MODULE_SCREEN_NAME, config('plugins.real-estate.email', []))
            ->setVariableValue('verify_link', URL::temporarySignedRoute(
                'public.account.confirm',
                Carbon::now()->addMinutes($expirationMinutes),
                ['user' => $notifiable->id]
            ));

        return (new MailMessage())
            ->view(['html' => new HtmlString($emailHandler->getContent())])
            ->subject($emailHandler->getSubject());
    }
}
