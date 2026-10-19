<?php

namespace App\Actions\Reservations;

use App\Enums\PaymentMethod;
use App\Enums\ReservationStatus;
use App\Exceptions\ScheduleConflictException;
use App\Models\Court;
use App\Models\Payment;
use App\Models\Reservation;
use App\Models\User;
use App\Services\Availability\AvailabilityService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use PDOException;

/**
 * Creates a pending reservation that holds its slot until payment is verified.
 *
 * The availability check and the insert happen inside one transaction, and the
 * database exclusion constraint is the final authority. Application checks can
 * pass for two simultaneous requests; the constraint cannot.
 */
class CreateReservation
{
    public function __construct(private readonly AvailabilityService $availability) {}

    /**
     * @throws ScheduleConflictException
     */
    public function handle(
        User $customer,
        Court $court,
        CarbonImmutable $startsAt,
        CarbonImmutable $endsAt,
        PaymentMethod $method,
    ): Reservation {
        $rate = $court->hourly_rate;

        return DB::transaction(function () use ($customer, $court, $startsAt, $endsAt, $method, $rate): Reservation {
            $check = $this->availability->confirmInterval($court, $startsAt, $endsAt);

            if (! $check['available']) {
                throw ScheduleConflictException::because($check['reason'] ?? 'That slot is no longer available.');
            }

            $reservation = new Reservation([
                'reference_number' => $this->uniqueReference(),
                'user_id' => $customer->getKey(),
                'court_id' => $court->getKey(),
                'starts_at' => $startsAt,
                'ends_at' => $endsAt,
                // Locked at booking time so later rate changes never rewrite
                // an existing reservation.
                'hourly_rate' => $rate,
                'total_amount' => $this->quoteFor($rate, $startsAt, $endsAt),
                'status' => ReservationStatus::Pending,
                'payment_method' => $method,
                'payment_deadline' => $this->availability->deadlineFor($startsAt, $method),
            ]);

            try {
                $reservation->save();
            } catch (PDOException $e) {
                // 23P01 is exclusion_violation: the database rejected an overlap.
                if ($this->isExclusionViolation($e)) {
                    throw ScheduleConflictException::because(
                        'That slot was just taken by another booking. Please choose another time.',
                    );
                }

                throw $e;
            }

            Payment::create([
                'reservation_id' => $reservation->getKey(),
                'method' => $method,
                'provider' => $method === PaymentMethod::Online ? config('payments.gateway') : null,
                'amount' => $reservation->total_amount,
                'currency' => config('reservation.currency'),
            ]);

            return $reservation;
        }, 3);
    }

    /**
     * Total charged for an interval.
     *
     * Rounded to two decimals so money is never a float artifact.
     */
    public function quoteFor(string|float $rate, CarbonImmutable $startsAt, CarbonImmutable $endsAt): string
    {
        $hours = $startsAt->diffInMinutes($endsAt) / 60;

        return number_format((float) $rate * $hours, 2, '.', '');
    }

    private function uniqueReference(): string
    {
        do {
            $reference = config('reservation.reference_prefix').'-'.now()->format('ymd').'-'.Str::upper(Str::random(6));
        } while (Reservation::where('reference_number', $reference)->exists());

        return $reference;
    }

    private function isExclusionViolation(PDOException $e): bool
    {
        return ($e->errorInfo[0] ?? null) === '23P01'
            || str_contains($e->getMessage(), 'reservations_no_overlapping_holds');
    }
}
