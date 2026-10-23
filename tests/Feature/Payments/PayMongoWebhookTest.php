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
use App\Notifications\ReservationConfirmed;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use ReflectionProperty;
use Tests\TestCase;

/**
 * A PayMongo callback is only a claim.
 *
 * Every test here asserts the same rule from a different angle: local state
 * changes only when the signature verifies AND PayMongo's own secret-key
 * endpoint agrees on the amount, currency, and reference. The payload is never
 * trusted on its own, and a browser redirect settles nothing.
 */
class PayMongoWebhookTest extends TestCase
{
    use RefreshDatabase;

    private const SECRET = 'whsk_test_secret';

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'payments.paymongo.secret_key' => 'sk_test_payment_key',
            'payments.paymongo.webhook_secret' => self::SECRET,
        ]);
    }

    /** @return array{Reservation, Payment} */
    private function pendingOnlineReservation(): array
    {
        $court = Court::factory()->create(['hourly_rate' => 500]);
        $customer = User::factory()->create(['role' => Role::Customer]);
        $start = CarbonImmutable::now(config('reservation.timezone'))->addDays(3)->setTime(10, 0);

        $reservation = Reservation::create([
            'reference_number' => 'RSRV-PM-1',
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
            'provider' => 'paymongo',
            'provider_checkout_session_id' => 'cs_test_123',
            'amount' => 1000,
            'currency' => 'PHP',
            'status' => PaymentStatus::Pending,
        ]);

        return [$reservation, $payment];
    }

    /**
     * Stand in for PayMongo's secret-key checkout lookup.
     *
     * @param  array<string, mixed>  $overrides
     */
    private function stubPayMongo(array $overrides = []): void
    {
        Http::fake([
            // Retrieval is served on /v1 only; /v2 exists for creation and
            // returns "route does not exist" for GET (verified against the
            // live API), so the fake must match the same path the gateway calls.
            'https://api.paymongo.com/v1/checkout_sessions/cs_test_123' => Http::response([
                'data' => ['attributes' => array_merge([
                    'reference_number' => 'RSRV-PM-1',
                    'payments' => [[
                        'id' => 'pay_test_123',
                        'attributes' => ['status' => 'paid', 'amount' => 100000, 'currency' => 'PHP'],
                    ]],
                ], $overrides)],
            ]),
        ]);
    }

    /**
     * Deliver a callback exactly as PayMongo would sign it.
     *
     * @param  array<string, mixed>  $payload
     */
    private function sendSigned(array $payload = [], ?string $signatureOverride = null, ?int $timestampOverride = null): mixed
    {
        $body = (string) json_encode($payload ?: $this->eventPayload());
        $timestamp = (string) ($timestampOverride ?? now()->timestamp);
        $signature = $signatureOverride
            ?? hash_hmac('sha256', $timestamp.'.'.$body, self::SECRET);

        return $this->call(
            'POST',
            route('webhooks.paymongo'),
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json', 'HTTP_PAYMONGO_SIGNATURE' => "t={$timestamp},te={$signature},li="],
            $body,
        );
    }

    /** @return array<string, mixed> */
    private function eventPayload(string $eventId = 'evt_test_123', string $sessionId = 'cs_test_123'): array
    {
        return [
            'data' => [
                'id' => $eventId,
                'attributes' => [
                    'type' => 'checkout_session.payment.paid',
                    'livemode' => false,
                    'data' => ['id' => $sessionId],
                ],
            ],
        ];
    }

    public function test_a_signed_paid_event_confirms_a_server_verified_checkout(): void
    {
        Queue::fake();
        [$reservation, $payment] = $this->pendingOnlineReservation();
        $this->stubPayMongo();

        $this->sendSigned()->assertOk();

        $this->assertSame(ReservationStatus::Confirmed, $reservation->fresh()->status);
        $this->assertSame(PaymentStatus::Paid, $payment->fresh()->status);
        $this->assertNotNull($payment->fresh()->paid_at);
        Queue::assertPushed(SendConfirmationEmail::class, 1);
    }

    public function test_an_unsigned_callback_is_rejected(): void
    {
        [$reservation, $payment] = $this->pendingOnlineReservation();
        $this->stubPayMongo();

        $this->postJson(route('webhooks.paymongo'), $this->eventPayload())->assertStatus(400);

        $this->assertSame(ReservationStatus::Pending, $reservation->fresh()->status);
        $this->assertSame(PaymentStatus::Pending, $payment->fresh()->status);
        $this->assertSame(0, Receipt::count());
    }

    public function test_a_forged_signature_is_rejected(): void
    {
        [$reservation] = $this->pendingOnlineReservation();
        $this->stubPayMongo();

        $this->sendSigned(signatureOverride: 'deadbeef')->assertStatus(400);

        $this->assertSame(ReservationStatus::Pending, $reservation->fresh()->status);
        $this->assertSame(0, Receipt::count());
    }

    public function test_a_signature_signed_with_the_wrong_secret_is_rejected(): void
    {
        [$reservation] = $this->pendingOnlineReservation();
        $this->stubPayMongo();

        $body = (string) json_encode($this->eventPayload());
        $timestamp = (string) now()->timestamp;
        $bad = hash_hmac('sha256', $timestamp.'.'.$body, 'the-wrong-secret');

        $this->call('POST', route('webhooks.paymongo'), [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_PAYMONGO_SIGNATURE' => "t={$timestamp},te={$bad},li=",
        ], $body)->assertStatus(400);

        $this->assertSame(ReservationStatus::Pending, $reservation->fresh()->status);
    }

    /**
     * A captured callback must not stay replayable forever.
     */
    public function test_a_stale_timestamp_is_rejected(): void
    {
        [$reservation] = $this->pendingOnlineReservation();
        $this->stubPayMongo();

        $this->sendSigned(timestampOverride: now()->subMinutes(30)->timestamp)->assertStatus(400);

        $this->assertSame(ReservationStatus::Pending, $reservation->fresh()->status);
        $this->assertSame(0, Receipt::count());
    }

    public function test_a_rejected_callback_is_audited(): void
    {
        $this->pendingOnlineReservation();

        $this->postJson(route('webhooks.paymongo'), [])->assertStatus(400);

        $this->assertDatabaseHas('audit_logs', ['action' => 'payment.webhook_rejected']);
    }

    public function test_a_confirmed_payment_issues_exactly_one_receipt(): void
    {
        Queue::fake();
        [$reservation] = $this->pendingOnlineReservation();
        $this->stubPayMongo();

        $this->sendSigned()->assertOk();

        $receipt = Receipt::where('reservation_id', $reservation->id)->firstOrFail();

        $this->assertSame(1, Receipt::where('reservation_id', $reservation->id)->count());
        $this->assertSame($reservation->reference_number, $receipt->reservation_reference);
        $this->assertSame('1000.00', $receipt->amount_paid);
        $this->assertSame('PHP', $receipt->currency);
    }

    public function test_the_receipt_captures_an_immutable_snapshot(): void
    {
        Queue::fake();
        [$reservation] = $this->pendingOnlineReservation();
        $courtName = $reservation->court->name;
        $this->stubPayMongo();

        $this->sendSigned()->assertOk();

        $receipt = Receipt::firstOrFail();

        // Renaming the court afterwards must not rewrite an issued receipt.
        $reservation->court->update(['name' => 'Renamed Court']);

        $this->assertSame($courtName, $receipt->fresh()->court_name);
    }

    public function test_a_duplicate_event_does_not_confirm_twice(): void
    {
        Queue::fake();
        [$reservation] = $this->pendingOnlineReservation();
        $this->stubPayMongo();

        $this->sendSigned()->assertOk();
        $this->sendSigned()->assertOk();

        $this->assertSame(1, Receipt::where('reservation_id', $reservation->id)->count());
        $this->assertSame(1, PaymentEvent::where('provider_event_id', 'evt_test_123')->count());
        $this->assertSame(ReservationStatus::Confirmed, $reservation->fresh()->status);
    }

    public function test_a_duplicate_event_does_not_queue_a_second_email(): void
    {
        Queue::fake();
        $this->pendingOnlineReservation();
        $this->stubPayMongo();

        $this->sendSigned()->assertOk();
        Queue::assertPushed(SendConfirmationEmail::class, 1);

        $this->sendSigned()->assertOk();
        Queue::assertPushed(SendConfirmationEmail::class, 1);
    }

    public function test_an_amount_mismatch_is_flagged_without_confirming(): void
    {
        Queue::fake();
        [$reservation, $payment] = $this->pendingOnlineReservation();
        $this->stubPayMongo(['payments' => [[
            'id' => 'pay_test_123',
            'attributes' => ['status' => 'paid', 'amount' => 100, 'currency' => 'PHP'],
        ]]]);

        $this->sendSigned()->assertOk();

        $this->assertSame(ReservationStatus::Pending, $reservation->fresh()->status);
        $this->assertSame(PaymentStatus::RequiresReview, $payment->fresh()->status);
        $this->assertSame(0, Receipt::count());
        Queue::assertNothingPushed();
    }

    public function test_a_currency_mismatch_is_flagged_without_confirming(): void
    {
        [$reservation, $payment] = $this->pendingOnlineReservation();
        $this->stubPayMongo(['payments' => [[
            'id' => 'pay_test_123',
            'attributes' => ['status' => 'paid', 'amount' => 100000, 'currency' => 'USD'],
        ]]]);

        $this->sendSigned()->assertOk();

        $this->assertSame(ReservationStatus::Pending, $reservation->fresh()->status);
        $this->assertSame(PaymentStatus::RequiresReview, $payment->fresh()->status);
    }

    public function test_a_reference_mismatch_is_flagged_without_confirming(): void
    {
        [$reservation, $payment] = $this->pendingOnlineReservation();
        $this->stubPayMongo(['reference_number' => 'RSRV-SOMEONE-ELSE']);

        $this->sendSigned()->assertOk();

        $this->assertSame(ReservationStatus::Pending, $reservation->fresh()->status);
        $this->assertSame(PaymentStatus::RequiresReview, $payment->fresh()->status);
    }

    /**
     * The signature is valid but PayMongo has no paid payment on record. The
     * claim alone must not settle a booking.
     */
    public function test_an_unpaid_checkout_is_flagged_even_with_a_valid_signature(): void
    {
        [$reservation, $payment] = $this->pendingOnlineReservation();
        $this->stubPayMongo(['payments' => []]);

        $this->sendSigned()->assertOk();

        $this->assertSame(ReservationStatus::Pending, $reservation->fresh()->status);
        $this->assertSame(PaymentStatus::RequiresReview, $payment->fresh()->status);
        $this->assertSame(0, Receipt::count());
    }

    public function test_paymongo_being_unreachable_does_not_confirm(): void
    {
        [$reservation, $payment] = $this->pendingOnlineReservation();

        Http::fake(['https://api.paymongo.com/*' => Http::response(status: 500)]);

        $this->sendSigned()->assertOk();

        $this->assertSame(ReservationStatus::Pending, $reservation->fresh()->status);
        $this->assertSame(PaymentStatus::RequiresReview, $payment->fresh()->status);
    }

    public function test_a_late_payment_does_not_reclaim_an_expired_reservation(): void
    {
        Queue::fake();
        [$reservation, $payment] = $this->pendingOnlineReservation();

        // The hold expired and the slot was released before the callback landed.
        $reservation->update([
            'status' => ReservationStatus::Expired,
            'payment_deadline' => now()->subMinutes(5),
        ]);

        $this->stubPayMongo();
        $this->sendSigned()->assertOk();

        $this->assertSame(ReservationStatus::Expired, $reservation->fresh()->status);
        $this->assertSame(PaymentStatus::RequiresReview, $payment->fresh()->status);
        $this->assertSame(0, Receipt::count());
        Queue::assertNothingPushed();
    }

    public function test_a_cancelled_reservation_is_not_confirmed_by_a_late_callback(): void
    {
        [$reservation, $payment] = $this->pendingOnlineReservation();

        $reservation->update(['status' => ReservationStatus::Cancelled]);

        $this->stubPayMongo();
        $this->sendSigned()->assertOk();

        $this->assertSame(ReservationStatus::Cancelled, $reservation->fresh()->status);
        $this->assertSame(PaymentStatus::RequiresReview, $payment->fresh()->status);
    }

    public function test_an_unknown_checkout_session_does_not_confirm_anything(): void
    {
        [$reservation, $payment] = $this->pendingOnlineReservation();
        $this->stubPayMongo();

        $this->sendSigned($this->eventPayload(sessionId: 'cs_does_not_exist'))->assertOk();

        $this->assertSame(ReservationStatus::Pending, $reservation->fresh()->status);
        $this->assertSame(PaymentStatus::Pending, $payment->fresh()->status);
        $this->assertSame(0, Receipt::count());
    }

    public function test_an_unrelated_event_type_is_ignored(): void
    {
        [$reservation] = $this->pendingOnlineReservation();
        $this->stubPayMongo();

        $payload = $this->eventPayload();
        $payload['data']['attributes']['type'] = 'checkout_session.expired';

        $this->sendSigned($payload)->assertOk();

        $this->assertSame(ReservationStatus::Pending, $reservation->fresh()->status);
        $this->assertSame(0, Receipt::count());
        $this->assertDatabaseHas('payment_events', ['processing_status' => 'ignored']);
    }

    public function test_the_event_is_recorded_for_audit(): void
    {
        Queue::fake();
        $this->pendingOnlineReservation();
        $this->stubPayMongo();

        $this->sendSigned()->assertOk();

        $this->assertDatabaseHas('payment_events', [
            'provider' => 'paymongo',
            'provider_event_id' => 'evt_test_123',
            'processing_status' => 'processed',
        ]);
    }

    public function test_raw_payload_is_not_stored(): void
    {
        Queue::fake();
        $this->pendingOnlineReservation();
        $this->stubPayMongo();

        $this->sendSigned()->assertOk();

        $event = PaymentEvent::latest('id')->firstOrFail();

        $this->assertNull($event->payload, 'Raw provider payloads may contain billing details.');
    }

    public function test_the_webhook_requires_no_authentication(): void
    {
        Queue::fake();
        $this->pendingOnlineReservation();
        $this->stubPayMongo();

        // No actingAs: PayMongo is an external server.
        $this->sendSigned()->assertOk();
    }

    public function test_a_browser_redirect_alone_does_not_confirm(): void
    {
        Queue::fake();
        [$reservation, $payment] = $this->pendingOnlineReservation();

        // This is the success_url the customer lands on after paying.
        $this->actingAs($reservation->user)
            ->get(route('customer.reservations.show', $reservation))
            ->assertOk();

        $this->assertSame(ReservationStatus::Pending, $reservation->fresh()->status);
        $this->assertSame(PaymentStatus::Pending, $payment->fresh()->status);
        $this->assertSame(0, Receipt::count());
    }

    public function test_email_is_dispatched_only_after_confirmation(): void
    {
        Queue::fake();
        $this->pendingOnlineReservation();
        $this->stubPayMongo(['payments' => []]);

        $this->sendSigned()->assertOk();

        Queue::assertNothingPushed();
    }

    public function test_confirmation_job_emails_payment_and_receipt_details_to_the_customer(): void
    {
        Queue::fake();
        Notification::fake();
        [$reservation] = $this->pendingOnlineReservation();
        $this->stubPayMongo();

        $this->sendSigned()->assertOk();

        $receipt = Receipt::where('reservation_id', $reservation->id)->firstOrFail();

        (new SendConfirmationEmail($reservation->id))->handle();

        Notification::assertSentTo(
            $reservation->user,
            ReservationConfirmed::class,
            function (ReservationConfirmed $notification) use ($reservation, $receipt): bool {
                $mail = $notification->toMail($reservation->user);
                $data = $mail->viewData;

                // The body is now a hand-built HTML view rather than a list of
                // intro lines, so the same facts are asserted where the view
                // actually receives them.
                return $mail->subject === "Reservation confirmed — {$reservation->reference_number}"
                    && $data['amount'] === '1,000.00'
                    && $data['paymentMethod'] === 'Online'
                    && $data['receiptNumber'] === $receipt->receipt_number
                    && $data['reference'] === $reservation->reference_number
                    && $data['hasPdf'] === true
                    && count($mail->rawAttachments) === 1
                    && str_starts_with($mail->rawAttachments[0]['data'], '%PDF')
                    && $mail->actionUrl === route('customer.receipts.show', $receipt);
            },
        );
    }

    /**
     * bootstrap/app.php resolves the webhook path from the environment before
     * the config repository is bound, and the framework skips forgery
     * verification whenever it is running tests, so a request based test cannot
     * catch a mismatch here. This asserts the registered exclusion itself.
     */
    public function test_the_webhook_path_is_excluded_from_csrf_verification(): void
    {
        $property = new ReflectionProperty(PreventRequestForgery::class, 'neverVerify');
        $property->setAccessible(true);

        $excluded = array_map(
            fn (string $uri): string => str_replace('\/', '/', $uri),
            $property->getValue(),
        );

        $this->assertContains(config('payments.webhook.path'), $excluded);
    }
}
