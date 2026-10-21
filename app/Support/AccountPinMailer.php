<?php

namespace App\Support;

use Botble\Base\Facades\EmailHandler;
use Illuminate\Support\Facades\Log;
use Throwable;

final class AccountPinMailer
{
    public static function send(string $email, string $accountName, string $pin, string $context = 'auth'): bool
    {
        try {
            $isForgot = $context === 'forgot-password';

            $handler = EmailHandler::setModule(REAL_ESTATE_MODULE_SCREEN_NAME)
                ->setVariableValues([
                    'account_name' => $accountName,
                    'account_email' => $email,
                    'account_password' => $pin,
                    'pin_context' => $isForgot ? 'forgot' : 'register',
                ]);

            $subject = $isForgot
                ? 'Your new Serik Realty login PIN'
                : null;

            $sent = $handler->sendUsingTemplate('account-registered', $email, [], false, 'plugins', $subject);

            if (! $sent) {
                Log::warning('[AccountPinMailer] template disabled or not sent', [
                    'email' => $email,
                    'context' => $context,
                ]);
            }

            return (bool) $sent;
        } catch (Throwable $e) {
            Log::error('[AccountPinMailer] failed', [
                'email' => $email,
                'context' => $context,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
