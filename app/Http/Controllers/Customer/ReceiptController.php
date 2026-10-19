<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Receipt;
use App\Services\Receipts\GenerateReceiptPdf;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Serves the PDF receipt for a verified payment.
 */
class ReceiptController extends Controller
{
    public function __construct(private readonly GenerateReceiptPdf $pdf) {}

    public function show(Request $request, Receipt $receipt): Response|StreamedResponse
    {
        $this->authorize('download', $receipt);

        return $this->pdf->handle($receipt);
    }
}
