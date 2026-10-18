<?php

namespace App\Services\Payments;

use App\Contracts\PaymentGateway;
use App\Models\Reservation;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * PayMongo Hosted Checkout adapter.
 *
 * Card details are entered on PayMongo's own page and never reach this
 * application. Only the checkout URL is exchanged here.
 */
class PayMongoGateway implements PaymentGateway
{
    public function name(): string
    {
        return 'paymongo';
    }

    /**
     * @return array{id: string, url: string}
     *
     * @throws RuntimeException when the provider call fails
     */
    public function createCheckoutSession(Reservation $reservation): array
    {
        $response = $this->client()->post('/checkout_sessions', [
            'data' => [
                'attributes' => [
                    // PayMongo works in minor units (centavos).
                    'amount' => (int) round((float) $reservation->total_amount * 100),
                    'currency' => config('reservation.currency'),
                    'description' => sprintf(
                        '%s reservation %s',
                        $reservation->court?->name ?? 'Court',
                        $reservation->reference_number,
                    ),
                    'billing' => [
                        'name' => $reservation->user?->name,
                        'email' => $reservation->user?->email,
                    ],
                    // Echoed back on the webhook so we can find the reservation.
                    'metadata' => [
                        'reservation_reference' => $reservation->reference_number,
                    ],
                    'success_url' => route('customer.reservations.show', $reservation),
                    'cancel_url' => route('customer.reservations.show', $reservation),
                    'payment_method_types' => ['card', 'gcash', 'paymaya'],
                ],
            ],
        ]);

        if ($response->failed()) {
            // Log the status and body, never the secret key.
            Log::error('PayMongo checkout session failed.', [
                'status' => $response->status(),
                'reference' => $reservation->reference_number,
            ]);

            throw new RuntimeException('Unable to start the payment session. Please try again.');
        }

        $id = $response->json('data.id');
        $url = $response->json('data.attributes.checkout_url');

        if (! is_string($id) || ! is_string($url)) {
            throw new RuntimeException('The payment provider returned an unexpected response.');
        }

        return ['id' => $id, 'url' => $url];
    }

    /**
     * PayMongo signs the raw request body with HMAC-SHA256 using the webhook
     * secret and sends the digest in the Paymongo-Signature header.
     */
    public function verifyWebhookSignature(string $rawBody, ?string $signature): bool
    {
        $secret = config('payments.paymongo.webhook_secret');

        if (! is_string($secret) || $secret === '' || $signature === null || $signature === '') {
            return false;
        }

        return hash_equals(hash_hmac('sha256', $rawBody, $secret), $signature);
    }

    /**
     * @return array{status: string, amount: int|null, currency: string|null, reference: string|null}
     */
    public function fetchSession(string $sessionId): array
    {
        try {
            $response = $this->client()->get('/checkout_sessions/'.$sessionId);
        } catch (ConnectionException $e) {
            Log::warning('PayMongo session lookup failed.', ['session_id' => $sessionId]);

            return ['status' => 'unknown', 'amount' => null, 'currency' => null, 'reference' => null];
        }

        if ($response->failed()) {
            return ['status' => 'unknown', 'amount' => null, 'currency' => null, 'reference' => null];
        }

        return [
            'status' => (string) $response->json('data.attributes.status', 'unknown'),
            'amount' => $response->json('data.attributes.amount'),
            'currency' => $response->json('data.attributes.currency'),
            'reference' => $response->json('data.attributes.metadata.reservation_reference'),
        ];
    }

    private function client(): PendingRequest
    {
        $secret = config('payments.paymongo.secret_key');

        if (! is_string($secret) || $secret === '') {
            throw new RuntimeException('PayMongo is not configured. Set PAYMONGO_SECRET_KEY.');
        }

        return Http::baseUrl(config('payments.paymongo.base_url'))
            ->timeout((int) config('payments.paymongo.timeout'))
            ->withBasicAuth($secret, '')
            ->acceptJson()
            ->asJson();
    }
}
