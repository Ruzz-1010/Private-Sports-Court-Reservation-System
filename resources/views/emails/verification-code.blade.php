{{--
    Verification code — sent when an account is created or a code is resent.

    Same wrapper as the other emails so every message from the facility looks
    like one system. The code is the only big thing on the page because that is
    all the customer needs to copy.
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
                            Enter this code on the verification page to finish setting up your account.
                        </p>

                        {{-- The code --}}
                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#F8FAFC;border:2px solid #CBD5E1;border-radius:10px;margin-bottom:20px;">
                            <tr>
                                <td align="center" style="padding:20px 18px;">
                                    <p style="margin:0 0 8px;font-size:11px;font-weight:700;color:#64748B;letter-spacing:1.2px;">
                                        YOUR VERIFICATION CODE
                                    </p>
                                    <p style="margin:0;font-size:34px;font-weight:700;color:#0F172A;font-family:'Consolas',Menlo,monospace;letter-spacing:10px;">
                                        {{ $code }}
                                    </p>
                                </td>
                            </tr>
                        </table>

                        <p style="margin:0;padding:12px 14px;background:#FFFBEB;border-left:3px solid #FCD34D;border-radius:0 6px 6px 0;font-size:13px;line-height:1.6;color:#92400E;">
                            This code expires in {{ $expiresMinutes }} minutes and works once.
                            If you did not create an account, you can ignore this email.
                        </p>

                        <p style="margin:18px 0 0;font-size:13px;line-height:1.6;color:#64748B;">
                            For your safety we never ask for this code by phone, chat, or text message.
                        </p>

                    </td>
                </tr>

                {{-- Footer --}}
                <tr>
                    <td style="background:#F8FAFC;padding:16px 26px;border:1px solid #E2E8F0;border-top:none;border-radius:0 0 12px 12px;">
                        <p style="margin:0;font-size:12px;line-height:1.6;color:#94A3B8;">
                            This is an automated message from {{ $facilityName }}. Visit the court management desk
                            if you need help with your account.
                        </p>
                    </td>
                </tr>

            </table>

        </td>
    </tr>
</table>

</body>
</html>
