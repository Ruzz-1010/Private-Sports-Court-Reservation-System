<?php

namespace Tests\Feature\Reservations;

use App\Enums\PaymentMethod;
use App\Enums\ReservationStatus;
use App\Models\Court;
use App\Models\Reservation;
use App\Services\Availability\AvailabilityService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AvailabilityTest extends TestCase
{
    use RefreshDatabase;

    private function court(array $attributes = []): Court
    {
        return Court::factory()->create($attributes + ['hourly_rate' => 500]);
    }

    /**
     * A future slot inside operating hours that satisfies lead time.
     *
     * @return array{0: CarbonImmutable, 1: CarbonImmutable}
     */
    private function slot(string $time = '10:00', string $duration = 'PT2H'): array
    {
        $start = CarbonImmutable::now(config('reservation.timezone'))
            ->addDays(2)
            ->setTimeFromTimeString($time);

        return [$start, $start->add($duration)];
    }

    private function service(): AvailabilityService
    {
        return app(AvailabilityService::class);
    }

    /**
     * The next date falling on the given PHP day-of-week, at least 2 days out.
     */
    private function nextDayOfWeek(int $dayOfWeek): CarbonImmutable
    {
        $date = CarbonImmutable::now(config('reservation.timezone'))->addDays(2)->startOfDay();

        while ($date->dayOfWeek !== $dayOfWeek) {
            $date = $date->addDay();
        }

        return $date;
    }

    public function test_an_empty_court_is_available(): void
    {
        [$start, $end] = $this->slot();

        $result = $this->service()->checkInterval($this->court(), $start, $end);

        $this->assertTrue($result['available']);
        $this->assertNull($result['reason']);
    }

    public function test_a_confirmed_reservation_blocks_the_slot(): void
    {
        [$start, $end] = $this->slot();
        $court = $this->court();

        Reservation::factory()->confirmed()->create([
            'court_id' => $court->id,
            'starts_at' => $start,
            'ends_at' => $end,
        ]);

        $result = $this->service()->checkInterval($court, $start, $end);

        $this->assertFalse($result['available']);
        $this->assertStringContainsString('already reserved', $result['reason']);
    }

    public function test_an_active_pending_hold_blocks_the_slot(): void
    {
        [$start, $end] = $this->slot();
        $court = $this->court();

        Reservation::factory()->create([
            'court_id' => $court->id,
            'starts_at' => $start,
            'ends_at' => $end,
            'status' => ReservationStatus::Pending,
            'payment_deadline' => now()->addMinutes(10),
        ]);

        $result = $this->service()->checkInterval($court, $start, $end);

        $this->assertFalse($result['available']);
        $this->assertStringContainsString('pending', $result['reason']);
    }

    public function test_an_expired_pending_hold_does_not_block(): void
    {
        [$start, $end] = $this->slot();
        $court = $this->court();

        Reservation::factory()->create([
            'court_id' => $court->id,
            'starts_at' => $start,
            'ends_at' => $end,
            'status' => ReservationStatus::Pending,
            // Deadline passed but the sweeper has not run yet.
            'payment_deadline' => now()->subMinutes(5),
        ]);

        $result = $this->service()->checkInterval($court, $start, $end);

        $this->assertTrue(
            $result['available'],
            'Availability must ignore expired holds even if the scheduler is delayed.',
        );
    }

    public function test_adjacent_reservations_are_allowed(): void
    {
        $court = $this->court();

        [$startA, $endA] = $this->slot('10:00');
        [$startB, $endB] = $this->slot('12:00');

        Reservation::factory()->confirmed()->create([
            'court_id' => $court->id, 'starts_at' => $startA, 'ends_at' => $endA,
        ]);

        $this->assertTrue(
            $this->service()->checkInterval($court, $startB, $endB)['available'],
            'A booking ending at 12:00 must not block one starting at 12:00.',
        );
    }

    public function test_a_partially_overlapping_reservation_is_rejected(): void
    {
        $court = $this->court();

        [$startA, $endA] = $this->slot('10:00');
        [$startB, $endB] = $this->slot('11:00');

        Reservation::factory()->confirmed()->create([
            'court_id' => $court->id, 'starts_at' => $startA, 'ends_at' => $endA,
        ]);

        $this->assertFalse($this->service()->checkInterval($court, $startB, $endB)['available']);
    }

    public function test_a_court_block_prevents_booking(): void
    {
        $court = $this->court();
        [$start, $end] = $this->slot();

        $court->blocks()->create([
            'starts_at' => $start, 'ends_at' => $end, 'reason' => 'Maintenance',
        ]);

        $result = $this->service()->checkInterval($court, $start, $end);

        $this->assertFalse($result['available']);
        $this->assertStringContainsString('Maintenance', $result['reason']);
    }

    public function test_an_inactive_court_cannot_be_booked(): void
    {
        $court = $this->court(['is_active' => false]);
        [$start, $end] = $this->slot();

        $result = $this->service()->checkInterval($court, $start, $end);

        $this->assertFalse($result['available']);
        $this->assertStringContainsString('not currently accepting', $result['reason']);
    }

    public function test_a_start_time_in_the_past_is_rejected(): void
    {
        $court = $this->court();
        $start = CarbonImmutable::now(config('reservation.timezone'))
            ->subHours(3)->setTimeFromTimeString('10:00');

        $result = $this->service()->checkInterval($court, $start, $start->addHours(2));

        $this->assertFalse($result['available']);
        $this->assertStringContainsString('already passed', $result['reason']);
    }

    public function test_a_duration_below_the_minimum_is_rejected(): void
    {
        $court = $this->court();
        [$start] = $this->slot('10:00', 'PT30M');

        $result = $this->service()->checkInterval($court, $start, $start->addMinutes(30));

        $this->assertFalse($result['available']);
        $this->assertStringContainsString('minimum booking duration', $result['reason']);
    }

    public function test_a_duration_above_the_maximum_is_rejected(): void
    {
        $court = $this->court();
        [$start] = $this->slot('10:00', 'PT6H');

        $result = $this->service()->checkInterval($court, $start, $start->addHours(6));

        $this->assertFalse($result['available']);
        $this->assertStringContainsString('maximum booking duration', $result['reason']);
    }

    public function test_a_start_time_off_the_increment_is_rejected(): void
    {
        $court = $this->court();
        $start = CarbonImmutable::now(config('reservation.timezone'))->addDays(2)->setTime(10, 7);

        $result = $this->service()->checkInterval($court, $start, $start->addHours(2));

        $this->assertFalse($result['available']);
        $this->assertStringContainsString('increment', $result['reason']);
    }

    public function test_a_booking_outside_operating_hours_is_rejected(): void
    {
        $court = $this->court();
        $start = CarbonImmutable::now(config('reservation.timezone'))->addDays(2)->setTime(5, 0);

        $result = $this->service()->checkInterval($court, $start, $start->addHours(2));

        $this->assertFalse($result['available']);
        $this->assertStringContainsString('operating hours', $result['reason']);
    }

    public function test_a_booking_on_a_closed_day_is_rejected(): void
    {
        $court = $this->court();
        $start = $this->nextDayOfWeek(0)->setTime(10, 0);

        $this->assertSame(0, $start->dayOfWeek, 'Guard: expected a Sunday.');

        $result = $this->service()->checkInterval($court, $start, $start->addHours(2));

        $this->assertFalse($result['available']);
        $this->assertStringContainsString('closed', $result['reason']);
    }

    public function test_a_booking_beyond_the_advance_window_is_rejected(): void
    {
        $court = $this->court();

        // Keep the date inside the advance window but on an open day so the
        // rejection is about the booking horizon, not the closure rule.
        $start = $this->nextDayOfWeek(1)
            ->addDays((int) config('reservation.advance_booking_days'))
            ->setTime(10, 0);

        $this->assertNotContains($start->dayOfWeek, (array) config('reservation.closed_days'));

        $result = $this->service()->checkInterval($court, $start, $start->addHours(2));

        $this->assertFalse($result['available']);
        $this->assertStringContainsString('days in advance', $result['reason']);
    }

    public function test_slots_report_pending_and_reserved_states(): void
    {
        $court = $this->court();
        $date = CarbonImmutable::now(config('reservation.timezone'))->addDays(2)->startOfDay();

        [$pendingStart] = $this->slot('10:00');
        Reservation::factory()->create([
            'court_id' => $court->id,
            'starts_at' => $pendingStart,
            'ends_at' => $pendingStart->addHours(2),
            'status' => ReservationStatus::Pending,
            'payment_deadline' => now()->addMinutes(10),
        ]);

        [$reservedStart] = $this->slot('14:00');
        Reservation::factory()->confirmed()->create([
            'court_id' => $court->id,
            'starts_at' => $reservedStart,
            'ends_at' => $reservedStart->addHours(2),
        ]);

        $slots = $this->service()->slotsFor($court, $date);
        $byTime = collect($slots)->keyBy(fn ($s) => CarbonImmutable::parse($s['start'])->format('H:i'));

        $this->assertSame(AvailabilityService::PENDING, $byTime['10:00']['status']);
        $this->assertSame(AvailabilityService::PENDING, $byTime['11:00']['status']);
        $this->assertSame(AvailabilityService::RESERVED, $byTime['14:00']['status']);
        $this->assertSame(AvailabilityService::AVAILABLE, $byTime['18:00']['status']);
    }

    public function test_slots_are_empty_on_a_closed_day(): void
    {
        $sunday = $this->nextDayOfWeek(0);

        $this->assertSame([], $this->service()->slotsFor($this->court(), $sunday));
    }

    public function test_the_sweeper_expires_overdue_holds_only(): void
    {
        $court = $this->court();

        $overdue = Reservation::factory()->create([
            'court_id' => $court->id,
            'status' => ReservationStatus::Pending,
            'starts_at' => now()->addDays(2)->setTime(10, 0),
            'ends_at' => now()->addDays(2)->setTime(12, 0),
            'payment_deadline' => now()->subMinute(),
        ]);

        $healthy = Reservation::factory()->create([
            'court_id' => $court->id,
            'status' => ReservationStatus::Pending,
            'starts_at' => now()->addDays(3)->setTime(10, 0),
            'ends_at' => now()->addDays(3)->setTime(12, 0),
            'payment_deadline' => now()->addMinutes(15),
        ]);

        $this->assertSame(1, $this->service()->expireStaleHolds());
        $this->assertSame(ReservationStatus::Expired, $overdue->fresh()->status);
        $this->assertSame(ReservationStatus::Pending, $healthy->fresh()->status);
    }

    public function test_the_sweeper_records_an_expiration_reason(): void
    {
        $reservation = Reservation::factory()->create([
            'court_id' => $this->court()->id,
            'status' => ReservationStatus::Pending,
            'starts_at' => now()->addDays(2)->setTime(10, 0),
            'ends_at' => now()->addDays(2)->setTime(12, 0),
            'payment_deadline' => now()->subMinute(),
        ]);

        $this->service()->expireStaleHolds();

        $this->assertNotNull($reservation->fresh()->expiration_reason);
    }

    public function test_online_holds_are_shorter_than_on_site_holds(): void
    {
        $start = CarbonImmutable::now(config('reservation.timezone'))->addDays(2)->setTime(10, 0);

        $online = $this->service()->deadlineFor($start, PaymentMethod::Online);
        $onSite = $this->service()->deadlineFor($start, PaymentMethod::OnSite);

        $this->assertTrue($online->lt($onSite));
    }

    public function test_a_hold_never_outlives_the_reservation_start(): void
    {
        $start = CarbonImmutable::now(config('reservation.timezone'))->addHours(2);

        $deadline = $this->service()->deadlineFor($start, PaymentMethod::OnSite);

        $this->assertLessThanOrEqual($start, $deadline);
    }
}
