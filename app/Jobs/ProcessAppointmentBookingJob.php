<?php

namespace App\Jobs;

use App\Exceptions\AppointmentWorkflowException;
use App\Models\AppointmentBooking;
use App\Services\Appointments\AppointmentBookingOrchestrator;
use App\Support\SerikQueue;
use Illuminate\Contracts\Queue\ShouldBeUniqueUntilProcessing;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

class ProcessAppointmentBookingJob implements ShouldQueue, ShouldBeUniqueUntilProcessing
{
    use Queueable;

    public int $tries;

    /** @var list<int> */
    public array $backoff;

    public int $timeout = 180;

    public int $uniqueFor = 900;

    public function __construct(public int $bookingId)
    {
        $this->onQueue(SerikQueue::ghl());
        $this->tries = max(1, (int) config('serik.appointment.job_tries', 8));
        $this->backoff = array_values(array_map(
            'intval',
            (array) config('serik.appointment.job_backoff', [15, 30, 60, 120, 300, 600, 900])
        ));
        $timeout = (int) config('serik.appointment.job_timeout', 180);
        $this->timeout = $timeout > 0 ? $timeout : 180;
    }

    public function uniqueId(): string
    {
        return 'appointment-booking-' . $this->bookingId;
    }

    public function handle(AppointmentBookingOrchestrator $orchestrator): void
    {
        $booking = AppointmentBooking::query()->find($this->bookingId);
        if (! $booking) {
            return;
        }

        if ($booking->isConfirmed() || $booking->isCancelled()) {
            return;
        }

        try {
            $orchestrator->run($booking);
        } catch (AppointmentWorkflowException $e) {
            if (! $e->retryable) {
                return;
            }

            throw $e;
        }
    }

    public function failed(?Throwable $e): void
    {
        $booking = AppointmentBooking::query()->find($this->bookingId);
        if ($booking && ! $booking->isConfirmed()) {
            $booking->status = AppointmentBooking::STATUS_FAILED;
            $booking->failed_step = $booking->failed_step ?: 'queue';
            $booking->error_code = $booking->error_code ?: AppointmentWorkflowException::QUEUE_STALL;
            $booking->save();
        }

        Log::channel('appointments')->error('appointment_workflow', [
            'booking_reference' => $booking->booking_reference ?? null,
            'appointment_id' => $this->bookingId,
            'step' => $booking->failed_step ?? 'queue',
            'attempt' => $booking->attempts ?? null,
            'provider' => 'queue',
            'ok' => false,
            'error_code' => AppointmentWorkflowException::QUEUE_STALL,
        ]);
    }
}
