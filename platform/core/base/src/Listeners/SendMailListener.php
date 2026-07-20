<?php

namespace Botble\Base\Listeners;

use Botble\Base\Events\SendMailEvent;
use Botble\Base\Facades\BaseHelper;
use Botble\Base\Supports\EmailAbstract;
use Exception;
use Illuminate\Contracts\Mail\Mailer;
use Illuminate\Support\Facades\Log;

/**
 * Sends immediately (not queued). Registration PIN emails must arrive in-request.
 * Exceptions are rethrown so callers/logs see real SMTP failures (was swallowing them).
 */
class SendMailListener
{
    public function __construct(protected Mailer $mailer)
    {
    }

    public function handle(SendMailEvent $event): void
    {
        try {
            $this->mailer->to($event->to)->send(new EmailAbstract($event->content, $event->title, $event->args));
        } catch (Exception $exception) {
            Log::error('[mail] Send failed: ' . $exception->getMessage(), [
                'to' => $event->to,
                'title' => $event->title,
            ]);
            BaseHelper::logError($exception);

            if ($event->debug) {
                throw $exception;
            }

            // Still throw so EmailHandler can mark failure — silent swallow hid SMTP outages.
            throw $exception;
        }
    }
}
