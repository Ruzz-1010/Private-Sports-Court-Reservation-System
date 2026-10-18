<?php

namespace App\Services\Availability;

use App\Enums\PaymentMethod;
use App\Enums\ReservationStatus;
use App\Models\Court;
use App\Models\CourtBlock;
use App\Models\Reservation;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Collection;

/**
 * Answers "is this slot bookable?" and explains why not.
 *
 * Availability is always derived from stored records. It is never cached in a
 * single status column on a court, because availability changes across dates
 * and times while a court itself is either active or not.
 *
 * Intervals are half-open `[start, end)`. A booking ending at 11:00 does not
 * conflict with one starting at 11:00.
 */
class AvailabilityService
{
    /**
     * Slot states returned to the calendar.
     */
    public const AVAILABLE = 'available';

    public const RESERVED = 'reserved';

    public const PENDING = 'pending';

    public const BLOCKED = 'blocked';

    public const CLOSED = 'closed';

    public const PAST = 'past';

    /**
     * Build the slot map for one court on one date.
     *
     * @return array<string, array{start: string, end: string, status: string, reservation_id: int|null, reason: string|null}>
     */
    public function slotsFor(Court $court, CarbonInterface $date): array
    {
        $operating = $this->operatingWindow($date);

        if ($operating === null) {
            return [];
        }

        [$open, $close] = $operating;

        $slots = [];
        $increment = (int) config('reservation.increment_minutes');

        for ($cursor = $open; $cursor < $close; $cursor = $cursor->addMinutes($increment)) {
            $end = $cursor->addMinutes($increment);

            $status = match (true) {
                $cursor->isPast() => self::PAST,
                default => $this->statusForInterval($court, $cursor, $end),
            };

            $slots[] = [
                'start' => $cursor->toIso8601String(),
                'end' => $end->toIso8601String(),
                'status' => $status,
                'reservation_id' => $status === self::PENDING || $status === self::RESERVED
                    ? $this->reservationIdFor($court, $cursor, $end)
                    : null,
                'reason' => $status === self::BLOCKED
                    ? $this->blockReasonFor($court, $cursor, $end)
                    : null,
            ];
        }

        return $slots;
    }

    /**
     * Determine whether an interval can be booked, and why not if it cannot.
     *
     * @return array{available: bool, reason: string|null}
     */
    public function checkInterval(Court $court, CarbonInterface $start, CarbonInterface $end): array
    {
        $rules = $this->validateInterval($start, $end);

        if ($rules['reason'] !== null) {
            return ['available' => false, 'reason' => $rules['reason']];
        }

        if (! $court->is_active) {
            return ['available' => false, 'reason' => 'This court is not currently accepting bookings.'];
        }

        $conflicts = $this->conflictsFor($court, $start, $end);

        if ($conflicts !== []) {
            return ['available' => false, 'reason' => $conflicts[0]];
        }

        return ['available' => true, 'reason' => null];
    }

    /**
     * Confirm the slot is still free, expiring any stale holds first.
     *
     * Called inside the booking transaction. The calendar is advisory, so this
     * is the authoritative check. Expiring here means a scheduler that has not
     * run yet cannot keep a dead hold blocking the court.
     *
     * @return array{available: bool, reason: string|null}
     */
    public function confirmInterval(Court $court, CarbonInterface $start, CarbonInterface $end): array
    {
        $this->expireStaleHolds();

        return $this->checkInterval($court, $start, $end);
    }

    /**
     * Mark every pending reservation whose deadline has passed as expired.
     *
     * @return int number of reservations expired
     */
    public function expireStaleHolds(): int
    {
        return Reservation::query()
            ->overdue()
            ->update([
                'status' => ReservationStatus::Expired->value,
                'expiration_reason' => 'Payment deadline passed before payment was verified.',
                'updated_at' => now(),
            ]);
    }

    /**
     * Validate duration and operating hours before touching the database.
     *
     * @return array{reason: string|null}
     */
    public function validateInterval(CarbonInterface $start, CarbonInterface $end): array
    {
        if ($end->lessThanOrEqualTo($start)) {
            return ['reason' => 'The end time must be after the start time.'];
        }

        if ($start->isPast()) {
            return ['reason' => 'That start time has already passed.'];
        }

        $minutes = $start->diffInMinutes($end);

        if ($minutes < (int) config('reservation.min_duration_minutes')) {
            return ['reason' => 'The minimum booking duration is '.config('reservation.min_duration_minutes').' minutes.'];
        }

        if ($minutes > (int) config('reservation.max_duration_minutes')) {
            return ['reason' => 'The maximum booking duration is '.config('reservation.max_duration_minutes').' minutes.'];
        }

        $increment = (int) config('reservation.increment_minutes');

        if ($start->minute % $increment !== 0) {
            return ['reason' => "The start time must align to a {$increment}-minute increment."];
        }

        if ($minutes % $increment !== 0) {
            return ['reason' => "The duration must be a multiple of {$increment} minutes."];
        }

        if (! $this->isFacilityOpen($start)) {
            return ['reason' => 'The facility is closed on that day.'];
        }

        $window = $this->operatingWindow($start);

        if ($window === null || $start->lt($window[0]) || $end->gt($window[1])) {
            return ['reason' => 'That time falls outside the facility operating hours.'];
        }

        $latest = now()->addDays((int) config('reservation.advance_booking_days'));
        if ($start->gt($latest)) {
            return ['reason' => 'Bookings open only '.config('reservation.advance_booking_days').' days in advance.'];
        }

        $leadTime = now()->addMinutes((int) config('reservation.lead_time_minutes'));
        if ($start->lt($leadTime)) {
            return ['reason' => 'Bookings must be made at least '.config('reservation.lead_time_minutes').' minutes in advance.'];
        }

        return ['reason' => null];
    }

