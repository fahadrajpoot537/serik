<?php

namespace App\Services\GoHighLevel;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Webhook authentication + optional replay / idempotency protection.
 * Defaults never reject valid GHL traffic when secret is unset.
 */
final class GoHighLevelWebhookGuard
{
    public function authorize(Request $request): bool
    {
        $secret = (string) config('gohighlevel.mls_sync.webhook_secret', '');
        if ($secret === '') {
            // Secret optional: allow when GHL is enabled (workflow may not sign).
            return (bool) config('services.gohighlevel.enabled', true);
        }

        $provided = (string) (
            $request->header('X-GHL-Signature')
            ?? $request->header('X-Webhook-Secret')
            ?? $request->header('X-GHL-Webhook-Secret')
            ?? $request->query('token')
            ?? $request->input('token')
            ?? ''
        );

        if ($provided === '') {
            return false;
        }

        // Timing-safe compare (supports raw secret or sha256 hex of body+secret).
        if (hash_equals($secret, $provided)) {
            return true;
        }

        $body = $request->getContent();
        $digest = hash_hmac('sha256', $body, $secret);
        if (hash_equals($digest, $provided)) {
            return true;
        }

        // Some GHL setups send "sha256=<hex>"
        if (str_starts_with(strtolower($provided), 'sha256=')) {
            $hex = substr($provided, 7);

            return hash_equals($digest, $hex);
        }

        return false;
    }

    /**
     * Optional timestamp skew check (feature-flagged; never blocks when header absent).
     */
    public function timestampFresh(Request $request): bool
    {
        if (! config('gohighlevel.mls_sync.webhook_timestamp_check', false)) {
            return true;
        }

        $raw = $request->header('X-GHL-Timestamp')
            ?? $request->header('X-Webhook-Timestamp')
            ?? $request->input('timestamp');

        if ($raw === null || $raw === '') {
            return true; // do not reject unsigned timestamp
        }

        $ts = is_numeric($raw) ? (int) $raw : strtotime((string) $raw);
        if ($ts === false || $ts <= 0) {
            return true;
        }

        $maxSkew = max(60, (int) config('gohighlevel.mls_sync.webhook_max_skew_seconds', 900));

        return abs(time() - $ts) <= $maxSkew;
    }

    /**
     * Deduplicate rapid GHL retries for the same contact+MLS (or contact-only).
     * Returns false when this delivery is a duplicate within the window.
     */
    public function claimIdempotency(string $contactId, ?string $mlsNumber, string $correlationId): bool
    {
        if (! config('gohighlevel.mls_sync.webhook_idempotency', true)) {
            return true;
        }

        $window = max(30, (int) config('gohighlevel.mls_sync.webhook_idempotency_seconds', 120));
        $mls = strtoupper(trim((string) $mlsNumber));
        $key = 'ghl:wh:idem:' . md5(strtolower(trim($contactId)) . '|' . $mls);

        $claimed = Cache::add($key, $correlationId, $window);
        if (! $claimed) {
            Log::channel('ghl_sync')->info('GoHighLevel webhook duplicate suppressed', [
                'correlation_id' => $correlationId,
                'contact_id' => $contactId,
                'mls' => $mls !== '' ? $mls : null,
                'window_seconds' => $window,
            ]);
            GoHighLevelMetrics::incrDay('webhook_duplicate');
        }

        return $claimed;
    }
}
