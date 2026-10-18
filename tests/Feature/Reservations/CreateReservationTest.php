<?php

namespace Tests\Feature\Reservations;

use App\Actions\Reservations\CreateReservation;
use App\Enums\PaymentMethod;
use App\Enums\ReservationStatus;
use App\Enums\Role;
use App\Exceptions\ScheduleConflictException;
use App\Models\Court;
use App\Models\Payment;
use App\Models\Reservation;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CreateReservationTest extends TestCase
{
    use RefreshDatabase;

    private function court(array $attributes = []): Court
    {
        return Court::factory()->create($attributes + ['hourly_rate' => 500]);
    }

    /**
     * @return array{0: CarbonImmutable, 1: CarbonImmutable}
     */
    private function slot(string $time = '10:00', int $hours = 2): array
    {
        $start = CarbonImmutable::now(config('reservation.timezone'))->addDays(2)->setTimeFromTimeString($time);

        return [$start, $start->addHours($hours)];
    }

    private function book(User $customer, Court $court, ?string $time = null, int $hours = 2): Reservation
    {
        [$start, $end] = $this->slot($time ?? '10:00', $hours);

        return app(CreateReservation::class)->handle($customer, $court, $start, $end, PaymentMethod::Online);
    }

    public function test_a_valid_reservation_is_created_as_pending(): void
    {
        $customer = User::factory()->create(['role' => Role::Customer]);
        $court = $this->court();

        $reservation = $this->book($customer, $court);

        $this->assertSame(ReservationStatus::Pending, $reservation->status);
        $this->assertNotEmpty($reservation->reference_number);
        $this->assertSame($customer->id, $reservation->user_id);
        $this->assertSame($court->id, $reservation->court_id);
        $this->assertNotNull($reservation->payment_deadline);
    }

    public function test_a_pending_payment_record_is_created_with_the_reservation(): void
    {
        $customer = User::factory()->create(['role' => Role::Customer]);
        $reservation = $this->book($customer, $this->court());

        $payment = Payment::where('reservation_id', $reservation->id)->firstOrFail();

        $this->assertSame(PaymentMethod::Online, $payment->method);
        $this->assertSame($reservation->total_amount, $payment->amount);
        $this->assertSame('paymongo', $payment->provider);
    }

    public function test_the_total_is_calculated_from_duration_and_rate(): void
    {
        $customer = User::factory()->create(['role' => Role::Customer]);
        $court = $this->court(['hourly_rate' => 600]);

        $reservation = $this->book($customer, $court, '10:00', 3);

        $this->assertSame('1800.00', $reservation->total_amount, '3 hours at 600 should be 1800.00');
    }

    public function test_the_booked_rate_is_locked_against_later_rate_changes(): void
    {
        $customer = User::factory()->create(['role' => Role::Customer]);
        $court = $this->court(['hourly_rate' => 500]);

        $reservation = $this->book($customer, $court);

        $court->update(['hourly_rate' => 9999]);

        $reservation->refresh();

        $this->assertSame('500.00', $reservation->hourly_rate);
        $this->assertSame('1000.00', $reservation->total_amount);
    }

    public function test_reference_numbers_are_unique(): void
    {
        $customer = User::factory()->create(['role' => Role::Customer]);
        $courtA = $this->court();
        $courtB = $this->court();

        $first = $this->book($customer, $courtA, '10:00');
        $second = $this->book($customer, $courtB, '14:00');

        $this->assertNotSame($first->reference_number, $second->reference_number);
    }

    public function test_an_overlapping_reservation_is_rejected(): void
    {
        $customer = User::factory()->create(['role' => Role::Customer]);
        $court = $this->court();

        $this->book($customer, $court, '10:00', 2);

        $this->expectException(ScheduleConflictException::class);

        $this->book($customer, $court, '11:00', 2);
    }

    public function test_an_adjacent_reservation_is_accepted(): void
    {
        $customer = User::factory()->create(['role' => Role::Customer]);
        $court = $this->court();

        $this->book($customer, $court, '10:00', 2);
        $second = $this->book($customer, $court, '12:00', 2);

        $this->assertSame(ReservationStatus::Pending, $second->status);
    }

    public function test_a_blocked_interval_is_rejected(): void
    {
        $customer = User::factory()->create(['role' => Role::Customer]);
        $court = $this->court();

        [$start, $end] = $this->slot('10:00', 2);
        $court->blocks()->create(['starts_at' => $start, 'ends_at' => $end, 'reason' => 'Repainting']);

        $this->expectException(ScheduleConflictException::class);

        $this->book($customer, $court, '10:00', 2);
    }

    public function test_an_inactive_court_is_rejected(): void
    {
        $customer = User::factory()->create(['role' => Role::Customer]);

        $this->expectException(ScheduleConflictException::class);

        $this->book($customer, $this->court(['is_active' => false]));
    }

    public function test_an_expired_hold_does_not_prevent_rebooking(): void
    {
        $customer = User::factory()->create(['role' => Role::Customer]);
        $court = $this->court();

        [$start, $end] = $this->slot('10:00', 2);

        // Simulate a hold whose deadline passed without the sweeper running.
        $stale = $this->book($customer, $court, '10:00', 2);
        $stale->update(['payment_deadline' => now()->subMinutes(30)]);

        $fresh = app(CreateReservation::class)
            ->handle($customer, $court, $start, $end, PaymentMethod::Online);

        $this->assertSame(ReservationStatus::Expired, $stale->fresh()->status);
        $this->assertSame(ReservationStatus::Pending, $fresh->status);
    }

    public function test_the_database_rejects_a_directly_inserted_overlap(): void
    {
        $customer = User::factory()->create(['role' => Role::Customer]);
        $court = $this->court();

        $this->book($customer, $court, '10:00', 2);

        [$start, $end] = $this->slot('11:00', 2);

        // Bypass the application entirely to prove the constraint holds even
        // when two requests race past the PHP checks.
        $this->expectException(\PDOException::class);

        DB::table('reservations')->insert([
            'reference_number' => 'RSRV-DIRECT-OVERLAP',
            'user_id' => $customer->id,
            'court_id' => $court->id,
            'starts_at' => $start,
            'ends_at' => $end,
            'hourly_rate' => 500,
            'total_amount' => 1000,
            'status' => ReservationStatus::Pending->value,
            'payment_method' => PaymentMethod::Online->value,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_two_sequential_booking_attempts_on_the_same_slot_yield_one_reservation(): void
    {
        $customer = User::factory()->create(['role' => Role::Customer]);
        $court = $this->court();

        $this->book($customer, $court, '10:00', 2);

        try {
            $this->book($customer, $court, '10:00', 2);
        } catch (ScheduleConflictException) {
            // expected
        }

        $this->assertSame(
            1,
            $court->reservations()->count(),
            'Only one reservation may occupy a court at a time.',
        );
    }
}
