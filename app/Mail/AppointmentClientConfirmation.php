<?php

namespace App\Mail;

use App\Models\AppointmentBooking;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class AppointmentClientConfirmation extends Mailable
{
    public function __construct(public AppointmentBooking $booking)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your Serik Realty appointment is booked — ' . $this->booking->booking_reference,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.appointment-client',
            with: [
                'clientName' => $this->booking->name,
                'date' => $this->booking->dateString(),
                'timeLabel' => $this->booking->timeLabel(),
                'timezone' => $this->booking->timezone,
                'consultationType' => $this->booking->consultation_type,
                'bookingReference' => $this->booking->booking_reference,
                'officePhone' => \App\Support\OfficePhone::display(),
                'officeEmail' => 'info@serik.ca',
            ],
        );
    }
}
