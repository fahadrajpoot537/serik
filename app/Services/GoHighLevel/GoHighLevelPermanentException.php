<?php

namespace App\Services\GoHighLevel;

use RuntimeException;
use Throwable;

/**
 * Non-retryable GoHighLevel failure (request validation, missing config, bad field mapping).
 * Queue jobs should fail permanently; the circuit breaker must not treat these as outages.
 */
class GoHighLevelPermanentException extends RuntimeException
{
    public function __construct(
        string $message,
        public readonly ?int $httpStatus = null,
        public readonly ?string $invalidField = null,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }

    public static function fromApiMessage(string $message, ?int $httpStatus = null): self
    {
        $field = null;
        // GHL may use ASCII or curly apostrophes in "couldn't".
        if (preg_match('/couldn[\x{2019}\']t validate the mapped field:\s*([a-z0-9_.]+)/iu', $message, $m)
            || preg_match('/validate the mapped field:\s*([a-z0-9_.]+)/i', $message, $m)
        ) {
            $field = strtolower(rtrim(trim($m[1]), '.,;:'));
            // Prefer short key when GHL echoes a prefixed custom-object key.
            if (str_contains($field, '.')) {
                $field = (string) substr($field, (int) strrpos($field, '.') + 1);
            }
        }

        return new self($message, $httpStatus, $field !== '' ? $field : null);
    }
}
