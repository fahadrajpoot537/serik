<?php

namespace App\Mail;

use App\Models\AppointmentBooking;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class AppointmentTeamNotification extends Mailable
{
    public function __construct(public AppointmentBooking $booking)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'New appointment ' . $this->booking->booking_reference . ' — ' . $this->booking->consultation_type,
        );
    }

    public function content(): Content
    {
        $ghlId = trim((string) $this->booking->ghl_contact_id);

        return new Content(
            view: 'emails.appointment-team',
            with: [
                'clientName' => $this->booking->name,
                'clientEmail' => $this->booking->email,
                'clientPhone' => $this->booking->phone,
                'date' => $this->booking->dateString(),
                'timeLabel' => $this->booking->timeLabel(),
                'timezone' => $this->booking->timezone,
                'consultationType' => $this->booking->consultation_type,
                'source' => $this->booking->source,
                'bookingReference' => $this->booking->booking_reference,
                'propertyUrl' => $this->booking->property_url,
                'crmContactId' => $ghlId !== '' ? $ghlId : null,
                'submittedPage' => $this->booking->submitted_page,
            ],
        );
    }
}
