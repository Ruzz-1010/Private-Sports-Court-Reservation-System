<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Reservation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * Local stand-in for the provider's hosted checkout page.
 *
 * Only active when PAYMENT_GATEWAY=fake. It produces the same signed payload
 * a provider would send and posts it to the real webhook endpoint, so the
 * production code path runs unchanged during development and presentations.
 */
class PaymentSimulatorController extends Controller
{
    public function show(Request $request, string $reference): View
    {
        $reservation = Reservation::where('reference_number', $reference)
            ->with('court')
            ->firstOrFail();

        $this->authorize('view', $reservation);

        return view('customer.payments.simulator', [
            'reservation' => $reservation,
            'payment' => $reservation->payments()->latest('id')->first(),
            'isFakeGateway' => config('payments.gateway') === 'fake',
        ]);
    }

    /**
     * Send a signed webhook to the application's own endpoint.
     */
    public function send(Request $request, string $reference): JsonResponse
    {
        $reservation = Reservation::where('reference_number', $reference)
            ->with(['court', 'user', 'payments'])
            ->firstOrFail();

        $this->authorize('view', $reservation);

        $payment = $reservation->payments()->latest('id')->firstOrFail();

        $outcome = $request->string('outcome')->toString() ?: 'paid';
        $payload = $this->buildPayload($reservation, $payment, $outcome);
        $rawBody = json_encode($payload, JSON_THROW_ON_ERROR);

        $secret = config('payments.paymongo.webhook_secret') ?: 'fake-webhook-secret';

        // withBody sends the exact bytes that were signed. Using asJson() here
        // would re-encode the payload and break the HMAC comparison, because
        // the signature covers the raw request body.
        $response = Http::withBody($rawBody, 'application/json')
            ->withHeaders([
                config('payments.webhook.signature_header') => hash_hmac('sha256', $rawBody, $secret),
            ])
            ->post(url('/'.config('payments.webhook.path')));

        return response()->json([
            'sent' => $outcome,
            'webhook_status' => $response->status(),
            'webhook_body' => $response->json(),
            'reservation_status' => $reservation->fresh()->status->value,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function buildPayload(Reservation $reservation, Payment $payment, string $outcome): array
    {
        $status = match ($outcome) {
            'paid' => 'paid',
            'failed' => 'failed',
            'amount_mismatch' => 'paid',
            default => 'paid',
        };

        $amount = $outcome === 'amount_mismatch'
            ? (int) round(((float) $payment->amount * 100) + 10000)
            : (int) round((float) $payment->amount * 100);

        return [
            'data' => [
                'id' => 'evt_'.Str::lower(Str::random(24)),
                'type' => 'checkout.session.payment.paid',
                'attributes' => [
                    'data' => [
                        'id' => $payment->provider_checkout_session_id ?? 'cs_'.Str::lower(Str::random(24)),
                        'type' => 'checkout.session',
                        'attributes' => [
                            'status' => $status,
                            'amount' => $amount,
                            'currency' => $payment->currency,
                            'payment_method_type' => 'card',
                            'metadata' => [
                                'reservation_reference' => $reservation->reference_number,
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }
}
