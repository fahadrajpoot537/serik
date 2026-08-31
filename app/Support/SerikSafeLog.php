<?php

namespace App\Support;

use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Never throw from logging. Missing channels and unwritable log files must not
 * become HTTP 500s or failed queue jobs.
 */
final class SerikSafeLog
{
    private static bool $writing = false;

    /**
     * @param  array<string, mixed>  $context
     */
    public static function write(string $level, string $message, array $context = [], ?string $channel = null): void
    {
        if (self::$writing) {
            return;
        }

        self::$writing = true;

        try {
            $context = self::redact($context);
            $logger = $channel ? Log::channel($channel) : Log::getFacadeRoot();

            match ($level) {
                'debug' => $logger->debug($message, $context),
                'warning' => $logger->warning($message, $context),
                'error' => $logger->error($message, $context),
                'critical' => $logger->critical($message, $context),
                default => $logger->info($message, $context),
            };
        } catch (Throwable $e) {
            try {
                if ($channel !== null) {
                    Log::log($level === 'debug' ? 'debug' : ($level === 'warning' ? 'warning' : ($level === 'error' || $level === 'critical' ? $level : 'info')), $message, self::redact($context + [
                        'fallback_from_channel' => $channel,
                    ]));
                } else {
                    error_log('[SerikSafeLog] '.$message.' | '.$e->getMessage());
                }
            } catch (Throwable $fallback) {
                error_log('[SerikSafeLog] '.$message.' | '.$fallback->getMessage());
            }
        } finally {
            self::$writing = false;
        }
    }

    /**
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>
     */
    public static function redact(array $context): array
    {
        $sensitive = ['password', 'password_confirmation', 'token', 'api_key', 'apikey', 'secret', 'authorization', 'cookie', 'credit_card', 'card_number'];

        foreach ($context as $key => $value) {
            $normalized = strtolower((string) $key);
            if (in_array($normalized, $sensitive, true) || str_contains($normalized, 'password') || str_contains($normalized, 'secret') || str_contains($normalized, 'token')) {
                $context[$key] = '[redacted]';

                continue;
            }

            if (is_array($value)) {
                $context[$key] = self::redact($value);
            }
        }

        return $context;
    }
}
