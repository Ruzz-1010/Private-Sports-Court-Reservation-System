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
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use RuntimeException;
use Tests\TestCase;

class DemoPaymentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['payments.gateway' => 'demo']);
    }

    private function checkout(): array
    {
        $customer = User::factory()->create(['role' => Role::Customer]);
        $court = Court::factory()->create(['hourly_rate' => '500.00']);
        $start = CarbonImmutable::now(config('reservation.timezone'))->addDays(3)->setTime(10, 0);
        $reservation = Reservation::create([
            'reference_number' => 'RSRV-DEMO-'.uniqid(),
            'user_id' => $customer->id,
            'court_id' => $court->id,
            'starts_at' => $start,
            'ends_at' => $start->addHours(2),
            'hourly_rate' => '500.00',
            'total_amount' => '1000.00',
            'status' => ReservationStatus::Pending,
            'payment_method' => PaymentMethod::Online,
            'payment_deadline' => now()->addMinutes(15),
        ]);
        $payment = Payment::create([
            'reservation_id' => $reservation->id,
            'method' => PaymentMethod::Online,
            'provider' => 'paymongo_demo',
            'amount' => '1000.00',
            'currency' => 'PHP',
            'status' => PaymentStatus::Pending,
        ]);

        return [$customer, $reservation, $payment];
    }

    public function test_demo_confirmation_creates_receipt_and_dispatches_email_once(): void
    {
        Queue::fake();
        [$customer, $reservation, $payment] = $this->checkout();

        $this->actingAs($customer)
            ->post(route('customer.payments.demo.simulate', $reservation))
            ->assertRedirect(route('customer.reservations.show', $reservation))
            ->assertSessionHasNoErrors();

        $this->assertSame(PaymentStatus::Paid, $payment->fresh()->status);
        $this->assertSame(ReservationStatus::Confirmed, $reservation->fresh()->status);
        $this->assertSame(1, Receipt::where('reservation_id', $reservation->id)->count());
        Queue::assertPushed(SendConfirmationEmail::class, fn ($job) => $job->reservationId === $reservation->id);

        $this->post(route('customer.payments.demo.simulate', $reservation))->assertSessionHasErrors('payment');
        $this->assertSame(1, Receipt::where('reservation_id', $reservation->id)->count());
        Queue::assertPushed(SendConfirmationEmail::class, 1);
    }

    public function test_expired_hold_cannot_be_simulated_even_before_the_sweeper_runs(): void
    {
        Queue::fake();
        [$customer, $reservation, $payment] = $this->checkout();
        $reservation->update(['payment_deadline' => now()->subMinute()]);

        $this->actingAs($customer)->post(route('customer.payments.demo.simulate', $reservation))
            ->assertSessionHasErrors('payment');

        $this->assertSame(PaymentStatus::Pending, $payment->fresh()->status);
        $this->assertSame(0, Receipt::count());
        Queue::assertNothingPushed();
    }

    public function test_real_provider_payment_cannot_be_confirmed_by_the_demo_endpoint(): void
    {
        Queue::fake();
        [$customer, $reservation, $payment] = $this->checkout();
        $payment->update(['provider' => 'paymongo']);

        $this->actingAs($customer)->post(route('customer.payments.demo.simulate', $reservation))
            ->assertSessionHasErrors('payment');

        $this->assertSame(PaymentStatus::Pending, $payment->fresh()->status);
        Queue::assertNothingPushed();
    }

    public function test_another_customer_cannot_simulate_the_payment(): void
    {
        Queue::fake();
        [, $reservation, $payment] = $this->checkout();
        $other = User::factory()->create(['role' => Role::Customer]);

        $this->actingAs($other)->post(route('customer.payments.demo.simulate', $reservation))->assertForbidden();

        $this->assertSame(PaymentStatus::Pending, $payment->fresh()->status);
        Queue::assertNothingPushed();
    }

    public function test_demo_routes_are_disabled_when_paymongo_is_selected(): void
    {
        Queue::fake();
        [$customer, $reservation] = $this->checkout();
        config(['payments.gateway' => 'paymongo']);

        $this->actingAs($customer)->post(route('customer.payments.demo.simulate', $reservation))->assertNotFound();
        Queue::assertNothingPushed();
    }

    public function test_sync_email_failure_does_not_hide_or_reverse_confirmation(): void
    {
        config(['queue.default' => 'sync']);
        Notification::shouldReceive('send')->once()->andThrow(new RuntimeException('Mail unavailable.'));
        [$customer, $reservation, $payment] = $this->checkout();

        $this->actingAs($customer)->post(route('customer.payments.demo.simulate', $reservation))
            ->assertRedirect(route('customer.reservations.show', $reservation))
            ->assertSessionHas('status', fn ($message) => str_contains($message, 'email could not be sent'));

        $this->assertSame(ReservationStatus::Confirmed, $reservation->fresh()->status);
        $this->assertSame(PaymentStatus::Paid, $payment->fresh()->status);
        $this->assertSame(1, Receipt::where('reservation_id', $reservation->id)->count());
    }
}
