<?php

namespace App\Services\Receipts;

use App\Models\Receipt;
use Barryvdh\DomPDF\Facade\Pdf;
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
        $receipt->loadMissing('reservation');

        $pdf = Pdf::loadView('receipts.pdf', [
            'receipt' => $receipt,
            'timezone' => config('reservation.timezone'),
            'facilityName' => config('reservation.facility_name'),
            'facilityAddress' => config('reservation.facility_address'),
        ]);

        // A4 portrait, millimetres. Filenames are derived from the receipt
        // number only, never from customer input, so no header value can carry
        // untrusted characters into the response.
        $pdf->setPaper('a4', 'portrait');

        return $pdf->download($this->filename($receipt));
    }

    private function filename(Receipt $receipt): string
    {
        return 'receipt-'.$receipt->receipt_number.'.pdf';
    }
}
