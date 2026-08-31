<?php

namespace App\Support;

use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Structured reliability audit events (imports / sync / retries / failures / recoveries).
 * Additive — never throws into callers. Missing channels fall back to the default logger.
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
        $payload = [
            'ts' => now()->toIso8601String(),
            'domain' => $domain,
            'action' => $action,
            'context' => SerikSafeLog::redact($context),
        ];

        $channel = match ($domain) {
            self::DOMAIN_IMPORTS => 'treb_archive',
            self::DOMAIN_GHL => 'ghl_sync',
            self::DOMAIN_SEARCH => 'search_sync',
            default => 'reliability',
        };

        $message = sprintf('[%s] %s', strtoupper($domain), $action);

        SerikSafeLog::write($level, $message, $payload, $channel);

        if ($level === 'error' && $channel !== 'reliability') {
            SerikSafeLog::write('error', $message, $payload, 'reliability');
        }
    }

    /**
     * True when a named logging channel is defined and resolvable.
     */
    public static function channelExists(string $channel): bool
    {
        try {
            Log::channel($channel);

            return true;
        } catch (Throwable) {
            return false;
        }
    }
}
