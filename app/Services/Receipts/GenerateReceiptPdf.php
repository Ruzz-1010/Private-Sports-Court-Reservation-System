<?php

namespace App\Services\Receipts;

use App\Models\Receipt;
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
            'timezone' => config('reservation.timezone'),
            'facilityName' => config('reservation.facility_name'),
            'facilityAddress' => config('reservation.facility_address'),
        ]);

        // A4 portrait, millimetres.
        $pdf->setPaper('a4', 'portrait');

        return $pdf;
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
