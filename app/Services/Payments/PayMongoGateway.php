<?php

namespace App\Services\Payments;

use App\Contracts\PaymentGateway;
use App\Models\Reservation;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/** PayMongo Hosted Checkout adapter for QRPh payments. */
class PayMongoGateway implements PaymentGateway
{
    public function name(): string
    {
        return 'paymongo';
    }

    public function createCheckoutSession(Reservation $reservation): array
    {
        $response = $this->client()->withHeaders([
            'Idempotency-Key' => 'reservation-'.$reservation->reference_number,
        ])->post('/v2/checkout_sessions', [
            'data' => ['attributes' => [
                'line_items' => [[
                    'name' => $reservation->court->name.' court reservation',
                    'amount' => $this->centavos((string) $reservation->total_amount),
                    'currency' => config('reservation.currency'),
                    'quantity' => 1,
                ]],
                'payment_method_types' => ['qrph'],
                'success_url' => route('customer.reservations.show', $reservation),
                'cancel_url' => route('customer.reservations.show', $reservation),
                'reference_number' => $reservation->reference_number,
            ]],
        ]);

        if ($response->failed()) {
            Log::error('PayMongo checkout creation failed.', ['status' => $response->status(), 'reservation' => $reservation->id]);
            throw new RuntimeException('Unable to open PayMongo checkout. Please try again.');
        }

        $id = $response->json('data.id');
        $url = $response->json('data.attributes.checkout_url');
        if (! is_string($id) || ! is_string($url)) {
            throw new RuntimeException('PayMongo returned an unexpected checkout response.');
        }

        return ['id' => $id, 'url' => $url, 'reference' => $reservation->reference_number];
    }

    public function fetchSession(string $sessionId): array
    {
        try {
            // PayMongo only exposes checkout-session retrieval on /v1. The /v2
            // path exists for creation but returns "route does not exist" for
            // GET, which made server-side verification impossible.
            $response = $this->client()->get('/v1/checkout_sessions/'.$sessionId);
        } catch (ConnectionException) {
            return ['status' => 'unknown', 'amount' => null, 'currency' => null, 'reference' => null, 'payment_id' => null];
        }

        if ($response->failed()) {
            Log::warning('PayMongo checkout lookup failed.', ['session_id' => $sessionId, 'status' => $response->status()]);

            return ['status' => 'unknown', 'amount' => null, 'currency' => null, 'reference' => null, 'payment_id' => null];
        }

        $attributes = $response->json('data.attributes', []);
        $paid = collect($attributes['payments'] ?? [])->first(fn (mixed $payment): bool => data_get($payment, 'attributes.status') === 'paid');

        return [
            'status' => $paid === null ? 'pending' : 'paid',
            'amount' => data_get($paid, 'attributes.amount'),
            'currency' => data_get($paid, 'attributes.currency'),
            'reference' => $attributes['reference_number'] ?? null,
            'payment_id' => data_get($paid, 'id'),
        ];
    }

    private function client(): PendingRequest
    {
        $key = config('payments.paymongo.secret_key');
        if (! is_string($key) || $key === '') {
            throw new RuntimeException('PayMongo is not configured. Set PAYMONGO_SECRET_KEY.');
        }

        return Http::baseUrl((string) config('payments.paymongo.base_url'))
            ->connectTimeout(3)->timeout((int) config('payments.paymongo.timeout'))
            ->withBasicAuth($key, '')->acceptJson()->asJson();
    }

    private function centavos(string $amount): int
    {
        [$whole, $fraction] = array_pad(explode('.', $amount, 2), 2, '');

        return ((int) $whole * 100) + (int) str_pad(substr($fraction, 0, 2), 2, '0');
    }
}
