<?php

namespace App\Services\Appointments;

use App\Exceptions\AppointmentWorkflowException;
use App\Models\AppointmentBooking;
use App\Support\AppointmentScheduler;
use Carbon\Carbon;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class AppointmentCalendarService
{
    public function createOrSkip(AppointmentBooking $booking): string
    {
        $existing = trim((string) $booking->calendar_event_id);
        if ($existing !== '') {
            return $existing;
        }

        $calendarId = trim((string) config('serik.appointment.calendar_id', ''));
        if ($calendarId === '') {
            throw new AppointmentWorkflowException(
                AppointmentWorkflowException::CALENDAR_NOT_CONFIGURED,
                'calendar',
                false,
                'Calendar is not configured.'
            );
        }

        $token = (string) config('services.gohighlevel.api_token');
        $locationId = (string) config('services.gohighlevel.location_id');
        if ($token === '' || $locationId === '') {
            throw new AppointmentWorkflowException(
                AppointmentWorkflowException::CALENDAR_AUTH,
                'calendar',
                false,
                'Calendar credentials are missing.'
            );
        }

        $contactId = trim((string) $booking->ghl_contact_id);
        if ($contactId === '') {
            throw new AppointmentWorkflowException(
                AppointmentWorkflowException::CRM_VALIDATION,
                'calendar',
                true,
                'Calendar event requires a CRM contact.'
            );
        }

        $slot = AppointmentScheduler::slotByCanonical((string) $booking->appointment_time);
        if ($slot === null) {
            throw new AppointmentWorkflowException(
                AppointmentWorkflowException::VALIDATION,
                'calendar',
                false,
                'Invalid appointment time.'
            );
        }

        $timezone = (string) $booking->timezone;
        $start = Carbon::createFromFormat(
            'Y-m-d H:i',
            $booking->dateString() . ' ' . sprintf('%02d:%02d', $slot['hour'], $slot['minute']),
            $timezone
        );
        if ($start === false) {
            throw new AppointmentWorkflowException(
                AppointmentWorkflowException::VALIDATION,
                'calendar',
                false,
                'Unable to resolve appointment start time.'
            );
        }

        $end = $start->copy()->addMinutes((int) config('serik.appointment.slot_minutes', 30));
        $startIso = $start->toIso8601String();
        $endIso = $end->toIso8601String();

        if ($start->timezoneName !== $timezone) {
            throw new AppointmentWorkflowException(
                AppointmentWorkflowException::VALIDATION,
                'calendar',
                false,
                'Appointment timezone could not be applied.'
            );
        }

        $assignedUserId = trim((string) config('serik.appointment.assigned_user_id', ''));
        $payload = array_filter([
            'calendarId' => $calendarId,
            'locationId' => $locationId,
            'contactId' => $contactId,
            'startTime' => $startIso,
            'endTime' => $endIso,
            'title' => sprintf(
                '%s consultation — %s [%s]',
                $booking->consultation_type,
                $booking->name,
                $booking->booking_reference
            ),
            'appointmentStatus' => 'new',
            'assignedUserId' => $assignedUserId !== '' ? $assignedUserId : null,
            'address' => '',
            'ignoreFreeSlotValidation' => true,
            'toNotify' => false,
            'ignoreDateRange' => true,
        ], static fn ($v) => $v !== null && $v !== '');

        $url = rtrim((string) config('services.gohighlevel.base_url'), '/') . '/calendars/events/appointments';
        $t0 = microtime(true);

        try {
            $response = Http::withToken($token)
                ->withHeaders([
                    'Version' => (string) config('serik.appointment.calendar_api_version', '2021-04-15'),
                    'Accept' => 'application/json',
                    'Idempotency-Key' => $booking->idempotency_key,
                ])
                ->timeout(15)
                ->acceptJson()
                ->asJson()
                ->post($url, $payload);
        } catch (ConnectionException $e) {
            $this->logStep($booking, false, (int) ((microtime(true) - $t0) * 1000), null, null);

            throw new AppointmentWorkflowException(
                AppointmentWorkflowException::CALENDAR_TIMEOUT,
                'calendar',
                true,
                'Calendar request timed out.'
            );
        } catch (Throwable $e) {
            $this->logStep($booking, false, (int) ((microtime(true) - $t0) * 1000), null, null);

            throw new AppointmentWorkflowException(
                AppointmentWorkflowException::CALENDAR_TIMEOUT,
                'calendar',
                true,
                'Calendar request failed.'
            );
        }

        $durationMs = (int) ((microtime(true) - $t0) * 1000);
        $providerId = $response->header('x-request-id') ?: $response->header('x-correlation-id');

        if (! $response->successful()) {
            $this->logStep($booking, false, $durationMs, $response->status(), is_string($providerId) ? $providerId : null);
            $exception = AppointmentWorkflowException::fromCalendarHttp($response->status());
            if ($response->status() === 400 || $response->status() === 422) {
                $body = strtolower((string) $response->body());
                if (str_contains($body, 'conflict') || str_contains($body, 'slot') || str_contains($body, 'booked')) {
                    throw new AppointmentWorkflowException(
                        AppointmentWorkflowException::CALENDAR_CONFLICT,
                        'calendar',
                        false,
                        'Calendar conflict.',
                        $response->status(),
                        is_string($providerId) ? $providerId : null
                    );
                }
            }

            throw $exception;
        }

        $json = $response->json();
        $eventId = data_get($json, 'id')
            ?: data_get($json, 'event.id')
            ?: data_get($json, 'appointment.id');

        if (! is_string($eventId) || $eventId === '') {
            $this->logStep($booking, false, $durationMs, $response->status(), is_string($providerId) ? $providerId : null);

            throw new AppointmentWorkflowException(
                AppointmentWorkflowException::CALENDAR_CONFLICT,
                'calendar',
                true,
                'Calendar did not return an event id.'
            );
        }

        $this->logStep($booking, true, $durationMs, $response->status(), is_string($providerId) ? $providerId : $eventId);

        return $eventId;
    }

    private function logStep(
        AppointmentBooking $booking,
        bool $ok,
        int $durationMs,
        ?int $httpStatus,
        ?string $providerId
    ): void {
        Log::channel('appointments')->info('appointment_workflow', [
            'booking_reference' => $booking->booking_reference,
            'appointment_id' => $booking->id,
            'step' => 'calendar',
            'attempt' => $booking->attempts,
            'provider' => 'ghl_calendar',
            'provider_request_id' => $providerId,
            'http_status' => $httpStatus,
            'duration_ms' => $durationMs,
            'ok' => $ok,
        ]);
    }
}
