<?php

namespace App\Services\Payments;

use App\Contracts\PaymentGateway;
use App\Models\Reservation;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Maya Pay with Maya adapter for one-time Dynamic QRPh payments.
 *
 * Maya creates the QR and hosts it on its payment page. This application never
 * receives wallet credentials or card details.
 */
class MayaGateway implements PaymentGateway
{
    public function name(): string
    {
        return 'maya';
    }

    /**
     * @return array{id: string, url: string, reference: string}
     */
    public function createCheckoutSession(Reservation $reservation): array
    {
        $reference = $this->referenceFor($reservation);

        $response = $this->publicClient()->post('/payments/v1/qr/payments', [
            'totalAmount' => [
                'value' => (float) $reservation->total_amount,
                'currency' => config('reservation.currency'),
            ],
            'requestReferenceNumber' => $reference,
            'redirectUrl' => [
                'success' => route('customer.reservations.show', $reservation),
                'failure' => route('customer.reservations.show', $reservation),
                'cancel' => route('customer.reservations.show', $reservation),
            ],
        ]);

        if ($response->failed()) {
            Log::error('Maya Dynamic QR creation failed.', [
                'status' => $response->status(),
                'reference' => $reservation->reference_number,
            ]);

            throw new RuntimeException('Unable to generate the Maya QR code. Please try again.');
        }

        $id = $response->json('paymentId');
        $url = $response->json('redirectUrl');

        if (! is_string($id) || ! is_string($url)) {
            throw new RuntimeException('Maya returned an unexpected QR payment response.');
        }

        return ['id' => $id, 'url' => $url, 'reference' => $reference];
    }

    /**
     * @return array{status: string, amount: int|float|null, currency: string|null, reference: string|null}
     */
    public function fetchSession(string $sessionId): array
    {
        try {
            $response = $this->secretClient()->get('/payments/v1/payments/'.$sessionId);
        } catch (ConnectionException) {
            Log::warning('Maya payment lookup failed.', ['payment_id' => $sessionId]);

            return ['status' => 'unknown', 'amount' => null, 'currency' => null, 'reference' => null];
        }

        if ($response->failed()) {
            Log::warning('Maya payment lookup returned an error.', [
                'payment_id' => $sessionId,
                'status' => $response->status(),
            ]);

            return ['status' => 'unknown', 'amount' => null, 'currency' => null, 'reference' => null];
        }

        return [
            'status' => (string) $response->json('status', 'unknown'),
            'amount' => $response->json('amount', $response->json('totalAmount.value')),
            'currency' => $response->json('currency', $response->json('totalAmount.currency')),
            'reference' => $response->json('requestReferenceNumber'),
        ];
    }

    private function referenceFor(Reservation $reservation): string
    {
        // Maya limits this field to 36 characters and requires it to be unique
        // for each generated payment, including a retry for the same booking.
        return Str::limit($reservation->reference_number, 23, '').'-'.Str::upper(Str::random(12));
    }

    private function publicClient(): PendingRequest
    {
        return $this->client($this->key('public_key', 'MAYA_PUBLIC_KEY'));
    }

    private function secretClient(): PendingRequest
    {
        return $this->client($this->key('secret_key', 'MAYA_SECRET_KEY'));
    }

    private function client(string $key): PendingRequest
    {
        return Http::baseUrl(config('payments.maya.base_url'))
            ->timeout((int) config('payments.maya.timeout'))
            ->withBasicAuth($key, '')
            ->acceptJson()
            ->asJson();
    }

    private function key(string $configKey, string $environmentName): string
    {
        $key = config('payments.maya.'.$configKey);

        if (! is_string($key) || $key === '') {
            throw new RuntimeException("Maya is not configured. Set {$environmentName}.");
        }

        return $key;
    }
}
