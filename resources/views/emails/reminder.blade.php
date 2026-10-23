{{--
    Reservation reminder — sent shortly before a confirmed booking starts.

    Same visual shape as emails/received and emails/confirmed so all three read
    as one system, but the emphasis here is the time left and the reference
    number the customer shows at the desk.
--}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="color-scheme" content="light">
    <meta name="supported-color-schemes" content="light">
    <title>{{ $subject }}</title>
</head>
<body style="margin:0;padding:0;background:#F1F5F9;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif;">

<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#F1F5F9;">
    <tr>
        <td align="center" style="padding:28px 16px;">

            <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%;">

                {{-- Header --}}
                <tr>
                    <td style="background:#0F172A;padding:20px 26px;border-radius:12px 12px 0 0;">
                        <p style="margin:0;font-size:15px;font-weight:700;color:#FFFFFF;letter-spacing:.2px;">
                            {{ $facilityName }}
                        </p>
                        <p style="margin:3px 0 0;font-size:12px;color:#94A3B8;">
                            {{ $facilityAddress }}
                        </p>
                    </td>
                </tr>

                {{-- Body --}}
                <tr>
                    <td style="background:#FFFFFF;padding:26px;border:1px solid #E2E8F0;border-top:none;">

                        <p style="margin:0 0 4px;font-size:17px;font-weight:700;color:#0F172A;">
                            Hi {{ $customerName }},
                        </p>

                        <p style="margin:0 0 18px;font-size:14px;line-height:1.6;color:#475569;">
                            Your court time starts in about {{ $timeLeft }}. Here is everything you need at the desk.
                        </p>

                        {{-- Countdown banner --}}
                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#ECFDF5;border:1px solid #A7F3D0;border-radius:10px;margin-bottom:20px;">
                            <tr>
                                <td align="center" style="padding:16px 18px;">
                                    <p style="margin:0;font-size:11px;font-weight:700;color:#047857;letter-spacing:1.4px;">
                                        STARTS AT
                                    </p>
                                    <p style="margin:4px 0 0;font-size:26px;font-weight:700;color:#065F46;">
                                        {{ $startsAt }}
                                    </p>
                                    <p style="margin:4px 0 0;font-size:12px;color:#047857;">
                                        Be at the court 10 minutes early so you can warm up.
                                    </p>
                                </td>
                            </tr>
                        </table>

                        {{-- Reference card --}}
                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#EFF6FF;border:2px solid #60A5FA;border-radius:10px;margin-bottom:22px;">
                            <tr>
                                <td style="padding:16px 18px;">
                                    <p style="margin:0;font-size:11px;color:#1E40AF;letter-spacing:.4px;">SHOW THIS REFERENCE NUMBER AT THE COURT</p>
                                    <p style="margin:3px 0 0;font-size:20px;font-weight:700;color:#0F172A;font-family:'Consolas',Menlo,monospace;">
                                        {{ $reference }}
                                    </p>
                                </td>
                            </tr>
                        </table>

                        {{-- Booking details --}}
                        <p style="margin:0 0 6px;font-size:11px;font-weight:700;color:#64748B;letter-spacing:1.2px;">
                            BOOKING DETAILS
                        </p>

                        @include('emails._booking-details', ['rows' => $details])

                        <p style="margin:16px 0 0;padding:12px 14px;background:#F8FAFC;border-left:3px solid #CBD5E1;border-radius:0 6px 6px 0;font-size:13px;line-height:1.6;color:#475569;">
                            Cannot make it? Cancel from your dashboard as early as you can, so the slot can go to another player.
                        </p>

                        {{-- Action button --}}
                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:24px 0 6px;">
                            <tr>
                                <td align="center">
                                    <a href="{{ $actionUrl }}" style="display:inline-block;background:#1D4ED8;color:#FFFFFF;text-decoration:none;font-size:15px;font-weight:700;padding:13px 30px;border-radius:8px;">
                                        {{ $actionText }}
                                    </a>
                                </td>
                            </tr>
                        </table>

                    </td>
                </tr>

                {{-- Footer --}}
                <tr>
                    <td style="background:#F8FAFC;padding:16px 26px;border:1px solid #E2E8F0;border-top:none;border-radius:0 0 12px 12px;">
                        <p style="margin:0;font-size:12px;line-height:1.6;color:#94A3B8;">
                            This is an automated reminder from {{ $facilityName }}. See you on the court!
                        </p>
                    </td>
                </tr>

            </table>

        </td>
    </tr>
</table>

</body>
</html>
