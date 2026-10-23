{{--
    Reservation received — sent the moment a booking is created, before payment.

    Same shape as emails/confirmed so the two read as one system, but the
    emphasis is the reference number and the payment deadline rather than a
    receipt: no receipt exists yet and none is faked here.
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
                            We have your reservation. Your slot is held for you.
                        </p>

                        {{-- Status banner --}}
                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#EFF6FF;border:1px solid #BFDBFE;border-radius:10px;margin-bottom:20px;">
                            <tr>
                                <td style="padding:14px 18px;">
                                    <p style="margin:0;font-size:15px;font-weight:700;color:#1D4ED8;letter-spacing:.4px;">
                                        &#9679;&nbsp; RESERVATION RECEIVED
                                    </p>
                                    <p style="margin:4px 0 0;font-size:13px;color:#1E40AF;">
                                        {{ $statusLine }}
                                    </p>
                                </td>
                            </tr>
                        </table>

                        {{-- Highlighted slip card --}}
                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#EFF6FF;border:2px solid #60A5FA;border-radius:10px;margin-bottom:22px;">
                            <tr>
                                <td style="padding:16px 18px;">

                                    <p style="margin:0 0 12px;font-size:11px;font-weight:700;color:#1D4ED8;letter-spacing:1.2px;">
                                        YOUR BOOKING SLIP
                                    </p>

                                    <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                                        <tr>
                                            <td style="padding:0 0 10px;">
                                                <p style="margin:0;font-size:11px;color:#1E40AF;letter-spacing:.4px;">REFERENCE NUMBER</p>
                                                <p style="margin:2px 0 0;font-size:16px;font-weight:700;color:#0F172A;font-family:'Consolas',Menlo,monospace;">
                                                    {{ $reference }}
                                                </p>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td style="padding:0;">
                                                <p style="margin:0;font-size:11px;color:#1E40AF;letter-spacing:.4px;">{{ $onSite ? 'AMOUNT TO PAY AT THE COURT' : 'AMOUNT TO PAY' }}</p>
                                                <p style="margin:2px 0 0;font-size:22px;font-weight:700;color:#0F172A;">
                                                    PHP {{ $amount }}
                                                </p>
                                            </td>
                                        </tr>
                                    </table>

                                    @if ($deadlineLine !== null)
                                        <p style="margin:12px 0 0;padding-top:12px;border-top:1px dashed #93C5FD;font-size:12px;color:#1E40AF;">
                                            {{ $deadlineLine }}
                                        </p>
                                    @endif

                                    @if ($hasPdf)
                                        <p style="margin:10px 0 0;font-size:12px;color:#1E40AF;">
                                            Your printable slip is <strong>attached to this email</strong> and stamped <strong>PENDING PAYMENT</strong>.
                                        </p>
                                    @endif

                                </td>
                            </tr>
                        </table>

                        {{-- Booking details --}}
                        <p style="margin:0 0 6px;font-size:11px;font-weight:700;color:#64748B;letter-spacing:1.2px;">
                            BOOKING DETAILS
                        </p>

                        @include('emails._booking-details', ['rows' => $details])

                        @if ($instruction !== null)
                            <p style="margin:16px 0 0;padding:12px 14px;background:#F8FAFC;border-left:3px solid #CBD5E1;border-radius:0 6px 6px 0;font-size:13px;line-height:1.6;color:#475569;">
                                {{ $instruction }}
                            </p>
                        @endif

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
                            This is an automated message from {{ $facilityName }}. Reply to this email or visit the
                            court management desk if anything above looks wrong.
                        </p>
                    </td>
                </tr>

            </table>

        </td>
    </tr>
</table>

</body>
</html>
