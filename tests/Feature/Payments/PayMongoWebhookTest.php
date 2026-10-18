<?php

namespace Tests\Feature\Payments;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\ReservationStatus;
use App\Enums\Role;
use App\Jobs\SendConfirmationEmail;
use App\Models\Court;
use App\Models\Payment;
use App\Models\PaymentEvent;
use App\Models\Receipt;
use App\Models\Reservation;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class PayMongoWebhookTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'payments.gateway' => 'fake',
            'payments.paymongo.webhook_secret' => 'test-webhook-secret',
        ]);
    }

    private function webhookUrl(): string
    {
        return '/'.config('payments.webhook.path');
    }

    /**
     * Build a provider-shaped payload.
     *
     * @return array<string, mixed>
     */
    private function payload(
        Reservation $reservation,
        Payment $payment,
        ?int $amount = null,
        string $status = 'paid',
        string $currency = 'PHP',
        ?string $eventId = null,
    ): array {
        return [
            'data' => [
                'id' => $eventId ?? 'evt_'.strtolower(bin2hex(random_bytes(8))),
                'type' => 'checkout.session.payment.paid',
                'attributes' => [
                    'data' => [
                        'id' => $payment->provider_checkout_session_id ?? 'cs_test',
                        'type' => 'checkout.session',
                        'attributes' => [
                            'status' => $status,
                            'amount' => $amount ?? (int) round((float) $payment->amount * 100),
                            'currency' => $currency,
                            'metadata' => [
                                'reservation_reference' => $reservation->reference_number,
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function sendWebhook(array $payload, ?string $signature = null)
    {
        $raw = json_encode($payload);

        return $this->call(
            'POST',
            $this->webhookUrl(),
            [],
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_PAYMONGO_SIGNATURE' => $signature ?? hash_hmac('sha256', $raw, 'test-webhook-secret'),
            ],
            $raw,
        );
    }

    private function pendingOnlineReservation(): array
    {
        $court = Court::factory()->create(['hourly_rate' => 500]);
        $customer = User::factory()->create(['role' => Role::Customer]);

        $start = CarbonImmutable::now(config('reservation.timezone'))->addDays(3)->setTime(10, 0);

        $reservation = Reservation::create([
            'reference_number' => 'RSRV-WEBHOOK-1',
            'user_id' => $customer->id,
            'court_id' => $court->id,
            'starts_at' => $start,
            'ends_at' => $start->addHours(2),
            'hourly_rate' => 500,
            'total_amount' => 1000,
            'status' => ReservationStatus::Pending,
            'payment_method' => PaymentMethod::Online,
            'payment_deadline' => now()->addMinutes(15),
        ]);

        $payment = Payment::create([
            'reservation_id' => $reservation->id,
            'method' => PaymentMethod::Online,
            'provider' => 'fake',
            'amount' => 1000,
            'currency' => 'PHP',
            'status' => PaymentStatus::Pending,
        ]);

        return [$reservation, $payment];
    }

    public function test_a_signed_payment_event_confirms_the_reservation(): void
    {
        Queue::fake();
        [$reservation, $payment] = $this->pendingOnlineReservation();

        $this->sendWebhook($this->payload($reservation, $payment))->assertOk();

        $this->assertSame(ReservationStatus::Confirmed, $reservation->fresh()->status);
        $this->assertSame(PaymentStatus::Paid, $payment->fresh()->status);
        $this->assertNotNull($payment->fresh()->paid_at);
    }

    public function test_a_confirmed_payment_issues_exactly_one_receipt(): void
    {
        Queue::fake();
        [$reservation, $payment] = $this->pendingOnlineReservation();

        $this->sendWebhook($this->payload($reservation, $payment))->assertOk();

        $this->assertSame(1, Receipt::where('reservation_id', $reservation->id)->count());

        $receipt = Receipt::where('reservation_id', $reservation->id)->firstOrFail();

        $this->assertSame($reservation->reference_number, $receipt->reservation_reference);
        $this->assertSame('1000.00', $receipt->amount_paid);
        $this->assertSame('PHP', $receipt->currency);
    }

    public function test_the_receipt_captures_an_immutable_snapshot(): void
    {
        Queue::fake();
        [$reservation, $payment] = $this->pendingOnlineReservation();
        $courtName = $reservation->court->name;

        $this->sendWebhook($this->payload($reservation, $payment))->assertOk();

        $receipt = Receipt::firstOrFail();

        // Changing the court afterwards must not rewrite an issued receipt.
        $reservation->court->update(['name' => 'Renamed Court']);

        $this->assertSame($courtName, $receipt->fresh()->court_name);
    }

    public function test_an_invalid_signature_is_rejected(): void
    {
        Queue::fake();
        [$reservation, $payment] = $this->pendingOnlineReservation();

        $this->sendWebhook($this->payload($reservation, $payment), 'forged-signature')->assertUnauthorized();

        $this->assertSame(ReservationStatus::Pending, $reservation->fresh()->status);
        $this->assertSame(PaymentStatus::Pending, $payment->fresh()->status);
        $this->assertSame(0, Receipt::count());
    }

    public function test_a_missing_signature_is_rejected(): void
    {
        Queue::fake();
        [$reservation, $payment] = $this->pendingOnlineReservation();

        $raw = json_encode($this->payload($reservation, $payment));

        $this->call('POST', $this->webhookUrl(), [], [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], $raw)->assertUnauthorized();

        $this->assertSame(ReservationStatus::Pending, $reservation->fresh()->status);
    }

    public function test_a_rejected_webhook_is_audited(): void
    {
        Queue::fake();
        [$reservation, $payment] = $this->pendingOnlineReservation();

        $this->sendWebhook($this->payload($reservation, $payment), 'forged');

        $this->assertDatabaseHas('audit_logs', ['action' => 'payment.webhook_rejected']);
    }

    public function test_a_duplicate_event_does_not_confirm_twice(): void
    {
        Queue::fake();
        [$reservation, $payment] = $this->pendingOnlineReservation();

        $payload = $this->payload($reservation, $payment, eventId: 'evt_duplicate_test');

        $this->sendWebhook($payload)->assertOk();
        $this->sendWebhook($payload)->assertOk();

        $this->assertSame(1, Receipt::where('reservation_id', $reservation->id)->count());
        $this->assertSame(1, PaymentEvent::where('provider_event_id', 'evt_duplicate_test')->count());
    }

    public function test_a_duplicate_event_does_not_queue_a_second_email(): void
    {
        Queue::fake();
        [$reservation, $payment] = $this->pendingOnlineReservation();

        $payload = $this->payload($reservation, $payment, eventId: 'evt_dup_email');

        $this->sendWebhook($payload)->assertOk();
        Queue::assertPushed(SendConfirmationEmail::class, 1);

        $this->sendWebhook($payload)->assertOk();
        Queue::assertPushed(SendConfirmationEmail::class, 1);
    }

    public function test_an_amount_mismatch_is_flagged_for_review(): void
    {
        Queue::fake();
        [$reservation, $payment] = $this->pendingOnlineReservation();

        // 100.00 claimed against a 1000.00 reservation.
        $this->sendWebhook($this->payload($reservation, $payment, amount: 10000))->assertOk();

        $this->assertSame(PaymentStatus::RequiresReview, $payment->fresh()->status);
        $this->assertSame(ReservationStatus::Pending, $reservation->fresh()->status);
        $this->assertSame(0, Receipt::count());
    }

    public function test_a_currency_mismatch_is_flagged_for_review(): void
    {
        Queue::fake();
        [$reservation, $payment] = $this->pendingOnlineReservation();

        $this->sendWebhook($this->payload($reservation, $payment, currency: 'USD'))->assertOk();

        $this->assertSame(PaymentStatus::RequiresReview, $payment->fresh()->status);
        $this->assertSame(ReservationStatus::Pending, $reservation->fresh()->status);
    }

    public function test_a_non_paid_status_does_not_confirm(): void
    {
        Queue::fake();
        [$reservation, $payment] = $this->pendingOnlineReservation();

        $this->sendWebhook($this->payload($reservation, $payment, status: 'failed'))->assertOk();

        $this->assertSame(ReservationStatus::Pending, $reservation->fresh()->status);
        $this->assertSame(0, Receipt::count());
    }

    public function test_an_unknown_reservation_reference_is_ignored(): void
    {
        Queue::fake();
        [$reservation, $payment] = $this->pendingOnlineReservation();

        $payload = $this->payload($reservation, $payment);
        $payload['data']['attributes']['data']['attributes']['metadata']['reservation_reference'] = 'RSRV-DOES-NOT-EXIST';

        $this->sendWebhook($payload)->assertOk();

        $this->assertSame(ReservationStatus::Pending, $reservation->fresh()->status);
    }

    public function test_a_late_payment_does_not_reclaim_an_expired_reservation(): void
    {
        Queue::fake();
        [$reservation, $payment] = $this->pendingOnlineReservation();

        // Hold expired and the slot was released before the callback landed.
        $reservation->update([
            'status' => ReservationStatus::Expired,
            'payment_deadline' => now()->subMinutes(5),
        ]);

        $this->sendWebhook($this->payload($reservation, $payment))->assertOk();

        $this->assertSame(ReservationStatus::Expired, $reservation->fresh()->status);
        $this->assertSame(PaymentStatus::RequiresReview, $payment->fresh()->status);
        $this->assertSame(0, Receipt::count());
    }

    public function test_a_cancelled_reservation_is_not_confirmed(): void
    {
        Queue::fake();
        [$reservation, $payment] = $this->pendingOnlineReservation();

        $reservation->update(['status' => ReservationStatus::Cancelled]);

        $this->sendWebhook($this->payload($reservation, $payment))->assertOk();

        $this->assertSame(ReservationStatus::Cancelled, $reservation->fresh()->status);
        $this->assertSame(PaymentStatus::RequiresReview, $payment->fresh()->status);
    }

    public function test_the_event_is_recorded_for_audit(): void
    {
        Queue::fake();
        [$reservation, $payment] = $this->pendingOnlineReservation();

        $this->sendWebhook($this->payload($reservation, $payment, eventId: 'evt_audit_check'));

        $this->assertDatabaseHas('payment_events', [
            'provider_event_id' => 'evt_audit_check',
            'processing_status' => 'processed',
        ]);
    }

    public function test_raw_payload_is_not_stored(): void
    {
        Queue::fake();
        [$reservation, $payment] = $this->pendingOnlineReservation();

        $this->sendWebhook($this->payload($reservation, $payment));

        $event = PaymentEvent::latest('id')->firstOrFail();

        $this->assertNull($event->payload, 'Raw provider payloads may contain billing details.');
    }

    public function test_the_webhook_requires_no_authentication(): void
    {
        Queue::fake();
        [$reservation, $payment] = $this->pendingOnlineReservation();

        // No actingAs: the provider is an external server.
        $this->sendWebhook($this->payload($reservation, $payment))->assertOk();
    }

    public function test_a_browser_redirect_alone_does_not_confirm(): void
    {
        Queue::fake();
        [$reservation, $payment] = $this->pendingOnlineReservation();

        $this->actingAs($reservation->user)
            ->get(route('customer.reservations.show', $reservation))
            ->assertOk();

        // Visiting the page after a provider redirect must not settle anything.
        $this->assertSame(ReservationStatus::Pending, $reservation->fresh()->status);
        $this->assertSame(PaymentStatus::Pending, $payment->fresh()->status);
        $this->assertSame(0, Receipt::count());
    }

    public function test_email_is_dispatched_only_after_confirmation(): void
    {
        Queue::fake();
        [$reservation, $payment] = $this->pendingOnlineReservation();

        $this->sendWebhook($this->payload($reservation, $payment, amount: 1))->assertOk();

        Queue::assertNothingPushed();
    }
}