    /**
     * Determine the payment deadline for a new hold.
     */
    public function deadlineFor(CarbonInterface $start, PaymentMethod $method): CarbonInterface
    {
        $minutes = (int) config('reservation.hold_minutes.'.($method === PaymentMethod::Online ? 'online' : 'on_site'));

        $deadline = now()->addMinutes($minutes);

        // A hold never outlives the slot it protects.
        return $deadline->greaterThan($start) ? $start : $deadline;
    }

    /**
     * Reason strings for every record that overlaps the interval.
     *
     * @return list<string>
     */
    private function conflictsFor(Court $court, CarbonInterface $start, CarbonInterface $end): array
    {
        $reasons = [];

        $blocking = Reservation::query()
            ->where('court_id', $court->getKey())
            ->blocking()
            ->where('starts_at', '<', $end)
            ->where('ends_at', '>', $start)
            ->get();

        foreach ($blocking as $reservation) {
            // A pending hold whose deadline has passed must not block, because
            // the exclusion constraint cannot use now() in its predicate.
            if ($reservation->status === ReservationStatus::Pending && ! $reservation->holdIsActive()) {
                continue;
            }

            $reasons[] = $reservation->status === ReservationStatus::Pending
                ? 'That slot is currently held by another pending booking.'
                : 'That slot is already reserved.';
        }

        foreach ($this->blocksFor($court, $start, $end) as $block) {
            $reasons[] = "Unavailable: {$block->reason}.";
        }

        return array_values(array_unique($reasons));
    }

    /**
     * @return Collection<int, CourtBlock>
     */
    private function blocksFor(Court $court, CarbonInterface $start, CarbonInterface $end)
    {
        return CourtBlock::query()
            ->where('court_id', $court->getKey())
            ->where('starts_at', '<', $end)
            ->where('ends_at', '>', $start)
            ->get();
    }

    private function reservationIdFor(Court $court, CarbonInterface $start, CarbonInterface $end): ?int
    {
        return Reservation::query()
            ->where('court_id', $court->getKey())
            ->blocking()
            ->where('starts_at', '<', $end)
            ->where('ends_at', '>', $start)
            ->orderBy('starts_at')
            ->value('id');
    }

    private function blockReasonFor(Court $court, CarbonInterface $start, CarbonInterface $end): ?string
    {
        return $this->blocksFor($court, $start, $end)->first()?->reason;
    }

    private function statusForInterval(Court $court, CarbonInterface $start, CarbonInterface $end): string
    {
        if ($this->blocksFor($court, $start, $end)->isNotEmpty()) {
            return self::BLOCKED;
        }

        $reservation = Reservation::query()
            ->where('court_id', $court->getKey())
            ->blocking()
            ->where('starts_at', '<', $end)
            ->where('ends_at', '>', $start)
            ->orderByRaw("CASE WHEN status = 'pending' THEN 0 ELSE 1 END")
            ->first();

        if ($reservation === null) {
            return self::AVAILABLE;
        }

        if ($reservation->status === ReservationStatus::Pending && ! $reservation->holdIsActive()) {
            return self::AVAILABLE;
        }

        return $reservation->status === ReservationStatus::Pending ? self::PENDING : self::RESERVED;
    }

    private function isFacilityOpen(CarbonInterface $date): bool
    {
        return ! in_array($date->dayOfWeek, (array) config('reservation.closed_days'), true);
    }

    /**
     * Today's or the requested day's operating window, or null when closed.
     *
     * @return array{0: CarbonImmutable, 1: CarbonImmutable}|null
     */
    private function operatingWindow(CarbonInterface $date): ?array
    {
        if (! $this->isFacilityOpen($date)) {
            return null;
        }

        $day = CarbonImmutable::parse(
            $date->toDateString(),
            config('reservation.timezone'),
        );

        return [
            $day->setTime((int) config('reservation.operating_hours.start'), 0),
            $day->setTime((int) config('reservation.operating_hours.end'), 0),
        ];
    }
}
