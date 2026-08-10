<?php

namespace App\Support;

use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Structured reliability audit events (imports / sync / retries / failures / recoveries).
 * Additive — never throws into callers.
 */
final class SerikAuditLog
{
    public const DOMAIN_IMPORTS = 'imports';

    public const DOMAIN_GHL = 'ghl';

    public const DOMAIN_SEARCH = 'search';

    public const DOMAIN_QUEUE = 'queue';

    public const DOMAIN_RELIABILITY = 'reliability';

    /**
     * @param  array<string, mixed>  $context
     */
    public static function event(
        string $domain,
        string $action,
        array $context = [],
        string $level = 'info',
    ): void {
        try {
            $payload = [
                'ts' => now()->toIso8601String(),
                'domain' => $domain,
                'action' => $action,
                'context' => $context,
            ];

            $channel = match ($domain) {
                self::DOMAIN_IMPORTS => 'treb_archive',
                self::DOMAIN_GHL => 'ghl_sync',
                self::DOMAIN_SEARCH => 'search_sync',
                default => 'reliability',
            };

            $logger = Log::channel($channel);
            $message = sprintf('[%s] %s', strtoupper($domain), $action);

            match ($level) {
                'warning' => $logger->warning($message, $payload),
                'error' => $logger->error($message, $payload),
                default => $logger->info($message, $payload),
            };

            // Mirror critical failures onto reliability channel.
            if ($level === 'error' && $channel !== 'reliability') {
                Log::channel('reliability')->error($message, $payload);
            }
        } catch (Throwable) {
        }
    }
}
