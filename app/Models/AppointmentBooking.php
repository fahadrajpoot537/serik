<?php

namespace App\Models;

use App\Support\AppointmentScheduler;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class AppointmentBooking extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_PROCESSING = 'processing';

    public const STATUS_CONFIRMED = 'confirmed';

    public const STATUS_FAILED = 'failed';

    public const STATUS_CANCELLED = 'cancelled';

    public const STEP_LOCAL = 'local';

    public const STEP_GHL = 'ghl_contact';

    public const STEP_CRM = 'crm_appointment';

    public const STEP_CALENDAR = 'calendar';

    public const STEP_CLIENT_MAIL = 'client_email';

    public const STEP_TEAM_MAIL = 'team_email';

    protected $table = 're_appointment_bookings';

    protected $fillable = [
        'public_token',
        'booking_reference',
        'idempotency_key',
        'slot_key',
        'contact_id',
        'status',
        'name',
        'email',
        'phone',
        'consultation_type',
        'appointment_date',
        'appointment_time',
        'timezone',
        'source',
        'submitted_page',
        'property_url',
        'assigned_recipient',
        'ghl_contact_id',
        'calendar_event_id',
        'client_mail_id',
        'team_mail_id',
        'client_mail_sent_at',
        'team_mail_sent_at',
        'confirmed_at',
        'failed_step',
        'error_code',
        'attempts',
        'last_attempted_at',
        'steps',
    ];

    protected $casts = [
        'appointment_date' => 'date',
        'steps' => 'array',
        'attempts' => 'integer',
        'client_mail_sent_at' => 'datetime',
        'team_mail_sent_at' => 'datetime',
        'confirmed_at' => 'datetime',
        'last_attempted_at' => 'datetime',
        'contact_id' => 'integer',
    ];

    public function isConfirmed(): bool
    {
        return $this->status === self::STATUS_CONFIRMED;
    }

    public function isFailed(): bool
    {
        return $this->status === self::STATUS_FAILED;
    }

    public function isCancelled(): bool
    {
        return $this->status === self::STATUS_CANCELLED;
    }

    public function occupiesSlot(): bool
    {
        return in_array($this->status, [
            self::STATUS_PENDING,
            self::STATUS_PROCESSING,
            self::STATUS_CONFIRMED,
            self::STATUS_FAILED,
        ], true);
    }

    public function stepDone(string $step): bool
    {
        $steps = is_array($this->steps) ? $this->steps : [];

        return ! empty($steps[$step]);
    }

    public function markStep(string $step, mixed $value = true): void
    {
        $steps = is_array($this->steps) ? $this->steps : [];
        $steps[$step] = $value;
        $this->steps = $steps;
    }

    public function timeLabel(): string
    {
        $slot = AppointmentScheduler::slotByCanonical((string) $this->appointment_time);

        return is_array($slot) ? (string) $slot['label'] : (string) $this->appointment_time;
    }

    public function dateString(): string
    {
        $date = $this->appointment_date;

        if ($date instanceof \DateTimeInterface) {
            return $date->format('Y-m-d');
        }

        return (string) $date;
    }

    public static function makePublicToken(): string
    {
        return bin2hex(random_bytes(24));
    }

    public static function makeBookingReference(): string
    {
        return 'SRK-' . strtoupper(Str::random(8));
    }

    public static function makeIdempotencyKey(string $email, string $date, string $time): string
    {
        return hash('sha256', 'appointment|' . strtolower(trim($email)) . '|' . $date . '|' . $time);
    }

    public static function makeSlotKey(string $date, string $time): string
    {
        return $date . '|' . $time;
    }

    public static function source(): string
    {
        $configured = trim((string) config('serik.appointment.source', ''));

        return $configured !== '' ? $configured : 'Serik.ca - Appointment';
    }
}
