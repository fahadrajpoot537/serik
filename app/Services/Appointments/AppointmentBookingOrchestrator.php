<?php

namespace App\Services\Appointments;

use App\Exceptions\AppointmentWorkflowException;
use App\Models\AppointmentBooking;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

class AppointmentBookingOrchestrator
{
    public function __construct(
        protected AppointmentGhlService $ghl,
        protected AppointmentCalendarService $calendar,
        protected AppointmentMailer $mailer,
    ) {
    }

    public function run(AppointmentBooking $booking): AppointmentBooking
    {
        $lock = Cache::lock('appointment-workflow:' . $booking->id, 90);
        if (! $lock->get()) {
            throw new AppointmentWorkflowException(
                AppointmentWorkflowException::QUEUE_STALL,
                'processing',
                true,
                'Appointment is already being confirmed.'
            );
        }

        $t0 = microtime(true);

        try {
            $booking->refresh();
            if ($booking->isConfirmed()) {
                return $booking;
            }

            $booking->status = AppointmentBooking::STATUS_PROCESSING;
            $booking->attempts = ((int) $booking->attempts) + 1;
            $booking->last_attempted_at = now();
            $booking->save();

            $this->stepGhl($booking);
            $this->stepCalendar($booking);
            $this->stepClientMail($booking);
            $this->stepTeamMail($booking);

            $booking->markStep('confirmed', true);
            $booking->status = AppointmentBooking::STATUS_CONFIRMED;
            $booking->confirmed_at = now();
            $booking->failed_step = null;
            $booking->error_code = null;
            $booking->save();

            Log::channel('appointments')->info('appointment_workflow', [
                'booking_reference' => $booking->booking_reference,
                'appointment_id' => $booking->id,
                'step' => 'confirmed',
                'attempt' => $booking->attempts,
                'provider' => 'orchestrator',
                'duration_ms' => (int) ((microtime(true) - $t0) * 1000),
                'ok' => true,
            ]);

            return $booking;
        } catch (AppointmentWorkflowException $e) {
            $this->persistFailure($booking, $e);
            Log::channel('appointments')->warning('appointment_workflow', [
                'booking_reference' => $booking->booking_reference,
                'appointment_id' => $booking->id,
                'step' => $e->step,
                'attempt' => $booking->attempts,
                'provider' => 'orchestrator',
                'duration_ms' => (int) ((microtime(true) - $t0) * 1000),
                'ok' => false,
                'error_code' => $e->errorCode,
                'retryable' => $e->retryable,
            ]);

            throw $e;
        } catch (Throwable $e) {
            $wrapped = new AppointmentWorkflowException(
                AppointmentWorkflowException::CRM_TIMEOUT,
                'processing',
                true,
                'Appointment confirmation failed.'
            );
            $this->persistFailure($booking, $wrapped);
            Log::channel('appointments')->error('appointment_workflow', [
                'booking_reference' => $booking->booking_reference,
                'appointment_id' => $booking->id,
                'step' => 'processing',
                'attempt' => $booking->attempts,
                'provider' => 'orchestrator',
                'ok' => false,
                'error_code' => 'unexpected',
            ]);

            throw $wrapped;
        } finally {
            optional($lock)->release();
        }
    }

    private function stepGhl(AppointmentBooking $booking): void
    {
        if ($booking->stepDone(AppointmentBooking::STEP_GHL) && filled($booking->ghl_contact_id)) {
            return;
        }

        $result = $this->ghl->upsertContact($booking);
        $booking->ghl_contact_id = $result['contact_id'];
        $booking->markStep(AppointmentBooking::STEP_GHL, true);
        $booking->markStep(AppointmentBooking::STEP_CRM, true);
        $booking->failed_step = null;
        $booking->save();
    }

    private function stepCalendar(AppointmentBooking $booking): void
    {
        if ($booking->stepDone(AppointmentBooking::STEP_CALENDAR) && filled($booking->calendar_event_id)) {
            return;
        }

        $eventId = $this->calendar->createOrSkip($booking);
        $booking->calendar_event_id = $eventId;
        $booking->markStep(AppointmentBooking::STEP_CALENDAR, true);
        $booking->failed_step = null;
        $booking->save();
    }

    private function stepClientMail(AppointmentBooking $booking): void
    {
        if ($booking->stepDone(AppointmentBooking::STEP_CLIENT_MAIL) && ($booking->client_mail_sent_at !== null || filled($booking->client_mail_id))) {
            return;
        }

        $id = $this->mailer->sendClient($booking);
        $booking->client_mail_id = $id;
        $booking->client_mail_sent_at = now();
        $booking->markStep(AppointmentBooking::STEP_CLIENT_MAIL, true);
        $booking->failed_step = null;
        $booking->save();
    }

    private function stepTeamMail(AppointmentBooking $booking): void
    {
        if ($booking->stepDone(AppointmentBooking::STEP_TEAM_MAIL) && ($booking->team_mail_sent_at !== null || filled($booking->team_mail_id))) {
            return;
        }

        $recipient = AppointmentMailer::resolveTeamRecipient($booking->assigned_recipient);
        $booking->assigned_recipient = $recipient;
        $id = $this->mailer->sendTeam($booking, $recipient);
        $booking->team_mail_id = $id;
        $booking->team_mail_sent_at = now();
        $booking->markStep(AppointmentBooking::STEP_TEAM_MAIL, true);
        $booking->failed_step = null;
        $booking->save();
    }

    private function persistFailure(AppointmentBooking $booking, AppointmentWorkflowException $e): void
    {
        try {
            $booking->refresh();
            if ($booking->isConfirmed()) {
                return;
            }
            $booking->failed_step = $e->step;
            $booking->error_code = $e->errorCode;
            $booking->status = $e->retryable
                ? AppointmentBooking::STATUS_PENDING
                : AppointmentBooking::STATUS_FAILED;
            $booking->save();
        } catch (Throwable) {
        }
    }
}
