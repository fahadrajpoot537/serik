<?php

namespace App\Support;

use App\Exceptions\AppointmentWorkflowException;
use App\Jobs\ProcessAppointmentBookingJob;
use App\Models\AppointmentBooking;
use App\Rules\ConsultPhoneNumber;
use App\Services\Appointments\AppointmentBookingOrchestrator;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Throwable;

/**
 * Consultation appointment calendar: timezone, slots, and booking rules.
 *
 * Canonical stored times stay in the historical scheduler format (e.g. "9:30",
 * "1:30"). User-facing labels are 12-hour with AM/PM.
 */
final class AppointmentScheduler
{
    public const CONSULTATION_LABEL = 'Consultation Type';

    /**
     * @var list<string>
     */
    public const CONSULTATION_TYPES = [
        'Buyer',
        'Seller',
        'Tenant',
        'Landlord',
        'General Consultation',
    ];

    /**
     * Existing scheduler slots as [canonical, hour24, minute].
     *
     * @var list<array{0: string, 1: int, 2: int}>
     */
    private const SLOTS = [
        ['9:30', 9, 30],
        ['10:30', 10, 30],
        ['11:30', 11, 30],
        ['1:30', 13, 30],
        ['2:30', 14, 30],
        ['3:30', 15, 30],
        ['4:30', 16, 30],
        ['5:30', 17, 30],
        ['6:30', 18, 30],
    ];

    public static function timezoneId(): string
    {
        $configured = trim((string) config('serik.appointment.timezone', ''));
        if (self::isValidTimezone($configured)) {
            return $configured;
        }

        $setting = '';
        if (function_exists('setting')) {
            $setting = trim((string) setting('time_zone', ''));
        }
        if (self::isValidTimezone($setting) && strtoupper($setting) !== 'UTC') {
            return $setting;
        }

        return 'America/Toronto';
    }

    public static function now(?CarbonInterface $now = null): CarbonInterface
    {
        if ($now instanceof CarbonInterface) {
            return $now->copy()->timezone(self::timezoneId());
        }

        return Carbon::now(self::timezoneId());
    }

    public static function todayDateString(?CarbonInterface $now = null): string
    {
        return self::now($now)->toDateString();
    }

    public static function maxDateString(?CarbonInterface $now = null): string
    {
        $maxYear = (int) config('serik.appointment.max_year', 2030);
        $year = self::now($now)->year;
        if ($maxYear < $year) {
            $maxYear = $year;
        }

        return sprintf('%04d-12-31', $maxYear);
    }

    public static function formatDisplay(int $hour24, int $minute): string
    {
        $hour24 = (($hour24 % 24) + 24) % 24;
        $suffix = $hour24 >= 12 ? 'PM' : 'AM';
        $hour12 = $hour24 % 12;
        if ($hour12 === 0) {
            $hour12 = 12;
        }

        return $hour12 . ':' . str_pad((string) $minute, 2, '0', STR_PAD_LEFT) . ' ' . $suffix;
    }

    /**
     * @return list<array{value: string, label: string, hour: int, minute: int}>
     */
    public static function catalog(): array
    {
        $out = [];
        foreach (self::SLOTS as [$canonical, $hour, $minute]) {
            $out[] = [
                'value' => $canonical,
                'label' => self::formatDisplay($hour, $minute),
                'hour' => $hour,
                'minute' => $minute,
            ];
        }

        return $out;
    }

    /**
     * @return list<string>
     */
    public static function canonicalTimes(): array
    {
        return array_column(self::catalog(), 'value');
    }

    /**
     * @return list<array{value: string, label: string, available: bool}>
     */
    public static function slotsForDate(string $date, ?CarbonInterface $now = null, ?array $taken = null): array
    {
        $date = self::normalizeDate($date);
        $now = self::now($now);
        $taken = $taken ?? ($date !== null ? self::takenTimesForDate($date) : []);

        if ($date === null) {
            return [];
        }

        $out = [];
        foreach (self::catalog() as $slot) {
            $available = self::isSlotAvailable($date, $slot['value'], $now, $taken);
            $out[] = [
                'value' => $slot['value'],
                'label' => $slot['label'],
                'available' => $available,
            ];
        }

        return $out;
    }

    public static function dateHasAvailableSlot(string $date, ?CarbonInterface $now = null, ?array $taken = null): bool
    {
        foreach (self::slotsForDate($date, $now, $taken) as $slot) {
            if ($slot['available']) {
                return true;
            }
        }

        return false;
    }

