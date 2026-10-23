<?php

namespace App\Services\Receipts;

use App\Enums\PaymentMethod;
use App\Models\Receipt;
use App\Models\Reservation;
use Barryvdh\DomPDF\Facade\Pdf as DomPdf;
use Barryvdh\DomPDF\PDF;
use Illuminate\Http\Response;

/**
 * Renders an issued receipt as a downloadable PDF.
 *
 * The receipt row is the only source. Every displayed value was copied at
 * issue time, so a later rename of the court or a rate change cannot rewrite
 * a receipt that a customer already downloaded.
 */
class GenerateReceiptPdf
{
    public function handle(Receipt $receipt): Response
    {
        return $this->render($receipt)->download($this->filename($receipt));
    }

    /**
     * Build the PDF without streaming a response, so callers that need the
     * bytes (the confirmation email attachment) render the exact same document
     * the download route serves.
     */
    public function render(Receipt $receipt): PDF
    {
        $receipt->loadMissing('reservation');

        $pdf = DomPdf::loadView('receipts.pdf', [
            'receipt' => $receipt,
            'pending' => false,
            'timezone' => config('reservation.timezone'),
            'facilityName' => config('reservation.facility_name'),
            'facilityAddress' => config('reservation.facility_address'),
        ]);

        // A4 portrait, millimetres.
        $pdf->setPaper('a4', 'portrait');

        return $pdf;
    }

    /**
     * Build the same document layout for a reservation whose payment is still
     * pending, so the booking email looks exactly like the confirmation email.
     * The only difference is the PENDING PAYMENT stamp and the amount wording.
     *
     * Nothing is stored: the receipt number is only issued once a payment is
     * verified, so this slip stays a reservation document.
     */
    public function renderPending(Reservation $reservation): PDF
    {
        $reservation->loadMissing('court');

        $pdf = DomPdf::loadView('receipts.pdf', [
            'receipt' => $this->pendingDocument($reservation),
            'pending' => true,
            'timezone' => config('reservation.timezone'),
            'facilityName' => config('reservation.facility_name'),
            'facilityAddress' => config('reservation.facility_address'),
        ]);

        $pdf->setPaper('a4', 'portrait');

        return $pdf;
    }

    /**
     * Shape a reservation like a receipt row so one Blade template renders both
     * documents. The returned model is never saved.
     */
    public function pendingDocument(Reservation $reservation): Receipt
    {
        $reservation->loadMissing(['court', 'user']);
        $timezone = config('reservation.timezone');

        return new Receipt([
            'receipt_number' => '',
            'reservation_id' => $reservation->id,
            'reservation_reference' => $reservation->reference_number,
            'customer_name' => $reservation->user?->name ?? 'Customer',
            'customer_email' => $reservation->user?->email ?? '',
            'court_name' => $reservation->court?->name ?? 'Court',
            'court_location' => $reservation->court?->location ?? '',
            'reservation_date' => $reservation->starts_at->setTimezone($timezone)->toDateString(),
            'starts_at' => $reservation->starts_at,
            'ends_at' => $reservation->ends_at,
            'duration_minutes' => (int) $reservation->starts_at->diffInMinutes($reservation->ends_at),
            'payment_method' => $reservation->payment_method === PaymentMethod::OnSite ? 'on_site' : 'online',
            'payment_status' => 'pending',
            'amount_paid' => $reservation->total_amount,
            'currency' => 'PHP',
            'transaction_date' => $reservation->created_at ?? now(),
        ]);
    }

    public function pendingFilename(Reservation $reservation): string
    {
        return 'reservation-slip-'.$reservation->reference_number.'.pdf';
    }

    public function filename(Receipt $receipt): string
    {
        // Receipt numbers open with the issue-date dash, which would otherwise
        // double up into "receipt--261020-...". Derived from the stored number
        // only, never from customer input, so no untrusted characters reach
        // the Content-Disposition header.
        return 'receipt-'.ltrim($receipt->receipt_number, '-').'.pdf';
    }
}
