<?php

namespace App\Services\Appointments;

use App\Exceptions\AppointmentWorkflowException;
use App\Models\AppointmentBooking;
use App\Services\GoHighLevel\GoHighLevelLeadService;
use Illuminate\Support\Facades\Log;
use Throwable;

class AppointmentGhlService
{
    public function __construct(protected GoHighLevelLeadService $ghl)
    {
    }

    /**
     * @return array{contact_id: string}
     */
    public function upsertContact(AppointmentBooking $booking): array
    {
        $existing = trim((string) $booking->ghl_contact_id);
        if ($existing !== '' && $booking->stepDone(AppointmentBooking::STEP_GHL)) {
            return ['contact_id' => $existing];
        }

        if (! $this->ghl->enabled()) {
            throw new AppointmentWorkflowException(
                AppointmentWorkflowException::CRM_AUTH,
                'ghl_contact',
                false,
                'CRM is not configured.'
            );
        }

        $lead = $this->leadPayload($booking);
        $t0 = microtime(true);

        try {
            $result = $this->ghl->upsertLead($lead);
        } catch (AppointmentWorkflowException $e) {
            $this->log($booking, false, (int) ((microtime(true) - $t0) * 1000), $e->errorCode, $e->httpStatus);

            throw $e;
        } catch (Throwable $e) {
            $this->log($booking, false, (int) ((microtime(true) - $t0) * 1000), AppointmentWorkflowException::CRM_TIMEOUT, null);

            throw new AppointmentWorkflowException(
                AppointmentWorkflowException::CRM_TIMEOUT,
                'ghl_contact',
                true,
                'CRM request failed.'
            );
        }

        $contactId = is_array($result) ? (string) data_get($result, 'contact.id') : '';
        if ($contactId === '' && $existing !== '') {
            $contactId = $existing;
        }

        if ($contactId === '') {
            $this->log($booking, false, (int) ((microtime(true) - $t0) * 1000), AppointmentWorkflowException::CRM_VALIDATION, null);

            throw new AppointmentWorkflowException(
                AppointmentWorkflowException::CRM_VALIDATION,
                'ghl_contact',
                true,
                'CRM did not return a contact id.'
            );
        }

        $this->log($booking, true, (int) ((microtime(true) - $t0) * 1000), null, null, $contactId);

        return ['contact_id' => $contactId];
    }

    /**
     * @return array<string, mixed>
     */
    public function leadPayload(AppointmentBooking $booking): array
    {
        $source = AppointmentBooking::source();
        $timeLabel = $booking->timeLabel();
        $message = implode("\n", array_filter([
            'Appointment Date: ' . $booking->dateString(),
            'Appointment Time: ' . $timeLabel . ' (' . $booking->timezone . ')',
            'Consultation Type: ' . $booking->consultation_type,
            'Booking reference: ' . $booking->booking_reference,
            'Submitted page: ' . (string) $booking->submitted_page,
            $booking->property_url ? 'Property: ' . $booking->property_url : null,
        ]));

        return [
            'name' => $booking->name,
            'email' => $booking->email,
            'phone' => $booking->phone,
            'subject' => $booking->consultation_type . ' Consultation Appointment',
            'message' => $message,
            'source' => $source,
            'tags' => ['Website Lead', 'Appointment', 'Serik Realty', $source],
            'submitted_page' => $booking->submitted_page,
            'submitted_at' => now()->toIso8601String(),
            'merge_existing_tags' => true,
            'omit_empty' => true,
            'fail_hard' => true,
            'custom_fields' => $this->customFields($booking, $source, $timeLabel),
        ];
    }

    /**
     * @return list<array{id: string, field_value: mixed}>
     */
    public function customFields(AppointmentBooking $booking, string $source, string $timeLabel): array
    {
        $map = [
            [
                'config_key' => 'serik.appointment.inquiry_type_field_id',
                'value' => $booking->consultation_type,
            ],
            [
                'config_key' => 'gohighlevel.contact_forms.lead_source_field_id',
                'value' => $source,
            ],
            [
                'config_key' => 'serik.appointment.date_field_id',
                'value' => $booking->dateString(),
            ],
            [
                'config_key' => 'serik.appointment.time_field_id',
                'value' => $timeLabel,
            ],
            [
                'config_key' => 'serik.appointment.timezone_field_id',
                'value' => $booking->timezone,
            ],
            [
                'config_key' => 'serik.appointment.booking_ref_field_id',
                'value' => $booking->booking_reference,
            ],
            [
                'config_key' => 'serik.appointment.submitted_page_field_id',
                'value' => (string) $booking->submitted_page,
            ],
            [
                'config_key' => 'serik.appointment.assigned_member_field_id',
                'value' => (string) $booking->assigned_recipient,
            ],
        ];

        if (filled($booking->property_url)) {
            $map[] = [
                'config_key' => 'serik.appointment.property_url_field_id',
                'value' => (string) $booking->property_url,
            ];
        }

        $fields = [];
        foreach ($map as $item) {
            $id = trim((string) config($item['config_key'], ''));
            $value = is_string($item['value']) ? trim($item['value']) : $item['value'];
            if ($id === '' || $value === '' || $value === null) {
                continue;
            }
            $fields[] = [
                'id' => $id,
                'field_value' => $value,
            ];
        }

        return $fields;
    }

    private function log(
        AppointmentBooking $booking,
        bool $ok,
        int $durationMs,
        ?string $errorCode,
        ?int $httpStatus,
        ?string $contactId = null
    ): void {
        Log::channel('appointments')->info('appointment_workflow', [
            'booking_reference' => $booking->booking_reference,
            'appointment_id' => $booking->id,
            'step' => 'ghl_contact',
            'attempt' => $booking->attempts,
            'provider' => 'gohighlevel',
            'provider_request_id' => $contactId,
            'http_status' => $httpStatus,
            'duration_ms' => $durationMs,
            'ok' => $ok,
            'error_code' => $errorCode,
            'email_hash' => hash('sha256', strtolower((string) $booking->email)),
            'phone_mask' => self::maskPhone((string) $booking->phone),
        ]);
    }

    public static function maskPhone(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone) ?? '';
        if (strlen($digits) < 4) {
            return '***';
        }

        return str_repeat('*', max(0, strlen($digits) - 4)) . substr($digits, -4);
    }
}
