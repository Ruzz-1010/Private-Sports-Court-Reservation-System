<?php

namespace App\Enums;

/**
 * Payment attempt states, tracked separately from ReservationStatus.
 *
 * A reservation may accumulate multiple attempts. Only Paid attempts that are
 * not duplicates or refunds may count toward collected-payment reports.
 */
enum PaymentStatus: string
{
    case Pending = 'pending';
    case Paid = 'paid';
    case Failed = 'failed';
    case Cancelled = 'cancelled';
    case Refunded = 'refunded';

    /**
     * Amount, currency, reference, or hold state did not match expectations and
     * a human must resolve it. Includes late payments arriving after expiry.
     */
    case RequiresReview = 'requires_review';

    /**
     * Determine whether this attempt may be counted as collected revenue.
     */
    public function countsAsRevenue(): bool
    {
        return $this === self::Paid;
    }
}
