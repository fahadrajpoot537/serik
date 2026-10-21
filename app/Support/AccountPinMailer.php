<?php

namespace App\Support;

use Illuminate\Mail\MailManager;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * PIN emails are always sent synchronously via Resend HTTP.
 * Never queue — forgot/register PIN must arrive within seconds.
 */
final class AccountPinMailer
{
    public static function send(string $email, string $accountName, string $pin, string $context = 'auth'): bool
    {
        $started = microtime(true);
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
            ?: ''
        );

        if ($resendKey === '' && function_exists('env')) {
            // Avoid env() after config:cache when possible — already tried config/settings.
            $resendKey = (string) (config('services.resend.key') ?: '');
        }

        $html = self::htmlBody($name, $email, $pin);

        Log::info('[AccountPinMailer] sending sync', [
            'email' => $email,
            'context' => $context,
            'from' => $fromAddress,
            'has_resend_key' => $resendKey !== '',
        ]);

        if ($resendKey === '') {
            Log::error('[AccountPinMailer] missing Resend API key', [
                'email' => $email,
                'context' => $context,
            ]);

            return false;
        }

        $result = self::sendViaResendCurl($resendKey, $fromName, $fromAddress, $email, $subject, $html);
        $elapsedMs = (int) round((microtime(true) - $started) * 1000);

        if ($result['ok']) {
            Log::info('[AccountPinMailer] resend ok (sync)', [
                'email' => $email,
                'context' => $context,
                'resend_id' => $result['id'],
                'elapsed_ms' => $elapsedMs,
            ]);

            return true;
        }

        Log::error('[AccountPinMailer] resend failed (sync)', [
            'email' => $email,
            'context' => $context,
            'status' => $result['status'],
            'body' => $result['body'],
            'elapsed_ms' => $elapsedMs,
        ]);

        return false;
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

    /**
     * @return array{ok: bool, id: ?string, status: int, body: string}
     */
    private static function sendViaResendCurl(
        string $apiKey,
        string $fromName,
        string $fromAddress,
        string $to,
        string $subject,
        string $html
    ): array {
        $payload = json_encode([
            'from' => $fromName . ' <' . $fromAddress . '>',
            'to' => [$to],
            'subject' => $subject,
            'html' => $html,
        ], JSON_UNESCAPED_UNICODE);

        $ch = curl_init('https://api.resend.com/emails');
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $apiKey,
                'Content-Type: application/json',
                'Accept: application/json',
            ],
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 20,
            CURLOPT_CONNECTTIMEOUT => 8,
        ]);

        $body = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_error($ch);
        curl_close($ch);

        if ($body === false) {
            return [
                'ok' => false,
                'id' => null,
                'status' => 0,
                'body' => 'curl_error: ' . $err,
            ];
        }

        $json = json_decode((string) $body, true);
        $id = is_array($json) ? ($json['id'] ?? null) : null;
        $ok = $status >= 200 && $status < 300 && is_string($id) && $id !== '';

        return [
            'ok' => $ok,
            'id' => is_string($id) ? $id : null,
            'status' => $status,
            'body' => (string) $body,
        ];
    }
}
