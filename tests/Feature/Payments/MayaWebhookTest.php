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
 * The Maya callback is only a notification that something happened.
 *
 * Every test here asserts the same rule from a different angle: local state is
 * only allowed to change when Maya's own secret-key endpoint agrees on the
 * status, amount, currency, and reference. The payload is never trusted.
 */
class MayaWebhookTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'payments.gateway' => 'maya',
            'payments.maya.secret_key' => 'maya-secret-for-tests',
        ]);
    }

    /** @return array{Reservation, Payment} */
    private function pendingMayaPayment(): array
    {
        $court = Court::factory()->create(['hourly_rate' => 500]);
        $customer = User::factory()->create(['role' => Role::Customer]);
        $start = CarbonImmutable::now(config('reservation.timezone'))->addDays(3)->setTime(10, 0);

        $reservation = Reservation::create([
            'reference_number' => 'RSRV-MAYA-1',
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
            'provider' => 'maya',
            'provider_checkout_session_id' => 'maya-payment-123',
            'provider_metadata' => ['maya_request_reference' => 'RSRV-MAYA-1-ATTEMPT'],
            'amount' => 1000,
            'currency' => 'PHP',
            'status' => PaymentStatus::Pending,
        ]);

        return [$reservation, $payment];
    }

    /**
     * Stand in for Maya's secret-key payment lookup.
     *
     * @param  array<string, mixed>  $overrides
     */
    private function stubMayaPayment(array $overrides = []): void
    {
        Http::fake([
            'https://pg-sandbox.paymaya.com/payments/v1/payments/maya-payment-123' => Http::response(array_merge([
                'id' => 'maya-payment-123',
                'status' => 'PAYMENT_SUCCESS',
                'amount' => '1000.00',
                'currency' => 'PHP',
                'requestReferenceNumber' => 'RSRV-MAYA-1-ATTEMPT',
            ], $overrides)),
        ]);
    }

    /** @param array<string, mixed> $payload */
    private function sendWebhook(array $payload = ['id' => 'maya-payment-123'])
    {
        return $this->postJson(route('webhooks.maya'), $payload);
    }

    public function test_a_maya_webhook_confirms_only_a_payment_verified_by_maya(): void
    {
        Queue::fake();
        [$reservation, $payment] = $this->pendingMayaPayment();
        $this->stubMayaPayment();

        $this->sendWebhook(['id' => 'maya-payment-123', 'paymentStatus' => 'PAYMENT_SUCCESS'])
            ->assertOk();

        $this->assertSame(ReservationStatus::Confirmed, $reservation->fresh()->status);
        $this->assertSame(PaymentStatus::Paid, $payment->fresh()->status);
        $this->assertNotNull($payment->fresh()->paid_at);
        $this->assertSame(1, Receipt::count());
        Queue::assertPushed(SendConfirmationEmail::class, 1);
    }

    public function test_a_duplicate_maya_webhook_issues_one_receipt_and_one_email(): void
    {
        Queue::fake();
        [$reservation] = $this->pendingMayaPayment();
        $this->stubMayaPayment();

        $this->sendWebhook()->assertOk();
        $this->sendWebhook()->assertOk();

        $this->assertSame(1, Receipt::count());
        $this->assertSame(1, PaymentEvent::where('provider_event_id', 'maya-payment-123')->count());
        Queue::assertPushed(SendConfirmationEmail::class, 1);
        $this->assertSame(ReservationStatus::Confirmed, $reservation->fresh()->status);
    }

    public function test_a_confirmed_payment_issues_exactly_one_receipt(): void
    {
        Queue::fake();
        [$reservation] = $this->pendingMayaPayment();
        $this->stubMayaPayment();

        $this->sendWebhook()->assertOk();

        $receipt = Receipt::where('reservation_id', $reservation->id)->firstOrFail();

        $this->assertSame($reservation->reference_number, $receipt->reservation_reference);
        $this->assertSame('1000.00', $receipt->amount_paid);
        $this->assertSame('PHP', $receipt->currency);
    }

    public function test_the_receipt_captures_an_immutable_snapshot(): void
    {
        Queue::fake();
        [$reservation] = $this->pendingMayaPayment();
        $courtName = $reservation->court->name;
        $this->stubMayaPayment();

        $this->sendWebhook()->assertOk();

        $receipt = Receipt::firstOrFail();

        // Renaming the court afterwards must not rewrite an issued receipt.
        $reservation->court->update(['name' => 'Renamed Court']);

        $this->assertSame($courtName, $receipt->fresh()->court_name);
    }

    public function test_a_forged_callback_is_rejected_because_maya_does_not_confirm_it(): void
    {
        Queue::fake();
        [$reservation, $payment] = $this->pendingMayaPayment();

        // The attacker posts a callback claiming success. Maya still says the
        // payment is only pending, so nothing may change.
        $this->stubMayaPayment(['status' => 'PENDING']);

        $this->sendWebhook(['id' => 'maya-payment-123', 'paymentStatus' => 'PAYMENT_SUCCESS'])
            ->assertOk();

        $this->assertSame(ReservationStatus::Pending, $reservation->fresh()->status);
        $this->assertNotSame(PaymentStatus::Paid, $payment->fresh()->status);
        $this->assertSame(0, Receipt::count());
        Queue::assertNothingPushed();
    }

    public function test_an_unverifiable_payment_is_flagged_for_review_not_confirmed(): void
    {
        Queue::fake();
        [$reservation, $payment] = $this->pendingMayaPayment();

        // Maya cannot be reached. The callback must not settle the booking.
        Http::fake([
            'https://pg-sandbox.paymaya.com/*' => Http::response(status: 500),
        ]);

        $this->sendWebhook()->assertOk();

        $this->assertSame(ReservationStatus::Pending, $reservation->fresh()->status);
        $this->assertSame(PaymentStatus::RequiresReview, $payment->fresh()->status);
        $this->assertSame(0, Receipt::count());
    }

    public function test_a_rejected_callback_is_audited(): void
    {
        Queue::fake();
        $this->pendingMayaPayment();

        $this->sendWebhook(['paymentStatus' => 'PAYMENT_SUCCESS']);

        $this->assertDatabaseHas('audit_logs', ['action' => 'payment.webhook_rejected']);
    }

    public function test_a_callback_without_a_payment_id_is_audited_and_rejected(): void
    {
        Queue::fake();
        $this->pendingMayaPayment();

        $this->sendWebhook([])->assertStatus(422);

        $this->assertDatabaseHas('audit_logs', ['action' => 'payment.webhook_rejected']);
    }

    public function test_an_amount_mismatch_is_flagged_without_confirming(): void
    {
        Queue::fake();
        [$reservation, $payment] = $this->pendingMayaPayment();
        $this->stubMayaPayment(['amount' => '1.00']);

        $this->sendWebhook()->assertOk();

        $this->assertSame(ReservationStatus::Pending, $reservation->fresh()->status);
        $this->assertSame(PaymentStatus::RequiresReview, $payment->fresh()->status);
        $this->assertSame(0, Receipt::count());
        Queue::assertNothingPushed();
    }

    public function test_a_currency_mismatch_is_flagged_without_confirming(): void
    {
        Queue::fake();
        [$reservation, $payment] = $this->pendingMayaPayment();
        $this->stubMayaPayment(['currency' => 'USD']);

        $this->sendWebhook()->assertOk();

        $this->assertSame(ReservationStatus::Pending, $reservation->fresh()->status);
        $this->assertSame(PaymentStatus::RequiresReview, $payment->fresh()->status);
    }

    public function test_a_reference_mismatch_is_flagged_without_confirming(): void
    {
        Queue::fake();
        [$reservation, $payment] = $this->pendingMayaPayment();

        // Maya confirms a different booking than the one we asked about.
        $this->stubMayaPayment(['requestReferenceNumber' => 'RSRV-SOMEONE-ELSE-ATTEMPT']);

        $this->sendWebhook()->assertOk();

        $this->assertSame(ReservationStatus::Pending, $reservation->fresh()->status);
        $this->assertSame(PaymentStatus::RequiresReview, $payment->fresh()->status);
    }

    public function test_an_unknown_payment_id_does_not_confirm_anything(): void
    {
        Queue::fake();
        [$reservation, $payment] = $this->pendingMayaPayment();
        $this->stubMayaPayment();

        $this->sendWebhook(['id' => 'maya-does-not-exist'])->assertOk();

        $this->assertSame(ReservationStatus::Pending, $reservation->fresh()->status);
        $this->assertSame(PaymentStatus::Pending, $payment->fresh()->status);
        $this->assertSame(0, Receipt::count());
    }

    public function test_a_late_payment_does_not_reclaim_an_expired_reservation(): void
    {
        Queue::fake();
        [$reservation, $payment] = $this->pendingMayaPayment();

        // The hold expired and the slot was released before the callback landed.
        $reservation->update([
            'status' => ReservationStatus::Expired,
            'payment_deadline' => now()->subMinutes(5),
        ]);

        $this->stubMayaPayment();

        $this->sendWebhook()->assertOk();

        $this->assertSame(ReservationStatus::Expired, $reservation->fresh()->status);
        $this->assertSame(PaymentStatus::RequiresReview, $payment->fresh()->status);
        $this->assertSame(0, Receipt::count());
        Queue::assertNothingPushed();
    }

    public function test_a_cancelled_reservation_is_not_confirmed_by_a_late_callback(): void
    {
        Queue::fake();
        [$reservation, $payment] = $this->pendingMayaPayment();

        $reservation->update(['status' => ReservationStatus::Cancelled]);

        $this->stubMayaPayment();

        $this->sendWebhook()->assertOk();

        $this->assertSame(ReservationStatus::Cancelled, $reservation->fresh()->status);
        $this->assertSame(PaymentStatus::RequiresReview, $payment->fresh()->status);
    }

    public function test_the_event_is_recorded_for_audit(): void
    {
        Queue::fake();
        $this->pendingMayaPayment();
        $this->stubMayaPayment();

        $this->sendWebhook()->assertOk();

        $this->assertDatabaseHas('payment_events', [
            'provider' => 'maya',
            'provider_event_id' => 'maya-payment-123',
            'processing_status' => 'processed',
        ]);
    }

    public function test_raw_payload_is_not_stored(): void
    {
        Queue::fake();
        $this->pendingMayaPayment();
        $this->stubMayaPayment();

        $this->sendWebhook(['id' => 'maya-payment-123', 'payerName' => 'Juan Dela Cruz']);

        $event = PaymentEvent::latest('id')->firstOrFail();

        $this->assertNull($event->payload, 'Raw provider payloads may contain billing details.');
    }

    public function test_the_webhook_requires_no_authentication(): void
    {
        Queue::fake();
        $this->pendingMayaPayment();
        $this->stubMayaPayment();

        // No actingAs: Maya is an external server.
        $this->sendWebhook()->assertOk();
    }

    /**
     * Maya never receives a CSRF token, so the webhook path must be excluded from
     * request forgery verification.
     *
     * bootstrap/app.php resolves that path from the environment before the config
     * repository is bound, so a stale variable name there silently starts returning
     * 419 and no callback can ever confirm a booking again. A request based test
     * cannot catch that: the framework short circuits the check whenever it is
     * running tests, so the request passes either way. This asserts the registered
     * exclusion itself.
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

    public function test_a_browser_redirect_alone_does_not_confirm(): void
    {
        Queue::fake();
        [$reservation, $payment] = $this->pendingMayaPayment();

        // This is the URL Maya redirects to after the customer pays.
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
        $this->pendingMayaPayment();
        $this->stubMayaPayment(['amount' => '1.00']);

        $this->sendWebhook()->assertOk();

        Queue::assertNothingPushed();
    }

    public function test_confirmation_job_emails_payment_and_receipt_details_to_the_customer(): void
    {
        Queue::fake();
        Notification::fake();
        [$reservation] = $this->pendingMayaPayment();
        $this->stubMayaPayment();

        $this->sendWebhook()->assertOk();

        $receipt = Receipt::where('reservation_id', $reservation->id)->firstOrFail();

        (new SendConfirmationEmail($reservation->id))->handle();

        Notification::assertSentTo(
            $reservation->user,
            ReservationConfirmed::class,
            function (ReservationConfirmed $notification) use ($reservation, $receipt): bool {
                $mail = $notification->toMail($reservation->user);

                return $mail->subject === "Reservation confirmed — {$reservation->reference_number}"
                    && in_array('Amount paid: PHP 1,000.00', $mail->introLines, true)
                    && in_array('Payment method: Online', $mail->introLines, true)
                    && in_array('Payment status: Paid', $mail->introLines, true)
                    && in_array('Receipt number: '.$receipt->receipt_number, $mail->introLines, true);
            },
        );
    }
}
