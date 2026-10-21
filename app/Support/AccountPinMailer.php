<?php

namespace App\Support;

use Illuminate\Mail\MailManager;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * PIN mailer — prefers Resend HTTP API (reliable on production),
 * then falls back to Laravel mail / Botble templates.
 */
final class AccountPinMailer
{
    public static function send(string $email, string $accountName, string $pin, string $context = 'auth'): bool
    {
        $email = strtolower(trim($email));
        $isForgot = $context === 'forgot-password';
        $name = trim($accountName) !== '' ? trim($accountName) : 'there';
        $subject = $isForgot
            ? 'Your new Serik Realty login PIN'
            : 'Your Serik Realty login PIN';

        try {
            app(MailManager::class);
        } catch (Throwable $e) {
            // ignore
        }

        $fromAddress = (string) (config('mail.from.address') ?: setting('email_from_address') ?: 'info@serik.ca');
        $fromName = (string) (config('mail.from.name') ?: setting('email_from_name') ?: 'Serik Realty');
        $resendKey = (string) (
            config('services.resend.key')
            ?: setting('email_resend_key')
            ?: env('RESEND_API_KEY', '')
        );

        $html = self::htmlBody($name, $email, $pin);

        Log::info('[AccountPinMailer] sending', [
            'email' => $email,
            'context' => $context,
            'mailer' => config('mail.default'),
            'from' => $fromAddress,
            'has_resend_key' => $resendKey !== '',
        ]);

        // 1) Direct Resend HTTP — most reliable on live (bypasses Laravel mailer quirks).
        if ($resendKey !== '') {
            try {
                if (self::sendViaResendApi($resendKey, $fromName, $fromAddress, $email, $subject, $html)) {
                    Log::info('[AccountPinMailer] resend api ok', [
                        'email' => $email,
                        'context' => $context,
                    ]);

                    return true;
                }
            } catch (Throwable $e) {
                Log::error('[AccountPinMailer] resend api exception', [
                    'email' => $email,
                    'context' => $context,
                    'error' => $e->getMessage(),
                ]);
            }
        } else {
            Log::warning('[AccountPinMailer] no Resend API key configured', [
                'email' => $email,
                'context' => $context,
            ]);
        }

        // 2) Laravel Mail::html fallback
        try {
            \Illuminate\Support\Facades\Mail::html($html, function ($message) use ($email, $subject): void {
                $message->to($email)->subject($subject);
            });
            Log::info('[AccountPinMailer] laravel mail ok', [
                'email' => $email,
                'context' => $context,
            ]);

            return true;
        } catch (Throwable $e) {
            Log::error('[AccountPinMailer] laravel mail failed', [
                'email' => $email,
                'context' => $context,
                'error' => $e->getMessage(),
            ]);
        }

        // 3) Botble template last resort
        try {
            $sent = \Botble\Base\Facades\EmailHandler::setModule(REAL_ESTATE_MODULE_SCREEN_NAME)
                ->setVariableValues([
                    'account_name' => $name,
                    'account_email' => $email,
                    'account_password' => $pin,
                ])
                ->sendUsingTemplate('account-registered', $email, [], false, 'plugins', $subject);

            Log::info('[AccountPinMailer] template result', [
                'email' => $email,
                'context' => $context,
                'sent' => (bool) $sent,
            ]);

            return (bool) $sent;
        } catch (Throwable $e) {
            Log::error('[AccountPinMailer] all send paths failed', [
                'email' => $email,
                'context' => $context,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    private static function htmlBody(string $name, string $email, string $pin): string
    {
        return '<p>Hi ' . e($name) . ',</p>'
            . '<p>Here is your Serik Realty login PIN.</p>'
            . '<p>Your login email is: <strong>' . e($email) . '</strong></p>'
            . '<p><strong>Your 6-digit PIN / Password is: '
            . '<span style="font-size:20px;font-weight:800;color:#fb8500;">' . e($pin) . '</span></strong></p>'
            . '<p>Use this PIN to sign in. If you did not request this, you can ignore this email.</p>';
    }

    private static function sendViaResendApi(
        string $apiKey,
        string $fromName,
        string $fromAddress,
        string $to,
        string $subject,
        string $html
    ): bool {
        $response = Http::withToken($apiKey)
            ->acceptJson()
            ->timeout(25)
            ->post('https://api.resend.com/emails', [
                'from' => $fromName . ' <' . $fromAddress . '>',
                'to' => [$to],
                'subject' => $subject,
                'html' => $html,
            ]);

        if ($response->successful()) {
            Log::info('[AccountPinMailer] resend response', [
                'id' => $response->json('id'),
                'to' => $to,
            ]);

            return true;
        }

        Log::error('[AccountPinMailer] resend api rejected', [
            'status' => $response->status(),
            'body' => $response->body(),
            'to' => $to,
            'from' => $fromAddress,
        ]);

        return false;
    }
}
