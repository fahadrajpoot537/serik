<?php

namespace App\Support;

use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * PIN emails: sync Resend HTTP only. No MailManager / queue / afterResponse.
 * Windows/IIS: CURLOPT_NOSIGNAL so timeouts actually fire (otherwise request "sticks").
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

        self::trace('start', [
            'email' => $email,
            'context' => $context,
        ]);

        $fromAddress = self::mailFromAddress();
        $fromName = self::mailFromName();
        $resendKey = self::resendApiKey();

        $html = self::htmlBody($name, $email, $pin);

        if ($resendKey === '') {
            self::trace('missing_key', ['email' => $email], true);

            return false;
        }

        self::trace('curl_begin', [
            'email' => $email,
            'from' => $fromAddress,
            'key_len' => strlen($resendKey),
        ]);

        $result = self::sendViaResendCurl($resendKey, $fromName, $fromAddress, $email, $subject, $html);
        $elapsedMs = (int) round((microtime(true) - $started) * 1000);

        if ($result['ok']) {
            self::trace('ok', [
                'email' => $email,
                'resend_id' => $result['id'],
                'elapsed_ms' => $elapsedMs,
            ]);

            return true;
        }

        self::trace('fail', [
            'email' => $email,
            'status' => $result['status'],
            'body' => mb_substr((string) $result['body'], 0, 500),
            'elapsed_ms' => $elapsedMs,
        ], true);

        return false;
    }

    private static function mailFromAddress(): string
    {
        $from = (string) config('mail.from.address', '');
        if ($from !== '') {
            return $from;
        }

        try {
            $from = (string) setting('email_from_address', '');
        } catch (Throwable $e) {
            $from = '';
        }

        return $from !== '' ? $from : 'info@serik.ca';
    }

    private static function mailFromName(): string
    {
        $name = (string) config('mail.from.name', '');
        if ($name !== '') {
            return $name;
        }

        try {
            $name = (string) setting('email_from_name', '');
        } catch (Throwable $e) {
            $name = '';
        }

        return $name !== '' ? $name : 'Serik Realty';
    }

    private static function resendApiKey(): string
    {
        $key = (string) config('services.resend.key', '');
        if ($key !== '') {
            return $key;
        }

        try {
            $key = (string) setting('email_resend_key', '');
        } catch (Throwable $e) {
            $key = '';
        }

        return $key;
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
        $opts = [
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $apiKey,
                'Content-Type: application/json',
                'Accept: application/json',
            ],
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 12,
            CURLOPT_CONNECTTIMEOUT => 5,
            // Critical on Windows: without this, TIMEOUT often never fires → IIS request hangs.
            CURLOPT_NOSIGNAL => true,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
        ];
        if (defined('CURLOPT_IPRESOLVE') && defined('CURL_IPRESOLVE_V4')) {
            $opts[CURLOPT_IPRESOLVE] = CURL_IPRESOLVE_V4;
        }
        curl_setopt_array($ch, $opts);

        $body = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_error($ch);
        $errno = curl_errno($ch);
        curl_close($ch);

        if ($body === false || $errno !== 0) {
            return [
                'ok' => false,
                'id' => null,
                'status' => 0,
                'body' => 'curl_error[' . $errno . ']: ' . $err,
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

    /**
     * @param  array<string, mixed>  $ctx
     */
    private static function trace(string $step, array $ctx = [], bool $error = false): void
    {
        $line = '[AccountPinMailer] ' . $step . ' ' . json_encode($ctx, JSON_UNESCAPED_UNICODE);
        try {
            if ($error) {
                Log::error($line);
            } else {
                Log::info($line);
            }
        } catch (Throwable $e) {
            // ignore
        }

        // Belt-and-suspenders for IIS when Laravel log channel is quiet.
        try {
            $path = storage_path('logs/pin-mail.log');
            @file_put_contents(
                $path,
                '[' . date('Y-m-d H:i:s') . '] ' . $line . PHP_EOL,
                FILE_APPEND | LOCK_EX
            );
        } catch (Throwable $e) {
            // ignore
        }
    }
}
