{{-- dompdf renders this with its own layout engine, so the styling is plain
     CSS only. Tailwind classes are not available in a PDF. --}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Receipt {{ $receipt->receipt_number }}</title>
    <style>
        @page { margin: 28px 32px; }

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 11px;
            color: #0f172a;
            margin: 0;
        }

        .header {
            border-bottom: 2px solid #0f172a;
            padding-bottom: 12px;
            margin-bottom: 18px;
        }

        .facility { font-size: 15px; font-weight: bold; letter-spacing: .3px; }
        .address { font-size: 10px; color: #475569; margin-top: 2px; }

        .title-row { text-align: right; }
        .doc-title { font-size: 18px; font-weight: bold; letter-spacing: 1.5px; }
        .doc-number { font-size: 12px; font-weight: bold; margin-top: 3px; }
        .issued { font-size: 9px; color: #64748b; margin-top: 2px; }

        .paid-stamp {
            border: 2px solid #15803d;
            color: #15803d;
            font-size: 11px;
            font-weight: bold;
            padding: 5px 10px;
            display: inline-block;
            margin-top: 10px;
        }

        table { width: 100%; border-collapse: collapse; }
        .section-title {
            font-size: 10px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: .8px;
            color: #64748b;
            border-bottom: 1px solid #cbd5e1;
            padding-bottom: 4px;
            margin: 18px 0 8px;
        }

        .label { font-size: 10px; color: #64748b; width: 34%; text-align: left; }
        .value { font-size: 11px; font-weight: bold; text-align: right; width: 66%; }
        td, th { padding: 5px 0; vertical-align: top; }

        .amount-row td {
            border-top: 2px solid #0f172a;
            border-bottom: 2px solid #0f172a;
            padding: 9px 0;
            font-size: 13px;
            font-weight: bold;
        }

        .footer {
            margin-top: 22px;
            border-top: 1px solid #cbd5e1;
            padding-top: 10px;
            font-size: 9px;
            color: #64748b;
            text-align: center;
            line-height: 1.6;
        }
    </style>
</head>
<body>

    <table>
        <tr>
            <td style="width: 60%; text-align: left; border: none;">
                <div class="facility">{{ $facilityName }}</div>
                <div class="address">{{ $facilityAddress }}</div>
            </td>
            <td class="title-row" style="border: none;">
                <div class="doc-title">RECEIPT</div>
                <div class="doc-number">{{ $receipt->receipt_number }}</div>
                <div class="issued">Issued {{ $receipt->transaction_date->setTimezone($timezone)->format('M j, Y g:i A') }}</div>
                <div class="paid-stamp">PAID</div>
            </td>
        </tr>
    </table>

    <div class="section-title">Reservation</div>
    <table>
        <tr>
            <td class="label">Reference number</td>
            <td class="value">{{ $receipt->reservation_reference }}</td>
        </tr>
        <tr>
            <td class="label">Court</td>
            <td class="value">{{ $receipt->court_name }}</td>
        </tr>
        <tr>
            <td class="label">Location</td>
            <td class="value">{{ $receipt->court_location }}</td>
        </tr>
        <tr>
            <td class="label">Date</td>
            <td class="value">{{ \Illuminate\Support\Carbon::parse($receipt->reservation_date)->format('l, M j, Y') }}</td>
        </tr>
        <tr>
            <td class="label">Time</td>
            <td class="value">
                {{ $receipt->starts_at->setTimezone($timezone)->format('g:i A') }}
                &ndash;
                {{ $receipt->ends_at->setTimezone($timezone)->format('g:i A') }}
                ({{ intdiv($receipt->duration_minutes, 60) }} {{ intdiv($receipt->duration_minutes, 60) === 1 ? 'hour' : 'hours' }})
            </td>
        </tr>
    </table>

    <div class="section-title">Customer</div>
    <table>
        <tr>
            <td class="label">Name</td>
            <td class="value">{{ $receipt->customer_name }}</td>
        </tr>
        <tr>
            <td class="label">Email</td>
            <td class="value">{{ $receipt->customer_email }}</td>
        </tr>
    </table>

    <div class="section-title">Payment</div>
    <table>
        <tr>
            <td class="label">Payment method</td>
            <td class="value">{{ $receipt->payment_method === 'on_site' ? 'On-site (verified by staff)' : 'Online' }}</td>
        </tr>
        <tr>
            <td class="label">Payment status</td>
            <td class="value">{{ ucfirst($receipt->payment_status) }}</td>
        </tr>
        <tr>
            <td class="label">Transaction date</td>
            <td class="value">{{ $receipt->transaction_date->setTimezone($timezone)->format('M j, Y g:i A') }}</td>
        </tr>
        <tr class="amount-row">
            <td style="font-size: 13px; font-weight: bold;">Amount paid</td>
            <td style="text-align: right; font-size: 14px;">
                {{ $receipt->currency }} {{ number_format((float) $receipt->amount_paid, 2) }}
            </td>
        </tr>
    </table>

    <div class="footer">
        This receipt was generated from a verified payment and is valid as proof of booking.<br>
        Amounts are already inclusive of all charges. No card or wallet credentials are stored by this facility.
    </div>

</body>
</html>