    public static function isSlotAvailable(string $date, string $canonicalTime, ?CarbonInterface $now = null, ?array $taken = null): bool
    {
        $date = self::normalizeDate($date);
        $slot = self::slotByCanonical($canonicalTime);
        if ($date === null || $slot === null) {
            return false;
        }

        $now = self::now($now);
        $today = $now->toDateString();
        $max = self::maxDateString($now);

        if ($date < $today || $date > $max) {
            return false;
        }

        $taken = $taken ?? self::takenTimesForDate($date);
        if (in_array($canonicalTime, $taken, true)) {
            return false;
        }

        if ($date === $today) {
            $slotAt = Carbon::createFromFormat(
                'Y-m-d H:i',
                $date . ' ' . sprintf('%02d:%02d', $slot['hour'], $slot['minute']),
                self::timezoneId()
            );
            if ($slotAt === false || $slotAt->lessThanOrEqualTo($now)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param  array<string, mixed>  $input
     * @param  list<string>  $takenTimes
     * @return array{ok: bool, errors: array<string, string>, date?: string, time?: string, consultation_type?: string, name?: string, email?: string, phone?: string}
     */
    public static function validateBooking(array $input, ?CarbonInterface $now = null, array $takenTimes = []): array
    {
        $validator = Validator::make($input, [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email'],
            'phone' => ['bail', 'required', 'string', 'max:50', new ConsultPhoneNumber()],
            'date' => ['required', 'string'],
            'time' => ['required', 'string', Rule::in(self::canonicalTimes())],
            'consultation_type' => ['required', 'string', Rule::in(self::CONSULTATION_TYPES)],
        ], [
            'consultation_type.required' => 'Please select a Consultation Type.',
            'consultation_type.in' => 'Please select a valid Consultation Type.',
            'time.in' => 'Please select a valid appointment time.',
        ]);

        if ($validator->fails()) {
            $errors = [];
            foreach ($validator->errors()->messages() as $key => $messages) {
                $errors[$key] = (string) ($messages[0] ?? 'Invalid value.');
            }

            return ['ok' => false, 'errors' => $errors];
        }

        $date = self::normalizeDate((string) $input['date']);
        if ($date === null) {
            return ['ok' => false, 'errors' => ['date' => 'Please select a valid appointment date.']];
        }

        $time = (string) $input['time'];
        $now = self::now($now);
        $today = $now->toDateString();
        $max = self::maxDateString($now);

        if ($date < $today) {
            return ['ok' => false, 'errors' => ['date' => 'Past dates cannot be booked.']];
        }
        if ($date > $max) {
            return ['ok' => false, 'errors' => ['date' => 'That date is outside the booking window.']];
        }
        if (! self::isSlotAvailable($date, $time, $now, $takenTimes)) {
            $message = $date === $today
                ? 'That time is no longer available. Please choose another time.'
                : 'That date or time is not available. Please choose another slot.';

            return ['ok' => false, 'errors' => ['time' => $message], 'stale' => true, 'date' => $date];
        }

        return [
            'ok' => true,
            'errors' => [],
            'date' => $date,
            'time' => $time,
            'consultation_type' => (string) $input['consultation_type'],
            'name' => trim((string) $input['name']),
            'email' => strtolower(trim((string) $input['email'])),
            'phone' => PhoneNumberNormalizer::parse($input['phone'])['e164'] ?? trim((string) $input['phone']),
        ];
    }

    /**
     * @return \Illuminate\Http\JsonResponse
     */
    public static function slotsResponse(Request $request)
    {
        $date = self::normalizeDate((string) $request->query('date', ''));
        if ($date === null) {
            return response()->json([
                'status' => false,
                'message' => 'Please select a valid date.',
            ], 422);
        }

        return response()->json([
            'status' => true,
            'date' => $date,
            'timezone' => self::timezoneId(),
            'slots' => self::slotsForDate($date),
        ]);
    }

    /**
     * @return \Illuminate\Http\JsonResponse
     */
    public static function book(Request $request)
    {
        $limitKey = 'appointment-scheduler:' . sha1($request->ip() . '|' . strtolower(trim((string) $request->input('email', ''))));
        if (RateLimiter::tooManyAttempts($limitKey, 8)) {
            return response()->json([
                'status' => false,
                'message' => 'Too many requests. Please try again shortly.',
            ], 429);
        }
        RateLimiter::hit($limitKey, 60);

        $validated = self::validateBooking($request->all());
        if (! $validated['ok']) {
            $status = ! empty($validated['stale']) ? 409 : 422;
            $payload = [
                'status' => false,
                'message' => $validated['errors'],
            ];
            if (! empty($validated['date'])) {
                $payload['slots'] = self::slotsForDate($validated['date']);
            }

            return response()->json($payload, $status);
        }

        $date = $validated['date'];
        $time = $validated['time'];
        $lock = Cache::lock(self::slotLockKey($date, $time), 10);

        if (! $lock->get()) {
            return response()->json([
                'status' => false,
                'message' => ['time' => 'That time is no longer available. Please choose another time.'],
                'slots' => self::slotsForDate($date),
            ], 409);
        }

        $booking = null;

        try {
            $idempotencyKey = AppointmentBooking::makeIdempotencyKey($validated['email'], $date, $time);
            $existing = null;
            if (Schema::hasTable('re_appointment_bookings')) {
                $existing = AppointmentBooking::query()->where('idempotency_key', $idempotencyKey)->first();
            }

            if ($existing && $existing->isConfirmed()) {
                return self::successResponse($existing);
            }

            if ($existing && ! $existing->isCancelled()) {
                $booking = $existing;
            } else {
                if (! self::isSlotAvailable($date, $time)) {
                    return response()->json([
                        'status' => false,
                        'message' => ['time' => 'That time is no longer available. Please choose another time.'],
                        'slots' => self::slotsForDate($date),
                    ], 409);
                }

                $booking = self::persistPending($validated, $request);
            }
        } catch (Throwable $e) {
            $duplicate = str_contains(strtolower($e->getMessage()), 'duplicate')
                || str_contains(strtolower($e->getMessage()), 'unique');
            if ($duplicate) {
                return response()->json([
                    'status' => false,
                    'message' => ['time' => 'That time is no longer available. Please choose another time.'],
                    'slots' => self::slotsForDate($date),
                ], 409);
            }

            Log::channel('appointments')->warning('appointment_workflow', [
                'step' => 'local',
                'ok' => false,
                'error_code' => 'local_persist',
            ]);

            return response()->json([
                'status' => false,
                'message' => 'Unable to book that appointment. Please try again.',
            ], 500);
        } finally {
            optional($lock)->release();
        }

        if (! $booking instanceof AppointmentBooking) {
            return response()->json([
                'status' => false,
                'message' => 'Unable to book that appointment. Please try again.',
            ], 500);
        }

        if ($booking->isConfirmed()) {
            return self::successResponse($booking);
        }

        $processSync = filter_var(config('serik.appointment.process_sync', true), FILTER_VALIDATE_BOOLEAN);

        try {
            if ($processSync) {
                $booking = app(AppointmentBookingOrchestrator::class)->run($booking);
                if ($booking->isConfirmed()) {
                    return self::successResponse($booking);
                }
            } else {
                return self::dispatchResume($booking);
            }
        } catch (AppointmentWorkflowException $e) {
            if ($e->retryable) {
                return self::dispatchResume($booking);
            }

            return self::failedResponse($booking->fresh() ?? $booking);
        } catch (Throwable $e) {
            Log::channel('appointments')->error('appointment_workflow', [
                'booking_reference' => $booking->booking_reference,
                'appointment_id' => $booking->id,
                'step' => 'processing',
                'ok' => false,
                'error_code' => 'unexpected',
            ]);

            return self::dispatchResume($booking);
        }

        if ($booking->isConfirmed()) {
            return self::successResponse($booking);
        }

        return self::dispatchResume($booking);
    }

    /**
     * @return \Illuminate\Http\JsonResponse
     */
    private static function dispatchResume(AppointmentBooking $booking)
    {
        ProcessAppointmentBookingJob::dispatch($booking->id);
        $booking = $booking->fresh() ?? $booking;
        if ($booking->isConfirmed()) {
            return self::successResponse($booking);
        }
        if ($booking->isFailed()) {
            return self::failedResponse($booking);
        }

        return self::pendingResponse($booking);
    }

    /**
     * @return \Illuminate\Http\JsonResponse
     */
    public static function statusResponse(Request $request)
    {
        $limitKey = 'appointment-status:' . sha1((string) $request->ip());
        if (RateLimiter::tooManyAttempts($limitKey, 60)) {
            return response()->json([
                'status' => false,
                'state' => 'error',
                'message' => 'Too many requests. Please try again shortly.',
            ], 429);
        }
        RateLimiter::hit($limitKey, 60);

        $token = trim((string) $request->query('token', $request->input('token', '')));
        if ($token === '' || strlen($token) < 32 || ! Schema::hasTable('re_appointment_bookings')) {
            return response()->json([
                'status' => false,
                'state' => 'error',
                'message' => 'This appointment status link is not valid.',
            ], 404);
        }

        $booking = AppointmentBooking::query()->where('public_token', $token)->first();
        if (! $booking) {
            return response()->json([
                'status' => false,
                'state' => 'error',
                'message' => 'This appointment status link is not valid.',
            ], 404);
        }

        $ageCutoff = now()->subDays(7);
        if ($booking->created_at && $booking->created_at->lt($ageCutoff)) {
            return response()->json([
                'status' => false,
                'state' => 'error',
                'message' => 'This appointment status link has expired.',
            ], 410);
        }

        if ($booking->isConfirmed()) {
            return self::successResponse($booking);
        }

        if ($booking->isFailed()) {
            return self::failedResponse($booking);
        }

        return self::pendingResponse($booking);
    }

    /**
     * @param  array{name: string, email: string, phone: string, date: string, time: string, consultation_type: string}  $validated
     */
    public static function persistPending(array $validated, Request $request): AppointmentBooking
    {
        $date = $validated['date'];
        $time = $validated['time'];
        $type = $validated['consultation_type'];
        $remarks = self::remarks($date, $time, $type);
        $source = AppointmentBooking::source();
        $submittedPage = url('/appointment-scheduler');
        $propertyUrl = trim((string) $request->input('property_url', ''));
        $customFields = [
            self::CONSULTATION_LABEL => $type,
            'Appointment Date' => $date,
            'Appointment Time' => $time,
            'Appointment Time Label' => self::slotByCanonical($time)['label'] ?? $time,
            'Source' => $source,
        ];

        return DB::transaction(function () use ($validated, $date, $time, $type, $remarks, $customFields, $source, $submittedPage, $propertyUrl) {
            $contactId = null;
            if (Schema::hasTable('contacts')) {
                $contactId = DB::table('contacts')->insertGetId([
                    'name' => $validated['name'],
                    'email' => $validated['email'],
                    'phone' => $validated['phone'],
                    'address' => null,
                    'subject' => $type,
                    'content' => $remarks,
                    'custom_fields' => json_encode($customFields),
                    'status' => 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            $booking = new AppointmentBooking();
            $booking->public_token = AppointmentBooking::makePublicToken();
            $booking->booking_reference = AppointmentBooking::makeBookingReference();
            $booking->idempotency_key = AppointmentBooking::makeIdempotencyKey($validated['email'], $date, $time);
            $booking->slot_key = AppointmentBooking::makeSlotKey($date, $time);
            $booking->contact_id = $contactId;
            $booking->status = AppointmentBooking::STATUS_PENDING;
            $booking->name = $validated['name'];
            $booking->email = $validated['email'];
            $booking->phone = $validated['phone'];
            $booking->consultation_type = $type;
            $booking->appointment_date = $date;
            $booking->appointment_time = $time;
            $booking->timezone = self::timezoneId();
            $booking->source = $source;
            $booking->submitted_page = $submittedPage;
            $booking->property_url = $propertyUrl !== '' ? $propertyUrl : null;
            $booking->markStep(AppointmentBooking::STEP_LOCAL, true);
            $booking->save();

            return $booking;
        });
    }

    /**
     * @return \Illuminate\Http\JsonResponse
     */
    public static function successResponse(AppointmentBooking $booking)
    {
        return response()->json([
            'status' => true,
            'state' => AppointmentBooking::STATUS_CONFIRMED,
            'message' => 'Appointment booked successfully',
            'booking_reference' => $booking->booking_reference,
            'token' => $booking->public_token,
        ]);
    }

    /**
     * @return \Illuminate\Http\JsonResponse
     */
    public static function pendingResponse(AppointmentBooking $booking)
    {
        return response()->json([
            'status' => 'pending',
            'pending' => true,
            'state' => $booking->status === AppointmentBooking::STATUS_PROCESSING
                ? AppointmentBooking::STATUS_PROCESSING
                : AppointmentBooking::STATUS_PENDING,
            'message' => 'We\'re confirming your appointment. Please wait.',
            'booking_reference' => $booking->booking_reference,
            'token' => $booking->public_token,
        ], 202);
    }

    /**
     * @return \Illuminate\Http\JsonResponse
     */
    public static function failedResponse(AppointmentBooking $booking)
    {
        $message = 'We couldn\'t confirm your appointment. Please try again or contact Serik Realty.';
        if (filled($booking->booking_reference)) {
            $message .= ' Reference: ' . $booking->booking_reference;
        }

        return response()->json([
            'status' => false,
            'state' => AppointmentBooking::STATUS_FAILED,
            'message' => $message,
            'booking_reference' => $booking->booking_reference,
            'token' => $booking->public_token,
        ], 503);
    }

    public static function remarks(string $date, string $time, string $consultationType): string
    {
        $label = self::slotByCanonical($time)['label'] ?? $time;

        return 'Appointment Date: ' . $date
            . ' Time: ' . $time
            . "\nConsultation Type: " . $consultationType
            . "\nDisplayed time: " . $label;
    }

    public static function slotLockKey(string $date, string $time): string
    {
        return 'serik-appointment:' . $date . ':' . $time;
    }

    /**
     * @return array<string, mixed>
     */
    public static function frontendConfig(?CarbonInterface $now = null): array
    {
        $now = self::now($now);
        $today = $now->toDateString();

        return [
            'timezone' => self::timezoneId(),
            'today' => $today,
            'nowHour' => (int) $now->hour,
            'nowMinute' => (int) $now->minute,
            'minDate' => $today,
            'maxDate' => self::maxDateString($now),
            'todayHasSlots' => self::dateHasAvailableSlot($today, $now),
            'catalog' => self::catalog(),
            'consultationTypes' => self::CONSULTATION_TYPES,
            'slotsUrl' => url('/api/v1/appointment-slots'),
            'bookUrl' => url('/api/v1/book-appointment'),
            'statusUrl' => url('/api/v1/appointment-status'),
            'successMessage' => 'Appointment booked successfully',
            'pendingMessage' => 'We\'re confirming your appointment. Please wait.',
            'failureMessage' => 'We couldn\'t confirm your appointment. Please try again or contact Serik Realty.',
            'pollIntervalMs' => 2000,
            'pollTimeoutMs' => 90000,
        ];
    }

    public static function normalizeDate(string $value): ?string
    {
        $value = trim($value);
        if ($value === '') {
            return null;
        }

        try {
            if (preg_match('/^(\d{4})-(\d{1,2})-(\d{1,2})$/', $value, $matches)) {
                $date = Carbon::createFromDate(
                    (int) $matches[1],
                    (int) $matches[2],
                    (int) $matches[3],
                    self::timezoneId()
                )->startOfDay();

                if ((int) $date->year !== (int) $matches[1]
                    || (int) $date->month !== (int) $matches[2]
                    || (int) $date->day !== (int) $matches[3]) {
                    return null;
                }

                return $date->toDateString();
            }
        } catch (Throwable) {
            return null;
        }

        return null;
    }

    /**
     * @return array{value: string, label: string, hour: int, minute: int}|null
     */
    public static function slotByCanonical(string $canonical): ?array
    {
        foreach (self::catalog() as $slot) {
            if ($slot['value'] === $canonical) {
                return $slot;
            }
        }

        return null;
    }

    /**
     * @return list<string>
     */
    public static function takenTimesForDate(string $date): array
    {
        $date = self::normalizeDate($date);
        if ($date === null || ! Schema::hasTable('contacts')) {
            return [];
        }

        try {
            $needle = 'Appointment Date: ' . $date . ' Time: ';
            $rows = DB::table('contacts')
                ->where('content', 'like', $needle . '%')
                ->pluck('content');
        } catch (Throwable) {
            return [];
        }

        $taken = [];
        foreach ($rows as $content) {
            if (preg_match('/Appointment Date: ' . preg_quote($date, '/') . ' Time: ([0-9]{1,2}:[0-9]{2})/', (string) $content, $matches)) {
                $taken[] = $matches[1];
            }
        }

        if (Schema::hasTable('re_appointment_bookings')) {
            try {
                $fromBookings = DB::table('re_appointment_bookings')
                    ->whereDate('appointment_date', $date)
                    ->whereIn('status', [
                        AppointmentBooking::STATUS_PENDING,
                        AppointmentBooking::STATUS_PROCESSING,
                        AppointmentBooking::STATUS_CONFIRMED,
                        AppointmentBooking::STATUS_FAILED,
                    ])
                    ->pluck('appointment_time');
                foreach ($fromBookings as $canonical) {
                    $taken[] = (string) $canonical;
                }
            } catch (Throwable) {
            }
        }

        return array_values(array_unique($taken));
    }

    private static function isValidTimezone(string $id): bool
    {
        return $id !== '' && in_array($id, timezone_identifiers_list(), true);
    }
}
