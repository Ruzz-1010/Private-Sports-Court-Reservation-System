<?php

namespace App\Enums;

/**
 * Reservation lifecycle states.
 *
 * Payment status is tracked separately in PaymentStatus; never infer payment
 * state from these values.
 */
enum ReservationStatus: string
{
    /** Slot is held while awaiting verified payment. */
    case Pending = 'pending';

    /** A verified payment confirmed the booking. */
    case Confirmed = 'confirmed';

    /** An authorized actor deliberately cancelled the booking. */
    case Cancelled = 'cancelled';

    /** The payment deadline passed before payment was verified. */
    case Expired = 'expired';

    /** The reserved time has passed and the booking was fulfilled. */
    case Completed = 'completed';

    /**
     * Statuses whose intervals still occupy the court schedule.
     *
     * Availability and the database overlap guard both rely on this set.
     *
     * @return list<string>
     */
    public static function blockingStatuses(): array
    {
        return [
            self::Pending->value,
            self::Confirmed->value,
            self::Completed->value,
        ];
    }

    /**
     * Determine whether a reservation in this state still holds the slot.
     */
    public function holdsSlot(): bool
    {
        return in_array($this, [
            self::Pending,
            self::Confirmed,
            self::Completed,
        ], true);
    }

    /**
     * Determine whether the reservation may still be paid for.
     */
    public function acceptsPayment(): bool
    {
        return $this === self::Pending;
    }
}
