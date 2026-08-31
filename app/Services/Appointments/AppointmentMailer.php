<?php

namespace App\Services\Appointments;

use App\Exceptions\AppointmentWorkflowException;
use App\Mail\AppointmentClientConfirmation;
use App\Mail\AppointmentTeamNotification;
use App\Models\AppointmentBooking;
use App\Support\EmailRecipients;
use Illuminate\Mail\SentMessage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class AppointmentMailer
{
    public function sendClient(AppointmentBooking $booking): string
    {
        $existing = trim((string) $booking->client_mail_id);
        if ($existing !== '' || $booking->client_mail_sent_at !== null) {
            return $existing !== '' ? $existing : 'accepted';
        }

        $email = strtolower(trim((string) $booking->email));
        if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new AppointmentWorkflowException(
                AppointmentWorkflowException::CLIENT_EMAIL,
                'client_email',
                false,
                'Client email is invalid.'
            );
        }

        try {
            $sent = Mail::to($email)->send(new AppointmentClientConfirmation($booking));
        } catch (Throwable $e) {
            Log::channel('appointments')->warning('appointment_workflow', [
                'booking_reference' => $booking->booking_reference,
                'appointment_id' => $booking->id,
                'step' => 'client_email',
                'attempt' => $booking->attempts,
                'provider' => 'mail',
                'ok' => false,
                'error_code' => AppointmentWorkflowException::MAIL_AUTH,
            ]);

            $message = strtolower($e->getMessage());
            $auth = str_contains($message, 'auth') || str_contains($message, 'credential') || str_contains($message, '535');

            throw new AppointmentWorkflowException(
                $auth ? AppointmentWorkflowException::MAIL_AUTH : AppointmentWorkflowException::CLIENT_EMAIL,
                'client_email',
                $auth,
                'Client confirmation was not accepted.'
            );
        }

        return $this->messageId($sent);
    }

    public function sendTeam(AppointmentBooking $booking, string $recipient): string
    {
        $existing = trim((string) $booking->team_mail_id);
        if ($existing !== '' || $booking->team_mail_sent_at !== null) {
            return $existing !== '' ? $existing : 'accepted';
        }

        $recipient = strtolower(trim($recipient));
        if ($recipient === '' || ! filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
            throw new AppointmentWorkflowException(
                AppointmentWorkflowException::MISSING_RECIPIENT,
                'team_email',
                false,
                'Assigned team recipient is missing.'
            );
        }

        try {
            $sent = Mail::to($recipient)->send(new AppointmentTeamNotification($booking));
        } catch (Throwable $e) {
            Log::channel('appointments')->warning('appointment_workflow', [
                'booking_reference' => $booking->booking_reference,
                'appointment_id' => $booking->id,
                'step' => 'team_email',
                'attempt' => $booking->attempts,
                'provider' => 'mail',
                'ok' => false,
                'error_code' => AppointmentWorkflowException::TEAM_EMAIL,
            ]);

            throw new AppointmentWorkflowException(
                AppointmentWorkflowException::TEAM_EMAIL,
                'team_email',
                true,
                'Team notification was not accepted.'
            );
        }

        return $this->messageId($sent);
    }

    public static function resolveTeamRecipient(?string $explicit = null): string
    {
        $explicit = trim((string) $explicit);
        if ($explicit !== '') {
            if (filter_var($explicit, FILTER_VALIDATE_EMAIL)) {
                return strtolower($explicit);
            }

            throw new AppointmentWorkflowException(
                AppointmentWorkflowException::MISSING_RECIPIENT,
                'team_email',
                false,
                'Assigned team recipient is missing.'
            );
        }

        $configured = trim((string) config('serik.appointment.notify_email', ''));
        if ($configured !== '') {
            if (filter_var($configured, FILTER_VALIDATE_EMAIL)) {
                return strtolower($configured);
            }

            throw new AppointmentWorkflowException(
                AppointmentWorkflowException::MISSING_RECIPIENT,
                'team_email',
                false,
                'Assigned team recipient is missing.'
            );
        }

        try {
            $notice = EmailRecipients::contactNoticeRecipients();
            foreach ((array) $notice as $email) {
                $email = strtolower(trim((string) $email));
                if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    return $email;
                }
            }
        } catch (Throwable) {
        }

        throw new AppointmentWorkflowException(
            AppointmentWorkflowException::MISSING_RECIPIENT,
            'team_email',
            false,
            'Assigned team recipient is missing.'
        );
    }

    private function messageId(mixed $sent): string
    {
        if ($sent instanceof SentMessage) {
            $id = trim((string) $sent->getMessageId());
            if ($id !== '') {
                return $id;
            }
        }

        return 'accepted';
    }
}
