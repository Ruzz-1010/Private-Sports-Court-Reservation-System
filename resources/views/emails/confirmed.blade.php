{{--
    Payment confirmed — the receipt email.

    Written as raw HTML rather than a MailMessage line list because the panel and
    the customer both need to read this quickly: one status banner, one
    highlighted receipt card, one short detail table, one button. Inline styles
    only, because Gmail and most clients strip <style> blocks and drop classes.

    The subject, the single action button, and the attached PDF are still set by
    App\Notifications\ReservationConfirmed, so Feature/Receipts assertions on
    actionText, actionUrl and rawAttachments keep passing unchanged.
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
                            Your payment has been confirmed and your court is booked. Nothing else
                            needs to be done — just arrive at the court at your scheduled time.
                        </p>

                        {{-- Status banner --}}
                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#ECFDF5;border:1px solid #A7F3D0;border-radius:10px;margin-bottom:20px;">
                            <tr>
                                <td style="padding:14px 18px;">
                                    <p style="margin:0;font-size:15px;font-weight:700;color:#047857;letter-spacing:.4px;">
                                        &#10003;&nbsp; RESERVATION CONFIRMED
                                    </p>
                                    <p style="margin:4px 0 0;font-size:13px;color:#065F46;">
                                        Payment received. Present the receipt below when you arrive.
                                    </p>
                                </td>
                            </tr>
                        </table>

                        {{-- Highlighted receipt card --}}
                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#FFFBEB;border:2px solid #F59E0B;border-radius:10px;margin-bottom:22px;">
                            <tr>
                                <td style="padding:16px 18px;">

                                    <p style="margin:0 0 12px;font-size:11px;font-weight:700;color:#B45309;letter-spacing:1.2px;">
                                        YOUR RECEIPT
                                    </p>

                                    <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                                        <tr>
                                            <td style="padding:0 0 10px;">
                                                <p style="margin:0;font-size:11px;color:#92400E;letter-spacing:.4px;">RECEIPT NUMBER</p>
                                                <p style="margin:2px 0 0;font-size:16px;font-weight:700;color:#0F172A;font-family:'Consolas',Menlo,monospace;">
                                                    {{ $receiptNumber ?? 'Being prepared' }}
                                                </p>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td style="padding:0;">
                                                <p style="margin:0;font-size:11px;color:#92400E;letter-spacing:.4px;">AMOUNT PAID</p>
                                                <p style="margin:2px 0 0;font-size:22px;font-weight:700;color:#0F172A;">
                                                    PHP {{ $amount }}
                                                </p>
                                            </td>
                                        </tr>
                                    </table>

                                    <p style="margin:12px 0 0;padding-top:12px;border-top:1px dashed #FCD34D;font-size:12px;color:#92400E;">
                                        Paid via {{ $paymentMethod }} &middot; status <strong style="color:#B45309;">PAID</strong>
                                    </p>

                                    @if ($hasPdf)
                                        <p style="margin:10px 0 0;font-size:12px;color:#92400E;">
                                            The full PDF receipt is <strong>attached to this email</strong> — you do not need to log in to open it.
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

                        <p style="margin:14px 0 0;text-align:center;font-size:12px;color:#94A3B8;">
                            Reference <strong style="color:#475569;">{{ $reference }}</strong> &mdash; keep this email as proof of your booking.
                        </p>

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
