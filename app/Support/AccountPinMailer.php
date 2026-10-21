<?php

namespace App\Support;

use Botble\Base\Facades\EmailHandler;
use Illuminate\Mail\MailManager;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

final class AccountPinMailer
{
    public static function send(string $email, string $accountName, string $pin, string $context = 'auth'): bool
    {
        $email = strtolower(trim($email));
        $isForgot = $context === 'forgot-password';

        // Ensure admin panel mail settings (Resend key / from) are applied.
        try {
            app(MailManager::class);
        } catch (Throwable $e) {
            // continue — Mail facade may still work
        }

        Log::info('[AccountPinMailer] sending', [
            'email' => $email,
            'context' => $context,
            'mailer' => config('mail.default'),
            'from' => config('mail.from.address'),
            'has_resend_key' => (bool) config('services.resend.key'),
        ]);

        try {
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

            if ($sent) {
                Log::info('[AccountPinMailer] template send ok', [
                    'email' => $email,
                    'context' => $context,
                ]);

                return true;
            }

            Log::warning('[AccountPinMailer] template disabled or returned false — trying direct mail', [
                'email' => $email,
                'context' => $context,
            ]);
        } catch (Throwable $e) {
            Log::error('[AccountPinMailer] template send failed — trying direct mail', [
                'email' => $email,
                'context' => $context,
                'error' => $e->getMessage(),
            ]);
        }

        return self::sendDirect($email, $accountName, $pin, $isForgot, $context);
    }

    private static function sendDirect(
        string $email,
        string $accountName,
        string $pin,
        bool $isForgot,
        string $context
    ): bool {
        $name = trim($accountName) !== '' ? trim($accountName) : 'there';
        $subject = $isForgot
            ? 'Your new Serik Realty login PIN'
            : 'Your Serik Realty login PIN';

        $html = '<p>Hi ' . e($name) . ',</p>'
            . '<p>Here is your Serik Realty login PIN.</p>'
            . '<p>Your login email is: <strong>' . e($email) . '</strong></p>'
            . '<p><strong>Your 6-digit PIN / Password is: '
            . '<span style="font-size:20px;font-weight:800;color:#fb8500;">' . e($pin) . '</span></strong></p>'
            . '<p>Use this PIN to sign in. If you did not request this, you can ignore this email.</p>';

        try {
            Mail::html($html, function ($message) use ($email, $subject): void {
                $message->to($email)->subject($subject);
            });

            Log::info('[AccountPinMailer] direct mail ok', [
                'email' => $email,
                'context' => $context,
            ]);

            return true;
        } catch (Throwable $e) {
            Log::error('[AccountPinMailer] direct mail failed', [
                'email' => $email,
                'context' => $context,
                'error' => $e->getMessage(),
                'mailer' => config('mail.default'),
                'from' => config('mail.from.address'),
            ]);

            throw $e;
        }
    }
}
