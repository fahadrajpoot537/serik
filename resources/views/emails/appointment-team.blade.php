<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>New appointment</title>
</head>
<body style="margin:0;padding:0;background:#f4f4f5;font-family:Arial,Helvetica,sans-serif;color:#111;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f4f4f5;padding:24px 0;">
    <tr>
        <td align="center">
            <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="max-width:600px;background:#ffffff;border-radius:8px;overflow:hidden;">
                <tr>
                    <td style="background:#111;color:#fff;padding:20px 28px;font-size:20px;font-weight:bold;">
                        Serik Realty — New appointment
                    </td>
                </tr>
                <tr>
                    <td style="padding:28px;">
                        <p style="margin:0 0 16px;">A consultation appointment has been booked.</p>
                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;margin:16px 0 8px;">
                            <tr>
                                <td style="padding:8px 0;border-bottom:1px solid #eee;">Client</td>
                                <td style="padding:8px 0;border-bottom:1px solid #eee;font-weight:bold;">{{ $clientName }}</td>
                            </tr>
                            <tr>
                                <td style="padding:8px 0;border-bottom:1px solid #eee;">Email</td>
                                <td style="padding:8px 0;border-bottom:1px solid #eee;font-weight:bold;">{{ $clientEmail }}</td>
                            </tr>
                            <tr>
                                <td style="padding:8px 0;border-bottom:1px solid #eee;">Phone</td>
                                <td style="padding:8px 0;border-bottom:1px solid #eee;font-weight:bold;">{{ $clientPhone }}</td>
                            </tr>
                            <tr>
                                <td style="padding:8px 0;border-bottom:1px solid #eee;">Date</td>
                                <td style="padding:8px 0;border-bottom:1px solid #eee;font-weight:bold;">{{ $date }}</td>
                            </tr>
                            <tr>
                                <td style="padding:8px 0;border-bottom:1px solid #eee;">Time</td>
                                <td style="padding:8px 0;border-bottom:1px solid #eee;font-weight:bold;">{{ $timeLabel }} ({{ $timezone }})</td>
                            </tr>
                            <tr>
                                <td style="padding:8px 0;border-bottom:1px solid #eee;">Consultation Type</td>
                                <td style="padding:8px 0;border-bottom:1px solid #eee;font-weight:bold;">{{ $consultationType }}</td>
                            </tr>
                            <tr>
                                <td style="padding:8px 0;border-bottom:1px solid #eee;">Source</td>
                                <td style="padding:8px 0;border-bottom:1px solid #eee;font-weight:bold;">{{ $source }}</td>
                            </tr>
                            <tr>
                                <td style="padding:8px 0;border-bottom:1px solid #eee;">Booking reference</td>
                                <td style="padding:8px 0;border-bottom:1px solid #eee;font-weight:bold;">{{ $bookingReference }}</td>
                            </tr>
                            @if (!empty($crmContactId))
                            <tr>
                                <td style="padding:8px 0;border-bottom:1px solid #eee;">CRM contact</td>
                                <td style="padding:8px 0;border-bottom:1px solid #eee;font-weight:bold;">{{ $crmContactId }}</td>
                            </tr>
                            @endif
                            @if (!empty($propertyUrl))
                            <tr>
                                <td style="padding:8px 0;border-bottom:1px solid #eee;">Property</td>
                                <td style="padding:8px 0;border-bottom:1px solid #eee;font-weight:bold;">{{ $propertyUrl }}</td>
                            </tr>
                            @endif
                            @if (!empty($submittedPage))
                            <tr>
                                <td style="padding:8px 0;">Submitted page</td>
                                <td style="padding:8px 0;font-weight:bold;">{{ $submittedPage }}</td>
                            </tr>
                            @endif
                        </table>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
</body>
</html>
