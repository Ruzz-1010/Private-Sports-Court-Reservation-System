<?php

namespace Tests\Feature\Payments;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\ReservationStatus;
use App\Enums\Role;
use App\Jobs\SendConfirmationEmail;
use App\Models\Court;
use App\Models\Payment;
use App\Models\Receipt;
use App\Models\Reservation;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class OnSitePaymentVerificationTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => Role::Admin]);
    }

    private function customer(): User
    {
        return User::factory()->create(['role' => Role::Customer]);
    }

    private function pendingOnSitePayment(string $amount = '1000.00'): array
    {
        $court = Court::factory()->create(['hourly_rate' => 500]);
        $customer = $this->customer();
        $start = CarbonImmutable::now(config('reservation.timezone'))->addDays(3)->setTime(10, 0);

        $reservation = Reservation::create([
            'reference_number' => 'RSRV-ONSITE-'.uniqid(),
            'user_id' => $customer->id,
            'court_id' => $court->id,
            'starts_at' => $start,
            'ends_at' => $start->addHours(2),
            'hourly_rate' => 500,
            'total_amount' => $amount,
            'status' => ReservationStatus::Pending,
            'payment_method' => PaymentMethod::OnSite,
            'payment_deadline' => now()->addHours(24),
        ]);

        $payment = Payment::create([
            'reservation_id' => $reservation->id,
            'method' => PaymentMethod::OnSite,
            'amount' => $amount,
            'currency' => 'PHP',
            'status' => PaymentStatus::Pending,
        ]);

        return [$reservation, $payment];
    }

    public function test_the_verification_page_lists_pending_on_site_payments(): void
    {
        [$reservation, $payment] = $this->pendingOnSitePayment();

        $this->actingAs($this->admin())
            ->get(route('admin.payments.index'))
            ->assertOk()
            ->assertSee($reservation->reference_number)
            ->assertSee('1,000.00');
    }

    public function test_an_admin_can_verify_an_on_site_payment(): void
    {
        Queue::fake();
        $admin = $this->admin();
        [$reservation, $payment] = $this->pendingOnSitePayment();

        $this->actingAs($admin)
            ->post(route('admin.payments.verify', $payment))
            ->assertRedirect();

        $this->assertSame(ReservationStatus::Confirmed, $reservation->fresh()->status);
        $this->assertSame(PaymentStatus::Paid, $payment->fresh()->status);
        $this->assertSame($admin->id, $payment->fresh()->verified_by);
    }

    public function test_verification_issues_exactly_one_receipt(): void
    {
        Queue::fake();
        [$reservation, $payment] = $this->pendingOnSitePayment();

        $this->actingAs($this->admin())->post(route('admin.payments.verify', $payment));

        $this->assertSame(1, Receipt::where('reservation_id', $reservation->id)->count());
    }

    public function test_verification_dispatches_the_confirmation_email(): void
    {
        Queue::fake();
        [$reservation] = $this->pendingOnSitePayment();

        $this->actingAs($this->admin())->post(route('admin.payments.verify', $reservation->payments()->first()));

        Queue::assertPushed(SendConfirmationEmail::class, 1);
    }

    public function test_verification_is_audited_with_the_administering_user(): void
    {
        Queue::fake();
        $admin = $this->admin();
        [$reservation, $payment] = $this->pendingOnSitePayment();

        $this->actingAs($admin)->post(route('admin.payments.verify', $payment));

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $admin->id,
            'action' => 'payment.verified',
        ]);
    }

    public function test_customers_cannot_verify_payments(): void
    {
        Queue::fake();
        [$reservation, $payment] = $this->pendingOnSitePayment();

        $this->actingAs($this->customer())
            ->post(route('admin.payments.verify', $payment))
            ->assertForbidden();

        $this->assertSame(ReservationStatus::Pending, $reservation->fresh()->status);
        $this->assertSame(PaymentStatus::Pending, $payment->fresh()->status);
    }

    public function test_guests_cannot_verify_payments(): void
    {
        [$reservation, $payment] = $this->pendingOnSitePayment();

        $this->post(route('admin.payments.verify', $payment))->assertRedirect(route('login'));

        $this->assertSame(ReservationStatus::Pending, $reservation->fresh()->status);
    }

    public function test_an_amount_mismatch_is_flagged_instead_of_confirming(): void
    {
        Queue::fake();
        // Payment recorded at a different amount than the reservation total.
        [$reservation, $payment] = $this->pendingOnSitePayment('1000.00');
        $payment->update(['amount' => '850.00']);

        $this->actingAs($this->admin())->post(route('admin.payments.verify', $payment));

        $this->assertSame(ReservationStatus::Pending, $reservation->fresh()->status);
        $this->assertSame(PaymentStatus::RequiresReview, $payment->fresh()->status);
        $this->assertSame(0, Receipt::count());
    }

    public function test_an_already_settled_payment_cannot_be_verified_twice(): void
    {
        Queue::fake();
        [$reservation, $payment] = $this->pendingOnSitePayment();
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('admin.payments.verify', $payment));
        $this->actingAs($admin)->post(route('admin.payments.verify', $payment));

        $this->assertSame(1, Receipt::where('reservation_id', $reservation->id)->count());
    }

    public function test_an_online_payment_cannot_be_verified_as_on_site(): void
    {
        Queue::fake();
        [$reservation, $payment] = $this->pendingOnSitePayment();
        $payment->update(['method' => PaymentMethod::Online]);

        $this->actingAs($this->admin())
            ->post(route('admin.payments.verify', $payment))
            ->assertSessionHasErrors('payment');

        $this->assertSame(ReservationStatus::Pending, $reservation->fresh()->status);
    }

    public function test_rejecting_a_payment_requires_a_reason(): void
    {
        [$reservation, $payment] = $this->pendingOnSitePayment();

        $this->actingAs($this->admin())
            ->post(route('admin.payments.reject', $payment))
            ->assertSessionHasErrors('reason');

        $this->assertSame(PaymentStatus::Pending, $payment->fresh()->status);
    }

    public function test_an_admin_can_reject_an_on_site_payment(): void
    {
        [$reservation, $payment] = $this->pendingOnSitePayment();

        $this->actingAs($this->admin())
            ->post(route('admin.payments.reject', $payment), ['reason' => 'Customer did not pay.'])
            ->assertRedirect();

        $this->assertSame(PaymentStatus::Failed, $payment->fresh()->status);
        // The reservation stays pending so the customer may still pay.
        $this->assertSame(ReservationStatus::Pending, $reservation->fresh()->status);
    }

    public function test_an_expired_reservation_cannot_be_verified(): void
    {
        Queue::fake();
        [$reservation, $payment] = $this->pendingOnSitePayment();

        $reservation->update([
            'status' => ReservationStatus::Expired,
            'payment_deadline' => now()->subMinute(),
        ]);

        $this->actingAs($this->admin())
            ->post(route('admin.payments.verify', $payment))
            ->assertSessionHasErrors('payment');

        $this->assertSame(ReservationStatus::Expired, $reservation->fresh()->status);
        $this->assertSame(0, Receipt::count());
    }
}
