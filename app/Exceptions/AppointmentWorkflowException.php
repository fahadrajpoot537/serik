<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Typed appointment-integration failure. Never converted into a success response.
 */
class AppointmentWorkflowException extends RuntimeException
{
    public const CRM_AUTH = 'crm_auth';

    public const CRM_VALIDATION = 'crm_validation';

    public const CRM_TIMEOUT = 'crm_timeout';

    public const CRM_RATE_LIMIT = 'crm_rate_limit';

    public const CALENDAR_AUTH = 'calendar_auth';

    public const CALENDAR_CONFLICT = 'calendar_conflict';

    public const CALENDAR_TIMEOUT = 'calendar_timeout';

    public const CALENDAR_RATE_LIMIT = 'calendar_rate_limit';

    public const CALENDAR_NOT_CONFIGURED = 'calendar_not_configured';

    public const MISSING_RECIPIENT = 'missing_assigned_recipient';

    public const MAIL_AUTH = 'mail_auth';

    public const CLIENT_EMAIL = 'client_email_rejection';

    public const TEAM_EMAIL = 'team_notification_failure';

    public const QUEUE_STALL = 'queue_not_running';

    public const VALIDATION = 'permanent_validation';

    public function __construct(
        public readonly string $errorCode,
        public readonly string $step,
        public readonly bool $retryable = true,
        string $message = '',
        public readonly ?int $httpStatus = null,
        public readonly ?string $providerRequestId = null,
    ) {
        parent::__construct($message !== '' ? $message : $errorCode);
    }

    public static function fromGhlHttp(int $status, string $step = 'ghl_contact'): self
    {
        return match (true) {
            $status === 401, $status === 403 => new self(self::CRM_AUTH, $step, false, 'CRM authentication failed.', $status),
            $status === 429 => new self(self::CRM_RATE_LIMIT, $step, true, 'CRM rate limited.', $status),
            $status === 408, $status >= 500 => new self(self::CRM_TIMEOUT, $step, true, 'CRM timeout.', $status),
            default => new self(self::CRM_VALIDATION, $step, $status >= 500, 'CRM validation failed.', $status),
        };
    }

    public static function fromCalendarHttp(int $status): self
    {
        return match (true) {
            $status === 401, $status === 403 => new self(self::CALENDAR_AUTH, 'calendar', false, 'Calendar authentication failed.', $status),
            $status === 409 => new self(self::CALENDAR_CONFLICT, 'calendar', false, 'Calendar conflict.', $status),
            $status === 429 => new self(self::CALENDAR_RATE_LIMIT, 'calendar', true, 'Calendar rate limited.', $status),
            $status === 408, $status >= 500 => new self(self::CALENDAR_TIMEOUT, 'calendar', true, 'Calendar timeout.', $status),
            default => new self(
                $status === 400 || $status === 422 ? self::CALENDAR_CONFLICT : self::CALENDAR_AUTH,
                'calendar',
                false,
                'Calendar event was not accepted.',
                $status
            ),
        };
    }
}
